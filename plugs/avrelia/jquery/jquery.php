<?php namespace Plug\Avrelia; if (!defined('AVRELIA')) die('Access is denied!');

use Avrelia\Core\Plug as Plug;
use Avrelia\Core\Cfg  as Cfg;

/**
 * Jquery Plug
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class JQuery
{
    private static $config; # string Plug's configs
    private static $link;   # string Actual full link to script (either local or googleapis)
    private static $tag;    # string Tag template

    /**
     * Will add jQuery to HTML footer.
     * --
     * @return  boolean
     */
    public static function _on_include_()
    {
        Plug::need(array(
            'Plug\\Avrelia\\HTML'
        ));
        
        self::$config = Plug::get_config(__FILE__);

        if (self::$config['local']) {
            # We have some public material
            Plug::set_public(__FILE__);

            # Link
            self::$link = url(
                Cfg::get('core/plug/public_dir', 'plugs').
                '/jquery/jquery-'.self::$config['version'].'.min.js');
        }
        else {
            # Link
            self::$link = 'http://ajax.googleapis.com/ajax/libs/jquery/'.
                            self::$config['version'].'/jquery.min.js';
        }

        self::$tag = '<script src="'.self::$link.'"></script>';

        # Add footer tag
        HTML::add_footer(self::$tag, 'Avrelia/Plug/JQuery');

        return true;
    }

    /**
     * Add jQuery to cHTML footer.
     * --
     * @return  void
     */
    public static function add()
        { HTML::add_footer(self::$tag, 'Avrelia/Plug/JQuery'); }

    /**
     * Remove jQuery from cHTML footer.
     * --
     * @return  void
     */
    public static function remove()
        { HTML::add_footer(false, 'Avrelia/Plug/JQuery'); }

    /**
     * Get only url
     * --
     * @return  string
     */
    public static function url()
        { return self::$link; }
}
