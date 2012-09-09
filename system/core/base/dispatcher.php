<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Dispatcher Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Dispatcher_Base
{
    # Full raw requested URI
    protected $request_uri;

    # Controllers cache
    protected $controllers;

    /**
     * Set request URI
     */
    public function __construct()
        { $this->request_uri = trim(Input::get_request_uri(false), '/'); }

    /**
     * Resolve routes and call appropriate controller
     * --
     * @return void
     */
    public function boot()
    {
        Event::trigger('/core/dispatcher/boot/before');

        # Is application offline?
        if ($this->_is_offline()) { $this->trigger_offline(); }

        # Do we have any action before regular route is called?
        $this->_before_dispatch();

        # If we don't dind URL, we just off-line
        if (!$this->_find_uri()) { $this->trigger_404(); }

        # After dispatch
        $this->_after_dispatch();

        Event::trigger('/core/dispatcher/boot/after');
    }

    /**
     * Check if there's any action which should be executed
     * before we dispatch.
     * --
     * @return  void
     */
    protected function _before_dispatch()
    {
        if (Cfg::get('system/routes/<before>', false)) {
            if (!$this->_resolve_uri(Cfg::get('system/routes/<before>'))) {
                Log::war(
                    'Before is set in config, but can\'t find method: `'.
                    Cfg::get('system/routes/<before>', false).'`.'
                );
            }
        }
    }

    /**
     * Check if there's any action which should be executed
     * after we dispatch.
     * --
     * @return  void
     */
    protected function _after_dispatch()
    {
        # Do we have after?
        if (Cfg::get('system/routes/<after>', false)) {
            if (!$this->_resolve_uri(Cfg::get('system/routes/<after>'))) {
                Log::war(
                    'After is set in config, but can\'t find method: `'.
                    Cfg::get('system/routes/<after>', false).'`.'
                );
            }
        }
    }

    /**
     * Will check current URI
     * --
     * @return  boolean
     */
    protected function _find_uri()
    {
        # In case we have no uri
        if (empty($this->request_uri)) {
            if (Cfg::get('system/routes/<index>')) 
                { return $this->_resolve_uri(Cfg::get('system/routes/<index>')); }
            else
                { return false; }
        }

        # Loop to check for uri
        $routes = Cfg::get('system/routes');

        # Unser all system routes
        unset(
            $routes['<index>'], 
            $routes['<404>'], 
            $routes['<before>'], 
            $routes['<after>']
        );

        foreach($routes as $route_regex => $route_call)
        {
            # Set matched to empty
            $matched = null;

            # Resolve route regular expression
            $route_regex = $this->_resolve_route($route_regex);

            # If route match our current url, then we'll dispatch it
            if (preg_match_all($route_regex, $this->request_uri, $matched, PREG_SET_ORDER)) {
                $patterns = $matched[0];
                unset($patterns[0]);

                # Call route...
                return $this->_resolve_uri($route_call, $patterns);
            }
        }
    }

    /**
     * Resolve the route, if it's not the regular expression format yet,
     * convert it now.
     * --
     * @param   string  $route
     * @return  string
     */
    protected function _resolve_route($route)
    {
        # It means we're having regular expression already
        if (substr($route, 0, 1) === '/') { return $route; }

        # Split route to pieces
        $route = explode('/', $route);

        # Loop through
        $optional = false; // Which particle is optional?

        foreach ($route as $i => $route_segment) {
            # Set the particle to be optional
            if (substr($route_segment, 0, 1) === '?') {
                if ($optional !== false) {
                    # It seems we already set one particle to be optional, 
                    # so all that follows should be too...
                    Log::war(
                        "Segment `{$optional}` was already set to be optional.\n".
                        "All segments following that one will be also optional.\n".
                        "Setting to optional another (latter) segment `{$i}` is unnecessary." );
                }
                else { $optional = $i; }

                $route_segment = substr($route_segment, 1);
            }

            # Check if we have simple copy pattern
            if (preg_match('/^\<([1-9])\>$/', $route_segment, $match)) {
                $k = (int) $match[1] - 1;
                if ($k >= $i) {
                    trigger_error(
                        "Referencing route particle which wasn't set yet: ".
                        "`{$k}` from `{$i}`.", E_USER_ERROR);
                }
                $route[$i] = $route[$k];
                continue;
            }

            # Match all our home-cooked patterns :)
            $route_segment = preg_replace_callback(
                                '/\<(.*?)\>/', 
                                array($this, '_resolve_route_helper'), 
                                $route_segment);

            $route[$i] = $route_segment;
        }

        $final_pattern = '/^';
        foreach ($route as $i => $route_segment) 
        {
            if ($optional !== false && $optional <= $i) {
                $final_pattern .= '(?:';
            }
            
            if ($i > 0) {
                $final_pattern .= '\/';
            }
            
            $final_pattern .= '(' . $route_segment . ')';

            if ($optional !== false && $optional <= $i) {
                $final_pattern .= ')?';
            }
        }

        $final_pattern .= '$/';

        return $final_pattern;
    }

    /**
     * Help resolve tags in route <az> etc..
     * --
     * @param   array   $match
     * @return  string
     */
    protected function _resolve_route_helper($match)
    {
        $match = $match[1];

        # If we have [] then just return it
        if (substr($match, 0, 1) === '[') { return $match; }

        # If we have *
        if ($match === '*') {
            $match = Cfg::get('system/route_all_tag');
        }
        else {
            # Escape it
            $match = preg_quote($match);

            # First resolve all small all bit letters
            $match = str_replace('aZ', 'a-zA-Z', $match);

            # Resolve numeric ranges
            $match = preg_replace('/(([0-9])([0-9]))/', '$2-$3', $match);
            # Small letters
            $match = preg_replace('/(([a-z])([a-z]))/', '$2-$3', $match);
            # Big letters
            $match = preg_replace('/(([A-Z])([A-Z]))/', '$2-$3', $match);
        }

        return '['.$match.']*';
    }

    /**
     * Will resolve particular route (URI)
     * --
     * @param   string  $route
     * @param   array   $uri_capture
     * @return  boolean
     */
    protected function _resolve_uri($route, $uri_capture=array())
    {
        # _POST + URI segments
        if (!is_array($uri_capture)) { $uri_capture = array(); }
        if (!is_array($_POST))       { $_POST       = array(); }
        $variables = Arr::merge($uri_capture, $_POST);

        Log::inf(
            "Route: {$route}" .
            (empty($variables) 
                ? null 
                : ', variables, '.print_r($variables, true))
        );

        # Get controller
        $route_helper = Str::explode_trim('->', $route, 2);
        $controller   = $route_helper[0];
        if (in_array(substr($controller, 0, 1), array(':', '%'))) {
            $controller = $this->_resolve_params($controller, $variables);
            $controller = $controller[0];
        }

        # Get method
        $route_helper = Str::explode_trim('(', $route_helper[1], 2);
        $method       = $route_helper[0];
        if (in_array(substr($method, 0, 1), array(':', '%'))) {
            $method = $this->_resolve_params($method, $variables);
            $method = $method[0];
        }

        # Get parameters
        $parameters = substr($route_helper[1], 0, -1);
        $parameters = Str::tokenize($parameters, ',', '"');

        # Set parameters
        if (!empty($parameters)) {
            $parameters = $this->_resolve_params($parameters, $variables);
        }

        # Dispatch now!
        return $this->_dispatch($controller, $method, $parameters);
    }

    /**
     * Will resolve route parameters
     * --
     * @param   mixed   $parameters string | array
     * @param   array   $variables
     * --
     * @return  array
     */
    protected function _resolve_params($parameters, $variables)
    {
        # Set empty params-values
        $params_values = array();

        # If not array
        if (!is_array($parameters)) {
            $parameters = array($parameters);
        }

        foreach ($parameters as $param) {
            # Check if we need to convert it
            $convert = false;
            if (substr($param, 1, 4) === 'str ') { $convert = 'string'; }
            if (substr($param, 1, 4) === 'int ') { $convert = 'integer'; }
            if (substr($param, 1, 5) === 'bool ') { $convert = 'boolean'; }
            if (substr($param, 1, 6) === 'float ') { $convert = 'float'; }

            # Clear convert prefix now
            if ($convert) {
                $param = explode(' ', $param, 2);
                $param = substr($param[0], 0, 1) . $param[1];
            }

            # Check if we have default
            if (strpos($param, '|') !== false) {
                $param = explode('|', $param, 2);
                $default = trim($param[1]);
                $param = trim($param[0]);
                $is_default_set = true;

                if (substr($default, 0, 1) === '"') {
                    # Default is string
                    $default = trim($default, '"');
                }
                elseif (in_array(strtolower($default), array('true', 'false'))) {
                    # Default is boolean
                    $default = strtolower($default) === 'true' ? true : false;
                }
                elseif (strpos($default, '.')) {
                    # Default is float
                    $default = (float) $default;
                }
                else {
                    # Default is integer
                    $default = (int) $default;
                }
            }
            else {
                $is_default_set = false;
            }

            # Check if we need date from _POST or URI
            if (substr($param, 0, 1) === '%') {
                $param = (int) substr($param, 1);
            }
            else {
                $param = substr($param, 1);
            }

            # Get actual key
            $current_val = false;
            if (isset($variables[$param])) {
                $current_val = $variables[$param];
            }
            else {
                if ($is_default_set) {
                    $current_val = $default;
                }
                else {
                    continue;
                }
            }

            # Do we need to convert type?
            if ($convert) {
                switch ($convert) {
                    case 'string':
                        $current_val = (string) $current_val;
                        break;
                    case 'integer':
                        $current_val = (int) $current_val;
                        break;
                    case 'boolean':
                        $current_val = Bool::parse($current_val);
                        break;
                    case 'float':
                        $current_val = (float) $current_val;
                        break;
                }
            }

            $params_values[] = $current_val;
        }

        # Return params-values
        return $params_values;
    }

    /**
     * Call appropriate controller
     * --
     * @param   string  $controller Class name
     * @param   string  $method     Method's name
     * @param   array   $params     Those will be send to the destination
     * --
     * @return  boolean
     */
    protected function _dispatch($controller, $method, $params=array())
    {
        Log::inf(
            "Dispatch: {$controller}->{$method}()" .
            (empty($params) 
                ? null 
                : ', params, '.print_r($params, true))
        );

        # Call user func needs array as params
        if (!$params)
            { $params = array(); }

        # Get object
        $controller = $this->_get_controller($controller.'Controller');

        if (!$controller) {
            return false;
        }

        # Call the function if exists
        if (is_callable(array($controller, $method))) {
            $r = call_user_func_array(array($controller, $method), $params);
            if (Cfg::get('system/dispatcher_check_response')) {
                return $r === false ? false : true;
            }
            else {
                return true;
            }
        }
        else {
            return false;
        }
    }

    /**
     * Get appropriate controller
     * --
     * @param   string  $class_name
     * @return  object  or false
     */
    protected function _get_controller($class_name)
    {
        if (!$this->controllers[$class_name]) {
            if (!class_exists($class_name, false)) {
                if (!Loader::get_controller($class_name)) {
                    $this->controllers[$class_name] = false;
                }
            }
            
            $this->controllers[$class_name] = new $class_name();
        }
        
        return $this->controllers[$class_name];
    }

    /**
     * Check if application is off-line
     * --
     * @return  void
     */
    protected function _is_offline()
    {
        return Cfg::get('system/offline');
    }

    /**
     * Set offline message, and send out apropriate response.
     * --
     * @return void
     */
    public function trigger_offline()
    {
        $message = Cfg::get('system/offline_message');
        if (substr($message,0,5) === 'view:') {
            $message = View::get(substr($message, 5))->do_return();
        }
        Http::status_503_service_unavailable($message);
    }

    /**
     * Trigger 404 error
     * --
     * @return  void
     */
    protected function trigger_404()
    {
        Http::status_404_not_found();
        Log::inf("We have 404 on `{$this->request_uri}`.");

        if (Cfg::get('system/routes/<404>')) {
            if ($this->_resolve_uri(Cfg::get('system/routes/<404>'))) 
                { return true; }
        }

        exit('404: ' . Cfg::get('system/routes/<404>'));
    }
}
