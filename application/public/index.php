<?php

/**
 * Index
 * -----------------------------------------------------------------------------
 * This is the file which start web framework, for CLI, see system/dot;
 * Some basic setup and initialization is made here.
 * ----
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */


/* -----------------------------------------------------------------------------
 * Define AVRELIA constant; all system files requires this to be set, to prevent
 * unwanted, direct inclusions of file.
 */
define('AVRELIA', true);


/**
 * Set timezone to default for now. Later we'll read it from the config file
 * and set it to the correct value.
 */
date_default_timezone_set('UTC');


/* -----------------------------------------------------------------------------
 * Just in case something goes wrong on this phase we wanna show it. Latter on,
 * error displaying and reporting will be reconfigured according to your setup.
 */
error_reporting(E_ALL);


/* -----------------------------------------------------------------------------
 * This is the application path, where your config, plugs, models, views, and
 * controllers are stored. By default we're looking for this path one level
 * bellow (current) public path.
 * We'll also define system path, just in case it wasn't defined as constant.
 */
$app_path = realpath(dirname(__FILE__).'/../');


/* -----------------------------------------------------------------------------
 * The define file contains constants like: APPPATH, SYSPATH, PUBPATH, DATPATH,
 * change this file if you were changing the paths.
 * Additionally you can add any of your own costume constants to it.
 */
if (file_exists($app_path.'/config/define.local.php')) {
    include($app_path.'/config/define.local.php');
}

if (file_exists($app_path.'/config/define.php')) {
    include($app_path.'/config/define.php');
}

if (defined('SYSPATH')) { $sys_path = SYSPATH; }
else { $sys_path = realpath(dirname(__FILE__).'/../../system'); }

if (file_exists($sys_path.'/config/define.php')) {
    include($sys_path.'/config/define.php');
}


/* -----------------------------------------------------------------------------
 * If we have debug turned on, we'll display errors
 */
ini_set('display_errors', DEBUG);


/* -----------------------------------------------------------------------------
 * Avrelia is core class, which initialize the framework, and if successful 
 * return dispatcher.
 */
if (file_exists(SYSPATH . '/core/avrelia.php')) {
    include(SYSPATH . '/core/avrelia.php');
} 
else {
    trigger_error("Can't load `avrelia.php` file.", E_USER_ERROR);
}


/* -----------------------------------------------------------------------------
 * First we initialize the core framework, this will return dispatcher,
 * which we can use if we wanna boot the system (check routes, use MVC).
 */
$avrelia_framework = new Avrelia();
$avrelia_framework
    ->initialize()
    ->boot();


/* -----------------------------------------------------------------------------
 * No matter what happened, error, JSON request, we use the `Output::get` method
 * to display / return it.
 * 
 * Also this is out last action, so we exit here.
 */
exit(Output::get());