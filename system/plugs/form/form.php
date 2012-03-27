<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Form Model ---
 * Usage example: $Form->attributes(array('class' => 'styled', 'method' => 'get'))->form('register');
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2011-07-21
 * --
 * @property	array	$FormData	Specific settings for form
 * @property	array	$Defaults	Default values for form
 */
class cForm
{
	private $FormData = array('wrap' => false);
	private $Defaults = array();

	/**
	 * Load Assign Class
	 * --
	 * @return	boolean
	 */
	public static function _DoInit()
	{
		# Get language
		Plug::GetLanguage(__FILE__);

		# Return true!
		return true;
	}
	//-

	/*  ****************************************************** *
	 *          Elements
	 *  **************************************  */

	/**
	 * Form start
	 * Will create <form> start tag
	 * --
	 * @param	string	$action		To which url to post, if not provided full url
	 * 								with http start, it will be created automatically
	 * 								using function url())
	 * @param	boolean	$recover	Should form's field will be automatically recovered?
	 * 								Note: If you want to provide attribute "method",
	 * 								use method attributes()
	 * --
	 * @return	string
	 */
	public function start($action=null, $recover=true)
	{
		$this->FormData['recover'] = $recover;

		# Define action
		if (is_null($action)) {
			$action = url();
		}
		elseif (substr($action, 0, 4) !== 'http') {
			$action = url($action);
		}

		$this->FormData['attributes']['action'] = $action;
		$this->FormData['attributes']['method'] = isset($this->FormData['attributes']['method']) ? $this->FormData['attributes']['method'] : 'post';

		return $this->returner("\n\n<form{attributes}>", 'form', $action);
	}
	//-

	/**
	 * Form textbox
	 * Will create <input type="text />
	 * --
	 * @param	string	$name
	 * @param	string	$label
	 * --
	 * @return	string
	 */
	public function textbox($name, $label=null)
	{
		$this->FormData['attributes']['name'] = $name;
		$this->FormData['attributes']['type'] = isset($this->FormData['attributes']['type']) ? $this->FormData['attributes']['type'] : 'text';

		# Label?
		if (!is_null($label)) {
			if (isset($this->FormData['attributes']['id'])) {
				$id = $this->FormData['attributes']['id'];
			}
			else {
				# Generate ID!
				$id = "aff__{$name}";
				$this->FormData['attributes']['id'] = $id;
			}
			$label = "\n\t<label class=\"lf_textbox\" for=\"{$id}\">{$label}</label>";
		}

		# Check for recovery...
		if ($this->FormData['recover'] === true && isset($_POST[$name])) {
			$this->FormData['attributes']['value'] = $_POST[$name];
		}
		elseif (isset($this->Defaults[$name])) {
			$this->FormData['attributes']['value'] = $this->Defaults[$name];
		}

		# The "masked" field will proxy through this method
		$type = $this->FormData['attributes']['type'] == 'text' ? 'textbox' : 'masked';

		return $this->returner("{$label}\n\t<input{attributes} />", $type, $name);
	}
	//-

	/**
	 * Form masked
	 * Will create <input type="password" />
	 * --
	 * @param	string	$name
	 * @param	string	$label
	 * --
	 * @return	string
	 */
	public function masked($name, $label=null)
	{
		$this->FormData['attributes']['type'] = 'password';
		return $this->textbox($name, $label);
	}
	//-

	/**
	 * Form upload
	 * Will create <input type"file" />
	 * --
	 * @param	string	$name
	 * @param	string	$label
	 * --
	 * @return	string
	 */
	public function upload($name, $label=null)
	{
		$this->FormData['attributes']['type'] = 'file';
		return $this->textbox($name, $label);
	}
	//-

	/**
	 * Form radio
	 * Will create <input type="radio" />
	 * --
	 * @param	string	$name
	 * @param	array 	$Options	In format array('value' => 'label')
	 * 								OR array('value' => 'label="My label" id="myId" class="myClass"')
	 * 								If you want label with link (not to be parser), put \ in front of it!
	 * @param	string	$selected
	 * --
	 * @return	string
	 */
	public function radio($name, $Options, $selected=null)
	{
		$fields            = '';
		$DefaultAttributes = $this->FormData['attributes'];
		$DefaultAttributes['name'] = $name;
		$DefaultAttributes['type'] = 'radio';

		# Set defaults
		if (isset($this->Defaults[$name])) {
			$selected = $this->Defaults[$name];
		}

		foreach ($Options as $val => $params) {
			if (strpos($params, '="') !== false && substr($params,0,1) != '\\') {
				# We have multiple params...
				# Process them...
				$this->att($params);
				$FieldAttributes = $this->FormData['attributes'];
			}
			else {
				# Else we have only label
				$FieldAttributes['label'] = ltrim($params, '\\');
			}

			# Reset Label before we merge...
			$label = isset($FieldAttributes['label']) ? $FieldAttributes['label'] : null;
			unset($FieldAttributes['label']);

			# Merge field + Default Attributes
			$FieldAttributes = array_merge($DefaultAttributes, $FieldAttributes);

			# Do we have label?
			if (!is_null($label)) {
				if (isset($FieldAttributes['id'])) {
					$id = $FieldAttributes['id'];
				}
				else {
					# Generate ID!
					$id = "aff__{$name}_{$val}";
					$FieldAttributes['id'] = $id;
				}
				$label = "\n\t<label class=\"lf_radio\" for=\"{$id}\">{$label}</label>";
			}

			# Check for recovery...
			if ($this->FormData['recover'] === true && !empty($_POST)) {
				if (isset($_POST[$name])) {
					$selectedS = $_POST[$name];
					if ($selectedS == $val) {
						$FieldAttributes['checked'] = 'checked';
					}
				}
			}
			else {
				# Check for default...
				if ($selected == $val) {
					$FieldAttributes['checked'] = 'checked';
				}
			}

			# Set Value
			$FieldAttributes['value'] = $val;

			# Process attributes
			$attributes = $this->processAttributes($FieldAttributes);

			# Reset...
			$this->FormData['attributes'] = null;
			$FieldAttributes              = null;

			$fields .= "\n\t<input{$attributes} />{$label}";
		}

		# We'll use returned only to put template (if any) arround all radio fields...
		return $this->returner($fields, 'radio', $name);
	}
	//-

	/**
	 * Form checkbox
	 * Will create <input type="checkbox" />
	 * --
	 * @param	string	$name
	 * @param	array	$Options	In format array('value' => 'label')
	 * 								OR array('value' => 'label="My label" id="myId" class="myClass"')
	 * 								If you want label with link (not to be parser), put \ in front of it!
	 * @param	string	$selected	Use comma to list more elements, eg: dogs,cats
	 * --
	 * @return	string
	 */
	public function checkbox($name, $Options, $selected=null)
	{
		$fields            = '';
		$Selected          = vString::ExplodeTrim(',', $selected);
		$DefaultAttributes = $this->FormData['attributes'];
		$DefaultAttributes['name'] = $name.'[]';
		$DefaultAttributes['type'] = 'checkbox';

		# Set defaults
		if (isset($this->Defaults[$name])) {
			$selected = $this->Defaults[$name];
		}

		foreach ($Options as $val => $params) {
			if (strpos($params, '="') !== false && substr($params,0,1) != '\\') {
				# We have multiple params...
				# Process them...
				$this->att($params);
				$FieldAttributes = $this->FormData['attributes'];
			}
			else {
				# Else we have only label
				$FieldAttributes['label'] = ltrim($params, '\\');
			}

			# Reset Label before we merge...
			$label = isset($FieldAttributes['label']) ? $FieldAttributes['label'] : null;
			unset($FieldAttributes['label']);

			# Merge field + Default Attributes
			$FieldAttributes = array_merge($DefaultAttributes, $FieldAttributes);

			# Do we have label?
			if (!is_null($label)) {
				if (isset($FieldAttributes['id'])) {
					$id = $FieldAttributes['id'];
				}
				else {
					# Generate ID!
					$id = "aff__{$name}_{$val}";
					$FieldAttributes['id'] = $id;
				}
				$label = "\n\t<label class=\"lf_checkbox\" for=\"{$id}\">{$label}</label>";
			}

			# Check for recovery...
			if ($this->FormData['recover'] === true && !empty($_POST)) {
				if (isset($_POST[$name])) {
					$selectedS = $_POST[$name];
					if (in_array($val, $selectedS)) {
						$FieldAttributes['checked'] = 'checked';
					}
				}
			}
			else {
				# Check for default...
				if (in_array($val, $Selected)) {
					$FieldAttributes['checked'] = 'checked';
				}
			}

			# Set Value
			$FieldAttributes['value'] = $val;

			# Process attributes
			$attributes = $this->processAttributes($FieldAttributes);

			# Reset...
			$this->FormData['attributes'] = null;
			$FieldAttributes              = null;

			$fields .= "\n\t<input{$attributes} />{$label}";
		}

		# We'll use returned only to put template (if any) arround all radio fields...
		return $this->returner($fields, 'checkbox', $name);
	}
	//-

	/**
	 * Form select
	 * Will create <select><option>...
	 * --
	 * @param	string	$name
	 * @param	array	$Options	array('val' => 'label')
	 * @param	string	$selected	In case of multi select use comma
	 * 								to list more elements, eg: dogs,cats
	 * @param	boolean	$multi		Multi or single select?
	 * --
	 * @return	string
	 */
	public function select($name, $Options, $label=null, $selected=null, $multi=false)
	{
		$fields            = '';
		$Selected          = vString::ExplodeTrim(',', $selected);
		$this->FormData['attributes']['name'] = $name . ($multi ? '[]' : '');
		$this->FormData['attributes']['type'] = 'select';
		if ($multi) {
			$this->FormData['attributes']['multiple'] = 'multiple';
		}

		# Set defaults
		if (isset($this->Defaults[$name])) {
			$selected = $this->Defaults[$name];
			if ($multi && strpos($selected,',')!==false) {
				$selected = vString::ExplodeTrim(',',$selected);
			}
		}

		foreach ($Options as $val => $lbl)
		{
			# Is selected? )
			$selOpt = null;

			# Check for recovery...
			if ($this->FormData['recover'] === true && !empty($_POST)) {
				if (isset($_POST[$name])) {
					$selectedS = $_POST[$name];
					$selectedS = $multi ? $selectedS : array($selectedS);
					if (in_array($val, $selectedS)) {
						$selOpt = ' selected="selected"';
					}
				}
			}
			else {
				# Check for default...
				if (in_array($val, $Selected)) {
					$selOpt = ' selected="selected"';
				}
			}

			$fields .= "\n\t\t<option value=\"{$val}\"{$selOpt}>{$lbl}</option>";
		}

		# Label?
		if (!is_null($label)) {
			if (isset($this->FormData['attributes']['id'])) {
				$id = $this->FormData['attributes']['id'];
			}
			else {
				# Generate ID!
				$id = "aff__{$name}";
				$this->FormData['attributes']['id'] = $id;
			}
			$label = "\n\t<label class=\"lf_select\" for=\"{$id}\">{$label}</label>";
		}

		# We'll use returned only to put template (if any) arround all radio fields...
		return $this->returner("{$label}\n\t<select{attributes}>".$fields."\n\t</select>", 'select', $name);
	}
	//-

	/**
	 * Form date (old)
	 * Will create <select name="{$name}_day">...<select name="{$name}_month">...
	 * --
	 * @param	string	$name
	 * @param	string	$label
	 * @param	string	$selected	Date in format: d.m.Y (16.04.1984)
	 * --
	 * @return	string
	 */
	public function date($name, $label=null, $selected=null)
	{
		$Days   = array();
		for ($i=0; $i<32; $i++) { $Days[$i] = ($i==0) ? l('DAY') : $i; }

		# Set defaults
		if (isset($this->Defaults[$name]['day'])) {
			$selected = $this->Defaults[$name]['day'] . '.' .
						$this->Defaults[$name]['month'] . '.' .
						$this->Defaults[$name]['year'];
		}

		$Months = array(
			'00' => l('MONTH'),
			'01' => l('JANUARY'),
			'02' => l('FEBRUARY'),
			'03' => l('MARCH'),
			'04' => l('APRIL'),
			'05' => l('MAY'),
			'06' => l('JUNE'),
			'07' => l('JULY'),
			'08' => l('AUGUST'),
			'09' => l('SEPTEMBER'),
			'10' => l('OCTOBER'),
			'11' => l('NOVEMBER'),
			'12' => l('DECEMBER')
		);

		$Years = array();
		for ($i=1900; $i<date('Y'); $i++) { $Years[$i] = $i; }

		# Label?
		if (!is_null($label)) {
			$label = "\n\t<label class=\"lf_date\">{$label}</label>";
		}


		$selDay   = null;
		$selMonth = null;
		$selYear  = null;

		if ($this->FormData['recover'] === true && !empty($_POST)) {
			$selDay   = isset($_POST[$name]['day'])   ? $_POST[$name]['day']   : null;
			$selMonth = isset($_POST[$name]['month']) ? $_POST[$name]['month'] : null;
			$selYear  = isset($_POST[$name]['year'])  ? $_POST[$name]['year']  : null;
		}
		else {
			if (!is_null($selected)) {
				$Selected = explode('.', $selected, 3);
				$selDay   = isset($Selected[0]) ? $Selected[0] : null;
				$selMonth = isset($Selected[1]) ? $Selected[1] : null;
				$selYear  = isset($Selected[2]) ? $Selected[2] : null;
			}
		}

		$fields  = '';
		$fields .= $label;
		$fields .= $this->rec(false)->att($this->FormData['attributes_raw'])->wrap(false)->select($name.'[day]',   $Days,   null, $selDay);
		$fields .= $this->rec(false)->att($this->FormData['attributes_raw'])->wrap(false)->select($name.'[month]', $Months, null, $selMonth);
		$fields .= $this->rec(false)->att($this->FormData['attributes_raw'])->wrap(false)->select($name.'[year]',  $Years,  null, $selYear);

		return $this->returner($fields, 'date', $name);
	}
	//-

	/**
	 * Form hidden
	 * Will create <input type="hidden"...
	 * --
	 * @param	string	$name
	 * @param	string	$value
	 * --
	 * @return	string
	 */
	public function hidden($name, $value)
	{
		return "\n\t<input type=\"hidden\" name=\"{$name}\" value=\"{$value}\" />";
	}
	//-

	/**
	 * Form textarea
	 * Will create <textarea>
	 * --
	 * @param	string	$name
	 * @param	string	$content
	 * @param	string	$label
	 * --
	 * @return	string
	 */
	public function textarea($name, $label=null, $content=null)
	{
		$this->FormData['attributes']['name'] = $name;
		$this->FormData['attributes']['rows'] = isset($this->FormData['attributes']['rows']) ? $this->FormData['attributes']['rows'] : 10;
		$this->FormData['attributes']['cols'] = isset($this->FormData['attributes']['cols']) ? $this->FormData['attributes']['cols'] : 40;

		# Set defaults
		if (isset($this->Defaults[$name])) {
			$content = $this->Defaults[$name];
		}

		# Label?
		if (!is_null($label)) {
			if (isset($this->FormData['attributes']['id'])) {
				$id = $this->FormData['attributes']['id'];
			}
			else {
				# Generate ID!
				$id = "aff__{$name}";
				$this->FormData['attributes']['id'] = $id;
			}
			$label = "\n\t<label class=\"lf_textarea\" for=\"{$id}\">{$label}</label>";
		}

		# Check for recovery...
		if ($this->FormData['recover'] === true) {
			if (isset($_POST[$name])) {
				$content = $_POST[$name];
			}
		}

		return $this->returner("{$label}\n\t<textarea{attributes}>{$content}</textarea>", 'textarea', $name);
	}
	//-

	/**
	 * Will add button
	 * --
	 * @param	string	$label
	 * @param	string	$name
	 * @param	string	$type	submit | button | reset
	 * --
	 * @return	string
	 */
	public function button($label=null, $name='submit', $type='submit')
	{
		$this->FormData['attributes']['name'] = $name;
		$this->FormData['attributes']['type'] = $type;

		return $this->returner("\n\t<button{attributes}>{$label}</button>", 'button', $name);
	}
	//-

	/**
	 * Will create empty wrapper field
	 * --
	 * @return	string
	 */
	public function spacer()
	{
		return $this->returner('<div class="fieldSpacer">&nbsp;</div>', 'spacer', false);
	}
	//-

	/**
	 * Form end
	 * Will create </form> end tag
	 * --
	 * @return	string
	 */
	public function end()
	{
		$this->FormData = array('wrap' => false);
		return "\n</form>";
	}
	//-

	/*  ****************************************************** *
	 *          Private / Helpers
	 *  **************************************  */

	/**
	 * Will set wrapper for all fields
	 * --
	 * @param	string	$mask	Options:
	 * 		{field}    -- the field itself
	 * 		{id}       -- field's ID -- note, if not ID was set, the aff_name will be used
	 * 		{name}     -- field's name
	 * 		{type}     -- field's type
	 * 		{oddEven}  -- will return odd or even
	 * 		{hasError} -- if validation enabled has error (return hasError)
	 * --
	 * @return	void
	 */
	public function wrapFields($mask)
	{
		$this->FormData['template'] = $mask;
		$this->FormData['wrap']     = true;
	}
	//-

	/**
	 * For current field set wrapper on / off
	 * --
	 * @param	boolean	$do
	 * --
	 * @return	$this
	 */
	public function wrap($do)
	{
		$this->FormData['wrap_default'] = $this->FormData['wrap'];
		$this->FormData['wrap'] = $do;

		return $this;
	}
	//-

	/**
	 * Will start wrapper, to wrap multiple fields...
	 * --
	 * @param	string	$name
	 * @param	string	$type
	 * @param	string	$id
	 * @param	mixed	$processOddEven	Should we automatically process oddEven
	 * --
	 * @return	string
	 */
	public function wrapStart($name, $type='manual', $id=null, $processOddEven=true)
	{
		# Process template...
		if (isset($this->FormData['template']))
		{
			$this->FormData['wrap']    = false;
			if ($processOddEven === true) {
				$oddEven                   = isset($this->FormData['oddEven']) ? $this->FormData['oddEven'] : false;
				$oddEven                   = $oddEven != 'even' ? 'even' : 'odd';
				$this->FormData['oddEven'] = $oddEven;
			}
			elseif ($processOddEven !== false) {
				$oddEven = $processOddEven;
				$this->FormData['oddEven'] = $oddEven;
			}
			else {
				$oddEven = '';
			}

			$template = $this->FormData['template'];

			$template = str_replace('{id}',      $id,      $template);
			$template = str_replace('{name}',    $name,    $template);
			$template = str_replace('{type}',    $type,    $template);
			$template = str_replace('{oddEven}', $oddEven, $template);
			# {hasError} -- if validation enabled has error (return hasError)
			$Template = explode('{field}', $template, 2);
			$this->FormData['wrapperTemplateProcessed'] = $Template;
			return $Template[0];
		}
	}
	//-

	/**
	 * Will end wrapper, (wrap multiple fields...)
	 * --
	 * @return	string
	 */
	public function wrapEnd()
	{
		if (isset($this->FormData['wrapperTemplateProcessed']))
		{
			$this->FormData['wrap'] = true;
			$template = $this->FormData['wrapperTemplateProcessed'][1];
			unset($this->FormData['wrapperTemplateProcessed']);
			return $template;
		}
	}
	//-

	/**
	 * Set defaults in form
	 * --
	 * @param	array	$Defaults
	 * --
	 * @return	$this
	 */
	public function defaults($Defaults)
	{
		$this->Defaults = array_merge($Defaults);
		return $this;
	}
	//-

	/**
	 * Will set attributes for current field
	 * --
	 * @param	string	$att	In format: 'class="myClass" id="myID" method="post"'
	 * --
	 * @return	$this
	 */
	public function att($att)
	{
		if (empty($att)) { return $this; }

		$this->FormData['attributes_raw'] = $att;
		$Att   = explode('" ', $att);
		$Final = array();

		foreach ($Att as $attribute) {
			$Attribute = explode('=', $attribute, 2);
			$Final[trim($Attribute[0])] = trim($Attribute[1], '"');
		}

		$this->FormData['attributes'] = $Final;

		return $this;
	}
	//-

	/**
	 * For current field set recovery option
	 * --
	 * @param	boolean	$do
	 * --
	 * @return	$this
	 */
	public function rec($do)
	{
		$this->FormData['default_recover'] = $this->FormData['recover'];
		$this->FormData['recover'] = $do;

		return $this;
	}
	//-

	/**
	 * Insert element in front of field or behind it...
	 * For example, if we say prefix for textbox is Enter your name:,
	 * and set inFront to true we'll get following result:
	 * Enter your name: <input ...
	 * --
	 * @param	string	$content
	 * @param	boolean	$inFront	Insert content in front of field or after field
	 * --
	 * @return	$this
	 */
	public function ins($content, $inFront=true)
	{
		$pos = $inFront ? 'front' : 'back';
		$this->FormData['insertion'][$pos] = $content;

		return $this;
	}
	//-

	/**
	 * Return correctly formated field
	 * --
	 * @param	string	$field
	 * @param	string	$type
	 * @param	string	$name
	 * --
	 * @return	string
	 */
	private function returner($field, $type, $name)
	{
		$attributes = $this->processAttributes();

		# Add attributes to field
		$field = str_replace('{attributes}', $attributes, $field);

		# Insertion
		if (isset($this->FormData['insertion']) && !empty($this->FormData['insertion'])) {
			foreach ($this->FormData['insertion'] as $pos => $insCnt) {
				if ($pos == 'front') {
					$field = $insCnt . "\n" . $field;
				}
				else {
					$field = $field . "\n" . $insCnt;
				}
			}
		}

		# Process template...
		if (isset($this->FormData['template']) && $type != 'form' && $this->FormData['wrap']) {
			$oddEven                   = isset($this->FormData['oddEven']) ? $this->FormData['oddEven'] : false;
			$oddEven                   = $oddEven != 'even' ? 'even' : 'odd';
			$this->FormData['oddEven'] = $oddEven;
			$template = $this->FormData['template'];

			if (isset($this->FormData['attributes']['id'])) {
				$id = $this->FormData['attributes']['id'];
			}
			else {
				# Generate ID!
				$id = null;
			}
			$template = str_replace('{id}',      $id,      $template);
			$template = str_replace('{name}',    $name,    $template);
			$template = str_replace('{type}',    $type,    $template);
			$template = str_replace('{oddEven}', $oddEven, $template);
			# {hasError} -- if validation enabled has error (return hasError)
			$template = str_replace('{field}',   $field,   $template);
		}
		else {
			$template = $field;
		}

		# Reset settings....
		if (isset($this->FormData['default_recover'])) {
			$this->FormData['recover'] = $this->FormData['default_recover'];
			unset($this->FormData['default_recover']);
		}
		if (isset($this->FormData['wrap_default'])) {
			$this->FormData['wrap'] = $this->FormData['wrap_default'];
			unset($this->FormData['wrap_default']);
		}
		$this->FormData['attributes'] = '';
		$this->FormData['insertion']  = array();

		# Finally return actual field
		return $template;
	}
	//-

	/**
	 * Will process attributes
	 * --
	 * @param	array	$Attributes
	 * --
	 * @return	string
	 */
	private function processAttributes($Attributes=null)
	{
		if (is_null($Attributes)) {
			$Attributes = $this->FormData['attributes'];
		}

		$result = '';

		if (is_array($Attributes) && !empty($Attributes)) {
			foreach ($Attributes as $key => $att) {
				$result .= ' ' . $key . '="' . $att . '"';
			}
		}

		return $result;
	}
	//-
}
//--
