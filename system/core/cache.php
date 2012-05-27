<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Cache Controller
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-01-12
 */
class Cache
{
	/**
	 * @var	string	Storage driver type apc or file
	 */
	private static $type;

	/**
	 * @var	string	APC prefix
	 */
	private static $prefix;


	/**
	 * Init the cache class
	 * --
	 * @return	void
	 */
	public static function Init()
	{
		if (Cfg::Get('cache/type', 'file') !== 'file') {
			if (function_exists('apc_add')) {
				Log::Add("Will use `apc` cache driver.");
				self::$type = 'apc';
				self::$prefix = Cfg::Get('cache/apc_prefix', 'avrelia_framework_');
				return;
			}
			else {
				Log::Add("In PHP `apc` must be enabled.", 'WAR');
			}
		}

		Log::Add("Will use `file` cache driver.");
		self::$type = 'file';
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
		# Convert to seconds
		$expires = $expires !== false ? $expires : 0;

		Log::Add("Set cache: `{$key}` to expires in `{$expires}` seconds.", 'INF');

		if (self::$type === 'file') {
			$expires = $expires === 0 ? 'infinite' : (time() + $expires);
			return FileSystem::Write($expires.'||'.$contents, self::FileFromKey($key), false, 0777);
		}
		else {
			return apc_add(self::$prefix.$key, $contents, $expires);
		}
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
		if (self::$type === 'file') {
			$filename = self::FileFromKey($key);

			if (file_exists($filename)) {
				$content = FileSystem::Read($filename);
				$Content = explode('||', $content, 2);
				$expire  = $Content[0];
				$content = $Content[1];
				if ((int)$expire > time() || $expire == 'infinite') {
					return $content;
				}
				else {
					# Remove it, since it's expired, and return false!
					FileSystem::Remove($filename);
				}
			}
		}
		else {
			return apc_fetch(self::$prefix.$key);
		}

		# We need false if there's no cache!
		return false;
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
		if (self::$type === 'file') {
			$filename = self::FileFromKey($key);
			return file_exists($filename);
		}
		else {
			return apc_exists(self::$prefix.$key);
		}
	}
	//-

	/**
	 * Will create full cache filename from key.
	 * Retutn string if successfull, and false if not.
	 * --
	 * @param	string	$key
	 * --
	 * @return	mixed
	 */
	private static function FileFromKey($key)
	{
		$key = vString::Clean($key, 200, 'a A 1 c', '_-');
		return ds(Cfg::Get('cache/location', DATPATH.'/cache').'/'.$key.'.cache');
	}
	//-
}
//--
