<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia Class
 * -----------------------------------------------------------------------------
 * Will initialite the framework, set some constants, return dispatcher.
 * ----
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Avrelia
{
    const VERSION   = '1.20';
    const NAME      = 'Avrelia Framework';
    const AUTHOR    = 'Avrelia';
    const FOUNDER   = 'Marko Gajšt';
    const WEBSITE   = 'http://framework.avrelia.com';
    const COPYRIGHT = '2010-2012';

    /**
     * Will, as name suggest, initialize core framework. After that dispatcher
     * will be returned, and `boot` method can be called.
     * ---
     * @return object $this
     */
    public function initialize()
    {
        /* ---------------------------------------------------------------------
         * For sure we need at least version 5.0 of PHP.
         */
        if (!defined('PHP_VERSION') || ((float) PHP_VERSION < 5)) {
            trigger_error('You need PHP version 5.0 or more.', E_USER_ERROR);
        }

        /* ---------------------------------------------------------------------
         * Magic Quotes can cause only mess, so better to turn them off. In case
         * we're running PHP 5.3 they already supposed to be off.
         */
        if ((float)PHP_VERSION < 5.3) {
            set_magic_quotes_runtime(0);
            ini_set('magic_quotes_gpc', 0);
            ini_set('magic_quotes_sybase', 0);
        }

        /* ---------------------------------------------------------------------
         * Ensure backward compatibility with versions of PHP bellow 5.2, 5.3 
         */
        if (!defined('E_RECOVERABLE_ERROR')) define('E_RECOVERABLE_ERROR', 4096); # 5.2.0
        if (!defined('E_DEPRECATED'))        define('E_DEPRECATED',        8192); # 5.3.0
        if (!defined('E_USER_DEPRECATED'))   define('E_USER_DEPRECATED',  16384); # 5.3.0

        /* ---------------------------------------------------------------------
         * If error handler function doesn't exists it means, functions.php file
         * wasn't included yet, so we'll do this now.
         */
        function_exists('avrelia_error_handler')
            or include(realpath(SYSPATH . '/core/functions.php'));

        /* ---------------------------------------------------------------------
         * Load exceptions
         */
        file_exists(app_path('core/exceptions.php'))
            and include(app_path('core/exceptions.php'));

        include(sys_path('core/exceptions.php'));

        # Error Handling
        set_error_handler('avrelia_error_handler');

        /* ---------------------------------------------------------------------
         * Load initializer
         */
        include(sys_path('core/initializer.php'));

        # Default timezone
        date_default_timezone_set(Cfg::get('core/timezone', 'UTC'));

        # Set Timer...
        Benchmark::set_timer('system');

        # First Log Entry...
        Log::inf('PHP version: ' . PHP_VERSION . ' | Framework version: ' . self::VERSION);

        # Now scan and autoload plugs
        if (Cfg::get('core/plug/enabled')) 
            { Plug::load(Cfg::get('core/plug/auto_load')); }

        # Trigger event after framework initialization
        Event::trigger('/avrelia/core/initialize');

        return $this;
    }

    /**
     * Boot the system - will find particular route and execute it.
     * --
     * @return object $this
     */
    public function boot()
    {
        Loader::get_core('Dispatcher');
        Loader::get_core('Route');

        // Trigger @BEFORE action
        Route::trigger('@BEFORE');

        // Check if application is offline
        if (Cfg::get('core/offline')) {
            Route::trigger('@OFFLINE');
        }
        else {
            Route::trigger(trim(Input::get_path_info(), '/'));
        }

        // Trigger @AFTER action
        Route::trigger('@AFTER');

        return $this;
    }

    /**
     * Apply headers and return output as string. Also trigger final action.
     * --
     * @return string
     */
    public function do_output()
    {
        Http::apply_headers();
        $output = Output::as_string();
        
        Event::trigger('/avrelia/core/do_output', $output);

        return $output;
    }

    /**
     * Executed at the very end of everything
     * --
     * @return void
     */
    public function __destruct()
    {
        if (!class_exists('Event', false) 
            || !class_exists('Cfg', false) 
            || !class_exists('Log', false)
        ) {
            return false;
        }

        // Final event
        Event::trigger('/avrelia/core/destruct');
    }
}
