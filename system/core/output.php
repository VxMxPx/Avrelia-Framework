<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Output
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://avrelia.com/license
 * @link       http://avrelia.com
 * @since      Version 0.80
 * @since      Date ned apr 11 21:00:24 2010
 */


class Output
{
	# Whole output
	private static $Output = array();


	/**
	 * Set Output
	 * --
	 * @param	string	$name		Name the item
	 * @param	string	$output		Content to output
	 * @param	boolean	$replace	Replace existing output (else will add to it)
	 * --
	 * @return	void
	 */
	public static function Set($name, $output, $replace=false)
	{
		if (isset(self::$Output[$name])) {
			if ($replace) {
				self::$Output[$name] = $output;
			}
			else {
				self::$Output[$name] = self::$Output[$name] . $output;
			}
		}
		else {
			self::$Output[$name] = $output;
		}
	}
	//-

	/**
	 * Will take particular output (it will return it, and then erase it)
	 * --
	 * @param	string	$particular	Get particular output item - if set to false, will get all
	 * @param	boolean	$asArray	Return all items as an array, or join them together and return string?
	 * --
	 * @return	mixed
	 */
	public static function Take($particular=false, $asArray=false)
	{
		$return = self::Get($particular, $asArray);
		self::Clear($particular);
		return $return;
	}
	//-

	/**
	 * Return Output
	 * --
	 * @param	boolean	$particular	Get particular output item - if set to false, will get all
	 * @param	boolean	$asArray	Return all items as an array, or join them together and return string?
	 * --
	 * @return	mixed
	 */
	public static function Get($particular=false, $asArray=false)
	{
		# Before get
		Event::Trigger('output.before.get', self::$Output);

		if ($particular) {
			if (isset(self::$Output[$particular])) {
				return self::$Output[$particular];
			}
			else {
				return false;
			}
		}
		else {
			# Before get all
			Event::Trigger('output.before.getall', self::$Output);

			if ($asArray) {
				return self::$Output;
			}
			else {
				return implode("\n", self::$Output);
			}
		}
	}
	//-


	/**
	 * Do we have particular view?
	 * --
	 * @param	string	$what
	 * --
	 * @return	boolean
	 */
	public static function Has($what)
	{
		return isset(self::$Output[$what]);
	}
	//-

	/**
	 * Clear Output
	 * --
	 * @param	string	$particular	Do you wanna clear particular output?
	 * --
	 * @return	void
	 */
	public static function Clear($particular=false)
	{
		if (!$particular) {
			self::$Output = array();
		}
		else {
			if (isset(self::$Output[$particular])) {
				unset(self::$Output[$particular]);
			}
		}
	}
	//-
}
//--
