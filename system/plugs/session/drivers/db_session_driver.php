<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Database Session Driver
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-03-27
 */

class dbSessionDriver implements interfaceSessionDriver
{
	private $Config;

	/**
	 * Will construct the database object.
	 * --
	 * @param	array	$Config
	 * --
	 * @return	void
	 */
	public function __construct($Config)
	{
		$this->Config = $Config;
	}
	//-

	/**
	 * Create all files / tables required by this plug to work
	 * --
	 * @param	array	$Config
	 * --
	 * @return	boolean
	 */
	public static function _create($Config)
	{
		# Create users table (if doesn't exists)
		if (Plug::Has('database')) {
			cDatabase::Query($Config['db']['Tables']['users_table'],    array('users_table'    => $Config['db']['users_table']));
			cDatabase::Query($Config['db']['Tables']['sessions_table'], array('sessions_table' => $Config['db']['sessions_table']));
		}
		else {
			trigger_error("Can't create, database plug must be enabled.", E_USER_ERROR);
			return false;
		}

		# Default users
		$Users = array();

		foreach ($Config['defaults'] as $DefUser)
		{
			$User['id']       = self::unameToId($DefUser['uname']);
			$User['uname']    = $DefUser['uname'];
			$User['password'] = vString::Hash($DefUser['password'], false, true);
			$User['active']   = true;

			$Users[$User['id']] = $User;
		}

		return uJSON::EncodeFile($Config['json']['users_filename'], $Users);
	}
	//-

	/**
	 * Destroy all elements created by this plug
	 * --
	 * @param	array	$Config
	 * --
	 * @return	boolean
	 */
	public static function _destroy($Config)
	{
		$r1 = FileSystem::Remove($Config['json']['users_filename']);
		$r2 = FileSystem::Remove($Config['json']['sessions_filename']);

		return $r1 && $r2;
	}
	//-

	/**
	 * Login the user
	 * --
	 * @param	string	$username
	 * @param	string	$password
	 * --
	 * @return	boolean
	 */
	public function login($username, $password)
	{

	}
	//-
}
//--
