<?php namespace Avrelia\Plug; if (!defined('AVRELIA')) die('Access is denied!');

/**
 * Database Driver Interface
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
interface DatabaseDriverInterface
{
    /**
     * Make the connection.
     * --
     * @return PDO
     */
    function connect();

    /**
     * Prepare statement, bind values, return PDOStatement, which is ready to be
     * executed.
     * --
     * @param  string $statement
     * @param  array  $bind
     * --
     * @return PDOStatement
     */
    function prepare($statement, $bind=false);

    /**
     * Empty all rows from the table. This should return only proper SQL for this
     * action.
     * --
     * @param  string $table
     * --
     * @return string
     */
    function truncate($table);

    /**
     * Return -raw- PDO object.
     * --
     * @return PDO
     */
    function get_PDO();

    /**
     * Create the database file (in case of SQLite)
     * --
     * @return boolean
     */
    function _create();

    /**
     * Destroy the database file (in case of SQLite)
     * --
     * @return boolean
     */
    function _destroy();
}
