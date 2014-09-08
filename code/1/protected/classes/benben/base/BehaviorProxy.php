<?php

namespace benben\base;

use benben\Benben;
class BehaviorProxy
{
	private $_m;
	private $_owner;
	
	public function __construct($owner)
	{
		$this->_owner = $owner;
	}
	
	public function getM()
	{
		return $this->_m;
	}
	
	/**
	 * Attaches a list of behaviors to the component.
	 * Each behavior is indexed by its name and should be an instance of
	 * {@link IBehavior}, a string specifying the behavior class, or an
	 * array of the following structure:
	 * <pre>
	 * array(
	 *     'class'=>'path.to.BehaviorClass',
	 *     'property1'=>'value1',
	 *     'property2'=>'value2',
	 * )
	 * </pre>
	 * @param array $behaviors list of behaviors to be attached to the component
	 */
	public function attachBehaviors($behaviors)
	{
		foreach($behaviors as $name=>$behavior)
			$this->attachBehavior($name,$behavior);
	}
	
	/**
	 * Detaches all behaviors from the component.
	 */
	public function detachBehaviors()
	{
		if($this->_m!==null)
		{
			foreach($this->_m as $name=>$behavior)
				$this->detachBehavior($name);
			$this->_m=null;
		}
	}
	
	/**
	 * Attaches a behavior to this component.
	 * This method will create the behavior object based on the given
	 * configuration. After that, the behavior object will be initialized
	 * by calling its {@link IBehavior::attach} method.
	 * @param string $name the behavior's name. It should uniquely identify this behavior.
	 * @param mixed $behavior the behavior configuration. This is passed as the first
	 * parameter to {@link YiiBase::createComponent} to create the behavior object.
	 * @return IBehavior the behavior object
	 */
	public function attachBehavior($name,$behavior)
	{
		if(!($behavior instanceof IBehavior))
			$behavior=Benben::createComponent($behavior);
		$behavior->setEnabled(true);
		$behavior->attach($this->_owner);
		return $this->_m[$name]=$behavior;
	}
	
	/**
	 * Detaches a behavior from the component.
	 * The behavior's {@link IBehavior::detach} method will be invoked.
	 * @param string $name the behavior's name. It uniquely identifies the behavior.
	 * @return IBehavior the detached behavior. Null if the behavior does not exist.
	 */
	public function detachBehavior($name)
	{
		if(isset($this->_m[$name]))
		{
			$this->_m[$name]->detach($this);
			$behavior=$this->_m[$name];
			unset($this->_m[$name]);
			return $behavior;
		}
	}
	
	/**
	 * Enables all behaviors attached to this component.
	 */
	public function enableBehaviors()
	{
		if($this->_m!==null)
		{
			foreach($this->_m as $behavior)
				$behavior->setEnabled(true);
		}
	}
	
	/**
	 * Disables all behaviors attached to this component.
	 */
	public function disableBehaviors()
	{
		if($this->_m!==null)
		{
			foreach($this->_m as $behavior)
				$behavior->setEnabled(false);
		}
	}
	
	/**
	 * Enables an attached behavior.
	 * A behavior is only effective when it is enabled.
	 * A behavior is enabled when first attached.
	 * @param string $name the behavior's name. It uniquely identifies the behavior.
	 */
	public function enableBehavior($name)
	{
		if(isset($this->_m[$name]))
			$this->_m[$name]->setEnabled(true);
	}
	
	/**
	 * Disables an attached behavior.
	 * A behavior is only effective when it is enabled.
	 * @param string $name the behavior's name. It uniquely identifies the behavior.
	 */
	public function disableBehavior($name)
	{
		if(isset($this->_m[$name]))
			$this->_m[$name]->setEnabled(false);
	}
}