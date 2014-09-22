<?php
namespace benben\db\ar;

use benben\base\Component;
use benben\db\DbException;
use benben\Benben;
use benben\db\ar\ActiveRelation;
use benben\db\ar\relations\StatRelation;
/**
 * ActiveRecord class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * ActiveFinder implements eager loading and lazy loading of related active records.
 *
 * When used in eager loading, this class provides the same set of find methods as
 * {@link ActiveRecord}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.db.ar
 * @since 1.0
 */
class ActiveFinder extends Component
{
	/**
	 * @var boolean join all tables all at once. Defaults to false.
	 * This property is internally used.
	 */
	public $joinAll=false;
	/**
	 * @var boolean whether the base model has limit or offset.
	 * This property is internally used.
	 */
	public $baseLimited=false;

	private $_joinCount=0;
	private $_joinTree;
	private $_builder;

	/**
	 * Constructor.
	 * A join tree is built up based on the declared relationships between active record classes.
	 * @param ActiveRecord $model the model that initiates the active finding process
	 * @param mixed $with the relation names to be actively looked for
	 */
	public function __construct($model,$with)
	{
		$this->_builder=$model->getCommandBuilder();
		$this->_joinTree=new JoinElement($this,$model);
		$this->buildJoinTree($this->_joinTree,$with);
	}

	/**
	 * Do not call this method. This method is used internally to perform the relational query
	 * based on the given DB criteria.
	 * @param CDbCriteria $criteria the DB criteria
	 * @param boolean $all whether to bring back all records
	 * @return mixed the query result
	 */
	public function query($criteria,$all=false)
	{
		$this->joinAll=$criteria->together===true;

		if($criteria->alias!='')
		{
			$this->_joinTree->tableAlias=$criteria->alias;
			$this->_joinTree->rawTableAlias=$this->_builder->getSchema()->quoteTableName($criteria->alias);
		}

		$this->_joinTree->find($criteria);
		$this->_joinTree->afterFind();

		if($all)
		{
			$result = array_values($this->_joinTree->records);
			if ($criteria->index!==null)
			{
				$index=$criteria->index;
				$array=array();
				foreach($result as $object)
					$array[$object->$index]=$object;
				$result=$array;
			}
		}
		elseif(count($this->_joinTree->records))
			$result = reset($this->_joinTree->records);
		else
			$result = null;

		$this->destroyJoinTree();
		return $result;
	}

	/**
	 * This method is internally called.
	 * @param string $sql the SQL statement
	 * @param array $params parameters to be bound to the SQL statement
	 * @return ActiveRecord
	 */
	public function findBySql($sql,$params=array())
	{
		Benben::trace(get_class($this->_joinTree->model).'.findBySql() eagerly','system.db.ar.ActiveRecord');
		if(($row=$this->_builder->createSqlCommand($sql,$params)->queryRow())!==false)
		{
			$baseRecord=$this->_joinTree->model->populateRecord($row,false);
			$this->_joinTree->findWithBase($baseRecord);
			$this->_joinTree->afterFind();
			$this->destroyJoinTree();
			return $baseRecord;
		}
		else
			$this->destroyJoinTree();
	}

	/**
	 * This method is internally called.
	 * @param string $sql the SQL statement
	 * @param array $params parameters to be bound to the SQL statement
	 * @return ActiveRecord[]
	 */
	public function findAllBySql($sql,$params=array())
	{
		Benben::trace(get_class($this->_joinTree->model).'.findAllBySql() eagerly','system.db.ar.ActiveRecord');
		if(($rows=$this->_builder->createSqlCommand($sql,$params)->queryAll())!==array())
		{
			$baseRecords=$this->_joinTree->model->populateRecords($rows,false);
			$this->_joinTree->findWithBase($baseRecords);
			$this->_joinTree->afterFind();
			$this->destroyJoinTree();
			return $baseRecords;
		}
		else
		{
			$this->destroyJoinTree();
			return array();
		}
	}

	/**
	 * This method is internally called.
	 * @param CDbCriteria $criteria the query criteria
	 * @return string
	 */
	public function count($criteria)
	{
		Benben::trace(get_class($this->_joinTree->model).'.count() eagerly','system.db.ar.ActiveRecord');
		$this->joinAll=$criteria->together!==true;

		$alias=$criteria->alias===null ? 't' : $criteria->alias;
		$this->_joinTree->tableAlias=$alias;
		$this->_joinTree->rawTableAlias=$this->_builder->getSchema()->quoteTableName($alias);

		$n=$this->_joinTree->count($criteria);
		$this->destroyJoinTree();
		return $n;
	}

	/**
	 * Finds the related objects for the specified active record.
	 * This method is internally invoked by {@link ActiveRecord} to support lazy loading.
	 * @param ActiveRecord $baseRecord the base record whose related objects are to be loaded
	 */
	public function lazyFind($baseRecord)
	{
		$this->_joinTree->lazyFind($baseRecord);
		if(!empty($this->_joinTree->children))
		{
			foreach($this->_joinTree->children as $child)
				$child->afterFind();
		}
		$this->destroyJoinTree();
	}

	private function destroyJoinTree()
	{
		if($this->_joinTree!==null)
			$this->_joinTree->destroy();
		$this->_joinTree=null;
	}

	/**
	 * Builds up the join tree representing the relationships involved in this query.
	 * @param JoinElement $parent the parent tree node
	 * @param mixed $with the names of the related objects relative to the parent tree node
	 * @param array $options additional query options to be merged with the relation
	 */
	private function buildJoinTree($parent,$with,$options=null)
	{
		if($parent instanceof CStatElement)
			throw new DbException(Benben::t('benben','The STAT relation "{name}" cannot have child relations.',
				array('{name}'=>$parent->relation->name)));

		if(is_string($with))
		{
			if(($pos=strrpos($with,'.'))!==false)
			{
				$parent=$this->buildJoinTree($parent,substr($with,0,$pos));
				$with=substr($with,$pos+1);
			}

			// named scope
			$scopes=array();
			if(($pos=strpos($with,':'))!==false)
			{
				$scopes=explode(':',substr($with,$pos+1));
				$with=substr($with,0,$pos);
			}

			if(isset($parent->children[$with]) && $parent->children[$with]->master===null)
				return $parent->children[$with];

			if(($relation=$parent->model->getActiveRelation($with))===null)
				throw new DbException(Benben::t('benben','Relation "{name}" is not defined in active record class "{class}".',
					array('{class}'=>get_class($parent->model), '{name}'=>$with)));

			$relation=clone $relation;
			$model=ActiveRecord::model($relation->className);

			if($relation instanceof \benben\db\ar\relations\ActiveRelation)
			{
				$oldAlias=$model->getTableAlias(false,false);
				if(isset($options['alias']))
					$model->setTableAlias($options['alias']);
				elseif($relation->alias===null)
					$model->setTableAlias($relation->name);
				else
					$model->setTableAlias($relation->alias);
			}

			if(!empty($relation->scopes))
				$scopes=array_merge($scopes,(array)$relation->scopes); // no need for complex merging

			if(!empty($options['scopes']))
				$scopes=array_merge($scopes,(array)$options['scopes']); // no need for complex merging

			$model->resetScope(false);
			$criteria=$model->getDbCriteria();
			$criteria->scopes=$scopes;
			$model->beforeFindInternal();
			$model->applyScopes($criteria);

			// select has a special meaning in stat relation, so we need to ignore select from scope or model criteria
			if($relation instanceof StatRelation)
				$criteria->select='*';

			$relation->mergeWith($criteria,true);

			// dynamic options
			if($options!==null)
				$relation->mergeWith($options);

			if($relation instanceof ActiveRelation)
				$model->setTableAlias($oldAlias);

			if($relation instanceof StatRelation)
				return new CStatElement($this,$relation,$parent);
			else
			{
				if(isset($parent->children[$with]))
				{
					$element=$parent->children[$with];
					$element->relation=$relation;
				}
				else
					$element=new JoinElement($this,$relation,$parent,++$this->_joinCount);
				if(!empty($relation->through))
				{
					$slave=$this->buildJoinTree($parent,$relation->through,array('select'=>false));
					$slave->master=$element;
					$element->slave=$slave;
				}
				$parent->children[$with]=$element;
				if(!empty($relation->with))
					$this->buildJoinTree($element,$relation->with);
				return $element;
			}
		}

		// $with is an array, keys are relation name, values are relation spec
		foreach($with as $key=>$value)
		{
			if(is_string($value))  // the value is a relation name
				$this->buildJoinTree($parent,$value);
			elseif(is_string($key) && is_array($value))
				$this->buildJoinTree($parent,$key,$value);
		}
	}
}