<?php

namespace benben\db\ar\relations;

use benben\db\DbCriteria;
class StatRelation extends BaseActiveRelation 
{
	/**
	 * @var string the statistical expression. Defaults to 'COUNT(*)', meaning
	 * the count of child objects.
	 */
	public $select='COUNT(*)';
	/**
	 * @var mixed the default value to be assigned to those records that do not
	 * receive a statistical query result. Defaults to 0.
	 */
	public $defaultValue=0;
	
	/**
	 * Merges this relation with a criteria specified dynamically.
	 * @param array $criteria the dynamically specified criteria
	 * @param boolean $fromScope whether the criteria to be merged is from scopes
	 */
	public function mergeWith($criteria,$fromScope=false)
	{
		if($criteria instanceof DbCriteria)
		{
			$criteria=$criteria->toArray();
		}
		
		parent::mergeWith($criteria,$fromScope);
	
		if(isset($criteria['defaultValue']))
		{
			$this->defaultValue=$criteria['defaultValue'];
		}
	}
}