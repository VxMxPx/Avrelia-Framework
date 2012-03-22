<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Interactive Console
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2011-12-24
 */

class cliAvr
{
	/**
	 * Print the console!
	 */
	public static function _empty()
	{
		# Main loop...
		do {
			if (function_exists('readline')) {
				$stdin = readline('avrelia> ');
				readline_add_history($stdin);
			}
			else {
				echo "avrelia> ";
				$stdin = fread(STDIN, 8192);
			}
			$stdin    = trim($stdin);
			$continue = ($stdin == 'exit' || $stdin == '\q') ? false : true;

			if ($continue) {
				eval('$val = ' . (substr($stdin,-1,1) == ';' ? $stdin : $stdin . ';') . ' echo dumpVar($val, false, true);');
				echo "\n";
			}
		} while($continue == true);

		# At the end...
		echo "See you!\n";
	}
	//-
}
//--
