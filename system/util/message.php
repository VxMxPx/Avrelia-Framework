<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Messages Library
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2009, Avrelia.com
 * @license    http://avrelia.com/license
 * @link       http://avrelia.com
 * @since      Version 0.80
 * @since      Tue Nov 08 11:13:20 2011
 */
class uMessage
{
	private static $List = array();	# array	The list of all messages


	/**
	 * Add a Message To The List.
	 * If you added OK or INF true will be returned else false.
	 * --
	 * @param	string	$type		INF|WAR|ERR|OK == information, warning, error, successfully done
	 * @param	string	$message
	 * @param	string	$group		Any particular group?
	 * --
	 * @return	boolean
	 */
	public static function Add($type, $message, $group=null)
	{
		$type = strtoupper($type);

		self::$List[] = array
		(
			'type'      => $type,
			'message'   => $message,
			'group'     => $group,
		);

		if ($type == 'OK' || $type == 'INF') {
			return true;
		}
		else {
			return false;
		}
	}
	//-

	/**
	 * Add a message to the list AND to log.
	 * $file will be used as a group.
	 * If you added OK or INF true will be returned else false.
	 * --
	 * @param	string	$type		INF|WAR|ERR|OK == information, warning, error, successfully done
	 * @param	string	$message
	 * @param	integer	$line
	 * @param	string	$file
	 * --
	 * @return	boolean
	 */
	public static function Log($type, $message, $line, $file)
	{
		self::Add($type, $message, $file);
		return Log::Add($type, $message, $line, $file);
	}
	//-

	/**
	 * Return or echo all messages
	 * --
	 * @param	boolean	$echo
	 * @param	string	$group	Any particular group?
	 * --
	 * @return	string
	 */
	public static function Get($echo=true, $group=null)
	{
		if (!is_array(self::$List)) { return false; }

		$return = '';

		$Inf = $War = $Err = $Ok = array();

		foreach ( self::$List as $Message )
		{
			if ($group != null && $Message['group'] != $group) continue;
			if ($Message['type']  == 'ERR')  $Err[] = $Message;
			if ($Message['type']  == 'WAR')  $War[] = $Message;
			if ($Message['type']  == 'INF')  $Inf[] = $Message;
			if ($Message['type']  == 'OK')   $Ok[]  = $Message;
		}

		# Get Errors
		if (!empty($Err)) {
			$return .= '<div id="tERR" class="mItem"><div class="mIco"><span>ERR:</span></div><div class="mMsg">';
			foreach ($Err as $Message) {
				$return .= '<div>'.$Message['message'].'</div>'."\n";
			}
			$return .= '</div></div>'."\n";
		}

		# Get Warnings
		if (!empty($War)) {
			$return .= '<div id="tWAR" class="mItem"><div class="mIco"><span>WAR:</span></div><div class="mMsg">';
			foreach ($War as $Message) {
				$return .= '<div>'.$Message['message'].'</div>'."\n";
			}
			$return .= '</div></div>'."\n";
		}

		# Get Infos
		if (!empty($Inf)) {
			$return .= '<div id="tINF" class="mItem"><div class="mIco"><span>INF:</span></div><div class="mMsg">';
			foreach ($Inf as $Message) {
				$return .= '<div>'.$Message['message'].'</div>'."\n";
			}
			$return .= '</div></div>'."\n";
		}

		# Get Ok
		if (!empty($Ok)) {
			$return .= '<div id="tOK" class="mItem"><div class="mIco"><span>OK:</span></div><div class="mMsg">';
			foreach ($Ok as $Message) {
				$return .= '<div>'.$Message['message'].'</div>'."\n";
			}
			$return .= '</div></div>'."\n";
		}

		if ($echo) {
			echo $return;
			return;
		}
		else {
			return $return;
		}
	}
	//-

	/**
	 * Return plain (array) list of messages
	 * --
	 * @param	boolean	$plain	If true, you'll get regular array (insted of associative array)
	 * --
	 * @return	array
	 */
	public static function GetRaw($plain=false)
	{
		if (empty(self::$List)) {
			return array();
		}

		$List = array();

		if ($plain) {
			foreach (self::$List as $Item) {
				$List[] = array($Item['type'], $Item['message'], $Item['group']);
			}
		}
		else {
			$List = self::$List;
		}

		return $List;
	}
	//-

	/**
	 * Set Messages List (from array)
	 * --
	 * @param	array	$Messages	List of messages
	 * @param	boolean	$merge		Merge list with existing?
	 * --
	 * @return	void
	 */
	public static function SetRaw($Messages, $merge=false)
	{
		if (!is_array($Messages)) return false;

		if ($merge) {
			self::$List = array_merge(self::$List, $Messages);
		}
		else {
			self::$List = $Messages;
		}
	}
	//-

	/**
	 * Check if there is any message (of particular type)
	 * --
	 * @param	string	$type
	 * @param	string	$group	For any particular group?
	 * --
	 * @return	boolean
	 */
	public static function Exists($type=false, $group=null)
	{
		if ($type) {
			if (self::Exists()) {
				foreach (self::$List as $key => $Messages) {
					if ($group == null || $Messages['group'] == $group) {
						if ($Messages['type'] == $type) {
							return true;
						}
					}
				}
			}
			return false;
		}

		if ((is_array(self::$List)) && (!empty(self::$List))) {
			return true;
		}
		else {
			return false;
		}
	}
	//-
}
//-
