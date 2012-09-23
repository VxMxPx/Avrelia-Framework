<?php namespace Avrelia\Plug; if (!defined('AVRELIA')) die('Access is denied!');

use Avrelia\Core\Cfg as Cfg;
use Avrelia\Core\FileSystem as FileSystem;

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
class DatabaseDriverSqlite 
    extends DatabaseDriverBase 
    implements DatabaseDriverInterface
{
    private $valid;
    private $databasePath;

    /**
     * Init the database driver, called initialy when connection is established.
     * --
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        # Check If sqlitePDO Exists
        if (!in_array('sqlite', \PDO::getAvailableDrivers())) {
            trigger_error("PDO sqlite extension is not enabled!", E_USER_ERROR);
        }

        # Since This Is SQLite database, we must define only path & database filename
        $this->databasePath = ds(DATPATH.'/'.Cfg::get('plugs/database/sqlite/filename'));

        # File was found?
        if (!file_exists($this->databasePath)) {
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
                $this->PDO = new \PDO('sqlite:'.$this->databasePath);
                return true;
            }
            catch (\PDOException $e) {
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
        FileSystem::Write('', $this->databasePath);

        if (is_cli()) {
            # Chmod it to full permission!
            if (!chmod(ds($this->databasePath), 0777)) {
                Log::war("Can't set aproprite chmod permissions for database file: `".
                    $this->databasePath.'`.');
            }
        }

        if (file_exists($this->databasePath)) {
            $this->valid = true;
            return $this->connect() ? true : false;
        }
        else {
            Log::war("File wasn't created: `{$this->databasePath}`.");
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
        return FileSystem::Remove($this->databasePath);
    }
    //-
}
//--
