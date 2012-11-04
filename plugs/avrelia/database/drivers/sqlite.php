<?php namespace Plug\Avrelia; if (!defined('AVRELIA')) die('Access is denied!');

use Avrelia\Core\Cfg as Cfg;
use Avrelia\Core\Log as Log;
use Avrelia\Core\FileSystem as FileSystem;

/**
 * Database Driver SQLite
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class DatabaseDriverSqlite 
    extends DatabaseDriverBase 
    implements DatabaseDriverInterface
{
    private $valid;
    private $database_path;

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
        $this->database_path = dat_path(Cfg::get('plugs/database/sqlite/filename'));

        # File was found?
        if (!file_exists($this->database_path)) 
            { $this->valid = false; }
        else 
            { $this->valid = true; }
    }

    /**
     * Make the connection.
     * --
     * @return PDO
     */
    public function connect()
    {
        if ($this->valid) {
            # Try to connect to database
            try {
                $this->PDO = new \PDO('sqlite:'.$this->database_path);
                return true;
            }
            catch (\PDOException $e) {
                trigger_error(
                    "Can't create PDO object: `" . $e->getMessage() . '`.', 
                    E_USER_WARNING);
                return false;
            }
        }
        else 
            { return false; }
    }

    /**
     * Create the database file (in case of SQLite)
     * --
     * @return boolean
     */
    public function _create()
    {
        # Create dummy file
        FileSystem::Write('', $this->database_path);

        //if (is_cli()) {
            # Chmod it to full permission!
            if (!chmod(ds($this->database_path), 0777)) {
                Log::war("Can't set aproprite chmod permissions for database file: `".
                    $this->database_path.'`.');
            }
        //}

        if (file_exists($this->database_path)) {
            $this->valid = true;
            return $this->connect() ? true : false;
        }
        else {
            Log::err("File wasn't created: `{$this->database_path}`.");
            return false;
        }
    }

    /**
     * Empty all rows from the table. This should return only proper SQL for this
     * action.
     * --
     * @param  string $table
     * --
     * @return string
     */
    function truncate($table)
    {
        return 'DELETE FROM ' . $table . ';';
    }

    /**
     * Destroy the database file (in case of SQLite)
     * --
     * @return boolean
     */
    public function _destroy()
        { return FileSystem::Remove($this->database_path); }
}
