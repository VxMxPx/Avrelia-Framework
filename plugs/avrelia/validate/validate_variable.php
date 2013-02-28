<?php

namespace Plug\Avrelia;

use Avrelia\Core\Plug as Plug;

/**
 * ValidateVariable Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class ValidateVariable
{
    private $is_valid    = true;  # boolean
    private $add_message = true;  # boolean  If true, message will be added
                                  #          using Message plug.
    private $value       = '';    # string   Actual field's value.
    private $name        = false; # string   The name of the field.
    private $need_value  = false; # boolean  To be valid, does this field need
                                  #          to have value?

    /**
     * New Validation Assigment!
     * --
     * @param   string  $value
     * @param   string  $name
     * --
     * @return  void
     */
    public function __construct($value, $name=false)
    {
        $this->value = $value;
        $this->name  = $name;

        if (!$name && Plug::has('Plug\\Avrelia\\Message'))
            { $this->add_message = false; }
    }

    /**
     * Is valid?
     * --
     * @return  boolean
     */
    public function is_valid()
    {
        return !$this->need_value && empty($this->value)
                    ? true
                    : $this->is_valid;
    }

    /*  ****************************************************** *
     *          Filters
     *  **************************************  */

    /**
     * Check if has value (not empty)
     * --
     * @return  $this
     */
    public function has_value()
    {
        $return = true;

        $this->need_value = true;

        if (is_string($this->value)) {
            if (strlen($this->value) === 0)
                { $return = false; }
        }
        elseif (empty($this->value)) {
            $return = false;
        }

        if ($return == false) {
            if ($this->add_message) {
                Message::war(
                    l('VAL_FIELD_CANT_BE_EMPTY', $this->name),
                    __CLASS__);
            }
            $this->is_valid = false;
        }

        // Always return self
        return $this;
    }

    /**
     * Check if variable contains valid e-mail
     * --
     * @param   string  $domain Check if is on particular domain
     *                          (example: @gmail.com)
     * --
     * @return  $this
     */
    public function is_email($domain=null)
    {
        # Preform test
        $return = filter_var($this->value, FILTER_VALIDATE_EMAIL);

        # Add any message?
        if (!$return) {
            if ($this->add_message) {
                Message::war(
                    l('VAL_FIELD_DOESNT_VALID_EMAIL', $this->name),
                    __CLASS__);
            }
            $this->is_valid = false;
            return $this;
        }

        # Valid domain?
        if (!is_null($domain)) {
            $len_front = strlen($domain);
            $len_back  = $len_front * -1;
            $return = (substr($this->value, $len_back, $len_front) == $domain)
                        ? true
                        : false;

            if (!$return) {
                if ($this->add_message) {
                    Message::war(
                        l(
                            'VAL_FIELD_MUST_EMAIL_ON_DOMAIN',
                            array($this->name, $domain)),
                    __CLASS__);
                }
                $this->is_valid = false;
            }
        }

        return $this;
    }

    /**
     * Check if is valid IP address
     * --
     * @param   string  $mask
     * --
     * @return  $this
     */
    public function is_ip($mask=null)
    {
        $return = filter_var($this->value, FILTER_VALIDATE_IP);

        if (!$return) {
            if ($this->add_message) {
                Message::war(
                    l('VAL_FIELD_MUST_VALID_IP_ADDRESS', $this->name),
                    __CLASS__);
            }
            $this->is_valid = false;
            return $this;
        }

        # Check for mask...
        $mask_reg = str_replace(array('.', '*'), array('\.', '.*'), $mask);
        if (!preg_match("/^{$mask_reg}$/", $this->value)) {
            if ($this->add_message) {
                Message::war(
                    l('VAL_FIELD_IP_MUST_EQ_MASK', array($this->name, $mask)),
                    __CLASS__);
            }
            $this->is_valid = false;
        }

        return $this;
    }

    /**
     * Test for particular Regex
     * --
     * @param   string  $mask
     * --
     * @return  $this
     */
    public function is_regex($mask)
    {
        $cleaned = Str::clean_regex($this->value, $mask);

        if ($cleaned != $this->value) {
            if ($this->add_message) {
                Message::war(
                    l('VAL_FIELD_MUST_MATCH_PATTERN', array($this->name, $mask)),
                    __CLASS__);
            }
            $this->is_valid = false;
        }

        return $this;
    }

    /**
     * Check if is URL
     * --
     * @return  $this
     */
    public function is_url()
    {
        if (!filter_var($this->value, FILTER_VALIDATE_URL)) {
            if ($this->add_message) {
                Message::war(
                    l('VAL_FIELD_MUST_VALID_WEB_ADDRESS', $this->name),
                    __CLASS__);
            }
            $this->is_valid = false;
        }

        return $this;
    }

    /**
     * Check if is numeric and is it in particular range
     * --
     * @param   integer $min
     * @param   integer $max
     * --
     * @return  $this
     */
    public function is_numeric($min=null, $max=null)
    {
        if (is_numeric($this->value))
        {
            $variable = (int) $this->value;

            if (!is_null($min)) {
                if ($variable < $min) {
                    if ($this->add_message) {
                        Message::war(
                            l(
                                'VAL_FIELD_NUM_MIN_AT_LEAST',
                                array($this->name, $min)),
                            __CLASS__);
                    }
                    $this->is_valid = false;
                }
            }
            if (!is_null($max)) {
                if ($variable > $max) {
                    if ($this->add_message) {
                        Message::war(
                            l(
                                'VAL_FIELD_NUM_MAX_CANT_MORE_THAN',
                                array($this->name, $max)),
                            __CLASS__);
                    }
                    $this->is_valid = false;
                }
            }

        }
        else {
            if ($this->add_message) {
                Message::war(
                    l('VAL_FIELD_MUST_BE_NUMERIC', $this->name),
                    __CLASS__);
            }
            $this->is_valid = false;
        }

        return $this;
    }

    /**
     * Check if is numeric, - whole numbers, not float
     * --
     * @param   boolean $only_positive  Must have only positive numbers
     * --
     * @return  $this
     */
    public function is_numeric_whole($only_positive=false)
    {
        # Is Whole?
        if ((int)$this->value != $this->value) {
            if ($this->add_message) {
                Message::war(
                    l('VAL_FIELD_MUST_BE_WHOLE_NUMBER', $this->name),
                    __CLASS__);
            }
            $this->is_valid = false;
            return $this;
        }

        # Is positive?
        if ($only_positive) {
            if ((int)$this->value < 0) {
                if ($this->add_message) {
                    Message::war(
                        l('VAL_FIELD_MUST_BE_POSITIVE_NUMER', $this->name),
                        __CLASS__);
                }
                $this->is_valid = false;
            }
        }

        return $this;
    }

    /**
     * Check if is boolean
     * --
     * @param   boolean $particular Set to true / false: it will check for
     *                              particular boolean value (either true or false)
     * @param   boolean $strict     If set to "false" we'll approve also:
     *                              1,0,"true","false","yes","no","on","off",
     *                              "1", "0" (string values)
     * --
     * @return  $this
     */
    public function is_boolean($particular=null, $strict=true)
    {
        # Internal value
        $value = $this->value;

        # Check for non-strict
        if (!$strict) {
            $true_values  = array('1', 'true', 'yes', 'on',  1);
            $false_values = array('0', 'false', 'no', 'off', 0);

            if (in_array(strtolower($value), $true_values))
                { $value = true; }
            elseif (in_array(strtolower($value), $false_values))
                { $value = false; }
        }

        # Check if is boolean
        if (!is_bool($value)) {
            if ($this->add_message) {
                Message::war(
                    l('VAL_FIELD_MUST_CONTAIN_BOOLEAN', $this->name),
                    __CLASS__);
            }
            $this->is_valid = false;
            return $this;
        }

        # Is particular?
        if (!is_null($particular)) {
            if ($value !== $particular) {
                if ($this->add_message) {
                    if ($particular === true) {
                        Message::war(
                            l(
                                'VAL_FIELD_MUST_BE_SET_TO_TRUE',
                                $this->name),
                            __CLASS__);
                    }
                    else {
                        Message::war(
                            l(
                                'VAL_FIELD_MUST_BE_SET_TO_FALSE',
                                $this->name),
                            __CLASS__);
                    }
                }
                $this->is_valid = false;
            }
        }

        return $this;
    }

    /**
     * Check if string is particular length
     * --
     * @param   integer $min
     * @param   integer $max
     * --
     * @return  $this
     */
    public function is_length($min=null, $max=null)
    {
        if (!is_null($min)) {
            if (strlen($this->value) < $min) {
                if ($this->add_message) {
                    Message::war(
                        l(
                            'VAL_FIELD_MUST_CONTAIN_AT_LEAST',
                            array($this->name, $min)),
                        __CLASS__);
                }
                $this->is_valid = false;
                return $this;
            }
        }

        if (!is_null($max)) {
            if (strlen($this->value) > $max) {
                if ($this->add_message) {
                    Message::war(
                        l(
                            'VAL_FIELD_MUST_HAVE_NOT_MORE_THAN',
                            array($this->name, $max)),
                        __CLASS__);
                }
                $this->is_valid = false;
            }
        }

        return $this;
    }

    /**
     * Check if field contain valid date
     * --
     * @param   string  $format 'd.m.Y'
     * --
     * @return  $this
     */
    public function is_date($format)
    {
        $final_date = strtotime($this->value);
        $final_date = date($format, strtotime(date('Y-m-d H:i:s', $final_date)));

        if ($final_date != $this->value) {
            if ($this->add_message) {
                Message::war(
                    l(
                        'VAL_FIELD_MUST_HAVE_VALID_DATE',
                        array($this->name, $format)),
                    __CLASS__);
            }
            $this->is_valid = false;
        }
        return $this;
    }

    /**
     * Check if field contain exact value
     * --
     * @param   array   $allowed        Array of allowed values
     * @param   boolean $check_for_key  Will check for key of provided values
     * --
     * @return  $this
     */
    public function is_exactly($allowed, $check_for_key=true)
    {
        if ($check_for_key) {
            if (!isset($allowed[$this->value])) {
                if ($this->add_message) {
                    Message::war(
                        l(
                            'VAL_FIELD_MUST_BE_VALUES',
                            array($this->name, implode(',', $allowed))),
                        __CLASS__);
                }
                $this->is_valid = false;
            }
        }
        else {
            if (!in_array($this->value, $allowed)) {
                if ($this->add_message) {
                    Message::war(
                        l(
                            'VAL_FIELD_MUST_BE_VALUES',
                            array($this->name, implode(',', $allowed))),
                        __CLASS__);
                }
                $this->is_valid = false;
            }
        }

        return $this;
    }

    /**
     * Check if field is the same as another filed
     * --
     * @param   string  $field  Another field's value
     * @param   string  $name   Another field's name
     * --
     * @return  $this
     */
    public function is_same_as($field, $name)
    {

        if ($this->value != $field) {
            if ($this->add_message) {
                Message::war(
                    l(
                        'VAL_FIELD_MUST_BE_THE_SAME_AS',
                        array($this->name, $name)),
                    __CLASS__);
            }
            $this->is_valid = false;
        }

        return $this;
    }
}
