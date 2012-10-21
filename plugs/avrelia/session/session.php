<?php namespace Plug\Avrelia; if (!defined('AVRELIA')) die('Access is denied!');

use Avrelia\Core\Plug as Plug;
use Avrelia\Core\Cfg  as Cfg;

/**
 * Session Class
 * -----------------------------------------------------------------------------
 * Session Plug, Main Class
 * ----
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Session
{
    /**
     * Session Driver Instance
     * @var SessionDriverInterface
     */
    private static $driver;


    /**
     * Cload config and apropriate driver
     * --
     * @return  boolean
     */
    public static function _on_include_()
    {
        Plug::get_config(__FILE__);
        $class = Plug::get_driver(
            __FILE__, 
            Cfg::get('plugs/session/driver'), 
            __NAMESPACE__,
            false);

        # Create new driver instance
        self::$driver = new $class();
        return true;
    }

    /**
     * Enable plug
     * --
     * @return  boolean
     */
    public static function _on_enable_()
    {
        Plug::get_config(__FILE__);
        $class = Plug::get_driver(
            __FILE__, 
            Cfg::get('plugs/session/driver'), 
            __NAMESPACE__,
            false);

        if (!$class::_on_enable_()) {
            Log::err("Failed to enable session driver: `{$class}`.");
            return false;
        }
        else {
            return true;
        }
    }

    /**
     * Disable plug
     * --
     * @return  boolean
     */
    public static function _on_disable_()
    {
        Plug::get_config(__FILE__);
        $class = Plug::get_driver(
            __FILE__, 
            Cfg::get('plugs/session/driver'), 
            __NAMESPACE__,
            false);

        if (!$class::_on_disable_()) {
            Log::err("Failed to disable session driver: `{$class}`.");
            return false;
        }
        else {
            return true;
        }
    }


    /**
     * Create new session by id
     * --
     * @param   integer $id      User's id as in DB or JSON
     * @param   integer $expires Null for default or costume expiration in seconds,
     *                           0, to expires when browser is closed.
     * --
     * @return  boolean
     */
    public static function create($id, $expires=null)
    {
        return self::$driver 
            ? self::$driver->create($id, $expires) 
            : false;
    }

    /**
     * Destroy current session
     * --
     * @return void
     */
    public static function destroy()
    {
        return self::$driver 
                ? self::$driver->destroy() 
                : false;
    }

    /**
     * Is session set?
     * --
     * @return  boolean
     */
    public static function has()
    {
        return self::$driver 
                ? self::$driver->has() 
                : false;
    }

    /**
     * Will reload current session.
     * Useful after updating user's informations.
     * --
     * @return  void
     */
    public static function reload()
    {
        if (self::is_set() && self::$driver) 
            { self::$driver->reload(); }
    }

    /**
     * Will clear all expired sessions.
     * --
     * @return  void
     */
    public function cleanup()
        { self::$driver and self::$driver->cleanup(); }

    /**
     * Get particular information about user (session).
     * --
     * @param  string  $key
     * @param  mixed   $default
     * --
     * @return mixed
     */
    public static function get($key, $default=false)
    {
        return self::$driver
                ? self::$driver->get($key, $default)
                : $default;
    }

    /**
     * Return user's information as an array.
     * --
     * @return array
     */
    public static function as_array()
    {
        return self::$driver 
                ? self::$driver->as_array() 
                : array();
    }

    /**
     * List all sessions.
     * --
     * @return array
     */
    public static function list_all()
    {
        return self::$driver
                ? self::$driver->list_all()
                : array();
    }

    /**
     * Clear all sessions.
     * --
     * @return void
     */
    public static function clear_all()
        { self::$driver and self::$driver->clear_all(); }
}
