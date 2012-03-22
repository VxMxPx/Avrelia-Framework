<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Cookie Library
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2009, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.60
 * @since      Date 2009-08-18
 */


class uCookie
{

	/**
	 * Create cookie
	 * --------
	 * @param string $name
	 * @param string $value
	 * @param string $expire -- use false for default expire time (set in configuration), or enter value (actuall value so must be time() + seconds)
	 *  ------
	 * @return bool
	 */
	public static function Create($name, $value, $expire=false)
	{
		# Expire
		if ($expire === false) {
			$expire = time() + Cfg::Get('Cookie/timeout');
		}

		# Domain
		$domain = Cfg::Get('Cookie/domain');
		if (!$domain) {
			$domain = $_SERVER['SERVER_NAME'];
		}

		Log::Add('INF', 'Cookie will be set, as: "'  .
			Cfg::Get('Cookie/prefix') .
			$name . '", with value: "'  .
			$value . '", set to expire: "' .
			($expire) .
			'" to domain: "' . $domain . '"',
			__LINE__,
			__FILE__
		);

		return setcookie(Cfg::Get('Cookie/prefix') . $name, $value, $expire, "/", $domain);
	}
	//-

	/**
	 * Fetch an item from the COOKIE array
	 *  ------
	 * @param string $key
	 *  ------
	 * @return mixed
	 */
	public static function Read($key='')
	{
		$keyP = Cfg::Get('Cookie/prefix') . $key;

		# Is Cookie With Prefix Set?
		if (isset($_COOKIE[$keyP])) {
			$return = $_COOKIE[$keyP];
		}
		elseif (isset($_COOKIE[$key])) {
			$return = $_COOKIE[$key];
		}
		else {
			return false;
		}

		return vString::EncodeEntities($return);
	}
	//-

	/**
	 * Remove cookie
	 * --------
	 * @param string $name
	 * --------
	 * @return bool
	 */
	public static function Remove($name)
	{
	   Log::Add('INF', 'Cookie will be unset: `' . Cfg::Get('Cookie/prefix') . $name . '`.', __LINE__, __FILE__);

		# Domain
		$domain = Cfg::Get('Cookie/domain');
		if (!$domain) {
			$domain = $_SERVER['SERVER_NAME'];
		}

	   return setcookie(Cfg::Get('Cookie/prefix') . $name, '', time() - 3600, "/", $domain);
	}
	//-

}
//--
