<?php

namespace Plug\Avrelia;

use Avrelia\Core\Cfg as Cfg;
use Avrelia\Core\Arr as Arr;

/**
 * Database Driver MySQL
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class DatabaseDriverMysql
    extends DatabaseDriverBase
    implements DatabaseDriverInterface
{
    /**
     * Init the database driver, called initialy when connection is established.
     * --
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        # Check If MySQL Exists
        if (!in_array('mysql', \PDO::getAvailableDrivers())) {
            trigger_error("PDO mysql extension is not enabled!", E_USER_ERROR);
        }
    }

    /**
     * Make the connection.
     * --
     * @return PDO
     */
    public function connect()
    {
        $username = Cfg::get('plugs/database/mysql/username');
        $password = Cfg::get('plugs/database/mysql/password');
        $database = Cfg::get('plugs/database/mysql/database');
        $hostname = Cfg::get('plugs/database/mysql/hotstname');

        # Try to connect to database
        try {
            $connection = new \PDO(
                                'mysql:host='.$hostname.
                                ';dbname='.$database,
                                $username,
                                $password);
            $connection->query('SET NAMES utf8');
            $this->PDO = $connection;
            return true;
        }
        catch ( \PDOException $e ) {
            trigger_error(
                "Can't create PDO object: `" . $e->getMessage() . '`.',
                E_USER_WARNING);
        }
    }

    /**
     * Create many records with only one query.  [
     *     [name => Marko, age => 28],
     *     [name => Inna,  age => 24],
     *     ...
     * ]
     *
     * @param  array  $values
     * @param  string $table
     * @return DatabaseResult
     */
    public function create_many(&$values, $table)
    {
        if (!is_array($values[0])) { return false; }

        $new_values = [];

        # Create statement
        $sql_intro = "INSERT INTO {$table} (" . Arr::implode_keys(', ', $values[0]) . ') VALUES';
        $sql = [];

        foreach ($values as $k1 => $sub_values) {

            $sub_sql = '(';

            foreach ($sub_values as $k2 => $v) {

                $sub_sql .= ":{$k1}_{$k2}, ";
                $new_values[$k1.'_'.$k2] = $v;
            }

            $sub_sql = substr($sub_sql, 0, -2) . ')';
            $sql[]   = $sub_sql;
        }

        $values = $new_values;

        return $sql_intro . ' ' . implode(', ', $sql);
    }

    /**
     * Create the database file (in case of SQLite)
     * --
     * @return boolean
     */
    public function _create()
    {
        return $this->query('CREATE DATABASE ' . Cfg::get('plugs/database/mysql/database'));
    }

    /**
     * Destroy the database file (in case of SQLite)
     * --
     * @return boolean
     */
    public function _destroy()
    {
        return $this->query('DROP DATABASE ' . Cfg::get('plugs/database/mysql/database'));
    }
}
