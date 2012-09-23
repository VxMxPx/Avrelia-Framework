<?php namespace Avrelia\Plug; if (!defined('AVRELIA')) die('Access is denied!');

use Avrelia\Core\Log as Log;

/**
 * DatabaseQuery Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class DatabaseQuery
{
    # Used in create method
    const DATABASE = 2;
    const TABLE    = 4;

    /**
     * @var string  CREATE || SELECT || INSERT || UPDATE || DELETE
     */
    protected $type;

    /**
     * @var array   Values, set by: create, insert, update (set).
     */
    protected $where;

    /**
     * @var array
     */
    protected $values;

    /**
     * @var array   List of joins
     */
    protected $joins;

    /**
     * @var string  Group by
     */
    protected $group;

    /**
     * @var string  Table name, set by: into, from, update, delete
     */
    protected $table;

    /**
     * @var string  Selected fields (SELECT field_1, field_2 FROM)
     */
    protected $select;

    /**
     * @var string  ORDER BY
     */
    protected $order;

    /**
     * @var string  LIMIT, set by limit, page
     */
    protected $limit;

    /**
     * @var array   Binded values (if any), set after _prepare() method call
     */
    protected $binded_values;


    /**
     * Create new table or database
     * --
     * @param   string  $name
     * @param   integer $type    DatabaseQuery::DATABASE || DatabaseQuery::TABLE
     * @param   array   $fields
     * --
     * @return  $this
     */
    public function create($name, $type, $fields=null)
    {
        $this->type  = 'CREATE';
        $this->table = $name;

        $this->values = ($type === self::TABLE)
                            ? $fields
                            : false;
        return $this;
    }

    /**
     * Values to be inserted into database.
     * Can be an array or string key/value
     * --
     * @param   mixed   $key    Array or string
     * @param   string  $value  If $key is string
     * --
     * @return  $this
     */
    public function insert($key, $value=false)
    {
        if (!is_array($key)) 
            { $key = array($key => $value); }

        $this->type   = 'INSERT';
        $this->values = is_array($this->values) 
                            ? Arr::merge($this->values, $key) 
                            : $key;
        return $this;
    }

    /**
     * Into which table do we wanna insert values
     * --
     * @param   string  $table
     * --
     * @return  $this
     */
    public function into($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Select fields from database
     * --
     * @param   string  $what
     * --
     * @return  $this
     */
    public function select($what=false)
    {
        $this->type   = 'SELECT';
        $this->select = !$what ? '*' : $what;
        return $this;
    }

    /**
     * From which table?
     * --
     * @param   string  $table
     * --
     * @return  $this
     */
    public function from($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * WHERE condition
     * --
     * @param   string  $key    name || name !=
     * @param   string  $value
     * @param   boolean $group  true=start group ( || false=stop group )
     * --
     * @return  $this
     */
    public function where($key, $value, $group=null)
    {
        $this->_make_where($key, $value, $group, 'AND');
        return $this;
    }

    /**
     * AND (WHERE) condition
     * --
     * @param   string  $key    name || name !=
     * @param   string  $value
     * @param   boolean $group  true=start group ( || false=stop group )
     * --
     * @return  $this
     */
    public function and_where($key, $value, $group=null)
    {
        $this->_make_where($key, $value, $group, 'AND');
        return $this;
    }

    /**
     * OR (WHERE) condition
     * --
     * @param   string  $key    name || name !=
     * @param   string  $value
     * @param   boolean $group  true=start group ( || false=stop group )
     * --
     * @return  $this
     */
    public function or_where($key, $value, $group=null)
    {
        $this->_make_where($key, $value, $group, 'OR');
        return $this;
    }

    /**
     * Make AND | OR Where statement
     * --
     * @param   string  $key    name || name !=
     * @param   string  $value
     * @param   boolean $group  true=start group ( || false=stop group )
     * @param   string  $type   OR || AND
     * --
     * @return  $this
     */
    protected function _make_where($key, $value, $group, $type)
    {
        if ($group !== null) {
            if ($group === true) {
                $key = '(' . $key;
            }
            elseif ($group === false) {
                $key = $key . ')';
            }
        }

        # Duplicated?
        // $i = 2;
        // $nKey = $key;
        // while (isset($this->where[$nKey])) {
        //  $nKey = $key . '_' . $i;
        //  $i ++;
        // }

        $key = $this->where ? $type . ' ' . $key  : $key;
        $this->where[$key] = $value;
    }

    /**
     * ORDER BY
     * --
     * @param   mixed   $field  string || 
     *                          ['name' => 'DESC'] ||
     *                          ['name' => 'DESC', 'date' => 'ASC'] || 
     *                          ['name', 'id' => 'DESC']
     * @param   string  $type   ASC || DESC
     * --
     * @return  $this
     */
    public function order($field, $type=false)
    {
        if (!is_array($field)) 
            { $order = array($field => $type); }
        else 
            { $order = $field; }

        # Prepear order sql
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

        $this->order = 'ORDER BY ' . substr($order_statement, 0, -2);

        return $this;
    }

    /**
     * Update table
     * --
     * @param   string  $table
     * --
     * @return  $this
     */
    public function update($table)
    {
        $this->type = 'UPDATE';
        $this->table = $table;

        return $this;
    }

    /**
     * Values to be set for insert.
     * Can be an array or string key/value
     * --
     * @param   mixed   $key    Array or string
     * @param   string  $value  If $key is string
     * --
     * @return  $this
     */
    public function set($key, $value=false)
    {
        if (!is_array($key)) 
            { $values = array($key => $value); }
        else 
            { $values = $key; }

        $this->values = is_array($this->values) 
                            ? Arr::merge($this->values, $values) 
                            : $values;
        return $this;
    }

    /**
     * DELETE FROM table
     * --
     * @param   string  $table
     * --
     * @return  $this
     */
    public function delete($table)
    {
        $this->type  = 'DELETE';
        $this->table = $table;

        return $this;
    }

    /**
     * LIMIT
     * --
     * @param   integer $start  start
     * @param   integer $count  amoutn
     * --
     * @return  $this
     */
    public function limit($start, $amount)
    {
        $this->limit = "LIMIT {$start}, {$amount}";
        return $this;
    }

    /**
     * Create calculated limit
     * --
     * @param   integer $number Page number
     * @param   integer $amount Number of items to select
     * --
     * @return  $this
     */
    public function page($number, $amount)
    {
        $start = ($number - 1) * $amount;
        $this->limit($start, $amount);

        return $this;
    }

    /**
     * Will LEFT JOIN table
     * --
     * @param   string  $table
     * @param   strign  $on
     * --
     * @return  $this
     */
    public function join($table, $on)
    {
        $this->joins[$table] = $on;
        return $this;
    }

    /**
     * Will GROUP BY field
     * --
     * @param   string  $field
     * --
     * @return  $this
     */
    public function group($field)
    {
        $this->group = $field;
        return $this;
    }

    /**
     * Execute statement
     * --
     * @return  DatabaseResult
     */
    public function execute()
    {
        # Always need to prepare before anything can be done
        $sql = $this->_prepare();

        $statement = new DatabaseStatement($sql);
        $statement->bind($this->binded_values);

        return $statement->execute();
    }

    /**
     * Get SQL statement as string
     * --
     * @return  string
     */
    public function as_string()
        { return $this->_prepare(); }

    /**
     * Get SQL statement as array. Return string (in case you selected string index) or array.
     * --
     * @param   string  $index  False for all; Otherwise, you can enter:
     *                          type, where, values, table, select, order, limit, binded
     * --
     * @return  mixed
     */
    public function as_array($index=false)
    {
        $this->_prepare();

        if ($index) {
            $index = $index == 'binded' ? 'binded_values' : $index;
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
                'binded' => $this->binded_values
            );
        }
    }

    /**
     * From all parameters create valid SQL string, 
     * and list of values for binding.
     * --
     * @return  void
     */
    protected function _prepare()
    {
        // CREATE || SELECT || INSERT || UPDATE || DELETE
        switch(strtoupper($this->type))
        {
            /* -----------------------------------------------------------------
             * CREATE STATEMENT
             */
            case 'CREATE':
                if ($this->values === false) {
                    # Create database
                    $sql = 'CREATE DATABASE IF NOT EXISTS ' . $this->table;
                }
                else {
                    # Create table
                    $sql = 'CREATE TABLE IF NOT EXISTS '.$this->table.' (';
                    foreach ($this->values as $k => $v) {
                        $sql .= "\n{$k} {$v},";
                    }
                    $sql = substr($sql, 0, -1) . "\n)";
                }
                break;

            /* -----------------------------------------------------------------
             * SELECT STATEMENT
             */
            case 'SELECT':
                # Select values from database
                $sql = 'SELECT ' . $this->select . ' FROM ' . $this->table;

                # Joins
                if (is_array($this->joins)) {
                    foreach ($this->joins as $jKey => $jVal) {
                        $sql .= " LEFT JOIN {$jKey} ON {$jVal}";
                    }
                }

                break;

            /* -----------------------------------------------------------------
             * INSERT STATEMENT
             */
            case 'INSERT':
                # Build insert statement
                $values = $this->prepare_bind($this->values, 'v_');
                $sql = 'INSERT INTO ' . $this->table .
                        ' (' . Arr::implode_keys(', ', $values) .  ') VALUES ('. 
                        implode(', ', $values) . ')';
                break;

            /* -----------------------------------------------------------------
             * UPDATE STATEMENT
             */
            case 'UPDATE':
                $values = $this->prepare_bind($this->values, 's_');
                $sql = 'UPDATE ' . $this->table . ' SET ';
                if (!empty($values)) {
                    foreach ($values as $k => $v) {
                        $sql .= "{$k}={$v}, ";
                    }
                }
                else {
                    Log::war("There was no values set.");
                    return false;
                }
                $sql = substr($sql, 0, -2);
                break;

            /* -----------------------------------------------------------------
             * DELETE STATEMENT
             */
            case 'DELETE':
                $sql = 'DELETE FROM ' . $this->table;
                break;

            /* -----------------------------------------------------------------
             * DEFAULT
             */
            default:
                Log::err("Invalid type: `{$this->type}`.");
                return false;
        }

        /* ---------------------------------------------------------------------
         * WHERE CONDITION
         */
        if (is_array($this->where)) {
            $where     = $this->prepare_bind($this->where, 'w_');
            $where_str = '';
            foreach ($where as $k => $v) {
                $k = trim($k);
                if (substr($k,-1) === ')') {
                    $cls_group = ')';
                    $k = substr($k,0,-1);
                }
                else {
                    $cls_group = '';
                }
                $divider = strpos(str_replace(array('AND ', 'OR '), '', $k), ' ') !== false 
                                ? ' ' 
                                : ' = ';

                $where_str .= "{$k}{$divider}{$v}{$cls_group} ";
            }
            $where = 'WHERE ' . substr($where_str, 0, -1);
            $sql  .= ' ' . $where;
        }

        /* ---------------------------------------------------------------------
         * ORDER BY
         */
        if ($this->order) {
            $sql .= ' ' . $this->order;
        }

        /* ---------------------------------------------------------------------
         * LIMIT
         */
        if ($this->limit) {
            $sql .= ' ' . $this->limit;
        }

        /* ---------------------------------------------------------------------
         * GROUP BY
         */
        if ($this->group) {
            $sql .= ' GROUP BY ' . $this->group;
        }

        return $sql;
    }

    /**
     * Will add values to be $this->binded_values. Require array, return array,
     * with key => :key_bind
     * --
     * @param   array   $values
     * @param   string  $prefix Bind key prefix :<prefix><key>
     * --
     * @return  array
     */
    public function prepare_bind($values, $prefix='')
    {
        $result = array();

        foreach ($values as $key => $val)
        {
            $key_bind = str_replace(array('AND ', 'OR ', 'LIKE'), '', $key);
            $key_bind = $prefix . Str::clean($key_bind, 'aA1', '_');

            # Duplicated?
            $n_key_bind = $key_bind;
            $i = 2;
            while (isset($this->binded_values[$n_key_bind])) {
                $n_key_bind = "{$key_bind}_{$i}";
                $i++;
            }

            $result[$key] = ':'.$n_key_bind;
            $this->binded_values[$n_key_bind] = $val;
        }

        return $result;
    }
}
