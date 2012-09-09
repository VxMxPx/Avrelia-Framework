<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Loader Base Class
 * -----------------------------------------------------------------------------
 * Will load application's model, controller, plug's class, etc...
 * ----
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Loader_Base
{
    /**
     * Load class by filename
     * --
     * @param   string  $class_name
     * @return  boolean
     */
    public static function get($class_name)
    {
        # Try to understand what kind of a class do we have...
        if (substr($class_name,  -10) === 'Controller') 
            { return self::get_controller($class_name); }

        if (substr($class_name,   -5) === 'Model')
            { return self::get_model($class_name); }

        if (substr($class_name, 0, 1) === 'u')
            { return self::get_util($class_name); }

        if (substr($class_name, 0, 1) === 'c')
            { return self::get_plug($class_name); }

        # Nothing of above rules?
        trigger_error("Autoload failed for: `{$class_name}`.", E_USER_ERROR);
    }

    /**
     * Will load plug's class
     * --
     * @param   string  $class_name
     * @return  boolean
     */
    public static function get_plug($class_name)
    {
        $file_name = to_underscore(substr($class_name, 1));
        return Plug::load($file_name);
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
        $file_name = to_underscore(substr($class_name, 1));

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
            "`{$fileName}`, prefix `{$classPrefix}`.", E_USER_ERROR);
    }

    /**
     * Loads application's model
     * @param  string $class_name
     * @return boolean
     */
    public static function get_model($class_name)
    {
        return self::_get_mc($class_name, 'models');
    }

    /**
     * Loads application's controller
     * @param  string $class_name
     * @return boolean
     */
    public static function get_controller($class_name)
    {
        return self::_get_mc($class_name, 'controllers');
    }

    /**
     * Will load application's model or controllers
     * --
     * @param   string  $class_name
     * @return  boolean
     */
    protected static function _get_mc($class_name, $type)
    {
        in_array($type, array('controllers', 'models'))
            or trigger_error(
                "Type must be either `controllers` or `models`.", E_USER_ERROR);

        $name = substr($class_name, 0, -(strlen($type)-1));
        $name = strtolower(to_underscore($name));
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
