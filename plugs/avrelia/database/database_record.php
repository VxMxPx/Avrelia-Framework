<?php namespace Plug\Avrelia; if (!defined('AVRELIA')) die('Access is denied!');

use Avrelia\Core\Log as Log;
use Avrelia\Core\Str as Str;

/**
 * DatabaseRecord
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2013, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class DatabaseRecord
{
    protected static $table;
    protected static $auto_load = true;
    protected static $relations = [
        // Use as:
        // 'relation_name' => 'params', ....
        // List of params:
        // :belongs_to  This model belongs to another one
        // :has_one     This model has one of another
        // :has_many    This model has many of another
        // :model=      Specify the name of the model, if not provided, it will
        //              be generated automatically (relation_name + _Model)
        // :where()     Condition, how the two models relate to each other.
        //              For example: this.id = that.some_id - note that 'this'
        //              and 'that' are keywords.
    ];
    protected static $fields    = [
        // Use as:
        // 'field_name' => 'params', ...
        // List of params:
        // :no_read    Field is read protected (you can't get value of it)
        // :read_only  No changing of the value
        // :primary    Positive integer
        // :unique     Validate uniqueness of field
        // :default=   Specify default value if no value is passed in.
        // :created=   Specify value which will be set when item is created,
        //             you can specify format as a second parameter: Y-m-d H:i:s
        // :updated=   Specify value which will be set when item is updated,
        //             you can specify format as a second parameter: Y-m-d H:i:s
        // :null       System won't complain if null (empty) value is beeing set
        // :required   Field is mandatory
        // :id         Positive integer
        // :boolean    Accepts 'true', 'false', '1', '0', 1, 0, true, false
        // :integer    Accept integers, - can be '1' also, return integer
        // :numeric    Accept any numeric, return any numeric
        // :float      Accept floats, - can be '1.2' also, return float
        // :positive   Require positive number
        // :date_time  Require date time in format: Y-m-d H:i:s
        // :time       Require time in format: H:i:s
        // :date       Require date in format Y-m-d
        // :rangen=    Number must be in range. For example :rangen=1-4, accepts
        //             following numbers: 1,2,3,4
        // :maxn=      Require number which isn't more than the one specified
        // :minn=      Require number which isn't lower than the one specified
        // :max=       Require string length not to be more than the one specefied
        // :min=       Require string length not to be less than the one specefied
    ];
    protected $updated_hash      = '';
    protected $relations_objects = array();
    protected $fields_values     = array();
    protected $validation_errors = array();

    /**
     * Create new instance of object, assign values.
     * --
     * @param array $data
     */
    public function __construct($data = null)
    {
        // Set the default values
        $this->_set_defaults();

        if (is_array($data)) {
            $this->update_from_array($data);
        }
    }

    /**
     * Will update object with data from database.
     * --
     * @param  array  $data
     * --
     * @return void
     */
    public function update_from_database($data = null)
    {
        // Set the default values
        $this->_set_defaults();

        if (is_array($data)) {
            $this->update_from_array($data);
        }

        // Create state hash!
        $this->_create_hash();
    }

    /**
     * Will use an array to set fields.
     * --
     * @param  array $input
     * --
     * @return void
     */
    public function update_from_array(array $input)
    {
        foreach ($input as $field => $value) {

            if (isset(static::$fields[$field]) || method_exists($this, 'set_'.$field)) {
                try {
                    $this->{$field} = $value;
                }
                catch (DatabaseValueException $e) {
                    // Alright, write it to log
                    Log::war($e->getMessage());
                }
            }
        }
    }

    /**
     * Will use post to set fields.
     * --
     * @param string $allowed -- List of allowed fields (name, email, ...)
     * --
     * @return void
     */
    public function update_from_post($allowed = null) 
    {
        $fields = array();

        if ($allowed) {
            $allowed = Str::explode_trim(',', $allowed);
            foreach ($allowed as $field) {
                if (isset($_POST[$field])) {
                    $fields[$field] = $_POST[$field];
                }
            }
        }
        else {
            $fields = $_POST;
        }

        $this->update_from_array($fields);
    }

    /**
     * Get all data where...
     * --
     * @param  string  $field
     * @param  mixed   $value
     * @param  boolean $one_only
     * --
     * @throws DatabaseValueException If invalid field or value
     * --
     * @return array  Contains instances of self || empty array
     */
    public static function get_by($field, $value, $one_only=false)
    {
        // Validate value and field
        try {
            static::_validate_value($field, $value);
        }
        catch (DatabaseValueException $e) {
            throw $e;
        }

        $db = new DatabaseQuery();

        // Execute query now
        $db
            ->select()
            ->from(static::$table)
            ->where($field, $value);
        
        if ($one_only) { $db->limit(0, 1); }

        $data = $db->execute()->as_array();

        $out = array();

        // Yes, we wanna return an empty array here
        if (empty($data)) { $data = array(0 => array()); }

        if ($one_only) {
            $obj = new static();
            $obj->update_from_database($data[0]);
            return $obj;
        }

        foreach ($data as $key => $sub_data) {
            
            if (!is_array($sub_data)) { continue; }

            try {
                $obj = new static();
                $obj->update_from_database($sub_data);
                $out[] = $obj;
            }
            catch (DatabaseValueException $e) {
                continue;
            }
        }

        return $out;
    }

    /**
     * Should automatically load information from database,
     * when relation is created.
     * --
     * @return boolean
     */
    public static function do_autoload()
    {
        return static::$auto_load;
    }

   /**
    * Create new data.
    * --
    * @param  array $data -- See the vali fields above
    * --
    * @return object      -- New instance of self.
    */
    public static function create($data) { return self::update($data, false); }

    /**
     * Update existring data.
     * --
     * @param  array   $data
     * @param  integer $id
     * --
     * @return object  -- Instance of self.
     */
    public static function update($data, $id)
    {
        $Object = new static($data);
        $Object->save($id);

        return $Object;
    }

    /**
     * Check if we have a valid record.
     * --
     * @return boolean
     */
    public function is_valid()
    {
        if (!empty($this->validation_errors)) { return false; }

        // Check if all required fields are set...
        foreach (static::$fields as $field => $params) {
            if (isset($params[':required'])) {
                if (!isset($this->fields_values) || empty($this->fields_values)) {
                    $this->validation_errors[$field] = array(
                        'DBRECORD_THE_FIELD_IS_REQUIRED',
                        array($field)
                    );
                }
            }
        }

        // Check uniqueness
        $primary_value = $this->_get_primary_field_value();
        $primaty_name  = $this->_get_primary_field_name();

        foreach (static::$fields as $field => $params) {
            if (isset($params[':unique'])) {
                if (Database::find(
                        static::$table,
                        "WHERE ({$field} = :{$field} AND {$primaty_name} != :{$primaty_name})",
                        array($this->{$field}, $primary_value))->count() > 0) {
                    
                    $this->validation_errors[$field] = array(
                        'DBRECORD_DUPLICATE_FIELD_NEEDS_TO_BE_UNIQUE',
                        array($field)
                    );
                }
            }
        }

        // Check it again!
        return empty($this->validation_errors);
    }

    /**
     * Return list of exceptions.
     * --
     * @return array
     */
    public function get_validation_errors_raw() 
    { 
        $this->is_valid();
        return $this->validation_errors; 
    }

    /**
     * Get properly formatted validation error, 
     * which can be passed to Message::add_array()
     * --
     * @param  boolean $translate Pass every error message through l() function.
     * --
     * @return array
     */
    public function get_validation_errors($translate=true)
    {
        $new_array = array();

        if (is_array($this->validation_errors)) {
            foreach ($this->validation_errors as $field => $error) {
                
                $message = $translate 
                            ? l($error[0], isset($error[1]) ? $error[1] : array())
                            : $error[0];

                $new_array[] = array(
                    'type'    => 'war',
                    'message' => $message,
                    'group'   => 'Plug\Avrelia\DatabaseRecord'
                );
            }
        }
        
        return $new_array;
    }

    /**
     * Update the record in the database.
     * --
     * @return boolean
     */
    public function save() 
    {
        // Check if is valid at all...
        if (!$this->is_valid()) {
            return false;
        }

        // Check if was modifed...
        if (!$this->_is_modified()) {
            return true;
        }

        // Check if we're updating or saving
        $id      = $this->_get_primary_field_value();
        $primary = $this->_get_primary_field_name();

        $this->_set_updated_on_timestamp();

        $db = new DatabaseQuery();

        if ($id) {

            // Unset it before update
            unset($this->fields_values[$primary]);

            $r = $db
                ->update(static::$table)
                ->set($this->fields_values)
                ->where($primary, $id)
                ->execute();
        }
        else {

            $this->_set_created_on_timestamp();

            $r = $db
                ->into(static::$table)
                ->insert($this->fields_values)
                ->execute();

            // Set last inserted id
            $id = (int) $r->inserted_id();
        }

        // Set the primary key
        $this->fields_values[$primary] = $id;

        // Create new snapshot
        $this->_create_hash();

        return $r->succeed();
    }

    /**
     * This will create data hash, which can be used to compare if data was
     * changed since last save.
     * --
     * @return void
     */
    protected function _create_hash()
    {
        $this->updated_hash = md5( serialize( $this->fields_values ) );
    }

    /**
     * Check if object was modifed since last save.
     * --
     * @return boolean
     */
    protected function _is_modified()
    {
        return !!($this->updated_hash !== md5( serialize( $this->fields_values ) ));
    }

    /**
     * Value of the primary field.
     * --
     * @return integer
     */
    protected function _get_primary_field_value()
    {
        $field = $this->_get_primary_field_name();

        return isset($this->fields_values[$field])
                ? $this->fields_values[$field]
                : false;
    }

    /**
     * Name of the primary field.
     * --
     * @return string
     */
    protected function _get_primary_field_name()
    {
        foreach (static::$fields as $field => $params) {
            if (isset($params[':primary'])) {
                return $field;
            }
        }
    }

    /**
     * Will loop through fields, and set defaults.
     * --
     * @return void
     */
    protected function _set_defaults()
    {
        // First check if we have processed fields
        static::_process_fields();

        foreach (static::$fields as $field => $params) {
            if (!isset($this->fields_values[$field])) {
                if (isset($params[':default'])) {
                    // Just set empty value, and it will automatically set default
                    $this->{$field} = $params[':default'];
                }
            }
        }
    }

    /**
     * Will loop through field, and set default created on timestamp.
     */
    protected function _set_created_on_timestamp()
    {
        // First check if we have processed fields
        static::_process_fields();

        foreach (static::$fields as $field => $params) {
            if (!isset($this->fields_values[$field]) || empty($this->fields_values[$field])) {
                if (isset($params[':created'])) {
                    // Just set empty value, and it will automatically set default
                    $this->{$field} = gmdate($params[':created']);
                }
            }
        }
    }

    /**
     * Will loop through field, and set default updated on timestamp.
     */
    protected function _set_updated_on_timestamp()
    {
        // First check if we have processed fields
        static::_process_fields();

        foreach (static::$fields as $field => $params) {
            if (!isset($this->fields_values[$field]) || empty($this->fields_values[$field])) {
                if (isset($params[':updated'])) {
                    // Just set empty value, and it will automatically set default
                    $this->{$field} = gmdate($params[':updated']);
                }
            }
        }
    }

    /**
     * This will convert hard-to-manipulate string format of fields to more
     * easy to read array format of fields.
     * --
     * @return void
     */
    protected static function _process_fields()
    {
        $fields = static::$fields;
        if (is_array(reset($fields))) { return; }

        // Alright start processing fields now
        foreach (static::$fields as $field => $params) {
            $params     = explode(' ', $params);
            $new_params = array();
            foreach ($params as $param) {
                if (strpos($param, '=') !== false) {
                    $param = explode('=', $param);
                    $key   = $param[0];
                    $param = $param[1];
                }
                else {
                    $key   = $param;
                    $param = false;
                }
                $new_params[$key] = $param;
            }
            static::$fields[$field] = $new_params;
        }
    }

    /**
     * Will trigger an error (add error to the list of error + throw exception)
     * --
     * @param  string  $message
     * @param  string  $field
     * @param  boolean $exception
     * --
     * @return void
     */
    protected static function _trigger_error($message, $field, $exception=false)
    {
        $errors[$field] = array(
            $message,
            array($field)
        );
        
        if ($exception) {
            throw new DatabaseValueException($message);
        }
    }

    /**
     * Checck if value can be assigned to the particular field.
     * --
     * @param  string $field
     * @param  mixed  $value
     * @param  array  $errors The collection of errors.
     * --
     * @throws DatabaseValueException If trying to set field which doesn't exists or trying
     *                   to set field with wrong data (type).
     * --
     * @return mixed  $value
     */
    protected static function _validate_value($field, $value, &$errors = array())
    {
        // First check if we have processed fields
        static::_process_fields();

        if (!isset(static::$fields[$field])) {
            throw new DatabaseValueException("Failed to set value, field not exists: `{$field}`");
        }

        $params = static::$fields[$field];

        // :read_only
        if (isset($params[':ready_only'])) {
            throw new DatabaseValueException("The field is read only: `{$field}`.");
        }

        // :required
        if (isset($params[':required'])) {
            if (!$value) {
                $errors[$field] = array(
                    'DBRECORD_THE_FIELD_IS_REQUIRED',
                    array($field)
                );
                throw new DatabaseValueException("The field is required: `{$field}`!");
            }
        }

        // :null
        if (isset($params[':null'])) {
            if (!$value) {
                return $value;
            }
        }

        // :boolean
        if (isset($params[':boolean'])) {
            if (!in_array(strtolower($value), array('true', 'false', '0', '1', 0, 1, false, true))) {
                $errors[$field] = array(
                    'DBRECORD_VALUE_NEEDS_TO_BE_BOOLEAND',
                    array($field)
                );
                throw new DatabaseValueException("Value needs to be boolean: `{$field}`.");
            }
            else {
                $value = in_array($value, array('true', '1', 1, true)) ? true : false;
            }
        }

        // :integer
        if (isset($params[':integer'])) {
            if ((int) $value != $value) {
                $errors[$field] = array(
                    'DBRECORD_VALUE_NEEDS_TO_BE_A_VALID_INTEGER',
                    array($field)
                );
                throw new DatabaseValueException("Value needs to be a valid integer: `{$field}`.");
            }
            else {
                $value = (int) $value;
            }
        }
            
        // :numeric
        if (isset($params[':numeric'])) {
            if (!is_numeric($value)) {
                $errors[$field] = array(
                    'DBRECORD_VALUE_NEEDS_TO_BE_NUMERIC',
                    array($field)
                );
                throw new DatabaseValueException("Value needs to be numeric: `{$field}`.");
            }
        }

        // :float
        if (isset($params[':float'])) {
            if ((float) $value != $value) {
                $errors[$field] = array(
                    'DBRECORD_VALUE_NEEDS_TO_BE_VALID_FLOAT',
                    array($field)
                );
                throw new DatabaseValueException("Value needs to be a valid float: `{$field}`.");
            }
            else {
                $value = (float) $value;
            }
        }


        // :notags
        if (isset($params[':notags'])) {
            $value = strip_tags($value);
        }

        // :id || :primary
        if (isset($params[':id']) || isset($params[':primary'])) {
            if ($value != (int) $value || (int) $value < 1) {
                $errors[$field] = array(
                    'DBRECORD_ID_OR_PRIMARY_FIELDS_VALUE_NEED_TO_BE_POSITIVE_FULL_NUMBER',
                    array($field)
                );
                throw new DatabaseValueException("ID or primary field's value need to be positive full number: `{$field}`.");
            } 
            else {
                $value = (int) $value;
            }
        }

        // :positive
        if (isset($params[':positive'])) {
            if ((float) $value <= 0) {
                $errors[$field] = array(
                    'DBRECORD_VALUE_NEEDS_TO_BE_POSITIVE_NUMBER',
                    array($field)
                );
                throw new DatabaseValueException("Value needs to be positive number: `{$field}`.");
            }
        }

        // :date_time
        if (isset($params[':date_time'])) {
            if (date('Y-m-d H:i:s', strtotime($value)) !== $value) {
                $errors[$field] = array(
                    'DBRECORD_VALUE_NEEDS_TO_BE_VALID_DATETIME',
                    array($field, 'Y-m-d H:i:s')
                );
                throw new DatabaseValueException("Value needs to be a valid date-time: `{$field}`.");
            }
        }

        // :time
        if (isset($params[':time'])) {
            if (date('H:i:s', strtotime($value)) !== $value) {
                $errors[$field] = array(
                    'DBRECORD_VALUE_NEEDS_TO_BE_VALID_TIME',
                    array($field, 'H:i:s')
                );
                throw new DatabaseValueException("Value needs to be a valid time: `{$field}`.");
            }
        }
            
        // :date
        if (isset($params[':date'])) {
            if (date('Y-m-d', strtotime($value)) !== $value) {
                $errors[$field] = array(
                    'DBRECORD_VALUE_NEEDS_TO_BE_VALID_DATE',
                    array($field, 'Y-m-d')
                );
                throw new DatabaseValueException("Value needs to be a valid date: `{$field}`.");
            }
        }

        // :maxn=
        if (isset($params[':maxn'])) {
            $max = (int) $params[':maxn'];
            if ($value > $max) {
                $errors[$field] = array(
                    'DBRECORD_VALUE_EXCEEDED_MAXIMUM_AMOUNT_ALLOWED',
                    array($field, $max)
                );
                throw new DatabaseValueException("Value exceeded the maximum amount allowed ({$max}): `{$field}`.");
            }
        }

        // :minn=
        if (isset($params[':minn'])) {
            $min = (int) $params[':minn'];
            if ($value < $min) {
                $errors[$field] = array(
                    'DBRECORD_VALUE_IS_LEES_THAN_MINIMUM_AMOUNT_ALLOWED',
                    array($field, $min)
                );
                throw new DatabaseValueException("Value is less than the minimum amount allowed ({$min}): `{$field}`.");
            }
        }

        // :rangen=
        if (isset($params[':rangen'])) {
            $range = explode('-', $params[':rangen'], 2);
            $min = (int) $range[0];
            $max = (int) $range[1];

            if ($value < $min) {
                $errors[$field] = array(
                    'DBRECORD_VALUE_IS_LEES_THAN_MINIMUM_AMOUNT_ALLOWED',
                    array($field, $min)
                );
                throw new DatabaseValueException("Value is less than the minimum amount allowed ({$min}): `{$field}`");
            }
            if ($value > $max) {
                $errors[$field] = array(
                    'DBRECORD_VALUE_EXCEEDED_MAXIMUM_AMOUNT_ALLOWED',
                    array($field, $max)
                );
                throw new DatabaseValueException("Value exceeded the maximum amount allowed ({$max}): `{$field}`.");
            }
        }

        // :max=
        if (isset($params[':max'])) {
            $max = (int) $params[':max'];
            if (mb_strlen($value) > $max) {
                $errors[$field] = array(
                    'DBRECORD_VALUE_EXCEEDED_MAXIMUM_LENGTH_ALLOWED',
                    array($field, $max)
                );
                throw new DatabaseValueException("Value exceeded the maximum length allowed ({$max}): `{$field}`.");
            }
        }
            
        // :min=
        if (isset($params[':min'])) {
            $min = (int) $params[':min'];
            if (mb_strlen($value) < $min) {
                $errors[$field] = array(
                    'DBRECORD_VALUE_IS_LEES_THAN_MINIMUM_LENGTH_ALLOWED',
                    array($field, $min)
                );
                throw new DatabaseValueException("Value is less than the minimum length allowed ({$min}): `{$field}`.");
            }
        }

        // Seems we have a valid value
        if (isset($errors[$field])) { unset($errors[$field]); }

        return $value;
    }

    /**
     * Get only model's name for the relation.
     * --
     * @param  string $relation
     * --
     * @return string
     */
    protected function _get_relation_model($relation)
    {
        // We need to resolve it! Does it exists?
        if (!isset(static::$relations[$relation])) {
            throw new DatabaseValueException("Seems relation for the object `{$relation}` is not defined.");
        }

        // Alright, do resolve it now!
        $model  = null;

        $params = Str::tokenize(static::$relations[$relation], ' ', ['(', ')']);
        
        if (!is_array($params)) {
            throw new DatabaseValueException("Seems that relation for field `{$relation}` has no definition.");
        }

        foreach ($params as $param) {
            // :model=
            if (substr($param, 0, 7) === ':model=') {
                $model = substr($param, 7);
                break;
            }
        }

        // Don't have a model? Try to guess it!
        if (!$model) {
            $model = to_camelcase($relation) . '_Model';
        }

        // Don't have the model we need?
        if (!class_exists($model)) {
            throw new DatabaseValueException("Mode not found: `{$model}`.");
        }

        return $model;
    }

    /**
     * Will resolve relation.
     * --
     * @param  string $field
     * --
     * @return object Or array
     */
    protected function _resolve_relation($field)
    {
        // We have it already stored there
        if (isset($this->relations_objects[$field])) {
            return $this->relations_objects[$field];
        }

        // We need to resolve it! Does it exists?
        if (!isset(static::$relations[$field])) {
            throw new DatabaseValueException("Seems relation for the object `{$field}` is not defined.");
        }

        // Alright, do resolve it now!
        $single = true;
        $model  = null;
        $where  = null;

        $params = Str::tokenize(static::$relations[$field], ' ', ['(', ')']);
        
        if (!is_array($params)) {
            throw new DatabaseValueException("Seems that relation for field `{$field}` has no definition.");
        }

        foreach ($params as $param) {
            
            // :has_one || :belongs_to
            if ($param === ':has_one' || $param === ':belongs_to') {
                $single = true;
                continue;
            }

            // :has_many
            if ($param === ':has_many') {
                $single = false;
                continue;
            }

            // :model=
            if (substr($param, 0, 7) === ':model=') {
                $model = substr($param, 7);
                continue;
            }

            // :where(
            if (substr($param, 0, 7) === ':where(') {
                $where = substr($param, 7, -1);
                continue;
            }
        }

        // Do we have where?
        if (!$where) {
            throw new DatabaseValueException("Where not defined for: `{$field}`.");
        }

        // Do we have model?
        if (!$model) {
            // Try to guess it!
            $model = to_camelcase($field) . '_Model';
        }

        // Do we have the model we need?
        if (!class_exists($model)) {
            throw new DatabaseValueException("Mode not found: `{$model}`.");
        }

        // Get this_where
        preg_match('/this\.([a-z_]*)/i', $where, $this_where);
        $this_where = isset($this_where[1]) ? $this_where[1] : false;
        // Get that_where
        preg_match('/that\.([a-z_]*)/i', $where, $that_where);
        $that_where = isset($that_where[1]) ? $that_where[1] : false;

        // Do we have both this and that?
        if (!$this_where || !$that_where) {
            throw new DatabaseValueException("Fields in where `this` ({$this_where}) or `that` ({$that_where}) not recognized.");
        }

        // Find this where field now if possible
        try {
            $this_where_value = $this->{$this_where};
        }
        catch (DatabaseValueException $e) {
            throw $e;
        }

        // Execute the query now...
        if ($model::do_autoload()) {
            $r = call_user_func_array([$model, 'get_by_'.$that_where], [$this_where_value]);
        }
        else {
            return new $model(array(
                $that_where => $this_where_value
            ));
        }

        if (is_array($r) && !empty($r)) {
            if ($single) {
                $this->relations_objects[$field] = $r[0];
            }
            else {
                $this->relations_objects[$field] = $r;
            }

            return $this->relations_objects[$field];
        }
        else {
            Log::inf("No results from call: `{$model}::get_by_{$that_where}({$this_where_value})`.");
            return false;
        }
    }

    /**
     * Create new relation.
     * --
     * @param  string $relation
     * @param  array  $arguments
     * --
     * @return mixed  object or false
     */
    protected function _create_relation($relation, $arguments)
    {
        try {
            $model = $this->_get_relation_model($relation);
        }
        catch(DatabaseValueException $e) {
            throw $e;
        }

        // Get where
        $where  = null;
        $params = Str::tokenize(static::$relations[$relation], ' ', ['(', ')']);
        
        if (!is_array($params)) {
            throw new DatabaseValueException("Seems that relation for field `{$relation}` has no definition.");
        }

        foreach ($params as $param) {

            // :where(
            if (substr($param, 0, 7) === ':where(') {
                $where = substr($param, 7, -1);
                break;
            }
        }

        // Do we have where?
        if (!$where) {
            throw new DatabaseValueException("Where not defined for: `{$relation}`.");
        }

        // Get this_where
        preg_match('/this\.([a-z_]*)/i', $where, $this_where);
        $this_where = isset($this_where[1]) ? $this_where[1] : false;
        // Get that_where
        preg_match('/that\.([a-z_]*)/i', $where, $that_where);
        $that_where = isset($that_where[1]) ? $that_where[1] : false;

        // Do we have both this and that?
        if (!$this_where || !$that_where) {
            throw new DatabaseValueException("Fields in where `this` ({$this_where}) or `that` ({$that_where}) not recognized.");
        }

        // Find this where field now if possible
        try {
            $this_where_value = $this->{$this_where};
        }
        catch (DatabaseValueException $e) {
            throw $e;
        }

        // Set relationship id
        $arguments = $arguments[0];
        $arguments[$that_where] = $this_where_value;

        $this->relations_objects[$relation] = new $model($arguments);

        // Return new instance of the model
        return $this->relations_objects[$relation];
    }

    /**
     * Execute for queries as get_by_id(12)
     * --
     * @param  string $method
     * @param  array  $arguments
     * --
     * @throws DatabaseValueException If invalid field or value
     * --
     * @return array     Or false if not get_by_
     */
    public static function __callStatic($method, $arguments)
    {
        if (substr($method, 0, 7) === 'get_by_' || substr($method, 0, 7) === 'one_by_') {
            $field_name = substr($method, 7);
            $value      = $arguments[0];

            try {
                return self::get_by($field_name, $value, (substr($method, 0, 7) === 'one_by_'));
            }
            catch (DatabaseValueException $e) {
                throw $e;
            }
        }
    }

    /**
     * Set particular field - preform validation on it.
     * --
     * @param string $field
     * @param mixed  $value
     */
    public function __set($field, $value)
    {
        // Check if setter method exists
        if (method_exists($this, 'set_'.$field)) {
            try {
                $value = call_user_func_array([$this, 'set_'.$field], [$value]);
            }
            catch (DatabaseValueException $e) {
                Log::war($e->getMessage());
                return;
            }
        }

        try {
            $this->fields_values[$field] = static::_validate_value($field, $value, $this->validation_errors);
        }
        catch (DatabaseValueException $e) {
            Log::war($e->getMessage());
        }
    }

    /**
     * Get particular fied if exists.
     * --
     * @param  string $field
     * --
     * @throws DatabaseValueException If requested field not found or 
     *                   if trying to access no_read field.
     * --
     * @return mixed
     */
    public function __get($field)
    {
        // Check if relationship for this field is defined
        if (isset(static::$relations[$field])) {
            return $this->_resolve_relation($field);
        }

        // Check if getter method exists
        if (method_exists($this, 'get_'.$field)) {
            return call_user_func(array($this, 'get_'.$field));
        }

        // Check if we have requested fields
        if (!static::$fields[$field]) {
            throw new DatabaseValueException('Requested field not found: ' . $field, 1);
        }

        // Check if requited fields isn't protected (:no_read)
        if (isset(static::$fields[$field][':no_read'])) {
            throw new DatabaseValueException('Accessing :no_read field.', 2);
        }

        // Check if we have requested field's value
        return isset($this->fields_values[$field])
                ? $this->fields_values[$field]
                : null;
    }

    /**
     * Get particular method.
     * --
     * @param  string $method
     * @param  array  $arguments
     * --
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        // Check if we have create command?
        if (substr($method, 0, 7) === 'create_') {
            $relation = substr($method, 7);

            if (isset(static::$relations[$relation])) {
                return $this->_create_relation($relation, $arguments);
            }
        }
    }
}