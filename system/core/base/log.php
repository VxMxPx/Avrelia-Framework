<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Log Base Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Log_Base
{
    # All log items
    protected static $logs = array();

    # Turn everything on/off.
    # Useful when we're saving log message (individualy), if in that process 
    # error happened, it may cause infinite loop.
    protected static $enabled = false;

    # Full path to log file, if this is set to false, the log won't be saved.
    protected static $filename = false;
    
    # Log types. Select which type of messages should be saved.
    # Available options are: inf, err, war; To log "INF" isn't recommended.
    protected static $types = array('err', 'war');

    # If set to true, every log message will be saved individually;
    # If set to false all messages will be saved at the end of script execution.
    protected static $write_individual = true;

    # Create always fresh file, so it should be unique filename.
    protected static $filename_if_fatal = null;

    # This template is used when outputting log as an HTML
    protected static $templates = array(
        'wrap' =>
        '<div style="background-color:#222; color:#999; font-size:14px;">
        {{items}}</div>',

        'html_item' =>
        '<div style="text-align:left; border-bottom: 2px solid #444;
        padding:10px; background-color:#{{background}}; class="type_{{class}}">
            <div style="text-align:left;margin:0 0 10px 0;padding:0px;">
                <pre style="color:#{{type_color}}; white-space: pre-wrap;">{{message}}</pre>
            </div>
            <p style="text-align:left;font-family:Sans, sans-serif;margin:0px;">
                <small style="font-size:12px;">{{date_time}}: {{type}} | 
                    <span title="{{file}}">{{file_short}}</span>: {{line}}
                </small>
            </p>
        </div>',
        
        'colors' => 
        array(
            'inf'  => '99cc66', 
            'err'  => 'cc6666', 
            'war'  => 'cc9966',
            'odd'  => '111',
            'even' => '222'),
    );

    /**
     * Init the Log
     * --
     * @param  array $Config  Log configurations array!
     * @return void
     */
    public static function _on_include_()
    {
        # If this fail, then we'll save log to file
        self::$filename          = Cfg::get('log/path', false);
        self::$filename_if_fatal = Cfg::get('log/fatal_path');

        self::$types             = Cfg::get('log/types');
        self::$write_individual  = Cfg::get('log/write_individual');
        self::$enabled           = Cfg::get('log/enabled');
    }

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

        # Write this message into file?
        self::_write_line($type, $message, $line, $file);

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

        # Add Item to An Array
        self::$logs[] = array
        (
            'date_time' => date('Y-m-d H:i:s'),
            'type'      => $type,
            'message'   => $message,
            'line'      => $line,
            'file'      => $file
        );


        if (
            is_cli() 
            && in_array($type, array('war', 'err')) 
            && class_exists('Dot', false)
        ) {
            Dot::out($type, $message);
            Dot::out($type, $file . " " . $line);
            Dot::out($type, str_repeat('-', 40));
        }

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
        if (!self::has()) { return array(); }
        if (!$type) { return self::$logs; }

        $collection = array();
        foreach (self::$logs as $log) {
            if (in_array($log['type'], $type)) { $collection[] = $log; }
        }

        return $collection;
    }

    /**
     * Return particular type of logs, or all of them, as string.
     * @param  mixed $type inf|war|err -- or false
     * @return string
     */
    public static function as_string($type=false)
    {
        if (!self::has()) { return null; }

        $collection = array();
        foreach (self::$logs as $log) {
            if ($type && in_array($log['type'], $type)) { continue; }
            $collection[] = "Date/Time: {$log['date_time']}\n".
                            "Type: {$log['type']}\n".
                            "Message: {$log['message']}\n".
                            "File: {$log['file']}\n".
                            "Line: {$log['line']}\n".
                            str_repeat('-', 50) . "\n";
        }

        return implode("\n", $collection);
    }

    /**
     * Return particular type of logs, or all of them, as HTML.
     * @param  mixed $type inf|war|err -- or false
     * @return string
     */
    public static function as_html($type=false)
    {
        if (!self::has()) { return null; }

        $collection = array();
        $i = 0;
        
        foreach (self::$logs as $log) {
            if ($type && in_array($log['type'], $type)) { continue; }
            # To remove absolute long paths in case of application, system or
            # public path.
            if (strpos($log['file'], SYSPATH) !== false)
                { $file = substr($log['file'], strlen(dirname(SYSPATH))+1); }
            elseif (strpos($log['file'],APPPATH) !== false)
                { $file = substr($log['file'], strlen(dirname(APPPATH))+1); }
            elseif (strpos($log['file'],PUBPATH) !== false)
                { $file = substr($log['file'], strlen(dirname(PUBPATH))+1); }
            else
                { $file = $log['file']; }

            $collection[] = str_replace(
                array(
                    '{{background}}', 
                    '{{class}}', 
                    '{{type_color}}',
                    '{{message}}', 
                    '{{date_time}}', 
                    '{{type}}', 
                    '{{file}}',
                    '{{file_short}}', 
                    '{{line}}'),
                array(
                    (($i == 1) 
                        ? self::$templates['colors']['odd'] 
                        : self::$templates['colors']['even']),
                    $log['type'],
                    self::$templates['colors'][$log['type']],
                    $log['message'],
                    $log['date_time'],
                    $log['type'],
                    $log['file'],
                    $file,
                    $log['line']
                    ), 
                self::$templates['html_item']);

            $i = $i == 1 ? 0 : 1;
        }

        return str_replace(
                    '{{items}}', 
                    implode('', $collection), 
                    self::$templates['wrap']);
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

    /**
     * Write Whole Log Array into file
     * --
     * @param   boolean $was_fatal -- if was fatal error, the location of file
     *                               is different.
     * @return  boolean
     */
    public static function save_all($was_fatal=false)
    {
        if (!self::_can_write()) { return false; }

        # Add benchmarks
        self::add_benchmarks();

        if ($was_fatal) {
            if (!self::$filename_if_fatal) {
                return false;
            }
            else {
                $filename = self::$filename_if_fatal;
                $logs     = self::as_html();
            }
        }
        else {
            if (!self::$filename) {
                return false;
            }
            else {
                $filename = self::$filename;
                $logs     = self::as_string(self::$types);
            }
        }

        if (self::_can_write() || $was_fatal) {
            self::$enabled = false;
            $return = FileSystem::Write($logs, $filename, true, 0777);
            @chmod($filename, 0777); // Always full write to file
            self::$enabled = true;
            return $return;
        }
        else {
            return false;
        }
    }

    /**
     * Write Log Message To File
     * --
     * @param  string  $type       err|inf|war : error, information, warning
     * @param  string  $message    Plain English message
     * @return boolean
     */
    private static function _write_line($type, $message, $line, $file)
    {
        if (!self::_can_write($type, true)) { return false; }
        if (!self::$filename) { return false; }

        $type    = strtolower($type);
        $to_file = 'Date/time: ' . date('Y-m-d H:i:s') . "\n" .
                    "Type: {$type}\n" .
                    "Message: " . str_replace(
                        '&lt;br /&gt;', '<br />', 
                        vString::EncodeEntities($message)) . "\n" .
                    "File: {$file}\n" .
                    "Line: {$line}\n" .
                    str_repeat('-', 50) . "\n";

        self::$enabled = false;
        $return = FileSystem::Write($to_file, self::$filename, true, 0777);
        @chmod(self::$filename, 0777); // Always full write to file
        self::$enabled = true;

        return $return;
    }

    /**
     * Will Check If Log Can Be Written
     * --
     * @param  string  $type           Which type are we checking? 
     *                                 eg: inf, war, err (false to ignore types)
     * @param  boolean $is_individual 
     * @return boolean
     */
    private static function _can_write($type=false, $is_individual=false)
    {
        # Check if is enabled first...
        if (!self::$enabled) { return false; }

        # Is INF
        if (!isset(self::$types['inf']) || self::$types['inf'] == false) {
            if ($type == 'inf') return false;
        }

        # Is WAR
        if (!isset(self::$types['war']) || self::$types['war'] == false) {
            if ($type == 'war') return false;
        }

        # Is ERR
        if (!isset(self::$types['err']) || self::$types['err'] == false) {
            if ($type == 'err') return false;
        }

        # Is Individual?
        if ($is_individual && !self::$write_individual) {
            return false;
        }

        # Everything is alright...
        return true;
    }
}