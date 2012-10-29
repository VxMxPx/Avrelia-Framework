<?php namespace Avrelia\Core; if (!defined('AVRELIA')) die('Access is denied!');

/**
 * Output class
 * -----------------------------------------------------------------------------
 * Take case of any kind of output. The output can be set, replaced and 
 * retrieved. It gets dumpeed to the display only at the end of execution.
 * ----
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Output
{
    # Whole output
    protected static $output_cache = array();

    /**
     * Add output string.
     * --
     * @param string  $contents
     * @param mixed   $key      Either false for automatic, or particular key.
     */
    public static function add($contents, $key=false) 
    {
        if (!$key) {
            self::$output_cache[] = $contents;
        }
        else {
            if (isset(self::$output_cache[$key])) {
                $contents = self::$output_cache[$key] . $contents;
            }

            self::$output_cache[$key] = $contents;
        }
    }

    /**
     * Replace output if exists, otherwise just add it.
     * --
     * @param  string $key
     * @param  string $contents
     * --
     * @return void
     */
    public static function replace($contents, $key) 
    {
        self::$output_cache[$key] = $contents;
    }

    /**
     * Will take particular output (it will return it, and then erase it)
     * --
     * @param   string  $key Get particular output item.
     *                       If set to false, will get all.
     * --
     * @return  mixed
     */
    public static function take($key=false) 
    {
        $output = self::as_string($key);
        self::clear($key);

        return $output;
    }

    /**
     * Return one par of whole output as a string.
     * --
     * @param  mixed $key
     * --
     * @return string
     */
    public static function as_string($key=false) 
    {
        if (!$key) { return implode("\n", self::$output_cache); }
        return Arr::element($key, self::$output_cache, null);
    }

    /**
     * Do we have particular key? Or any output at all?
     * --
     * @param   string  $key
     * --
     * @return  boolean
     */
    public static function has($key=false)
    {
        if (!$key) {
            return is_array(self::$output_cache) && !empty(self::$output_cache);
        }
        else {
            return isset(self::$output_cache[$key]);
        }
    }

    /**
     * Clear Output. If key is provided only particular item will be cleared.
     * Otherwise all cache will be cleared.
     * --
     * @param   string  $key
     * --
     * @return  void
     */
    public static function clear($key=false)
    {
        if (!$key) {
            self::$output_cache = array();
        }
        else {
            if (isset(self::$output_cache[$key])) {
                unset(self::$output_cache[$key]);
            }
        }
    }

    /**
     * Return all output items as an array.
     * --
     * @return array
     */
    public static function as_array() 
    {
        return self::$output_cache;
    }
}