<?php

namespace benben\db\ar;

use benben\db\DbException;
use benben\Benben;

class StatElement 
{
	/**
	 * @var ActiveRelation the relation represented by this tree node
	 */
	public $relation;
	
	private $_finder;
	private $_parent;
	
	/**
	 * Constructor.
	 * @param ActiveFinder $finder the finder
	 * @param StatRelation $relation the STAT relation
	 * @param JoinElement $parent the join element owning this STAT element
	 */
	public function __construct($finder,$relation,$parent)
	{
		$this->_finder=$finder;
		$this->_parent=$parent;
		$this->relation=$relation;
		$parent->stats[]=$this;
	}
	
	/**
	 * Performs the STAT query.
	 */
	public function query()
	{
		if(preg_match('/^\s*(.*?)\((.*)\)\s*$/',$this->relation->foreignKey,$matches))
			$this->queryManyMany($matches[1],$matches[2]);
		else
			$this->queryOneMany();
	}
	
	private function queryOneMany()
	{
		$relation=$this->relation;
		$model=ActiveRecord::model($relation->className);
		$builder=$model->getCommandBuilder();
		$schema=$builder->getSchema();
		$table=$model->getTableSchema();
		$parent=$this->_parent;
		$pkTable=$parent->model->getTableSchema();
	
		$fks=preg_split('/\s*,\s*/',$relation->foreignKey,-1,PREG_SPLIT_NO_EMPTY);
		if(count($fks)!==count($pkTable->primaryKey))
			throw new DbException(Benben::t('benben','The relation "{relation}" in active record class "{class}" is specified with an invalid foreign key. The columns in the key must match the primary keys of the table "{table}".',
					array('{class}'=>get_class($parent->model), '{relation}'=>$relation->name, '{table}'=>$pkTable->name)));
	
		// set up mapping between fk and pk columns
		$map=array();  // pk=>fk
		foreach($fks as $i=>$fk)
		{
			if(!isset($table->columns[$fk]))
				throw new DbException(Benben::t('benben','The relation "{relation}" in active record class "{class}" is specified with an invalid foreign key "{key}". There is no such column in the table "{table}".',
						array('{class}'=>get_class($parent->model), '{relation}'=>$relation->name, '{key}'=>$fk, '{table}'=>$table->name)));
	
			if(isset($table->foreignKeys[$fk]))
			{
				list($tableName,$pk)=$table->foreignKeys[$fk];
				if($schema->compareTableNames($pkTable->rawName,$tableName))
					$map[$pk]=$fk;
				else
					throw new DbException(Benben::t('benben','The relation "{relation}" in active record class "{class}" is specified with a foreign key "{key}" that does not point to the parent table "{table}".',
							array('{class}'=>get_class($parent->model), '{relation}'=>$relation->name, '{key}'=>$fk, '{table}'=>$pkTable->name)));
			}
			else  // FK constraints undefined
			{
				if(is_array($pkTable->primaryKey)) // composite PK
					$map[$pkTable->primaryKey[$i]]=$fk;
				else
					$map[$pkTable->primaryKey]=$fk;
			}
		}
	
		$records=$this->_parent->records;
	
		$join=empty($relation->join)?'' : ' '.$relation->join;
		$where=empty($relation->condition)?' WHERE ' : ' WHERE ('.$relation->condition.') AND ';
		$group=empty($relation->group)?'' : ', '.$relation->group;
		$having=empty($relation->having)?'' : ' HAVING ('.$relation->having.')';
		$order=empty($relation->order)?'' : ' ORDER BY '.$relation->order;
	
		$c=$schema->quoteColumnName('c');
		$s=$schema->quoteColumnName('s');
	
		$tableAlias=$model->getTableAlias(true);
	
		// generate and perform query
		if(count($fks)===1)  // single column FK
		{
			$col=$table->columns[$fks[0]]->rawName;
			$sql="SELECT $col AS $c, {$relation->select} AS $s FROM {$table->rawName} ".$tableAlias.$join
			.$where.'('.$builder->createInCondition($table,$fks[0],array_keys($records),$tableAlias.'.').')'
					." GROUP BY $col".$group
					.$having.$order;
			$command=$builder->getDbConnection()->createCommand($sql);
			if(is_array($relation->params))
				$builder->bindValues($command,$relation->params);
			$stats=array();
			foreach($command->queryAll() as $row)
				$stats[$row['c']]=$row['s'];
		}
		else  // composite FK
		{
			$keys=array_keys($records);
			foreach($keys as &$key)
			{
				$key2=unserialize($key);
				$key=array();
				foreach($pkTable->primaryKey as $pk)
					$key[$map[$pk]]=$key2[$pk];
			}
			$cols=array();
			foreach($pkTable->primaryKey as $n=>$pk)
			{
				$name=$table->columns[$map[$pk]]->rawName;
				$cols[$name]=$name.' AS '.$schema->quoteColumnName('c'.$n);
			}
			$sql='SELECT '.implode(', ',$cols).", {$relation->select} AS $s FROM {$table->rawName} ".$tableAlias.$join
			.$where.'('.$builder->createInCondition($table,$fks,$keys,$tableAlias.'.').')'
					.' GROUP BY '.implode(', ',array_keys($cols)).$group
					.$having.$order;
			$command=$builder->getDbConnection()->createCommand($sql);
			if(is_array($relation->params))
				$builder->bindValues($command,$relation->params);
			$stats=array();
			foreach($command->queryAll() as $row)
			{
				$key=array();
				foreach($pkTable->primaryKey as $n=>$pk)
					$key[$pk]=$row['c'.$n];
				$stats[serialize($key)]=$row['s'];
			}
		}
	
		// populate the results into existing records
		foreach($records as $pk=>$record)
			$record->addRelatedRecord($relation->name,isset($stats[$pk])?$stats[$pk]:$relation->defaultValue,false);
	}
	
	/*
	 * @param string $joinTableName jointablename
	* @param string $keys keys
	*/
	private function queryManyMany($joinTableName,$keys)
	{
		$relation=$this->relation;
		$model=ActiveRecord::model($relation->className);
		$table=$model->getTableSchema();
		$builder=$model->getCommandBuilder();
		$schema=$builder->getSchema();
		$pkTable=$this->_parent->model->getTableSchema();
	
		$tableAlias=$model->getTableAlias(true);
	
		if(($joinTable=$builder->getSchema()->getTable($joinTableName))===null)
			throw new DbException(Benben::t('benben','The relation "{relation}" in active record class "{class}" is not specified correctly. The join table "{joinTable}" given in the foreign key cannot be found in the database.',
					array('{class}'=>get_class($this->_parent->model), '{relation}'=>$relation->name, '{joinTable}'=>$joinTableName)));
	
		$fks=preg_split('/\s*,\s*/',$keys,-1,PREG_SPLIT_NO_EMPTY);
		if(count($fks)!==count($table->primaryKey)+count($pkTable->primaryKey))
			throw new DbException(Benben::t('benben','The relation "{relation}" in active record class "{class}" is specified with an incomplete foreign key. The foreign key must consist of columns referencing both joining tables.',
					array('{class}'=>get_class($this->_parent->model), '{relation}'=>$relation->name)));
	
		$joinCondition=array();
		$map=array();
	
		$fkDefined=true;
		foreach($fks as $i=>$fk)
		{
			if(!isset($joinTable->columns[$fk]))
				throw new DbException(Benben::t('benben','The relation "{relation}" in active record class "{class}" is specified with an invalid foreign key "{key}". There is no such column in the table "{table}".',
						array('{class}'=>get_class($this->_parent->model), '{relation}'=>$relation->name, '{key}'=>$fk, '{table}'=>$joinTable->name)));
	
			if(isset($joinTable->foreignKeys[$fk]))
			{
				list($tableName,$pk)=$joinTable->foreignKeys[$fk];
				if(!isset($joinCondition[$pk]) && $schema->compareTableNames($table->rawName,$tableName))
					$joinCondition[$pk]=$tableAlias.'.'.$schema->quoteColumnName($pk).'='.$joinTable->rawName.'.'.$schema->quoteColumnName($fk);
				elseif(!isset($map[$pk]) && $schema->compareTableNames($pkTable->rawName,$tableName))
				$map[$pk]=$fk;
				else
				{
					$fkDefined=false;
					break;
				}
			}
			else
			{
				$fkDefined=false;
				break;
			}
		}
	
		if(!$fkDefined)
		{
			$joinCondition=array();
			$map=array();
			foreach($fks as $i=>$fk)
			{
				if($i<count($pkTable->primaryKey))
				{
					$pk=is_array($pkTable->primaryKey) ? $pkTable->primaryKey[$i] : $pkTable->primaryKey;
					$map[$pk]=$fk;
				}
				else
				{
					$j=$i-count($pkTable->primaryKey);
					$pk=is_array($table->primaryKey) ? $table->primaryKey[$j] : $table->primaryKey;
					$joinCondition[$pk]=$tableAlias.'.'.$schema->quoteColumnName($pk).'='.$joinTable->rawName.'.'.$schema->quoteColumnName($fk);
				}
			}
		}
	
		if($joinCondition===array() || $map===array())
			throw new DbException(Benben::t('benben','The relation "{relation}" in active record class "{class}" is specified with an incomplete foreign key. The foreign key must consist of columns referencing both joining tables.',
					array('{class}'=>get_class($this->_parent->model), '{relation}'=>$relation->name)));
	
		$records=$this->_parent->records;
	
		$cols=array();
		foreach(is_string($pkTable->primaryKey)?array($pkTable->primaryKey):$pkTable->primaryKey as $n=>$pk)
		{
			$name=$joinTable->rawName.'.'.$schema->quoteColumnName($map[$pk]);
			$cols[$name]=$name.' AS '.$schema->quoteColumnName('c'.$n);
		}
	
		$keys=array_keys($records);
		if(is_array($pkTable->primaryKey))
		{
			foreach($keys as &$key)
			{
				$key2=unserialize($key);
				$key=array();
				foreach($pkTable->primaryKey as $pk)
					$key[$map[$pk]]=$key2[$pk];
			}
		}
	
		$join=empty($relation->join)?'' : ' '.$relation->join;
		$where=empty($relation->condition)?'' : ' WHERE ('.$relation->condition.')';
		$group=empty($relation->group)?'' : ', '.$relation->group;
		$having=empty($relation->having)?'' : ' AND ('.$relation->having.')';
		$order=empty($relation->order)?'' : ' ORDER BY '.$relation->order;
	
		$sql='SELECT '.$this->relation->select.' AS '.$schema->quoteColumnName('s').', '.implode(', ',$cols)
		.' FROM '.$table->rawName.' '.$tableAlias.' INNER JOIN '.$joinTable->rawName
		.' ON ('.implode(') AND (',$joinCondition).')'.$join
		.$where
		.' GROUP BY '.implode(', ',array_keys($cols)).$group
		.' HAVING ('.$builder->createInCondition($joinTable,$map,$keys).')'
				.$having.$order;
	
		$command=$builder->getDbConnection()->createCommand($sql);
		if(is_array($relation->params))
			$builder->bindValues($command,$relation->params);
	
		$stats=array();
		foreach($command->queryAll() as $row)
		{
			if(is_array($pkTable->primaryKey))
			{
				$key=array();
				foreach($pkTable->primaryKey as $n=>$k)
					$key[$k]=$row['c'.$n];
				$stats[serialize($key)]=$row['s'];
			}
			else
				$stats[$row['c0']]=$row['s'];
		}
	
		foreach($records as $pk=>$record)
			$record->addRelatedRecord($relation->name,isset($stats[$pk])?$stats[$pk]:$this->relation->defaultValue,false);
	}
}