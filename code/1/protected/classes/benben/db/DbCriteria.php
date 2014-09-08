<?php
namespace benben\db;

use benben\collections\CMap;
class DbCriteria
{
   public $select = '*';
   public $condition = '';
   public $limit = -1;
   public $offset = -1;
   public $order = '';
   public $with;
   public $group = '';
   public $alias = '';
   public $distinct = '';
   public $join = '';
   public $having = '';
   public $params=array();
   /**
    * @var boolean whether the foreign tables should be joined with the primary table in a single SQL.
    * This property is only used in relational AR queries for HAS_MANY and MANY_MANY relations.
    *
    * When this property is set true, only a single SQL will be executed for a relational AR query,
    * even if the primary table is limited and the relationship between a foreign table and the primary
    * table is many-to-one.
    *
    * When this property is set false, a SQL statement will be executed for each HAS_MANY relation.
    *
    * When this property is not set, if the primary table is limited or paginated,
    * a SQL statement will be executed for each HAS_MANY relation.
    * Otherwise, a single SQL statement will be executed for all.
    *
    */
   public $together;
   /**
    * @var string the name of the AR attribute whose value should be used as index of the query result array.
    * Defaults to null, meaning the result array will be zero-based integers.
    */
   public $index;
   /**
    * @var mixed scopes to apply
    *
    * This property is effective only when passing criteria to
    * the one of the following methods:
    * <ul>
    * <li>{@link ActiveRecord::find()}</li>
    * <li>{@link ActiveRecord::findAll()}</li>
    * <li>{@link ActiveRecord::findByPk()}</li>
    * <li>{@link ActiveRecord::findAllByPk()}</li>
    * <li>{@link ActiveRecord::findByAttributes()}</li>
    * <li>{@link ActiveRecord::findAllByAttributes()}</li>
    * <li>{@link ActiveRecord::count()}</li>
    * </ul>
    *
    * Can be set to one of the following:
    * <ul>
    * <li>One scope: $criteria->scopes='scopeName';</li>
    * <li>Multiple scopes: $criteria->scopes=array('scopeName1','scopeName2');</li>
    * <li>Scope with parameters: $criteria->scopes=array('scopeName'=>array($params));</li>
    * <li>Multiple scopes with parameters: $criteria->scopes=array('scopeName1'=>array($params1),'scopeName2'=>array($params2));</li>
    * <li>Multiple scopes with the same name: array(array('scopeName'=>array($params1)),array('scopeName'=>array($params2)));</li>
    * </ul>
    */
   public $scopes;
   
   /**
    * Constructor.
    * @param array $data criteria initial property values (indexed by property name)
    */
   public function __construct($data=array())
   {
   	foreach($data as $name=>$value)
   		$this->$name=$value;
   }
   
	/**
	 * Appends a condition to the existing {@link condition}.
	 * The new condition and the existing condition will be concatenated via the specified operator
	 * which defaults to 'AND'.
	 * The new condition can also be an array. In this case, all elements in the array
	 * will be concatenated together via the operator.
	 * This method handles the case when the existing condition is empty.
	 * After calling this method, the {@link condition} property will be modified.
	 * @param mixed $condition the new condition. It can be either a string or an array of strings.
	 * @param string $operator the operator to join different conditions. Defaults to 'AND'.
	 * @return DbCriteria the criteria object itself
	 */
	public function addCondition($condition,$operator='AND')
	{
		if(is_array($condition))
		{
			if($condition===array())
				return $this;
			$condition='('.implode(') '.$operator.' (',$condition).')';
		}
		if($this->condition==='')
			$this->condition=$condition;
		else
			$this->condition='('.$this->condition.') '.$operator.' ('.$condition.')';
		return $this;
	}
	
	/**
	 * Merges with another criteria.
	 * In general, the merging makes the resulting criteria more restrictive.
	 * For example, if both criterias have conditions, they will be 'AND' together.
	 * Also, the criteria passed as the parameter takes precedence in case
	 * two options cannot be merged (e.g. LIMIT, OFFSET).
	 * @param mixed $criteria the criteria to be merged with. Either an array or CDbCriteria.
	 * @param boolean $useAnd whether to use 'AND' to merge condition and having options.
	 * If false, 'OR' will be used instead. Defaults to 'AND'.
	 */
	public function mergeWith($criteria,$useAnd=true)
	{
		$and=$useAnd ? 'AND' : 'OR';
		if(is_array($criteria))
			$criteria=new self($criteria);
		if($this->select!==$criteria->select)
		{
			if($this->select==='*')
				$this->select=$criteria->select;
			else if($criteria->select!=='*')
			{
				$select1=is_string($this->select)?preg_split('/\s*,\s*/',trim($this->select),-1,PREG_SPLIT_NO_EMPTY):$this->select;
				$select2=is_string($criteria->select)?preg_split('/\s*,\s*/',trim($criteria->select),-1,PREG_SPLIT_NO_EMPTY):$criteria->select;
				$this->select=array_merge($select1,array_diff($select2,$select1));
			}
		}
	
		if($this->condition!==$criteria->condition)
		{
			if($this->condition==='')
				$this->condition=$criteria->condition;
			else if($criteria->condition!=='')
				$this->condition="({$this->condition}) $and ({$criteria->condition})";
		}
	
		if($this->params!==$criteria->params)
			$this->params=array_merge($this->params,$criteria->params);
	
		if($criteria->limit>0)
			$this->limit=$criteria->limit;
	
		if($criteria->offset>=0)
			$this->offset=$criteria->offset;
	
		if($criteria->alias!==null)
			$this->alias=$criteria->alias;
	
		if($this->order!==$criteria->order)
		{
			if($this->order==='')
				$this->order=$criteria->order;
			else if($criteria->order!=='')
				$this->order=$criteria->order.', '.$this->order;
		}
	
		if($this->group!==$criteria->group)
		{
			if($this->group==='')
				$this->group=$criteria->group;
			else if($criteria->group!=='')
				$this->group.=', '.$criteria->group;
		}
	
		if($this->join!==$criteria->join)
		{
			if($this->join==='')
				$this->join=$criteria->join;
			else if($criteria->join!=='')
				$this->join.=' '.$criteria->join;
		}
	
		if($this->having!==$criteria->having)
		{
			if($this->having==='')
				$this->having=$criteria->having;
			else if($criteria->having!=='')
				$this->having="({$this->having}) $and ({$criteria->having})";
		}
	
		if($criteria->distinct>0)
			$this->distinct=$criteria->distinct;
	
		if($criteria->together!==null)
			$this->together=$criteria->together;
	
		if($criteria->index!==null)
			$this->index=$criteria->index;
	
		if(empty($this->scopes))
			$this->scopes=$criteria->scopes;
		else if(!empty($criteria->scopes))
		{
			$scopes1=(array)$this->scopes;
			$scopes2=(array)$criteria->scopes;
			foreach($scopes1 as $k=>$v)
			{
				if(is_integer($k))
					$scopes[]=$v;
				else if(isset($scopes2[$k]))
					$scopes[]=array($k=>$v);
				else
					$scopes[$k]=$v;
			}
			foreach($scopes2 as $k=>$v)
			{
				if(is_integer($k))
					$scopes[]=$v;
				else if(isset($scopes1[$k]))
					$scopes[]=array($k=>$v);
				else
					$scopes[$k]=$v;
			}
			$this->scopes=$scopes;
		}
	
		if(empty($this->with))
			$this->with=$criteria->with;
		else if(!empty($criteria->with))
		{
			$this->with=(array)$this->with;
			foreach((array)$criteria->with as $k=>$v)
			{
				if(is_integer($k))
					$this->with[]=$v;
				else if(isset($this->with[$k]))
				{
					$excludes=array();
					foreach(array('joinType','on') as $opt)
					{
						if(isset($this->with[$k][$opt]))
							$excludes[$opt]=$this->with[$k][$opt];
						if(isset($v[$opt]))
							$excludes[$opt]= ($opt==='on' && isset($excludes[$opt]) && $v[$opt]!==$excludes[$opt]) ?
							"($excludes[$opt]) AND $v[$opt]" : $v[$opt];
						unset($this->with[$k][$opt]);
						unset($v[$opt]);
					}
					$this->with[$k]=new self($this->with[$k]);
					$this->with[$k]->mergeWith($v,$useAnd);
					$this->with[$k]=$this->with[$k]->toArray();
					if (count($excludes)!==0)
						$this->with[$k]=CMap::mergeArray($this->with[$k],$excludes);
				}
				else
					$this->with[$k]=$v;
			}
		}
	}
   
}