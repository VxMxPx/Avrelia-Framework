<?php namespace Avrelia\Plug; if (!defined('AVRELIA')) die('Access is denied!');

use Avrelia\Core\Plug as Plug;
use Avrelia\Core\Log  as Log;
use Avrelia\Core\FileSystem as FileSystem;
use Avrelia\Core\Event as Event;
use Avrelia\Core\View as View;

/**
 * Avrelia
 * ----
 * Debug Plug
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-04-08
 */
class Debug
{
    /**
     * Initialize Debug plug
     */
    public static function _on_include_()
    {
        Plug::need(array(
            'Avrelia\\Plug\\JQuery',
            'Avrelia\\Plug\\HTML'
        ));

        # Add jQuery
        JQuery::add();
        HTML::add_header('<style>'.FileSystem::Read(ds(dirname(__FILE__).'/libraries/debug.css')).'</style>', 'cdebug_css');

        Event::watch('/plug/html/get_footers', array('Debug', 'AddPanel'));

        return true;
    }
    //-

    /**
     * Will add footers
     * --
     * @return  void
     */
    public static function AddPanel()
    {
        Log::add_benchmarks();
        HTML::add_footer(View::get(ds(dirname(__FILE__).'/views/panel.php'))->do_return(), 'cdebugPanel');
        HTML::add_footer('<script>'.FileSystem::Read(ds(dirname(__FILE__).'/libraries/debug.js')).'</script>', 'cdebug_js');
    }
    //-
}
//--
