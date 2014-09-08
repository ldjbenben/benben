<?php
namespace benben\db\ar;

use benben\Benben;

use benben\db\DbException;

/**
 * ActiveRecordMetaData represents the meta-data for an Active Record class.
 */
class ActiveRecordMetaData
{
	/**
	 * @var \benben\db\schema\DbTableSchema the table schema information
	 */
	public $tableSchema;
	/**
	 * @var array table columns
	 */
	public $columns;
	/**
	 * @var array list of relations
	 */
	public $relations=array();
	/**
	 * @var array attribute default values
	 */
	public $attributeDefaults=array();

	private $_model;

	/**
	 * Constructor.
	 * @param ActiveRecord $model the model instance
	 */
	public function __construct($model)
	{
		$this->_model=$model;

		$tableName=$model->tableName();
		if(($table=$model->getDbConnection()->getSchema()->getTable($tableName))===null)
			throw new DbException(Benben::t('benben','The table "{table}" for active record class "{class}" cannot be found in the database.',
				array('{class}'=>get_class($model),'{table}'=>$tableName)));
		if($table->primaryKey===null)
		{
			$table->primaryKey=$model->primaryKey();
			if(is_string($table->primaryKey) && isset($table->columns[$table->primaryKey]))
				$table->columns[$table->primaryKey]->isPrimaryKey=true;
			else if(is_array($table->primaryKey))
			{
				foreach($table->primaryKey as $name)
				{
					if(isset($table->columns[$name]))
						$table->columns[$name]->isPrimaryKey=true;
				}
			}
		}
		$this->tableSchema=$table;
		$this->columns=$table->columns;

		foreach($table->columns as $name=>$column)
		{
			if(!$column->isPrimaryKey && $column->defaultValue!==null)
				$this->attributeDefaults[$name]=$column->defaultValue;
		}

		foreach($model->relations() as $name=>$config)
		{
			$this->addRelation($name,$config);
		}
	}

	/**
	 * Adds a relation.
	 *
	 * $config is an array with three elements:
	 * relation type, the related active record class and the foreign key.
	 *
	 * @throws \benben\db\DbException
	 * @param string $name $name Name of the relation.
	 * @param array $config $config Relation parameters.
     * @return void
	 */
	public function addRelation($name,$config)
	{
		if(isset($config[0],$config[1],$config[2]))  // relation class, AR class, FK
			$this->relations[$name]=new $config[0]($name,$config[1],$config[2],array_slice($config,3));
		else
			throw new DbException(Benben::t('benben','Active record "{class}" has an invalid configuration for relation "{relation}". It must specify the relation type, the related active record class and the foreign key.', array('{class}'=>get_class($this->_model),'{relation}'=>$name)));
	}

	/**
	 * Checks if there is a relation with specified name defined.
	 *
	 * @param string $name $name Name of the relation.
	 * @return boolean
	 */
	public function hasRelation($name)
	{
		return isset($this->relations[$name]);
	}

	/**
	 * Deletes a relation with specified name.
	 *
	 * @param string $name $name
	 * @return void
	 */
	public function removeRelation($name)
	{
		unset($this->relations[$name]);
	}
}