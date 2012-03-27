<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Database Plug
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-03-21
 * ---
 * @property	interfaceDatabase	$Driver	PDO Database Driver Instance
 */
class cDatabase
{
	private static $Driver;

	/**
	 * Init the Database object
	 * --
	 * @return	boolean
	 */
	public static function _DoInit()
	{
		if (!self::$Driver->connect()) {
			Log::Add('ERR', "Can't connect to or create database.", __LINE__, __FILE__);
			return false;
		}

		return true;
	}
	//-

	/**
	 * Enable database plug
	 * --
	 * @return	boolean
	 */
	public static function _DoEnable()
	{
		self::$Driver->_create();
	}
	//-

	/**
	 * Remove the database
	 * --
	 * @return	boolean
	 */
	public static function _DoDisable()
	{
		return self::$Driver->_destroy();
	}
	//-

	/**
	 * Load database driver
	 * --
	 * @return	boolean
	 */
	private static function LoadDriver()
	{
		$Config = Plug::GetConfig(__FILE__);

		# Get basepath
		$path = dirname(__FILE__);

		# Load Interface
		if (!class_exists('interfaceDatabase', false))
		{
			$ifDatabasePath = ds($path . '/drivers/interface_database.php');
			if (file_exists($ifDatabasePath)) {
				include($ifDatabasePath);
			}
			else {
				Log::Add('ERR', "Can't find file `interface_database.php` file in: `{$ifDatabasePath}`.", __LINE__, __FILE__);
				return false;
			}
		}

		# Load Base class
		if (!class_exists('baseDatabase', false))
		{
			$baseDatabasePath = ds($path . '/drivers/base_database.php');
			if (file_exists($baseDatabasePath)) {
				include($baseDatabasePath);
			}
			else {
				Log::Add('ERR', "Can't find file `base_database.php` file in: `{$baseDatabasePath}`.", __LINE__, __FILE__);
				return false;
			}
		}

		# Get Driver
		$driverClass = $Config['driver'] . 'DatabaseDriver';

		if (!class_exists($driverClass, false))
		{
			$driverPath = ds($path . '/drivers/' . toUnderline($driverClass) . '.php');

			if (file_exists($driverPath)) {
				include($driverPath);
			}
			else {
				Log::Add('ERR', "Can't find driver file: `{$driverPath}`.", __LINE__, __FILE__);
				return false;
			}

			if (!class_exists($driverClass, false)) {
				Log::Add('ERR', "Database driver's class doesn't exists: `{$driverClass}`.", __LINE__, __FILE__);
				return false;
			}
		}

		Log::Add('INF', "Database driver was loaded: `{$driverClass}`", __LINE__, __FILE__);
		self::$Driver = new $driverClass($Config);

		# Finally load all other required libraries
		if (!class_exists('cDatabaseRecord',    false)) { include(ds($path.'/database_record.php'));    }
		if (!class_exists('cDatabaseResult',    false)) { include(ds($path.'/database_result.php'));    }
		if (!class_exists('cDatabaseStatement', false)) { include(ds($path.'/database_statement.php')); }

		return true;
	}
	//-

	/**
	 * Return PDO Driver object.
	 * ---
	 * @return	interfaceDatabase
	 */
	public static function _getDriver()
	{
		return self::$Driver;
	}
	//-

	/**
	 * SQL query
	 * --
	 * @param	string	$statement
	 * @param	array	$bind
	 * --
	 * @return	cDatabaseResult
	 */
	public static function Query($statement, $bind=false)
	{
		$Statement = new cDatabaseStatement($statement);

		if ($bind) {
			$Statement->bind($bind);
		}

		return $Statement->execute();
	}
	//-

	/**
	 * Create new record.
	 * --
	 * @param	array	$Values
	 * @param	string	$table
	 * --
	 * @return	cDatabaseResult
	 */
	public static function Create($Values, $table)
	{
		# Create statement
		$sql = "INSERT INTO {$table} (" . vArray::ImplodeKeys(', ', $Values) . ')' .
				' VALUES (';

		foreach ($Values as $k => $v) {
			$sql .= ":{$k}, ";
		}

		$sql = substr($sql, 0, -2);
		$sql .= ')';

		$Statement = new cDatabaseStatement($sql);
		$Statement->bind($Values);

		return $Statement->execute();
	}
	//-

	/**
	 * Will read items from database.
	 * --
	 * @param	string	$table
	 * @param	mixed	$condition	['id' => 12] || 'id=:id AND name=:name' and bind it later.
	 * @param	array	$bind
	 * @param	mixed	$limit		Select 12 records || range: [10, 25]
	 * @param	array	$order		['name' => 'DESC'] || ['name' => 'DESC', 'date' => 'ASC'] || ['name', 'id' => 'DESC']
	 * --
	 * @return	cDatabaseResult
	 */
	public static function Read($table, $condition=false, $bind=false, $limit=false, $order=false)
	{
		# Initial statement
		$sql = "SELECT * FROM {$table}";

		# Parse condition, if is an array
		if (is_array($condition)) {
			# So, it's quite simple, if we have array in condition, then for sure,
			# we don't have bind, set, because, bind is used only if we have costume,
			# string condition. So we'll assign, array condition, to bind.
			$bind      = $condition;
			$condition = self::ParseCondition($condition);
		}

		# Append condition
		if ($condition) {
			$sql .= ' ' . $condition;
		}

		# Do we have limit?
		if ($limit) {
			if (!is_array($limit)) {
				$limit = array(0, $limit);
			}

			$limit = implode(', ', $limit);
			$sql .= ' LIMIT ' . $limit;
		}

		# Do we have order?
		if ($order) {
			$orderStatement = '';
			foreach ($order as $field => $type) {
				if (is_integer($field)) {
					# name, id DESC
					$orderStatement .= $type . ', ';
				}
				else {
					# id DESC, name ASC, ...
					$orderStatement .= "{$field} {$type}, ";
				}
			}
			$sql .= ' ORDER BY ' . substr($orderStatement, 0, -2);
		}

		# Make it happened
		$Statement = new cDatabaseStatement($sql);

		# Do we have anything to bind?
		if ($bind) {
			$Statement->bind($bind);
		}

		# Execute, and return results
		return $Statement->execute();
	}
	//-

	/**
	 * Update particular record.
	 * --
	 * @param	array	$Values
	 * @param	string	$table
	 * @param	mixed	$condition	['id' => 12] || 'id=:id AND name=:name' and bind it.
	 * @param	array	$bind
	 * --
	 * @return	cDatabaseResult
	 */
	public static function Update($Values, $table, $condition, $bind=false)
	{
		$sql = "UPDATE {$table} SET ";

		foreach ($Values as $key => $val) {
			$sql .= "{$key}=:{$key}, ";
		}

		$sql = substr($sql, 0, -2);

		if (is_array($condition)) {
			$bind      = $condition;
			$condition = self::ParseCondition($condition);
		}

		$sql .= ' '.$condition;

		$Statement = new cDatabaseStatement($sql);
		$Statement->bind($Values);
		if ($bind) {
			$Statement->bind($bind);
		}

		return $Statement->execute();
	}
	//-

	/**
	 * Will delete particular item.
	 * --
	 * @param	string	$table
	 * @param	mixed	$condition	['id' => 12] || 'id=:id AND name=:name' and bind it later.
	 * @param	array	$bind
	 * --
	 * @return	cDatabaseResult
	 */
	public static function Delete($table, $condition, $bind=false)
	{
		$sql  = "DELETE FROM {$table}";

		if (is_array($condition)) {
			$bind = $condition;
			$condition = self::ParseCondition($condition);
		}

		$sql .= ' ' . $condition;

		$Statement = new cDatabaseStatement($sql);

		if ($bind) {
			$Statement->bind($bind);
		}

		return $Statement->execute();
	}
	//-

	/**
	 * Parse an array condition (like) ['id' => 12] into WHERE id=:id
	 * --
	 * @param	array	$condition
	 * --
	 * @return	string
	 */
	private static function ParseCondition($condition)
	{
		if (is_array($condition)) {

			$newCondition = 'WHERE ';

			foreach ($condition as $k => $v) {
				$newCondition .= "{$k}=:{$k} AND ";
			}

			return substr($newCondition, 0, -5);
		}
		else {
			Log::Add('WAR', "Condition must be an array: {$condition}.", __LINE__, __FILE__);
			return $condition;
		}
	}
	//-
}
//--
