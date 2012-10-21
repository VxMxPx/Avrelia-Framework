<?php namespace Plug\Avrelia; if (!defined('AVRELIA')) die('Access is denied!');

use Avrelia\Core\Cfg as Cfg;
use Avrelia\Core\Log as Log;

/**
 * Cache Driver Apc
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class CacheDriverApc implements CacheDriverInterface
{
    private $prefix;

    public function __construct()
    {
        Log::inf("Will use `apc` cache driver.");

        function_exists('apc_add')
            ? $this->prefix = Cfg::get('plugs/cache/apc_prefix', 'avrelia_framework_')
            : Log::war("The `apc` must be enabled in PHP.");
    }

    /**
     * Called when plug is enabled.
     * 
     * @return  boolean
     */
    public function _create()
        { return true; }

    /**
     * Called when plug is disabled.
     * 
     * @return  boolean
     */
    public function _destroy() 
        { return true; }

    /**
     * Will set cache (store content into cache file)
     * 
     * @param   string  $contents
     * @param   string  $key
     * @param   integer $expires    Time when chache expires, in seconds.
     *                              If set to false, then cache won't expire at all.
     *                              It can be refreshed if we set it again.
     * @return  boolean
     */
    public function set($contents, $key, $expires=false)
    {
        # Convert to seconds
        $expires = $expires !== false ? $expires : 0;

        Log::inf("Set cache: `{$key}` to expires in `{$expires}` seconds.");

        return apc_add($this->prefix.$key, $contents, $expires);
    }

    /**
     * Will get cache or return false if can't find it.
     * --
     * @param   string  $key
     * @return  mixed
     */
    public function get($key)
        { return apc_fetch($this->prefix.$key); }

    /**
     * Check if particular key exists.
     * 
     * @param   string  $key
     * @return  boolean
     */
    public function has($key)
        { return apc_exists($this->prefix.$key); }

    /**
     * Clear particular cache, or all cache (if key is false)
     * 
     * @param   mixed   $key    String key name or false to clear all cache
     * @return  boolean
     */
    public function clear($key=false)
    {
        if ($key && !$this->has($key)) {
            Log::inf("The cache key you're trying to remove doesn't exists anymore: `{$key}`.");
            return false;
        }

        return !$key
            ? apc_clear_cache()
            : apc_delete($key);
    }
}