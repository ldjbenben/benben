<?php

namespace application\components\behaviors;

use benben\base\Behavior;

class AccountBehavior extends Behavior 
{
	public function test()
	{
		echo 'test()';
	}
	
	public function events()
	{
		return array(
			'onLogin'=>'onLoginCallback',			
		);
	}
	
	public function onLoginCallback($event)
	{
		echo '<pre>';print_r($event->params);
	}
}