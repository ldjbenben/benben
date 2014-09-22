<?php
namespace benben\db\ar;

class JoinQuery 
{
	/**
	 * @var array list of column selections
	 */
	public $selects=array();
	/**
	 * @var boolean whether to select distinct result set
	*/
	public $distinct=false;
	/**
	 * @var array list of join statement
	 */
	public $joins=array();
	/**
	 * @var array list of WHERE clauses
	*/
	public $conditions=array();
	/**
	 * @var array list of ORDER BY clauses
	*/
	public $orders=array();
	/**
	 * @var array list of GROUP BY clauses
	*/
	public $groups=array();
	/**
	 * @var array list of HAVING clauses
	*/
	public $havings=array();
	/**
	 * @var integer row limit
	*/
	public $limit=-1;
	/**
	 * @var integer row offset
	 */
	public $offset=-1;
	/**
	 * @var array list of query parameters
	 */
	public $params=array();
	/**
	 * @var array list of join element IDs (id=>true)
	*/
	public $elements=array();
	
	/**
	 * Constructor.
	 * @param JoinElement $joinElement The root join tree.
	 * @param CDbCriteria $criteria the query criteria
	*/
	public function __construct($joinElement,$criteria=null)
	{
		if($criteria!==null)
		{
			$this->selects[]=$joinElement->getColumnSelect($criteria->select);
			$this->joins[]=$joinElement->getTableNameWithAlias();
			$this->joins[]=$criteria->join;
			$this->conditions[]=$criteria->condition;
			$this->orders[]=$criteria->order;
			$this->groups[]=$criteria->group;
			$this->havings[]=$criteria->having;
			$this->limit=$criteria->limit;
			$this->offset=$criteria->offset;
			$this->params=$criteria->params;
			if(!$this->distinct && $criteria->distinct)
				$this->distinct=true;
		}
		else
		{
			$this->selects[]=$joinElement->getPrimaryKeySelect();
			$this->joins[]=$joinElement->getTableNameWithAlias();
			$this->conditions[]=$joinElement->getPrimaryKeyRange();
		}
		$this->elements[$joinElement->id]=true;
	}
	
	/**
	 * Joins with another join element
	 * @param JoinElement $element the element to be joined
	 */
	public function join($element)
	{
		if($element->slave!==null)
			$this->join($element->slave);
		if(!empty($element->relation->select))
			$this->selects[]=$element->getColumnSelect($element->relation->select);
		$this->conditions[]=$element->relation->condition;
		$this->orders[]=$element->relation->order;
		$this->joins[]=$element->getJoinCondition();
		$this->joins[]=$element->relation->join;
		$this->groups[]=$element->relation->group;
		$this->havings[]=$element->relation->having;
	
		if(is_array($element->relation->params))
		{
			if(is_array($this->params))
				$this->params=array_merge($this->params,$element->relation->params);
			else
				$this->params=$element->relation->params;
		}
		$this->elements[$element->id]=true;
	}
	
	/**
	 * Creates the SQL statement.
	 * @param CDbCommandBuilder $builder the command builder
	 * @return CDbCommand DB command instance representing the SQL statement
	 */
	public function createCommand($builder)
	{
		$sql=($this->distinct ? 'SELECT DISTINCT ':'SELECT ') . implode(', ',$this->selects);
		$sql.=' FROM ' . implode(' ',$this->joins);
	
		$conditions=array();
		foreach($this->conditions as $condition)
		if($condition!=='')
			$conditions[]=$condition;
		if($conditions!==array())
			$sql.=' WHERE (' . implode(') AND (',$conditions).')';
	
		$groups=array();
		foreach($this->groups as $group)
		if($group!=='')
			$groups[]=$group;
		if($groups!==array())
			$sql.=' GROUP BY ' . implode(', ',$groups);
	
		$havings=array();
		foreach($this->havings as $having)
		if($having!=='')
			$havings[]=$having;
		if($havings!==array())
			$sql.=' HAVING (' . implode(') AND (',$havings).')';
	
		$orders=array();
		foreach($this->orders as $order)
		if($order!=='')
			$orders[]=$order;
		if($orders!==array())
			$sql.=' ORDER BY ' . implode(', ',$orders);
	
		$sql=$builder->applyLimit($sql,$this->limit,$this->offset);
		$command=$builder->getDbConnection()->createCommand($sql);
		$builder->bindValues($command,$this->params);
		return $command;
	}
}