<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Cache Driver: File
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2012, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-06-28
 */
class cCacheDriverFile implements cCacheDriverInterface
{

	public function __construct()
	{
		Log::Add("Will use `file` cache driver.");
	}
	//-

	/**
	 * Called when plug is enabled.
	 * --
	 * @return	boolean
	 */
	public function _create()
	{
		$directory = ds(Cfg::Get('plugs/cache/location', DATPATH.'/cache'));
		if (!is_dir($directory)) {
			return FileSystem::MakeDir($directory, true, 0777);
		}

		return true;
	}
	//-

	/**
	 * Called when plug is disabled.
	 * --
	 * @return	boolean
	 */
	public function _destroy()
	{
		$directory = ds(Cfg::Get('plugs/cache/location', DATPATH.'/cache'));
		if (!is_dir($directory)) {
			return FileSystem::Remove($directory);
		}

		return true;
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
	public function set($contents, $key, $expires=false)
	{
		# Convert to seconds
		$expires = $expires !== false ? $expires : 0;

		Log::Add("Set cache: `{$key}` to expires in `{$expires}` seconds.", 'INF');

		$expires = $expires === 0 ? 'infinite' : (time() + $expires);
		return FileSystem::Write($expires.'||'.$contents, $this->fileFromKey($key), false, 0777);
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
		$filename = $this->fileFromKey($key);

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
	 * Check if particular key exists.
	 * --
	 * @param	string	$key
	 * --
	 * @return	boolean
	 */
	public function has($key)
	{
		$filename = $this->fileFromKey($key);
		return file_exists($filename);
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
			return (bool) FileSystem::Remove(ds(Cfg::Get('plugs/cache/location', DATPATH.'/cache')), '*.cache');
		}
		else {
			$filename = $this->fileFromKey($key);
			return (bool) FileSystem::Remove($filename);
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
	private function fileFromKey($key)
	{
		$key = vString::Clean($key, 200, 'a A 1 c', '_-');
		return ds(Cfg::Get('cache/location', DATPATH.'/cache').'/'.$key.'.cache');
	}
	//-
}
//--