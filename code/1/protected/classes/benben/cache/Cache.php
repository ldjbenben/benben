<?php
namespace benben\cache;

use benben\cache\dependence\ICacheDependcy;

use benben\base\Exception;

use benben\Benben;

use benben\cache\ICache;

abstract class Cache implements ICache
{
    public function init()
    {
        
    }
    
    /*
     * (non-PHPdoc) @see \benben\cache\ICache::add()
    */
    public function add ($key, $value, $expire = 0, $dependency = null)
    {
    	Benben::trace('Adding "'.$key.'" to cache','benben.cache.'.get_class($this));

		if ($dependency !== null && $this->serializer !== false)
			$dependency->evaluateDependency();

		$value = serialize(array($value,$dependency));

		return $this->addValue($this->generateUniqueKey($key), $value, $expire);
    }
    
    /*
     * (non-PHPdoc) @see \benben\cache\ICache::delete()
    */
    public function delete ($key)
    {
    	Benben::trace('Deleting "'.$key.'" from cache','benben.cache.'.get_class($this));
		return $this->deleteValue($this->generateUniqueKey($key));
    }
    
    /*
     * (non-PHPdoc) @see \benben\cache\ICache::flush()
    */
    public function flush ()
    {
    	Benben::trace('Flushing cache','benben.cache.'.get_class($this));
		return $this->flushValues();
    }
    
    /*
     * (non-PHPdoc) @see \benben\cache\ICache::get()
    */
    protected function getValue($key)
	{
    	throw new Exception(Benben::t('benben','{className} does not support get() functionality.',
			array('{className}'=>get_class($this))));
    }
    
    protected function getValues($keys)
    {
    	$results=array();
    	foreach($keys as $key)
    		$results[$key]=$this->getValue($key);
    	return $results;
    }
    
    protected function setValue($key,$value,$expire)
    {
    	throw new Exception(Benben::t('benben','{className} does not support set() functionality.',
    			array('{className}'=>get_class($this))));
    }
    
    protected function addValue($key,$value,$expire)
    {
    	throw new Exception(Benben::t('benben','{className} does not support add() functionality.',
    			array('{className}'=>get_class($this))));
    }
    
    protected function deleteValue($key)
    {
    	throw new Exception(Benben::t('benben','{className} does not support delete() functionality.',
    			array('{className}'=>get_class($this))));
    }
    
    protected function flushValues()
    {
    	throw new Exception(Benben::t('benben','{className} does not support flushValues() functionality.',
    			array('{className}'=>get_class($this))));
    }
    
    public function get($key)
    {
        $value = $this->getValue($this->generalUniqueKey($key));
        if (false===$value)
        {
            return $value;
        }
        $value = unserialize($value);
        if(is_array($value) && (!$value[1] instanceof ICacheDependcy || !$value[1]->getHasChanged()))
        {
            Benben::trace('Serving "'.$key.'" from cache','benben.cache.'.get_class($this));
            return $value[0];
        }
        else
        {
            return false;
        }
    }
    
    /*
     * (non-PHPdoc) @see \benben\cache\ICache::mget()
    */
    public function mget ($keys)
    {
    	// TODO Auto-generated method stub
    }
    
    /*
     * (non-PHPdoc) @see \benben\cache\ICache::set()
    */
    public function set ($key, $value, $expire = 0, $dependency = null)
    {
    	Benben::trace('Saving "'.$key.' to cache', 'benben.cache.'.get_class($this));
    	if($dependency !== null && $this->serializer !== false)
    	{
    	    $dependency->evaluateDependency();
    	}
    	$value = serialize(array($value, $dependency));
    	return $this->setValue($this->generalUniqueKey($key), $value, $expire);
    }
    
    public function generalUniqueKey($key)
    {
        return md5($key);
    }
}

