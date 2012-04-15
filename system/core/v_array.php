<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Array Manipulations
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-03-20
 */

class vArray
{
	/**
	 * Converts Multi-dimensional Array, to _flat array_ (one dimension),
	 * for example, from: [key] => array([sub-key] => [value]) to: [key] => [value]
	 * if we provide parameter "$setKey", example would look like this:
	 * 		from: [key] => array([sub-key] => [value], [another-key] => [another-val]) to: [another-val] => [value]
	 * 		{if we use: ($Array, 'sub-key', 'another-key')}
	 * --
	 * @param	mixed	$Array		Array or object data
	 * @param	string	$selValue	For value, we use which sub-key's value?
	 * @param	string	$setKey		For key, we use which sub-key's value?
	 * --
	 * @return	array
	 */
	public static function Flat($Array, $selValue, $setKey=false)
	{
		if (is_object($Array)) { $Array = get_object_vars($Array); }

		$NewArr = '';
		if (is_array($Array) and !empty($Array))
		{
			foreach ($Array as $key => $Item) {
				if (is_object($Item)) {
					if ($setKey) {
						$NewArr[$Item->$setKey] = isset($Item->$selValue) ? $Item->$selValue : '';
					}
					else {
						$NewArr[$key] = isset($Item->$selValue) ? $Item->$selValue : '';
					}
				}
				else {
					if ($setKey) {
						$NewArr[$Item[$setKey]] = isset($Item[$selValue]) ? $Item[$selValue] : '';
					}
					else {
						$NewArr[$key] = isset($Item[$selValue]) ? $Item[$selValue] : '';
					}
				}
			}
		}

		return $NewArr;
	}
	//-

	/**
	 * Will implode array's keys.
	 * --
	 * @param	string	$glue
	 * @param	array	$Pieces
	 * --
	 * @return	string
	 */
	public static function ImplodeKeys($glue, $Pieces)
	{
		return implode($glue, array_keys($Pieces));
	}
	//-

	/**
	 * In multi dimensional arrays, set sub-key, to main key, for example:
	 * array(0 => array('id' => 'sunshine', 'name' => 'Lisa', 'age' => '25' )), if we provide 'id' as $selectKey,
	 * the array will be reshaped to: array('sunshine' => array('id' => 'sunshine', 'name' => 'Lisa', 'age' => '25'))
	 * --
	 * @param	array	$Array
	 * @param	string	$selectKey
	 * @param	mixed	$autoKeys	If select key isn't set, should we do auto key? (will be numeric, you can provide prefix)
	 * @param	boolean	$rewrite	Rewrite is key exists (else, add number after it, example: sunshine, sunshine_1, sunshine_2)
	 * --
	 * @return	array
	 */
	public static function SubToKey($Array, $selectKey, $autoKeys=false, $rewrite=true)
	{
		if (!is_array($Array)) return false;
		$NA = array();
		foreach($Array as $SA) {
			if (is_object($SA)) {
				$SA = get_object_vars($SA);
			}
			if (isset($SA[$selectKey])) {
				if (isset($NA[$selectKey]) and !$rewrite) {
					$i = 1;
					do {
						$newKey = $SA[$selectKey].'_'.$i;
						$i++;
					}
					while (isset($NA[$newKey]));
					$NA[$newKey] = $SA;
				}
				else {
					$NA[$SA[$selectKey]] = $SA;
				}
			}
			elseif ($autoKeys!==false) {
				$i = 1;
				do {
					$newKey = $autoKeys.$i;
					$i++;
				}
				while (isset($NA[$newKey]));
				$NA[$newKey] = $SA;
			}
		}
		return $NA;
	}
	//-

	/**
	 * Will Remove Empty Values Out Of Array
	 * --
	 * @param	array	$Array
	 * --
	 * @return	array
	 */
	public static function RemoveEmpty($Array)
	{
		if (!is_array($Array) OR empty($Array)) return $Array;

		$NewArray = array();
		foreach($Array as $key => $val) {
			if (is_array($val) AND !empty($val)) {
				$NewArray[$key] = self::RemoveEmpty($val);
			}
			elseif (is_object($val) OR is_bool($val)) {
				$NewArray[$key] = $val;
			}
			else {
				$val = trim($val);
				if (!empty($val)) {
					$NewArray[$key] = $val;
				}
			}
		}
		return $NewArray;
	}
	//-

	/**
	 * Will Explode Every Array Value and Set key
	 * e.g.: [0] => my_key:sample  => [my_key] => sample
	 * --
	 * @param	array	$Array
	 * @param	string	$seperator
	 * --
	 * @return	array
	 */
	public static function ExplodeToKey($Array, $seperator=':')
	{
		if (!is_array($Array) || empty($Array)) return $Array;

		$NewArray = array();
		foreach($Array as $val) {
			$Val = explode($seperator, $val, 2);
			if (isset($Val[0]) && isset($Val[1])) $NewArray[trim($Val[0])] = trim($Val[1]);
		}

		return $NewArray;
	}
	//-

	/**
	 * Clean Array keys - remove spaces, dashes, etc...
	 * --
	 * @param	array	$Array
	 * @param	string	$type	To what should spaces be converted, enter character; type: # to convert them to camelcase
	 * @param	string	$case	Convert case: upper, lower, ucfirst, lcfirst
	 * @param	array	$Filter	Define new filter
	 * --
	 * @return	array
	 */
	public static function CleanKeys($Array, $type, $case=false, $Filter=false)
	{
		if (!is_array($Array))
			return $Array;

		$NewArray = array();
		$Filter   = array(' ', '-', '_');

		foreach ($Array as $key => $val) {
			$key = vString::Clean($key, 400, 'a A 1 c', implode(' ', $Filter));
			if ($type=='#') {
				$key = str_replace($Filter, ' ', $key);
				$Key = explode(' ', $key);
				$key = '';
				foreach($Key as $v) {
					$key .= ucfirst(strtolower($v));
				}
				$key = lcfirst($key);
			}
			else {
				$key = str_replace($Filter, $type, $key);
			}

			if ($case) {
				$case = strtolower($case);
				switch ($case) {
					case 'upper':
						$key = strtoupper($key);
					break;

					case 'lower':
						$key = strtolower($key);
					break;

					case 'ucfirst':
						$key = ucfirst($key);
					break;

					case 'lcfirst':
						$key = lcfirst($key);
					break;
				}
			}

			$NewArray[$key] = $val;
		}

		return $NewArray;
	}
	//-

	/**
	 * Check if key of aray is valid - will check for:
	 * isset, is_array, !empty
	 * --
	 * @param	array	$Arr
	 * @param	string	$key	If you'd like to check if key of $Arr is valid array
	 * --
	 * @return	boolean
	 */
	public static function IsValidKey($Arr, $key=null)
	{
		if (!is_array($Arr) || empty($Arr))
			return false;

		if ($key === null)
			return true;

		// if we have to check for key too...
		if (isset($Arr[$key]) && is_array($Arr[$key]) && !empty($Arr[$key]))
			return true;
	}
	//-

	/**
	 * Better Merger (this will keep values in sub-arrays)
	 * --
	 * @param	array ...
	 * --
	 * @return	array
	 */
	public static function Merge()
	{
		$NewArray = array();
		$All      = func_get_args();
		foreach ($All as $Item) {

			# If Is not an array, then we'll just skip it
			if (!is_array($Item)) continue;

			foreach ($Item as $key => $item) {
				if (!isset($NewArray[$key])) {
					$NewArray[$key] = $item;
					continue;
				}
				else {
					if (!is_array($item)) {
						$NewArray[$key] = $item;
					}
					else {
						$NewArray[$key] = self::Merge($NewArray[$key], $item);
					}
				}
			}
		}

		return $NewArray;
	}
	//-

	/**
	 * Check if array has particular keys, this will set isset, and if you set
	 * $strict to true, then also, if key has any value (or if that values is empty)
	 * --
	 * @param	array	$Array
	 * @param	string	$keys	'one,two,three'
	 * @param	boolean	$strict
	 * --
	 * @return	boolean
	 */
	public static function HasKeys($Array, $keys, $strict=false)
	{
		$Keys = vString::ExplodeTrim(',', $keys);

		foreach ($Keys as $key) {
			if (!isset($Array[$key])) {
				return false;
			}

			if ($strict && empty($Array[$key])) {
				return false;
			}
		}

		return true;
	}
	//-

	/**
	 * Will get array value by entering path, example:
	 * array('user' => array('addres' => 'My Address')),
	 * to get "My Address", we can enter: user/address
	 * --
	 * @param	string	$path
	 * @param	array	$Array
	 * @param	mixed	$default
	 * --
	 * @return	mixed
	 */
	public static function GetByPath($path, $Array, $default=null)
	{
		if (empty($Array) || !is_array($Array)) {
			return $default;
		}

		$path = trim($path, '/');
		$Path = explode('/', $path);
		$Get  = $Array;

		foreach ($Path as $w) {
			if (isset($Get[$w])) {
				$Get = $Get[$w];
			}
			else {
				return $default;
			}
		}

		return $Get;
	}
	//-

	/**
	 * Will set array value by entering path, example:
	 * array('user' => array('addres' => 'My Address')),
	 * to set "address" to "My New Address", we can enter: $path = user/address, $value = My New Address
	 * --
	 * @param	string	$path
	 * @param	mixed 	$value
	 * @param	array	$Array	Passed as reference
	 * --
	 * @return	void
	 */
	public static function SetByPath($path, $value, &$Array)
	{
		$what = trim($path, '/');
		$What = explode('/', $what);
		$previous = $value;
		$new      = array();

		for ($i=count($What); $i--; $i==0) {
				$w = $What[$i];
				$new[$w]  = $previous;
				$previous = $new;
				$new = array();
		}

		$Array = self::Merge($Array, $previous);
	}
	//-

	/**
	 * Will delete array value by entering path, example:
	 * array('user' => array('addres' => 'My Address')),
	 * to delete "address", we can enter: user/address
	 * --
	 * @param	string	$what
	 * @param	array	$Array
	 * --
	 * @return	void
	 */
	public static function DeleteByPath($path, &$Array)
	{
		$Array = self::DeleteByPathHelper($Array, $path, null);
	}
	//-

	/**
	 * Helper Remove Items Out Of Array...
	 * --
	 * @param	array	$Array
	 * @param	string	$path
	 * @param	string	$cp
	 * --
	 * @return	array
	 */
	protected static function DeleteByPathHelper($Array, $path, $cp)
	{
		$Where    = $Array;
		$NewArray = array();

		foreach ($Where as $k => $i) {
			$cup = $cp . '/' . $k;

			if (trim($cup,'/') == trim($path,'/')) continue;

			if (is_array($i)) {
				$NewArray[$k] = self::DeleteByPathHelper($i, $path, $cup);
			}
			else {
				$NewArray[$k] = $i;
			}
		}

		return $NewArray;
	}
	//-

	/**
	 * Will trim array values(!)
	 * --
	 * @param	array	$Array
	 * @param	string	$mask
	 * --
	 * @return	void
	 */
	public static function Trim(&$Array, $mask=false)
	{
		foreach ($Array as $key => $val) {
			if ($mask) {
				$Array[$key] = trim($val, $mask);
			}
			else {
				$Array[$key] = trim($val);
			}
		}
	}
	//-

	/**
	 * Send variable as reference, and list of parameters, which will be filtered.
	 * For example, if you have Array of unknown srouce (can be $_POST), you can
	 * filter its values by:
	 * ($_POST, array('username' => 'string', 'password' => 'string'))
	 * Note that fields which aren't specefied will be droped.
	 * --
	 * @param	mixed	$Variable	Can be array or string / integer / ...
	 * @param	mixed	$Params		Can be string or array, if array, then it should be
	 * 		used as 'param' => 'setttings', where param represent key of $Variable.
	 *		Else, only settings can be provided. The following settings are possible:
	 * 			string		optional: string[regular_expression_for_filter]|default_value_if_wasn_not_set
	 * 			boolean		optional: boolean|false (false=default value)
	 * 			integer		optional: integer[-1,200]|12 (range min, max - if you want only min use: [12,] else [,12], default)
	 * 			float		optional: float[-1.00,200.00]|12.00 (range min, max - if you want only min use: [12.00,] else [,12.00], default)
	 * --
	 * @return	mixed
	 */
	public static function Filter(&$Variable, $Params)
	{
		if (is_array($Variable)) {

			$Drop = array();
			$Add  = array();

			foreach ($Variable as $key => $val) {
				if (!isset($Params[$key])) {
					$Drop[] = $key;
					unset($Variable[$key]);
				}
				else {
					$Variable[$key] = self::Filter($val, $Params[$key]);
				}
				unset($Params[$key]);
			}

			# See if we have any additional...
			if (!empty($Params)) {
				foreach ($Params as $key => $param) {
					if (strpos($param, '|') !== false) {
						$Add[] = $key;
						$param = vString::ExplodeTrim('|', $param);
						$param = array_pop($param);
						$Variable[$key] = $param;
					}
				}
			}

			if (!empty($Drop) || !empty($Add)) {
				Log::Add('INF', "Filter was used, the following keys were removed: `" .
									implode(', ', $Drop) . '` and added: `' .
									implode(', ', $Add)  . '`.', __LINE__, __FILE__);
			}
			return true;
		}

		if (is_array($Params)) {
			trigger_error("Params can't be array, if Variable isn't array also!", E_USER_ERROR);
		}

		$position = strrpos($Params, '|');
		if ($position !== false) {
			$default = $Params = substr($Params, 0, $position);
		}
		else {
			$default = false;
		}

		# Get options... string | integer | float | boolean
		$Params  = vString::ExplodeTrim('[', $Params, 2);
		$type    = substr(strtolower($Params[0]), 0, 3);
		$options = isset($Params[1]) ? rtrim($Params[1], ']') : false;

		switch ($type) {

			case 'str':
				if ($options) {
					return vString::RegExClean($Variable, '/['.preg_quote($options).']/');
				}
				else {
					return (string) $Variable;
				}
				break;

			case 'int':
				if ($options) {
					$options = vString::ExplodeTrim(',', $options, 2);
					$min = empty($options[0]) ? false : (int) $options[0];
					$max = empty($options[1]) ? false : (int) $options[1];

					if ($min && (int) $Variable < $min) {
						return $min;
					}

					if ($max && (int) $Variable > $max) {
						return $max;
					}
				}

				return (int) $Variable;
				break;

			case 'flo':
				if ($options) {
					$options = vString::ExplodeTrim(',', $options, 2);
					$min = empty($options[0]) ? false : (float) $options[0];
					$max = empty($options[1]) ? false : (float) $options[1];

					if ($min && (float) $Variable < $min) {
						return $min;
					}

					if ($max && (float) $Variable < $max) {
						return $max;
					}
				}

				return (float) $Variable;
				break;

			case 'boo':
				return vBoolean::Parse($Variable);
				break;

			default:
				trigger_error("Invalid type provided for filter: `{$type}`", E_USER_ERROR);
		}
	}
	//-
}
//--
