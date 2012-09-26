<?php namespace Avrelia\Plug; if (!defined('AVRELIA')) die('Access is denied!');

use Avrelia\Core\Plug as Plug;

/**
 * Migrate Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Migrate
{
	public static function _on_enable_()
	{
		# Plug need the following:
		Plug::need('Avrelia\\Plug\\Database');

		return true;
	}
}