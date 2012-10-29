<?php namespace Avrelia\Core; if (!defined('AVRELIA')) die('Access is denied!');

/**
 * Dispatcher Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Dispatcher
{
    # Controllers cache
    protected static $controllers;

    /**
     * Execute particular function, path. This will return the result of executed
     * function or false if function can't be found / executed.
     * --
     * @param  mixed $action   Action to execute - string or function
     * @param  array $segments Any segments which we'd like to pass on to the
     *                         function we're executing.
     * --
     * @return boolean
     */
    public static function execute($action, $segments=false)
    {
        if (is_string($action)) {
            return self::_execute_action($action, $segments);
        }
        else if (is_a($action, 'Closure')) {
            return $action();
        }
        else {
            return false;
        }
    }


    /**
     * Will resolve particular route (URI)
     * --
     * @param   string  $action
     * @param   array   $segments
     * @return  boolean
     */
    protected static function _execute_action($action, $segments=array())
    {
        # URI segments
        $variables = !is_array($segments) ? array($segments) : $segments;

        Log::inf(
            "Dispatching: {$action}" .
            (empty($variables) 
                ? null 
                : ', variables, ' . print_r($variables, true))
        );

        # Get controller
        $action_helper = Str::explode_trim('->', $action, 2);
        $controller    = self::_assign_variables($action_helper[0], $segments);

        # Get method
        $action_helper = Str::explode_trim('(', $action_helper[1], 2);
        $method        = self::_assign_variables($action_helper[0], $segments);

        # Get parameters
        if (isset($action_helper[1])) {
            $parameters = substr($action_helper[1], 0, -1);
            $parameters = Str::tokenize($parameters, ',', '"');
            $params_new = array();

            foreach ($parameters as $param_id) {
                $param_id = (int) trim(substr($param_id, 1)) - 1;
                if (isset($variables[$param_id])) {
                    $params_new[] = $variables[$param_id];
                }
            }

            $parameters = $params_new;
        }
        else {
            $parameters = array();
        }

        # Dispatch now!
        return self::_dispatch($controller, $method, $parameters);
    }

    /**
     * Will check if we have anything like post_:2, and try to resolve it.
     * --
     * @param  string $string
     * @param  array $variables
     * --
     * @return string
     */
    protected static function _assign_variables($string, $variables)
    {
        if (is_array($variables)) 
        {
            foreach ($variables as $key => $value) {
                $string = str_replace(':'.$key, $value, $string);
            }
        }

        return $string;
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
    protected static function _dispatch($controller, $method, $params=array())
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
        $controller = self::_get_controller(ucfirst($controller).'_Controller');

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
    protected static function _get_controller($class_name)
    {
        if (!isset(self::$controllers[$class_name]) || !self::$controllers[$class_name]) {
            if (!class_exists($class_name, false)) {
                if (!Loader::get_controller($class_name)) {
                    self::$controllers[$class_name] = false;
                }
            }
            
            self::$controllers[$class_name] = new $class_name();
        }
        
        return self::$controllers[$class_name];
    }
}
