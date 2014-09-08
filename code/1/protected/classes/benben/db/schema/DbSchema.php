<?php
namespace benben\db\schema;

use benben\db\DbConnection;

use benben\base\Component;
use benben\Benben;

abstract class DbSchema extends Component
{
    /**
     * @var CommandBuider
     */    
    private $_builder = null;
    /**
     * @var DbConnection
     */
    private $_connection = null;
    private $_tables = array();
    private $_tableNames = array();
    
    /**
     * Loads the metadata for the specified table.
     * @param string $name table name
     * @return TableSchema driver dependent table metadata, null if the table does not exist.
     */
    abstract protected function loadTable($name);
    
    public function __construct($connection)
    {
        $this->_connection = $connection;
    }
    
    public function getDbConnection()
    {
        return $this->_connection;
    }
    
    /**
     * @return \benben\db\schema\CommandBuider
     */
    public function getCommandBuilder()
    {
        if(null === $this->_builder)
        {
            $this->_builder = new CommandBuider($this);
        }
        return $this->_builder;
    }
    
    /**
     * Quotes a name for use in query.
     * If the name contains prefix, the prefix will be properly quoted.
     * @param string $name the will be quoted name
	 * @return string the properly quoted name
	 * @see simpleQuote
     */
    public function quote($name)
    {
        if(strpos($name,'.')===false)
			return $this->simpleQuote($name);
		$parts=explode('.',$name);
		foreach($parts as $i=>$part)
			$parts[$i]=$this->simpleQuote($part);
		return implode('.',$parts);
    }
    
    /**
     * Quotes a simple table name or field name (eg..) for use in a query.
     * A simple name does not schema prefix.
     * @param string $name the will be quoted name
     * @return string the properly quoted name
     */
    public function simpleQuote($name)
    {
        return "`$name`";
    }
    
    /**
     * Obtains the metadata for the named table.
     * @param string $name table name
     * @param boolean $refresh if we need to refresh schema cache for a table.
     * @return TableSchema table metadata. Null if the named table does not exist.
     */
    public function getTable($name, $refresh=false)
    {
    	if($refresh===false && isset($this->_tables[$name]))
    	{
    		return $this->_tables[$name];
    	}
    	else
    	{
    		if($this->_connection->tablePrefix!==null && strpos($name,'{{')!==false)
    		{
    			$realName=preg_replace('/\{\{(.*?)\}\}/',$this->_connection->tablePrefix.'$1',$name);
    		}
    		else
    		{
    			$realName=$name;
    		}
    		// temporarily disable query caching
    		if($this->_connection->queryCachingDuration>0)
    		{
    			$qcDuration=$this->_connection->queryCachingDuration;
    			$this->_connection->queryCachingDuration=0;
    		}
    		
    		if(($duration=$this->_connection->schemaCachingDuration)>0 
    			&& $this->_connection->schemaCacheId!==false 
    			&& ($cache=Benben::app()->getComponent($this->_connection->schemaCacheId))!==null)
    		{
    			$key='benben:dbschema'.$this->_connection->connectionString.':'.$this->_connection->username.':'.$name;
    			$table=$cache->get($key);
    			if($refresh===true || $table===false)
    			{
    				$table=$this->loadTable($realName);
    				if($table!==null)
    					$cache->set($key,$table,$duration);
    			}
    			$this->_tables[$name]=$table;
    		}
    		else
    		{
    			$this->_tables[$name]=$table=$this->loadTable($realName);
    		}
    	}
    	
    	if(isset($qcDuration))  // re-enable query caching
    	{
    		$this->_connection->queryCachingDuration=$qcDuration;
    	}
    	
    	return $table;
    }
}