<?php
namespace application\controllers;
use application\models\RegFormModel;
use benben\web\widgets\ActiveForm;
use benben\Benben;

/** 
 * @author benben
 * 
 */
class AccountController extends Controller
{
	public $layout = 'simple';
	
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
						'actions'=>array('login','reg','code'),
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
	
	public function behaviors()
	{
		return array(
			'a'=>array(
				'class'=>'application\\components\\behaviors\\ValidateCodeBehavior',
			),			
		);
	}
	
	public function onLogin($event)
	{
		$this->eventProxy->raiseEvent('onLogin', $event);
	}

    public function actionReg()
    {
    	$formModel = new RegFormModel();
    	$formModel->setScenario('register');
    	$this->performAjaxValidation($formModel);
    	$formData = isset($_POST['RegFormModel']) ? $_POST['RegFormModel'] : array();
    	
    	$formModel->attributes = $formData;
    	
    	$formModel->setScenario('reg');
    	
    	if(!empty($formData) && $formModel->validate())
    	{
    		if(empty($_GET['jump']))
    		{
    			$_GET['jump'] = '/';
    		}
    		$this->redirect($_GET['jump']);
    	}
    	else
    	{
			$this->render(array('model'=>$formModel));
    	}
    }
    
    protected function performAjaxValidation($model)
    {
    	if(isset($_POST['ajax']) && $_POST['ajax']==='user-form')
    	{
    		 $errors = ActiveForm::validate($model);
    		 if($errors != '[]')
    		 {
    		 	echo $errors;
    		 }
    		 else
    		 {
    		 	if($_POST['RegFormModel']['username'] == 'test')
    		 	{
    		 		echo json_encode(array('RegFormModel_username'=>array('name has existed!')));
    		 	}
    		 }
    		 Benben::app()->end();
    	}
    }
    
    public function actionLogout()
    {
        
    }
}