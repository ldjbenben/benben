<?php

namespace application\components;

use benben\Benben;
use application\models\UserModel;
use benben\validators\EmailValidator;
class UserIdentity extends \benben\web\auth\UserIdentity
{
	/**
	 * Validates the username and password.
	 * This method should check the validity of the provided username
	 * and password in some way. In case of any authentication failure,
	 * set errorCode and errorMessage with appropriate values and return false.
	 * @param string username
	 * @param string password
	 * @return boolean whether the username and password are valid
	 */
	public function authenticate()
	{
		// 用户即可以通过用户名也可以通过邮箱进行登录，首先判断是否为邮箱
		$emailValidator = new EmailValidator();
		$userinfo = null;
		
		if($emailValidator->validateValue($this->username))
		{
			$userinfo = UserModel::model()->find('email=:email AND password=:password',
					array(':email'=>$this->username, ':password'=>self::encrypt($this->password))
			);
		}
		else
		{
			$userinfo = UserModel::model()->find('username=:username AND `password`=:password',
					array(':username'=>$this->username, ':password'=>self::encrypt($this->password))
			);
		}
		
		if(empty($userinfo))
		{
			$this->errorCode=self::ERROR_PASSWORD_INVALID;
			$this->errorMessage = Benben::t('message', 'username or password error.');
		}
		else 
		{
			$this->errorCode = self::ERROR_NONE;
			Benben::app()->user->login($this);
		}
		
		return !$this->errorCode;
	}
	
	/**
	 * 对密码进行加密
	 * @param string $password
	 * @return string 加密后的密码串
	 */
	public static function encrypt($password)
	{
		return md5($password);
	}
}