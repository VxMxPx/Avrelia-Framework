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
	private static $Driver;			# cSessionDriverInterface	Session driver instance

	/**
	 * Cload config and apropriate driver
	 * --
	 * @return	boolean
	 */
	public static function _OnInit()
	{
		Plug::GetConfig(__FILE__);
		$class = Plug::GetDriver(__FILE__, Cfg::Get('plugs/session/driver'), false);

		# Create new driver instance
		self::$Driver = new $class();
		return true;
	}
	//-

	/**
	 * Enable plug
	 * --
	 * @return	boolean
	 */
	public static function _OnEnable()
	{
		Plug::GetConfig(__FILE__);
		$class = Plug::GetDriver(__FILE__, Cfg::Get('plugs/session/driver'), false);

		if (!$class::_create()) {
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
	public static function _OnDisable()
	{
		Plug::GetConfig(__FILE__);
		$class = Plug::GetDriver(__FILE__, Cfg::Get('plugs/session/driver'), false);

		if (!$class::_destroy()) {
			Log::Add('ERR', "Failed to disable session driver: `{$class}`.", __LINE__, __FILE__);
			return false;
		}
		else {
			return true;
		}
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
