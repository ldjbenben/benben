<?php
namespace benben;

use benben\log\Logger;

use benben\Benben;

use benben\web\WebApplication;
use benben\translation\MessageTranslation;
use benben\translation\BenbenTranslation;
use benben\base\Exception;
defined('BENBEN_DEBUG') or define('BENBEN_DEBUG', false);
defined('BENBEN_TRACE_LEVEL') or define('BENBEN_TRACE_LEVEL', 1);
defined('CLASS_PATH') or define('CLASS_PATH', APPLICATION_PATH.DIRECTORY_SEPARATOR.'classes');


date_default_timezone_set('Asia/Shanghai');

class BenbenBase
{
    /**
     * 保存类名或目录别名标识
     * 通过此窗口来判断别名是否已经存在
     * @var array
     */
    public static $imports = array();
    /**
     * 类到文件的映射
     * @var array
     */
    public static $classMap = array();
    /**
     * save the path of alias
     * @var array
     */
    private static $_aliases = array('system'=>BENBEN_PATH,'benben'=>BENBEN_PATH);
    /**
     * @var Application
     */
    private static $_app = null;
    private static $_includePaths = array();
    private static $_enableIncludePath = true;
    private static $_logger = null;
    
    /**
     * @return string the version of Yii framework
     */
    public static function getVersion()
    {
    	return '1.0.0';
    }
    
    /**
     * @return \benben\web\WebApplication
     */
    public static function app()
    {
    	return self::$_app;
    }
    
    public static function createWebApplication($config=null)
    {
    	self::$_app = new WebApplication($config);
    	return self::$_app;
    }
    
    public static function createApplication($class,$config=null)
    {
        self::$_app = new $class($config);
        return self::$_app;
    }
    
    /**
     *
     * @param category  类别, benben已经被系统保留
     * @param message   消息标识
     * @param params  参数
     */
    public static function t($category, $message, array $params = array())
    {
    	$source = 'benben' == $category ? new BenbenTranslation() : new MessageTranslation();
    	
    	return $source->translate($category, $message, $params);
    }
    
    /**
     * Marks the begin of a code block for profiling.
     * This has to be matched with a call to {@link endProfile()} with the same token.
     * The begin- and end- calls must also be properly nested, e.g.,
     * <pre>
     * Benben::beginProfile('block1');
     * Benben::beginProfile('block2');
     * Benben::endProfile('block2');
     * Benben::endProfile('block1');
     * </pre>
     * The following sequence is not valid:
     * <pre>
     * Benben::beginProfile('block1');
     * Benben::beginProfile('block2');
     * Benben::endProfile('block1');
     * Benben::endProfile('block2');
     * </pre>
     * @param string $token token for the code block
     * @param string $category the category of this log message
     * @see endProfile
     */
    public static function beginProfile($token,$category='application')
    {
    	self::log('begin:'.$token,Logger::LEVEL_PROFILE,$category);
    }
    
    /**
     * Marks the end of a code block for profiling.
     * This has to be matched with a previous call to {@link beginProfile()} with the same token.
     * @param string $token token for the code block
     * @param string $category the category of this log message
     * @see beginProfile
     */
    public static function endProfile($token,$category='application')
    {
    	self::log('end:'.$token,Logger::LEVEL_PROFILE,$category);
    }
    
    /**
     * Class autoload loader
     * This method is provided to be invoked within an __autoload() magic method.
     * 
     * @param string $class_name class name
     * @return boolean whether the class has been loaded successfully
     */
    public static function autoload($class_name)
    {
        // use include so that the error PHP file may appear
        if(isset(self::$classMap[$class_name]))
        {
            include(self::$classMap[$class_name]);
        }
        else
        {
            if (false === strpos($class_name, '\\')) // class without namespace
            {
                if (false === self::$_enableIncludePath)
                {
                    foreach (self::$_includePaths as $path)
                    {
                        $class_file = $path.DIRECTORY_SEPARATOR.$class_name.'.php';
                        if(is_file($class_file))
                        {
                            include($class_file);
                            // 这里对文件大小写进行了验证
                            if (BENBEN_DEBUG && basename(realpath($class_file))!==$class_name.'.php')
                            {
                                throw new Exception(Benben::t('benben','Class name "{class}" does not match class file "{file}".',array(
                                        '{class}'=>$class_name,
                                        '{file}'=>$class_file,
                                )));
                            }
                            break;
                        }
                    }
                }
                else
                {
                    include($class_name.'.php');
                }
            }
            else
            {
                $namespace = str_replace('\\', '.', ltrim($class_name, '\\'));
                
                if (is_file($file = CLASS_PATH.'/'.str_replace('\\', '/', ltrim($class_name, '\\')).'.php'))
                {
                    include($file);
                }
                else
                {
//                     foreach (self::$_includePaths as $path)
//                     {
//                         if(is_file($file = $path.'/'.str_replace('\\', '/', ltrim($class_name, '\\')).'.php'))
//                         {
                            include($file);
                            break;
//                         }
//                     }
                }
            }
            return class_exists($class_name, false) || interface_exists($class_name, false);
        }
        return true;
    }
    
    /**
     * Creates an object and initialized it based on the given configuration.
     * 
     * Any additional parameters passed to this method will be
     * passed to the constructor of the object being created.
     * 
     * @param array $config
     * @return mixed the created object
     * @throws Exception if the configuration does not have a 'class' element.
     */
    public static function createComponent($config)
    {
        if (is_string($config))
        {
            $config = array('class'=>$config);
        }
        
        if(!isset($config['class']))
        {
            throw new Exception(Benben::t('benben','Object configuration must be an array containing a "class" element.'));
        }
        
        $type = $config['class'];
        unset($config['class']);
        
        if (!class_exists($type, false))
        {
            $type = Benben::import($type, true);
        }
        
        if (($n=func_num_args())>1)
        {
            $args = func_get_args();
            // first try simple and lite method
            if(2==$n)
            {
                $object = new $type($args[1]);
            }
            elseif(3==$n)
            {
                $object = new $type($args[1], $args[2]);
            }
            elseif(4==$n)
            {
                $object = new $type($args[1], $args[2], $args[3]);
            }
            else 
            {
                //cause performace reflection method is the last method
                unset($args[0]);
                $class = new \ReflectionClass($type);
                $object = $class->newInstanceArgs($args);
            }
        }
        else
        {
            $object = new $type;
        }
        
        foreach ($config as $key=>$value)
        {
            $object->$key = $value;
        }
        
        return $object;
    }
    
    /**
     * Imports a directory or a file
     * 
     * Importing a class is like including the corresponding class file.
     * The main difference is that importing a class is much lighter because it only
     * includes the class file when the class is referenced the first time.
     * 
     * Importing a directory is equivalent to adding a directory into the PHP include path.
     * If multiple directories are imported, the directories imported later will take precedence 
     * in class file searching(they are added to the front of the PHP include path).
     * 
     * Path aliases are used to import a class or directory. For example,
     * <ul>
     *     <li><code>application.components.GoogleMap</code>: import the <code>GoogleMap</code></li>
     *     <li><code>application.components.*</code>: import the <code>components</code> directory.</li>
     * </ul>
     * 
     * The same path alias can be imported multiple times, but only the first time is effective.
     * Importing a directory does not import any of its subdirectories.
     * This method can also be used to import a class in namespace format
     * (avaliable for PHP 5.3 or above only). It is similar to imporint a class in path alias format,
     * except that the dot separator is replaced by the backslash separator.
     * 
     * Note, importing a class in namespace format requires that the namespace is corresponding to 
     * a valid path alias if we replace the backslash characters with dot charcters.
     * For example, the namespace <code>application\components\*</code> must correspond to a valid
     * path alias <code>application.components.*</code>.
     * 
     * @param string $alias path alias to be imported
     * @param boolean $forceInclude whether to include the class file immediately. 
     * If false, the class file will be included only when the class is being used. 
     * This Parameter is used only when the path alias refers to a class.
     * 
     * @return string the class name or the directory that this alias refers to
     * @throws Exception if the alias is invalid
     */
    
    /**
     * 导入一个目录或类文件
     * 在配置文件中通过import键进行配置：
     * <code>
     * 'import'=>array(
     *          'application.components.*', // 导入目录
     *          'application.others.Payment', // 导入类文件
     *          'linkc\link\SimpleLink',    // 导入一个命名空间类
     * );
     * </code>
     * 程序会按以下步骤来执行：
     * 1、首先会在别名配置(<self::$imports)变量中查找是否包含些别名，如果查找到则返回。
     * 2、通过class_exists或interface_exists来判断与别名一致的类或接口是否已存在，如果存在设置别名关联并返回。
     * 3、对别名进行分析导入，在分析的结果中会出现如下几种情形
     *    1)命名空间类，请确保此命名空间的根目录存放在CLASS_PATH常量所指定的目录里或include_path环境变量里。
     *    2)普通类
     *    3）目录，程序会把此目录通过set_include_path加入到include_path环境变量中
     * @param string $alias
     * @param bool $forceInclude 此值仅对类文件有效，设置是否立即导入此类文件，
     * 注意通过配置文件导入的文件不会被立即导入，只有框架类才会被立即导入。
     * 
     * @return string 返回关联别名
     * @throws Exception 如果类文件被设置成立即导入，而此文件并不存在，程序会抛出异常
     */
    public static function import($alias, $forceInclude=false)
    {
        if(isset(self::$imports[$alias])) // previously imported
        {
            return self::$imports[$alias];
        }
        
        if(class_exists($alias, false) || interface_exists($alias, false)) // class or interface exsited
        {
            return self::$imports[$alias] = $alias;
        }
        
        if(($pos = strpos($alias, '\\')) !== false) // PHP 5.3 namespace format
        {
            $class_file = CLASS_PATH.'/'.str_replace('\\', '/', $alias).'.php';
            $class_name = $alias;
            
            if ($forceInclude && is_file($class_file))
            {
                require $class_file;
            }
            elseif($forceInclude && !is_file($class_file))
            {
                throw new Exception(Benben::t('benben', 'file {file} not exist',array(
                    '{file}'=>$class_file,                        
                )));
            }
            
            self::$imports[$alias] = $alias;
            self::$classMap[$alias] = $class_file;
            return $alias;
        }
        
        if(($pos = strrpos($alias, '.')) === false || ($pos = strpos($alias, '\\')) !== false) // a simple class name
        {
            if ($forceInclude && self::autoload($alias))
            {
                self::$imports[$alias] = $alias;
            }
            
            return $alias;
        }
        
        $class_name = (string)substr($alias, $pos+1);
        $is_class = ($class_name !== '*');
        
        if($is_class &&(class_exists($class_name, false) || interface_exists($class_name, false)))
        {
            return self::$imports[$alias] = $class_name;
        }
        
        if(($path = self::getPathOfAlias($alias)) !== false)
        {
           if ($is_class)
           {
               if($forceInclude)
               {
                   if (is_file($path.'.php'))
                   {
                       require($path.'.php');
                   }
                   else
                   {
                       throw new Exception(Benben::t('benben', 'Alias "{alias}" is invalid. Make sure it points to an existing PHP file and the file is readable.', array('{alias}'=>$alias)));
                   }
                   self::$imports[$alias] = $class_name;
               }
               else
               {
                   self::$classMap[$class_name] = $path.'.php';
               }
               return $class_name;
           }
           else // a directory
           {
               if (null === self::$_includePaths)
               {
                   self::$_includePaths = array_unique(explode(PATH_SEPARATOR, get_include_path()));
               }
               if (($pos=array_search($path,self::$_includePaths,true))===false)
               {
               	    array_unshift(self::$_includePaths, $path);
               	    set_include_path('.'.PATH_SEPARATOR.implode(PATH_SEPARATOR, self::$_includePaths));
               }
               return self::$imports[$alias] = $path;
           }
        }
        else
        {
            throw new Exception(Benben::t('benben','Alias "{alias}" is invalid. Make sure it points to an existing directory or file.',
            		array('{alias}'=>$alias)));
        }
    }
    
    /**
     * Translates an alias into a file path
     * Note, this method does not ensure the existence of the resulting file path.
     * It only checks if the root alias is valid or not.
     * @param string $alias alias (e.g. system.web.HttpRequest)
     * @return mixed file path corresponding to the alias, false if the alias is invalid.
     */
    public static function getPathOfAlias($alias)
    {
        if (isset(self::$_aliases[$alias]))
        {
            return self::$_aliases[$alias];
        }
        elseif (($pos=strpos($alias, '.')) !== false)
        {
            $rootAlias = substr($alias, 0, $pos);
            if (isset(self::$_aliases[$rootAlias]))
            {
                return self::$_aliases[$alias] = rtrim(self::$_aliases[$rootAlias].DIRECTORY_SEPARATOR.str_replace('.', DIRECTORY_SEPARATOR, substr($alias, $pos+1)), '*'.DIRECTORY_SEPARATOR);
            }
        }
        return false;
    }
    
    public static function setPathOfAlias($alias, $path)
    {
          self::$_aliases[$alias] = $path;
    }
    
    /**
     * Logs a message.
     * Messages logged by this method may be retrieved via {@link CLogger::getLogs}
     * and may be recorded in different media, such as file, email, database, using
     * {@link CLogRouter}.
     * @param string $msg message to be logged
     * @param string $level level of the message (e.g. 'trace', 'warning', 'error'). It is case-insensitive.
     * @param string $category category of the message (e.g. 'system.web'). It is case-insensitive.
     */
    public static function log($msg,$level=Logger::LEVEL_INFO,$category='application')
    {
    	if(self::$_logger===null)
    		self::$_logger=new Logger();
    	$files = array();
    	if(BENBEN_DEBUG && BENBEN_TRACE_LEVEL>0)
    	{
    		$traces=debug_backtrace(); 
    		$count=0;
    		foreach($traces as $trace)
    		{
    			if(isset($trace['file'],$trace['line']) && strpos($trace['file'],BENBEN_PATH)===false)
    			{
    				$files[] = array('file'=>$trace['file'], 'line'=>$trace['line']);
    				if(++$count>=BENBEN_TRACE_LEVEL)
    					break;
    			}
    		}
    	}
		self::$_logger->log($msg,$level,$category,array('files'=>$files));
    }
    
    public static function getLogger()
    {
        return self::$_logger;
    }
    
    /**
     * Sets the logger object.
     * @param CLogger $logger the logger object.
     * @since 1.1.8
     */
    public static function setLogger($logger)
    {
    	self::$_logger=$logger;
    }
    
    public static function trace($msg, $category='application')
    {
       self::log($msg,Logger::LEVEL_TRACE,$category);
    }
 
}

spl_autoload_register(array('benben\BenbenBase','autoload'));