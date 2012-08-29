<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

# ============================================================ #
#                WARNING: DON'T EDIT THIS FILE!                #
# ------------------------------------------------------------ #
#  If you want to change anything, put the file with same name #
#      into application/config folder, to rewrite values.      #
# ============================================================ #

/* -----------------------------------------------------------------------------
 * Define absolute paths, for application, public, database and system
 */
if (!defined('SYSPATH')) define('SYSPATH', realpath(dirname(__FILE__).'/../'));
if (!defined('APPPATH')) define('APPPATH', realpath(SYSPATH.'/../application'));
if (!defined('PUBPATH')) define('PUBPATH', realpath(APPPATH.'/public'));
if (!defined('DATPATH')) define('DATPATH', realpath(APPPATH.'/database'));

/* -----------------------------------------------------------------------------
 * This should be removed (turned off when in production version)!
 */
if (!defined('DEBUG')) define('DEBUG', false);