<?php namespace Avrelia\Core; if (!defined('AVRELIA')) die('Access is denied!');

/**
 * Arr Base Class
 * -----------------------------------------------------------------------------
 * Arrays manipulation.
 * ----
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Arr
{
    /**
     * Multi dimensional array, to one dimension.
     * Pass in value = name
     *     [['id' => 15, 'name' => 'Jack'], ['id' => 42, 'name' => 'Neo']] ---->
     *     ['Jack', 'Neo']
     * Pass in value = id
     *     [15, 42]
     * 
     * If you pass in the key, we'll grab item[key] value, and set it as a key.
     * Pass in key = id, value = name
     *     ['id' => 12, 'name' => 'Inna'] ----> [12 => 'Inna']
     * --
     * @param   array   $input_array
     * @param   string  $value   Which sub-key's value, we use as value?
     * @param   string  $key     Which sub-key's value, we use as key?
     * @return  array
     */
    public static function to_one_dimension($input_array, $value, $key=false)
    {
        $result = '';

        if (is_array($input_array) and !empty($input_array))
        {
            foreach ($input_array as $item_key => $item) 
            {
                $set_key = $key ? $item[$key] : $item_key;
                $result[$set_key] = isset($item[$value]) ? $item[$value] : '';
            }
        }

        return $result;
    }

    /**
     * Will implode array's keys.
     * --
     * @param   string  $glue
     * @param   array   $pieces
     * @return  string
     */
    public static function implode_keys($glue, $pieces)
    {
        return implode($glue, array_keys($pieces));
    }

   /**
     * In multi dimensional arrays, take sub-key, and set it as main key.
     * If we pass in 'id' as '$index_key':
     *     [['id' => 12, 'age' => 25], ['id' => 20, 'age' => 30]] ---->
     *     ['12' => ['id' => 12, 'age' => 25], 20 => ['id' => 20, 'age' => 30]]
     * If key already exists, and rewrite is set to true:
     *     [['id' => 12, 'age' => 25], ['id' => 12, 'age' => 40]] ---->
     *     [12 => ['id' => 12, 'age' => 40]]
     * If rewrite is set to false:
     *     [12 => ['id' => 12, 'age' => 25], 12_1 => ['id' => 12, 'age' => 40]]
     * If automatic is set to true:
     *     [['id' => 12, 'age' => 25], ['age' => 40]] ---->
     *     [12 => ['id' => 12, 'age' => 25], 1 => ['age' => 40]]
     *  If automatic is set to false:
     *     [12 => ['id' => 12, 'age' => 25]]
     * --
     * @param   array   $input_array
     * @param   string  $index_key
     * @param   mixed   $automatic   If select key isn't set, should it be set
     *                               automatically? It will be numeric.
     * @param   boolean $rewrite     Rewrite if key exists, else, add number 
     *                               after it.
     * @return  array
     */
    public static function key_from_sub($input_array, $index_key, $automatic=true, $rewrite=false)
    {
        if (!is_array($input_array)) { return false; }

        $result = array();

        foreach($input_array as $item) 
        {
            if (isset($item[$index_key])) 
            {
                if (isset($result[$index_key]) && !$rewrite) {
                    $i = 1;
                    do {
                        $new_key = $item[$index_key].'_'.$i;
                        $i++;
                    }
                    while (isset($result[$new_key]));
                    $result[$new_key] = $item;
                }
                else {
                    $result[$item[$index_key]] = $item;
                }
            }
            elseif ($automatic !== false) {
                $i = 1;
                do {
                    $new_key = $i;
                    $i++;
                }
                while (isset($result[$new_key]));
                $result[$new_key] = $item;
            }
        }

        return $result;
    }

    /**
     * Remove empty values from Array.
     * --
     * @param   array   $input_array
     * @return  array
     */
    public static function remove_empty($input_array)
    {
        if (!is_array($input_array) || empty($input_array)) 
            { return $input_array; }

        $result = array();

        foreach($input_array as $key => $val) 
        {
            if (is_array($val) && !empty($val)) 
                { $result[$key] = self::remove_empty($val); }
            elseif (is_object($val) OR is_bool($val)) 
                { $result[$key] = $val; }
            else {
                $val = trim($val);
                if (!empty($val)) {
                    $result[$key] = $val;
                }
            }
        }

        return $result;
    }

    /**
     * Set key from array value.
     * If we set separator to '.':
     * [0 => '12.Inna', 1 => '23.Neo'] ----> [12 => 'Inna', '23' => 'Neo']
     * --
     * @param   array   $input_array
     * @param   string  $separator
     * @return  array
     */
    public static function explode_to_key($input_array, $separator=':')
    {
        if (!is_array($input_array) || empty($input_array))
            { return $input_array; }

        $new_array = array();

        foreach($input_array as $val) {
            $value = explode($separator, $val, 2);
            if (isset($value[0]) && isset($value[1])) {
                $new_array[trim($value[0])] = trim($value[1]);
            }
        }

        return $new_array;
    }

    /**
     * Clean array keys - remove spaces, dashes, etc...
     * --
     * @param   array   $input_array
     * @param   integer $divider    STRING_CAMELCASE | STRING_UNDERSCORE
     * @return  array
     */
    public static function clean_keys($input_array, $divider=STRING_UNDERSCORE)
    {
        if (!is_array($input_array)) { return $input_array; }
        $new_array = array();
        $to_space  = array('_', '-', '.', ',', ';', '+', '/');

        foreach ($input_array as $key => $val) 
        {
            # Convert some common characters to spaces
            $key = str_replace($to_space, ' ', $key);

            # There should be maximum on space
            $key = Str::limit_repeat($key, ' ', 1);
            $key = Str::clean($key, 'aA1s');

            $key =
                ($divider === STRING_UNDERSCORE)
                ? to_underscore($key)
                : to_camelcase($key);

            $new_array[$key] = $val;
        }

        return $new_array;
    }

    /**
     * Check if is valid array, if is not empty.
     * --
     * @param   array   $input_array
     * @return  boolean
     */
    public static function is_empty($input_array)
    {
        return !is_array($input_array) || empty($input_array);
    }

    /**
     * Check if input is array, if the required key is set.
     * @param  array  $input_array
     * @param  string $key
     * @return boolean
     */
    public static function has_key($input_array, $key)
    {
        if (self::is_empty($input_array)) { return false; }
        return isset($input_array[$key]);
    }

    /**
     * Check if array has ALL required keys. Returns true only if all keys are
     * found, and false otherwise.
     * @param  array  $input_array
     * @param  array  $keys
     * @return boolean
     */
    public static function has_keys($input_array, $keys)
    {
        if (self::is_empty($input_array) || self::is_empty($keys)) 
            { return false; }

        foreach ($keys as $key => $value) {
            if (!isset($input_array[$key])) { return false; }
        }

        return true;
    }

    /**
     * Get particular element out of array, of return default if element doesn't
     * exists, or if passed in array is not valid.
     * @param  string  $key
     * @param  array   $input_array
     * @param  mixed   $default
     * @return mixed
     */
    public static function element($key, $input_array, $default=false)
    {
        if (self::is_empty($input_array)) 
            { return $default; }

        if (!self::has_key($input_array, $key)) 
            { return $default; }
        else 
            { return $input_array[$key]; }
    }

    /**
     * Get particular elements out of array, or return default if element doesn't
     * exists, or if passed in array is not valid. This will build new array,
     * with required keys in it.
     * --
     * @param  mixed $keys        Can be array or string with comma separated keys.
     * @param  array $input_array
     * @param  mixed $default     Can be string or an array to set default for 
     *                            each key.
     * --
     * @return array
     */
    public static function elements($keys, $input_array, $default=false)
    {
        // If we have string we can covert it to an array
        if (!is_array($keys)) { $keys = Str::explode_trim(',', $keys); }

        // If nothing in array, then we'll just return default
        if (self::is_empty($keys)) { return $default; }
        
        // Do we have any input and is it an array?
        if (!is_array($input_array)) { $input_array = array($input_array); }

        // Set empty results
        $result = array();

        // Check each key, to see if is set, - if not we'll return default
        foreach ($keys as $i => $key) {

            // If we're having default values as array, we'll check them now
            if (is_array($default)) {
                $default_current = isset($default[$i])
                                        ? $default[$i]
                                        : false;
            }
            else {
                $default_current = $default;
            }
            
            // Set result
            $result[$key] =
                isset($input_array[$key])
                    ? $input_array[$key]
                    : $default_current;
        }

        return $result;
    }

    /**
     * Returns random array element.
     * @param  array $input_array
     * @return mixed
     */
    public static function random_element($input_array)
    {
        if (self::is_empty($input_array)) { return false; }

        shuffle($input_array);
        return array_pop($input_array);
    }

    /**
     * Better merge, will keep values in sub-arrays.
     * @param   array ...
     * @return  array
     */
    public static function merge()
    {
        $result     = array();
        $all_arrays = func_get_args();

        foreach ($all_arrays as $array) {

            # If Is not an array, then we'll just skip it
            if (self::is_empty($array)) { continue; }

            foreach ($array as $key => $item) {
                if (!isset($result[$key])) {
                    $result[$key] = $item;
                    continue;
                }
                else {
                    if (!is_array($item)) {
                        $result[$key] = $item;
                    }
                    else {
                        $result[$key] = self::merge($result[$key], $item);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Will get array value by entering string path.
     * ['user' => ['address' => 'My Address']] 
     *     ----> user/address
     *     ----> My Address
     * 
     * @param   string  $path
     * @param   array   $input_array
     * @param   mixed   $default
     * @return  mixed
     */
    public static function get_by_path($path, $input_array, $default=null)
    {
        if (self::is_empty($input_array)) { return $default; }

        $path = trim($path, '/');
        $path = explode('/', $path);
        $get  = $input_array;

        foreach ($path as $w) {
            if (isset($get[$w])) 
                { $get = $get[$w]; }
            else 
                { return $default; }
        }

        return $get;
    }

    /**
     * Will set array value by entering path.
     * ['user' => ['address' => 'My Address']]
     *     ----> user/address, 'New Address'
     *     ----> ['user' => ['address' => 'New Address']]
     * 
     * @param   string  $path
     * @param   mixed   $value
     * @param   array   $input_array  Passed as reference
     * @return  void
     */
    public static function set_by_path($path, $value, &$input_array)
    {
        $what = trim($path, '/');
        $what = explode('/', $what);
        $previous = $value;
        $new      = array();

        for ($i=count($what); $i--; $i==0) {
                $w = $what[$i];
                $new[$w]  = $previous;
                $previous = $new;
                $new = array();
        }

        $input_array = self::merge($input_array, $previous);
    }

    /**
     * Will delete array value by entering path.
     * ['user' => ['address' => 'My Address']]
     *     ----> user/address
     * --
     * @param   string  $path
     * @param   array   $input_array
     * --
     * @return  void
     */
    public static function delete_by_path($path, &$input_array)
    {
        $input_array = self::_delete_by_path_helper($input_array, $path, null);
    }

    /**
     * Delete by path helper
     * @param   array   $input_array
     * @param   string  $path
     * @param   string  $cp
     * @return  array
     */
    protected static function _delete_by_path_helper($input_array, $path, $cp)
    {
        $result = array();

        foreach ($input_array as $k => $i) {
            $cup = $cp . '/' . $k;

            if (trim($cup,'/') == trim($path,'/')) { continue; }

            if (is_array($i)) 
                { $result[$k] = self::_delete_by_path_helper($i, $path, $cup); }
            else 
                { $result[$k] = $i; }
        }

        return $result;
    }

    /**
     * Will trim array values(!)
     * 
     * @param   array   $input_array
     * @param   string  $mask
     * @return  void
     */
    public static function trim(&$input_array, $mask=false)
    {
        if (self::is_empty($input_array)) { return false; }

        foreach ($input_array as $key => $val) {
            if ($mask) 
                { $input_array[$key] = trim($val, $mask); }
            else 
                { $input_array[$key] = trim($val); }
        }
    }

}