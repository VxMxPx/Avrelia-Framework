<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Boolean Manipulations
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-03-20
 */

class vBoolean
{
	/**
	 * Will parse string, and convert it to boolean.
	 * This will convert '1', 1, 'yes' and 'true' to true
	 * --
	 * @param	string	$input
	 * --
	 * @return	boolean
	 */
	public static function Parse($input)
	{
		switch (strtolower($input)) {
			case 1:
			case '1':
			case 'yes':
			case 'true':
				return true;

			default:
				return false;
		}
	}
	//-
}
//--
