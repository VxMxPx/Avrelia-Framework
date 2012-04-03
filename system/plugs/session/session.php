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
 * ---
 * @property	array					$Config			All Plug's Config
 * @property	interfaceSessionDriver	$Driver			Session driver instance
 * @property	string					$driverClass	Driver's class name
 */
class cSession
{
	private static $Config;
	private static $Driver;
	private static $driverClass;

	/**
	 * Cload config and apropriate driver
	 * --
	 * @return	boolean
	 */
	public static function _DoInit()
	{
		self::LoadDriver();
		$class = self::$driverClass;

		# Create new driver instance
		self::$Driver = new $class(self::$Config);
		return true;
	}
	//-

	/**
	 * Enable plug
	 * --
	 * @return	boolean
	 */
	public static function _DoEnable()
	{
		self::LoadDriver();
		$class = self::$driverClass;

		if (!$class::_create(self::$Config)) {
			Log::Add('ERR', "Failed to enable session driver: `{$class}`.", __LINE__, __FILE__);
			return false;
		}
		else {
			return true;
		}
	}
	//-

	/**
	 * Disable plug
	 * --
	 * @return	boolean
	 */
	public static function _DoDisable()
	{
		self::LoadDriver();
		$class = self::$driverClass;

		if (!$class::_destroy(self::$Config)) {
			Log::Add('ERR', "Failed to disable session driver: `{$class}`.", __LINE__, __FILE__);
			return false;
		}
		else {
			return true;
		}
	}
	//-

	/**
	 * Will load driver
	 * --
	 * @return	boolean
	 */
	private static function LoadDriver()
	{
		if (self::$Driver) {
			return true;
		}

		# Get Config
		$Config = Plug::GetConfig(__FILE__);

		# Get driver if exists
		$path   = dirname(__FILE__);
		$driver = $Config['driver'];
		$class  = $driver.'SessionDriver';

		# Load interface first
		if (!interface_exists('interfaceSessionDriver', false)) {
			if (file_exists(ds($path."/drivers/interface_session_driver.php"))) {
				include(ds($path."/drivers/interface_session_driver.php"));
			}
			else {
				Log::Add('WAR', "Can't load interface driver: " . ds($path."/drivers/interface_session_driver.php"), __LINE__, __FILE__);
				return false;
			}
		}

		if (!class_exists($class, false)) {
			if (file_exists(ds($path . "/drivers/{$driver}_session_driver.php"))) {
				include(ds($path . "/drivers/{$driver}_session_driver.php"));
			}
		}

		# Do we have clas now?
		if (!class_exists($class, false)) {
			Log::Add('ERR', "Class can't be loaded: `{$class}`.", __LINE__, __FILE__);
			return false;
		}

		self::$Config      = $Config;
		self::$driverClass = $class;
		return true;
	}
	//-

	/**
	 * Login the user
	 * --
	 * @param	string	$username
	 * @param	string	$password
	 * @param	boolean	$rememberMe	If set to false, session will expire when user
	 * 								close browser's window.
	 * --
	 * @return	boolean
	 */
	public static function Login($username, $password, $rememberMe=true)
	{
		return self::$Driver ? self::$Driver->login($username, $password, $rememberMe) : false;
	}
	//-

	/**
	 * Logout the user
	 * --
	 * @return	void
	 */
	public static function Logout()
	{
		return self::$Driver ? self::$Driver->logout() : false;
	}
	//-

	/**
	 * Is User logged in?
	 * --
	 * @return	boolean
	 */
	public static function IsLoggedin()
	{
		return self::$Driver ? self::$Driver->isLoggedin() : false;
	}
	//-

	/**
	 * Return user's information as an array. If key provided, then only particular
	 * info can be returned. For example $key = uname
	 * --
	 * @param	string	$key
	 * --
	 * @return	mixed
	 */
	public static function AsArray($key=false)
	{
		return self::$Driver ? self::$Driver->asArray($key) : false;
	}
	//-
}
//--
