<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Interface for Session drivers
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-03-25
 */
interface cSessionDriverInterface
{
	/**
	 * Create all files / tables required by this plug to work
	 * --
	 * @return	boolean
	 */
	static function _create();

	/**
	 * Destroy all elements created by this plug
	 * --
	 * @return	boolean
	 */
	static function _destroy();

	/**
	 * Login the user
	 * --
	 * @param	string	$username
	 * @param	string	$password
	 * @param	boolean	$rememberMe
	 * --
	 * @return	boolean
	 */
	function login($username, $password, $rememberMe=true);

	/**
	 * Will log-in user based on id.
	 * --
	 * @param	mixed	$id
	 * @param	boolean	$rememberMe
	 * --
	 * @return	boolean
	 */
	function loginId($id, $rememberMe=true);

	/**
	 * Logout (if logged in) the user
	 * --
	 * @return	void
	 */
	function logout();

	/**
	 * Check if the user is logged in.
	 * --
	 * @return	boolean
	 */
	function isLoggedin();

	/**
	 * Will reload current user's informations; Useful after an update.
	 * --
	 * @return	void
	 */
	function reload();

	/**
	 * Return user's informations as an array.
	 * If key is provided, only selected key will be returned, for example:
	 * $key = 'id', the $User['id'] will be returned.
	 * --
	 * @param	string	$key
	 * --
	 * @return	array
	 */
	function asArray($key=false);
}
//--
