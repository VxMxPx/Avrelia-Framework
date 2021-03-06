<?php namespace Avrelia\Core; if (!defined('AVRELIA')) die('Access is denied!');

/**
 * Loader Base Class
 * -----------------------------------------------------------------------------
 * Will load application's model, controller, plug's class, etc...
 * ----
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Loader
{
    /**
     * Just register self to be autoloader
     * @return true
     */
    public static function _on_include_()
    {
        # Register autoloader
        spl_autoload_register('Loader::get');

        return true;
    }

    /**
     * Load class by filename
     * --
     * @param   string  $class_name
     * --
     * @return  boolean
     */
    public static function get($class_name)
    {
        # Try to understand what kind of a class do we have...

        if (substr($class_name,  -11) === '_Controller') { 
            // CONTROLLER ------------------------------------------------------
            return self::get_controller($class_name); 
        }
        else if (substr($class_name,   -6) === '_Model') { 
            // MODEL -----------------------------------------------------------
            return self::get_model($class_name); 
        }
        else if (substr($class_name, 0, 5) === 'Util\\' 
                 || substr($class_name,   -5) === '_Util') {
            // UTIL ------------------------------------------------------------
            return self::get_util($class_name); 
        }
        else if (Plug::has($class_name)) {
            // PLUG ------------------------------------------------------------
            return self::get_plug($class_name); 
        }
        else {
            # If nothing of above, it doesn't exists  --------------------------
            trigger_error("Autoload failed for: `{$class_name}`.", E_USER_ERROR);
        }
    }

    /**
     * Will load core-class.
     * --
     * @param  string $class_name
     * --
     * @return boolean
     */
    public static function get_core($class_name)
    {
        $file_name = strtolower($class_name);
        $file_name = sys_path("core/base/{$file_name}.php");
        $ns_class  = "Avrelia\\Core\\{$class_name}";

        if (class_exists($ns_class, false) || class_exists($class_name, false)) {
            Log::war("Class already loaded: `{$ns_class}`.");
            return true;
        }

        if (!file_exists($file_name)) {
            trigger_error("Core file not found: `{$file_name}`.", E_USER_ERROR);
        }

        include $file_name;

        class_alias($ns_class, $class_name);

        // Will execute on include method if exits.
        if (method_exists($ns_class, '_on_include_')) { 
            call_user_func(array($ns_class, '_on_include_')); 
        }

        return true;
    }

    /**
     * Will load plug's class
     * --
     * @param   string  $class_name
     * --
     * @return  boolean
     */
    public static function get_plug($class_name)
    {        
        return Plug::load($class_name);
    }

    /**
     * Will load utils class
     * --
     * @param   string  $class_name
     * @return  boolean
     */
    public static function get_util($class_name)
    {
        $path = 'util';

        # Get filename
        if (substr($class_name, 0, 5) === 'Util\\') {
            $file_name = to_underscore(substr($class_name, 5));
        }
        else {
            $file_name = to_underscore(substr($class_name, 0, -5));
        }

        # Check APPLICATION folder...
        if (file_exists(app_path("{$path}/{$file_name}.php"))) {
            include app_path("{$path}/{$file_name}.php");
            return true;
        }

        # Check SYSTEM folder...
        if (file_exists(sys_path("{$path}/{$file_name}.php"))) {
            include sys_path("/{$path}/{$file_name}.php");
            return true;
        }

        trigger_error(
            "Autoload failed for: `{$class_name}`, class not found: ".
            "`{$file_name}`, prefix `{$classPrefix}`.", E_USER_ERROR);
    }

    /**
     * Loads application's model
     * --
     * @param  string $class_name
     * --
     * @return boolean
     */
    public static function get_model($class_name)
    {
        return self::_get_mc($class_name, 'models');
    }

    /**
     * Loads application's controller
     * --
     * @param  string $class_name
     * --
     * @return void
     */
    public static function get_controller($class_name)
    {
        return self::_get_mc($class_name, 'controllers');
    }

    /**
     * Will load application's model or controllers
     * --
     * @param   string  $class_name
     * --
     * @return  void
     */
    protected static function _get_mc($class_name, $type)
    {
        in_array($type, array('controllers', 'models'))
            or trigger_error(
                "Type must be either `controllers` or `models`.", E_USER_ERROR);

        $name = substr($class_name, 0, -(strlen($type)));
        $name = to_underscore($name);
        $name_split = explode('_', $name, 2);

        # Some possibilities
        $files = array();
        $files[] = app_path($type.'/'.$name.'.php');
        if (isset($name_split[1])) {
            $files[] = app_path($type.'/'.$name_split[0].'/'.$name.'.php');
            $files[] = app_path($type.'/'.$name_split[0].'/'.$name_split[1].'.php');
        }
        else {
            $files[] = app_path($type.'/'.$name_split[0].'/'.$name_split[0].'.php');
        }

        foreach ($files as $file) {
            if (file_exists($file)) {
                include $file;
                return true;
            }
        }

        trigger_error(
            "Can't load class `{$class_name}`, tried:" . print_r($files, true), 
            E_USER_ERROR);
    }
}
