<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Database Query Constructor
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2009, Avrelia.com
 * @license    http://avrelia.com/license
 * @link       http://avrelia.com
 * @since      Version 0.80
 * @since      čet apr 05 16:42:15 2012
 * --
 * @param	string	$type	CREATE || SELECT || INSERT || UPDATE || DELETE
 * @param	array	$where	Where confition added by where, andWhere, orWhere
 * @param	array	$values	Values, set by: create, insert, update (set)
 * @param	string	$table	Table name, set by: into, from, update, delete, count
 * @param	string	$select	Selected fields (SELECT field_1, field_2 FROM)
 * @param	array	$order	ORDER BY
 * @param	string	$limit	LIMIT, set by limit, page
 */
class cDatabaseQuery
{
	const DATABASE = 2;
	const TABLE    = 4;

	private $type;
	private $where;
	private $values;
	private $table;
	private $select;
	private $order;
	private $limit;

	/**
	 * Create new table or database
	 * --
	 * @param	string	$name
	 * @param	integer	$type		cDatabaseQuery::DATABASE || cDatabaseQuery::TABLE
	 * @param	array	$fields
	 * --
	 * @return	$this
	 */
	public function create($name, $type, $fields=null)
	{
		$this->type  = 'CREATE';
		$this->table = $name;

		if ($type === self::TABLE) {
			$this->values = $fields;
		}
		else {
			$this->values = false;
		}

		return $this;
	}
	//-

	/**
	 * Values to be inserted into database.
	 * Can be an array or string key/value
	 * --
	 * @param	mixed	$key	Array or string
	 * @param	string	$value	If $key is string
	 * --
	 * @return	$this
	 */
	public function insert($key, $value=false)
	{
		if (!is_array($key)) {
			$key = array($key => $value);
		}

		$this->type   = 'INSERT';
		$this->values = is_array($this->values) ? vArray::Merge($this->values, $key) : $key;

		return $this;
	}
	//-

	/**
	 * Into which table do we wanna insert values
	 * --
	 * @param	string	$table
	 * --
	 * @return	$this
	 */
	public function into($table)
	{
		$this->table = $table;

		return $this;
	}
	//-

	/**
	 * Select fields from database
	 * --
	 * @param	string	$what
	 * --
	 * @return	$this
	 */
	public function select($what=false)
	{
		$this->table  = 'SELECT';
		$this->select = $what;
		return $this;
	}
	//-

	/**
	 * From which table?
	 * --
	 * @param	string	$table
	 * --
	 * @return	$this
	 */
	public function from($table)
	{
		$this->table = $table;
		return $this;
	}
	//-

	/**
	 * WHERE condition
	 * --
	 * @param	string	$key	name || name != || (name || name)
	 * @param	string	$value
	 * --
	 * @return	$this
	 */
	public function where($key, $value)
	{
		
	}
	//-

	/**
	 * AND (WHERE) condition
	 * --
	 * @param	string	$key	name || name != || (name || name)
	 * @param	string	$value
	 * --
	 * @return	$this
	 */
	public function andWhere($key, $value)
	{}
	//-

	/**
	 * OR (WHERE) condition
	 * --
	 * @param	string	$key	name || name != || (name || name)
	 * @param	string	$value
	 * --
	 * @return	$this
	 */
	public function orWhere($key, $value)
	{}
	//-

	/**
	 * ORDER BY
	 * --
	 * @param	mixed	$field	string || ['name' => 'DESC'] ||
	 * 							['name' => 'DESC', 'date' => 'ASC'] || ['name', 'id' => 'DESC']
	 * @param	string	$type	ASC || DESC
	 * --
	 * @return	$this
	 */
	public function order($field, $type=false)
	{}
	//-

	/**
	 * Update table
	 * --
	 * @param	string	$table
	 * --
	 * @return	$this
	 */
	public function update($table)
	{}
	//-

	/**
	 * Values to be set for insert.
	 * Can be an array or string key/value
	 * --
	 * @param	mixed	$key	Array or string
	 * @param	string	$value	If $key is string
	 * --
	 * @return	$this
	 */
	public function set($key, $value=false)
	{}
	//-

	/**
	 * DELETE FROM table
	 * --
	 * @param	string	$table
	 * --
	 * @return	$this
	 */
	public function delete($table)
	{}
	//-

	/**
	 * LIMIT
	 * --
	 * @param	integer	$start	start
	 * @param	integer	$count	amoutn
	 * --
	 * @return	$this
	 */
	public function limit($start, $amount)
	{}
	//-

	/**
	 * Create calculated limit
	 * --
	 * @param	integer	$number	Page number
	 * @param	integer	$amount	Number of items to select
	 * --
	 * @return	$this
	 */
	public function page($number, $amount)
	{}
	//-

	/**
	 * Amount of fields in particular table (can be used with where())
	 * --
	 * @param	string	$table
	 * --
	 * @return	$this
	 */
	public function count($table)
	{}
	//-

	/**
	 * Execute statement
	 * --
	 * @return	cDatabaseResult
	 */
	public function execute()
	{}
	//-
}
//--
