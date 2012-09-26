<?php namespace Avrelia\Exception; if (!defined('AVRELIA')) die('Access is denied!');

/**
 * Exceptions Classes
 * -----------------------------------------------------------------------------
 * For the reasons of pure simplicity, all exception classes are collected here.
 * To overwrite any or all of them, or to define your own, just create file 
 * exceptions.php in application/core directory.
 * ----
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */

# Base AvreliaException; all exceptions extends this one.
if (!class_exists('AvreliaException', false)) 
	{ class Base extends \Exception {} }

# When: creating, deleting, moving, copying, reading file...
if (!class_exists('Avrelia\\Exception\\FileSystem', false)) 
	{ class FileSystem extends Base {} }

# Any value problem (wrong variable's value was passes in, etc...)
if (!class_exists('Avrelia\\Exception\\ValueError', false))
	{ class ValueError extends Base {} }

# General database-related exception
if (!class_exists('Avrelia\\Exception\\Database', false))
    { class Database extends Base {} }

# General plug-related exception
if (!class_exists('Avrelia\\Exception\\Plug', false))
	{ class Plug extends Base {} }