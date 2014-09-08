<?php

namespace application\models;

use benben\web\FormModel;
use application\components\UserIdentity;

class RegFormModel extends FormModel
{
	public $username;
	public $password;
	public $password2;
	public $email;
	
	public function rules()
	{
		return array(
	       array('username,password,password2', 'required'),
	       array('username', 'length', 'min'=>3, 'max'=>12),
	       array('password', 'compare', 'compareAttribute'=>'password2', 'on'=>'register'),
	       array('password', 'authenticate', 'on'=>'login'),
		   array('email', 'email'),
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