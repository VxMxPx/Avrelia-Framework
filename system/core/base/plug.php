<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Plug Base Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Plug_Base
{
    # List of included plugs
    protected static $included = array();

    # List of available plugs
    protected static $available = array();


    /**
     * This will refresh plugs in particular folder. This method will make sure,
     * that all plugs which we need are enabled.
     *
     * Return list of all plugs, with their statuses (time-stamp if was enabled, 
     * false if not).
     * --
     * @return  array
     */
    public static function _on_include_()
    {
        $list = Cfg::get('plug/enabled');

        # Load enabled, if there are any.
        if (file_exists(self::get_database_path('plugs.json'))) {
            self::$available = Json::decode_file(self::get_database_path('plugs.json'), true);
            self::$available = is_array(self::$available) ? self::$available : array();
        }
        else {
            self::$available = array();
        }

        # Nothing to see or do here if both are empty...
        if (empty($list) && empty(self::$available)) {
            return true;
        }

        # Do we have to disable & remove anything?
        foreach (self::$available as $plug => $status) {
            if ($status) {
                if (!in_array($plug, $list)) {
                    self::disable($plug);
                }
            }
        }

        # See if we have all we require on the list
        foreach ($list as $plug) {
            if (!isset(self::$available[$plug])) {
                # Enable it then...
                self::enable($plug);
            }
        }

        return self::$available;
    }

    /**
     * Check if particular plug is enabled.
     * --
     * @param   string  $name
     * @return  boolean
     */
    public static function has($name)
    {
        return 
            isset(self::$available[$name]) && self::$available[$name] 
                ? true 
                : false;
    }

    /**
     * Will enable particular plug. This will check for static method _OnEnable,
     * if method can't be found, we'll return true (no need to enable it).
     * If method can be found, it will be called and result will be returned.
     * --
     * @param   string  $plug
     * @return  boolean
     */
    public static function enable($plug)
    {
        $plug_path  = self::calculate_path($plug);
        $class_name = self::class_name($plug);

        if (!$class_name) {
            include ds("{$plug_path}/{$plug}.php");
        }

        $class_name = self::class_name($plug);

        if (!$class_name) {
            Log::war("Can't enable plug, class not found for: `{$plug}`.");
            return false;
        }

        if (method_exists($class_name, '_OnEnable')) {
            $return = $class_name::_OnEnable();
        }
        else {
            Log::inf("Method `_OnEnable` not found in `{$class_name}`.");
            $return = true;
        }

        # Add it to the list
        self::$available[$plug] = time();
        self::_save_list();

        # We Included Class, So We Need to Init it now.
        self::load($plug);
        return $return;
    }

    /**
     * Will disable particular plug. This will check for static method _OnDisable,
     * if method can't be found, we'll return true (no need to do anything disable).
     * If method can be found, it will be called and result will be returned.
     * --
     * @param   string  $plug
     * @return  boolean
     */
    public static function disable($plug)
    {
        # Remove it from list
        if (isset(self::$available[$plug])) {
            unset(self::$available[$plug]);
            self::_save_list();
        }

        $plug_path  = self::calculate_path($plug);
        $class_name = self::class_name($plug);

        if (!$class_name) {
            include ds("{$plug_path}/{$plug}.php");
        }

        $class_name = self::class_name($plug);

        if (!$class_name) {
            Log::war("Can't disable plug, class not found for: `{$plug}`.");
            return false;
        }

        if (method_exists($class_name, '_OnDisable')) {
            return $class_name::_OnDisable();
        }
        else {
            Log::inf("Method `_OnDisable` no found in `{$class_name}`.");
            return true;
        }
    }

    /**
     * Save list of available plugs
     * --
     * @return  void
     */
    protected static function _save_list()
    {
        return FileSystem::Write(
                    Json::encode(self::$available),
                    self::get_database_path('plugs.json'),
                    false,
                    0777
                );
    }

    /**
     * Will copy (if not found) all files from plug's "public" folder to
     * actual public folder.
     * The public folder name will be set based on plug's _id_ (name).
     * --
     * @param   string  $full_path   You can just pass __FILE__
     * @return  boolean
     */
    public static function set_public($full_path)
    {
        # Get full plug's path and plug's name
        $full_path = dirname($full_path);
        $com_name  = basename($full_path);

        # Full componet's public path
        $full_com_public_path = ds($full_path.'/public');

        # Check if plug has public directory
        if (!is_dir($full_com_public_path)) 
            { return true; }

        # Define public path
        $public_path = ds(PUBPATH.'/'.Cfg::get('plug/public_dir', 'plugs'));

        # Full public path
        $full_public_path = ds($public_path.'/'.$com_name);

        # Debug mode?
        if (Cfg::get('plug/debug') && is_dir($full_public_path)) {
            Log::inf(
                "The debug mode is enabled, will remove folder: ".
                "`{$full_public_path}`.");
            FileSystem::Remove($full_public_path);
        }

        # Exists not? :)
        if (!is_dir($full_public_path)) {
            Log::inf(
                "Can't find public copy, creating it: `{$full_public_path}` ".
                "from `{$full_com_public_path}`.");
            return FileSystem::Copy($full_com_public_path, $full_public_path);
        }

        return true;
    }

    /**
     * Will get config for particular plug
     * --
     * @param   string  $full_path   You can just pass __FILE__
     * @return  array
     */
    public static function get_config($full_path)
    {
        $path     = dirname($full_path);
        $name     = strtolower(FileSystem::FileName($path));
        $c_conf   = ds("{$path}/{$name}_config.php");
        $a_conf   = app_path("config/plugs/{$name}_config.php");
        $al_conf  = app_path("config/plugs/{$name}_config.local.php");
        $variable = "{$name}_config";
        $included = array();

        # Include plug's default settings (from plug's folder)
        if (file_exists($c_conf)) {
            $included[] = $c_conf;
            include $c_conf;
        }

        # Include settings for plug from application folder
        if (file_exists($a_conf)) {
            $included[] = $a_conf;
            include $a_conf;
        }

        # Include settings for plug from application folder, local version
        if (file_exists($al_conf)) {
            $included[] = $al_conf;
            include $al_conf;
        }

        # Check if variable is set and return it!
        if (!isset($$variable)) {
            $$variable = false;
        }

        # Log the list of included files
        Log::inf(
            "The following config files were included: \n" . 
            implode("\n", $included));

        # Append it to the global config
        $config['plugs'][$name] = $$variable;
        Cfg::append($config);

        return $$variable;
    }

    /**
     * Will load driver class for particular plug (+base, +interface if exists)
     * --
     * @param   string  $full_path  Full path to plug (including filename for 
     *                              main static class __FILE__)
     * @param   string  $type       Which driver do we need
     * @param   boolean $construct  If true, the driver will be constructed
     * @param   mixed   $prefix     In some cases we have more than one driver, 
     *                              and they're prefixed
     * @return  mixed               False if not loaded, 
     *                              object if construct is true, 
     *                              string (class name) if construct is false.
     */
    public static function get_driver($full_path, $type, $construct=true, $prefix=false)
    {
        # Get basepath
        $path = dirname($full_path);
        $plug_name = basename($path);

        # Resolve prefix
        if ($prefix) {
            $type = $prefix . '_' . $type;
        }

        # Resolve filenames
        # 1. interface
        $interface_class  = 'c' . to_camelcase($plug_name) . 'Driver';
        $interface_class .= ($prefix) ? to_camelcase($prefix) : '';
        $interface_class .= 'Interface';

        $interface_file  = $path . '/drivers/';
        $interface_file .= ($prefix) ? $prefix.'_' : '';
        $interface_file .= 'interface.php';

        # 2. base
        $base_class  = 'c' . to_camelcase($plug_name) . 'Driver';
        $base_class .= ($prefix) ? to_camelcase($prefix) : '';
        $base_class .= 'Base';

        $base_file  = $path . '/drivers/';
        $base_file .= ($prefix) ? $prefix.'_' : '';
        $base_file .= 'base.php';

        # 3. driver
        $driver_class  = 'c' . to_camelcase($plug_name) . 'Driver';
        $driver_class .= ($prefix) ? to_camelcase($prefix) : '';
        $driver_class .= to_camelcase($type);

        $driver_file  = $path . '/drivers/';
        $driver_file .= ($prefix) ? $prefix.'_' : '';
        $driver_file .= $type.'.php';

        # Load interface if exists
        if (!interface_exists($interface_class, false)) {
            if (file_exists($interface_file)) {
                include($interface_file);
            }
        }

        # Load base if exists
        if (!class_exists($base_class, false)) {
            if (file_exists($base_file)) {
                include($base_file);
            }
        }

        # Get driver's class
        if (!class_exists($driver_class, false)) {
            if (file_exists($driver_file)) {
                include($driver_file);
            }
            else {
                Log::war(
                    "Can't load driver: `{$driver_class}` from ".
                    "`{$driver_file}`, file not found.");
                return false;
            }
        }

        if (!class_exists($driver_class, false)) {
            Log::war("Class `{$driver_class}` not found in `{$driver_file}`.");
            return false;
        }

        Log::inf("Driver was loaded: `{$driver_class}`.");
        if ($construct) {
            return new $driver_class();
        }
        else {
            return $driver_class;
        }
    }

    /**
     * Will get language for particular plug
     * --
     * @param   string  $full_path   Full path to plug (including filename for 
     *                               main static class __FILE__)
     * @param   string  $language    Do we need particular language?
     * @param   boolean $get_default Get first default language, if requested 
     *                               can't be found
     * @return  void
     */
    public static function get_language($full_path, $language=false, $get_default=false)
    {
        $language = !$language ? '%' : $language;
        $path     = dirname($full_path);
        $name     = FileSystem::FileName($path, true);
        $c_lang    = ds("{$path}/languages/{$name}.{$language}.lng");
        $a_lang    = app_path("/languages/plugs/{$name}.{$language}.lng");

        Language::load($c_lang, $get_default);
        Language::load($a_lang, $get_default);
    }

    /**
     * Include plug(s).
     * Return true if successful and array (list of failed plugs) if not.
     * --
     * @param   array   $components     List of plugs to initialize
     * @param   boolean $auto_init      By default all plugs will be auto-initialize,
     *                                  set this to false, to avoid this behavior.
     *                                  Plug need to have static public method "_OnInit".
     * @param   boolean $stop_on_failed If one of the plugs, doesn't initialize, 
     *                                  should we stop loading?
     * @return  mixed
     */
    public static function load($components, $auto_init=true, $stop_on_failed=false)
    {
        if (!is_array($components)) { $components = array($components); }

        $failed = array();

        foreach ($components as $component)
        {
            if (isset(self::$included[$component])) 
                { continue; }
            else 
                { self::$included[$component] = true; }

            # Is it enabled?
            if (
                !isset(self::$available[$component]) 
                || self::$available[$component] == false
            ) {
                # If we're dealing with sub-class_
                if (!self::_guess($component)) {
                    trigger_error(
                        "Plug `{$component}` isn't enabled, can't continue.", 
                        E_USER_ERROR);
                    return false;
                }
            }

            # Do we have class already?
            $class_name = self::class_name($component);

            if (!$class_name) {
                # Try to include main class!
                $base_class_filename = self::calculate_path($component);
                $full_class_fileName = ds($base_class_filename."/{$component}.php");

                if (file_exists($full_class_fileName)) {
                    include $full_class_fileName;
                }
                else {
                    Log::war("Can't find plug: `{$full_class_fileName}`.");
                    $failed[] = $component;
                    if ($stop_on_failed) {
                        break;
                    }
                }
            }

            if ($auto_init)
            {
                $class_name = self::class_name($component);

                if (!$class_name) {
                    Log::war("Can't find plug's class for: `{$component}`.");
                    $failed[] = $component;
                    if ($stop_on_failed) {
                        break;
                    }
                }

                if (method_exists($class_name, '_OnInit'))
                {
                    if (!$class_name::_OnInit()) {
                        Log::war("Method: `_OnInit` in `{$class_name}` failed!");
                    }
                }
            }
        }

        return (empty($failed)) ? true : $failed;
    }

    /**
     * Used when we want to construct sub-class for particular plug before main
     * class was initialized. For example: cDatabaseQuery (before we called cDatabase,
     * which would actually included this class).
     * This will only find main class and initialize it, then if sub-class exists,
     * return true else false.
     * --
     * @param   string  $plug   Sub-class name
     * @return  boolean
     */
    protected static function _guess($plug)
    {
        # If we don't have any _, then we know it's not sub-class
        if (strpos($plug,'_') === false) { return false; }

        # Check if parent component is enable...
        $plug_pieces = explode('_', $plug);
        $final = '';

        foreach($plug_pieces as $p)
        {
            $final .= trim('_' . $p, '_');

            if (self::has($final)) {
                # Include it
                if (self::load($final)) {
                    # Do we have this class now?
                    return class_exists('c'.to_camelcase($plug), false);
                }
                else {
                    break;
                }
            }
        }

        return false;
    }

    /**
     * Get full absolute public path + additional
     * --
     * @param   string  $path
     * @return  string
     */
    public static function get_public_path($path=null)
    {
        return pub_path(Cfg::get('plug/public_dir', 'plugs') . '/' . $path);
    }

    /**
     * Get full public url + additional
     * --
     * @param   string  $uri
     * @return  string
     */
    public static function get_public_url($uri=false)
    {
        return url(Cfg::get('plug/public_dir', 'plugs') . '/' . $uri);
    }

    /**
     * Get full absolute database path + additional
     * --
     * @param   string  $path
     * @return  string
     */
    public static function get_database_path($path=null)
    {
        return dat_path(Cfg::get('plug/public_dir', 'plugs') . '/' . $path);
    }

    /**
     * Will calculate (look into application and system folder) plug's path.
     * --
     * @param   string  $plug
     * @return  string
     */
    public static function calculate_path($plug)
    {
        $app_path = app_path("plugs/{$plug}");
        $sys_path = sys_path("/plugs/{$plug}");

        return 
            is_dir($app_path) 
            ? $app_path 
            : is_dir($sys_path) 
                ? $sys_path 
                : false;
    }

    /**
     * Get Class Name from plug's name
     * --
     * @param   string  $plug
     * @return  string
     */
    public static function class_name($plug)
    {
        # Guess class name :)
        $class_name_one = 'c' . to_camelcase($plug, true);
        $class_name_two = 'c' . strtoupper($plug);

        if (class_exists($class_name_one, false)) 
            { return $class_name_one; }
        elseif (class_exists($class_name_two, false)) 
            { return $class_name_two; }
        else 
            { return false; }
    }
}
