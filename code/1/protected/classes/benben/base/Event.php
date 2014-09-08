<?php

namespace benben\base;

class Event extends Component 
{
	/**
	 * @var object the sender of this event
	 */
	public $sender;
	/**
	 * @var boolean whether the event is handled. Defaults to false.
	 * When a handler sets this true, the rest of the uninvoked event handlers will not be invoked anymore.
	 */
	public $handled=false;
	/**
	 * @var mixed additional event parameters.
	 */
	public $params;
	
	/**
	 * Constructor.
	 * @param mixed $sender sender of the event
	 * @param mixed $params additional parameters for the event
	 */
	public function __construct($sender=null,$params=null)
	{
		$this->sender=$sender;
		$this->params=$params;
	}
}