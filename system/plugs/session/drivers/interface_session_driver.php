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
 * ---
 * @method	boolean	_canConstruct
 * @method	boolean	_create
 * @method	boolean	_destroy
 * @method	boolean	login
 * @method	void	logout
 * @method	boolean	isLoggedin
 * @method	array	asArray
 */
interface interfaceSessionDriver
{
	/**
	 * Do we have all we need to construct this plug's class?
	 * --
	 * @param	array	$Config
	 * --
	 * @return	boolean
	 */
	static function _canConstruct($Config);

	/**
	 * Create all files / tables required by this plug to work
	 * --
	 * @param	array	$Config
	 * --
	 * @return	boolean
	 */
	static function _create($Config);

	/**
	 * Destroy all elements created by this plug
	 * --
	 * @param	array	$Config
	 * --
	 * @return	boolean
	 */
	static function _destroy($Config);

	/**
	 * Login the user
	 * --
	 * @param	string	$username
	 * @param	string	$password
	 * --
	 * @return	boolean
	 */
	function login($username, $password);

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
