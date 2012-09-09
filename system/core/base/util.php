<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Util Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Util_Base
{
    # List of loaded files
    private static $loaded = array();

    /**
     * Will load util file
     * --
     * @param   string  $name
     * @return  void
     */
    public static function get($name)
    {
        if (in_array($name, self::$loaded)) { return false; }

        $util_name = app_path('util/'.strtolower($name).'.php');

        if (file_exists($util_name)) {
            include($util_name);
            self::$loaded[] = $name;
        }
        else {
            Log::err("Can't load util: `{$util_name}`, file not found.");
        }
    }
}
