<?php namespace Avrelia\Core; if (!defined('AVRELIA')) die('Access is denied!');

/**
 * Route Class, RouteAssign Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Route
{
    /**
     * All registered urls, converted to regular expression
     * @var array
     */
    protected static $costume_uris;

    /**
     * All actions like: @INDEX, @BEFORE, @AFTER, ...
     * @var array
     */
    protected static $costume_actions;

    /**
     * All costume segments, like :numeric, :alpha, :alphanum, ...
     * @var array
     */
    protected static $costume_segments;


    /**
     * Register some defaults on include...
     * --
     * @return void
     */
    public static function _on_include_()
    {
        // Costume segments
        self::segment(array(':alphanum', ':alphanumeric'), '[\w]+',  true);
        self::segment(':alpha',                            '[a-z]+', true);
        self::segment(array(':num', ':numeric'),           '[0-9]+', true);
        self::segment(':any',                              '.+',     true);

        // Include our costume routes
        self::_load(array(
            sys_path('config/routes.php'),
            app_path('config/routes.php'),
            app_path('config/routes.local.php')
        ));
    }

    /**
     * Add -on- object
     * --
     * @param  string  $key
     * @param  mixed   $action String, pointing to controller, function
     * --
     * @return object          RouteAssign object
     */
    public static function on($key, $action=false)
    {
        $route_assign = new RouteAssign($key, $action);

        // We're having action
        if (substr($key, 0, 1) === '@') 
            { self::$costume_actions[$key] = $route_assign; }
        else 
            { self::$costume_uris[self::_key_to_regex($key)] = $route_assign; }

        return $route_assign;
    }

    /**
     * Costume segment validations...
     * --
     * @param  mixed $key    string or an array
     * @param  mixed $action function, string, array
     * --
     * @return void
     */
    public static function segment($key, $regex, $action)
    {
        if (!is_array($key)) { $key = array($key); }

        foreach ($key as $key_item) {
            self::$costume_segments[$key_item] = array(
                'action' => $action,
                'regex'  => $regex,
            );
        }
    }

    /**
     * Will convert key, to regular expression.
     * --
     * @param  string $key
     * --
     * @return string
     */
    protected static function _key_to_regex($key)
    {
        $segments = Str::explode_trim('/', $key);
        $has_optional = false;
        $regex = '';

        foreach ($segments as $segment) {

            // Is it optional
            if (substr($segment, 0, 1) === '?') {
                $segment = substr($segment, 1);
                if (!$has_optional) {
                    $has_optional = true;
                    // Include last / to be optional
                    $regex = substr($regex, 0, -2) . '(?:\/';
                }
            }

            // Is is special thing?
            if (substr($segment, 0, 1) === ':') {
                if (self::$costume_segments[$segment]) {
                    $segment = self::$costume_segments[$segment]['regex']
                                ? self::$costume_segments[$segment]['regex']
                                : '.*?';
                }
                else {
                    trigger_error("Segment not registered: `{$segment}`.");
                    return false;
                }
            }
            else {
                $segment = preg_quote($segment);

                // Restore | character
                $segment = str_replace('\|', '|', $segment);
            }

            $regex .= "({$segment})\/";
        }

        // Remove last /
        $regex = substr($regex, 0, -2);

        // If we had optional
        if ($has_optional) { $regex .= ')?'; }

        return "/^{$regex}$/i";
    }

    /**
     * Easy enough, will just check if required files exists, and include them.
     * --
     * @param  array $files
     * --
     * @return void
     */
    protected static function _load($files)
    {
        if (!is_array($files)) { $files = array($files); }

        foreach ($files as $file) {
            if (file_exists($file)) {
                include $file;
            }
        }
    }
}


class RouteAssign
{
    protected $key;
    protected $key_segments;

    protected $on_post   = false;
    protected $on_get    = false;
    protected $on_delete = false;
    protected $on_put    = false;

    protected $before    = false;
    protected $after     = false;

    /**
     * Set key and default action, if any.
     * --
     * @param string  $key
     * @param mixed   $action
     */
    public function __construct($key, $action=false)
    {
        $this->key = $key;
        $this->key_segments = Str::explode_trim('/', $key);

        if ($action) {
            $this->any($action);
        }
    }

    /**
     * What happens before this route
     * --
     * @param  string $actions
     * --
     * @return object $this
     */
    public function before($actions)
    {
        $this->before = Str::explode_trim(',', $actions);
        return $this;
    }

    /**
     * What happens after this route
     * --
     * @param  string $actions
     * --
     * @return object $this
     */
    public function after($actions)
    {
        $this->after = Str::explode_trim(',', $action);
        return $this;
    }

    /**
     * Set CRUD controller
     * --
     * @param  string $controller
     * --
     * @return object $this
     */
    public function crud($controller)
    {
        $this->on_get    = "{$controller}->get_:2";
        $this->on_put    = "{$controller}->put_:2";
        $this->on_post   = "{$controller}->post_:2";
        $this->on_delete = "{$controller}->delete_:2";

        return $this;
    }

    /**
     * POST Action
     * --
     * @param  mixed  $action
     * --
     * @return object $this
     */
    public function post($action)
    {
        $this->on_post = $action;
        return $this;
    }

    /**
     * GET Action
     * --
     * @param  mixed  $action
     * --
     * @return object $this
     */
    public function get($action)
    {
        $this->on_get = $action;
        return $this;
    }

    /**
     * PUT Action
     * --
     * @param  mixed  $action
     * --
     * @return object $this
     */
    public function put($action)
    {
        $this->on_put = $action;
        return $this;
    }

    /**
     * DELETE Action
     * --
     * @param  mixed  $action
     * --
     * @return object $this
     */
    public function delete($action)
    {
        $this->on_delete = $action;
        return $this;
    }

    /**
     * Trigger this action on any event.
     * --
     * @param  mixed  $action
     * --
     * @return object $this
     */
    public function any($action)
    {
        // Set all actions to be the same
        $this->on_put    = $action;
        $this->on_get    = $action;
        $this->on_post   = $action;
        $this->on_delete = $action;

        return $this;
    }
}