<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Cache Interface
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2012, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-06-28
 */
class cCacheDriverInterface
{

	/**
	 * Called when plug is enabled.
	 * --
	 * @return	boolean
	 */
	function _create();
	//-

	/**
	 * Called when plug is disabled.
	 * --
	 * @return	boolean
	 */
	function _destroy();
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
	function set($contents, $key, $expires=false);
	//-

	/**
	 * Will get cache or return false if can't find it.
	 * --
	 * @param	string	$key
	 * --
	 * @return	mixed
	 */
	function get($key);
	//-

	/**
	 * Check if particular key exists.
	 * --
	 * @param	string	$key
	 * --
	 * @return	boolean
	 */
	function has($key);
	//-

	/**
	 * Clear particular cache, or all cache (if key is false)
	 * --
	 * @param	mixed	$key	String key name or false to clear all cache
	 * --
	 * @return	boolean
	 */
	function clear($key=false);
	//-
}
//--