<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Dot Class
 * -----------------------------------------------------------------------------
 * Handing command line interface
 * ----
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Dot_Base
{
    # Parameters to execute
    protected $params = null;

    public function __construct($params) { $this->params = $params; }

    /**
     * Execute the params previously set.
     * @return void
     */
    public function execute()
    {
        $params = $this->params;

        if (!isset($params[1])) { 
            self::war("Plase enter the command. Type `help` for list of commands.");
            return; 
        }

        $class = 'cli'.to_camelcase($params[1]);
        $file  = strtolower(str_replace('.', '', $params[1]));

        if (!class_exists($class, false))
        {
            if (file_exists(app_path("scripts/{$file}.php")))
            {
                include(app_path("scripts/{$file}.php"));
                if (!class_exists($class, false))
                {
                    self::err("File was found, but class couldn't be constucted.");
                    return;
                }
            }
            elseif (file_exists(sys_path("scripts/{$file}.php")))
            {
                include(sys_path("scripts/{$file}.php"));
                if (!class_exists($class, false))
                {
                    self::err("File was found, but class couldn't be constucted.");
                    return;
                }
            }
            else
            {
                self::war("Invalid command.");
                return;
            }
        }

        $params[2] = (isset($params[2])) ? $params[2] : '_empty';

        if (method_exists($class, $params[2]))
        {
            $commands = array_slice($params, 3);
            return call_user_func_array(array($class, $params[2]), $commands);
        }
        elseif (method_exists($class, '_empty'))
        {
            $commands = array_slice($params, 2);
            return call_user_func_array(array($class, '_empty'), $commands);
        }
        else
        {
            self::war("Undefined action `{$params[2]}`!");
            return;
        }
    }

    /**
     * Print out the message
     * @param  string  $message
     * @param  boolean $new_line
     * @return void
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
     * Will print out the message
     * --
     * @param   string  $type
     *                      inf -- Regular white message
     *                      err -- Red message
     *                      war -- Yellow message
     *                      ok  -- Green message
     * @param   string  $message
     * @param   boolean $new_line   Should message be in new line
     * @return  void
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
