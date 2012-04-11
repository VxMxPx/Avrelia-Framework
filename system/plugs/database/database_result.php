<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Database Result Class
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-03-21
 */
class cDatabaseResult
{
	/**
	 * @var	PDOStatement	Instance of PDOStatement
	 */
	private $PDOStatement;

	/**
	 * @var	array	List of fetched items.
	 */
	private $Fetched;

	/**
	 * @var	string	Last inserted ID.
	 */
	private $lastId;

	/**
	 * @var	boolean	The status of PDO statement. Was execution valid / successful or not.
	 */
	private $status;


	/**
	 * Construct the database result object.
	 * This require prepeared PDOStatement, which will be executed on construction
	 * of this this class.
	 * --
	 * @param	PDOStatement	$PDOStatement	Prepeared PDO statement.
	 * --
	 * @return	void
	 */
	public function __construct($PDOStatement)
	{
		if ($PDOStatement) {
			$this->PDOStatement = $PDOStatement;
			$this->status = $this->PDOStatement->execute();
			$this->lastId = cDatabase::_getDriver()->getPDO()->lastInsertId();

			if (!$this->status) {
				trigger_error("Failed to execute: `" . print_r(cDatabase::_getDriver()->getPDO()->errorInfo(), true) . '`.', E_USER_WARNING);
			}
		}
		else {
			$this->status = false;
		}
	}
	//-

	/**
	 * Return true if this query succeed, and false if didn't
	 * --
	 * @return	boolean
	 */
	public function succeed()
	{
		return $this->status;
	}
	//-

	/**
	 * Return true if this query failed, and false if succeed
	 * --
	 * @return	boolean
	 */
	public function failed()
	{
		return !$this->status;
	}
	//-

	/**
	 * Returns the number of columns in the result set represented by the PDOStatement object.
	 * If there is no result set, PDOStatement::columnCount() returns 0.
	 * --
	 * @return	integer
	 */
	public function count()
	{
		return count($this->asArray());
	}
	//-

	/**
	 * Will return ALL rows as an array.
	 * You can enter index, if you want particular row (this will still fetch all).
	 * You can set index to true, to get get next row (if you're doing loop).
	 * --
	 * @param	integer	$index
	 * --
	 * @return	array
	 */
	public function asArray($index=false)
	{
		if ($index === false) {
			if (!is_array($this->Fetched)) {
				$this->Fetched = $this->PDOStatement->fetchAll(PDO::FETCH_ASSOC);
			}
			return $this->Fetched;
		}
		elseif ($index === true) {
			return $this->PDOStatement->fetch(PDO::FETCH_ASSOC);
		}
		elseif (is_integer($index)) {
			$Fetched = $this->asArray(false);
			return isset($Fetched[$index]) ? $Fetched[$index] : false;
		}
	}
	//-

	/**
	 * Will put particular result's index, to record.
	 * You MUST enter index, of particular row! Most likely this will be 0, for
	 * first item returned (offten the only one).
	 * --
	 * @param	integer	$index
	 * --
	 * @return	cDatabaseRecord
	 */
	# public function asRecord($index)
	# {
    #
	# }
	//-

	/**
	 * Return _raw_ PDOStatement object.
	 * Read more about it here: http://www.php.net/manual/en/class.pdostatement.php
	 * --
	 * @return	PDOStatement
	 */
	public function asRaw()
	{
		return $this->PDOStatement;
	}
	//-

	/**
	 * Return ID (of last inserted statement)
	 * --
	 * @return	mixed
	 */
	public function insertId()
	{
		return $this->lastId;
	}
	//-
}
//--
