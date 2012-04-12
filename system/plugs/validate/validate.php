<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Validate Static Class and Validate Assign Class
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2011-11-19
 */
class cValidate
{
	/**
	 * @var	array	List of fields to be validated
	 */
	private static $ValidationsList = array();

	
	/**
	 * Get files, ...
	 * --
	 * @return	boolean
	 */
	public static function _DoInit()
	{
		# Get validate asssign
		include ds(dirname(__FILE__) . '/validate_variable.php');

		# Get language
		Plug::GetLanguage(__FILE__);

		return true;
	}
	//-

	/**
	 * Will add new filed to validate it.
	 * --
	 * @param	mixed	$value
	 * @param	string	$name	If there's no name, no message will be set!
	 * --
	 * @return	cValidateVariable
	 */
	public static function Add($value, $name=false)
	{
		$Validator = new cValidateVariable($value, $name);
		self::$ValidationsList[] = $Validator;
		return $Validator;
	}
	//-

	/**
	 * Check if every field is valid...
	 * --
	 * @return	boolean
	 */
	public static function IsValid()
	{
		if (is_array(self::$ValidationsList) && (!empty(self::$ValidationsList))) {
			foreach (self::$ValidationsList as $Obj) {
				if ($Obj->isValid() === false) {
					return false;
				}
			}
		}
		else {
			Log::Add('WAR', "You run validation with empty list!?", __LINE__, __FILE__);
			return true;
		}

		return true;
	}
	//-

	/**
	 * Will add new simple filed to validate it.
	 * --
	 * @param	mixed	$value
	 * --
	 * @return	cValidateVariable
	 */
	private static function AddSimple($value)
	{
		return new cValidateVariable($value, false);
	}
	//-

	/**
	 * Check if value is valid e-mail address
	 * --
	 * @param	string	$value
	 * @param	string	$domain	Check if is on particular domain (example: @gmail.com)
	 * --
	 * @return	boolean
	 */
	public static function IsEmail($value, $domain=null)
	{
		return self::AddSimple($value)->hasValue()->isEmail($domain)->isValid();
	}
	//-
}
//--
