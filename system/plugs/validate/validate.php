<?php namespace Avrelia\Plug; if (!defined('AVRELIA')) die('Access is denied!');

use Avrelia\Core\Plug as Plug;

/**
 * Validate Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Validate
{
    /**
     * @var array  List of fields to be validated
     */
    private static $validations_list = array();
    
    /**
     * Get files, ...
     * --
     * @return  boolean
     */
    public static function _on_include_()
    {
        # Get validate asssign
        include ds(dirname(__FILE__) . '/validate_variable.php');

        # Get language
        Plug::get_language(__FILE__);

        return true;
    }

    /**
     * Will add new filed to validate it.
     * --
     * @param   mixed   $value
     * @param   string  $name   If there's no name, no message will be set!
     * --
     * @return  ValidateVariable
     */
    public static function add($value, $name=false)
    {
        $validator = new ValidateVariable($value, $name);
        self::$validations_list[] = $validator;
        return $validator;
    }

    /**
     * Check if every field is valid...
     * --
     * @return  boolean
     */
    public static function is_valid()
    {
        if (is_array(self::$validations_list) && (!empty(self::$validations_list))) {
            foreach (self::$validations_list as $obj) {
                if ($obj->is_valid() === false) {
                    return false;
                }
            }
        }
        else {
            Log::war("You run validation with empty list!?");
            return true;
        }

        return true;
    }

    /**
     * Will add new simple filed to validate it.
     * --
     * @param   mixed   $value
     * --
     * @return  ValidateVariable
     */
    private static function check($value)
    {
        return new ValidateVariable($value, false);
    }

    /**
     * Check if value is valid e-mail address
     * --
     * @param   string  $value
     * @param   string  $domain Check if is on particular domain 
     *                          (example: @gmail.com)
     * --
     * @return  boolean
     */
    public static function is_email($value, $domain=null)
    {
        return self::check($value)
                ->has_value()
                ->is_email($domain)
                ->is_valid();
    }
}
