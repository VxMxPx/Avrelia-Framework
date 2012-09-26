<?php namespace Avrelia\Core; if (!defined('AVRELIA')) die('Access is denied!');

/**
 * Event Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Event
{
    # List of events to be executed
    protected static $waiting = array();

    /**
     * Wait for paticular event to happened - then call the assigned function / method.
     * --
     * @param   string  $event      Name of the event you're waiting for
     * @param   mixed   $call       Can be name of the function, or array('className', 'methodName')
     * @param   boolean $in_front   Should be event added to the front of the list?
     * @return  void
     */
    public static function watch($event, $call, $in_front=false)
    {
        if (!isset(self::$waiting[$event]) || !is_array(self::$waiting[$event])) {
            self::$waiting[$event] = array();
        }

        if ($in_front) {
            array_unshift(self::$waiting[$event], $call);
        }
        else {
            self::$waiting[$event][] = $call;
        }
    }

    public static function inf($event, $message)
        { return self::_log('inf', $event, $message); }
    public static function err($event, $message)
        { return self::_log('err', $event, $message); }
    public static function war($event, $message)
        { return self::_log('war', $event, $message); }

    /**
     * Trigger event with some message and log it.
     * --
     * @param  string  $type    inf|err|war
     * @param  string  $event   Event name
     * @param  string  $message Event message
     * --
     * @return integer Number of called functions.
     *                 Function count only if "true" was returned.
     */
    private static function _log($type, $event, $message)
    {
        Log::add($message, $type);
        return self::trigger($event, $message);
    }

    /**
     * Trigger the event.
     * --
     * @param   string  $event  Which event?
     * @param   mixed   $params Shall we provide any params?
     * @return  integer Number of called functions.
     *                  Function count only if "true" was returned.
     */
    public static function trigger($event, &$params=null)
    {
        $num = 0;

        if (
            isset(self::$waiting[$event]) && 
            is_array(self::$waiting[$event]) && 
            !empty(self::$waiting[$event])
        ) {
            foreach (self::$waiting[$event] as $call) {
                $num = $num + (call_user_func_array($call, array(&$params)) ? 1 : 0);
            }
        }

        return $num;
    }
}
