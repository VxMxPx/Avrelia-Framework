<?php namespace Plug\Avrelia; if (!defined('AVRELIA')) die('Access is denied!');

use Avrelia\Core\Log as Log;

/**
 * Database Driver Base
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class DatabaseDriverBase
{
    /**
     * @var object Link to the PDO Connection
     */
    protected $PDO;

    /**
     * Init the database driver, called initialy when connection is established.
     * --
     * @return  void
     */
    public function __construct()
    {
        # Check If is PDO enabled...
        if (!class_exists('\PDO', false)) {
            trigger_error(
                'PDO class doesn\'t exists. Please enable PDO extension.', 
                E_USER_ERROR);
        }
    }

    /**
     * Bind values and execute particular statement.
     * --
     * @param   string  $statement
     * @param   array   $bind
     * --
     * @return  PDOStatement
     */
    public function prepare($statement, $bind=false)
    {
        Log::inf("Will prepare following statement: `{$statement}` with params: ". 
                    print_r($bind, true));

        $link = $this->PDO->prepare($statement);

        if (is_object($link))
        {
            # Bind all values
            if ($bind) {
                foreach ($bind as $key => $value) {
                    # Value type
                    if (is_integer($value)) {
                        $type = \PDO::PARAM_INT;
                    }
                    elseif (is_bool($value)) {
                        $type = \PDO::PARAM_BOOL;
                    }
                    elseif (is_null($value)) {
                        $type = \PDO::PARAM_NULL;
                    }
                    else {
                        $type = \PDO::PARAM_STR;
                    }

                    if (!is_numeric($key)) {
                        $link->bindValue(':'.$key, $value, $type);
                    }
                    else {
                        $link->bindValue((int)$key+1, $value, $type);
                    }
                }
            }
        }
        else {
            trigger_error(
                "Failed to prepare: `" . print_r($this->PDO->errorInfo(), true) . '`.', 
                E_USER_WARNING);
        }

        return new DatabaseResult($link);
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
        return 'TRUNCATE TABLE ' . $table . ';';
    }

    /**
     * Will get -raw- PDO class.
     * --
     * @return  PDO
     */
    public function get_PDO()
        { return $this->PDO; }
}