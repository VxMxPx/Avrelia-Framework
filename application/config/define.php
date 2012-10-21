<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/* -----------------------------------------------------------------------------
 * Absolute paths, for application, public, database and system
 */
if (!defined('APPPATH')) define('APPPATH', realpath(dirname(__FILE__).'/../'));
if (!defined('PUBPATH')) define('PUBPATH', realpath(APPPATH.'/public'));
if (!defined('DATPATH')) define('DATPATH', realpath(APPPATH.'/database'));
if (!defined('SYSPATH')) define('SYSPATH', realpath(APPPATH.'/../avrelia'));

/* -----------------------------------------------------------------------------
 * Turn off when in production version
 */
if (!defined('DEBUG')) define('DEBUG', false);