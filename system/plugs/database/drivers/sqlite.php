<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * SQLite Database Driver
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-03-22
 * ---
 * @param boolean $valid Was construct successful?
 */
class cDatabaseDriverSqlite extends cDatabaseDriverBase implements cDatabaseDriverInterface
{
	private $valid;

	/**
	 * Init the database driver, called initialy when connection is established.
	 * ---
	 * @param array $Config
	 * ---
	 * @return void
	 */
	public function __construct($Config)
	{
		# Assign Config and check if base PDO is enabled.
		parent::__construct($Config);

		# Check If sqlitePDO Exists
		if (!in_array('sqlite', PDO::getAvailableDrivers())) {
			trigger_error("PDO sqlite extension is not enabled!", E_USER_ERROR);
		}

		# Since This Is SQLite database, we must define only path & database filename
		$this->Config['databasePath'] = ds(DATPATH.'/'.$this->Config['sqlite']['filename']);

		# File was found?
		if (!file_exists($this->Config['databasePath'])) {
			$this->valid = false;
		}
		else {
			$this->valid = true;
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
		if ($this->valid) {
			# Try to connect to database
			try {
				$this->PDO = new PDO('sqlite:'.$this->Config['databasePath']);
				return true;
			}
			catch (PDOException $e) {
				trigger_error("Can't create PDO object: `" . $e->getMessage() . '`.', E_USER_WARNING);
				return false;
			}
		}
		else {
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
		# Create dummy file
		FileSystem::Write('', $this->Config['databasePath']);

		if (IN_CLI) {
			# Chmod it to full permission!
			if (!chmod(ds($this->Config['databasePath']), 0777)) {
				Log::Add('WAR', "Can't set aproprite chmod permissions on database file: `".$this->Config['databasePath'].'`.', __LINE__, __FILE__);
			}
		}

		if (file_exists($this->Config['databasePath'])) {
			$this->valid = true;
			return $this->connect() ? true : false;
		}
		else {
			Log::Add('WAR', "It seems file wasn't created: `{$this->Config['databasePath']}`.", __LINE__, __FILE__);
			return false;
		}
	}
	//-

	/**
	 * Destroy the database file (in case of SQLite)
	 * ---
	 * @return boolean
	 */
	public function _destroy()
	{
		return FileSystem::Remove($this->Config['databasePath']);
	}
	//-
}
//--
