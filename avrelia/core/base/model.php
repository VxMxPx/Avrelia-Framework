<?php namespace Avrelia\Core; if (!defined('AVRELIA')) die('Access is denied!');

/**
 * Models Base Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Model
{
    # Already created models
    protected static $cache = array();

    /**
     * Load Application's Model,
     * --
     * @param   string  $name
     * @param   boolean $force_new    If set to true, this will create new 
     *                                instance of class, even if already exists.
     * @return  mixed   object or false
     */
    public static function get($name, $force_new=false)
    {
        $class = ucfirst($name) . '_Model';

        if (!$force_new && isset(self::$cache[$class]))
        {
            return self::$cache[$class];
        }

        $instance = self::_new_instance($class);

        if (!$instance) {
            Loader::get_model($class);
            $instance = self::_new_instance($class);

            if (!$instance) {
                trigger_error(
                    "Can't load application's model: `{$class}`.", 
                    E_USER_ERROR);
            }
        }

        return $instance;
    }

    /**
     * Create new instance of a class, if exists
     * --
     * @param   string  $class
     * @return  mixed   object or false
     */
    protected static function _new_instance($class)
    {
        if (class_exists($class, false))
        {
            $instance = new $class();
            self::$cache[$class] = $instance;
            return $instance;
        }
        else {
            return false;
        }
    }
}