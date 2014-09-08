<?php
namespace benben\db\ar;

use benben\db\DbConnection;

use benben\db\DbException;

use benben\Benben;

use benben\db\DbCriteria;

use benben\base\Model;

abstract class ActiveRecord extends Model
{
	const BELONGS_TO='benben\db\ar\BelongsToRelation';
	const HAS_ONE='HasOneRelation';
	const HAS_MANY='HasManyRelation';
	const MANY_MANY='ManyManyRelation';
	const STAT='StatRelation';
    /**
     * @var benben\db\DbConnection
     */
    public static $db = null;
    
    private static $_models = array();
    
    protected $_table;
    private $_attributes=array();				// attribute name => attribute value
    private $_related=array();					// attribute name => related objects
    private $_c;								// query criteria (used by finder only)
    private $_pk;								// old primary key value
    private $_alias='t';						// the table alias being used for query
    /**
     * @var ActiveRecordMetaData
     */
    private $_md = null;
    
    public function __construct()
    {
        //$this->initOrm();
    }
    
    /**
     * Initializes this model.
     * This method is invoked when an AR instance is newly created and has
     * its {@link scenario} set.
     * You may override this method to provide code that is needed to initialize the model (e.g. setting
     * initial property values.)
     */
    public function init()
    {
    }
    
    /**
     * Returns the static model of the specified AR class.
     * The model returned is a static instance of the AR class.
     * @param string $className active record class name.
     * @return ActiveRecord active record model instance
     */
    public static function model($className=__CLASS__)
    {
        if(isset(self::$_models[$className]))
        {
			return self::$_models[$className];
        }
		else
		{
		    $model = self::$_models[$className] = new $className();
		    $model->_md=new ActiveRecordMetaData($model);
		}
		return $model;
    }
    
    public function tableName()
    {
        return get_class($this);
    }
    
    public function getAttributes()
    {
        return $this->_attributes;
    }
    
    public function getRules()
    {
        return $this->_rules;
    }
    
    public function getPrimaryKey()
    {
        return $this->_primaryKey;
    }
    
    /**
     * Finds a single active record with the specified condition.
     * @param mixed $condition query condition or criteria.
     * If a string, it is treated as query condition (the WHERE clause);
     * If an array, it is treated as the initial values for constructing a {@link DbCriteria} object;
     * Otherwise, it should be an instance of {@link DbCriteria}.
     * @param array $params parameters to be bound to an SQL statement.
     * This is only used when the first parameter is a string (query condition).
     * In other cases, please use {@link DbCriteria::params} to set parameters.
     * @return array the record found. Null if no record is found.
     */
    public function find($condition='',$params=array())
    {
    	Benben::trace(get_class($this).'.find()','benben.db.ar.ActiveRecord');
    	$criteria=$this->getCommandBuilder()->createCriteria($condition,$params);
    	return $this->query($criteria);
    }
    
    /**
     * Finds a single row with the specifed primary key.
     * See {@link find()} for detailed explanation about $condition and $params.
     * @param mixed $pk primary key values(s). Use array for multiple primary keys.
     * @param mixed $condition query condition or criteria.
	 * @param array $params parameters to be bound to an SQL statement.
     * For composite key, each key value must be an array (column name=>column value).
     * @return array Null if none is found.
     */
    public function findByPk($pk,$condition='',$params=array())
    {
        Benben::trace(get_class($this).'.findByPk()','benben.db.ar.ActiveRecord');
		$prefix=$this->getTableAlias(true).'.';
        $criteria = $this->getCommandBuilder()->createPkCriteria($this->getTableSchema(), $pk, $condition, $params, $prefix);
        return $this->query($criteria);
    }
    
    /**
     * Finds all active records with the specified primary keys.
     * See {@link find()} for detailed explanation about $condition and $params.
     * @param mixed $pk primary key value(s). Use array for multiple primary keys. For composite key, each key value must be an array (column name=>column value).
     * @param mixed $condition query condition or criteria.
     * @param array $params parameters to be bound to an SQL statement.
     * @return array the records found. An empty array is returned if none is found.
     */
    public function findAllByPk($pk,$condition='',$params=array())
    {
    	Benben::trace(get_class($this).'.findAllByPk()','benben.db.ar.ActiveRecord');
    	$prefix=$this->getTableAlias(true).'.';
    	$criteria=$this->getCommandBuilder()->createPkCriteria($this->getTableSchema(),$pk,$condition,$params,$prefix);
    	return $this->query($criteria,true);
    }
    
    /**
     * Finds all active records satisfying the specified condition.
     * See {@link find()} for detailed explanation about $condition and $params.
     * @param mixed $condition query condition or criteria.
     * @param array $params parameters to be bound to an SQL statement.
     * @return array list of active records satisfying the specified condition. An empty array is returned if none is found.
     */
    public function findAll($condition='',$params=array())
    {
    	Benben::trace(get_class($this).'.findAll()','benben.db.ar.ActiveRecord');
    	$criteria=$this->getCommandBuilder()->createCriteria($condition,$params);
    	return $this->query($criteria,true);
    }
    
    /**
     * Finds a single active record that has the specified attribute values.
     * See {@link find()} for detailed explanation about $condition and $params.
     * @param array $attributes list of attribute values (indexed by attribute names) that the active records should match.
     * An attribute value can be an array which will be used to generate an IN condition.
     * @param mixed $condition query condition or criteria.
     * @param array $params parameters to be bound to an SQL statement.
     * @return ActiveRecord the record found. Null if none is found.
     */
    public function findByAttributes($attributes,$condition='',$params=array())
    {
    	Benben::trace(get_class($this).'.findByAttributes()','benben.db.ar.ActiveRecord');
    	$prefix=$this->getTableAlias(true).'.';
    	$criteria=$this->getCommandBuilder()->createColumnCriteria($this->getTableSchema(),$attributes,$condition,$params,$prefix);
    	return $this->query($criteria);
    }
    
    /**
     * Finds all active records that have the specified attribute values.
     * See {@link find()} for detailed explanation about $condition and $params.
     * @param array $attributes list of attribute values (indexed by attribute names) that the active records should match.
     * An attribute value can be an array which will be used to generate an IN condition.
     * @param mixed $condition query condition or criteria.
     * @param array $params parameters to be bound to an SQL statement.
     * @return array the records found. An empty array is returned if none is found.
     */
    public function findAllByAttributes($attributes,$condition='',$params=array())
    {
    	Benben::trace(get_class($this).'.findAllByAttributes()','benben.db.ar.ActiveRecord');
    	$prefix=$this->getTableAlias(true).'.';
    	$criteria=$this->getCommandBuilder()->createColumnCriteria($this->getTableSchema(),$attributes,$condition,$params,$prefix);
    	return $this->query($criteria,true);
    }
    
    /**
     * Finds a single active record with the specified SQL statement.
     * @param string $sql the SQL statement
     * @param array $params parameters to be bound to the SQL statement
     * @return array the record found. Null if none is found.
     */
    public function findBySql($sql,$params=array())
    {
    	Benben::trace(get_class($this).'.findBySql()','benben.db.ar.CActiveRecord');
    	$this->beforeFind();
    	
    	$command=$this->getCommandBuilder()->createSqlCommand($sql,$params);
    	return $command->queryRow();
    }
    
	/**
	 * Finds all active records using the specified SQL statement.
	 * @param string $sql the SQL statement
	 * @param array $params parameters to be bound to the SQL statement
	 * @return array the records found. An empty array is returned if none is found.
	 */
	public function findAllBySql($sql,$params=array())
	{
		Benben::trace(get_class($this).'.findAllBySql()','benben.db.ar.CActiveRecord');
		$this->beforeFind();
		$command=$this->getCommandBuilder()->createSqlCommand($sql,$params);
		return $command->queryAll();
	}
    
    /**
     * Performs the actual DB query and populates the AR objects with the query result.
     * This method is mainly internally used by other AR query methods.
     * @param DbCriteria $criteria the query criteria
     * @param boolean $all whether to return all data
     * @return mixed the AR objects populated with the query result
     */
    protected function query($criteria,$all=false)
    {
    	$this->applyScopes($criteria);
    	$this->beforeFind($criteria);
    
    	/* if(empty($criteria->with))
    	{ */
    		if(!$all)
    			$criteria->limit=1;
    		$command=$this->getCommandBuilder()->createFindCommand($this->getTableSchema(),$criteria);
    		return $all ? $command->queryAll() : $command->queryRow();
//     	}
    	/* else
    	{
    		$finder=new CActiveFinder($this,$criteria->with);
    		return $finder->query($criteria,$all);
    	} */
    }
    
    /**
     * Finds the number of rows satisfying the specified query condition.
     * See {@link find()} for detailed explanation about $condition and $params.
     * @param mixed $condition query condition or criteria.
     * @param array $params parameters to be bound to an SQL statement.
     * @return string the number of rows satisfying the specified query condition. Note: type is string to keep max. precision.
     */
    public function count($condition='',$params=array())
    {
    	Benben::trace(get_class($this).'.count()','benben.db.ar.ActiveRecord');
    	$builder=$this->getCommandBuilder();
    	$criteria=$builder->createCriteria($condition,$params);
    	$this->applyScopes($criteria);
    
    	if(empty($criteria->with))
    		return $builder->createCountCommand($this->getTableSchema(),$criteria)->queryScalar();
    	/* else
    	{
    		$finder=new CActiveFinder($this,$criteria->with);
    		return $finder->count($criteria);
    	} */
    }
    
    /**
     * Updates records with the specified primary key(s).
     * See {@link find()} for detailed explanation about $condition and $params.
     * Note, the attributes are not checked for safety and validation is NOT performed.
     * @param mixed $pk primary key value(s). Use array for multiple primary keys. For composite key, each key value must be an array (column name=>column value).
     * @param array $attributes list of attributes (name=>$value) to be updated
     * @param mixed $condition query condition or criteria.
     * @param array $params parameters to be bound to an SQL statement.
     * @return integer the number of rows being updated
     */
    public function updateByPk($pk,$attributes,$condition='',$params=array())
    {
    	Benben::trace(get_class($this).'.updateByPk()','benben.db.ar.ActiveRecord');
    	$builder=$this->getCommandBuilder();
    	$table=$this->getTableSchema();
    	$criteria=$builder->createPkCriteria($table,$pk,$condition,$params);
    	$command=$builder->createUpdateCommand($table,$attributes,$criteria);
    	return $command->execute();
    }
    
    /**
     * Updates records with the specified condition.
     * See {@link find()} for detailed explanation about $condition and $params.
     * Note, the attributes are not checked for safety and no validation is done.
     * @param array $attributes list of attributes (name=>$value) to be updated
     * @param mixed $condition query condition or criteria.
     * @param array $params parameters to be bound to an SQL statement.
     * @return integer the number of rows being updated
     */
    public function updateAll($attributes,$condition='',$params=array())
    {
    	Benben::trace(get_class($this).'.updateAll()','benben.db.ar.ActiveRecord');
    	$builder=$this->getCommandBuilder();
    	$criteria=$builder->createCriteria($condition,$params);
    	$command=$builder->createUpdateCommand($this->getTableSchema(),$attributes,$criteria);
    	return $command->execute();
    }
    
	/**
	 * Deletes rows with the specified primary key.
	 * See {@link find()} for detailed explanation about $condition and $params.
	 * @param mixed $pk primary key value(s). Use array for multiple primary keys. For composite key, each key value must be an array (column name=>column value).
	 * @param mixed $condition query condition or criteria.
	 * @param array $params parameters to be bound to an SQL statement.
	 * @return integer the number of rows deleted
	 */
	public function deleteByPk($pk,$condition='',$params=array())
	{
		Benben::trace(get_class($this).'.deleteByPk()','benben.db.ar.ActiveRecord');
		$builder=$this->getCommandBuilder();
		$criteria=$builder->createPkCriteria($this->getTableSchema(),$pk,$condition,$params);
		$command=$builder->createDeleteCommand($this->getTableSchema(),$criteria);
		return $command->execute();
	}

	/**
	 * Deletes rows with the specified condition.
	 * See {@link find()} for detailed explanation about $condition and $params.
	 * @param mixed $condition query condition or criteria.
	 * @param array $params parameters to be bound to an SQL statement.
	 * @return integer the number of rows deleted
	 */
	public function deleteAll($condition='',$params=array())
	{
		Benben::trace(get_class($this).'.deleteAll()','benben.db.ar.ActiveRecord');
		$builder=$this->getCommandBuilder();
		$criteria=$builder->createCriteria($condition,$params);
		$command=$builder->createDeleteCommand($this->getTableSchema(),$criteria);
		return $command->execute();
	}

	/**
	 * Deletes rows which match the specified attribute values.
	 * See {@link find()} for detailed explanation about $condition and $params.
	 * @param array $attributes list of attribute values (indexed by attribute names) that the active records should match.
	 * An attribute value can be an array which will be used to generate an IN condition.
	 * @param mixed $condition query condition or criteria.
	 * @param array $params parameters to be bound to an SQL statement.
	 * @return integer number of rows affected by the execution.
	 */
	public function deleteAllByAttributes($attributes,$condition='',$params=array())
	{
		Benben::trace(get_class($this).'.deleteAllByAttributes()','benben.db.ar.ActiveRecord');
		$builder=$this->getCommandBuilder();
		$table=$this->getTableSchema();
		$criteria=$builder->createColumnCriteria($table,$attributes,$condition,$params);
		$command=$builder->createDeleteCommand($table,$criteria);
		return $command->execute();
	}
    
    /**
     * @return \benben\db\schema\CommandBuider
     */
    public function getCommandBuilder()
    {
        return $this->getDbConnection()->getCommandBuilder();
    }
    
    /**
     * @return \benben\db\DbConnection
     */
    public function getDbConnection()
    {
        if (self::$db !== null)
        {
            return self::$db;
        }
        
        self::$db=Benben::app()->getDb();
        
        if (self::$db instanceof DbConnection) 
        {
            return self::$db;
        }
        
        throw new DbException(Benben::t('benben','Active Record requires a "db" DbConnection application component.'));
    }
    
    /**
     * Returns the query criteria associated with this model.
     * @param boolean $createIfNull whether to create a criteria instance if it does not exist. Defaults to true.
     * @return DbCriteria the query criteria that is associated with this model.
     * This criteria is mainly used by {@link scopes named scope} feature to accumulate
     * different criteria specifications.
     */
    public function getDbCriteria($createIfNull=true)
    {
    	if($this->_c===null)
    	{
    		if(($c=$this->defaultScope())!==array() || $createIfNull)
    			$this->_c=new DbCriteria($c);
    	}
    	return $this->_c;
    }
    
    /**
     * This method should be overridden to declare related objects.
     *
     * There are four types of relations that may exist between two active record objects:
     * <ul>
     * <li>BELONGS_TO: e.g. a member belongs to a team;</li>
     * <li>HAS_ONE: e.g. a member has at most one profile;</li>
     * <li>HAS_MANY: e.g. a team has many members;</li>
     * <li>MANY_MANY: e.g. a member has many skills and a skill belongs to a member.</li>
     * </ul>
     *
     * Besides the above relation types, a special relation called STAT is also supported
     * that can be used to perform statistical query (or aggregational query).
     * It retrieves the aggregational information about the related objects, such as the number
     * of comments for each post, the average rating for each product, etc.
     *
     * Each kind of related objects is defined in this method as an array with the following elements:
     * <pre>
     * 'varName'=>array('relationType', 'className', 'foreign_key', ...additional options)
     * </pre>
     * where 'varName' refers to the name of the variable/property that the related object(s) can
     * be accessed through; 'relationType' refers to the type of the relation, which can be one of the
     * following four constants: self::BELONGS_TO, self::HAS_ONE, self::HAS_MANY and self::MANY_MANY;
     * 'className' refers to the name of the active record class that the related object(s) is of;
     * and 'foreign_key' states the foreign key that relates the two kinds of active record.
     * Note, for composite foreign keys, they can be either listed together, separated by commas or specified as an array
     * in format of array('key1','key2'). In case you need to specify custom PK->FK association you can define it as
     * array('fk'=>'pk'). For composite keys it will be array('fk_c1'=>'pk_Ñ�1','fk_c2'=>'pk_c2').
     * For foreign keys used in MANY_MANY relation, the joining table must be declared as well
     * (e.g. 'join_table(fk1, fk2)').
     *
     * Additional options may be specified as name-value pairs in the rest array elements:
     * <ul>
     * <li>'select': string|array, a list of columns to be selected. Defaults to '*', meaning all columns.
     *   Column names should be disambiguated if they appear in an expression (e.g. COUNT(relationName.name) AS name_count).</li>
     * <li>'condition': string, the WHERE clause. Defaults to empty. Note, column references need to
     *   be disambiguated with prefix 'relationName.' (e.g. relationName.age&gt;20)</li>
     * <li>'order': string, the ORDER BY clause. Defaults to empty. Note, column references need to
     *   be disambiguated with prefix 'relationName.' (e.g. relationName.age DESC)</li>
     * <li>'with': string|array, a list of child related objects that should be loaded together with this object.
     *   Note, this is only honored by lazy loading, not eager loading.</li>
     * <li>'joinType': type of join. Defaults to 'LEFT OUTER JOIN'.</li>
     * <li>'alias': the alias for the table associated with this relationship.
     *   It defaults to null,
     *   meaning the table alias is the same as the relation name.</li>
     * <li>'params': the parameters to be bound to the generated SQL statement.
     *   This should be given as an array of name-value pairs.</li>
     * <li>'on': the ON clause. The condition specified here will be appended
     *   to the joining condition using the AND operator.</li>
     * <li>'index': the name of the column whose values should be used as keys
     *   of the array that stores related objects. This option is only available to
     *   HAS_MANY and MANY_MANY relations.</li>
     * <li>'scopes': scopes to apply. In case of a single scope can be used like 'scopes'=>'scopeName',
     *   in case of multiple scopes can be used like 'scopes'=>array('scopeName1','scopeName2').
     *   This option has been available since version 1.1.9.</li>
     * </ul>
     *
     * The following options are available for certain relations when lazy loading:
     * <ul>
     * <li>'group': string, the GROUP BY clause. Defaults to empty. Note, column references need to
     *   be disambiguated with prefix 'relationName.' (e.g. relationName.age). This option only applies to HAS_MANY and MANY_MANY relations.</li>
     * <li>'having': string, the HAVING clause. Defaults to empty. Note, column references need to
     *   be disambiguated with prefix 'relationName.' (e.g. relationName.age). This option only applies to HAS_MANY and MANY_MANY relations.</li>
     * <li>'limit': limit of the rows to be selected. This option does not apply to BELONGS_TO relation.</li>
     * <li>'offset': offset of the rows to be selected. This option does not apply to BELONGS_TO relation.</li>
     * <li>'through': name of the model's relation that will be used as a bridge when getting related data. Can be set only for HAS_ONE and HAS_MANY. This option has been available since version 1.1.7.</li>
     * </ul>
     *
     * Below is an example declaring related objects for 'Post' active record class:
     * <pre>
     * return array(
     *     'author'=>array(self::BELONGS_TO, 'User', 'author_id'),
     *     'comments'=>array(self::HAS_MANY, 'Comment', 'post_id', 'with'=>'author', 'order'=>'create_time DESC'),
     *     'tags'=>array(self::MANY_MANY, 'Tag', 'post_tag(post_id, tag_id)', 'order'=>'name'),
     * );
     * </pre>
     *
     * @return array list of related object declarations. Defaults to empty array.
     */
    public function relations()
    {
    	return array();
    }
    
    /**
     * Specifieds which related objects should be eagerly loaded.
     * This method takes variable number of parameters. Each parameter specifies
     * the name of a relation or child-relation. For example,
     * <pre>
     * // find all posts together with their author and comments
     * Post::model()->with('author')->with('comments')->findAll();
     * // find all posts together with their author and the author's profile
     * Post::model()->with('author')->with('author.profile')->findAll();
     * </pre>
     * The relations should be declared in {@link relations()}.
     * By default, the options specified in {@link relations()} will be used
     * to do relational query. In order to customize the options on the fly,
     * we should pass an array parameter to the with() method. The array keys
     * are relation names, and the array values are the corresponding query options.
     * For example,
     * <pre>
     * Post::model()->with(array(
     * 		'author'=>array('select'>'id,name'),
     * 		'comments'=>array('condition'=>'approved=1', 'order'=>'create_time),
     * ))->findAll();
     * </pre>
     * @return array
     */
    public function with()
    {
        if(func_num_args()>0)
        {
            $with = func_get_args();
            if(is_array($with[0]))  // the parameter is given as an array
            	$with=$with[0];
            if(!empty($with))
            {
            	
            	$this->getDbCriteria()->mergeWith(array('with'=>$with));
            }
        }
        return $this;
    }
    
    /**
     * Returns the table alias to be used by the find methods.
     * In relational queries, the returned table alias may vary according to
     * the corresponding relation declaration. Also, the default table alias
     * set by {@link setTableAlias} may be overridden by the applied scopes.
     * @param boolean $quote whether to quote the alias name
     * @param boolean $checkScopes whether to check if a table alias is defined in the applied scopes so far.
     * This parameter must be set false when calling this method in {@link defaultScope}.
     * An infinite loop would be formed otherwise.
     * @return string the default table alias
     */
    public function getTableAlias($quote=false, $checkScopes=true)
    {
    	if($checkScopes && ($criteria=$this->getDbCriteria(false))!==null && $criteria->alias!='')
    		$alias=$criteria->alias;
    	else
    		$alias=$this->_alias;
    	return $quote ? $this->getDbConnection()->getSchema()->quote($alias) : $alias;
    }
    
    /**
     * Returns the metadata of the table that this AR belongs to
     * @return CDbTableSchema the metadata of the table that this AR belongs to
     */
    public function getTableSchema()
    {
    	return $this->getMetaData()->tableSchema;
    }
    
    /**
     * Returns the meta-data for this AR
     * @return ActiveRecordMetaData the meta for this AR class.
     */
    public function getMetaData()
    {
    	if($this->_md!==null)
    		return $this->_md;
    	else
    		return $this->_md=self::model(get_class($this))->_md;
    }
    
    /**
     * @param string $name scope name
     * @return ActiveRecord
     */
    public function scope($name)
    {
    	$scopes=$this->scopes();
    	if(isset($scopes[$name]))
    	{
    		$this->getDbCriteria()->mergeWith($scopes[$name]);
    		return $this;
    	}
		return $this;
    }
    
    /**
     * Returns the declaration of named scopes.
     * A named scope represents a query criteria that can be chained together with
     * other named scopes and applied to a query. This method should be overridden
     * by child classes to declare named scopes for the particular AR classes.
     * For example, the following code declares two named scopes: 'recently' and
     * 'published'.
     * <pre>
     * return array(
     *     'published'=>array(
     *           'condition'=>'status=1',
     *     ),
     *     'recently'=>array(
     *           'order'=>'create_time DESC',
     *           'limit'=>5,
     *     ),
     * );
     * </pre>
     * If the above scopes are declared in a 'Post' model, we can perform the following
     * queries:
     * <pre>
     * $posts=Post::model()->scope('published)->findAll();
     * $posts=Post::model()->scope('published')->scope('recently')->findAll();
     * $posts=Post::model()->scope('published')->with('comments')->findAll();
     * </pre>
     * Note that the last query is a relational query.
     *
     * @return array the scope definition. The array keys are scope names; the array
     * values are the corresponding scope definitions. Each scope definition is represented
     * as an array whose keys must be properties of {@link DbCriteria}.
     */
    public function scopes()
    {
    	return array();
    }
    
    /**
     * Returns the default named scope that should be implicitly applied to all queries for this model.
     * Note, default scope only applies to SELECT queries. It is ignored for INSERT, UPDATE and DELETE queries.
     * The default implementation simply returns an empty array. You may override this method
     * if the model needs to be queried with some default criteria (e.g. only active records should be returned).
     * @return array the query criteria. This will be used as the parameter to the constructor
     * of {@link DbCriteria}.
     */
    public function defaultScope()
    {
    	return array();
    }
    
    /**
     * Resets all scopes and criterias applied including default scope.
     *
     * @return ActiveRecord
     */
    public function resetScope()
    {
    	$this->_c=new DbCriteria();
    	return $this;
    }
    
    /**
     * Applies the query scopes to the given criteria.
     * This method merges {@link dbCriteria} with the given criteria parameter.
     * It then resets {@link dbCriteria} to be null.
     * @param DbCriteria $criteria the query criteria. This parameter may be modified by merging {@link dbCriteria}.
     */
    public function applyScopes(&$criteria)
    {
    	if(!empty($criteria->scopes))
    	{
    		$scs=$this->scopes();
    		$c=$this->getDbCriteria();
    		foreach((array)$criteria->scopes as $name)
    		{
    			call_user_func_array(array($this,scope),(array)$name);
    		}
    	}
    	
    	if(isset($c) || ($c=$this->getDbCriteria(false))!==null)
    	{
    		$c->mergeWith($criteria);
    		$criteria=$c;
    		$this->_c=null;
    	}
    }
    
    /**
     * Sets the named attribute value.
     * You may also use $this->AttributeName to set the attribute value.
     * @param string $name the attribute name
     * @param mixed $value the attribute value.
     * @return boolean whether the attribute exists and the assignment is conducted successfully
     * @see hasAttribute
     */
    public function setAttribute($name,$value)
    {
    	if(property_exists($this,$name))
    		$this->$name=$value;
    	else if(isset($this->getMetaData()->columns[$name]))
    		$this->_attributes[$name]=$value;
    	else
    		return false;
    	return true;
    }
    
    /**
     * Returns the related record(s).
     * This method will return the related record(s) of the current record.
     * If the relation is HAS_ONE or BELONGS_TO, it will return a single object
     * or null if the object does not exist.
     * 
     */
    public function getRelated()
    {
        
    }
    
    protected function beforeFind($criteria)
    {
        
    }
    
    protected function afterFind()
    {
    
    }
    
}