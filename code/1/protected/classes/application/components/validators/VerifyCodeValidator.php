<?php

namespace application\components\validators;

use benben\validators\Validator;
use benben\Benben;

class VerifyCodeValidator extends Validator 
{
	/**
	 * Validates the attribute of the object.
	 * If there is any error, the error message is added to the object.
	 * @param Model $object the object being validated
	 * @param string $attribute the attribute being validated
	 */
	protected function validateAttribute($object,$attribute)
	{
		$value=$object->$attribute;
		
		if($value != Benben::app()->session->get('verifyCode'))
		{
			$message = $this->message!==null ? $this->message:Benben::t('message','verify code error.');
			$this->addError($object,$attribute,$message);
		}
	}
	
}