<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Database Statement Class
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-03-21
 */
class cDatabaseStatement
{
	/**
	 * @var	string	$statement	An SQL statement which will be executed.
	 */
	private $statement;

	/**
	 * @var	array	An array of all values which need to be binded.
	 */
	private $Bind;


	/**
	 * Construct object with some initial statement.
	 * --
	 * @param	string	$statement
	 * --
	 * @return	void
	 */
	public function __construct($statement)
	{
		$this->statement = $statement;
	}
	//-

	/**
	 * Add string to the end of the statement.
	 * --
	 * @param	string	$statement
	 * --
	 * @return	$this
	 */
	public function add($statement)
	{
		$this->statement .= ' ' . $statement;
		return $this;
	}
	//-

	/**
	 * Replace whole statement with something else.
	 * --
	 * @param	string	$statement
	 * --
	 * @return	$this
	 */
	public function replace($statement)
	{
		$this->statement = $statement;
		return $this;
	}
	//-

	/**
	 * Will bind values. Can accept $key as array or as string,
	 * If you entered $key as string, then you must enter $val also.
	 * --
	 * @param	mixed	$key
	 * @param	string	$val
	 * --
	 * @return	$this
	 */
	public function bind($key, $val=false)
	{
		if (is_array($key)) {
			foreach ($key as $k => $v) {
				$this->Bind[$k] = $v;
			}
		}
		else {
			$this->Bind[$key] = $val;
		}

		return $this;
	}
	//-

	/**
	 * Execute statement and return cDatabaseResult object.
	 * --
	 * @return	cDatabaseResult
	 */
	public function execute()
	{
		return cDatabase::_getDriver()->prepare($this->statement, $this->Bind);
	}
	//-
}
//--
