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
	/**
	 * @var	array	Loaded models cache
	 */
	protected static $Cache = array();

	/**
	 * Load Application's Model,
	 * --
	 * @param	string	$name
	 * @param	boolean	$newInstance	If set to true, this will create new instance of class, even if it exists in cache
	 * --
	 * @return	mixed	Objct or false
	 */
	public static function Get($name, $newInstance=false)
	{
		$class = $name . 'Model';

		if (!$newInstance && isset(self::$Cache[$class]))
		{
			return self::$Cache[$class];
		}

		$instance = self::NewInstance($class);

		if (!$instance) {
			Loader::GetMC($class, 'models');
			$instance = self::NewInstance($class);

			if (!$instance) {
				trigger_error("Can't load application's model: `{$class}`.", E_USER_ERROR);
			}
		}

		return $instance;
	}
	//-

	/**
	 * Create new instance of a class, if exists
	 * --
	 * @param	string	$class
	 * --
	 * @return	mixed	object or false
	 */
	private static function NewInstance($class)
	{
		if (class_exists($class, false))
		{
			$instance = new $class();
			self::$Cache[$class] = $instance;
			return $instance;
		}
		else {
			return false;
		}
	}
	//-
}
//--