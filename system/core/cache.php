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
	 * Will set cache (store content into cache file)
	 * ---
	 * @param string $contents
	 * @param string $key
	 * @param integer $expires -- Time when chache expires, in minutes.
	 * 					If set to false, then cache won't expire at all.
	 * 					It can be refreshed if we set it again.
	 * ---
	 * @return bool
	 */
	public static function Set($contents, $key, $expires=false)
	{
		if ($expires !== false) {
			$prefix = (time() + ($expires * 60)) . '||';
		}
		else {
			$prefix = 'infinite||';
		}
		return FileSystem::Write($prefix.$contents, self::FileFromKey($key), false, 0777);
	}
	//-

	/**
	 * Will get cache, and return false if can't find it.
	 * ---
	 * @param string $key
	 * ---
	 * @return mixed
	 */
	public static function Get($key)
	{
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

		# We need false if there's no cache!
		return false;
	}
	//-

	/**
	 * Will create full cache filename from key.
	 * Retutn string if successfull, and false if not.
	 * ---
	 * @param string $key
	 * ---
	 * @return mixed
	 */
	private static function FileFromKey($key)
	{
		$key = vString::Clean($key, 200, 'a A 1 c', '_-');
		return ds(Cfg::Get('system/cache_dir', APPPATH.'/database/cache').'/'.$key.'.cache');
	}
	//-
}
//--
