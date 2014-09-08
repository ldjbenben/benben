<?php
namespace benben\web;
use benben\base\Model;
/**
 * FormModel class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * FormModel represents a data model that collects HTML form inputs.
 *
 * Unlike {@link ActiveRecord}, the data collected by FormModel are stored
 * in memory only, instead of database.
 *
 * To collect user inputs, you may extend FormModel and define the attributes
 * whose values are to be collected from user inputs. You may override
 * {@link rules()} to declare validation rules that should be applied to
 * the attributes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 1.0
 */
class FormModel extends Model
{
	private static $_names=array();

	/**
	 * Constructor.
	 * @param string $scenario name of the scenario that this model is used in.
	 * See {@link Model::scenario} on how scenario is used by models.
	 * @see getScenario
	 */
	public function __construct($scenario='')
	{
		$this->setScenario($scenario);
		$this->init();
		$this->getBehaviorProxy()->attachBehaviors($this->behaviors());
	}

	/**
	 * Initializes this model.
	 * This method is invoked in the constructor right after {@link scenario} is set.
	 * You may override this method to provide code that is needed to initialize the model (e.g. setting
	 * initial property values.)
	 */
	public function init()
	{
	}

	protected function behaviors()
	{
		return array();
	}
	
	/**
	 * Returns the list of attribute names.
	 * By default, this method returns all public properties of the class.
	 * You may override this method to change the default.
	 * @return array list of attribute names. Defaults to all public properties of the class.
	 */
	public function attributeNames()
	{
		$className=get_class($this);
		if(!isset(self::$_names[$className]))
		{
			$class=new \ReflectionClass(get_class($this));
			$names=array();
			foreach($class->getProperties() as $property)
			{
				$name=$property->getName();
				if($property->isPublic() && !$property->isStatic())
					$names[]=$name;
			}
			return self::$_names[$className]=$names;
		}
		else
			return self::$_names[$className];
	}
}