<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Benchmark
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://avrelia.com/license
 * @link       http://avrelia.com
 * @since      Version 0.80
 * @since      Date sob avg 07 18:18:44 2010
 */


class Benchmark
{
	# Has all times stored
	private static $MicroTimes;


	/**
	 * MircoTime start
	 * ---
	 * @param string $name -- we should gave unique name to our timer
	 * ---
	 * @return void
	 */
	public static function SetTimer($name)
	{
		$temp = explode( ' ', microtime() );
		self::$MicroTimes[$name] = $temp[1] + $temp[0];
	}
	//-

	/**
	 * Return the time that was set in "setTimer"
	 *  ------
	 * @param string $name -- name of the timer
	 *  ------
	 * @return string
	 */
	public static function GetTimer($name)
	{
		if (isset(self::$MicroTimes[$name]))
		{
			$start = self::$MicroTimes[$name];
			$temp  = explode(' ', microtime());
			$total = $temp[0] + $temp[1] - $start;
			$total = sprintf('%.3f',  $total);

			return $total;
		}
	}
	//-

	/**
	 * Return memory usage
	 *
	 * @param bool $peak
	 * @param bool $formated
	 *
	 * @return string
	 */
	public static function GetMemoryUsage($peak=true, $formated=true)
	{
		$memory = 0;

		if ($peak && function_exists('memory_get_peak_usage')) {
			$memory = memory_get_peak_usage(true);
		}
		elseif (function_exists('memory_get_usage')) {
			$memory = memory_get_usage(true);
		}

		return ($formated) ? FileSystem::FormatSize($memory) : $memory;
	}
	//-

}
//--
