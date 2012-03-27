<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Input Library
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2009, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.60
 * @since      Date 2009-08-18
 */


class Input
{
	# List Of All URI segments (segment0/segment1) ('segment0', 'segment1')
	private static $UriSegments = array();

	# List Of All Actions (action=value)
	private static $UriActions  = array();

	# Build uri segements
	private static $BuildUri    = array();

	/**
	 * Get Rid Of Globals & Set Uri Segment
	 * from: http://www.phpguru.org/article/yet-more-on-cleaning-input-data
	 *
	 * @return void
	 */
	public static function Init()
	{
		if (ini_get('register_globals'))
		{
			Log::Add('INF', "We\'ll dispel globals now.", __LINE__, __FILE__);

			// Might want to change this perhaps to a nicer error
			if (isset($_REQUEST['GLOBALS'])) {
				trigger_error('GLOBALS overwrite attempt detected.', E_USER_ERROR);
			}

			// Variables that shouldn't be unset
			$NoUnset = array('GLOBALS', 'GET', 'POST', '_COOKIE', '_SERVER', '_ENV', '_FILES');

			$Input = array_merge($_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES, isset($_SESSION) ? (array)$_SESSION : array());

			foreach ($Input as $k => $v) {
				if (!in_array($k, $NoUnset) && isset($GLOBALS[$k])) {
					unset($GLOBALS[$k]);
				}
			}
		}

		# Set Get Actions And Segments
		self::SetGet();
	}
	//-

	/**
	 * Set Get Actions And Segments
	 *
	 * @return void
	 */
	private static function SetGet()
	{
		# Set Get Actions And Segments
		$uri = self::Server('REQUEST_URI');
		$uri = vString::RegExClean($uri, Cfg::Get('system/input_get_filter', '/[^a-z0-9_]/'));

		# Shouldn't Be Empty
		if ($uri == '') { return false; }

		# Get Array Of Segments
		$UriSegments = explode('/', $uri);

		# It Should Be Array, And Shouldn't Be Empty!
		if (!is_array($UriSegments) && !empty($UriSegments)) { return false; }

		# We'll have 120 values in array
		$UriSegments = array_slice($UriSegments, 0, 120);

		# Main Loop
		foreach($UriSegments as $uriSegment)
		{
			# Clean Up Url Here
			//$uriSegment = vString::Clean($uriSegment, 20000, 'a A 1 c', '=_-.');

			# Check If It's An Action Or Segment
			if (strpos($uriSegment, '='))
			{
				// is action
				$action = '';
				$action = explode('=', $uriSegment, 2);
				$value  = (isset($action[1]) ? trim($action[1]) : '');
				$action = (isset($action[0]) ? trim($action[0]) : '');
				if ($action != '')
				{
					//$action = vString::Clean($action, 80, 'a A 1 c', '_-');
					$action = strtolower($action);
					self::$UriActions[$action] = $value; # This Is Clean, since we clean whole segment
				}
			}
			else {
				// is segment
				if (strlen(trim($uriSegment)) > 0) {
					self::$UriSegments[] = substr($uriSegment, 0, 400); # This Is Clean, since we clean whole segment
				}
			}
		}
	}
	//-

	/**
	 * Get the request uri (example /edit/me/now)
	 * ---
	 * @param bool $includeActions
	 * ---
	 * @return string
	 */
	public static function GetRequestUri($includeActions=true)
	{
		return $includeActions ? $_SERVER['REQUEST_URI'] : implode('/', self::$UriSegments);
	}
	//-

	/**
	 * Will set default value for particular segment,...
	 * This is mostly used when we don't have set segment 0, and we need it to
	 * build firther navigation!
	 * ---
	 * @param array $Uri -- array(0 => 'home')
	 * @param bool $ifNotSet will set it only if it's not already set
	 * ---
	 * @return void
	 */
	public static function BuildUriSet($Uri, $ifNotSet=true)
	{
		foreach ($Uri as $key => $val) {
			if (!Input::Get($key) || !$ifNotSet) {
				self::$BuildUri[$key] = $val;
			}
		}
	}
	//-

	/**
	 * This will build url (by replacing existing) from segments / actions.
	 *
	 * @param array $Uri -- examples:
	 * 	array(0 => 'segment', 1 => 'segment1', 'action' => 'value')
	 *
	 * @param bool $updateCurrent -- will keep current uri's segments / actions and update them
	 *
	 * @return string
	 */
	public static function BuildUri($Uri, $updateCurrent=true)
	{
		if (!is_array($Uri)) {
			$Uri = array($Uri);
		}

		if (!empty(self::$BuildUri)) {
			foreach (self::$BuildUri as $id => $segment) {
				if (!isset($Uri[$id])) {
					$Uri[$id] = $segment;
				}
			}
		}
		ksort($Uri);
		$finalUri = '';

		# Update Current Url
		if ($updateCurrent)
		{
			foreach (self::$UriSegments as $num => $value)
			{
				if (isset($Uri[$num])) {
					$finalUri .= $Uri[$num] . '/';
					unset($Uri[$num]);
				}
				else {
					$finalUri .= $value . '/';
				}
			}
			foreach (self::$UriActions as $num => $value)
			{
				if (isset($Uri[$num])) {

					if ($Uri[$num] === false) { continue; }

					$finalUri .= "{$num}=".$Uri[$num]."/";
					unset($Uri[$num]);
				}
				elseif ($value !== false) {
					$finalUri .= "{$num}={$value}/";
				}
			}
		}

		# Add All New Segments
		foreach ($Uri as $num => $value) {
			if (is_numeric($num)) {
				$finalUri .= $value . '/';
			}
			elseif ($value !== false) {
				$finalUri .= "{$num}={$value}/";
			}
		}

		return trim($finalUri, '/');
	}
	//-

	/**
	 * Fetch an item from the POST array
	 *
	 * @param mixed $key  ---  If key is empty, then we'll return whole post!
	 *
	 * @param mixed $default -- default if variable isn't set....
	 *
	 * @return mixed
	 */
	public static function Post($key=false, $default=null)
	{
		if (!$key) {
			if (!empty($_POST)) {
				return $_POST;
			}
			else {
				return $default;
			}
		}
		elseif (is_array($key))
		{
			$NewArray = array();
			foreach ($key as $id => $sel) {
				$id = is_integer($id) ? $sel : $id;
				$NewArray[$id] = isset($_POST[$sel]) ? $_POST[$sel] : $default;
			}
			return $NewArray;
		}
		elseif (!isset($_POST[$key]))
		{
			return $default;
		}
		else
		{
			return $_POST[$key];
		}
	}
	//-

	/**
	 * Return true if any data was posted, and false if wasn't
	 * ---
	 * @return bool
	 */
	public static function HasPost()
	{
		return !empty($_POST);
	}
	//-

	/**
	 * Return current url, if withQuery is set to true, it will return full url,
	 * query included.
	 * ---
	 * @param bool $withQuery
	 * ---
	 * @return string
	 */
	public static function GetUrl($withQuery=false)
	{
		$url = trim(Cfg::Get('system/full_url', false));

		if (empty($url) || !$url) {
			$url = 'http://'.$_SERVER['SERVER_NAME'];
		}

		# Make sure we have ending '/'!
		$url = trim($url, '/') . '/';

		if ($withQuery) {
			$url = $url . ltrim($_SERVER['REQUEST_URI'], '/');
		}

		return $url;
	}
	//-

	/**
	 * Fetch an item from the GET array
	 *
	 * @param mixed $key - enter one of the following:
	 *				false: return whole url
	 *				integer: for segment (example: 0 - will get segment 0)
	 *				string: for action (example: `my_action` - will get `some_action` from: `my_uri/my_action=some_action`)
	 *				string with -?- prefix: for regular get (example: `?my_action` will get `some_action` from `my_uri?my_action=some_action` )
	 * @param mixed $default - Default value (if request isn't set)
	 *
	 * @return mixed
	 */
	public static function Get($key, $default=false)
	{
		if ($key === false) {
			return self::Server('REQUEST_URI');
		}

		if (is_numeric($key)) {
			if (isset(self::$UriSegments[$key]))
				return self::$UriSegments[$key];
			else
				return $default;
		}
		else {
			if (substr($key,0,1)=='?') {
				$key = substr($key,1);
				if (isset($_GET[$key]))
					return $_GET[$key];
				else
					return $default;
			}
			else {
				if (isset(self::$UriActions[$key])) {
					return self::$UriActions[$key];
				}
				else {
					return $default;
				}
			}
		}
	}
	//-

	/**
	 * Will get current domain
	 * ---
	 * @return string
	 */
	public static function Domain()
	{
		return $_SERVER['SERVER_NAME'];
	}
	//-

	/**
	 * Fetch an item from the SERVER array
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public static function Server($key='')
	{
		if (!isset($_SERVER[$key])) {
			return false;
		}

		return vString::EncodeEntities($_SERVER[$key]);
	}
	//-

}
//--
