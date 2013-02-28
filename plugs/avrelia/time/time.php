<?php

namespace Plug\Avrelia;

/**
 * Time Plug
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Time
{
    /**
     * Return date(Y-m-d H:i:s)
     * --
     * @param  boolean $date_only  Return only date, or date and time?
     * --
     * @return string
     */
    public static function now($date_only = false)
    {
        return $date_only ? date('Y-m-d') : date('Y-m-d H:i:s');
    }

    /**
     * Format Date / Time
     * --
     * @param  string $pattern  Example: %Y-%m-%d %H:%M:%S
     * @param  string $date     Time (YYYY-dd-mm HH:ii:ss)
     * --
     * @return string
     */
    public static function format($pattern, $date)
    {
        return strftime($pattern, strtotime($date));
    }

    /**
     * Format Date / Time
     * --
     * @param  string $pattern Example: Y-m-d H:i:s
     * @param  string $date    Time (YYYY-dd-mm HH:ii:ss)
     * --
     * @return string
     */
    public static function format_d($pattern, $date)
    {
        return date($pattern, strtotime($date));
    }

    /**
     * This will format any type of date/time to particular format
     * --
     * @param  string $date           Example: 20110416194512 // for 2001 April 16, 19:45:12
     * @param  string $date_format    Example: yyyymmddhhiiss // This is description of our date
     * @param  string $output_format
     * @param  string $year_prefix    Usually is 20 (as for 20+11 = 2011)
     * --
     * @return string
     */
    public static function format_c(
        $date,
        $date_format,
        $output_format = '%Y-%m-%d %H:%M:%S',
        $year_prefix = '20')
    {
        $year   = '';
        $month  = '';
        $day    = '';
        $hour   = '';
        $minute = '';
        $second = '';

        $date_format = strtolower($date_format);

        for ($i=0; $i<strlen($date); $i++)
        {
            switch ($date_format[$i])
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
            $year = $year_prefix . $year;
        }

        $month  = str_pad($month,  2, '0', STR_PAD_LEFT);
        $day    = str_pad($day,    2, '0', STR_PAD_LEFT);
        $hour   = str_pad($hour,   2, '0', STR_PAD_LEFT);
        $minute = str_pad($minute, 2, '0', STR_PAD_LEFT);
        $second = str_pad($second, 2, '0', STR_PAD_LEFT);

        $final = "{$year}-{$month}-{$day} {$hour}:{$minute}:{$second}";
        return self::format($output_format, $final);
    }

    /**
     * Will conver timezone.
     * --
     * @param string $date_time     Format: YYYY-dd-mm HH:ii:ss
     * @param string $new_timezone  Example: Europe/Ljubljana
     * @param string $format        Example: %Y-%m-%d %H:%M:%S
     * --
     * @return string
     */
    public static function new_timezone(
        $date_time,
        $new_timezone,
        $format = 'Y-m-d H:i:s')
    {
        $date_time = new DateTime($date_time);
        $date_time->setTimezone(new DateTimeZone($new_timezone));
        return $date_time->format($format);
    }

}