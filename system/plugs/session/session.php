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
	private static $Config;			# array						All Plug's Config
	private static $Driver;			# cSessionDriverInterface	Session driver instance
	private static $driverClass;	# string					Driver's class name


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
		$class  = 'cSessionDriver'.ucfirst($driver);

		# Load interface first
		if (!interface_exists('cSessionDriverInterface', false)) {
			if (file_exists(ds($path."/drivers/interface.php"))) {
				include(ds($path."/drivers/interface.php"));
			}
			else {
				Log::Add('WAR', "Can't load interface driver: " . ds($path."/drivers/interface.php"), __LINE__, __FILE__);
				return false;
			}
		}

		if (!class_exists($class, false)) {
			if (file_exists(ds($path . "/drivers/{$driver}.php"))) {
				include(ds($path . "/drivers/{$driver}.php"));
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
	 * Will log-in user based on id.
	 * --
	 * @param	mixed	$id
	 * @param	boolean	$rememberMe	If set to false, session will expire when user
	 * 								close browser's window.
	 * --
	 * @return	boolean
	 */
	public static function LoginId($id, $rememberMe=true)
	{
		return self::$Driver ? self::$Driver->loginId($id, $rememberMe) : false;
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
	 * Will reload current user's informations; Useful after you're doing an update.
	 * --
	 * @return	void
	 */
	public static function Reload()
	{
		if (self::IsLoggedin()) {
			self::$Driver->reload();
		}
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
