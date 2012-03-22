<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Help cli
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2011-08-31
 */

class cliHelp
{

	/**
	 * Print out the help..
	 */
	public static function _empty()
	{
		AvreliaCli::Say('INF', "Available commands:");

		if (is_dir(ds(SYSPATH.'/scripts'))) {
			$ListSys = scandir(ds(SYSPATH.'/scripts'));
		}
		else {
			$ListSys = array();
		}

		if (is_dir(ds(APPPATH.'/scripts'))) {
			$ListApp = scandir(ds(APPPATH.'/scripts'));
		}
		else {
			$ListApp = array();
		}

		$ListAll = array_merge($ListSys, $ListApp);

		if (!empty($ListAll)) {
			foreach ($ListAll as $comm) {
				if (substr($comm, -4, 4) != '.php') continue;
				AvreliaCli::Say('INF', "  " . substr($comm, 0, -4));
			}
		}
	}
	//-

}
//--
