<?php namespace Avrelia\Core; if (!defined('AVRELIA')) die('Access is denied!');

/**
 * Input Base Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Input
{
    # Request methods types
    const METHOD_GET    = 'GET';
    const METHOD_POST   = 'POST';
    const METHOD_PUT    = 'PUT';
    const METHOD_DELETE = 'DELETE';

    # List of all url segments
    protected static $segments = array();

    # Get segments
    protected static $get      = array();

    /**
     * Get Rid Of Globals & Set Uri Segment
     * from: http://www.phpguru.org/article/yet-more-on-cleaning-input-data
     * --
     * @return void
     */
    public static function _on_include_()
    {
        // Set get segments
        self::$get = $_GET;

        // Set list of segments
        $segments = Str::explode_trim('/', self::get_path_info());

        // Register segments containing equal sign, to get
        foreach ($segments as $segment) {
            if (Cfg::get('core/input/eq_segments_as_get')) {
                if (strpos($segment, '=') !== false) {
                    $segment_get = Str::explode_trim('=', $segment, 2);
                    self::$get[$segment_get[0]] = $segment_get[1];
                    continue;
                }
            }
            self::$segments[] = $segment;
        }
    }

    /**
     * Get server's path info.
     * --
     * @return string
     */
    public static function get_path_info() 
    {
        return Cfg::get('core/input/ignore_get_segmments') 
                ? $_SERVER['PATH_INFO']
                : $_SERVER['REQUEST_URI'];
    }

    /**
     * Return particular uri segment if set, otherwise return default value.
     * --
     * @param  integer  $number
     * @param  mixed    $defult
     * --
     * @return mixed
     */
    public static function segment($number, $defult=false) 
    {
        return isset(self::$segments[$number]) 
                ? self::$segments[$number]
                : $default;
    }

    /**
     * Return list all segments currently set.
     * --
     * @return array
     */
    public static function segments()
    {
        return self::$segments;
    }

    /**
     * Set entirely new list of segments.
     * --
     * @param array $list
     * --
     * return null
     */
    public static function set_segments($list) 
    {
        self::$segments = $list;
    }

    /**
     * Get URI _GET segment.
     * @param  mixed   $key    Following options are available:
     *                             false:  return all keys
     *                             string: return particular key if exists
     *                             array:  return keys specified in array
     * @param  mixed   $defult Default value(s) when key not found
     * --
     * @return mixed
     */
    public static function get($key=false, $defult=false) 
    {
        if (!$key) { return self::$get; }

        if (is_array($key)) {
            return Arr::elements($key, self::$get, $default);
        }
        else {
            return Arr::element($key, self::$get, $default);
        }
    }

    /**
     * Get _POST segment.
     * @param  mixed   $key    Following options are available:
     *                             false:  return all keys
     *                             string: return particular key if exists
     *                             array:  return keys specified in array
     * @param  mixed   $defult Default value(s) when key not found
     * --
     * @return mixed
     */
    public static function post($key=false, $default=false) 
    {
        if (!$key) { return $_POST; }

        if (is_array($key)) {
            return Arr::elements($key, $_POST, $default);
        }
        else {
            return Arr::element($key, $_POST, $default);
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

    /**
     * Return current url, if withQuery is set to true, it will return full url,
     * query included.
     * --
     * @param   boolean $with_query
     * --
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
     * Will get current domain
     * --
     * @return string
     */
    public static function get_domain() 
    { 
        return $_SERVER['SERVER_NAME']; 
    }

    /**
     * Get request method: Input::METHOD_GET, Input::METHOD_POST, 
     *                     Input::METHOD_PUT, Input::METHOD_DELETE
     * --
     * @return integer
     */
    public static function get_method() 
    {
        // Is it put?
        if (is_array(Cfg::get('core/input/put_from_post'))) {
            list($method, $value) = Cfg::get('core/input/put_from_post');
            if (self::post($method) === $value) {
                return self::METHOD_PUT;
            }
        }

        // Is it delete?
        if (is_array(Cfg::get('core/input/delete_from_post'))) {
            list($method, $value) = Cfg::get('core/input/delete_from_post');
            if (self::post($method) === $value) {
                return self::METHOD_DELETE;
            }
        }
        if (is_array(Cfg::get('core/input/delete_from_get'))) {
            list($method, $value) = Cfg::get('core/input/delete_from_get');
            if (self::get($method) === $value) {
                return self::METHOD_DELETE;
            }
        }

        // Is it post?
        if (self::has_post()) {
            return self::METHOD_POST;
        }

        // It must be get.
        return self::METHOD_GET;
    }
}
