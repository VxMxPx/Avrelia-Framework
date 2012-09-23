<?php namespace Avrelia\Plug; if (!defined('AVRELIA')) die('Access is denied!');

use Avrelia\Core\Plug as Plug;
use Avrelia\Core\Cfg  as Cfg;

/**
 * Avrelia
 * ----
 * Will All jQuery Library To cHTML Footers
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-02-19
 */
class JQuery
{
    private static $Config; # string    Plug's configs
    private static $link;   # string    Actual full link to script (either local or googleapis)
    private static $tag;    # string    Tag template

    /**
     * Will add jQuery to HTML footer.
     * --
     * @return  boolean
     */
    public static function _on_include_()
    {
        self::$Config = Plug::get_config(__FILE__);

        if (self::$Config['local']) {
            # We have some public material
            Plug::set_public(__FILE__);

            # Link
            self::$link = url(Cfg::get('plug/public_dir', 'plugs').'/jquery/jquery-'.self::$Config['version'].'.min.js');
        }
        else {
            # Link
            self::$link = 'http://ajax.googleapis.com/ajax/libs/jquery/'.self::$Config['version'].'/jquery.min.js';
        }

        self::$tag = '<script src="'.self::$link.'"></script>';

        # Add footer tag
        HTML::add_footer(self::$tag, 'cjquery');

        return true;
    }
    //-

    /**
     * Add jQuery to cHTML footer.
     * --
     * @return  void
     */
    public static function Add()
    {
        HTML::add_footer(self::$tag, 'cjquery');
    }
    //-

    /**
     * Remove jQuery from cHTML footer.
     * --
     * @return  void
     */
    public static function Remove()
    {
        HTML::add_footer(false, 'cjquery');
    }

    /**
     * Get only url
     * --
     * @return  string
     */
    public static function Url()
    {
        return self::$link;
    }
}
