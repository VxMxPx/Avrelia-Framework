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
        # Need cJquery
        if (!Plug::has('Avrelia\\Plug\\JQuery'))
        {
            Log::war("Plug `debug` need `jquery` plug to be enabled.");
            return false;
        }

        # Need cHTML (if we have jQuery then HTML is almost for sure available too, but just to be sure)
        if (!Plug::has('Avrelia\\Plug\\HTML')) {
            Log::war("Plug `debug` need `html` plug to be enabled.");
            return false;
        }

        # Add jQuery
        JQuery::Add();
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
