<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Cache Driver: APC
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2012, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-06-28
 */
class cCacheDriverApc implements cCacheDriverInterface
{
	/**
	 * @var	string	APC prefix
	 */
	private $prefix;


	public function __construct()
	{
		Log::Add("Will use `apc` cache driver.");

		if (function_exists('apc_add')) {
			$this->prefix = Cfg::Get('plugs/cache/apc_prefix', 'avrelia_framework_');
		}
		else {
			Log::Add("In PHP `apc` must be enabled.", 'WAR');
		}
	}
	//-

	/**
	 * Called when plug is enabled.
	 * --
	 * @return	boolean
	 */
	public function _create() { return true; }
	//-

	/**
	 * Called when plug is disabled.
	 * --
	 * @return	boolean
	 */
	public function _destroy() { return true; }
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
	public function set($contents, $key, $expires=false)
	{
		# Convert to seconds
		$expires = $expires !== false ? $expires : 0;

		Log::Add("Set cache: `{$key}` to expires in `{$expires}` seconds.", 'INF');

		return apc_add($this->prefix.$key, $contents, $expires);
	}
	//-

	/**
	 * Will get cache or return false if can't find it.
	 * --
	 * @param	string	$key
	 * --
	 * @return	mixed
	 */
	public function get($key)
	{
		return apc_fetch($this->prefix.$key);
	}
	//-

	/**
	 * Check if particular key exists.
	 * --
	 * @param	string	$key
	 * --
	 * @return	boolean
	 */
	public function has($key)
	{
		return apc_exists($this->prefix.$key);
	}
	//-

	/**
	 * Clear particular cache, or all cache (if key is false)
	 * --
	 * @param	mixed	$key	String key name or false to clear all cache
	 * --
	 * @return	boolean
	 */
	public function clear($key=false)
	{
		if ($key && !$this->has($key)) {
			Log::Add("The cache key you're trying to remove doesn't exists anymore: `{$key}`.", 'INF');
			return false;
		}

		if (!$key) {
			return apc_clear_cache();
		}
		else {
			return apc_delete($key);
		}
	}
	//-
}
//--