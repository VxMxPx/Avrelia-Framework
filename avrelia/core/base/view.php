<?php namespace Avrelia\Core; if (!defined('AVRELIA')) die('Access is denied!');

/**
 * View Class and ViewAssign
 * -----------------------------------------------------------------------------
 * This is sub-class for view, returned when we call "View::get"
 * Handle loading of classes.
 * ----
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class View
{
    # How many vew rendering is in progress? (for calls from template itself)
    protected static $views_progress = 0;

    # Variables For Views
    protected static $views_data     = array();

    # Amount of all loaded views
    protected static $views_loaded   = 0;

    /**
     * Add Data To View (at any point)
     * --
     * @param   mixed   $key        Key Name - Or Array Of Variables
     * @param   string  $content    Content (only in case if you didn't provide array as key)
     * @return  void
     */
    public static function assign($key, $content=null)
    {
        if (!is_array($key)) 
            { self::$views_data[$key] = $content; }
        else
            { self::$views_data = array_merge(self::$views_data, $key); }
    }

    /**
     * Will Load The View, and output it
     * --
     * @param   string  $file   Only filename
     * @param   array   $data   List of variables to inclued
     * @return  ViewAssign
     */
    public static function get($file, $data=array())
    {
        $BT = debug_backtrace();
        if (isset($BT[3]['class']) &&  isset($BT[3]['type']) && isset($BT[3]['function'])) {
            $bt_type = $BT[3]['function'];
        }
        else {
            $bt_type = false;
        }

        $result = self::_render($file, $data);

        if ($bt_type !== 'get') {
            $output_key = 'AvreliaView.'.self::$views_loaded.'.'.$file;
            Output::set($output_key, $result);
            return new ViewAssign($result, $output_key);
        }
        else {
            # This mean that call was made from template iself...
            echo $result;
        }
    }

    /**
     * Will Load The View, and return it
     * Return string or boolean - depends if view was found and loaded or not.
     * --
     * @param  string  $file   Only filename
     * @param  array   $data   List of variables to inclued
     * @return string
     */
    protected static function _render($file, $data=array())
    {
        # Add ext?
        $file = ((substr($file,-4,4) == '.php') || (substr($file,-5,5) == '.html')) 
                    ? $file 
                    : $file . '.php';

        # Absolute path provided?
        if (substr($file,0,1) != '/')
            { $filename = app_path('views/'.$file); }
        else
            { $filename = $file; }

        if (!file_exists($filename)) {
            Log::err("File not found: `{$filename}`.");
            return false;
        }

        self::assign($data);
        $data = self::$views_data;

        if (!empty($data)) {
            foreach($data as $var => $val) {
                $$var = $val;
            }
        }

        self::$views_progress++;

        ob_start();
            include($filename);
            $result = ob_get_contents();
        ob_end_clean();

        self::$views_progress--;
        self::$views_loaded++;

        return $result;
    }

    /**
     * Placeholder for region
     * --
     * @param   string  $name
     * @return  void
     */
    public static function region($name)
    {
        echo Output::take('AvreliaView.region.'.$name), 
        '<!-- Avrelia Framework Region {'.$name.'} -->';
    }
}

/**
 * ViewAssign
 */
class ViewAssign
{
    # View's content
    private $contents;

    # View's Output key
    private $output_key;

    /**
     * Construct ViewAssign
     * --
     * @param   string  $contents
     * @param   string  $output_key
     * @return  void
     */
    public function __construct($contents, $output_key)
    {
        $this->contents   = $contents;
        $this->output_key = $output_key;
    }

    /**
     * Set current view as master
     * --
     * @return  void
     */
    public function as_master()
    {
        Output::set('AvreliaView.master', $this->contents);
        Output::clear($this->output_key);
    }

    /**
     * Will assign current view as region
     * --
     * @param   string  $name   Region's name
     * @return  void
     */
    public function as_region($name)
    {
        if (Output::has('AvreliaView.master')) {
            $master = Output::take('AvreliaView.master');
            $master = str_replace('<!-- Avrelia Framework Region {'.$name.'} -->',
                                  $this->contents . "\n" . '<!-- Avrelia Framework Region {'.$name.'} -->',
                                  $master);
            Output::set('Avrelia.master', $master);
        }
        else {
            Output::set("AvreliaView.region.{$name}", $this->contents);
        }

        Output::clear($this->output_key);
    }

    /**
     * Echo view
     * --
     * @return  void
     */
    public function to_screen()
    {
        echo $this->do_return();
    }

    /**
     * Return view
     * --
     * @return  string
     */
    public function do_return()
    {
        Output::clear($this->output_key);
        return $this->contents;
    }
}
