<?php
namespace benben\log;

use benben\base\ApplicationComponent;

use benben\Benben;

class Logger extends ApplicationComponent
{
    const LEVEL_TRACE = 'trace';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_INFO='info';
    const LEVEL_PROFILE = 'profile';
    
    public $flushCapacity = 1000;
    
    protected $_logs = array();
    protected $_logCount = 0;
    protected $_routers = array();
    
    public function init()
    {
        
    }
    
    public function log($msg, $level, $category, $additional = array())
    {
        $this->_logs[] = array('msg'=>$msg, 'category'=>$category, 'level'=>$level, 'timestamp'=>microtime(true),'addtional'=>$additional);
        $this->_logCount++;
        if($this->_logCount>$this->flushCapacity)
        {
            $this->flush();
        }
    }
    
    public function flush()
    {
        foreach ($this->_routers as $config)
        {
            $router = Benben::createComponent($config);
            $router->log($this->_logs);
        }
    }
    
    /*
     * Log routes may be configured in application configuration like following:
     * <pre>
     * array(
     *     'preload'=>array('log'), // preload log component when app starts
     *     'components'=>array(
     *         'log'=>array(
     *             'class'=>'CLogRouter',
     *             'routes'=>array(
     *                 array(
     *                     'class'=>'CFileLogRoute',
     *                     'levels'=>'trace, info',
     *                     'categories'=>'system.*',
     *                 ),
     *                 array(
     *                     'class'=>'CEmailLogRoute',
     *                     'levels'=>'error, warning',
     *                     'emails'=>array('admin@example.com'),
     *                 ),
     *             ),
     *         ),
     *     ),
     * )
     * </pre>
     */
    public function setRouters($routers)
    {
        $this->_routers = $routers;
    }
}