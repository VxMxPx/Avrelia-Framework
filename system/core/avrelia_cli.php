<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Avrelia CLI
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2009, Avrelia.com
 * @license    http://avrelia.com/license
 * @link       http://avrelia.com
 * @since      Version 0.80
 * @since      Tue May 03 13:45:25 2011
 */

// Colors Definitions...


class AvreliaCli
{
	private $AllCommands = array();


	function __construct($Params)
	{
		if (!isset($Params[1]))
		{
			self::Say('WAR', "Plase enter the command. Type `help` for list of commands.");
			return;
		}

		$class = 'cli'.toCamelCase($Params[1]);
		$file  = strtolower(str_replace('.', '', $Params[1]));

		if (!class_exists($class, false))
		{
			if (file_exists(ds(APPPATH.'/scripts/'.$file.'.php')))
			{
				include(ds(APPPATH.'/scripts/'.$file.'.php'));
				if (!class_exists($class, false))
				{
					AvreliaCli::Say('ERR', "File was found, but class couldn't be constucted.");
					return;
				}
			}
			elseif (file_exists(ds(SYSPATH.'/scripts/'.$file.'.php')))
			{
				include(ds(SYSPATH.'/scripts/'.$file.'.php'));
				if (!class_exists($class, false))
				{
					AvreliaCli::Say('ERR', "File was found, but class couldn't be constucted.");
					return;
				}
			}
			else
			{
				AvreliaCli::Say('WAR', "Invalid command.");
				return;
			}
		}

		$Params[2] = (isset($Params[2])) ? $Params[2] : '_empty';

		if (method_exists($class, $Params[2]))
		{
			$Commands = array_slice($Params, 3);
			return call_user_func_array(array($class, $Params[2]), $Commands);
		}
		elseif (method_exists($class, '_empty'))
		{
			$Commands = array_slice($Params, 2);
			return call_user_func_array(array($class, '_empty'), $Commands);
		}
		else
		{
			self::Say('WAR', "Undefined action `{$Params[2]}`!");
			return;
		}
	}
	//-

	/**
	 * Will print out the message
	 *
	 * @param string $type
	 * 		INF -- Regular white message
	 * 		ERR -- Red message
	 * 		WAR -- Yellow message
	 * 		OK  -- Green message
	 * @param string $message
	 * @param bool   $newLine -- shoudl message be in new line
	 *
	 * @return void
	 */
	public static function Say($type, $message, $newLine=true)
	{
		switch (strtolower($type))
		{
			case 'err':
				$color = "\x1b[31;01m";
				break;

			case 'war':
				$color = "\x1b[33;01m";
				break;

			case 'ok':
				$color = "\x1b[32;01m";
				break;

			default:
				$color = null;
		}

		echo
			(!is_null($color) ? $color : ''),
			$message,
			"\x1b[39;49;00m";

		if ($newLine)
		{
			echo "\n";
		}

		flush();
	}
	//-

}
//--
