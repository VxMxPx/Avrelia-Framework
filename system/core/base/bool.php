<?php namespace Avrelia\Core; if (!defined('AVRELIA')) die('Access is denied!');

/**
 * Bool Base Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Bool
{
    /**
     * Parse string, and convert it to boolean.
     * Convert '1', 1, 'yes' and 'true' to true
     * --
     * @param   string  $input
     * @return  boolean
     */
    public static function parse($input)
    {
        switch (strtolower($input))
        {
            case 1:
            case '1':
            case 'yes':
            case 'true':
                return true;

            default:
                return false;
        }
    }
}
