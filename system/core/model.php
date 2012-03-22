<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Model management
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2011-12-20
 */


class Model
{
	# Models storage
	protected static $Cache = array();

	/**
	 * Load Application's Model,
	 * ---
	 * @param string $name
	 * @param bool $newInstance if set to true, this will create new instance of class, even if it exists ...
	 * ---
	 * @return obj || false
	 */
	public static function Get($name, $newInstance=false)
	{
		$class = $name . 'Model';
	
		if (!$newInstance && isset(self::$Cache[$class]))
		{
			return self::$Cache[$class];
		}

		if (class_exists($class, false))
		{
			$instance = new $class();
			self::$Cache[$class] = $instance;
			return $instance;
		}

		$fullname = ds(APPPATH.'/models/'.strtolower($name).'.php');

		if (file_exists($fullname)) {
			include $fullname;
		}
		else {
			trigger_error("Can't load model - file doesn't exits: `{$fullname}`.", E_USER_ERROR);
		}

		if (class_exists($class, false))
		{
			$instance = new $class();
			self::$Cache[$class] = $instance;
			return $instance;
		}
		else {
			trigger_error("Can't load model - class doesn't exits: `{$class}`.", E_USER_ERROR);
		}
	}
	//-
}
//--
