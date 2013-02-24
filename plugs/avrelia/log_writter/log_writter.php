<?php namespace Plug\Avrelia; if (!defined('AVRELIA')) die('Access is denied!');

use Avrelia\Core\Plug as Plug;
use Avrelia\Core\Cfg as Cfg;
use Avrelia\Core\Log as Log;
use Avrelia\Core\Dot as Dot;
use Avrelia\Core\FileSystem as FileSystem;
use Avrelia\Core\Event as Event;
use Avrelia\Core\vString as vString;

class LogWritter
{
    // Turn everything on/off.
    // Useful when we're saving log message (individualy), if in that process 
    // error happened, it may cause infinite loop.
    protected static $to_file = false;

    // When set to true, special file will be created, which will contain all logs 
    // for whole session; all messages will be included.
    // This will write logs even if `to_file` is set to false.
    protected static $save_fatal = false;

    // The filename of regular log item
    protected static $file_name = false;
    
    // Log types. Which type of messages should be saved. Options: err, war, inf
    protected static $types = array('err', 'war');

    // True: every log message will be saved individually.
    // False: all logs will be saved at the end of script execution.
    protected static $save_individual = true;

    // Filename for fatal error. If `save_fatal` is set to true.
    protected static $fatal_file = null;

    // This templates are used when outputting log as an HTML
    protected static $templates = array(
        'wrap'     => null,
        'item'     => null,
        'colors'   => array
        (
            'inf'  => '9c6', 
            'err'  => 'c66', 
            'war'  => 'c96',
            'odd'  => '111',
            'even' => '222',
        ),
    );

    /**
     * Try to create log directory.
     * --
     * @return boolean
     */
    public static function _on_enable_()
    {
        // Get config for this item
        Plug::get_config(__FILE__);

        $directory = ds(Cfg::get('plugs/log_writter/directory'));

        if (!is_dir($directory)) {
            return !!FileSystem::MakeDir($directory, true, 0777);
        }

        return true;
    }

    /**
     * Init the Log
     * --
     * @param  array $Config  Log configurations array!
     * @return void
     */
    public static function _on_include_()
    {
        // Get config for this item
        Plug::get_config(__FILE__);

        $directory  = Cfg::get('plugs/log_writter/directory');
        $filename   = Cfg::get('plugs/log_writter/file_name');
        $fatal_file = Cfg::get('plugs/log_writter/fatal_file');


        # If this fail, then we'll save log to file
        self::$file_name  = ds($directory, $filename);
        self::$fatal_file = ds($directory, $fatal_file);

        self::$types            = Cfg::get('plugs/log_writter/types');
        self::$save_individual  = Cfg::get('plugs/log_writter/save_individual');
        self::$to_file          = Cfg::get('plugs/log_writter/to_file');
        self::$save_fatal       = Cfg::get('plugs/log_writter/save_fatal');


        // Get current directory
        $dir = dirname(__FILE__);

        // Load templates
        self::$templates['wrap'] = FileSystem::Read(ds($dir, 'template/wrap.html'));
        self::$templates['item'] = FileSystem::Read(ds($dir, 'template/item.html'));

        // Register events
        self::_register_events();

        return true;
    }

    /**
     * Register various events.
     * @return void
     */
    protected static function _register_events()
    {
        // Even individual log item added
        Event::on('/avrelia/core/log/add', function($log_item) {
            self::_write_line(
                    $log_item['type'], 
                    $log_item['message'], 
                    $log_item['line'], 
                    $log_item['file']);

            if (is_cli() && in_array($log_item['type'], array('war', 'err'))) {
                Dot::out($log_item['type'], $log_item['message']);
                Dot::out($log_item['type'], $log_item['file'] . " " . $log_item['line']);
                Dot::out($log_item['type'], str_repeat('-', 40));
            }
        });

        // On the end of session
        Event::on('/avrelia/core/destruct', function() {
            if (self::$to_file && self::$save_individual === false) {
                self::save_all(false);
            }
        });

        // On fatal error
        Event::on('/avrelia/core/fatal_error', function() {
            if (self::$save_fatal) { 
                self::save_all(true); 
            }
        });

        // Errro report
        Event::on('/avrelia/core/fatal_error/report', function() {
            if (is_cli()) 
                { $error_report = self::as_string('war', 'err'); }
            elseif (DEBUG && !is_cli()) 
                { $error_report = self::as_html(); }

            return $error_report;
        });
    }

    /**
     * Return particular type of logs, or all of them, as string.
     * @param  mixed $type inf|war|err -- or false
     * @return string
     */
    public static function as_string($type=false)
    {
        if (!Log::has()) { return null; }
        if ($type && !is_array($type)) { $type = array($type); }

        $logs = Log::as_array();

        $collection = array();
        foreach ($logs as $log) {
            if (!$type || in_array($log['type'], $type)) {
                $collection[] = "Date/Time: {$log['date_time']}\n".
                                "Type: {$log['type']}\n".
                                "Message: {$log['message']}\n".
                                "File: {$log['file']}\n".
                                "Line: {$log['line']}\n".
                                str_repeat('-', 50) . "\n";
                }
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
        if (!Log::has()) { return null; }
        if ($type && !is_array($type)) { $type = array($type); }

        $logs = Log::as_array();

        $collection = array();
        $i = 0;
        
        foreach ($logs as $log) {
            if ($type && !in_array($log['type'], $type)) { continue; }
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
                    htmlspecialchars($log['message']),
                    $log['date_time'],
                    $log['type'],
                    $log['file'],
                    $file,
                    $log['line']
                    ), 
                self::$templates['item']);

            $i = $i == 1 ? 0 : 1;
        }

        return str_replace(
                    '{{items}}', 
                    implode('', $collection), 
                    self::$templates['wrap']);
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
        Log::add_benchmarks();

        if ($was_fatal) {
            if (!self::$fatal_file) {
                return false;
            }
            else {
                $filename = self::$fatal_file;
                $logs     = self::as_html();
            }
        }
        else {
            if (!self::$file_name) {
                return false;
            }
            else {
                $filename = self::$file_name;
                $logs     = self::as_string(self::$types);
            }
        }

        if (self::_can_write() || $was_fatal) {
            self::$to_file = false;
            $return = FileSystem::Write($logs, $filename, true, 0777);
            @chmod($filename, 0777); // Always full write to file
            self::$to_file = true;
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
        if (!self::$file_name) { return false; }

        $type    = strtolower($type);
        $to_file = 'Date/time: ' . date('Y-m-d H:i:s') . "\n" .
                    "Type: {$type}\n" .
                    "Message: " . str_replace(
                        '&lt;br /&gt;', '<br />', 
                        vString::EncodeEntities($message)) . "\n" .
                    "File: {$file}\n" .
                    "Line: {$line}\n" .
                    str_repeat('-', 50) . "\n";

        self::$to_file = false;
        $return = FileSystem::Write($to_file, self::$file_name, true, 0777);
        @chmod(self::$file_name, 0777); // Always full write to file
        self::$to_file = true;

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
        if (!self::$to_file) { return false; }

        # Is INF
        if (!in_array($type, self::$types)) { return false; }

        # Is Individual?
        if ($is_individual && !self::$save_individual) { return false; }

        # Everything is alright...
        return true;
    }
}