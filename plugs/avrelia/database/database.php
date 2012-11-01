<?php namespace Plug\Avrelia; if (!defined('AVRELIA')) die('Access is denied!');

use Avrelia\Core\Arr  as Arr;
use Avrelia\Core\Log  as Log;
use Avrelia\Core\Plug as Plug;
use Avrelia\Core\Cfg  as Cfg;
use Avrelia\Core\Str  as Str;

/**
 * Database Plug
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Database
{
    # DatabaseDriverInterface PDO Database Driver Instance
    private static $driver;

    /**
     * Init the Database object
     * 
     * @return  boolean
     */
    public static function _on_include_()
    {
        self::_load_driver();

        # Load all other required libraries
        if (!class_exists('Plug\Avrelia\DatabaseQuery',     false)) 
            { include(ds(dirname(__FILE__).'/database_query.php')); }
        
        if (!class_exists('Plug\Avrelia\DatabaseResult',    false)) 
            { include(ds(dirname(__FILE__).'/database_result.php')); }

        if (!class_exists('Plug\Avrelia\DatabaseStatement', false)) 
            { include(ds(dirname(__FILE__).'/database_statement.php')); }

        if (!self::$driver->connect()) {
            Log::err("Can't connect to, or create database.");
            return false;
        }

        return true;
    }

    /**
     * Enable database plug
     * 
     * @return boolean
     */
    public static function _on_enable_()
    {
        self::_load_driver();
        return self::$driver->_create();
    }

    /**
     * Remove the database
     * 
     * @return  boolean
     */
    public static function _on_disable_()
    {
        self::_load_driver();
        return self::$driver->_destroy();
    }

    /**
     * Will load driver and config
     * 
     * @return void
     */
    protected static function _load_driver()
    {
        Plug::get_config(__FILE__);
        self::$driver = Plug::get_driver(
            __FILE__, 
            Cfg::get('plugs/database/driver'),
            __NAMESPACE__
        );
    }

    /**
     * Return PDO Driver object.
     * 
     * @return DatabaseDriverInterface
     */
    public static function get_driver()
        { return self::$driver; }

    /**
     * Execute raw SQL statement
     * 
     * @param   string  $statement
     * @param   array   $bind
     * @return  DatabaseResult
     */
    public static function execute($statement, $bind=false)
    {
        $statement = new DatabaseStatement($statement);

        if ($bind) 
            { $statement->bind($bind); }

        return $statement->execute();
    }

    /**
     * Create new record.
     * 
     * @param   array   $values
     * @param   string  $table
     * @return  DatabaseResult
     */
    public static function create($values, $table)
    {
        # Create statement
        $sql = "INSERT INTO {$table} (" . Arr::implode_keys(', ', $values) . ')' .
                ' VALUES (';

        foreach ($values as $k => $v) {
            $sql .= ":{$k}, ";
        }

        $sql = substr($sql, 0, -2);
        $sql .= ')';

        $statement = new DatabaseStatement($sql);
        $statement->bind($values);

        return $statement->execute();
    }

    /**
     * Will read (select) items from database.
     * 
     * @param   string  $table
     * @param   mixed   $condition  ['id' => 12] || 
     *                              'id=:id AND name=:name' and bind it later.
     * @param   array   $bind
     * @param   mixed   $limit      Select 12 records || range: [10, 25]
     * @param   array   $order      ['name' => 'DESC'] || 
     *                              ['name' => 'DESC', 'date' => 'ASC'] || 
     *                              ['name', 'id' => 'DESC']
     * @return  DatabaseResult
     */
    public static function find($table, $condition=false, $bind=false, $limit=false, $order=false)
    {
        # Initial statement
        $sql = "SELECT * FROM {$table}";

        # Parse condition, if is an array
        if (is_array($condition)) {
            $bind = self::_parse_condition($condition);
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
            $order_statement = '';
            foreach ($order as $field => $type) {
                if (is_integer($field)) {
                    # name, id DESC
                    $order_statement .= $type . ', ';
                }
                else {
                    # id DESC, name ASC, ...
                    $order_statement .= "{$field} {$type}, ";
                }
            }
            $sql .= ' ORDER BY ' . substr($order_statement, 0, -2);
        }

        # Make it happened
        $statement = new DatabaseStatement($sql);

        # Do we have anything to bind?
        if ($bind) {
            $statement->bind($bind);
        }

        # Execute, and return results
        return $statement->execute();
    }

    /**
     * Update particular record.
     * 
     * @param   array   $values
     * @param   string  $table
     * @param   mixed   $condition  ['id' => 12] || 'id=:id AND name=:name' and bind it.
     * @param   array   $bind
     * @return  DatabaseResult
     */
    public static function update($values, $table, $condition, $bind=false)
    {
        $sql = "UPDATE {$table} SET ";

        foreach ($values as $key => $val) {
            $sql .= "{$key}=:{$key}, ";
        }

        $sql = substr($sql, 0, -2);

        if (is_array($condition)) {
            $bind = self::_parse_condition($condition);
        }

        $sql .= ' '.$condition;

        $statement = new DatabaseStatement($sql);
        $statement->bind($values);
        if ($bind) {
            $statement->bind($bind);
        }

        return $statement->execute();
    }

    /**
     * Will delete particular item.
     * 
     * @param   string  $table
     * @param   mixed   $condition  ['id' => 12] || 'id=:id AND name=:name' and bind it later.
     * @param   array   $bind
     * @return  DatabaseResult
     */
    public static function delete($table, $condition, $bind=false)
    {
        $sql  = "DELETE FROM {$table}";

        if (is_array($condition)) {
            $bind = self::_parse_condition($condition);
        }

        $sql .= ' ' . $condition;

        $statement = new DatabaseStatement($sql);

        if ($bind) {
            $statement->bind($bind);
        }

        return $statement->execute();
    }

    /**
     * Empty all rows from the table
     * --
     * @param  string $table
     * --
     * @return DatabaseResult
     */
    public static function truncate($table)
    {
        $statement = new DatabaseStatement(self::$driver->truncate($table));
        return $statement->execute();
    }

    /**
     * Parse an array condition (like) ['id' => 12] into WHERE id=:id
     * 
     * @param   array   $condition
     * @return  string
     */
    protected static function _parse_condition(&$condition)
    {
        if (is_array($condition)) {
            $bind          = array();
            $new_condition = 'WHERE ';

            foreach ($condition as $k => $v) {
                
                $divider = strpos(str_replace(array('AND ', 'OR '), '', $k), ' ') !== false 
                            ? ' ' 
                            : ' = ';

                $kclean  = Str::clean($k, 'aA1', '_', 100);
                $new_condition .= "{$k}{$divider}:{$kclean} AND ";
                $bind[$kclean] = $v;
            }

            $condition = substr($new_condition, 0, -5);
            return $bind;
        }
        else {
            Log::war("Condition must be an array: {$condition}.");
            return $condition;
        }
    }
}
