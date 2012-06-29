<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * MySQL Database Driver
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-03-22
 */
class cDatabaseDriverMysql extends cDatabaseDriverBase implements cDatabaseDriverInterface
{
	/**
	 * Init the database driver, called initialy when connection is established.
	 * ---
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();

		# Check If MySQL Exists
		if (!in_array('mysql', PDO::getAvailableDrivers())) {
			trigger_error("PDO mysql extension is not enabled!", E_USER_ERROR);
		}
	}
	//-

	/**
	 * Make the connection.
	 * ---
	 * @return PDO
	 */
	public function connect()
	{
		$username = Cfg::Get('plugs/database/mysql/username');
		$password = Cfg::Get('plugs/database/mysql/password');
		$database = Cfg::Get('plugs/database/mysql/database');
		$hostname = Cfg::Get('plugs/database/mysql/hotstname');

		# Try to connect to database
		try {
			$Connection = new PDO('mysql:host='.$hostname.';dbname='.$database, $username, $password);
			$Connection->query('SET NAMES utf8');
			$this->PDO = $Connection;
			return true;
		}
		catch ( PDOException $e ) {
			trigger_error("Can't create PDO object: `" . $e->getMessage() . '`.', E_USER_WARNING);
			return false;
		}
	}
	//-

	/**
	 * Create the database file (in case of SQLite)
	 * ---
	 * @return boolean
	 */
	public function _create()
	{
		return $this->query('CREATE DATABASE ' . Cfg::Get('plugs/database/mysql/database'));
	}
	//-

	/**
	 * Destroy the database file (in case of SQLite)
	 * ---
	 * @return boolean
	 */
	public function _destroy()
	{
		return $this->query('DROP DATABASE ' . Cfg::Get('plugs/database/mysql/database'));
	}
	//-
}
//--
