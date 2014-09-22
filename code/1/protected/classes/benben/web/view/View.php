<?php
namespace benben\web\view;

use benben\base\ApplicationComponent;

/**
 * 
 * @author benben
 * @property string $template
 * @property \benben\web\BaseController $owner
 */
abstract class View extends ApplicationComponent implements IView
{
    /**
     * @var \benben\web\BaseController
     */
    protected $_owner = null;
    /**
     * 视图模板
     * @var string
     */
    protected $_viewFile;
    protected $_data = array();
    protected $_layout = '';
    
    public function setOwner($owner)
    {
    	$this->_owner = $owner;
    }
    
    /**
     *
     * @return the $owner
     */
    public function getOwner ()
    {
    	return $this->_owner;
    }
}