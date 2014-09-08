<?php
namespace benben\cache;

class FileCache extends Cache
{
    /**
     * @var string the directory to store cache files. Defaults to null, meaning
     * using 'protected/runtime/cache' as the directory.
     */
    public $cachePath;
    /**
     * @var string cache file suffix. Defaults to '.bin'.
     */
    public $cacheFileSuffix='.bin';
    /**
     * 垃圾回收频率,此值越高赵垃圾回收频率越高,默认为100
     * @var int 此值要小于1000000
     */
    private $_gcProbability=100;
    /**
     * 垃圾回收标识量，标识本次请求是否已经运行过一次垃圾回收
     * @var bool
     */
    private $_gced = false;
    
    public function init()
    {
        parent::init();
        
        if (empty($this->cachePath))
        {
        	$this->cachePath = APPLICATION_PATH.DIRECTORY_SEPARATOR.'runtime'.DIRECTORY_SEPARATOR.'cache';
        }
    }
    
    /**
     * Removes expired cache files.
     * @param bool $expiredOnly whether to removed expired cache files only.
     * @param string $path the path to clean. If null,it will be {@link cachePath}
     */
    public function gc($expiredOnly = true, $path = null)
    {
        if (null===$path)
        {
            $path = $this->cachePath;
        }
        if(false===($handle=opendir($path)))
        {
            return;
        }
        while (($file=readdir($handle))!==false)
        {
            if('.'===$file || '..'===$file)
            {
                continue;
            }
            $fullPath = $path.DIRECTORY_SEPARATOR.$file;
            if(is_dir($fullPath))
            {
                $this->gc($expiredOnly, $fullPath);
            }
            elseif (($expiredOnly && filemtime($fullPath)<time()) || !$expiredOnly)
            {
                unlink($fullPath);
            }
        }
        closedir($handle);
    }
    
    protected function getValue($key)
    {
       $cacheFile = $this->getCacheFile($key);
       if(($time=@filemtime($cacheFile))>=time())
       {
           return file_get_contents($cacheFile);
       }
       elseif ($time>0)
       {
           unlink($cacheFile);
       }
       return false;
    }
    
    protected function setValue($key,$value,$expire)
    {
        if(!$this->_gced && mt_rand(0, 1000000)<$this->_gcProbability)
        {
            $this->gc();
            $this->_gced = true;
        }
        
        if($expire<=0)
        {
            $expire = 31536000; // 1 year
        }
        
        $expire+=time();
        
        $cacheFile = $this->getCacheFile($key);
        if(file_put_contents($cacheFile, $value, LOCK_EX)!==false)
        {
            chmod($cacheFile, 0777);
            return touch($cacheFile, $expire);
        }
        else
        {
            return false;
        }
    }
    
    protected function getCacheFile($key)
    {
    	return $this->cachePath.DIRECTORY_SEPARATOR.$key.$this->cacheFileSuffix;
    }
    
}