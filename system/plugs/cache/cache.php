<?php namespace Avrelia\Plug; if (!defined('AVRELIA')) die('Access is denied!');

use Avrelia\Core\Plug as Plug;
use Avrelia\Core\Cfg  as Cfg;

/**
 * Cache plug
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Cache
{
    private static $driver;

    public static function _on_include_()
    {
        Plug::get_config(__FILE__);
        self::$driver = Plug::get_driver(
            __FILE__, 
            Cfg::get('plugs/cache/driver'),
            __NAMESPACE__
        );

        # Do we have driver?
        return !!self::$driver;
    }

    /**
     * Enable plug
     * 
     * @return boolean
     */
    public static function _on_enable_()
    {
        self::_on_include_();
        self::$driver->_create();
    }

    /**
     * Disable plug
     * 
     * @return  boolean
     */
    public static function _on_disable_()
    {
        self::_on_include_();
        self::$driver->_destroy();
    }

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
    public static function set($contents, $key, $expires=false)
        { return self::$driver->set($contents, $key, $expires); }

    /**
     * Will get cache or return false if can't find it.
     * 
     * @param   string  $key
     * @return  mixed
     */
    public static function get($key)
        { return self::$driver->get($key); }

    /**
     * Check if particular key exists.
     * 
     * @param   string  $key
     * @return  boolean
     */
    public static function has($key)
        { return self::$driver->has($key); }

    /**
     * Clear particular cache, or all cache (if key is false)
     * 
     * @param   mixed   $key    String key name or false to clear all cache
     * @return  boolean
     */
    public static function clear($key=false)
        { return self::$driver->clear($key); }
}
