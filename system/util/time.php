<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Time Functions
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2009, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.60
 * @since      Date 2009-08-18
 */


class uTime
{
	/**
	 * Return date(Y-m-d H:i:s)
	 * ---
	 * @param bool $dateOnly -- return only date, or date and time?
	 * ---
	 * @return string
	 */
	public static function Now($dateOnly=false)
	{
		if ($dateOnly) {
			return date('Y-m-d');
		}
		else {
			return date('Y-m-d H:i:s');
		}
	}
	//-

	/**
	 * Format Date / Time
	 * ---
	 * @param string $pattern -- Example: %Y-%m-%d %H:%M:%S
	 * @param string $date / time (YYYY-dd-mm HH:ii:ss)
	 * ---
	 * @return string
	 */
	public static function Format($pattern, $date)
	{
		return strftime($pattern, strtotime($date));
	}
	//-

	/**
	 * Format Date / Time
	 * ---
	 * @param string $pattern -- Example: Y-m-d H:i:s
	 * @param string $date / time (YYYY-dd-mm HH:ii:ss)
	 * ---
	 * @return string
	 */
	public static function Format_d($pattern, $date)
	{
		return date($pattern, strtotime($date));
	}
	//-

	/**
	 * This will format any type of date/time to particular format
	 * ---
	 * @param str/int $date         -- example: 20110416194512 // for 2001 April 16, 19:45:12
	 * @param string  $dateFormat   -- example: yyyymmddhhiiss // This is description of our date
	 * @param string  $outputFormat
	 * @param string  $yearPrefix   -- usually is 20 (as for 20+11 = 2011)
	 * ---
	 * @return string
	 */
	public static function Format_c($date, $dateFormat, $outputFormat='%Y-%m-%d %H:%M:%S', $yearPrefix='20')
	{
		$year   = '';
		$month  = '';
		$day    = '';
		$hour   = '';
		$minute = '';
		$second = '';

		$dateFormat = strtolower($dateFormat);

		for ($i=0; $i<strlen($date); $i++)
		{
			switch ($dateFormat[$i])
			{
				case 'y':
					$year .= $date[$i];
					break;

				case 'm':
					$month .= $date[$i];
					break;

				case 'd':
					$day .= $date[$i];
					break;

				case 'h':
					$hour .= $date[$i];
					break;

				case 'i':
					$minute .= $date[$i];
					break;

				case 's':
					$second .= $date[$i];
					break;
			}
		}

		if (strlen($year) < 4) {
			$year = $yearPrefix . $year;
		}

		$month  = str_pad($month,  2, '0', STR_PAD_LEFT);
		$day    = str_pad($day,    2, '0', STR_PAD_LEFT);
		$hour   = str_pad($hour,   2, '0', STR_PAD_LEFT);
		$minute = str_pad($minute, 2, '0', STR_PAD_LEFT);
		$second = str_pad($second, 2, '0', STR_PAD_LEFT);

		$final = "{$year}-{$month}-{$day} {$hour}:{$minute}:{$second}";
		return self::Format($outputFormat, $final);
	}
	//-

	/**
	 * Will conver timezone.
	 *
	 * @param string $dateTime    - format: YYYY-dd-mm HH:ii:ss
	 * @param string $newTimezone - example: Europe/Ljubljana
	 * @param string $format      - Example: %Y-%m-%d %H:%M:%S
	 *
	 * @return string
	 */
	public static function NewTimezone($dateTime, $newTimezone, $format='Y-m-d H:i:s')
	{
		$dateTime = new DateTime($dateTime);
		$dateTime->setTimezone(new DateTimeZone($newTimezone));
		return $dateTime->format($format);
	}
	//-
}
//--
