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
 */
class cDatabaseQuery
{
	# Used in create method
	const DATABASE = 2;
	const TABLE    = 4;

	/**
	 * @var	string	CREATE || SELECT || INSERT || UPDATE || DELETE
	 */
	private $type;

	/**
	 * @var	array	Values, set by: create, insert, update (set).
	 */
	private $where;

	/**
	 * @var	array
	 */
	private $values;

	/**
	 * @var	string	Table name, set by: into, from, update, delete
	 */
	private $table;

	/**
	 * @var	string	Selected fields (SELECT field_1, field_2 FROM)
	 */
	private $select;

	/**
	 * @var	string	ORDER BY
	 */
	private $order;

	/**
	 * @var	string	LIMIT, set by limit, page
	 */
	private $limit;

	/**
	 * @var	array	Binded values (if any), set after prepare() method call
	 */
	private $bindedValues;


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
		$this->type   = 'SELECT';
		$this->select = !$what ? '*' : $what;
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
		$this->andWhere($key, $value);
		return $this;
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
	{
		$key = $this->where ? 'AND ' . $key  : $key;
		$this->where[$key] = $value;

		return $this;
	}
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
	{
		$key = $this->where ? 'OR ' . $key  : $key;
		$this->where[$key] = $value;

		return $this;
	}
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
	{
		if (!is_array($field)) {
			$order = array($field => $type);
		}
		else {
			$order = $field;
		}

		# Prepear order sql
		$orderStatement = '';
		foreach ($order as $field => $type) {
			if (is_integer($field)) {
				# name, id DESC
				$orderStatement .= $type . ', ';
			}
			else {
				# id DESC, name ASC, ...
				$orderStatement .= "{$field} {$type}, ";
			}
		}

		$this->order = 'ORDER BY ' . substr($orderStatement, 0, -2);

		return $this;
	}
	//-

	/**
	 * Update table
	 * --
	 * @param	string	$table
	 * --
	 * @return	$this
	 */
	public function update($table)
	{
		$this->type = 'UPDATE';
		$this->table = $table;

		return $this;
	}
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
	{
		if (!is_array($key)) {
			$values = array($key => $value);
		}
		else {
			$values = $key;
		}

		$this->values = is_array($this->values) ? vArray::Merge($this->values, $values) : $values;

		return $this;
	}
	//-

	/**
	 * DELETE FROM table
	 * --
	 * @param	string	$table
	 * --
	 * @return	$this
	 */
	public function delete($table)
	{
		$this->type  = 'DELETE';
		$this->table = $table;

		return $this;
	}
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
	{
		$this->limit = "LIMIT {$start}, {$amount}";

		return $this;
	}
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
	{
		$start = ($number - 1) * $amount;
		$this->limit($start, $amount);

		return $this;
	}
	//-

	/**
	 * Execute statement
	 * --
	 * @return	cDatabaseResult
	 */
	public function execute()
	{
		# Always need to prepare before anything can be done
		$sql = $this->prepare();

		$Statement = new cDatabaseStatement($sql);
		$Statement->bind($this->bindedValues);

		return $Statement->execute();
	}
	//-

	/**
	 * Get SQL statement as string
	 * --
	 * @return	string
	 */
	public function asString()
	{
		return $this->prepare();
	}
	//-

	/**
	 * Get SQL statement as array. Return string (in case you selected string index) or array.
	 * --
	 * @param	string	$index	False for all; Otherwise, you can enter:
	 * 							type, where, values, table, select, order, limit, binded
	 * --
	 * @return	mixed
	 */
	public function asArray($index=false)
	{
		$this->prepare();

		if ($index) {
			$index = $index == 'binded' ? 'bindedValues' : $index;
			return property_exists($this, $index) ? $this->{$index} : false;
		}
		else {
			return array(
				'type'   => $this->type,
				'where'  => $this->where,
				'values' => $this->values,
				'table'  => $this->table,
				'select' => $this->select,
				'order'  => $this->order,
				'limit'  => $this->limit,
				'binded' => $this->bindedValues
			);
		}
	}
	//-

	/**
	 * From all parameters create valid SQL string, and list of values for binding.
	 * --
	 * @return	void
	 */
	private function prepare()
	{
		// CREATE || SELECT || INSERT || UPDATE || DELETE
		switch(strtoupper($this->type))
		{
			case 'CREATE':
				if ($this->values === false) {
					# Create database
					$sql = 'CREATE DATABASE IF NOT EXISTS ' . $this->table;
				}
				else {
					# Create table
					$sql = 'CREATE TABLE IF NOT EXISTS '.$this->table.' (';
					foreach ($this->values as $k => $v) {
						$sql .= "\n{$k}	{$v},";
					}
					$sql = substr($sql, 0, -1) . "\n)";
				}
				break;

			case 'SELECT':
				# Select values from database
				$sql = 'SELECT ' . $this->select . ' FROM ' . $this->table;
				break;

			case 'INSERT':
				# Build insert statement
				$Values = $this->prepareBind($this->values, 'v_');
				$sql = 'INSERT INTO ' . $this->table .
						' (' . vArray::ImplodeKeys(', ', $Values) .  ') VALUES (' . implode(', ', $Values) . ')';
				break;

			case 'UPDATE':
				$Values = $this->prepareBind($this->values, 's_');
				$sql = 'UPDATE ' . $this->table . ' SET ';
				if (!empty($Values)) {
					foreach ($Values as $k => $v) {
						$sql .= "{$k}={$v}, ";
					}
				}
				else {
					Log::Add('WAR', "It seems there was no values set.", __LINE__, __FILE__);
					return false;
				}
				$sql = substr($sql, 0, -2);
				break;

			case 'DELETE':
				$sql = 'DELETE FROM ' . $this->table;
				break;

			default:
				Log::Add('ERR', "Invalid type: `{$this->type}`.", __LINE__, __FILE__);
				return false;
		}

		# Append where
		if (is_array($this->where)) {
			$where     = $this->prepareBind($this->where, 'w_');
			$whereStr  = '';
			foreach ($where as $k => $v) {
				$k         = trim($k);
				$divider   = strpos(str_replace(array('AND ', 'OR '), '', $k), ' ') !== false ? ' ' : ' = ';
				$whereStr .= "{$k}{$divider}{$v} ";
			}
			$where = 'WHERE ' . substr($whereStr, 0, -1);
			$sql  .= ' ' . $where;
		}

		# Append order
		if ($this->order) {
			$sql .= ' ' . $this->order;
		}

		# Append limit
		if ($this->limit) {
			$sql .= ' ' . $this->limit;
		}

		return $sql;
	}
	//-

	/**
	 * Will add values to be $this->bindedValues. Require array, return array,
	 * with key => :key_bind
	 * --
	 * @param	array	$Values
	 * @param	string	$prefix	Bind key prefix :<prefix><key>
	 * --
	 * @return	array
	 */
	public function prepareBind($Values, $prefix='')
	{
		$Result = array();

		foreach ($Values as $key => $val)
		{
			$keyBind = str_replace(array('AND ', 'OR ', 'LIKE'), '', $key);
			$keyBind = ':' . $prefix . vString::Clean($keyBind, 200, 'aA1c', '_');
			$Result[$key] = $keyBind;
			$this->bindedValues[$keyBind] = $val;
		}

		return $Result;
	}
	//-
}
//--
