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
        date_default_timezone_set(Cfg::get('system/timezone', 'UTC'));

        # Register autoloader
        spl_autoload_register('Loader::get');

        # Set Timer...
        Benchmark::set_timer('system');

        # First Log Entry...
        Log::inf('PHP version: ' . PHP_VERSION . ' | Framework version: ' . self::VERSION);

        # Now scan and autoload plugs
        if (Cfg::get('plug/enabled')) 
            { Plug::load(Cfg::get('plug/auto_load')); }

        # Trigger event after framework initialization
        Event::trigger('/core/avrelia/initialize');

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

        // Check if application is offline
        if (Cfg::get('system/offline')) {
            Route::trigger('@OFFLINE');
        }
        else {
            Route::trigger(Input::get_path_info());
        }

        return $this;
    }

    /**
     * Return otuput.
     * --
     * @return string
     */
    public function output()
    {
        return Output::get();
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

        # Final event
        Event::trigger('/core/avrelia/destruct');

        # Write final log
        if (Cfg::get('log/enabled') && Cfg::get('log/write_individual') === false) {
            Log::save_all(false);
        }
    }
}
