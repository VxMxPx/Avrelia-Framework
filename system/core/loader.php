<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Loader Class
 * Will load any application's model, controller, plug's class, etc...
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-07-10
 */

class Loader
{
	/**
	 * Load class by filename
	 * --
	 * @param	string	$className
	 * --
	 * @return	boolean
	 */
	public static function Get($className)
	{
		# Try to understand what kind of a class do we have...
		if (substr($className,  -10) === 'Controller') return self::GetMC($className, 'controllers');
		if (substr($className,   -5) === 'Model')      return self::GetMC($className, 'models');
		if (substr($className, 0, 1) === 'u')          return self::GetUtil($className);
		if (substr($className, 0, 1) === 'c')          return self::GetPlug($className);

		# Nothing of above rules?
		trigger_error("Autoload failed for: `{$className}`.", E_USER_ERROR);
	}
	//-

	/**
	 * Will load plug's class
	 * --
	 * @param	string	$className
	 * --
	 * @return	boolean
	 */
	public static function GetPlug($className)
	{
		$fileName = toUnderline(substr($className, 1));
		return Plug::Inc($fileName);
	}
	//-

	/**
	 * Will load utils class
	 * --
	 * @param	string	$className
	 * --
	 * @return	boolean
	 */
	public static function GetUtil($className)
	{
		$path = 'util';
		$fileName = toUnderline(substr($className, 1));

		# Check APPLICATION folder...
		if (file_exists(ds(APPPATH."/{$path}/{$fileName}.php"))) {
			include ds(APPPATH."/{$path}/{$fileName}.php");
			return true;
		}

		# Check SYSTEM folder...
		if (file_exists(ds(SYSPATH."/{$path}/{$fileName}.php"))) {
			include ds(SYSPATH."/{$path}/{$fileName}.php");
			return true;
		}

	    trigger_error("Autoload failed for: `{$className}`, class not found: `{$fileName}`, prefix `{$classPrefix}`.", E_USER_ERROR);
	}
	//-

	/**
	 * Will load application's model or controllers
	 * --
	 * @param	string	$className
	 * --
	 * @return	boolean
	 */
	public static function GetMC($className, $type)
	{
		if (!in_array($type, array('controllers', 'models'))) {
			trigger_error("Type must be either `controllers` or `models`.", E_USER_ERROR);
		}

		$name = substr($className, 0, -(strlen($type)-1));
		$name = strtolower(toUnderline($name));
		$nameSplit = explode('_', $name, 2);

		# Some possibilities
		$files = array();
		$files[] = ds(APPPATH.'/'.$type.'/'.$name.'.php');
		if (isset($nameSplit[1])) {
			$files[] = ds(APPPATH.'/'.$type.'/'.$nameSplit[0].'/'.$name.'.php');
			$files[] = ds(APPPATH.'/'.$type.'/'.$nameSplit[0].'/'.$nameSplit[1].'.php');
		}
		else {
			$files[] = ds(APPPATH.'/'.$type.'/'.$nameSplit[0].'/'.$nameSplit[0].'.php');
		}

		foreach ($files as $file) {
			if (file_exists($file)) {
				include $file;
				return true;
			}
		}

		trigger_error("Can't load class `{$className}`, tried:" . print_r($files, true), E_USER_ERROR);
	}
	//-
}
//--
