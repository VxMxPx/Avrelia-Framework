<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Input Base Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Input_Base
{
    # List Of All URI segments (segment0/segment1) ('segment0', 'segment1')
    private static $uri_segments = array();

    # List Of All Actions (action=value)
    private static $uri_actions  = array();

    # Build uri segements
    private static $build_uri    = array();

    /**
     * Get Rid Of Globals & Set Uri Segment
     * from: http://www.phpguru.org/article/yet-more-on-cleaning-input-data
     * --
     * @return void
     */
    public static function _on_include_()
    {
        if (ini_get('register_globals'))
        {
            Log::inf("We'll dispel globals now.");

            # Might want to change this perhaps to a nicer error
            if (isset($_REQUEST['GLOBALS'])) {
                trigger_error('GLOBALS overwrite attempt detected.', E_USER_ERROR);
            }

            # Variables that shouldn't be unset
            $no_unset = array('GLOBALS', 'GET', 'POST', '_COOKIE', '_SERVER', '_ENV', '_FILES');

            $input = array_merge(
                $_GET, 
                $_POST, 
                $_COOKIE, 
                $_SERVER, 
                $_ENV, 
                $_FILES, 
                isset($_SESSION) ? (array)$_SESSION : array());

            foreach ($input as $k => $v) {
                if (!in_array($k, $no_unset) && isset($GLOBALS[$k])) {
                    unset($GLOBALS[$k]);
                }
            }
        }

        # Set Get Actions And Segments
        $uri = self::server('REQUEST_URI');
        $uri = vString::RegExClean($uri, Cfg::get('system/input_get_filter', '/[^a-z0-9_]/'));

        # Shouldn't Be Empty
        if ($uri == '') { return false; }

        # Get Array Of Segments
        $uri_segments = explode('/', $uri);

        # It Should Be Array, And Shouldn't Be Empty!
        if (!is_array($uri_segments) && !empty($uri_segments)) { return false; }

        # We'll have 120 values in array
        $uri_segments = array_slice($uri_segments, 0, 120);

        # Main Loop
        foreach($uri_segments as $segment)
        {
            # Check If It's An Action Or Segment
            if (strpos($segment, '='))
            {
                # Is it action?
                $action = '';
                $action = explode('=', $segment, 2);
                $value  = (isset($action[1]) ? trim($action[1]) : '');
                $action = (isset($action[0]) ? trim($action[0]) : '');
                if ($action != '') {
                    # This Is Clean, since we clean whole segment
                    $action = strtolower($action);
                    self::$uri_actions[$action] = $value;
                }
            }
            else {
                // Is it segment?
                if (strlen(trim($segment)) > 0) {
                    # This Is Clean, since we clean whole segment
                    self::$uri_segments[] = substr($segment, 0, 400);
                }
            }
        }

        return true;
    }

    /**
     * Get the request uri (example /edit/me/now)
     * --
     * @param  boolean $include_actions
     * @return string
     */
    public static function get_request_uri($include_actions=true)
    {
        return $include_actions 
                ? $_SERVER['REQUEST_URI'] 
                : implode('/', self::$uri_segments);
    }

    /**
     * This will build url (by replacing existing) from segments / actions.
     * --
     * @param   array   $uri             Examples: array(
     *                                       0 => 'segment', 
     *                                       1 => 'segment1', 
     *                                       'action' => 'value')
     * @param   boolean $update_current  Will keep current uri's 
     *                                      segments / actions and update them.
     * @return  string
     */
    public static function build_uri($uri, $update_current=true)
    {
        if (!is_array($uri)) { $uri = array($uri); }

        if (!empty(self::$build_uri)) {
            foreach (self::$build_uri as $id => $segment) {
                if (!isset($uri[$id])) { $uri[$id] = $segment; }
            }
        }
        ksort($uri);
        $final_uri = '';

        # Update Current Url
        if ($update_current)
        {
            foreach (self::$uri_segments as $num => $value)
            {
                if (isset($uri[$num])) {
                    $final_uri .= $uri[$num] . '/';
                    unset($uri[$num]);
                }
                else {
                    $final_uri .= $value . '/';
                }
            }

            foreach (self::$uri_actions as $num => $value)
            {
                if (isset($uri[$num])) {
                    if ($uri[$num] === false) { continue; }

                    $final_uri .= "{$num}=".$uri[$num]."/";
                    unset($uri[$num]);
                }
                elseif ($value !== false) {
                    $final_uri .= "{$num}={$value}/";
                }
            }
        }

        # Add All New Segments
        foreach ($uri as $num => $value) {
            if (is_numeric($num))
                { $final_uri .= $value . '/'; }
            elseif ($value !== false)
                { $final_uri .= "{$num}={$value}/"; }
        }

        return trim($final_uri, '/');
    }

    /**
     * Fetch an item from the POST array
     * --
     * @param  mixed   $key      If key is empty, then we'll return whole post!
     * @param  mixed   $default  Default if variable isn't set....
     * @return mixed
     */
    public static function post($key=false, $default=false)
    {
        if (!$key) {
            return !empty($_POST) ? $_POST : $default;
        }
        elseif (is_array($key)) {
            $new_array = array();
            foreach ($key as $id => $sel) {
                $id = is_integer($id) ? $sel : $id;
                $new_array[$id] = isset($_POST[$sel]) ? $_POST[$sel] : $default;
            }
            return $new_array;
        }
        elseif (!isset($_POST[$key])) {
            return $default;
        }
        else {
            return $_POST[$key];
        }
    }

    /**
     * Return true if any data was posted, and false if wasn't
     * --
     * @param  string $key Are we looking for particular key?
     * @return boolean
     */
    public static function has_post($key=false)
    {
        if ($key)
            { return isset($_POST[$key]); }
        else
            { return !empty($_POST); }
    }
    //-

    /**
     * Return current url, if withQuery is set to true, it will return full url,
     * query included.
     * --
     * @param   boolean $with_query
     * @return  string
     */
    public static function get_url($with_query=false)
    {
        # TODO: What if there's https ?
        $url = 'http://'.$_SERVER['SERVER_NAME'];

        # Make sure we have ending '/'!
        $url = trim($url, '/') . '/';

        if ($with_query) {
            $url = $url . ltrim($_SERVER['REQUEST_URI'], '/');
        }

        return $url;
    }

    /**
     * Fetch an item from the GET array
     *
     * @param  mixed $key - enter one of the following values:
     *     false:   return whole url
     *     integer: for segment (example: 0 - will get segment 0)
     *     string:  for action (example: `my_action` - will get `some_action` 
     *              from: `my_uri/my_action=some_action`)
     *     string (? prefix): for regular get (example: `?my_action` will get 
     *                        `some_action` from `my_uri?my_action=some_action`)
     *
     * @param  mixed $default - Default value (if request isn't set)
     * @return mixed
     */
    public static function get($key, $default=false)
    {
        if ($key === false) { return self::server('REQUEST_URI'); }

        if (is_numeric($key)) {
            if (isset(self::$uri_segments[$key]))
                { return self::$uri_segments[$key]; }
            else
                { return $default; }
        }
        else {
            if (substr($key, 0, 1) === '?') {
                $key = substr($key, 1);
                return isset($_GET[$key]) ? $_GET['key'] : $default;
            }
            else {
                if (isset(self::$uri_actions[$key]))
                    { return self::$uri_actions[$key]; }
                else
                    { return $default; }
            }
        }
    }

    /**
     * Will get current domain
     * --
     * @return string
     */
    public static function get_domain() { return $_SERVER['SERVER_NAME']; }

    /**
     * Fetch an item from the SERVER array
     * --
     * @param  string $key
     * @return string
     */
    public static function server($key='')
    {
        if (!isset($_SERVER[$key])) { return false; }
        return vString::EncodeEntities($_SERVER[$key]);
    }
}
