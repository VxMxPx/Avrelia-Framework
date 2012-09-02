<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

# ============================================================ #
#                WARNING: DON'T EDIT THIS FILE!                #
# ------------------------------------------------------------ #
#  If you want to change anything, put the file with same name #
#      into application/config folder, to rewrite values.      #
# ============================================================ #

/* -----------------------------------------------------------------------------
 * Absolute paths, for application, public, database and system
 */
if (!defined('SYSPATH')) define('SYSPATH', realpath(dirname(__FILE__).'/../'));
if (!defined('APPPATH')) define('APPPATH', realpath(SYSPATH.'/../application'));
if (!defined('PUBPATH')) define('PUBPATH', realpath(APPPATH.'/public'));
if (!defined('DATPATH')) define('DATPATH', realpath(APPPATH.'/database'));

/* -----------------------------------------------------------------------------
 * Turn off when in production version
 */
if (!defined('DEBUG')) define('DEBUG', false);

/* -----------------------------------------------------------------------------
 * Testing environment variables.
 * Test initializer will set TESTING to true, and this will be skipped.
 */
if (!defined('TESTING'))  define('TESTING', false);
if (!defined('TESTPATH')) define('TESTPATH', realpath(SYSPATH.'/../tests'));

/* -----------------------------------------------------------------------------
 * Reserved constants for common use.
 */
!defined('FILE_DUPLICATE_REWRITE')
	? define('FILE_DUPLICATE_REWRITE', 1)
	: trigger_error('Use of reserved constant: `FILE_DUPLICATE_REWRITE`.');

!defined('FILE_DUPLICATE_UNIQUE')
	? define('FILE_DUPLICATE_UNIQUE', 2)
	: trigger_error('Use of reserved constant: `FILE_DUPLICATE_UNIQUE`.');

!defined('FILE_DUPLICATE_ERROR')
	? define('FILE_DUPLICATE_ERROR', 3)
	: trigger_error('Use of reserved constant: `FILE_DUPLICATE_EXCEPTION`.');

!defined('FILE_DUPLICATE_SILENT')
	? define('FILE_DUPLICATE_SILENT', 4)
	: trigger_error('Use of reserved constant: `FILE_DUPLICATE_SILENT`.');