<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Log Library
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2009, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.60
 * @since      Date 2009-08-18
 */


class Log
{
	# Array of all log items
	private static $Logs = array();

	# Turn everything off.
	# This is useful when we're saving log message (individualy),
	# if In that process error happened, it may cause infinite loop.
	private static $enabled = false;

	# Full path to log file, if this is set to false, the log won't be saved
	private static $filename = null;

	# Log types. Select which type of messages should be saved.
	# Available options are: INF, ERR, WAR; To log "INF" isn't recommended.
	private static $Types = array('ERR', 'WAR');

	# If set to true, every log message will be saved individually;
	# If set to false, all messages will be saved at the end of script execution
	private static $writeIndividual = true;

	# This won't append, but create always fresh file, so it should be unique filename
	private static $filenameOnFatal = null;


	/**
	 * Init the Log
	 * ---
	 * @param array $Config -- Log configurations array!
	 * ---
	 * @return void
	 */
	public static function Init()
	{
		# If this fail, then we'll save log to file
		self::$filename        = Cfg::Get('Log/path');
		self::$filenameOnFatal = Cfg::Get('Log/fatal_path');

		self::$Types           = Cfg::Get('Log/Types');
		self::$writeIndividual = Cfg::Get('Log/write_individual');
		self::$enabled         = Cfg::Get('Log/enabled');
	}
	//-


	/*  ****************************************************** *
	 *          Add / Get
	 *  **************************************  */

	/**
	 * Add System Log Message
	 * ---
	 * @param string $type     -- ERR|INF|WAR : error, informationm, warning
	 * @param string $message  -- plain englisg message
	 * @param string $line     -- __LINE__
	 * @param string $file     -- __FILE__
	 * ---
	 * @return bool (depends on type provided INF - true; anything else - false)
	 */
	public static function Add($type, $message, $line, $file)
	{
		$type = strtoupper($type);

		# Write this message into file?
		self::Write($type, $message, $line, $file);

		# Add Item to An Array
		self::$Logs[] = array
		(
			'date_time' => date('Y-m-d H:i:s'),
			'type'      => $type,
			'message'   => $message,
			'line'      => $line,
			'file'      => $file
		);

		if (IN_CLI && in_array($type, array('OK', 'WAR', 'ERR')) && class_exists('AvreliaCli', false)) {
			AvreliaCli::Say($type, $message);
			AvreliaCli::Say($type, $file . " " . $line);
			AvreliaCli::Say($type, str_repeat('-', 40));
		}

		# Return
		return $type == 'INF' || $type == 'OK' ? true : false;
	}
	//-

	/**
	 * Print out all LOG messages
	 * ---
	 * @param bool  $asString -- return an array, or string?
	 *   1 raw (for consoles),
	 *   2 as HTML,
	 *   3 raw for browser (with <br />)
	 * @param array $filterTo -- will filder log result to specific type(s) -- example $filterTo = array('WAR', 'ERR');
	 * ---
	 * @return mixed
	 */
	public static function Get($asString=false, $filterTo=false)
	{
		# Add Memory usage..
		$memory = Benchmark::GetMemoryUsage(true, false);
		self::Add('INF', "Memory usage: {$memory} -- " . FileSystem::FormatSize($memory) . " the memory limist is: " . ini_get('memory_limit'), __LINE__, __FILE__);

		# Add Total Loading Time Of System
		$sysTimer = Benchmark::GetTimer('System');
		self::Add(((float)$sysTimer > 5 ? 'WAR' : 'INF' ), "Total processing time: {$sysTimer}", __LINE__, __FILE__);

		$Items = array();
		# Output all log messages
		if (is_array(self::$Logs)) {
			# Do we have to output it as HTML?
			if ($asString === 2) {
				$i = 0;
				$typeColor['INF'] = '99cc66';
				$typeColor['ERR'] = 'cc6666';
				$typeColor['SEC'] = 'cc66cc';
				$typeColor['OK']  = '66aaee';
				$typeColor['WAR'] = 'cc9966';
			}

			foreach(self::$Logs as $logItem)
			{
				$type = $logItem['type'];

				if ($filterTo && is_array($filterTo)) {
					if (!in_array($type, $filterTo)) continue;
				}

				if ($asString) {
					if ($asString === true || $asString === 1) {
						$Items[] = 'Date/time: ' . $logItem['date_time'] . "\n" .
									'Type: ' . $logItem['type'] . "\n" .
									'Message: ' . $logItem['message'] . "\n" .
									'File: ' .  $logItem['file'] . "\n" .
									'Line: ' . $logItem['line'] . "\n" .
									str_repeat('-', 50) . "\n";
					}
					elseif ($asString === 2) {

						if (strpos($logItem['file'],SYSPATH) !== false) {
							$file = substr($logItem['file'],strlen(dirname(SYSPATH))+1);
						}
						elseif (strpos($logItem['file'],APPPATH) !== false) {
							$file = substr($logItem['file'],strlen(dirname(APPPATH))+1);
						}
						elseif (strpos($logItem['file'],PUBPATH) !== false) {
							$file = substr($logItem['file'],strlen(dirname(PUBPATH))+1);
						}
						else {
							$file = $logItem['file'];
						}

						$Items[] = '<div style="padding:10px; text-align:left; border-bottom: 2px solid #444; background-color:#'.(($i == 1) ? '111' : '222').';" class="msgType_'.$type.'">
											<div style="text-align:left;margin:0 0 10px 0;padding:0px;">
													<pre style="color:#'.$typeColor[$type].'; white-space: pre-wrap;">' .
															str_replace('&lt;br /&gt;', '<br />', vString::EncodeEntities($logItem['message'])) .
													"</pre>
											</div>\n" .
											'<p style="text-align:left;font-family:sans-serif;margin:0px;">
												<small style="font-size:12px;">'.
													date('H:i:s, d.m.Y', strtotime($logItem['date_time'])) . ': ' . $logItem['type']. ' | '.
													'<span title="'.$logItem['file'].'">' . $file . '</span>: ' . $logItem['line'] .
												'</small>'.
											"</p>\n" .
									 '</div>';

						$i = $i == 1 ? 0 : 1;
					}
					else {
						$Items[] = 'Date/time: ' . $logItem['date_time'] . '<br />'.
									'Type: '     . $logItem['type'] . '<br />'.
									'Message: '  . str_replace('&lt;br /&gt;', '<br />', vString::EncodeEntities($logItem['message'])) . '<br />'.
									'File: '     . $logItem['file'] . '<br />'.
									'Line: '     . $logItem['line'];
					}
				}
				else {
					$Items[] = $logItem;
				}
			}
		}

		if ($asString) {
			if ($asString === true || $asString === 1) {
				$Items = implode("\n", $Items);
			}
			elseif ($asString === 2) {
				$Items = '<div style="background-color:#222; color:#999; font-size:14px;">' . implode('', $Items) . '</div>';
			}
			else {
				$Items = implode('<br />'.str_repeat('-', 50).'<br />', $Items);
			}
		}

		return $Items;
	}
	//-


	/*  ****************************************************** *
	 *          Write into file
	 *  **************************************  */

	/**
	 * Write Whole Log Array into file
	 * ---
	 * @return bool
	 */
	public static function WriteAll($wasFatal=false)
	{
		if (!self::CanWrite(false, false)) return false;

		if ($wasFatal) {
			if (!self::$filenameOnFatal) {
				return false;
			}
			else {
				$filename = self::$filenameOnFatal;
				$LogTypes = array('INF', 'WAR', 'ERR');
				$how      = 1;
			}
		}
		else {
			if (!self::$filename) {
				return false;
			}
			else {
				$filename = self::$filename;
				$LogTypes = self::$Types;
				$how      = 2;
			}
		}

		if (self::CanWrite() || $wasFatal) {
			self::$enabled = false;
			$return = FileSystem::Write(self::Get($how, $LogTypes), $filename, true, 0777);
			chmod($filename, 0777); // Always full write to file
			self::$enabled = true;
			return $return;
		}
		else {
			return false;
		}
	}
	//-

	/**
	 * Write Log Message To File
	 * ---
	 * @param string $type     -- ERR|INF|WAR : error, information, warning
	 * @param string $message  -- plain englisg message
	 * @param string $line     -- __LINE__
	 * @param string $file     -- __FILE__
	 * ---
	 * @return bool
	 */
	private static function Write($type, $message, $line, $file)
	{
		if (!self::CanWrite($type, true)) return false;

		if (!self::$filename) {
			return false;
		}

		$type   = strtoupper($type);
		$toFile = 'Date/time: ' . date('Y-m-d H:i:s') . "\n" .
				  'Type: ' . $type . "\n" .
				  'Message: ' . str_replace('&lt;br /&gt;', '<br />', vString::EncodeEntities($message)) . "\n" .
				  'File: ' . $file . "\n" .
				  'Line: ' . $line . "\n" .
				  str_repeat('-', 50) . "\n";

		self::$enabled = false;
		$return = FileSystem::Write($toFile, self::$filename, true, 0777);
		chmod(self::$filename, 0777); // Always full write to file
		self::$enabled = true;

		return $return;
	}
	//-

	/**
	 * Will Check If Log Can Be Writen
	 * ---
	 * @param string $type -- which type? eg: INF, WAR, ERR (false to ignore types)
	 * @param bool $isIdividual
	 *
	 * @return bool
	 */
	private static function CanWrite($type=false, $isIdividual=false)
	{
		# Check if is enabled first...
		if (!self::$enabled) {
			return false;
		}

		# Is INF
		if (!isset(self::$Types['INF']) || self::$Types['INF'] == false) {
			if ($type == 'INF') return false;
		}

		# Is WAR
		if (!isset(self::$Types['WAR']) || self::$Types['WAR'] == false) {
			if ($type == 'WAR') return false;
		}

		# Is ERR
		if (!isset(self::$Types['ERR']) || self::$Types['ERR'] == false) {
			if ($type == 'ERR') return false;
		}

		# Is OK
		if (!isset(self::$Types['OK']) || self::$Types['OK'] == false) {
			if ($type == 'OK') return false;
		}

		# Is Individual?
		if ($isIdividual) {
			if (!self::$writeIndividual) {
				return false;
			}
		}

		# Everything's alright...
		return true;
	}
	//-

}
//--
