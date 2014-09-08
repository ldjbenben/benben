<?php
namespace application\modules\admin\controllers;
use benben\Benben;
use application\controllers\Controller;

/** 
 * @author benben
 * 
 */
class DefaultController extends Controller
{
    public function actionIndex()
    {
        echo 'module->admin->default->index()';
       if(Benben::app()->user->checkAccess('aaa'))
       {
           echo 'check ok';
       }
       else
       {
           echo 'check fail';
       }
       
       // $command = Benben::app()->db->createCommand("select * from {{user}}");
    }
}