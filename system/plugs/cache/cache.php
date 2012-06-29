<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Cache Plug
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2012, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-06-29
 */
class cCache
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
		self::$Driver = $class = Plug::GetDriver(__FILE__, Cfg::Get('plugs/session/driver'));

		# Do we have driver?
		return self::$Driver ? true : false;
	}
	//-

	/**
	 * Enable plug
	 * --
	 * @return	boolean
	 */
	public static function _OnEnable()
	{
		self::_OnInit();
		self::$Driver->_create();
	}
	//-

	/**
	 * Disable plug
	 * --
	 * @return	boolean
	 */
	public static function _OnDisable()
	{
		self::_OnInit();
		self::$Driver->_destroy();
	}
	//-


	/**
	 * Will set cache (store content into cache file)
	 * --
	 * @param	string	$contents
	 * @param	string	$key
	 * @param	integer	$expires	Time when chache expires, in seconds.
	 * 								If set to false, then cache won't expire at all.
	 * 								It can be refreshed if we set it again.
	 * --
	 * @return	boolean
	 */
	public static function Set($contents, $key, $expires=false)
	{
		return self::$Driver->set($contents, $key, $expires);
	}
	//-

	/**
	 * Will get cache or return false if can't find it.
	 * --
	 * @param	string	$key
	 * --
	 * @return	mixed
	 */
	public static function Get($key)
	{
		return self::$Driver->get($key);
	}
	//-

	/**
	 * Check if particular key exists.
	 * --
	 * @param	string	$key
	 * --
	 * @return	boolean
	 */
	public static function Has($key)
	{
		return self::$Driver->has($key);
	}
	//-

	/**
	 * Clear particular cache, or all cache (if key is false)
	 * --
	 * @param	mixed	$key	String key name or false to clear all cache
	 * --
	 * @return	boolean
	 */
	public static function Clear($key=false)
	{
		return self::$Driver->clear($key);
	}
	//-
}
//--
