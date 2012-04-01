<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Database Driver Interface
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-03-22
 * ---
 * @property	array	$Config	Array of all configurations, set on construction
 * @property	PDO		$PDO	Link to the PDO Connection
 * ----
 * @method	PDOStatement	prepare
 * @method	PDO				getPDO
 */
class baseDatabase
{
	protected $Config;
	protected $PDO;

	/**
	 * Init the database driver, called initialy when connection is established.
	 * ---
	 * @param array $Config
	 * ---
	 * @return void
	 */
	public function __construct($Config)
	{
		# Check If is PDO enabled...
		if (!class_exists('PDO')) {
			trigger_error('PDO class doesn\'t exists. Please enable PDO extension.', E_USER_ERROR);
		}

		# Assign config
		$this->Config = $Config;
	}
	//-

	/**
	 * Bind values and execute particular statement.
	 * ---
	 * @param string $statement
	 * @param array $bind
	 * ---
	 * @return PDOStatement
	 */
	public function prepare($statement, $bind=false)
	{
		Log::Add('INF', "Will prepare following statement: `{$statement}` with params: " . print_r($bind, true), __LINE__, __FILE__);

		$link = $this->PDO->prepare($statement);

		if (is_object($link))
		{
			# Bind all values
			if ($bind) {
				foreach ($bind as $key => $value) {
					# Value type
					if (is_integer($value)) {
						$type = PDO::PARAM_INT;
					}
					elseif (is_bool($value)) {
						$type = PDO::PARAM_BOOL;
					}
					elseif (is_null($value)) {
						$type = PDO::PARAM_NULL;
					}
					else {
						$type = PDO::PARAM_STR;
					}

					$link->bindValue(':'.$key, $value, $type);
				}
			}
		}
		else {
			trigger_error("Failed to prepare: `" . print_r($this->PDO->errorInfo(), true) . '`.', E_USER_WARNING);
		}

		return new cDatabaseResult($link);
	}
	//-

	/**
	 * Will get -raw- PDO class.
	 * ---
	 * @return PDO
	 */
	public function getPDO()
	{
		return $this->PDO;
	}
	//-
}
//--
