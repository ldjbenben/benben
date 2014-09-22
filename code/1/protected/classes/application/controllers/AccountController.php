<?php
namespace application\controllers;
use application\models\RegFormModel;
use benben\web\widgets\ActiveForm;
use benben\Benben;
use application\models\UserModel;
use application\models\LoginFormModel;
use application\components\UserIdentity;
/** 
 * @author benben
 * 
 */
class AccountController extends Controller
{
	protected $_layout = 'simple';
	
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
						'actions'=>array('login','reg','code','insert'),
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
	
	public function onLogin($event)
	{
		$this->eventProxy->raiseEvent('onLogin', $event);
	}
	
	public function actionLogin()
	{
		$formModel = new LoginFormModel();
		$formData = isset($_POST['LoginFormModel']) ? $_POST['LoginFormModel'] : array();
		$formModel->attributes = $formData;
		
		if(!empty($formData))
		{
			if($formModel->validate())
			{
				$this->redirect('/');
				Benben::app()->end();
			}
			else
			{
				$formModel->clearErrors();
				$formModel->addError('password', Benben::t('message', 'Incorrect username or password.'));
			}
		}
		
		$this->render(array('model'=>$formModel));
	}

    public function actionReg()
    {
    	$formModel = new RegFormModel();
    	$this->performAjaxValidation($formModel);
    	$formData = isset($_POST['RegFormModel']) ? $_POST['RegFormModel'] : array();
    	$formModel->attributes = $formData;
    	
    	$formModel->setScenario('reg');
    	
    	if(!empty($formData) && $formModel->validate())
    	{
    		unset($formData['password2']);
    		unset($formData['verifyCode']);
    		$formData['password'] = UserIdentity::encrypt($formData['password']);
    		$userModel = new UserModel();
    		$userModel->attributes = $formData;
    		
    		if($userModel->insert())
    		{
	    		if(empty($_GET['jump']))
	    		{
	    			$_GET['jump'] = '/';
	    		}
	    		
	    		$this->redirect($_GET['jump']);
	    		Benben::app()->end();
    		}
    	}
    	$this->render(array('model'=>$formModel));
    }
    
    protected function performAjaxValidation($model)
    {
    	if(isset($_POST['ajax']) && $_POST['ajax']==='register-form')
    	{
    		 $errors = ActiveForm::validate($model);
    		 if($errors != '[]')
    		 {
    		 	echo $errors;
    		 }
    		 Benben::app()->end();
    	}
    }
    
    public function actionLogout()
    {
        
    }
}