<?php

namespace application\models;

use benben\web\FormModel;
use application\components\UserIdentity;
use benben\Benben;

class LoginFormModel extends FormModel
{
	public $username;
	public $password;
	
	public function rules()
	{
		return array(
	       array('username,password', 'required'),
	       array('password', 'authenticate'),
	   );
	}
	
	/**
	 * Authenticates the password.
	 * This is the 'authenticate' validator as declared in rules().
	 */
	public function authenticate($attribute,$params)
	{
		$identity=new UserIdentity($this->username,$this->password);
		
		if(!$identity->authenticate())
		{
			$this->addError('password', Benben::t('message', 'Incorrect username or password.'));
		}
		
	}

}