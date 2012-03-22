<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Event Handler
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2009, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      Date 2010-04-06
 */


class Event
{
	# The waiting room. :)
	private static $Waiting = array();

	/**
	 * Wait for paticular event to happened - then call the assigned function / method.
	 * ---
	 * @param string  $event    -- name of the event you're waiting for
	 * @param mixed   $call     -- can be name of the function, or array('className', 'methodName')
	 * @param bool    $inFront  -- should be event added to the front of the list?
	 * ---
	 * @return void
	 */
	public static function Watch($event, $call, $inFront=false)
	{
		if (!isset(self::$Waiting[$event]) || !is_array(self::$Waiting[$event])) {
			self::$Waiting[$event] = array();
		}

		if ($inFront) {
			array_unshift(self::$Waiting[$event], $call);
		}
		else {
			self::$Waiting[$event][] = $call;
		}
	}
	//-

	/**
	 * Trigger the event.
	 * ---
	 * @param string $event  -- which event?
	 * @param mixed  $Params -- shall we provide any params?
	 * ---
	 * @return integer (number of called functions - function count only if "true" was returned)
	 */
	public static function Trigger($event, &$Params=null)
	{
		$num = 0;

		if (isset(self::$Waiting[$event]) && is_array(self::$Waiting[$event]) && !empty(self::$Waiting[$event])) {
			foreach (self::$Waiting[$event] as $call) {
				# $num = $num + (call_user_func($call, &$Params) ? 1 : 0);
				$num = $num + (call_user_func_array($call, array(&$Params)) ? 1 : 0);
			}
		}

		return $num;
	}
	//-

}
//--
