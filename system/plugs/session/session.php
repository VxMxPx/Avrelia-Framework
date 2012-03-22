<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Session Component
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-01-19
 */

class cSession
{
	# Session driver
	private static $Driver;

	/**
	 * Cload config and apropriate driver
	 * ---
	 * @return true
	 */
	public static function _doInit()
	{
		# Get Config
		$Config = Plug::GetConfig(__FILE__);

		# Get driver if exists
		$path   = dirname(__FILE__);
		$driver = $Config['driver'];
		$class  = $driver.'SessionDriver';

		if (!class_exists($class, false)) {
			if (file_exists(ds($path . "/libraries/{$driver}_session_driver.php"))) {
				include(ds($path . "/libraries/{$driver}_session_driver.php"));
			}
		}

		# Do we have clas now?
		if (!class_exists($class, false)) {
			Log::Add('ERR', "Class can't be loaded: `{$class}`.", __LINE__, __FILE__);
			return false;
		}

		# Create new driver instance
		if (!$class::_canConstruct($Config)) {
			if (!$class::_doEnable($Config)) {
				Log::Add('ERR', "Failed to enable session driver: `{$class}`.", __LINE__, __FILE__);
				return false;
			}
		}

		self::$Driver = new $class($Config);

		return true;
	}
	//-

	/**
	 * Get Session's Driver
	 * ---
	 * @param string $info -- if info provided, instead of object, you'll get
	 * returned string, with user's info, and false if user isn't loggedin!
	 * Use: 'email' or 'access/main'
	 * ---
	 * @return jsonSessionDriver
	 */
	public static function Get($info=false)
	{
		if ($info) {
			return self::Get()->userGetInfo($info);
		}
		else {
			return self::$Driver;
		}
	}
	//-

	/**
	 * Is User logged in?
	 * ---
	 * @return bool
	 */
	public static function IsLoggedin()
	{
		return self::Get() ? self::Get()->isLoggedin() : false;
	}
	//-
}
//--
