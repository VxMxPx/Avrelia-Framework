<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

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
class Output_Base
{
    # Whole output
    protected static $output_cache = array();

    /**
     * Set Output
     * --
     * @param   string  $name       Name the item
     * @param   string  $output     Content to output
     * @param   boolean $replace    Replace existing output (else will add to it)
     * @return  void
     */
    public static function set($name, $output, $replace=false)
    {
        if (isset(self::$output_cache[$name])) {
            if ($replace)
                { self::$output_cache[$name] = $output; }
            else
                { self::$output_cache[$name] = self::$output_cache[$name] . $output; }
        }
        else {
            self::$output_cache[$name] = $output;
        }
    }

    /**
     * Will take particular output (it will return it, and then erase it)
     * --
     * @param   string  $particular Get particular output item.
     *                              If set to false, will get all.
     * @return  mixed
     */
    public static function take($particular=false)
    {
        $return = self::Get($particular, false);
        self::clear($particular);
        return $return;
    }

    /**
     * Return Output
     * --
     * @param   boolean $particular Get particular output item.
     *                              If set to false, will get all.
     * @param   boolean $as_array   Return all items as an array, or join them 
     *                              together and return string?
     * @return  mixed
     */
    public static function get($particular=false, $as_array=false)
    {
        # Before get
        Event::trigger('/core/output/get', self::$output_cache);

        if ($particular) {
            if (isset(self::$output_cache[$particular])) 
                { return self::$output_cache[$particular]; }
            else
                { return false; }
        }
        else {
            # Before get all
            Event::trigger('/core/output/get_all', self::$output_cache);

            if ($as_array)
                { return self::$output_cache; }
            else
                { return implode("\n", self::$output_cache); }
        }
    }

    /**
     * Do we have particular key?
     * --
     * @param   string  $what
     * @return  boolean
     */
    public static function has($what) 
        { return isset(self::$output_cache[$what]); }

    /**
     * Clear Output
     * --
     * @param   string  $particular Do you wanna clear particular output?
     * @return  void
     */
    public static function clear($particular=false)
    {
        if (!$particular) {
            self::$output_cache = array();
        }
        else {
            if (isset(self::$output_cache[$particular])) {
                unset(self::$output_cache[$particular]);
            }
        }
    }
}