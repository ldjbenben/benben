<?php
namespace benben\db\schema;

use benben\db\DbConnection;

use benben\db\DbCommand;
use benben\db\DbException;
use benben\Benben;
use benben\db\DbCriteria;

class CommandBuider
{
	const PARAM_PREFIX = ':bp';
	
    /**
     * @var DbSchema
     */
    protected $_schema = null;
    /**
     * @var DbConnection
     */
    protected $_connection = null;
    
    
    public function __construct($schema)
    {
        $this->_schema = $schema;
        $this->_connection = $schema->getDbConnection();
    }
    
    /**
     * Creates a SELECT command for a single table.
     * @param TableSchema $tabel table name
     * @param DbCriteria $criteria the query criteria
     * @param string $alias the alias name of the primary table. Defaults to 't'.
     * @return DbCommand query command.
     */
    public function createFindCommand($table, $criteria, $alias = 't')
    {
    	$this->ensureTable($table);
        $select = is_array($criteria->select) ? implode(',', $criteria->select) : $criteria->select;
        
        if(!empty($criteria->alias))
        {
            $alias = $criteria->alias;
        }
        
        $alias = $this->_schema->quote($alias);
        
        if('*'===$select && !empty($criteria->join))
        {
            $prefix=$alias.'.';
			$select=array();
			foreach($table->getColumnNames() as $name)
				$select[]=$prefix.$this->_schema->quoteColumnName($name);
			$select=implode(', ',$select);
        }
        
        $sql = ($criteria->distinct ? 'SELECT DISTINCT':'SELECT')." {$select} FROM {$table->rawName} $alias";
        $sql=$this->applyJoin($sql,$criteria->join);
		$sql=$this->applyCondition($sql,$criteria->condition);
		$sql=$this->applyGroup($sql,$criteria->group);
		$sql=$this->applyHaving($sql,$criteria->having);
		$sql=$this->applyOrder($sql,$criteria->order);
		$sql=$this->applyLimit($sql,$criteria->limit,$criteria->offset);
		$command=$this->_connection->createCommand($sql);
		$this->bindValues($command,$criteria->params);
		return $command;
    }
    
    public function createInsertCommand($table, $data)
    {
        $this->ensureTable($table);
        $fields=array();
        $values=array();
        $placeholders=array();
        $i=0;
        $table = $this->_schema->quote($table);
        foreach($data as $name=>$value)
        {
        	if(($column=$table->getColumn($name))!==null && ($value!==null || $column->allowNull))
        	{
        		$fields[]=$column->rawName;
        		if($value instanceof DbException)
        		{
        			$placeholders[]=$value->expression;
        			foreach($value->params as $n=>$v)
        				$values[$n]=$v;
        		}
        		else
        		{
        			$placeholders[]=self::PARAM_PREFIX.$i;
        			$values[self::PARAM_PREFIX.$i]=$column->typecast($value);
        			$i++;
        		}
        	}
        }
        if($fields===array())
        {
        	$pks=is_array($table->primaryKey) ? $table->primaryKey : array($table->primaryKey);
        	foreach($pks as $pk)
        	{
        		$fields[]=$table->getColumn($pk)->rawName;
        		$placeholders[]='NULL';
        	}
        }
        $sql="INSERT INTO {$table->rawName} (".implode(', ',$fields).') VALUES ('.implode(', ',$placeholders).')';
        $command=$this->_connection->createCommand($sql);
        
        foreach($values as $name=>$value)
        	$command->bindValue($name,$value);
        
        return $command;
    }
    
    /**
     * Creates an UPDATE command.
     * @param mixed $table the table schema ({@link TableSchema}) or the table name (string).
     * @param array $data list of columns to be updated (name=>value)
     * @param DbCriteria $criteria the query criteria
     * @return DbCommand update command.
     */
    public function createUpdateCommand($table,$data,$criteria)
    {
    	$this->ensureTable($table);
    	$fields=array();
    	$values=array();
    	$bindByPosition=isset($criteria->params[0]);
    	$i=0;
    	foreach($data as $name=>$value)
    	{
    		if(($column=$table->getColumn($name))!==null)
    		{
    			if($value instanceof DbExpression)
    			{
    				$fields[]=$column->rawName.'='.$value->expression;
    				foreach($value->params as $n=>$v)
    					$values[$n]=$v;
    			}
    			else if($bindByPosition)
    			{
    				$fields[]=$column->rawName.'=?';
    				$values[]=$column->typecast($value);
    			}
    			else
    			{
    				$fields[]=$column->rawName.'='.self::PARAM_PREFIX.$i;
    				$values[self::PARAM_PREFIX.$i]=$column->typecast($value);
    				$i++;
    			}
    		}
    	}
    	if($fields===array())
    		throw new DbException(Benben::t('benben','No columns are being updated for table "{table}".',
    				array('{table}'=>$table->name)));
    	$sql="UPDATE {$table->rawName} SET ".implode(', ',$fields);
    	$sql=$this->applyJoin($sql,$criteria->join);
    	$sql=$this->applyCondition($sql,$criteria->condition);
    	$sql=$this->applyOrder($sql,$criteria->order);
    	$sql=$this->applyLimit($sql,$criteria->limit,$criteria->offset);
    
    	$command=$this->_connection->createCommand($sql);
    	$this->bindValues($command,array_merge($values,$criteria->params));
    
    	return $command;
    }
    
    /**
     * Creates a COUNT(*) command for a single table.
     * @param mixed $table the table schema ({@link TableSchema}) or the table name (string).
     * @param DbCriteria $criteria the query criteria
     * @param string $alias the alias name of the primary table. Defaults to 't'.
     * @return DbCommand query command.
     */
    public function createCountCommand($table,$criteria,$alias='t')
    {
    	$this->ensureTable($table);
    	if($criteria->alias!='')
    		$alias=$criteria->alias;
    	$alias=$this->_schema->quote($alias);
    
    	if(!empty($criteria->group) || !empty($criteria->having))
    	{
    		$select=is_array($criteria->select) ? implode(', ',$criteria->select) : $criteria->select;
    		if($criteria->alias!='')
    			$alias=$criteria->alias;
    		$sql=($criteria->distinct ? 'SELECT DISTINCT':'SELECT')." {$select} FROM {$table->rawName} $alias";
    		$sql=$this->applyJoin($sql,$criteria->join);
    		$sql=$this->applyCondition($sql,$criteria->condition);
    		$sql=$this->applyGroup($sql,$criteria->group);
    		$sql=$this->applyHaving($sql,$criteria->having);
    		$sql="SELECT COUNT(*) FROM ($sql) sq";
    	}
    	else
    	{
    		if(is_string($criteria->select) && stripos($criteria->select,'count')===0)
    			$sql="SELECT ".$criteria->select;
    		else if($criteria->distinct)
    		{
    			if(is_array($table->primaryKey))
    			{
    				$pk=array();
    				foreach($table->primaryKey as $key)
    					$pk[]=$alias.'.'.$key;
    				$pk=implode(', ',$pk);
    			}
    			else
    				$pk=$alias.'.'.$table->primaryKey;
    			$sql="SELECT COUNT(DISTINCT $pk)";
    		}
    		else
    			$sql="SELECT COUNT(*)";
    		$sql.=" FROM {$table->rawName} $alias";
    		$sql=$this->applyJoin($sql,$criteria->join);
    		$sql=$this->applyCondition($sql,$criteria->condition);
    	}
    
    	$command=$this->_connection->createCommand($sql);
    	$this->bindValues($command,$criteria->params);
    	return $command;
    }
    
    /**
     * Creates a DELETE command.
     * @param mixed $table the table schema ({@link TableSchema}) or the table name (string).
     * @param DbCriteria $criteria the query criteria
     * @return DbCommand delete command.
     */
    public function createDeleteCommand($table,$criteria)
    {
    	$this->ensureTable($table);
    	$sql="DELETE FROM {$table->rawName}";
    	$sql=$this->applyJoin($sql,$criteria->join);
    	$sql=$this->applyCondition($sql,$criteria->condition);
    	$sql=$this->applyGroup($sql,$criteria->group);
    	$sql=$this->applyHaving($sql,$criteria->having);
    	$sql=$this->applyOrder($sql,$criteria->order);
    	$sql=$this->applyLimit($sql,$criteria->limit,$criteria->offset);
    	$command=$this->_connection->createCommand($sql);
    	$this->bindValues($command,$criteria->params);
    	return $command;
    }
    
    /**
     * Creates a query criteria with the specified primary key.
     * @param mixed $table the table schema ({@link TableSchema}) or the table name (string).
     * @param mixed $pk primary key value(s). Use array for multiple primary keys. For composite key, each key value must be an array (column name=>column value).
     * @param mixed $condition query condition or criteria.
     * If a string, it is treated as query condition;
     * If an array, it is treated as the initial values for constructing a {@link DbCriteria};
     * Otherwise, it should be an instance of {@link DbCriteria}.
     * @param array $params parameters to be bound to an SQL statement.
     * This is only used when the second parameter is a string (query condition).
     * In other cases, please use {@link DbCriteria::params} to set parameters.
     * @param string $prefix column prefix (ended with dot). If null, it will be the table name
     * @return DbCriteria the created query criteria
     */
    public function createPkCriteria($table,$pk,$condition='',$params=array(),$prefix=null)
    {
    	$this->ensureTable($table);
    	$criteria=$this->createCriteria($condition,$params);
    	if($criteria->alias!='')
    		$prefix=$this->_schema->quote($criteria->alias).'.';
    	if(!is_array($pk)) // single key
    		$pk=array($pk);
    	if(is_array($table->primaryKey) && !isset($pk[0]) && $pk!==array()) // single composite key
    		$pk=array($pk);
    	$condition=$this->createInCondition($table,$table->primaryKey,$pk,$prefix);
    	if($criteria->condition!='')
    		$criteria->condition=$condition.' AND ('.$criteria->condition.')';
    	else
    		$criteria->condition=$condition;
    
    	return $criteria;
    }
    
    /**
     * Creates a query criteria with the specified column values.
     * @param mixed $table the table schema ({@link TableSchema}) or the table name (string).
     * @param array $columns column values that should be matched in the query (name=>value)
     * @param mixed $condition query condition or criteria.
     * If a string, it is treated as query condition;
     * If an array, it is treated as the initial values for constructing a {@link DbCriteria};
     * Otherwise, it should be an instance of {@link DbCriteria}.
     * @param array $params parameters to be bound to an SQL statement.
     * This is only used when the third parameter is a string (query condition).
     * In other cases, please use {@link DbCriteria::params} to set parameters.
     * @param string $prefix column prefix (ended with dot). If null, it will be the table name
     * @return DbCriteria the created query criteria
     */
    public function createColumnCriteria($table,$columns,$condition='',$params=array(),$prefix=null)
    {
    	$this->ensureTable($table);
    	$criteria=$this->createCriteria($condition,$params);
    	if($criteria->alias!='')
    		$prefix=$this->_schema->quoteTableName($criteria->alias).'.';
    	$bindByPosition=isset($criteria->params[0]);
    	$conditions=array();
    	$values=array();
    	$i=0;
    	if($prefix===null)
    		$prefix=$table->rawName.'.';
    	foreach($columns as $name=>$value)
    	{
    		if(($column=$table->getColumn($name))!==null)
    		{
    			if(is_array($value))
    				$conditions[]=$this->createInCondition($table,$name,$value,$prefix);
    			else if($value!==null)
    			{
    				if($bindByPosition)
    				{
    					$conditions[]=$prefix.$column->rawName.'=?';
    					$values[]=$value;
    				}
    				else
    				{
    					$conditions[]=$prefix.$column->rawName.'='.self::PARAM_PREFIX.$i;
    					$values[self::PARAM_PREFIX.$i]=$value;
    					$i++;
    				}
    			}
    			else
    				$conditions[]=$prefix.$column->rawName.' IS NULL';
    		}
    		else
    			throw new DbException(Benben::t('benben','Table "{table}" does not have a column named "{column}".',
    					array('{table}'=>$table->name,'{column}'=>$name)));
    	}
    	$criteria->params=array_merge($values,$criteria->params);
    	if(isset($conditions[0]))
    	{
    		if($criteria->condition!='')
    			$criteria->condition=implode(' AND ',$conditions).' AND ('.$criteria->condition.')';
    		else
    			$criteria->condition=implode(' AND ',$conditions);
    	}
    	return $criteria;
    }
    
    /**
     * Creates a query criteria.
     * @param mixed $condition query condition or criteria.
     * If a string, it is treated as query condition (the WHERE clause);
     * If an array, it is treated as the initial values for constructing a {@link DbCriteria} object;
     * Otherwise, it should be an instance of {@link DbCriteria}.
     * @param array $params parameters to be bound to an SQL statement.
     * This is only used when the first parameter is a string (query condition).
     * In other cases, please use {@link DbCriteria::params} to set parameters.
     * @return DbCriteria the created query criteria
     * @throws Exception if the condition is not string, array and DbCriteria
     */
    public function createCriteria($condition='',$params=array())
    {
    	if(is_array($condition))
    		$criteria=new DbCriteria($condition);
    	else if($condition instanceof DbCriteria)
    		$criteria=clone $condition;
    	else
    	{
    		$criteria=new DbCriteria;
    		$criteria->condition=$condition;
    		$criteria->params=$params;
    	}
    	return $criteria;
    }
    
    /**
     * Generates the expression for selecting rows of specified primary key values.
     * @param mixed $table the table schema ({@link CDbTableSchema}) or the table name (string).
     * @param mixed $columnName the column name(s). It can be either a string indicating a single column
     * or an array of column names. If the latter, it stands for a composite key.
     * @param array $values list of key values to be selected within
     * @param string $prefix column prefix (ended with dot). If null, it will be the table name
     * @return string the expression for selection
     */
    public function createInCondition($table,$columnName,$values,$prefix=null)
    {
    	if(($n=count($values))<1)
    		return '0=1';
    
    	$this->ensureTable($table);
    
    	if($prefix===null)
    		$prefix=$table->rawName.'.';
    
    	$db=$this->_connection;
    
    	if(is_array($columnName) && count($columnName)===1)
    		$columnName=reset($columnName);
    
    	if(is_string($columnName)) // simple key
    	{
    		if(!isset($table->columns[$columnName]))
    			throw new DbException(Benben::t('benben','Table "{table}" does not have a column named "{column}".',
    					array('{table}'=>$table->name, '{column}'=>$columnName)));
    		$column=$table->columns[$columnName];
    
    		foreach($values as &$value)
    		{
    			$value=$column->typecast($value);
    			if(is_string($value))
    				$value=$db->quoteValue($value);
    		}
    		if($n===1)
    			return $prefix.$column->rawName.($values[0]===null?' IS NULL':'='.$values[0]);
    		else
    			return $prefix.$column->rawName.' IN ('.implode(', ',$values).')';
    	}
    	else if(is_array($columnName)) // composite key: $values=array(array('pk1'=>'v1','pk2'=>'v2'),array(...))
    	{
    		foreach($columnName as $name)
    		{
    			if(!isset($table->columns[$name]))
    				throw new DbException(Benben::t('benben','Table "{table}" does not have a column named "{column}".',
    						array('{table}'=>$table->name, '{column}'=>$name)));
    
    			for($i=0;$i<$n;++$i)
    			{
    				if(isset($values[$i][$name]))
    				{
    					$value=$table->columns[$name]->typecast($values[$i][$name]);
    					if(is_string($value))
    						$values[$i][$name]=$db->quoteValue($value);
    					else
    						$values[$i][$name]=$value;
    				}
    				else
    					throw new DbException(Benben::t('benben','The value for the column "{column}" is not supplied when querying the table "{table}".',
    							array('{table}'=>$table->name,'{column}'=>$name)));
    			}
    		}
    		if(count($values)===1)
    		{
    			$entries=array();
    			foreach($values[0] as $name=>$value)
    				$entries[]=$prefix.$table->columns[$name]->rawName.($value===null?' IS NULL':'='.$value);
    			return implode(' AND ',$entries);
    		}
    
    		return $this->createCompositeInCondition($table,$values,$prefix);
    	}
    	else
    		throw new DbException(Benben::t('benben','Column name must be either a string or an array.'));
    }
    
    /**
     * Generates the expression for selecting rows with specified composite key values.
     * @param TableSchema $table the table schema
     * @param array $values list of primary key values to be selected within
     * @param string $prefix column prefix (ended with dot)
     * @return string the expression for selection
     */
    protected function createCompositeInCondition($table,$values,$prefix)
    {
    	$keyNames=array();
    	foreach(array_keys($values[0]) as $name)
    		$keyNames[]=$prefix.$table->columns[$name]->rawName;
    	$vs=array();
    	foreach($values as $value)
    		$vs[]='('.implode(', ',$value).')';
    	return '('.implode(', ',$keyNames).') IN ('.implode(', ',$vs).')';
    }
    
    /**
     * Binds parameter values for an SQL command.
     * @param DbCommand $command database command
     * @param array $values values for binding (integer-indexed array for question mark placeholders, string-indexed array for named placeholders)
     */
    public function bindValues($command, $values)
    {
    	if(($n=count($values))===0)
    		return;
    	if(isset($values[0])) // question mark placeholders
    	{
    		for($i=0;$i<$n;++$i)
    			$command->bindValue($i+1,$values[$i]);
    	}
    	else // named placeholders
    	{
    		foreach($values as $name=>$value)
    		{
    			if($name[0]!==':')
    				$name=':'.$name;
    			$command->bindValue($name,$value);
    		}
    	}
    }
    
    /**
     * Alters the SQL to apply JOIN clause.
     * @param string $sql the SQL statement to be altered
     * @param string $join the JOIN clause (starting with join type, such as LEFT JOIN)
     * @return string the altered SQL statement
     */
    public function applyJoin($sql,$join)
    {
    	if($join!='')
    		return $sql.' '.$join;
    	else
    		return $sql;
    }
    
    /**
     * Alters the SQL to apply WHERE clause
     * @param string $sql the SQL statement without WHERE clause.
     * @param string $condition the WHERE clause (without WHERE keyword)
     * @return string the altered SQL statement
     */
    public function applyCondition($sql,$condition)
    {
    	if($condition!='')
    		return $sql.' WHERE '.$condition;
    	else
    		return $sql;
    }
    
    /**
     * Alters the SQL apply to ORDER BY clause
     * @param string $sql SQL statement without ORDER BY clause.
     * @param string $orderBy column ordering
     * @return string altered SQL statement with ORDER BY clause.
     */
    public function applyOrder($sql,$orderBy)
    {
    	if($orderBy!='')
    		return $sql.' ORDER BY '.$orderBy;
    	else
    		return $sql;
    }
    
    /**
     * Alters the SQL to apply LIMIT and OFFSET.
     * Defaults implementation is applicable for PostgreSQL, MySQL and SQLite.
     * @param string $sql SQL QUERY string without LIMIT and OFFSET.
     * @param integer $limit maximum number of rows, -1 to ignore limit. 
     * @param integer $offset row offset, -1 to ignore offset.
     * @return string SQL with LIMIT and OFFSET
     */
    public function applyLimit($sql,$limit,$offset)
    {
    	if($limit>=0)
    		$sql.=' LIMIT '.(int)$limit;
    	if($offset>0)
    		$sql.=' OFFSET '.(int)$offset;
    	return $sql;
    }
    
    /**
     * Alters the SQL to apply GROUP BY.
     * @param string $sql SQL query string without GROUP BY.
     * @param string $group GROUP BY
     * @return string SQL with GROUP BY.
     */
    public function applyGroup($sql,$group)
    {
    	if($group!='')
    		return $sql.' GROUP BY '.$group;
    	else
    		return $sql;
    }
    
    /**
     * Alters the SQL to apply HAVING.
     * @param string $sql SQL query string without HAVING
     * @param string $having HAVING
     * @return string SQL with HAVING
     */
    public function applyHaving($sql,$having)
    {
    	if($having!='')
    		return $sql.' HAVING '.$having;
    	else
    		return $sql;
    }
    
    /**
     * Checks if the parameter is a valid table schema.
     * If it is a string, the corresponding table schema will be retrieved.
     * @param mixed $table table schema ({@link CDbTableSchema}) or table name (string).
     * If this refers to a valid table name, this parameter will be returned with the corresponding table schema.
     * @throws DbException if the table name is not valid
     */
    protected function ensureTable(&$table)
    {
    	if(is_string($table) && ($table=$this->_schema->getTable($tableName=$table))===null)
    		throw new DbException(Benben::t('benben','Table "{table}" does not exist.',
    				array('{table}'=>$tableName)));
    }
    
}