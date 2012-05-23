<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Config Handler
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2011-04-01
 */
class Cfg
{
	/**
	 * @var	array	All configurations.
	 */
	private static $Config = array();

	/**
	 * @var	array	Cached values
	 */
	private static $Cache = array();


	/**
	 * Append some config
	 * --
	 * @param	array	$Config
	 * --
	 * @return	void
	 */
	public static function Append($Config)
	{
		# First we'll clear all cached values
		self::$Cache = array();

		# Doesn't work, change 404 to 1 and on duplicates create new array
		//self::$Config = array_merge_recursive(self::$Config, $Config);

		# Doesn't work, merge failed
		//self::$Config = array_merge(self::$Config, $Config);

		# Works perfectly
		self::$Config = vArray::Merge(self::$Config, $Config);
	}
	//-

	/**
	 * Will load and append config file
	 * --
	 * @param	string	$filename	Fullpath or only name of file
	 * --
	 * @return	boolean
	 */
	public static function Load($filename)
	{
		# Check if is full path
		if (substr($filename,-4,4) != '.php') {
			$filename = ds(APPPATH."/config/{$filename}.php");
		}

		# In case of wrong filename!
		if (!file_exists($filename)) {
			trigger_error("File not found: `{$filename}`.", E_USER_ERROR);
			return false;
		}

		include($filename);

		if (!isset($AvreliaConfig))
		{
			trigger_error("File was loaded {$filename}, but \$AvreliaConfig isn't set!", E_USER_WARNING);
			return false;
		}

		# Try to include local too
		$localFile = substr($filename,0,-4) . '.local.php';
		if (file_exists($localFile)) {
			include($localFile);
		}

		self::Append($AvreliaConfig);
		return true;
	}
	//-

	/**
	 * Output all config
	 * --
	 * @return	void
	 */
	public static function Debug()
	{
		return
		'Cache ' . dumpVar(self::$Cache, false, true) .
		"\n" .
		'Config ' . dumpVar(self::$Config, false, true);
	}
	//-

	/**
	 * Get Config Item
	 * --
	 * @param	string	$path		In format: key/subkey
	 * @param	mixed	$default	Default value, if config isn't set
	 * --
	 * @return	mixed
	 */
	public static function Get($path, $default=null)
	{
		if (!isset(self::$Cache[$path])) {
			self::$Cache[$path] = vArray::GetByPath($path, self::$Config, $default);
		}

		return self::$Cache[$path];
	}
	//-

	/**
	 * Overwrite particular config key, this is temporary action,
	 * the changes won't get saved.
	 * --
	 * @param	string	$path	In format: key/subkey
	 * @param	mixed	$value
	 * --
	 * @return	void
	 */
	public static function Overwrite($path, $value)
	{
		# Clear cache to avoid conflicts
		self::$Cache = array();

		vArray::SetByPath($path, $value, self::$Config);
	}
	//-

}
//--
