<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * JSON
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://avrelia.com/license
 * @link       http://avrelia.com
 * @since      Version 0.80
 * @since      Date 2010-04-07
 */


class uJSON
{

	/*  ****************************************************** *
	 *          Decoding
	 *  **************************************  */

	/**
	 * Decode a JSON file, and return it as Array or Object
	 * ---
	 * @param string   $filename -- The file with JSON string
	 * @param bool     $assoc    -- When TRUE, returned object will be converted into associative array.
	 * @param integet  $depth    -- User specified recursion depth.
	 * ---
	 * @return mixed
	 */
	public static function DecodeFile($filename, $assoc=false, $depth=512)
	{
		$filename = ds($filename); // Clean up messy path

		if (file_exists($filename)) {
			$content = FileSystem::Read($filename);
			return self::Decode($content, $assoc, $depth);
		}
		else {
			Log::Add('WAR', "File not found: `{$filename}`.", __LINE__, __FILE__);
			return false;
		}
	}
	//-

	/**
	 * Decode a JSON string, and return it as Array or Object
	 * ---
	 * @param string   $json     -- The json string being decoded.
	 * @param bool     $assoc    -- When TRUE, returned object will be converted into associative array.
	 * @param integet  $depth    -- User specified recursion depth.
	 * ---
	 * @return mixed
	 */
	public static function Decode($json, $assoc=false, $depth=512)
	{
		$decoded = json_decode($json, $assoc, $depth);

		if (json_last_error() != JSON_ERROR_NONE) {
			$JSONErrors = array(
				JSON_ERROR_NONE      => 'No error has occurred',
				JSON_ERROR_DEPTH     => 'The maximum stack depth has been exceeded',
				JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
				JSON_ERROR_SYNTAX    => 'Syntax error',
			);
			Log::Add('ERR', "JSON decode error: `" . $JSONErrors[json_last_error()] . '`.', __LINE__, __FILE__);
			return false;
		}
		else {
			return $decoded;
		}
	}
	//-


	/*  ****************************************************** *
	 *          Encoding
	 *  **************************************  */

	/**
	 * Save the JSON representation of a value, to the file.
	 * If file exists, it will be overwritten.
	 * ---
	 * @param string $filename -- The file to which the data will be saved.
	 * @param mixed  $Value    -- The value being encoded. Can be any type except a resource . This function only works with UTF-8 encoded data.
	 * @param int    $options  -- Bitmask consisting of JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP, JSON_HEX_APOS, JSON_FORCE_OBJECT.
	 * ---
	 * @return bool
	 */
	public static function EncodeFile($filename, $Value, $options=0)
	{
		return FileSystem::Write(self::Encode($Value,$options), $filename, false);
	}
	//-

	/**
	 * Returns the JSON representation of a value
	 * ---
	 * @param mixed  $Value    -- The value being encoded. Can be any type except a resource . This function only works with UTF-8 encoded data.
	 * @param int    $options  -- Bitmask consisting of JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP, JSON_HEX_APOS, JSON_FORCE_OBJECT.
	 * ---
	 * @return string
	 */
	public static function Encode($Value, $options=0)
	{
		return json_encode($Value, $options);
	}
	//-
}
//--
