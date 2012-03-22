<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Math Class
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2011-08-17
 */

class uMath
{
	/**
	 * Get percent value from two numbers (amount, total)
	 *
	 * @param int $amount
	 * @param int $total
	 * @param int $precision -- decimal percision
	 *
	 * @return int
	 */
	public static function GetPercent($amount, $total, $precision=2)
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

		Log::Add('WAR', "Not a numeric parameter for amount: `{$amount}` or total: `{$total}`.", __LINE__, __FILE__);
		return false;
	}
	//-

	/**
	 * Get value by percent
	 *
	 * @param int $percent
	 * @param int $total
	 * @param int $precision -- decimal percision
	 *
	 * @return int
	 */
	public static function SetPercent($percent, $total, $precision=2)
	{
		if (is_numeric($percent) && is_numeric($total)) {
			if ($percent == 0 || $total == 0) {
				return 0;
			}

			# Calculate $percent from $total
			return number_format(($total / 100) * $percent, $precision);
		}

		Log::Add('WAR', "Not a numeric parameter for percent: `{$percent}` or total: `{$total}`.", __LINE__, __FILE__);
		return false;
	}
	//-
}
//--
