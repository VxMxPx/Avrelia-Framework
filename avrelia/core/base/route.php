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
    protected static $uris;

    /**
     * All actions like: @INDEX, @BEFORE, @AFTER, ...
     * @var array
     */
    protected static $actions;

    /**
     * All costume segments, like :numeric, :alpha, :alphanum, ...
     * @var array
     */
    protected static $segments;

    /**
     * Those are special action, which when not found will trigger special event.
     * @var array
     */
    protected static $special = array('@404', '@OFFLINE', '@INDEX', '@BEFORE', '@AFTER');

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
        if (substr($key, 0, 1) === '@') { 
            self::$actions[$key] = $route_assign; 
        }
        else { 
            self::$uris[self::_key_to_regex($key)] = array
            (
                'action'   => $route_assign,
                'segments' => Str::explode_trim('/', $key)
            ); 
        }

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
            self::$segments[$key_item] = array(
                'action' => $action,
                'regex'  => $regex,
            );
        }
    }

    /**
     * This will trigger either particular URL or action, and execute all related
     * actions (like before and after). Returned will be array of responses from
     * all executed actions.
     * -- 
     * @param  string $action Either this can be URL or action if action then
     *                        prefix it with @ symbol.
     * @param  mixed  $type   Route::TYPE_POST, Route::TYPE_DELETE, 
     *                        Route::TYPE_PUT, Route::TYPE_GET
     *                        Null: Automatically get
     * --
     * @return void
     */
    public static function trigger($action, $type=null)
    {
        if (!$type) { $type = Input::get_method(); }

        // If we have / then assign it to @INDEX
        if (!trim($action, '/')) {
            $action = '@INDEX';
        }

        Log::inf("Triggered route: `{$action}`, type: `{$type}`.");

        if (substr($action, 0, 1) === '@') {
            $route    = self::_find_action($action);
            $segments = Input::segments();
        }
        else {
            $segments = self::_find_segments($action);

            if ($segments) {
                Log::inf("Segments match: `" . print_r($segments, true) . "`.");

                // Assign segments to the input
                Input::set_segments($segments);

                // Find route
                $route = self::_find_route($action);
            }
            else {
                $route = false;
            }
        }

        if ($route && is_a($route, 'Avrelia\\Core\\RouteAssign')) {
            $result = $route->trigger($segments, $type);
            return $result;
        } 
        else {
            Log::inf("Not found: `{$action}`.");

            if (substr($action, 0, 1) !== '@' || $action === '@INDEX') {
                Log::inf("This was URL request, will trigger 404!");
                self::trigger('@404');
            }
        }

        return false;
    }

    /**
     * Will try to match current url and return assigned segments for it. This
     * return false if url is not found.
     * 
     * Return false, if url can't be found.
     * --
     * @param  string $url  Current url to be matched upon
     * --
     * @return array  Or false
     */
    protected static function _find_segments($url)
    {
        foreach (self::$uris as $uri_regex => $route_assign) {
            if (preg_match($uri_regex, $url, $regex_segments)) {

                // We don't need first segment
                $regex_segments = array_slice($regex_segments, 1);

                // Some segments might have costume functions assigned to them,
                // if that's the case, we'll validate and process them.
                // This will return true, if all goes fine, and false if one of
                // the segments is not valid.
                $uri_segments = self::_process_segments($uri_regex, $regex_segments);

                // Do we have valid segments?
                return $uri_segments;
            }
        }
    }

    /**
     * Will try to match current url and return RouteAssign object, 
     * or false if can't be found.
     * 
     * Return false, if url can't be found.
     * --
     * @param  string $url  Current url to be matched upon
     * --
     * @return object       RouteAssign || false
     */
    protected static function _find_route($url)
    {
        foreach (self::$uris as $uri_regex => $route_assign) {
            if (preg_match($uri_regex, $url)) {
                return $route_assign['action'];
            }
        }
    }

    /**
     * Will look for aprticular action (@) and return RouteAssign object, 
     * or false if action can't be found.
     * --
     * @param  string $name Current action's name to be find
     * --
     * @return object       RouteAssign || false
     */
    protected static function _find_action($name)
    {
        if (isset(self::$actions[$name])) {
            return self::$actions[$name];
        }
        else {
            Event::trigger('/core/avrelia/route/not_found/'.$name);
            return false;
        }
    }

    /**
     * Some segments might have costume functions assigned to them,
     * if that's the case, we'll validate and process them.
     * This will return true, if all goes fine, and false if one of
     * the segments is not valid.
     * --
     * @param  string $uri_regex
     * @param  array  $segments
     * --
     * @return array  or false
     */
    protected static function _process_segments($uri_regex, $segments)
    {
        $raw_uri = Arr::element($uri_regex, self::$uris, false);
        $raw_uri = Arr::element('segments', $raw_uri, false);

        if (!$raw_uri) { return false; }

        $segments_new = array();

        foreach ($raw_uri as $num => $segment) {

            $segment = trim($segment, '?');

            // We have special segment
            if (substr($segment, 0, 1) === ':') {
                // Try to find it on the list...
                if (Arr::element($segment, self::$segments)) {
                    $execute = Arr::element($segment, self::$segments);
                    $execute = Arr::element('action', $execute);
                    
                    if (is_bool($execute)) {
                        if (!$execute) { return false; }
                    }
                    elseif (is_a($execute, 'Closure')) {
                        $exe_result = $execute($segments[$num]);
                        if ($exe_result) {
                            if (!is_bool($exe_result)) {
                                $segments_new[] = $exe_result;
                                continue;
                            }
                        }
                        else {
                            return false;
                        }
                    }
                    else {
                        return false;
                    }
                }
            }

            if (isset($segments[$num])) {
                $segments_new[] = $segments[$num];
            }
        }

        return $segments_new;
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
                if (self::$segments[$segment]) {
                    $segment = self::$segments[$segment]['regex']
                                ? self::$segments[$segment]['regex']
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
     * Trigger this route, and return function / method 
     * which needs to be executed.
     * --
     * @param  array   $segments
     * @param  string  $type        
     * --
     * @return array   List of actions to be executed.
     */
    public function trigger($segments=false, $type=false)
    {
        if (is_array($this->before)) {
            foreach ($this->before as $action) {
                if (!Route::trigger($action)) {
                    Log::inf("The action `BEFORE` returnes false: `{$action}`.");
                    return false;
                }
            }
        }

        $main_action = $this->_find_action($type);

        if (!Dispatcher::execute($main_action, $segments)) {
            Log::inf("The main action was not executed: {$main_action}.");
            $result = false;
        }
        else {
            $result = true;
        }

        if (is_array($this->after)) {
            foreach ($this->after as $action) {
                if (!Route::trigger($action)) {
                    Log::inf("The action `AFTER` returned false: `{$action}`.");
                    return false;
                }
            }
        }

        return $result;
    }

    /**
     * Find particural action and return it. If action not found, false will be
     * returned.
     * --
     * @param  string $type     Route::TYPE_
     * @param  array  $segments
     * --
     * @return mixed
     */
    protected function _find_action($type)
    {
        $method = 'on_' . strtolower($type);

        if (!$this->{$method}) { return false; }

        return $this->{$method};
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
        $this->after = Str::explode_trim(',', $actions);
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
        if (strpos($controller, '->') === false) {
            $this->on_get    = "{$controller}->get_:2";
            $this->on_put    = "{$controller}->put_:2";
            $this->on_post   = "{$controller}->post_:2";
            $this->on_delete = "{$controller}->delete_:2";
        }
        else {
            $controller = Str::explode_trim('->', $controller);
            $this->on_get    = "{$controller[0]}->get_{$controller[1]}";
            $this->on_put    = "{$controller[0]}->put_{$controller[1]}";
            $this->on_post   = "{$controller[0]}->post_{$controller[1]}";
            $this->on_delete = "{$controller[0]}->delete_{$controller[1]}";
        }

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
        if (is_string($action) && strpos($action, '->') !== false) {
            if (strpos($action, '->post_') === false) {
                $action = str_replace('->', '->post_', $action);
            }
        }

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
        if (is_string($action) && strpos($action, '->') !== false) {
            if (strpos($action, '->get_') === false) {
                $action = str_replace('->', '->get_', $action);
            }
        }

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
        if (is_string($action) && strpos($action, '->') !== false) {
            if (strpos($action, '->put_') === false) {
                $action = str_replace('->', '->put_', $action);
            }
        }

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
        if (is_string($action) && strpos($action, '->') !== false) {
            if (strpos($action, '->delete_') === false) {
                $action = str_replace('->', '->delete_', $action);
            }
        }

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