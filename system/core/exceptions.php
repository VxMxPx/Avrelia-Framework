<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

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
	{ class AvreliaException extends Exception {} }

# When: creating, deleting, moving, copying, reading file...
if (!class_exists('FileSystem_AvreliaException', false)) 
	{ class File_AvreliaException extends Exception {} }