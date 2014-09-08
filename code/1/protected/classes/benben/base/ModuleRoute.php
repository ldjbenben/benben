<?php

namespace benben\base;

use benben\Benben;
use benben\base\Component;

/** 
 * @author benben
 * 
 */
class ModuleRoute extends Component
{
    /**
     * @param Module $module
     */
    public function route($route)
    {
        $route = trim($route,'/');
        $id = $route;
        
        if (($pos=strpos($route, '/'))!==false)
        {
            $id = substr($route, 0, strpos($route, '/'));
        }
        
        if(($module = Benben::app()->getModule($id))!==null)
        {
            $route = substr($route, $pos+1);
            $module = Benben::app()->getModule(Benben::app()->defaultModule);
        }
        else
        {
            $module = Benben::app()->getModule(Benben::app()->defaultModule);
        }
        
        $module->runController($route);
    }
    
    /**
     * Creates a controller instance based on a route
     * The route should contain the controller ID and the action ID.
     * It may also contain addtional GET variables. All these must be concatenated together with slashes.
     *
     * This method will attempt to create a controller in the following order:
     * <ol>
     * <li>If the first segment is found in {@link controllerMap}, the corresponding
     * controller configuration will be used to create the controller;</li>
     * <li>If the fist segment is found to be a module ID, the corresponding module
     * will be used to create the controller;</li>
     * <li>Otherwise, it will search under the {@link controllerPath} to create
     * the coressponding controller, For example, if the route is "admin/user/create",
     * then the controller will be created using the class file "protected/controllers/admin/UserController.php".</li>
     * </ol>
     * @param WebModule $owner the module that the new controller will belong to.
     * Defaults to null, meaning the application instance is the owner.
     * @return array the controller instance and the action ID.
     * Null if the controller class does not exist or the route is invalid.
     */
    public function createController($route, $owner=null)
    {
        $id = substr($route, strpos($route, '/'));
        
        if(($module = Benben::app()->getModule($id))===null)
        {
            $module = Benben::app()->getModule(Benben::app()->defaultModule);
        }
        
        $module->runController();
    }
    
    
    /* public function route(Module $module)
    {
        $str_controller = $module->controllerId;
        $str_action = $module->actionId;
        $str_basepath = $module->basePath; 
        $class_name = $module->namespace.'\\controllers\\'.ucfirst($str_controller).'Controller';
        $method_name = 'action'.ucfirst($str_action);
        $file_name = $str_basepath.'/controllers/'.ucfirst($str_controller).'Controller.php';
        
        if(!class_exists($class_name) && file_exists($file_name))
        {
        	include_once $file_name;
        }
        
        if (class_exists($class_name))
        {
        	$obj_controller = new $class_name();
        	if ($obj_controller instanceof Controller)
        	{
            	if (method_exists($obj_controller, $method_name) && is_callable(array($class_name, $method_name)))
            	{
            	    $view = new BasicView();
            	    $view->template = Benben::app()->getViewPath($str_controller, $str_action);
            	    $view->controller = $obj_controller;
            	    $obj_controller->view = $view;
            		call_user_func(array($obj_controller, $method_name));
            	}
            	else
            	{
        	        throw new Exception(Benben::t('benben', '{controller}\'s method {method} not exist',array(
        	                '{controller}'=>$class_name,
        	                '{method}'=>$method_name)));
            	}
        	}
        	else
        	{
        	    throw new Exception(Benben::t('benben', '{controller}\ not exist',array(
        	                '{controller}'=>$class_name,
        	            )));
        	}
        }
        else
        {
        	throw new Exception(Benben::t('benben', '{controller}\ not exist',array(
        	                '{controller}'=>$class_name,
        	            )));
        }
    } */
}