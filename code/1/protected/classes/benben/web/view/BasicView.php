<?php

namespace benben\web\view;

use benben\Benben;
use benben\base\Exception;
use benben\web\helpers\Html;

/**
 * 
 * @author benben
 */
class BasicView extends View
{
	/**
	 * Name of the hidden field storing persistent page states.
	 */
	const STATE_INPUT_NAME='BENBEN_PAGE_STATE';
	
	private $_cachingStack;
	protected $_widgets;
	protected $_widgetStack;
	public $fileExtension = '.php';
	
    public function setData($data)
    {
    	$this->_data = $data;
    }

    public function render ($viewFile, $layoutFile = '')
    {
        $this->_layout = $this->_owner->getLayout();
        $this->_teamplate = $viewFile;
        ob_start();
        extract($this->_data);
        include $viewFile;
        $output = ob_get_clean(); 
        ob_start();
       
        if(!empty($layoutFile))
        	$output=$this->renderFile($layoutFile,array('content'=>$output),true);
        
        return $this->processOutput( $output );
    }
    
    public function renderPartial ($viewFile)
    {
    	$this->_viewFile = $viewFile;
    	ob_start();
    	extract($this->_data);
    	include $viewFile;
    	
    	return $this->processOutput( ob_get_clean() );
    }
    
    /**
     * Renders a view file.
     *
     * @param string $viewFile view file path
     * @param array $data data to be extracted and made available to the view
     * @param boolean $return whether the rendering result should be returned instead of being echoed
     * @return string the rendering result. Null if the rendering result is not required.
     * @throws Exception if the view file does not exist
     */
    public function renderFile($viewFile,$data=null,$return=false)
    {
    	$widgetCount=count($this->_widgetStack);
    	$content=$this->renderInternal($viewFile,$data,$return);
    	if(count($this->_widgetStack)===$widgetCount)
    		return $content;
    	else
    	{
    		$widget=end($this->_widgetStack);
    		throw new Exception(Benben::t('Benben','{controller} contains improperly nested widget tags in its view "{view}". A {widget} widget does not have an endWidget() call.',
    				array('{controller}'=>get_class($this), '{view}'=>$viewFile, '{widget}'=>get_class($widget))));
    	}
    }
    
    /**
     * Renders a view file.
     * This method includes the view file as a PHP script
     * and captures the display result if required.
     * @param string $_viewFile_ view file
     * @param array $_data_ data to be extracted and made available to the view file
     * @param boolean $_return_ whether the rendering result should be returned as a string
     * @return string the rendering result. Null if the rendering result is not required.
     */
    public function renderInternal($_viewFile_,$_data_=null,$_return_=false)
    {
    	// we use special variable names here to avoid conflict when extracting data
    	if(is_array($_data_))
    		extract($_data_,EXTR_PREFIX_SAME,'data');
    	else
    		$data=$_data_;
    	if($_return_)
    	{
    		ob_start();
    		ob_implicit_flush(false);
    		require($_viewFile_);
    		return ob_get_clean();
    	}
    	else
    		require($_viewFile_);
    }
    
    /**
     * Postprocesses the output generated by {@link render()}.
     * This method is invoked at the end of {@link render()} and {@link renderText()}.
     * If there are registered client scripts, this method will insert them into the output
     * at appropriate places. If there are dynamic contents, they will also be inserted.
     * This method may also save the persistent page states in hidden fields of
     * stateful forms in the page.
     * @param string $output the output generated by the current action
     * @return string the output that has been processed.
     */
    public function processOutput($output)
    {
    	Benben::app()->getClientScript()->render($output);
    
    	// if using page caching, we should delay dynamic output replacement
    	if($this->_dynamicOutput!==null && $this->isCachingStackEmpty())
    	{
    		$output=$this->processDynamicOutput($output);
    		$this->_dynamicOutput=null;
    	}
    
    	if($this->_pageStates===null)
    		$this->_pageStates=$this->loadPageStates();
    	if(!empty($this->_pageStates))
    		$this->savePageStates($this->_pageStates,$output);
    
    	return $output;
    }
    
    /**
     * Returns whether the caching stack is empty.
     * @return boolean whether the caching stack is empty. If not empty, it means currently there are
     * some output cache in effect. Note, the return result of this method may change when it is
     * called in different output regions, depending on the partition of output caches.
     */
    public function isCachingStackEmpty()
    {
    	return $this->_cachingStack===null || !$this->_cachingStack->getCount();
    }
    
    /**
     * Creates a widget and initializes it.
     * This method first creates the specified widget instance.
     * It then configures the widget's properties with the given initial values.
     * At the end it calls {@link Widget::init} to initialize the widget.
     * Starting from version 1.1, if a {@link WidgetFactory widget factory} is enabled,
     * this method will use the factory to create the widget, instead.
     * @param string $className class name (can be in path alias format)
     * @param array $properties initial property values
     * @return Widget the fully initialized widget instance.
     */
    public function createWidget($className,$properties=array())
    {
    	$widget=Benben::app()->getWidgetFactory()->createWidget($this,$className,$properties);
    	$widget->init();
    	return $widget;
    }
    
    /**
     * Creates a widget and executes it.
     * @param string $className the widget class name or class in dot syntax (e.g. application.widgets.MyWidget)
     * @param array $properties list of initial property values for the widget (Property Name => Property Value)
     * @param boolean $captureOutput whether to capture the output of the widget. If true, the method will capture
     * and return the output generated by the widget. If false, the output will be directly sent for display
     * and the widget object will be returned. This parameter is available since version 1.1.2.
     * @return mixed the widget instance when $captureOutput is false, or the widget output when $captureOutput is true.
     */
    public function widget($className,$properties=array(),$captureOutput=false)
    {
    	if($captureOutput)
    	{
    		ob_start();
    		ob_implicit_flush(false);
    		$widget=$this->createWidget($className,$properties);
    		$widget->run();
    		return ob_get_clean();
    	}
    	else
    	{
    		$widget=$this->createWidget($className,$properties);
    		$widget->run();
    		return $widget;
    	}
    }
    
    /**
     * Creates a widget and executes it.
     * This method is similar to {@link widget()} except that it is expecting
     * a {@link endWidget()} call to end the execution.
     * @param string $className the widget class name or class in dot syntax (e.g. application.widgets.MyWidget)
     * @param array $properties list of initial property values for the widget (Property Name => Property Value)
     * @return Widget the widget created to run
     * @see endWidget
     */
    public function beginWidget($className,$properties=array())
    {
    	$widget=$this->createWidget($className,$properties);
    	$this->_widgetStack[]=$widget;
    	return $widget;
    }
    
    /**
     * Ends the execution of the named widget.
     * This method is used together with {@link beginWidget()}.
     * @param string $id optional tag identifying the method call for debugging purpose.
     * @return Widget the widget just ended running
     * @throws Exception if an extra endWidget call is made
     * @see beginWidget
     */
    public function endWidget($id='')
    {
    	if(($widget=array_pop($this->_widgetStack))!==null)
    	{
    		$widget->run();
    		return $widget;
    	}
    	else
    		throw new Exception(Benben::t('benben','{controller} has an extra endWidget({id}) call in its view.',
    				array('{controller}'=>get_class($this),'{id}'=>$id)));
    }
    
    /**
     * Returns a persistent page state value.
     * A page state is a variable that is persistent across POST requests of the same page.
     * In order to use persistent page states, the form(s) must be stateful
     * which are generated using {@link CHtml::statefulForm}.
     * @param string $name the state name
     * @param mixed $defaultValue the value to be returned if the named state is not found
     * @return mixed the page state value
     * @see setPageState
     * @see CHtml::statefulForm
     */
    public function getPageState($name,$defaultValue=null)
    {
    	if($this->_pageStates===null)
    		$this->_pageStates=$this->loadPageStates();
    	return isset($this->_pageStates[$name])?$this->_pageStates[$name]:$defaultValue;
    }
    
    /**
     * Saves a persistent page state value.
     * A page state is a variable that is persistent across POST requests of the same page.
     * In order to use persistent page states, the form(s) must be stateful
     * which are generated using {@link CHtml::statefulForm}.
     * @param string $name the state name
     * @param mixed $value the page state value
     * @param mixed $defaultValue the default page state value. If this is the same as
     * the given value, the state will be removed from persistent storage.
     * @see getPageState
     * @see CHtml::statefulForm
     */
    public function setPageState($name,$value,$defaultValue=null)
    {
    	if($this->_pageStates===null)
    		$this->_pageStates=$this->loadPageStates();
    	if($value===$defaultValue)
    		unset($this->_pageStates[$name]);
    	else
    		$this->_pageStates[$name]=$value;
    
    	$params=func_get_args();
    	$this->recordCachingAction('','setPageState',$params);
    }
    
    /**
     * Removes all page states.
     */
    public function clearPageStates()
    {
    	$this->_pageStates=array();
    }
    
    /**
     * Loads page states from a hidden input.
     * @return array the loaded page states
     */
    protected function loadPageStates()
    {
    	if(!empty($_POST[self::STATE_INPUT_NAME]))
    	{
    		if(($data=base64_decode($_POST[self::STATE_INPUT_NAME]))!==false)
    		{
    			if(extension_loaded('zlib'))
    				$data=@gzuncompress($data);
    			if(($data=Benben::app()->getSecurityManager()->validateData($data))!==false)
    				return unserialize($data);
    		}
    	}
    	return array();
    }
    
    /**
     * Saves page states as a base64 string.
     * @param array $states the states to be saved.
     * @param string $output the output to be modified. Note, this is passed by reference.
     */
    protected function savePageStates($states,&$output)
    {
    	$data=Benben::app()->getSecurityManager()->hashData(serialize($states));
    	if(extension_loaded('zlib'))
    		$data=gzcompress($data);
    	$value=base64_encode($data);
    	$output=str_replace(Html::pageStateField(''), Html::pageStateField($value),$output);
    }
    
}