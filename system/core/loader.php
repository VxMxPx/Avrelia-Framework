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
 * @since      2011-11-19
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
		if (substr($className,  -10) === 'Controller') return self::GetController($className);
		if (substr($className,   -5) === 'Model')      return self::GetModel($className);
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
	 * Will load application's controller
	 * --
	 * @param	string	$className
	 * --
	 * @return	boolean
	 */
	public static function GetController($className)
	{
		$name = substr($className, 0, -10);
		$name = toUnderline($name);
		$fullname = ds(APPPATH.'/controllers/'.strtolower($name).'.php');

		if (file_exists($fullname)) {
			include $fullname;
		}
		else {
			trigger_error("Can't load controller - file doesn't exits: `{$fullname}`.", E_USER_ERROR);
		}

		return true;
	}
	//-

	/**
	 * Will load application's model
	 * --
	 * @param	string	$className
	 * --
	 * @return	boolean
	 */
	public static function GetModel($className)
	{
		$name = substr($className, 0, -5);
		$name = toUnderline($name);
		$fullname = ds(APPPATH.'/models/'.strtolower($name).'.php');

		if (file_exists($fullname)) {
			include $fullname;
		}
		else {
			trigger_error("Can't load model - file doesn't exits: `{$fullname}`.", E_USER_ERROR);
		}

		return true;
	}
	//-
}
//--
