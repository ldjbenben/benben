<?php

namespace application\controllers;

use application\components\ValidateCode;
use benben\Benben;

class VerifyController extends Controller 
{
	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
				'accessControl', // perform access control for CRUD operations
		);
	}
	
	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
				array('allow',  // allow all users to access 'login,reg' actions.
						'actions'=>array('code'),
						'users'=>array('*'),
				),
				array('allow', // allow authenticated users to access all actions
						'users'=>array('@'),
				),
				array('deny',  // deny all users
						'users'=>array('*'),
				),
		);
	}
	
	public function actionCode()
	{
		$valiateCode = new ValidateCode();
		$valiateCode->getImg();
		Benben::app()->session->add('verifyCode', $valiateCode->getCode());
	}
}