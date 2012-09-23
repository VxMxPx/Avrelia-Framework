<?php namespace Avrelia\Core; if (!defined('AVRELIA')) die('Access is denied!');

/**
 * Base Cfg Class
 * -----------------------------------------------------------------------------
 * Handles core configurations of system.
 * ----
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Cfg
{
    # All configurations values
    private static $configs = array();

    # Cached values, for faster search
    private static $cache = array();

    /**
     * Load default configurations.
     * @return void
     */
    public static function _on_include_()
    {
        # Both application's and system's main config
        self::load(sys_path('config/main.php'));
        self::load(app_path('config/main.php'));
    }

    /**
     * Append config
     * --
     * @param  array $config
     * @return void
     */
    public static function append($config)
    {
        # First we'll clear all cached values
        self::$cache = array();

        # Doesn't work, change 404 to 1 and on duplicates create new array
        //self::$configs = array_merge_recursive(self::$configs, $config);

        # Doesn't work, merge failed
        //self::$configs = array_merge(self::$configs, $config);

        # Works perfectly
        self::$configs = Arr::merge(self::$configs, $config);
    }

    /**
     * Will load and append config file. File need to contain $avrelia_config
     * variable.
     * --
     * @param  string  $filename Fullpath or only name of file
     * @return boolean
     */
    public static function load($filename)
    {
        # Check if is full path
        if (substr($filename,-4,4) != '.php') {
            $filename = app_path("config/{$filename}.php");
        }

        # In case of wrong filename!
        if (!file_exists($filename)) {
            trigger_error("File not found: `{$filename}`.", E_USER_ERROR);
            return false;
        }

        include($filename);

        if (!isset($avrelia_config))
        {
            trigger_error("File was loaded {$filename}, but \$avrelia_config isn't set!", E_USER_WARNING);
            return false;
        }

        # Try to include local too
        $local_file = substr($filename,0,-4) . '.local.php';
        if (file_exists($local_file)) {
            include($local_file);
        }

        self::append($avrelia_config);
        return true;
    }

    /**
     * Return all config and cache as a string.
     * --
     * @return string
     */
    public static function debug()
    {
        $cache   = htmlspecialchars(print_r(self::$cache, true));
        $configs = htmlspecialchars(print_r(self::$configs, true));
        
        return
            'Cache ' . dump($cache, false, true) .
            "\n" .
            'Config ' . dump($configs, false, true);
    }

    /**
     * Get Config Item
     * --
     * @param  string  $key      In format: key/subkey
     * @param  mixed   $default  Default value, if config isn't set
     * @return mixed
     */
    public static function get($key, $default=null)
    {
        if (!isset(self::$cache[$key])) {
            self::$cache[$key] = Arr::get_by_path($key, self::$configs, $default);
        }

        return self::$cache[$key];
    }

    /**
     * Overwrite particular config key, this is temporary action,
     * the changes won't get saved.
     * --
     * @param   string  $path   In format: key/subkey
     * @param   mixed   $value
     * @return  void
     */
    public static function overwrite($path, $value)
    {
        # Clear cache to avoid conflicts
        self::$cache = array();

        Arr::set_by_path($path, $value, self::$configs);
    }
}
