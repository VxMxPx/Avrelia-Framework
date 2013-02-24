<?php namespace Avrelia\Core; if (!defined('AVRELIA')) die('Access is denied!');

/**
 * Avrelia
 * ----
 * String Manipulation
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-03-20
 */

class vString
{
    /**
     * This function works similar as native crypt in PHP.
     *
     * You can provide salt to it, if you don't the salt will be auto-generated.
     * When comparing, you must use hash itself as salt. So:
     * if ($input === vString::Hash($input, $hashedPassword)) ...
     *
     * The output will be slightly modified sha1: ah10salt.hash:
     * a(vrelia)
     * h(ash)
     * 1(version)
     * 0(method, currently only: sha1)
     * salt
     * .
     * hash
     * --
     * @param   string  $string
     * @param   string  $salt
     * @param   boolean $attachSalt
     * --
     * @return  string
     */
    public static function Hash($string, $salt=false, $attachSalt=false)
    {
        if ($attachSalt) {
            if (!$salt) {
                $salt = str_replace('.', '+', md5(Str::random(12)));
            }
            else {
                # Check if we have anything meaningful
                if (substr($salt, 0, 4) === 'ah10') {
                    $salt = substr($salt, 4);
                    $salt = explode('.', $salt, 2);
                    $salt = $salt[0];
                }
                else {
                    $salt = str_replace('.', '+', $salt);
                }
            }

            return 'ah10' . $salt . '.' . sha1( sha1 ($string) . sha1 ($salt) );
        }
        else {
            if ($salt) {
                return sha1( sha1 ($string) . sha1 ($salt) );
            }
            else {
                return sha1( sha1 ($string) );
            }
        }
    }
    //-

    /**
     * Strip HTML Tags, and Attributes
     * from PHP.net by Nick
     * <?php vString::stripTagsAttributes($string,'<strong><em><a>','href,rel'); ?>
     * --
     * @param   string  $string
     * @param   string  $allowTags          <strong><em><a>
     * @param   mixed   $allowAttributes    href,rel
     * --
     * @return  string
     */
    public static function StripTagsAttributes($string, $allowTags=null, $allowAttributes=null)
    {
        if ($allowAttributes) {
            if (!is_array($allowAttributes)) {
                $allowAttributes = explode(',', $allowAttributes);
            }

            if (is_array($allowAttributes)) {
                $allowAttributes = implode('|', $allowAttributes);
            }

            $rep = '/([^>]*) ('.$allowAttributes.')(=)(\'.*\'|".*")/i';
            $string = preg_replace($rep, '$1 $2_-_-$4', $string);
        }

        if (preg_match('/([^>]*) (.*)(=\'.*\'|=".*")(.*)/i', $string) > 0) {
            $string = preg_replace('/([^>]*) (.*)(=\'.*\'|=".*")(.*)/i', '$1$4', $string);
        }

        $rep = '/([^>]*) ('.$allowAttributes.')(_-_-)(\'.*\'|".*")/i';

        if ($allowAttributes) {
            $string = preg_replace($rep, '$1 $2=$4', $string);
        }

        return strip_tags($string, $allowTags);
    }
    //-

    /**
     * Will Convert HTML tags (< >) to save-for-output (&lt; &gt;)
     * --
     * @param   string  $input
     * --
     * @return  string
     */
    public static function EncodeEntities($input)
    {
        $output = str_replace(array('<', '>'), array('&lt;', '&gt;'), $input);
        return $output;
    }
    //-

    /**
     * Will Convert save-for-output tags (&lt; &gt;) back to HTML tags (< >)!
     * --
     * @param   string  $input
     * --
     * @return  string
     */
    public static function RestoreEntities($input)
    {
        $output = str_replace(array('&lt;', '&gt;'), array('<', '>'), $input);
        return $output;
    }
    //-
}
//--
