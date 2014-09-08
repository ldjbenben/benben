<?php
namespace benben\db\schema\mysql;
use benben\db\schema\DbSchema;

class MysqlSchema extends DbSchema
{
    protected function loadTable($name)
    {
        $table=new MysqlTableSchema();
        $this->resolveTableNames($table,$name);
        
        if($this->findColumns($table))
        {
        	$this->findConstraints($table);
        	return $table;
        }
        else
        	return null;
    }
    
    /**
     * Generates various kinds of table names.
     * @param MysqlTableSchema $table the table instance
     * @param string $name the unquoted table name
     */
    protected function resolveTableNames($table,$name)
    {
    	$parts=explode('.',str_replace('`','',$name));
    	if(isset($parts[1]))
    	{
    		$table->schemaName=$parts[0];
    		$table->name=$parts[1];
    		$table->rawName=$this->quoteTableName($table->schemaName).'.'.$this->quoteTableName($table->name);
    	}
    	else
    	{
    		$table->name=$parts[0];
    		$table->rawName=$this->quote($table->name);
    	}
    }
    
    /**
     * Collects the table column metadata.
     * @param CMysqlTableSchema $table the table metadata
     * @return boolean whether the table exists in the database
     */
    protected function findColumns($table)
    {
    	$sql='SHOW COLUMNS FROM '.$table->rawName;
    	try
    	{
    		$columns=$this->getDbConnection()->createCommand($sql)->queryAll();
    	}
    	catch(\Exception $e)
    	{
    		return false;
    	}
    	
    	foreach($columns as $column)
    	{
    	    $c = $this->createColumn($column);
    	    $table->columns[$c->name] = $c;
    	    if($c->isPrimaryKey)
    	    {
    	    	if(null===$table->primaryKey)
    	    	{
    	    		$table->primaryKey=$c->name;
    	    	}
    	    	else if(is_string($table->primaryKey))
    	    	{
    	    		$table->primaryKey=array($table->primaryKey,$c->name);
    	    	}
    	    	else
    	    	{
    	    		$table->primaryKey[]=$c->name;
    	    	}
    	    	if($c->autoIncrement)
    	    	{
    	    		$table->sequenceName='';
    	    	}
    	    }
    	}
    	return true;
    }
    
    /**
     * Creates a table column.
     * @param array $column column metadata
     * @return DbColumnSchema normalized column metadata
     */
    protected function createColumn($column)
    {
    	$c=new MysqlColumnSchema();
    	$c->name=$column['Field'];
    	$c->rawName=$this->quote($c->name);
    	$c->allowNull=$column['Null']==='YES';
    	$c->isPrimaryKey=strpos($column['Key'],'PRI')!==false;
    	$c->isForeignKey=false;
    	$c->init($column['Type'],$column['Default']);
    	$c->autoIncrement=strpos(strtolower($column['Extra']),'auto_increment')!==false;
    
    	return $c;
    }
    
    /**
     * Collects the foreign key column details for the given table.
     * @param CMysqlTableSchema $table the table metadata
     */
    protected function findConstraints($table)
    {
    	$row=$this->getDbConnection()->createCommand('SHOW CREATE TABLE '.$table->rawName)->queryRow();
    	$matches=array();
    	$regexp='/FOREIGN KEY\s+\(([^\)]+)\)\s+REFERENCES\s+([^\(^\s]+)\s*\(([^\)]+)\)/mi';
    	foreach($row as $sql)
    	{
    		if(preg_match_all($regexp,$sql,$matches,PREG_SET_ORDER))
    			break;
    	}
    	foreach($matches as $match)
    	{
    		$keys=array_map('trim',explode(',',str_replace('`','',$match[1])));
    		$fks=array_map('trim',explode(',',str_replace('`','',$match[3])));
    		foreach($keys as $k=>$name)
    		{
    			$table->foreignKeys[$name]=array(str_replace('`','',$match[2]),$fks[$k]);
    			if(isset($table->columns[$name]))
    				$table->columns[$name]->isForeignKey=true;
    		}
    	}
    }
}