<?php
namespace benben\db;

use benben\db\schema\CommandBuider;
use benben\db\schema\DbSchema;
use benben\log\Logger;
use benben\Benben;
use benben\base\Exception;
use benben\base\ApplicationComponent;

class DbConnection extends ApplicationComponent
{
    public $connectionString = '';
    public $username = '';
    public $password = '';
    public $charset = 'utf8';
    public $tablePrefix = '';
    
    /**
     * @var integer number of seconds that table metadata can remain valid in cache.
     * Use 0 or negative value to indicate not caching schema.
     * If greater than 0 and the primary cache is enabled, the table metadata will be cached.
     * @see schemaCachingExclude
     */
    public $schemaCachingDuration=0;
    /**
     * @var array list of tables whose metadata should NOT be cached. Defaults to empty array.
     * @see schemaCachingDuration
     */
    public $schemaCachingExclude=array();
    /**
     * @var string the ID of the cache application component that is used to cache the table metadata.
     * Defaults to 'cache' which refers to the primary cache application component.
     * Set this property to false if you want to disable caching table metadata.
    */
    public $schemaCacheID='cache';
    /**
     * the ID of the cache application component that is used for query caching.
     * Defaults to 'cache' which refers to the primary cache application component.
     * @var string
     */
    public $queryCacheID = 'cache';
    
    /**
     * number of seconds that query results can remain valid in cache.
     * Use or negative value to indicate not caching query results (the default behavior).
     * 
     * In order to enable query caching, this property must be a positive integer
     * and {@link queryCacheID} must point to a valid cache component ID.
     * 
     * The method {@link cache()} is provided as a convenient way of setting this property
     * and {@link queryCachingDependency} on the fly.
     * @var int
     */
    public $queryCacheDuration = 0;
    /**
     * the dependency that will be used when saving query results into cache.
     * @var CacheDependency
     */
    public $queryCacheDependency;
    /**
     * the number of SQL statements that need to be cached next.
     * If this is 0, then even if query cache is enabled, no query will be cached.
     * Note that each time after executeing a SQL statement (whether executed on DB server or fetched from
     * query cache), this property will be reduced by 1 until 0.
     * @var int
     */
    public $queryCacheCount=0;
    /**
     * @var boolean whether to log the values that are bound to a prepare SQL statement.
     * Defaults to false. During development, you may consider setting this property to true
     * so that parameter values bound to SQL statements are logged for debugging purpose.
     * You should be aware that logging parameter values could be expensive and have significant
     * impact on the performance of your application.
     */
    public $enableParamLogging=false;
    /**
     * @var boolean whether to enable profiling the SQL statements being executed.
     * Defaults to false. This should be mainly enabled and used during development
     * to find out the bottleneck of SQL executions.
     */
    public $enableProfiling=false;
    
    private $_pdo = null;
    private $_active = false;
    /**
     * @var DbSchema
     */
    private $_schema = null;
    private $_driverMap=array(
        'mysql'=>'benben\db\schema\mysql\MysqlSchema', 
    );
    
    public function __construct($dsn = '', $username = '', $password = '')
    {
        $this->connectionString = $dsn;
        $this->username = $username;
        $this->password = $password;
    }
    
    public function init()
    {
        parent::init();
    }
    
    /**
     * @param mixed $query
     * @return DbCommand
     */
    public function createCommand($query)
    {
        $this->setActive(true);
        return new DbCommand($this, $query);
    }
    
    /**
     * Returns the PDO instance
     * @return \PDO  the pdo instance, null if the connection is not established yet
     */
    public function getPdoInstance()
    {
        if (null===$this->_pdo)
        {
            $this->_pdo = new \PDO($this->connectionString, $this->username, $this->password);
        }
        return $this->_pdo;
    }
    
    /**
     * Returns whether the DB connection is established.
     * @return boolean whether the DB connection is established
     */
    public function getActive()
    {
    	return $this->_active;
    }
    
    /**
	 * Open or close the DB connection.
	 * @param boolean $value whether to open or close DB connection
	 * @throws Exception if connection fails
	 */
	public function setActive($value)
	{
		if($value!=$this->_active)
		{
			if($value)
				$this->open();
			else
				$this->close();
		}
	}
	
	/**
	 * @return CommandBuider
	 */
	public function getCommandBuilder()
	{
	    return $this->getSchema()->getCommandBuilder();
	}
	
	/**
	 * @return \benben\db\schema\DbSchema
	 */
	public function getSchema()
	{
	    if (null===$this->_schema)
	    {
	        $driver = $this->getDriverName();
	        if (isset($this->_driverMap[$driver]))
	        {
	            $this->_schema = Benben::createComponent($this->_driverMap[$driver], $this);
	        }
	        else
	        {
	            throw new DbException(Benben::t('benben','DbConnection does not support reading schema for {driver} database.',
					array('{driver}'=>$driver)));
	        }
	    }
	    return $this->_schema;
	}
	
	/**
	 * Returns the name of the DB driver
	 * @return string name of the DB driver
	 */
	public function getDriverName()
	{
	    if (($pos=strpos($this->connectionString, ':'))!==false)
	    {
	        return strtolower(substr($this->connectionString, 0, $pos));
	    }
	    return '';
	}
	
	/**
	 * Initializeds the open db connection.
	 * This method is invoked right after the db connection is established.
	 * The default implementation is to set the charset for MySQL.
	 * @param \PDO $pdo
	 */
	protected function initConnection($pdo)
	{
	    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
	    if (!empty($this->charset))
	    {
	        $driver = strtolower($pdo->getAttribute(\PDO::ATTR_DRIVER_NAME));
	        if (in_array($driver, array('mysql','mysqli','pgsql')))
	        {
	            $pdo->exec('SET NAMES '.$pdo->quote($this->charset));
	        }
	    }
	}
    
    /**
     * Opens DB connection if it is currently not
     * @throws Exception if connection fails
     */   
    protected function open()
    {
        if($this->_pdo===null)
		{
			if(empty($this->connectionString))
				throw new DbException('DbConnection.connectionString cannot be empty.');
			try
			{
				Benben::trace('Opening DB connection','benben.db.CDbConnection');
				$this->initConnection($this->getPdoInstance());
				$this->_active=true;
			}
			catch(\PDOException $e)
			{
				if(BENBEN_DEBUG)
				{
					throw new DbException('DbConnection failed to open the DB connection: '.
						$e->getMessage(),(int)$e->getCode(),$e->errorInfo);
				}
				else
				{
					Benben::log($e->getMessage(),Logger::LEVEL_ERROR,'exception.DbException');
					throw new DbException('DbConnection failed to open the DB connection.',(int)$e->getCode(),$e->errorInfo);
				}
			}
		}
    }
    
    protected function close()
    {
        Benben::trace('Closing DB connection','benben.db.DbConnection');
        $this->_pdo = null;
        $this->_active = false;
    }
    
    /**
     * Sets the parameters about query caching.
     * This method can be used to enable or disable query caching.
     * By setting the $duration parameter to be 0 or negative number, the query caching will be disabled.
     * Otherwise, query results of the new SQL statements executed next will be saved in cache and remain
     * valid for the specified duration.
     * If the same query is executed agin, the result may be fetched from cache directly
     * without actually executing the SQL statement.
     * @param integer $duration the number of seconds that query results may be remain valid in cache.
     * @param CacheDependency $dependency the dependency that will be used when saving the query results into cache.
     * @param integer $queryCount number of SQL queries that need to be cached after calling this method, Defaults to 1,
     * meaing that the next SQL query will be cached.
     * @return DbConnection the connection instance itself.
     */
    public function cache($duration, $dependency = null, $queryCount=1)
    {
        $this->queryCacheDuration = $duration;
        $this->queryDependency = $dependency;
        $this->queryCacheCount = $queryCount;
        return $this;
    }
    
    /**
     * Returns the ID of the last inserted row or sequence value.
     * @param string $sequenceName name of the sequence object (required by some DBMS)
     * @return string the row ID of the last row inserted, or the last value retrieved from the sequence object
     * @see http://www.php.net/manual/en/function.PDO-lastInsertId.php
     */
    public function getLastInsertID($sequenceName='')
    {
    	$this->setActive(true);
    	return $this->_pdo->lastInsertId($sequenceName);
    }
    
    /**
     * Quotes a string value for use in a query.
     * @param string $str string to be quoted
     * @return string the properly quoted string
     * @see http://www.php.net/manual/en/function.PDO-quote.php
     */
    public function quoteValue($str)
    {
    	if(is_int($str) || is_float($str))
    		return $str;
    
    	$this->setActive(true);
    	if(($value=$this->_pdo->quote($str))!==false)
    		return $value;
    	else  // the driver doesn't support quote (e.g. oci)
    		return "'" . addcslashes(str_replace("'", "''", $str), "\000\n\r\\\032") . "'";
    }
    
    /**
     * Determines the PDO type for the specified PHP type.
     * @param string $type The PHP type (obtained by gettype() call).
     * @return integer the corresponding PDO type
     */
    public function getPdoType($type)
    {
    	static $map=array
    	(
    			'boolean'=>\PDO::PARAM_BOOL,
    			'integer'=>\PDO::PARAM_INT,
    			'string'=>\PDO::PARAM_STR,
    			'NULL'=>\PDO::PARAM_NULL,
    	);
    	return isset($map[$type]) ? $map[$type] : \PDO::PARAM_STR;
    }
}