<?php

/**
 * Avrelia
 * ----
 * Index
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2011-04-01
 */


# Define Avrelia (needed to prevent direct calls to files)
define('AVRELIA', true);
define('IN_CLI', false);


# Turn Error Reporting ON for All ++ Set Error Displaying
error_reporting(E_ALL);
ini_set('display_errors', true);

# Try to include paths file (change this in case of problems!)
include(realpath(dirname(__FILE__).'/../config/define.php'));

# Load Framework Init
if (file_exists(SYSPATH . '/core/avrelia.php')) {
	include(SYSPATH . '/core/avrelia.php');
}
else {
	trigger_error("Can't load `avrelia.php` file.", E_USER_ERROR);
}

# We should have Avrelia now...
$Avrelia = new Avrelia();
$Avrelia->init()->boot();

# Get Ouput At The End
echo Output::Get();
