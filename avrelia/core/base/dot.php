<?php namespace Avrelia\Core; if (!defined('AVRELIA')) die('Access is denied!');

/**
 * Dot Class
 * -----------------------------------------------------------------------------
 * Handing command line interface
 * ----
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Dot
{
    # Parameters to execute
    protected $params = null;

    # Available scripts
    protected static $available = false;

    # Get available scripts

    # Construct the object
    public function __construct($params) { $this->params = $params; }

    /**
     * Execute the params previously set.
     * --
     * @return void
     */
    public function execute()
    {
        $params = $this->params;

        # Insert empty space / line
        self::inf('');

        # Parameter one must be set for sure
        if (!isset($params[1])) {
            return
                self::war(
                    "Plase enter the command.".
                    "Type `help` for list of commands.");
        }

        # Class in a format command_Cli
        $class = $params[1] . '_Cli';
        $file  = $params[1];

        if (!class_exists($class, false))
        {
            $file = self::get_script_by_name($file);

            if (!$file) {
                self::war("Invalid command. Type `help` for list of commands.");
                return;
            }

            if (file_exists($file))
            {
                include($file);
                if (!class_exists($class, false))
                {
                    self::err("File was found, but class couldn't be constucted.");
                    return;
                }
            }
        }

        # Construct object and try to run the action, if possible...
        $cli_class = new $class($params);
        $action    = (isset($params[2])) ? 'action_' . $params[2] : 'action_none';

        # Unset all params we don't need
        $params_in = array_slice($params, 3);

        if (method_exists($cli_class, $action))
            { call_user_func_array(array($cli_class, $action), $params_in); }

        # Insert empty space / line
        self::inf('');
        return;
    }

    /* -------------------------------------------------------------------------
     * STATIC METHODS
     */

    # Will set all scripts
    public static function _on_include_()
    {
        self::$available = self::get_all_scripts();
    }

    /**
     * Get list of available scripts. Return array in format:
     *     'script_id'  => 'script_path',
     *     'another_id' => 'another_path'
     * --
     * @return array
     */
    public static function get_all_scripts()
    {
        if (self::$available) { return self::$available; }

        // Scan application, system and plugs directories
        return array_merge(
            self::_find_in_dir(app_path('scripts')),
            self::_find_in_dir(sys_path('scripts')),
            (array) Plug::get_scripts()
        );
    }

    /**
     * Get particular script's path by script's id / name.
     * For example: help => /some/path/help.php
     * --
     * @param  string $name
     * --
     * @return string
     */
    public static function get_script_by_name($name)
    {
        return isset(self::$available[$name])
                ? self::$available[$name]
                : false;
    }

    /**
     * Scan particular directory to find scripts in it. Return array in format:
     *     'script_id'  => 'script_path',
     *     'another_id' => 'another_path'
     * --
     * @param  string $directory
     * @return array
     */
    protected static function _find_in_dir($directory)
    {
        $final = array();

        if (is_dir($directory)) 
            { $list = scandir($directory); }

        if (!empty($list)) {
            foreach ($list as $script) {
                if (substr($script, -4, 4) !== '.php') { continue; }
                $final[substr($script, 0, -4)] = ds($directory, $script);
            }
        }

        return $final;
    }

    /**
     * Capture the cursos - wait for user's input. You must pass in a function,
     * this will run until function returns false.
     * --
     * @param  string   $title The text displayed for input question.
     * @param  function $func  Function to be executed for each input, 
     *                         when function returns false, the cursor will be
     *                         released.
     * --
     * @return array    array(
     *                     'title'  => $title,
     *                     'inputs' => array(all inputed values),
     *                     'line'   => number of lines
    *                   )
     */
    public static function input($title, $func)
    {
        $shell = array(
            'title'  => $title,
            'inputs' => array(),
            'line'   => 0
        );

        do {
            if (function_exists('readline')) {
                $stdin = readline($shell['title']);
                readline_add_history($stdin);
            }
            else {
                echo $shell['title'];
                $stdin = fread(STDIN, 8192);
            }
            $stdin = trim($stdin);

            $shell['inputs'][] = $stdin;
            $shell['line']++;

        } while($func($stdin, $shell));

        return $shell;
    }

    /**
     * Print out the message
     * @param  string  $message
     * @param  boolean $new_line
     */
    public static function war($message, $new_line=true)
        { return self::out('war', $message, $new_line); }

    public static function err($message, $new_line=true)
        { return self::out('err', $message, $new_line); }

    public static function inf($message, $new_line=true)
        { return self::out('inf', $message, $new_line); }

    public static function ok($message, $new_line=true)
        { return self::out('ok', $message, $new_line); }

    /**
     * Create a new line
     * @param  integer $num Number of new lines
     */
    public static function nl($num=1)
        { echo str_repeat("\n", (int)$num); }

    /**
     * Create documentation / help for particular command.
     */
    public static function doc($title, $usage, $commands=false)
    {
        Dot::inf($title);
        Dot::nl();
        Dot::inf('  ' . $usage);
        Dot::nl();

        if (!is_array($commands)) { return; }

        // Get longest key, to align with it
        $longest = 0;
        foreach ($commands as $key => $command) {
            strlen($key) > $longest
                and $longest = strlen($key);
        }

        // Print all commands
        Dot::inf('Available options:');
        Dot::nl();
        foreach ($commands as $key => $command) {

            Dot::inf(
                '  ' . 
                (!is_integer($key) ? $key : ' ') . 
                '    ' . 
                str_repeat(" ", $longest - strlen($key)) .
                $command
            );
        }
    }

    /**
     * Will print out the message
     * --
     * @param   string  $type
     *                      inf -- Regular white message
     *                      err -- Red message
     *                      war -- Yellow message
     *                      ok  -- Green message
     * @param   string  $message
     * @param   boolean $new_line   Should message be in new line
     */
    public static function out($type, $message, $new_line=true)
    {
        # If we're in testing, we need plain messages...
        if (TESTING) {
            echo $message, ($new_line ? "\n" : '');
            flush();
            return;
        }

        switch (strtolower($type))
        {
            case 'err':
                $color = "\x1b[31;01m";
                break;

            case 'war':
                $color = "\x1b[33;01m";
                break;

            case 'ok':
                $color = "\x1b[32;01m";
                break;

            default:
                $color = null;
        }

        echo
            (!is_null($color) ? $color : ''),
            $message,
            "\x1b[39;49;00m";

        if ($new_line)
            { echo "\n"; }

        flush();
    }
}
