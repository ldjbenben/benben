<?php

namespace application\components\behaviors;

use benben\base\Behavior;
use application\components\ValidateCode;

class ValidateCodeBehavior extends Behavior 
{
	public function actionCode()
	{
		$valiateCode = new ValidateCode();
		$valiateCode->getImg();
	}
}