<?php

namespace Plug\Avrelia;

use Avrelia\Core\Log as Log;

/**
 * Math Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Math
{
    /**
     * Get percent value from two numbers (amount, total)
     * --
     * @param  integer $amount
     * @param  integer $total
     * @param  integer $precision  Decimal percision
     * --
     * @return integer or false
     */
    public static function get_percent($amount, $total, $precision = 2)
    {
        if (is_numeric($amount) && is_numeric($total)) {
            if ($amount == 0 || $total == 0) {
                return $amount;
            }
            $count = $amount / $total;
            $count = $count * 100;
            $count = number_format($count, $percision);
            return $count;
        }

        Log::war("Not a numeric parameter for amount: `{$amount}` or total: `{$total}`.");
        return false;
    }

    /**
     * Get value by percent
     * --
     * @param  integer $percent
     * @param  integer $total
     * @param  integer $precision  Decimal percision
     * --
     * @return integer
     */
    public static function set_percent($percent, $total, $precision = 2)
    {
        if (is_numeric($percent) && is_numeric($total)) {
            if ($percent == 0 || $total == 0) {
                return 0;
            }

            # Calculate $percent from $total
            return number_format(($total / 100) * $percent, $precision);
        }

        Log::war("Not a numeric parameter for percent: `{$percent}` or total: `{$total}`.");
        return false;
    }

}
