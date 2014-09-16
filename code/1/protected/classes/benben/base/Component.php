<?php

namespace benben\base;

use benben\Benben;
/** 
 * @author benben
 * 
 * @property benben\base\EventProxy $eventProxy
 * @property benben\base\BehaviorProxy $behaviorProxy
 */
class Component 
{
	private $_m;
	private $_eventProxy = null;
	private $_behaviorProxy = null;
	
    public function __set($key, $value)
	{
		$methodName = "set".ucfirst($key);
		if(method_exists($this, $methodName))
		{
			call_user_func(array($this,$methodName), $value);
		}
	}
	
	public function __get($key)
	{
		$method_name = 'get'.ucfirst($key);
		if(method_exists($this, $method_name))
		{
			return call_user_func(array($this, $method_name));
		}
		return null;
	}
	
	/**
	 * Calls the named method which is not a class method.
	 * Do not call this method. This is a PHP magic method that we override
	 * to implement the behavior feature.
	 * @param string $name the method name
	 * @param array $parameters method parameters
	 * @return mixed the method return value
	 */
	public function __call($name,$parameters)
	{
		$m = $this->getBehaviorProxy()->getM();
		
		if($m!==null)
		{
			foreach($m as $object)
			{
				if($object->getEnabled() && method_exists($object,$name))
					return call_user_func_array(array($object,$name),$parameters);
			}
		}
		
		// 匿名函数是一个比较新的功能，作用也不是太大，暂时不使用
		/* if(class_exists('Closure', false) && $this->canGetProperty($name) && $this->$name instanceof Closure)
			return call_user_func_array($this->$name, $parameters); */
		throw new Exception(Benben::t('benben','{class} and its behaviors do not have a method or closure named "{name}".',
				array('{class}'=>get_class($this), '{name}'=>$name)));
	}
	
	/**
	 * Get EventProxy instance which refer to the $owner
	 * @param Component $owner
	 * @return \benben\base\EventProxy
	 */
	public function getEventProxy()
	{
		if($this->_eventProxy == null)
		{
			$this->_eventProxy = new EventProxy($this);
		}
		return $this->_eventProxy;
	}
	
	/**
	 * Get BehaviorProxy instance which refer to the $owner
	 * @return \benben\base\BehaviorProxy
	 */
	public function getBehaviorProxy()
	{
		if($this->_behaviorProxy == null)
		{
			$this->_behaviorProxy = new BehaviorProxy($this);
		}
		return $this->_behaviorProxy;
	}
}