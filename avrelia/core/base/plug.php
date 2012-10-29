<?php namespace Avrelia\Core; if (!defined('AVRELIA')) die('Access is denied!');

/**
 * Plug Base Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Plug
{
    # List of included plugs, those are the one, which were loaded
    protected static $included = array();

    # List of available plugs, those are the one, which are enabled
    # But not necessarily loaded at the moment.
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
        $list = Cfg::get('core/plug/enabled');

        # Load enabled, if there are any.
        if (file_exists(self::get_database_path('plugs.json'))) {
            self::$available = Json::decode_file(
                                    self::get_database_path('plugs.json'), 
                                    true);
        }
        
        self::$available = is_array(self::$available) 
                            ? self::$available 
                            : array();

        # Nothing to see or do here if both are empty...
        if (empty($list) && empty(self::$available)) { return true; }

        # Need to disable and remove anything?
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
     * The same as "has" only that this will trigger fatal error if any of the 
     * needed plugs isn't enabled.
     * --
     * @param  mixed $name String or array, list of needed plugs
     * --
     * @return void
     */
    public static function need($name)
    {
        if (is_array($name)) {
            foreach ($name as $n) {
                self::need($n);
            }

            return true;
        }

        if (!self::has($name)) {
            trigger_error("Missing dependency: `{$name}`.", E_USER_ERROR);
            return false;
            //throw new \Avrelia\Exception\Plug("Missing dependency: `{$name}`.");
        }
        else 
            { return true; }
    }

    /**
     * Check if particular plug is enabled. If you pass in the array, all plugs
     * on the list will be checked, and if any of them is missing false will
     * be returned.
     * --
     * @param   mixed  $name
     * --
     * @return  boolean
     */
    public static function has($name)
    {
        if (is_array($name)) {
            foreach ($name as $n) {
                if (!self::has($n)) { return false; }
            }

            return true;
        }

        # If doesn't have Plug\ in the name, try to get it
        if (strpos($name, 'Plug\\') === false) {
            $name = self::_get_class_by_alias($name);
        }

        return 
            isset(self::$available[$name]) && self::$available[$name] 
                ? true 
                : false;
    }

    /**
     * Will enable particular plug. This will check for static method _on_enable_,
     * if method can't be found, we'll return true (no need to enable it).
     * If method can be found, it will be called and result will be returned.
     * --
     * @param   string  $plug
     * --
     * @return  boolean
     */
    public static function enable($plug)
    {
        $plug_path     = self::get_path($plug);
        $relative_path = substr($plug_path, strlen(plg_path()));
        $class_name    = $plug;

        if (!class_exists($class_name, false)) {
            $try_filename = ds($plug_path, self::get_file($plug).'.php');

            if (file_exists($try_filename)) 
                { include $try_filename; }
            else
                { return Log::war("File not found: `{$try_filename}` for `{$plug}`."); }
        }

        if (!class_exists($class_name, false)) {
            Log::war("Can't enable plug, class not found for: `{$plug}`.");
            return false;
        }

        if (method_exists($class_name, '_on_enable_')) {
            $return = $class_name::_on_enable_();
        }
        else {
            Log::inf("Method `_on_enable_` not found in `{$class_name}`.");
            $return = true;
        }

        # Check if we have script(s) for this plug...
        if (Dir::is_empty(ds($plug_path,'scripts'))) {
            $has_scripts = false;
        } else {
            $has_scripts = array();
            $files = scandir(ds($plug_path,'scripts'));
            foreach ($files as $file) {
                if (substr($file, -4, 4) === '.php') {
                    // Get relative script path
                    $script_path = ds($relative_path,'scripts',$file);
                    $has_scripts[substr($file, 0, -4)] = $script_path;
                }
            }
            if (empty($has_scripts))
                    { $has_scripts = false; }
        }

        # Add it to the list
        self::$available[$plug] = array(
            'path'        => $relative_path,
            'time'        => time(),
            'alias'       => self::_get_class_alias($plug),
            'has_scripts' => $has_scripts
        );
        self::_save_list();

        # We Included Class, So We Need to Init it now.
        self::load($plug);
        return $return;
    }

    /**
     * Will disable particular plug. This will check for static method _on_disable_,
     * if method can't be found, we'll return true (no need to do anything disable).
     * If method can be found, it will be called and result will be returned.
     * --
     * @param   string  $plug
     * --
     * @return  boolean
     */
    public static function disable($plug)
    {
        # Remove it from list
        if (isset(self::$available[$plug])) {
            unset(self::$available[$plug]);
            self::_save_list();
        }

        $plug_path  = self::get_path($plug);
        $class_name = $plug;

        if (!class_exists($class_name, false)) {
            include ds($plug_path, self::get_file($plug).'.php');
        }

        if (!class_exists($class_name, false)) {
            Log::war("Can't disable plug, class not found for: `{$plug}`.");
            return false;
        }

        if (method_exists($class_name, '_on_disable_')) {
            return $class_name::_on_disable_();
        }
        else {
            Log::inf("Method `_on_disable_` no found in `{$class_name}`.");
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
     * --
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
        $public_path = ds(PUBPATH.'/'.Cfg::get('core/plug/public_dir', 'plugs'));

        # Full public path
        $full_public_path = ds($public_path.'/'.$com_name);

        # Debug mode?
        if (Cfg::get('core/plug/debug') && is_dir($full_public_path)) {
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
     * --
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
     * Get all, in plugs, registered scripts. Return an array, example:
     *     'script_id' => 'full_path',
     *     'script_id' => 'full_path'
     * --
     * @return array
     */
    public static function get_scripts()
    {
        $result = array();

        if (is_array(self::$available)) {
            foreach (self::$available as $plug) {
                if (isset($plug['has_scripts']) && is_array($plug['has_scripts'])) {
                    foreach ($plug['has_scripts'] as $script_id => $script_path) {
                        $result[$script_id] = plg_path($script_path);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Will load driver class for particular plug (+base, +interface if exists)
     * --
     * @param   string  $full_path  Full path to plug (including filename for 
     *                              main static class __FILE__)
     * @param   string  $type       Which driver do we need
     * @param   string  $namespace
     * @param   boolean $construct  If true, the driver will be constructed
     * @param   mixed   $prefix     In some cases we have more than one driver, 
     *                              and they're prefixed
     * @return  mixed               False if not loaded, 
     *                              object if construct is true, 
     *                              string (class name) if construct is false.
     */
    public static function get_driver(
        $full_path,
        $type,
        $namespace,
        $construct=true,
        $prefix=false
    ) {
        # Get basepath
        $path = dirname($full_path);
        $plug_name = basename($path);

        # Resolve prefix
        if ($prefix) {
            $type = $prefix . '_' . $type;
        }

        # Resolve filenames
        # 1. interface
        $interface_class  = $namespace . CHAR_BACKSLASH;
        $interface_class .= to_camelcase($plug_name) . 'Driver';
        $interface_class .= ($prefix) ? to_camelcase($prefix) : '';
        $interface_class .= 'Interface';

        $interface_file  = $path . '/drivers/';
        $interface_file .= ($prefix) ? $prefix.'_' : '';
        $interface_file .= 'interface.php';

        # 2. base
        $base_class  = $namespace . CHAR_BACKSLASH;
        $base_class .= to_camelcase($plug_name) . 'Driver';
        $base_class .= ($prefix) ? to_camelcase($prefix) : '';
        $base_class .= 'Base';

        $base_file  = $path . '/drivers/';
        $base_file .= ($prefix) ? $prefix.'_' : '';
        $base_file .= 'base.php';

        # 3. driver
        $driver_class  = $namespace . CHAR_BACKSLASH;
        $driver_class .= to_camelcase($plug_name) . 'Driver';
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
     *                                  Plug need to have static public method "_on_include_".
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
            # If doesn't have Plug\ in the name, try to get it
            if (strpos($component, 'Plug\\') === false) {
                $component = self::_get_class_by_alias($component);
            }

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
            $class_name = $component;

            if (!class_exists($class_name, false)) {
                # Try to include main class!
                $base_class_filename = self::get_path($component);
                $full_class_fileName = ds($base_class_filename, self::get_file($component).'.php');

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
                $class_name = $component;

                if (!class_exists($class_name, false)) {
                    Log::war("Can't find plug's class for: `{$component}`.");
                    $failed[] = $component;
                    if ($stop_on_failed) {
                        break;
                    }
                }

                if (method_exists($class_name, '_on_include_'))
                {
                    if (!$class_name::_on_include_()) {
                        Log::war("Method: `_on_include_` in `{$class_name}` failed!");
                    }
                }
            }

            self::map_class($component);
        }

        return (empty($failed)) ? true : $failed;
    }

    /**
     * Get full class name (with namespace) by alias
     * @param  string $class
     * @return string
     */
    protected static function _get_class_by_alias($class)
    {
        if (is_array(self::$available)) {
            foreach (self::$available as $class_name => $data) {
                if ($data['alias'] === $class) {
                    return $class_name;
                }
            }
        }

        return $class;
    }

    /**
     * Get class alias, from namespaced name
     * @param  string $class_name
     * @return string
     */
    protected static function _get_class_alias($class_name)
    {
        # Conver it to path
        $path  = ds($class_name);
        return get_path_segment($path, -1);
    }

    /**
     * Convert namespaced plug to flat class name
     * --
     * @param  string $class_name
     * --
     * @return void
     */
    protected static function map_class($class_name, $alias=false)
    {
        if (!$alias) { $alias = self::_get_class_alias($class_name); }

        Log::inf("Will map class `{$class_name}` to `{$alias}`.");
        class_alias($class_name, $alias);
    }

    /**
     * Used when we want to construct sub-class for particular plug before main
     * class was initialized. For example: DatabaseQuery (before we called Database,
     * which would actually include this class).
     * This will only find main class and initialize it, if sub-class exists,
     * return true else false.
     * --
     * @param   string  $plug   Sub-class name
     * @return  boolean
     */
    protected static function _guess($plug)
    {
        $plug_us = to_underscore($plug);

        # If we don't have any _, then we know it's not sub-class
        if (strpos($plug_us,'_') === false) { return false; }

        # Check if parent component is enable...
        $plug_pieces = explode('_', $plug_us);
        $final = '';

        foreach($plug_pieces as $p)
        {
            $final .= ucfirst($p);

            if (self::has($final)) {
                # Include it
                if (self::load($final)) {
                    # Do we have this class now?
                    return class_exists($plug, false);
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
        return pub_path(Cfg::get('core/plug/public_dir', 'plugs') . '/' . $path);
    }

    /**
     * Get full public url + additional
     * --
     * @param   string  $uri
     * @return  string
     */
    public static function get_public_url($uri=false)
    {
        return url(Cfg::get('core/plug/public_dir', 'plugs') . '/' . $uri);
    }

    /**
     * Get full absolute database path + additional
     * --
     * @param   string  $path
     * @return  string
     */
    public static function get_database_path($path=null)
    {
        return dat_path(Cfg::get('core/plug/public_dir', 'plugs') . '/' . $path);
    }

    /**
     * Will calculate plug's path.
     * --
     * @param   string  $plug
     * @return  string
     */
    public static function get_path($plug)
    {
        $plug = substr(mb_strtolower($plug), 5);
        $plug = ds($plug);
        $path = plg_path($plug);

        return 
            is_dir($path) 
                ? $path 
                : false;
    }

    /**
     * Get filename from class / plug name
     * --
     * @param  string $plug
     * --
     * @return string
     */
    public static function get_file($plug)
    {
        $plug = mb_strtolower($plug);
        $path_segments = Str::explode_trim(CHAR_BACKSLASH, $plug);

        return array_pop($path_segments);
    }
}
