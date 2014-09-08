<?php
namespace benben\cache;

interface ICache
{
    /**
     * Retrieves a value from cache with a specified key.
     * @param string $key a key identifying the cached value
     * @return mixed the value stored in cache, false if the value not in the cache or expired.
     */
    function get($key);
    /**
     * Retrieves multiple values from cache with the specified keys.
     * Some caches (such as memcache, apc) allow retrieving multiple cached values at one time,
     * which may improve the performance since it reduces the communication cost.
     * In case a cache doesn't support this feature natively, it will be simulated by this method.
     * @param array $keys list of keys identifying the cached values
     * @return array list of cached values corresponding to the specified keys. The array is returned
     *  in terms of (key,value) paires.
     * If a value is not cached or expired, the corresponding array value will be false.
     */
    function mget($keys);
    /**
     * Strores a value identified by a key into cache.
     * If the cache already contains such a key, the existing value and expiration time 
     * will be replaced with the new ones.
     * @param string $key the key identifying the value to be cached
     * @param mixed $value the value to be cached
     * @param integer $expire the number of seconds in which the cached value will expire.
     * 0 means never expire.
     * @param ICacheDependency $dependency dependency of the cached item. If the dependency changes,
     * the item is labelled invalid.
     * @return boolean true if the value is successfully stored into cache, false otherwise
     */
    function set($key, $value, $expire=0, $dependency=null);
    /**
     * Stores a value identified by a key into cache if the cache does not contain this key.
     * Nothing will be done if the cache already contains the key.
     * @param string $key the key identifying the value to be cached
     * @param mixed $value the value to be cached
     * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
     * @param ICacheDependency $dependency dependency of the cached item. If the dependency changes, the item is labelled invalid.
     * @return boolean true if the value is successfully stored into cache, false otherwise
     */
    function add($key,$value,$expire=0,$dependency=null);
    /**
	 * Deletes a value with the specified key from cache
	 * @param string $key the key of the value to be deleted
	 * @return boolean whether the deletion is successful
	 */
	function delete($key);
	/**
	 * Deletes all values from cache.
	 * Be careful of performing this operation if the cache is shared by multiple applications.
	 * @return boolean whether the flush operation was successful.
	 */
	function flush();
}