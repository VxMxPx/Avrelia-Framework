<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Base Database Interface
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-03-22
 * ---
 * @method	PDO				connect
 * @method	PDOStatement	prepare
 * @method	PDO				getPDO
 * @method	boolean			create
 * @method	boolean			destroy
 */
interface interfaceDatabase
{
	/**
	 * Make the connection.
	 * ---
	 * @return PDO
	 */
	function connect();
	//-

	/**
	 * Prepare statement, bind values, return PDOStatement, which is ready to be
	 * executed.
	 * ---
	 * @param string $statement
	 * @param array $bind
	 * ---
	 * @return PDOStatement
	 */
	function prepare($statement, $bind=false);
	//-

	/**
	 * Return -raw- PDO object.
	 * ---
	 * @return PDO
	 */
	function getPDO();
	//-

	/**
	 * Create the database file (in case of SQLite)
	 * ---
	 * @return boolean
	 */
	function create();
	//-

	/**
	 * Destroy the database file (in case of SQLite)
	 * ---
	 * @return boolean
	 */
	function destroy();
	//-
}
//--
