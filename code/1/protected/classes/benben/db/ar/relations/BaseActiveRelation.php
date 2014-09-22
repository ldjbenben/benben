<?php
namespace benben\db\ar\relations;

use benben\base\Component;
use benben\db\DbCriteria;

/**
 * ActiveRelation is the base class for all active relations.
 */
class BaseActiveRelation extends Component
{
	/**
	 * name of the related object
	 * @var string
	 */
	public $name;
	/**
	 * name of the related active record class
	 * @var string
	 */
	public $className;
	/**
	 * the foreign key in this relation
	 * @var mixed
	 */
	public $foreignKey;
	/**
	 * list of column names (an array, or a string of names separated by commas) to be selected.
	 * Do not quote or preifx the column names unless they are used in an expression.
	 * In that case, you should prefix the column names with 'relationName.'.
	 * @var mixed
	 */
	public $select='*';
	/**
	 * @var string WHERE clause. For {@link ActiveRelation} descendant classes, column names
	 * referenced in the condition should be disambiguated with prefix 'relationName.'.
	 */
	public $condition='';
	/**
	 * @var array the parameters that are to be bound to the condition.
	 * The keys are parameter placeholder names, and the values are parameter values.
	 */
	public $params=array();
	/**
	 * @var string GROUP BY clause. For {@link ActiveRelation} descendant classes, column names
	 * referenced in this property should be disambiguated with prefix 'relationName.'.
	 */
	public $group='';
	/**
	 * @var string how to join with other tables. This refers to the JOIN clause in an SQL statement.
	 * For example, <code>'LEFT JOIN users ON users.id=authorID'</code>.
	 */
	public $join='';
	/**
	 * @var string HAVING clause. For {@link ActiveRelation} descendant classes, column names
	 * referenced in this property should be disambiguated with prefix 'relationName.'.
	 */
	public $having='';
	/**
	 * @var string ORDER BY clause. For {@link ActiveRelation} descendant classes, column names
	 * referenced in this property should be disambiguated with prefix 'relationName.'.
	 */
	public $order='';
	
	/**
	 * Constructor.
	 * @param string $name name of the relation
	 * @param string $className name of the realted active record class
	 * @param string $foreignKey foreign key for this relation
	 * @param array $options additional options (name=>value). The keys must be the property names of this class.
	 */
	public function __construct($name, $className, $foreignKey, $options=array())
	{
		$this->name=$name;
		$this->className=$className;
		$this->foreignKey=$foreignKey;
		foreach($options as $name=>$value)
			$this->$name=$value;
	}
	
	/**
	 * Merges this relation with a criteria specified dynamically.
	 * @param array $criteria the dynamically specified criteria
	 * @param boolean $fromScope whether the criteria to be merged is from scopes
	 */
	public function mergeWith($criteria, $fromScope=false)
	{
		if($criteria instanceof DbCriteria)
			$criteria=$criteria->toArray();
		if(isset($criteria['select']) && $this->select!==$criteria['select'])
		{
			if($this->select==='*')
				$this->select=$criteria['select'];
			else if($criteria['select']!=='*')
			{
				$select1=is_string($this->select)?preg_split('/\s*,\s*/',trim($this->select),-1,PREG_SPLIT_NO_EMPTY):$this->select;
				$select2=is_string($criteria['select'])?preg_split('/\s*,\s*/',trim($criteria['select']),-1,PREG_SPLIT_NO_EMPTY):$criteria['select'];
				$this->select=array_merge($select1,array_diff($select2,$select1));
			}
		}
		
		if(isset($criteria['condition']) && $this->condition!==$criteria['condition'])
		{
			if($this->condition==='')
				$this->condition=$criteria['condition'];
			else if($criteria['condition']!=='')
				$this->condition="({$this->condition}) AND ({$criteria['condition']})";
		}
		
		if(isset($criteria['params']) && $this->params!==$criteria['params'])
			$this->params=array_merge($this->params,$criteria['params']);
		
		if(isset($criteria['order']) && $this->order!==$criteria['order'])
		{
			if($this->order==='')
				$this->order=$criteria['order'];
			else if($criteria['order']!=='')
				$this->order=$criteria['order'].', '.$this->order;
		}
		
		if(isset($criteria['group']) && $this->group!==$criteria['group'])
		{
			if($this->group==='')
				$this->group=$criteria['group'];
			else if($criteria['group']!=='')
				$this->group.=', '.$criteria['group'];
		}
		
		if(isset($criteria['join']) && $this->join!==$criteria['join'])
		{
			if($this->join==='')
				$this->join=$criteria['join'];
			else if($criteria['join']!=='')
				$this->join.=' '.$criteria['join'];
		}
		
		if(isset($criteria['having']) && $this->having!==$criteria['having'])
		{
			if($this->having==='')
				$this->having=$criteria['having'];
			else if($criteria['having']!=='')
				$this->having="({$this->having}) AND ({$criteria['having']})";
		}
	}
}