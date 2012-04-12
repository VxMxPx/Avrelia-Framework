<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Validate Assign
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-03-27
 */
class cValidateVariable
{
	private $isValid    = true;		# boolean
	private $addMessage = true;		# boolean	If true, uMessage will be added
	private $value      = '';		# string	Actual field's value
	private $name       = false;	# string	The name of field
	private $needValue  = false;	# boolean	To be valid, does this field need to have value?

	/**
	 * New Validation Assigment!
	 * --
	 * @param	string	$value
	 * @param	string	$name
	 * --
	 * @return	void
	 */
	public function __construct($value, $name=false)
	{
		$this->value = $value;
		$this->name  = $name;

		if ($name == false) {
			$this->addMessage = false;
		}
	}
	//-

	/**
	 * Is valid?
	 * --
	 * @return	boolean
	 */
	public function isValid()
	{
		if (!$this->needValue && empty($this->value)) {
			return true;
		}
		else {
			return $this->isValid;
		}
	}
	//-

	/*  ****************************************************** *
	 *          Filters
	 *  **************************************  */

	/**
	 * Check if has value (not empty)
	 * --
	 * @return	$this
	 */
	public function hasValue()
	{
		$return = true;

		$this->needValue = true;

		if (is_string($this->value)) {
			if (strlen($this->value) === 0) {
				$return = false;
			}
		}
		elseif (empty($this->value)) {
			$return = false;
		}

		if ($return == false) {
			if ($this->addMessage) {
				uMessage::Add('WAR', l('VAL_FIELD_CANT_BE_EMPTY', $this->name), __FILE__);
			}
			$this->isValid = false;
		}

		// Always return self
		return $this;
	}
	//-

	/**
	 * Check if variable contains valid e-mail
	 * --
	 * @param	string	$domain	Check if is on particular domain (example: @gmail.com)
	 * --
	 * @return	$this
	 */
	public function isEmail($domain=null)
	{
		# Preform test
		$return = filter_var($this->value, FILTER_VALIDATE_EMAIL);

		# Add any message?
		if (!$return) {
			if ($this->addMessage) {
				uMessage::Add('WAR', l('VAL_FIELD_DOESNT_VALID_EMAIL', $this->name), __FILE__);
			}
			$this->isValid = false;
			return $this;
		}

		# Valid domain?
		if (!is_null($domain)) {
			$lenFront = strlen($domain);
			$lenBack  = $lenFront * -1;
			$return = (substr($this->value, $lenBack, $lenFront) == $domain) ? true : false;

			if (!$return) {
				if ($this->addMessage) {
					uMessage::Add('WAR', l('VAL_FIELD_MUST_EMAIL_ON_DOMAIN', array($this->name, $domain)), __FILE__);
				}
				$this->isValid = false;
			}
		}

		return $this;
	}
	//-

	/**
	 * Check if is valid IP address
	 * --
	 * @param	string	$mask
	 * --
	 * @return	$this
	 */
	public function isIP($mask=null)
	{
        $return = filter_var($this->value, FILTER_VALIDATE_IP);

		if (!$return) {
			if ($this->addMessage) {
				uMessage::Add('WAR', l('VAL_FIELD_MUST_VALID_IP_ADDRESS', $this->name), __FILE__);
			}
			$this->isValid = false;
			return $this;
		}

		# Check for mask...
		$maskReg = str_replace(array('.', '*'), array('\.', '.*'), $mask);
		if (!preg_match("/^{$maskReg}$/", $this->value)) {
			if ($this->addMessage) {
				uMessage::Add('WAR', l('VAL_FIELD_IP_MUST_EQ_MASK', array($this->name, $mask)), __FILE__);
			}
			$this->isValid = false;
		}

        return $this;
	}
	//-

	/**
	 * Test for particular Regex
	 * --
	 * @param	string	$mask
	 * --
	 * @return	$this
	 */
	public function isRegex($mask)
	{
		$cleaned = vString::RegExClean($this->value, $mask);

		if ($cleaned != $this->value) {
			if ($this->addMessage) {
				uMessage::Add('WAR', l('VAL_FIELD_MUST_MATCH_PATTERN', array($this->name, $mask)), __FILE__);
			}
			$this->isValid = false;
		}

		return $this;
	}
	//-

	/**
	 * Check if is URL
	 * --
	 * @return	$this
	 */
	public function isURL()
	{
		if (!filter_var($this->value, FILTER_VALIDATE_URL)) {
			if ($this->addMessage) {
				uMessage::Add('WAR', l('VAL_FIELD_MUST_VALID_WEB_ADDRESS', $this->name), __FILE__);
			}
			$this->isValid = false;
		}

		return $this;
	}
	//-

	/**
	 * Check if is numeric and is it in particular range
	 * --
	 * @param	integer	$min
	 * @param	integer	$max
	 * --
	 * @return	$this
	 */
	public function isNumeric($min=null, $max=null)
	{
		if (is_numeric($this->value))
		{
			$variable = (int) $this->value;

			if (!is_null($min)) {
				if ($variable < $min) {
					if ($this->addMessage) {
						uMessage::Add('WAR', l('VAL_FIELD_NUM_MIN_AT_LEAST', array($this->name, $min)), __FILE__);
					}
					$this->isValid = false;
				}
			}
			if (!is_null($max)) {
				if ($variable > $max) {
					if ($this->addMessage) {
						uMessage::Add('WAR', l('VAL_FIELD_NUM_MAX_CANT_MORE_THAN', array($this->name, $max)), __FILE__);
					}
					$this->isValid = false;
				}
			}

		}
		else {
			if ($this->addMessage) {
				uMessage::Add('WAR', l('VAL_FIELD_MUST_BE_NUMERIC', $this->name), __FILE__);
			}
			$this->isValid = false;
		}

		return $this;
	}
	//-

	/**
	 * Check if is numeric, - whole numbers, not float
	 * --
	 * @param	boolean	$onlyPositive	Must have only positive numbers
	 * --
	 * @return	$this
	 */
	public function isNumericWhole($onlyPositive=false)
	{
		# Is Whole?
		if ((int)$this->value != $this->value) {
			if ($this->addMessage) {
				uMessage::Add('WAR', l('VAL_FIELD_MUST_BE_WHOLE_NUMBER', $this->name), __FILE__);
			}
			$this->isValid = false;
			return $this;
		}

		# Is positive?
		if ($onlyPositive) {
			if ((int)$this->value < 0) {
				if ($this->addMessage) {
					uMessage::Add('WAR', l('VAL_FIELD_MUST_BE_POSITIVE_NUMER', $this->name), __FILE__);
				}
				$this->isValid = false;
			}
		}

		return $this;
	}
	//-

	/**
	 * Check if is boolean
	 * --
	 * @param	boolean $particular	Set to true / false: it will check for particular boolean value (either true or false)
	 * @param	boolean	$strict		If set to "false" we'll approve also: 1,0,"true","false","yes","no","on","off", "1", "0" (string values)
	 * --
	 * @return	$this
	 */
	public function isBoolean($particular=null, $strict=true)
	{
		# Internal value
		$value = $this->value;

		# Check for non-strict
		if (!$strict) {
			$trueValues  = array('1', 'true', 'yes', 'on',  1);
			$falseValues = array('0', 'false', 'no', 'off', 0);

			if (in_array(strtolower($value), $trueValues)) {
				$value = true;
			}
			elseif (in_array(strtolower($value), $falseValues)) {
				$value = false;
			}
		}

		# Check if is boolean
		if (!is_bool($value)) {
			if ($this->addMessage) {
				uMessage::Add('WAR', l('VAL_FIELD_MUST_CONTAIN_BOOLEAN', $this->name), __FILE__);
			}
			$this->isValid = false;
			return $this;
		}

		# Is particular?
		if (!is_null($particular)) {
			if ($value !== $particular) {
				if ($this->addMessage) {
					if ($particular === true) {
						uMessage::Add('WAR', l('VAL_FIELD_MUST_BE_SET_TO_TRUE', $this->name), __FILE__);
					}
					else {
						uMessage::Add('WAR', l('VAL_FIELD_MUST_BE_SET_TO_FALSE', $this->name), __FILE__);
					}
				}
				$this->isValid = false;
			}
		}

		return $this;
	}
	//-

	/**
	 * Check if string is particular length
	 * --
	 * @param	integer	$min
	 * @param	integer	$max
	 * --
	 * @return	$this
	 */
	public function isLength($min=null, $max=null)
	{
		if (!is_null($min)) {
			if (strlen($this->value) < $min) {
				if ($this->addMessage) {
					uMessage::Add('WAR', l('VAL_FIELD_MUST_CONTAIN_AT_LEAST', array($this->name, $min)));
				}
				$this->isValid = false;
				return $this;
			}
		}

		if (!is_null($max)) {
			if (strlen($this->value) > $max) {
				if ($this->addMessage) {
					uMessage::Add('WAR', l('VAL_FIELD_MUST_HAVE_NOT_MORE_THAN', array($this->name, $max)));
				}
				$this->isValid = false;
			}
		}

		return $this;
	}
	//-

	/**
	 * Check if field contain valid date
	 * --
	 * @param	string	$format	'd.m.Y'
	 * --
	 * @return	$this
	 */
	public function isDate($format)
	{
		$finalDate = strtotime($this->value);
		$finalDate = uTime::Format_d($format, date('Y-m-d H:i:s', $finalDate));

		if ($finalDate != $this->value) {
			if ($this->addMessage) {
				uMessage::Add('WAR', l('VAL_FIELD_MUST_HAVE_VALID_DATE', array($this->name, $format)), __FILE__);
			}
			$this->isValid = false;
		}
		return $this;
	}
	//-

	/**
	 * Check if field contain exact value
	 * --
	 * @param	array	$Allow			Array of allowed values
	 * @param	boolean	$checkForKey	Will check for key of provided values
	 * --
	 * @return	$this
	 */
	public function isExactly($Allow, $checkForKey=true)
	{
		if ($checkForKey) {
			if (!isset($Allow[$this->value])) {
				if ($this->addMessage) {
					uMessage::Add('WAR', l('VAL_FIELD_MUST_BE_VALUES', array($this->name, implode(',', $Allow))), __FILE__);
				}
				$this->isValid = false;
			}
		}
		else {
			if (!in_array($this->value, $Allow)) {
				if ($this->addMessage) {
					uMessage::Add('WAR', l('VAL_FIELD_MUST_BE_VALUES', array($this->name, implode(',', $Allow))), __FILE__);
				}
				$this->isValid = false;
			}
		}

		return $this;
	}
	//-

	/**
	 * Check if field is the same as another filed
	 * --
	 * @param	string	$field	Another field's value
	 * @param	string	$name	Another field's name
	 * --
	 * @return	$this
	 */
	public function isSameAs($field, $name)
	{

		if ($this->value != $field) {
			if ($this->addMessage) {
				uMessage::Add('WAR', l('VAL_FIELD_MUST_BE_THE_SAME_AS', array($this->name, $name)), __FILE__);
			}
			$this->isValid = false;
		}

		return $this;
	}
	//-

}
//--
