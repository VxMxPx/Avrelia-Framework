<?php namespace Avrelia\Core; if (!defined('AVRELIA')) die('Access is denied!');

/**
 * Log Base Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Log
{
    // All log items
    protected static $logs = array();

    /**
     * Add System Log Message
     * If you added OK or INF true will be returned else false.
     * --
     * @param   string  $message    Plain englisg message
     * @param   string  $type       inf|war|err == information, warning, error
     * @return  boolean
     */
    public static function add($message, $type)
    {
        # Always lower case
        $type = strtolower($type);

        # Auto assign line and file
        $BT   = debug_backtrace();
        $line = isset($BT[1]['line']) ? $BT[1]['line'] : null;
        $file = isset($BT[1]['file']) ? $BT[1]['file'] : null;

        # Add backtrace if error
        if ($type === 'err') {
            $message .= "\n";

            # We already know this and previous step
            unset($BT[0], $BT[1]);
            
            foreach ($BT as $Trace) {
                $trace  = '';
                $trace .= isset($Trace['class']) ? $Trace['class'] : '';
                $trace .= isset($Trace['type']) ? $Trace['type'] : '';
                $trace .= isset($Trace['function']) ? $Trace['function'] . '()' : '';
                if (isset($Trace['file'])) {
                    $trace .= ' [' . basename($Trace['file']) . ' ' . $Trace['line'] . ']';
                }
                $message .= "\n{$trace}";
            }
        }

        // Create new item
        $log_item = array
        (
            'date_time' => date('Y-m-d H:i:s'),
            'type'      => $type,
            'message'   => $message,
            'line'      => $line,
            'file'      => $file
        );

        // Trigger event telling to add new item
        Event::trigger('/avrelia/core/log/add', $log_item);

        // Add item to the stack
        self::$logs[] = $log_item;

        return $type == 'inf' ? true : false;
    }

    /**
     * Add information, warning, error to the log
     * @param  string $message
     * @return boolean          True always returned when adding information.
     */
    public static function inf($message) { return self::add($message, 'inf'); }
    public static function war($message) { return self::add($message, 'war'); }
    public static function err($message) { return self::add($message, 'err'); }

    /**
     * Is particular (or any) log message set?
     * @param  mixed $type inf|war|err -- or false
     * @return boolean
     */
    public static function has($type=false)
    {
        if (!$type) {
            return !empty(self::$logs);
        }
        else {
            if (empty(self::$logs)) { return false; }
            if (!is_array($type))   { $type = array($type); }

            foreach (self::$logs as $log) {
                if (in_array($log['type'], $type)) { return true; }
            }
        }
    }

    /**
     * Return particular type of logs, or all of them, as an array.
     * @param  mixed $type array inf|war|err -- or false
     * @return array
     */
    public static function as_array($type=false)
    {
        if (!self::has())     { return array(); }
        if (!$type)           { return self::$logs; }
        if (!is_array($type)) { $type = array($type); }

        $collection = array();
        foreach (self::$logs as $log) {
            if (in_array($log['type'], $type)) { $collection[] = $log; }
        }

        return $collection;
    }

    /**
     * Will add benchmark informations to log
     * --
     * @return void
     */
    public static function add_benchmarks()
    {
        # Add Memory usage..
        $memory       = Benchmark::get_memory_usage();
        $memory_bytes = Benchmark::get_memory_usage(false);
        self::inf("Memory usage: {$memory_bytes} bytes / " . $memory . 
                    " the memory limist is: " . ini_get('memory_limit'));

        # Add Total Loading Time Of System
        $sys_timer = Benchmark::get_timer('system');
        self::add(
            "Total processing time: {$sys_timer}", 
            ((float)$sys_timer > 5 ? 'war' : 'inf' ));
    }
}