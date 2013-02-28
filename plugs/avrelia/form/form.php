<?php

namespace Plug\Avrelia;

use Avrelia\Core\Plug as Plug;
use Avrelia\Core\Str  as Str;

/**
 * Form Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Form
{
    # Specific settings for form
    private $form_data = array('wrap' => false);

    # Default values for form
    private $defaults = array();

    /**
     * Load Assign Class
     * --
     * @return  boolean
     */
    public static function _on_include_()
    {
        # Get language
        Plug::get_language(__FILE__);

        # Return true!
        return true;
    }

    /**
     * Form start
     * Will create <form> start tag
     * --
     * @param   string  $action     To which url to post, if not provided full url
     *                              with http start, it will be created automatically
     *                              using function url())
     * @param   boolean $recover    Should form's field will be automatically recovered?
     *                              Note: If you want to provide attribute "method",
     *                              use method attributes()
     * --
     * @return  string
     */
    public function start($action = null, $recover = true)
    {
        $this->form_data['recover'] = $recover;

        # Define action
        if (is_null($action)) {
            $action = url();
        }
        elseif (substr($action, 0, 4) !== 'http') {
            $action = url($action);
        }

        $this->form_data['attributes']['action'] = $action;
        $this->form_data['attributes']['method'] = isset($this->form_data['attributes']['method'])
                                                    ? $this->form_data['attributes']['method']
                                                    : 'post';

        return $this->returner("\n\n<form{attributes}>", 'form', $action);
    }

    /**
     * Form textbox
     * Will create <input type="text />
     * --
     * @param   string  $name
     * @param   string  $label
     * --
     * @return  string
     */
    public function textbox($name, $label = null)
    {
        $this->form_data['attributes']['name'] = $name;
        $this->form_data['attributes']['type'] = isset($this->form_data['attributes']['type'])
                                                    ? $this->form_data['attributes']['type']
                                                    : 'text';

        # Label?
        if (!is_null($label)) {
            if (isset($this->form_data['attributes']['id'])) {
                $id = $this->form_data['attributes']['id'];
            }
            else {
                # Generate ID!
                $id = "aff__{$name}";
                $this->form_data['attributes']['id'] = $id;
            }
            $label = "\n\t<label class=\"lf_textbox\" for=\"{$id}\">{$label}</label>";
        }

        # Check for recovery...
        if ($this->form_data['recover'] === true && isset($_POST[$name])) {
            $this->form_data['attributes']['value'] = $_POST[$name];
        }
        elseif (isset($this->defaults[$name])) {
            $this->form_data['attributes']['value'] = $this->defaults[$name];
        }

        # The "masked" field will proxy through this method
        $type = $this->form_data['attributes']['type'] == 'text' ? 'textbox' : 'masked';

        return $this->returner("{$label}\n\t<input{attributes} />", $type, $name);
    }

    /**
     * Form masked
     * Will create <input type="password" />
     * --
     * @param   string  $name
     * @param   string  $label
     * --
     * @return  string
     */
    public function masked($name, $label = null)
    {
        $this->form_data['attributes']['type'] = 'password';
        return $this->textbox($name, $label);
    }

    /**
     * Form upload
     * Will create <input type"file" />
     * --
     * @param   string  $name
     * @param   string  $label
     * --
     * @return  string
     */
    public function upload($name, $label = null)
    {
        $this->form_data['attributes']['type'] = 'file';
        return $this->textbox($name, $label);
    }

    /**
     * Form radio
     * Will create <input type="radio" />
     * --
     * @param   string  $name
     * @param   array   $options    In format array('value' => 'label')
     *                              OR array('value' => 'label="My label" id="myId" class="myClass"')
     *                              If you want label with link (not to be parser), put \ in front of it!
     * @param   string  $selected
     * --
     * @return  string
     */
    public function radio($name, array $options, $selected = null)
    {
        $fields             = '';
        $default_attributes = $this->form_data['attributes'];
        $default_attributes['name'] = $name;
        $default_attributes['type'] = 'radio';

        # Set defaults
        if (isset($this->defaults[$name])) {
            $selected = $this->defaults[$name];
        }

        foreach ($options as $val => $params) {
            if (strpos($params, '="') !== false && substr($params,0,1) != '\\') {
                # We have multiple params...
                # Process them...
                $this->attr($params);
                $field_attributes = $this->form_data['attributes'];
            }
            else {
                # Else we have only label
                $field_attributes['label'] = ltrim($params, '\\');
            }

            # Reset Label before we merge...
            $label = isset($field_attributes['label']) ? $field_attributes['label'] : null;
            unset($field_attributes['label']);

            # Merge field + Default Attributes
            $field_attributes = array_merge($default_attributes, $field_attributes);

            # Do we have label?
            if (!is_null($label)) {
                if (isset($field_attributes['id'])) {
                    $id = $field_attributes['id'];
                }
                else {
                    # Generate ID!
                    $id = "aff__{$name}_{$val}";
                    $field_attributes['id'] = $id;
                }
                $label = "\n\t<label class=\"lf_radio\" for=\"{$id}\">{$label}</label>";
            }

            # Check for recovery...
            if ($this->form_data['recover'] === true && !empty($_POST)) {
                if (isset($_POST[$name])) {
                    $selected_s = $_POST[$name];
                    if ($selected_s == $val) {
                        $field_attributes['checked'] = 'checked';
                    }
                }
            }
            else {
                # Check for default...
                if ($selected == $val) {
                    $field_attributes['checked'] = 'checked';
                }
            }

            # Set Value
            $field_attributes['value'] = $val;

            # Process attributes
            $attributes = $this->process_attributes($field_attributes);

            # Reset...
            $this->form_data['attributes'] = null;
            $field_attributes              = null;

            $fields .= "\n\t<input{$attributes} />{$label}";
        }

        # We'll use returned only to put template (if any) arround all radio fields...
        return $this->returner($fields, 'radio', $name);
    }

    /**
     * Form checkbox
     * Will create <input type="checkbox" />
     * --
     * @param   string  $name
     * @param   array   $options    In format array('value' => 'label')
     *                              OR array('value' => 'label="My label" id="myId" class="myClass"')
     *                              If you want label with link (not to be parser), put \ in front of it!
     * @param   string  $selected   Use comma to list more elements, eg: dogs,cats
     * --
     * @return  string
     */
    public function checkbox($name, array $options, $selected = null)
    {
        $fields             = '';
        $selected_items     = Str::explode_trim(',', $selected);
        $default_attributes = $this->form_data['attributes'];
        $default_attributes['name'] = $name.'[]';
        $default_attributes['type'] = 'checkbox';

        # Set defaults
        if (isset($this->defaults[$name])) {
            $selected = $this->defaults[$name];
        }

        foreach ($options as $val => $params) {
            if (strpos($params, '="') !== false && substr($params,0,1) != '\\') {
                # We have multiple params...
                # Process them...
                $this->attr($params);
                $field_attributes = $this->form_data['attributes'];
            }
            else {
                # Else we have only label
                $field_attributes['label'] = ltrim($params, '\\');
            }

            # Reset Label before we merge...
            $label = isset($field_attributes['label']) ? $field_attributes['label'] : null;
            unset($field_attributes['label']);

            # Merge field + Default Attributes
            $field_attributes = array_merge($default_attributes, $field_attributes);

            # Do we have label?
            if (!is_null($label)) {
                if (isset($field_attributes['id'])) {
                    $id = $field_attributes['id'];
                }
                else {
                    # Generate ID!
                    $id = "aff__{$name}_{$val}";
                    $field_attributes['id'] = $id;
                }
                $label = "\n\t<label class=\"lf_checkbox\" for=\"{$id}\">{$label}</label>";
            }

            # Check for recovery...
            if ($this->form_data['recover'] === true && !empty($_POST)) {
                if (isset($_POST[$name])) {
                    $selected_s = $_POST[$name];
                    if (in_array($val, $selected_s)) {
                        $field_attributes['checked'] = 'checked';
                    }
                }
            }
            else {
                # Check for default...
                if (in_array($val, $selected_items)) {
                    $field_attributes['checked'] = 'checked';
                }
            }

            # Set Value
            $field_attributes['value'] = $val;

            # Process attributes
            $attributes = $this->process_attributes($field_attributes);

            # Reset...
            $this->form_data['attributes'] = null;
            $field_attributes              = null;

            $fields .= "\n\t<input{$attributes} />{$label}";
        }

        # We'll use returned only to put template (if any) arround all radio fields...
        return $this->returner($fields, 'checkbox', $name);
    }

    /**
     * Form select
     * Will create <select><option>...
     * --
     * @param   string  $name
     * @param   array   $options    array('val' => 'label')
     * @param   string  $selected   In case of multi select use comma
     *                              to list more elements, eg: dogs,cats
     * @param   boolean $multi      Multi or single select?
     * --
     * @return  string
     */
    public function select(
        $name,
        array $options,
        $label = null,
        $selected = null,
        $multi = false)
    {
        $fields            = '';
        $selected_items    = Str::explode_trim(',', $selected);
        $this->form_data['attributes']['name'] = $name . ($multi ? '[]' : '');
        $this->form_data['attributes']['type'] = 'select';
        if ($multi) {
            $this->form_data['attributes']['multiple'] = 'multiple';
        }

        # Set defaults
        if (isset($this->defaults[$name])) {
            $selected = $this->defaults[$name];
            if ($multi && strpos($selected,',')!==false) {
                $selected = Str::explode_trim(',',$selected);
            }
        }

        foreach ($options as $val => $lbl)
        {
            # Is selected? )
            $sel_opt = null;

            # Check for recovery...
            if ($this->form_data['recover'] === true && !empty($_POST)) {
                if (isset($_POST[$name])) {
                    $selected_s = $_POST[$name];
                    $selected_s = $multi ? $selected_s : array($selected_s);
                    if (in_array($val, $selected_s)) {
                        $sel_opt = ' selected="selected"';
                    }
                }
            }
            else {
                # Check for default...
                if (in_array($val, $selected_items)) {
                    $sel_opt = ' selected="selected"';
                }
            }

            $fields .= "\n\t\t<option value=\"{$val}\"{$sel_opt}>{$lbl}</option>";
        }

        # Label?
        if (!is_null($label)) {
            if (isset($this->form_data['attributes']['id'])) {
                $id = $this->form_data['attributes']['id'];
            }
            else {
                # Generate ID!
                $id = "aff__{$name}";
                $this->form_data['attributes']['id'] = $id;
            }
            $label = "\n\t<label class=\"lf_select\" for=\"{$id}\">{$label}</label>";
        }

        # We'll use returned only to put template (if any) arround all radio fields...
        return $this->returner("{$label}\n\t<select{attributes}>".$fields."\n\t</select>", 'select', $name);
    }

    /**
     * Form date (old)
     * Will create <select name="{$name}_day">...<select name="{$name}_month">...
     * --
     * @param   string  $name
     * @param   string  $label
     * @param   string  $selected   Date in format: d.m.Y (16.04.1984)
     * --
     * @return  string
     */
    public function date($name, $label = null, $selected = null)
    {
        $days = array();
        for ($i=0; $i<32; $i++) { $days[$i] = ($i==0) ? l('DAY') : $i; }

        # Set defaults
        if (isset($this->defaults[$name]['day'])) {
            $selected = $this->defaults[$name]['day'] . '.' .
                        $this->defaults[$name]['month'] . '.' .
                        $this->defaults[$name]['year'];
        }

        $months = array(
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

        $years = array();
        for ($i=1900; $i<date('Y'); $i++) { $years[$i] = $i; }

        # Label?
        if (!is_null($label)) {
            $label = "\n\t<label class=\"lf_date\">{$label}</label>";
        }

        $sel_day   = null;
        $sel_month = null;
        $sel_year  = null;

        if ($this->form_data['recover'] === true && !empty($_POST)) {
            $sel_day   = isset($_POST[$name]['day'])   ? $_POST[$name]['day']   : null;
            $sel_month = isset($_POST[$name]['month']) ? $_POST[$name]['month'] : null;
            $sel_year  = isset($_POST[$name]['year'])  ? $_POST[$name]['year']  : null;
        }
        else {
            if (!is_null($selected)) {
                $selected_items = explode('.', $selected, 3);
                $sel_day   = isset($selected_items[0]) ? $selected_items[0] : null;
                $sel_month = isset($selected_items[1]) ? $selected_items[1] : null;
                $sel_year  = isset($selected_items[2]) ? $selected_items[2] : null;
            }
        }

        $fields  = '';
        $fields .= $label;
        $fields .= $this->recover(false)->attr($this->form_data['attributes_raw'])->wrap(false)->select($name.'[day]',   $days,   null, $sel_day);
        $fields .= $this->recover(false)->attr($this->form_data['attributes_raw'])->wrap(false)->select($name.'[month]', $months, null, $sel_month);
        $fields .= $this->recover(false)->attr($this->form_data['attributes_raw'])->wrap(false)->select($name.'[year]',  $years,  null, $sel_year);

        return $this->returner($fields, 'date', $name);
    }

    /**
     * Form hidden
     * Will create <input type="hidden"...
     * --
     * @param   string  $name
     * @param   string  $value
     * --
     * @return  string
     */
    public function hidden($name, $value)
    {
        return "\n\t<input type=\"hidden\" name=\"{$name}\" value=\"{$value}\" />";
    }

    /**
     * Form textarea
     * Will create <textarea>
     * --
     * @param   string  $name
     * @param   string  $content
     * @param   string  $label
     * --
     * @return  string
     */
    public function textarea($name, $label = null, $content = null)
    {
        $this->form_data['attributes']['name'] = $name;
        $this->form_data['attributes']['rows'] = isset($this->form_data['attributes']['rows']) ? $this->form_data['attributes']['rows'] : 10;
        $this->form_data['attributes']['cols'] = isset($this->form_data['attributes']['cols']) ? $this->form_data['attributes']['cols'] : 40;

        # Set defaults
        if (isset($this->defaults[$name])) {
            $content = $this->defaults[$name];
        }

        # Label?
        if (!is_null($label)) {
            if (isset($this->form_data['attributes']['id'])) {
                $id = $this->form_data['attributes']['id'];
            }
            else {
                # Generate ID!
                $id = "aff__{$name}";
                $this->form_data['attributes']['id'] = $id;
            }
            $label = "\n\t<label class=\"lf_textarea\" for=\"{$id}\">{$label}</label>";
        }

        # Check for recovery...
        if ($this->form_data['recover'] === true) {
            if (isset($_POST[$name])) {
                $content = $_POST[$name];
            }
        }

        return $this->returner("{$label}\n\t<textarea{attributes}>{$content}</textarea>", 'textarea', $name);
    }

    /**
     * Will add button
     * --
     * @param   string  $label
     * @param   string  $name
     * @param   string  $type   submit | button | reset
     * --
     * @return  string
     */
    public function button($label = null, $name = 'submit', $type = 'submit')
    {
        $this->form_data['attributes']['name'] = $name;
        $this->form_data['attributes']['type'] = $type;

        return $this->returner("\n\t<button{attributes}>{$label}</button>", 'button', $name);
    }

    /**
     * Will create empty wrapper field
     * --
     * @return  string
     */
    public function spacer()
    {
        return $this->returner('<div class="fieldSpacer">&nbsp;</div>', 'spacer', false);
    }

    /**
     * Form end
     * Will create </form> end tag
     * --
     * @return  string
     */
    public function end()
    {
        //$this->form_data = array('wrap' => false);
        return "\n</form>";
    }

    /**
     * Will set wrapper for all fields
     * --
     * @param   string  $mask   options:
     *      {field}     -- the field itself
     *      {id}        -- field's ID -- note, if not ID was set, the aff_name will be used
     *      {name}      -- field's name
     *      {type}      -- field's type
     *      {odd_even}  -- will return odd or even
     *      {has_error} -- if validation enabled has error (return has_error)
     * --
     * @return  void
     */
    public function wrap_fields($mask)
    {
        $this->form_data['template'] = $mask;
        $this->form_data['wrap']     = true;
    }

    /**
     * For current field set wrapper on / off
     * --
     * @param   boolean $do
     * --
     * @return  $this
     */
    public function wrap($do)
    {
        $this->form_data['wrap_default'] = $this->form_data['wrap'];
        $this->form_data['wrap'] = $do;

        return $this;
    }

    /**
     * Will start wrapper, to wrap multiple fields...
     * --
     * @param   string  $name
     * @param   string  $type
     * @param   string  $id
     * @param   mixed   $process_odd_even  Should we automatically process odd_even
     * --
     * @return  string
     */
    public function wrap_start($name, $type = 'manual', $id = null, $process_odd_even = true)
    {
        # Process template...
        if (isset($this->form_data['template']))
        {
            $this->form_data['wrap']    = false;
            if ($process_odd_even === true) {
                $odd_even                    = isset($this->form_data['odd_even']) ? $this->form_data['odd_even'] : false;
                $odd_even                    = $odd_even != 'even' ? 'even' : 'odd';
                $this->form_data['odd_even'] = $odd_even;
            }
            elseif ($process_odd_even !== false) {
                $odd_even = $process_odd_even;
                $this->form_data['odd_even'] = $odd_even;
            }
            else {
                $odd_even = '';
            }

            $template = $this->form_data['template'];

            $template = str_replace('{id}',      $id,      $template);
            $template = str_replace('{name}',    $name,    $template);
            $template = str_replace('{type}',    $type,    $template);
            $template = str_replace('{odd_even}', $odd_even, $template);
            # {has_error} -- if validation enabled has error (return has_error)
            $template_array = explode('{field}', $template, 2);
            $this->form_data['wrapper_template_processed'] = $template_array;
            return $template_array[0];
        }
    }

    /**
     * Will end wrapper, (wrap multiple fields...)
     * --
     * @return  string
     */
    public function wrap_end()
    {
        if (isset($this->form_data['wrapper_template_processed']))
        {
            $this->form_data['wrap'] = true;
            $template = $this->form_data['wrapper_template_processed'][1];
            unset($this->form_data['wrapper_template_processed']);
            return $template;
        }
    }

    /**
     * Set defaults in form
     * --
     * @param   array   $defaults
     * --
     * @return  $this
     */
    public function defaults($defaults)
    {
        $this->defaults = array_merge($defaults);
        return $this;
    }

    /**
     * Will set attributes for current field
     * --
     * @param   string  $att  In format: 'class="myClass" id="myID" method="post"'
     * --
     * @return  $this
     */
    public function attr($att)
    {
        if (empty($att)) { return $this; }

        $this->form_data['attributes_raw'] = $att;
        $att_array = explode('" ', $att);
        $final     = array();

        foreach ($att_array as $attribute) {
            $attribute = explode('=', $attribute, 2);
            $final[trim($attribute[0])] = trim($attribute[1], '"');
        }

        $this->form_data['attributes'] = $final;

        return $this;
    }

    /**
     * For current field set recovery option
     * --
     * @param   boolean $do
     * --
     * @return  $this
     */
    public function recover($do)
    {
        $this->form_data['default_recover'] = $this->form_data['recover'];
        $this->form_data['recover'] = $do;

        return $this;
    }

    /**
     * Insert element in front of field or behind it...
     * For example, if we say prefix for textbox is Enter your name:,
     * and set in_front to true we'll get following result:
     * Enter your name: <input ...
     * --
     * @param   string  $content
     * @param   boolean $in_front  Insert content in front of field or after field
     * --
     * @return  $this
     */
    public function ins($content, $in_front = true)
    {
        $pos = $in_front ? 'front' : 'back';
        $this->form_data['insertion'][$pos] = $content;

        return $this;
    }

    /**
     * Return correctly formated field
     * --
     * @param   string  $field
     * @param   string  $type
     * @param   string  $name
     * --
     * @return  string
     */
    private function returner($field, $type, $name)
    {
        $attributes = $this->process_attributes();

        # Add attributes to field
        $field = str_replace('{attributes}', $attributes, $field);

        # Insertion
        if (isset($this->form_data['insertion']) && !empty($this->form_data['insertion'])) {
            foreach ($this->form_data['insertion'] as $pos => $ins_cnt) {
                if ($pos == 'front') {
                    $field = $ins_cnt . "\n" . $field;
                }
                else {
                    $field = $field . "\n" . $ins_cnt;
                }
            }
        }

        # Process template...
        if (isset($this->form_data['template']) && $type != 'form' && $this->form_data['wrap']) {
            $odd_even = isset($this->form_data['odd_even']) ? $this->form_data['odd_even'] : false;
            $odd_even = $odd_even != 'even' ? 'even' : 'odd';
            $this->form_data['odd_even'] = $odd_even;
            $template = $this->form_data['template'];

            if (isset($this->form_data['attributes']['id'])) {
                $id = $this->form_data['attributes']['id'];
            }
            else {
                # Generate ID!
                $id = null;
            }
            $template = str_replace('{id}',       $id,       $template);
            $template = str_replace('{name}',     $name,     $template);
            $template = str_replace('{type}',     $type,     $template);
            $template = str_replace('{odd_even}', $odd_even, $template);
            # {has_error} -- if validation enabled has error (return has_error)
            $template = str_replace('{field}',    $field,    $template);
        }
        else {
            $template = $field;
        }

        # Reset settings....
        if (isset($this->form_data['default_recover'])) {
            $this->form_data['recover'] = $this->form_data['default_recover'];
            unset($this->form_data['default_recover']);
        }
        if (isset($this->form_data['wrap_default'])) {
            $this->form_data['wrap'] = $this->form_data['wrap_default'];
            unset($this->form_data['wrap_default']);
        }
        $this->form_data['attributes'] = '';
        $this->form_data['insertion']  = array();

        # Finally return actual field
        return $template;
    }

    /**
     * Will process attributes
     * --
     * @param   array   $attributes
     * --
     * @return  string
     */
    private function process_attributes(array $attributes = array())
    {
        if (empty($attributes)) {
            $attributes = $this->form_data['attributes'];
        }

        $result = '';

        if (is_array($attributes) && !empty($attributes)) {
            foreach ($attributes as $key => $att) {
                $result .= ' ' . $key . '="' . $att . '"';
            }
        }

        return $result;
    }

}