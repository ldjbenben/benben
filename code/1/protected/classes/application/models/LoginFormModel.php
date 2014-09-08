<?php

namespace application\models;

use benben\web\FormModel;
use application\components\UserIdentity;

class LoginFormModel extends FormModel
{
	public $username;
	public $password;
	
	public function rules()
	{
		return array(
	       array('username,password', 'required'),
	      // array('username', 'length', 'min'=>3, 'max'=>12),
	       //array('password', 'compare', 'compareAttribute'=>'password2', 'on'=>'register'),
	       array('password', 'authenticate', 'on'=>'login'),
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
			$this->addError('password','Incorrect username or password.');
	}

}