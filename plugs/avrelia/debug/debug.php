<?php namespace Plug\Avrelia; if (!defined('AVRELIA')) die('Access is denied!');

use Avrelia\Core\Plug as Plug;
use Avrelia\Core\Log  as Log;
use Avrelia\Core\FileSystem as FileSystem;
use Avrelia\Core\Event as Event;
use Avrelia\Core\View as View;

/**
 * Debug Panel Code
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Debug
{
    /**
     * Initialize Debug plug
     */
    public static function _on_include_()
    {
        Plug::need(array(
            'Plug\\Avrelia\\JQuery',
            'Plug\\Avrelia\\HTML',
            'Plug\\Avrelia\\LogWritter'
        ));

        # Add jQuery
        JQuery::add();

        HTML::add_header(
            '<style>'.
                FileSystem::Read(ds(dirname(__FILE__).'/libraries/debug.css')).
            '</style>', 
            'Plug/Avrelia/Debug');

        Event::on('/plug/html/get_footers', function() {
            Log::add_benchmarks();

            HTML::add_footer(
                View::get(ds(dirname(__FILE__).'/views/panel.php'), array(
                    'log_html' => LogWritter::as_html()
                ))->do_return(), 
                'cdebugPanel');

            HTML::add_footer(
                '<script>'.
                    FileSystem::Read(ds(dirname(__FILE__).'/libraries/debug.js')).
                    '</script>',
                    'Plug/Avrelia/Debug');
        });

        return true;
    }
}
