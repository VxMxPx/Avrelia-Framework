<?php namespace Plug\Avrelia; if (!defined('AVRELIA')) die('Access is denied!');

use Avrelia\Core\Cfg  as Cfg;
use Avrelia\Core\Log  as Log;
use Avrelia\Core\Plug as Plug;

/**
 * Cookie Plug
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Cookie
{
    public static function _on_include_()
    {
        Plug::get_config(__FILE__);
        return true;
    }

    /**
     * Create cookie
     * 
     * @param  string $name
     * @param  string $value
     * @param  string $expire  Use false for default expire time (set in 
     *                         configuration), or enter value (actuall value so 
     *                         must be time() + seconds)
     * @return bool
     */
    public static function create($name, $value, $expire=false)
    {
        # Expire
        if ($expire === false) {
            $expire = time() + Cfg::get('plugs/cookie/timeout');
        }

        # Domain
        $domain = Cfg::get('plugs/cookie/domain');
        if (!$domain) { $domain = $_SERVER['SERVER_NAME']; }

        Log::inf('Cookie will be set, as: "' . Cfg::get('cookie/prefix') .
                    $name . '", with value: "'  .
                    $value . '", set to expire: "' .
                    ($expire) . '" to domain: "' . $domain . '"');

        return setcookie(
            Cfg::get('plugs/cookie/prefix') . $name, 
            $value, 
            $expire, 
            "/", 
            $domain);
    }

    /**
     * Fetch an item from the COOKIE array
     * 
     * @param  string $key
     * @return mixed
     */
    public static function read($key='')
    {
        $key_prefix = Cfg::get('plugs/cookie/prefix') . $key;

        # Is Cookie With Prefix Set?
        if (isset($_COOKIE[$key_prefix])) 
            { $return = $_COOKIE[$key_prefix]; }
        elseif (isset($_COOKIE[$key])) 
            { $return = $_COOKIE[$key]; }
        else 
            { return false; }

        return htmlspecialchars($return);
    }

    /**
     * Remove cookie
     * 
     * @param  string $name
     * @return boolean
     */
    public static function remove($name)
    {
       Log::inf('Cookie will be unset: `'.Cfg::get('plugs/cookie/prefix').$name.'`.');

        # Domain
        $domain = Cfg::get('plugs/cookie/domain');
        if (!$domain) {
            $domain = $_SERVER['SERVER_NAME'];
        }

       return setcookie(
            Cfg::get('plugs/cookie/prefix') . $name, 
            '', 
            time() - 3600, 
            "/", 
            $domain);
    }
}
