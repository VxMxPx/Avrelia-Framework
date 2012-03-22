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
	# All Configurations
	private static $Config = array();

	/**
	 * Append some config
	 * ---
	 * @param array $Config
	 * ---
	 * @return void
	 */
	public static function Append($Config)
	{
		self::$Config = array_merge_recursive(self::$Config, $Config);
	}
	//-

	/**
	 * Will load and append config file
	 * --
	 * @param string $filename -- fullpath or only name of file
	 * --
	 * @return bool
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
	 * ---
	 * @return void
	 */
	public static function Debug()
	{
		return dumpVar(self::$Config, false, true);
	}
	//-

	/**
	 * Get Config Item
	 *
	 * @param string $path   - in format: key/subkey
	 * @param mixed $default - default value, if config isn't set
	 *
	 * @return mixed
	 */
	public static function Get($path, $default=null)
	{
		return vArray::GetByPath($path, self::$Config, $default);
	}
	//-

}
//--
