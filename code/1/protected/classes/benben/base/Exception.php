<?php

namespace benben\base;

class Exception extends \Exception
{
	public function __construct($message)
	{
	    parent::__construct($message);
	}
}