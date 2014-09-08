<?php

namespace benben\base;

use benben\Benben;
class EventProxy 
{
	private $_owner = null;
	private $_e;
	
	public function __construct($owner)
	{
		$this->_owner = $owner;
	}
	
	/**
	 * Determines whether an event is defined.
	 * An event is defined if the class has a method named like 'onXXX'.
	 * Note, event name is case-insensitive.
	 * @param string $name the event name
	 * @return boolean whether an event is defined
	 */
	public function hasEvent($name)
	{
		return !strncasecmp($name,'on',2) && method_exists($this->_owner,$name);
	}
	
	/**
	 * Checks whether the named event has attached handlers.
	 * @param string $name the event name
	 * @return boolean whether an event has been attached one or several handlers
	 */
	public function hasEventHandler($name)
	{
		$name=strtolower($name);
		return ( isset($this->_e[$name]) && count($this->_e[$name])>0 );
	}
	
	/**
	 * Attaches an event handler to an event.
	 *
	 * An event handler must be a valid PHP callback, i.e., a string referring to
	 * a global function name, or an array containing two elements with
	 * the first element being an object and the second element a method name
	 * of the object.
	 *
	 * An event handler must be defined with the following signature,
	 * <pre>
	 * function handlerName($event) {}
	 * </pre>
	 * where $event includes parameters associated with the event.
	 *
	 * This is a convenient method of attaching a handler to an event.
	 * It is equivalent to the following code:
	 * <pre>
	 * $component->getEventHandlers($eventName)->add($eventHandler);
	 * </pre>
	 *
	 * Using {@link getEventHandlers}, one can also specify the excution order
	 * of multiple handlers attaching to the same event. For example:
	 * <pre>
	 * $component->getEventHandlers($eventName)->insertAt(0,$eventHandler);
	 * </pre>
	 * makes the handler to be invoked first.
	 *
	 * @param string $name the event name
	 * @param callback $handler the event handler
	 * @throws benben\base\Exception if the event is not defined
	 * @see detachEventHandler
	 */
	public function attachEventHandler($name,$handler)
	{
		if($this->hasEvent($name))
		{
			$name=strtolower($name);
			if(!isset($this->_e[$name]))
			{
				$this->_e[$name]=array();
			}
			$this->_e[$name][] = $handler;
		}
		else
			throw new Exception(Benben::t('benben','Event "{class}.{event}" is not defined.',
					array('{class}'=>get_class($this->_owner), '{event}'=>$name)));
	}
	
	public function attachEventHandlers($events)
	{
		foreach($this->_owner->events() as $name=>$callback)
		{
			$this->attachEventHandler($name,$callback);
		}
	}
	
	/**
	 * Detaches an existing event handler.
	 * This method is the opposite of {@link attachEventHandler}.
	 * @param string $name event name
	 * @param callback $handler the event handler to be removed
	 * @return boolean if the detachment process is successful
	 * @see attachEventHandler
	 */
	public function detachEventHandler($name,$handler)
	{
		$handlers = $this->hasEventHandler($name);
		
		if($handlers)
		{
			if(($index=array_search($handler,$handlers,true))!==false)
			{
				unset($handlers[$index]);
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Raises an event.
	 * This method represents the happening of an event. It invokes
	 * all attached handlers for the event.
	 * @param string $name the event name
	 * @param Event $event the event parameter
	 * @throws benben\base\Exception if the event is undefined or an event handler is invalid.
	 */
	public function raiseEvent($name,$event)
	{
		$name=strtolower($name);
		if(isset($this->_e[$name]))
		{
			foreach($this->_e[$name] as $handler)
			{
				if(is_string($handler))
					call_user_func($handler,$event);
				elseif(is_callable($handler,true))
				{
					if(is_array($handler))
					{
						// an array: 0 - object, 1 - method name
						list($object,$method)=$handler;
						if(is_string($object))	// static method call
							call_user_func($handler,$event);
						elseif(method_exists($object,$method))
						$object->$method($event);
						else
							throw new Exception(Benben::t('benben','Event "{class}.{event}" is attached with an invalid handler "{handler}".',
									array('{class}'=>get_class($this), '{event}'=>$name, '{handler}'=>$handler[1])));
					}
					else // PHP 5.3: anonymous function
						call_user_func($handler,$event);
				}
				else
					throw new Exception(Benben::t('benben','Event "{class}.{event}" is attached with an invalid handler "{handler}".',
							array('{class}'=>get_class($this), '{event}'=>$name, '{handler}'=>gettype($handler))));
				// stop further handling if param.handled is set true
				if(($event instanceof Event) && $event->handled)
					return;
			}
		}
		elseif(BENBEN_DEBUG && !$this->hasEvent($name))
		throw new Exception(Benben::t('benben','Event "{class}.{event}" is not defined.',
				array('{class}'=>get_class($this), '{event}'=>$name)));
	}
}