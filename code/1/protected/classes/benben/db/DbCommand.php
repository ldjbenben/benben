<?php
namespace benben\db;

use benben\log\Logger;

use benben\Benben;

class DbCommand
{
    private $_params = array();
    /**
     * @var DbConnection
     */
    private $_connection = null;
    /**
     * @var \PDOStatement
     */
    private $_statement = null;
    private $_query = '';
    private $_text = '';
    private $_paramLog=array();
    private $_fetchMode = \PDO::FETCH_ASSOC;
    
    public function __construct(DbConnection $connection, $query=null)
    {
        $this->_connection = $connection;
        $this->setText($query);
    }
    
    public function setText($value)
    {
    	if (!empty($this->_connection->tablePrefix) && !empty($value))
    	{
    		$this->_text = preg_replace('/{{(.*?)}}/', $this->_connection->tablePrefix.'\1', $value);
    	}
    	else
    	{
    		$this->_text = $value;
    	}
    	$this->cancel();
    	return $this;
    }
    
    public function getText()
    {
    	return $this->_text;
    }
    
    protected function prepare()
    {
        if (null===$this->_statement)
        {
            try 
            {
                $this->_statement = $this->_connection->getPdoInstance()->prepare($this->getText());
            }
            catch (\PDOException $e)
            {
                Benben::log('Error in prepareing SQL:'.$this->getQuery(), Logger::LEVEL_ERROR,'benben.db.DbCommand');
                $errorInfo = $e->errorInfo;
                throw new DbException(Benben::t('benben','DbCommand failed to prepare the SQL statement: {error}',
                       array('{error}'=>$e->getMessage())),$e->getCode(),$errorInfo);
            }
        }
    }
    
    /**
     * Cancels the execution of the SQL statement
     */
    public function cancel()
    {
        $this->_statement =null;
    }
    
    /**
     * Executes the SQL statement and returns the first row of the result.
     * @param array $params
     * @return array
     */
    public function queryRow($params = array())
    {
    	return $this->queryInternal('fetch',$this->_fetchMode, $params);
    }
    
    /**
     * Executes the SQL statement and returns the value of the first column in the first row of data.
     * @param array $params
     */
    public function queryScalar($params=array())
    {
       	return $this->queryInternal('fetchColumn',0,$params);
    }
    
    /**
     * Executes the SQL statement and returns the first column of the result.
     */
    public function queryColumn($params = array())
    {
        return $this->queryInternal('fetchAll',\PDO::FETCH_COLUMN,$params);
    }
    
    public function queryAll($params = array())
    {
        return $this->queryInternal('fetchAll',$this->_fetchMode, $params);
    }
    
    /**
     * @param string $method method of PDOStatement to be called
     * @param mixed $mode parameters to be passed to the method
     * @param array $params input parameters (name=>value) for the SQL execution. This is an alternative
	 * to {@link bindParam} and {@link bindValue}. If you have multiple input parameters, passing
	 * them in this way can improve the performance. Note that you pass parameters in this way,
	 * you cannot bind parameters or values using {@link bindParam} or {@link bindValue}, and vice versa.
	 * binding methods and  the input parameters this way can improve the performance.
	 * @return mixed the method execution result
     */
    private function queryInternal($method, $mode, $params = array())
    {
        $params = array_merge($this->_params, $params);
        Benben::trace('Querying SQL: '.$this->getText(),'benben.db.DbCommand');
        if($this->_connection->enableParamLogging && ($pars=array_merge($this->_paramLog,$params))!==array())
        {
        	$p=array();
        	foreach($pars as $name=>$value)
        		$p[$name]=$name.'='.var_export($value,true);
        	$par='. Bound with '.implode(', ',$p);
        }
        else
        	$par='';
        if(!empty($method)
                && $this->_connection->queryCacheDuration>0
                && $this->_connection->queryCacheCount>0
                && $this->_connection->queryCacheID!==false
                && ($cache=Benben::app()->getComponent($this->_connection->queryCacheID))!==null)
        {
            $this->_connection->queryCacheCount--;
            $cacheKey = 'benben:dbquery'.$this->_connection->connectionString.':'.$this->_connection->username;
            $cacheKey.=':'.$this->getText().':'.serialize($this->_params);
            if(($result=$cache->get($cacheKey))!==false)
            {
                Benben::trace('Query result found in cache','benben.db.DbCommand');
                return $result;
            }
        }
        
        try 
        {
            $this->prepare();
            if($params===array())
                $this->_statement->execute();
            else
                $this->_statement->execute($params);
            $mode=(array)$mode;
            $result = call_user_func_array(array($this->_statement, $method), $mode);
            $this->_statement->closeCursor();
            Benben::trace(Benben::t('benben', 'Load data from db'));
            if(isset($cache, $cacheKey))
            {
                $cache->set($cacheKey, $result, $this->_connection->queryCacheDuration);
            }
            return $result;
        }
        catch (\PDOException $e)
        {
            $errorInfo = $e->errorInfo;
            $message = $e->getMessage();
            Benben::log(Benben::t('benben', 'DbCommand::{method}() failed: {error}.The SQL statement executed was: {sql}.',
                array('{method}'=>$method, '{error}'=>$message, '{sql}'=>$this->getText())), Logger::LEVEL_ERROR,'benben.db.DbCommand');
            if(BENBEN_DEBUG)
            {
                $message .= '. The SQL statement executed was: '.$this->getText();
            }
            throw new DbException(Benben::t('benben', 'DbCommand failed to execute the SQL statement: {error}',
                    array('{error}'=>$message)),$e->getCode(),$errorInfo);
        }
    }
    
    /**
     * Executes the SQL statement.
     * This method is meant only for executing non-query SQL statement.
     * No result set will be returned.
     * @param array $params input parameters (name=>value) for the SQL execution. This is an alternative
     * to {@link bindParam} and {@link bindValue}. If you have multiple input parameters, passing
     * them in this way can improve the performance. Note that if you pass parameters in this way,
     * you cannot bind parameters or values using {@link bindParam} or {@link bindValue}, and vice versa.
     * binding methods and  the input parameters this way can improve the performance.
     * @return integer number of rows affected by the execution.
     * @throws Exception execution failed
     */
    public function execute($params=array())
    {
    	if($this->_connection->enableParamLogging && ($pars=array_merge($this->_paramLog,$params))!==array())
    	{
    		$p=array();
    		foreach($pars as $name=>$value)
    			$p[$name]=$name.'='.var_export($value,true);
    		$par='. Bound with ' .implode(', ',$p);
    	}
    	else
    		$par='';
    	Benben::trace('Executing SQL: '.$this->getText().$par,'benben.db.DbCommand');
    	try
    	{
    		if($this->_connection->enableProfiling)
    			Benben::beginProfile('system.db.CDbCommand.execute('.$this->getText().')','benben.db.CDbCommand.execute');
    
    		$this->prepare();
    		if($params===array())
    			$this->_statement->execute();
    		else
    			$this->_statement->execute($params);
    		$n=$this->_statement->rowCount();
    
    		if($this->_connection->enableProfiling)
    			Benben::endProfile('system.db.CDbCommand.execute('.$this->getText().')','benben.db.CDbCommand.execute');
    
    		return $n;
    	}
    	catch(\Exception $e)
    	{
    		if($this->_connection->enableProfiling)
    			Benben::endProfile('system.db.CDbCommand.execute('.$this->getText().')','benben.db.CDbCommand.execute');
    		$errorInfo = $e instanceof \PDOException ? $e->errorInfo : null;
    		$message = $e->getMessage();
    		Benben::log(Benben::t('benben','CDbCommand::execute() failed: {error}. The SQL statement executed was: {sql}.',
    		array('{error}'=>$message, '{sql}'=>$this->getText().$par)),Logger::LEVEL_ERROR,'benben.db.CDbCommand');
    		if(BENBEN_DEBUG)
    			$message .= '. The SQL statement executed was: '.$this->getText().$par;
    		throw new DbException(Benben::t('benben','DbCommand failed to execute the SQL statement: {error}',
    				array('{error}'=>$message)),(int)$e->getCode(),$errorInfo);
    	}
    }
    
    /**
     * Binds a parameter to the SQL statement to be executed.
     * @param mixed $name Parameter identifier. For a prepared statement
     * using named placeholders, this will be a parameter name of
     * the form :name. For a prepared statement using question mark
     * placeholders, this will be the 1-indexed position of the parameter.
     * @param mixed $value Name of the PHP variable to bind to the SQL statement parameter
     * @param integer $dataType SQL data type of the parameter. If null, the type is determined by the PHP type of the value.
     * @param integer $length length of the data type
     * @param mixed $driverOptions the driver-specific options (this is available since version 1.1.6)
     * @return DbCommand the current command being executed
     * @see http://www.php.net/manual/en/function.PDOStatement-bindParam.php
     */
    public function bindParam($name, &$value, $dataType=null, $length=null, $driverOptions=null)
    {
    	$this->prepare();
    	if($dataType===null)
    		$this->_statement->bindParam($name,$value,$this->_connection->getPdoType(gettype($value)));
    	else if($length===null)
    		$this->_statement->bindParam($name,$value,$dataType);
    	else if($driverOptions===null)
    		$this->_statement->bindParam($name,$value,$dataType,$length);
    	else
    		$this->_statement->bindParam($name,$value,$dataType,$length,$driverOptions);
    	$this->_paramLog[$name]=&$value;
    	return $this;
    }
    
    /**
     * Binds a value to a parameter.
     * @param mixed $name Parameter identifier. For a prepared statement
     * using named placeholders, this will be a parameter name of
     * the form :name. For a prepared statement using question mark
     * placeholders, this will be the 1-indexed position of the parameter.
     * @param mixed $value The value to bind to the parameter
     * @param integer $dataType SQL data type of the parameter. If null, the type is determined by the PHP type of the value.
     * @return DbCommand the current command being executed
     * @see http://www.php.net/manual/en/function.PDOStatement-bindValue.php
     */
    public function bindValue($name, $value, $dataType=null)
    {
    	$this->prepare();
    	if($dataType===null)
    		$this->_statement->bindValue($name,$value,$this->_connection->getPdoType(gettype($value)));
    	else
    		$this->_statement->bindValue($name,$value,$dataType);
    	$this->_paramLog[$name]=$value;
    	return $this;
    }
    
    /**
     * Resets query status
     * @return \benben\db\DbCommand
     */
    public function reset()
    {
        $this->_query = null;
        $this->_statement = null;
        $this->_openCache = false;
        return $this;
    }
    
}