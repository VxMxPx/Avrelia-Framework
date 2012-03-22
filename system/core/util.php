<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Get util
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2011-12-20
 */

class Util
{
	# List of loaded utils
	private static $Loaded = array();

	/**
	 * Will load util file
	 *
	 * @param string $name
	 *
	 * @return void
	 */
	public static function Get($name)
	{
		if (in_array($name, self::$Loaded)) {
			return false;
		}

		$utilName = ds(APPPATH.'/util/'.strtolower($name).'.php');
		if (file_exists($utilName)) {
			include($utilName);
			self::$Loaded[] = $name;
		}
		else {
			Log::Add('ERR', "Can't load util: `{$utilName}`, file not found.", __LINE__, __FILE__);
		}
	}
	//-
}
//--
