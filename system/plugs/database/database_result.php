<?php namespace Avrelia\Plug; if (!defined('AVRELIA')) die('Access is denied!');

use Avrelia\Core\Log as Log;

/**
 * DatabaseResult
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class DatabaseResult
{
    /**
     * @var PDOStatement  Instance of PDOStatement
     */
    protected $PDOStatement;

    /**
     * @var array  List of fetched items.
     */
    protected $fetched;

    /**
     * @var string  Last inserted ID.
     */
    protected $last_id;

    /**
     * @var boolean  The status of PDO statement. 
     *               Was execution successful or not.
     */
    protected $status;


    /**
     * Construct the database result object.
     * This require prepeared PDOStatement, which will be 
     * executed on construction of this this class.
     * --
     * @param   PDOStatement    $PDOStatement   Prepeared PDO statement.
     * --
     * @return  void
     */
    public function __construct($PDOStatement)
    {
        if (is_object($PDOStatement)) {
            $this->PDOStatement = $PDOStatement;
            $this->status = $this->PDOStatement->execute();
            $this->last_id = Database::get_driver()->get_PDO()->lastInsertId();

            if (!$this->status) {
                trigger_error("Failed to execute: `" . 
                    print_r(Database::get_driver()->get_PDO()->errorInfo(), true).'`.', 
                    E_USER_WARNING);
            }
        }
        else {
            $this->status = false;
        }
    }

    /**
     * Return true if this query succeed, and false if didn't.
     * --
     * @return  boolean
     */
    public function succeed()
        { return $this->status; }

    /**
     * Return true if this query failed, and false if succeed
     * --
     * @return  boolean
     */
    public function failed()
        { return !$this->status; }

    /**
     * Returns the number of columns in the result set represented by the PDOStatement object.
     * If there is no result set, PDOStatement::columnCount() returns 0.
     * --
     * @return  integer
     */
    public function count()
        { return $this->status ? count($this->as_array()) : 0; }

    /**
     * Will return ALL rows as an array.
     * You can enter index, if you want particular row (this will still fetch all).
     * You can set index to true, to get get next row (if you're doing loop).
     * --
     * @param   integer $index
     * --
     * @return  array
     */
    public function as_array($index=false)
    {
        if (is_object($this->PDOStatement)) {
            if ($index === false) {
                if (!is_array($this->fetched)) {
                    $this->fetched = $this->PDOStatement->fetchAll(PDO::FETCH_ASSOC);
                }
                return $this->fetched;
            }
            elseif ($index === true) {
                return $this->PDOStatement->fetch(PDO::FETCH_ASSOC);
            }
            elseif (is_integer($index)) {
                $fetched = $this->as_array(false);
                return isset($fetched[$index]) ? $fetched[$index] : false;
            }
        }
        else {
            Log::war('PDOStatement isn\'t object, returning empty array!');
            return array();
        }
    }

    /**
     * Return _raw_ PDOStatement object.
     * Read more about PDO: http://www.php.net/manual/en/class.pdostatement.php
     * --
     * @return  PDOStatement
     */
    public function as_raw()
        { return $this->PDOStatement; }

    /**
     * Return ID (of last inserted statement)
     * --
     * @return  mixed
     */
    public function inserted_id()
        { return $this->last_id; }
}
