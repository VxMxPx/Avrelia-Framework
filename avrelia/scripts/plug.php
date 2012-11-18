<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

use Avrelia\Core\Plug as Plug;

/**
 * plug CLI
 * -----------------------------------------------------------------------------
 * Plug management
 * ----
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class plug_Cli
{
    public function __construct($params)
    {
    }

    public function action_none()
    {
        $this->action_help();
    }

    public function action_list($param=false)
    {
        if ($param === 'all') {

            Dot::inf(print_r(Plug::list_all(), true));
        }
        else if ($param === 'detail') {

            Dot::inf(print_r(Plug::list_enabled(), true));
        }
        else {

            $plugs = Plug::list_enabled();
            $plugs = array_keys($plugs);

            Dot::inf(preg_replace('/^Plug\\\/m', '', implode("\n", $plugs)));
        }
    }

    public function action_enable($plug)
    {
        substr($plug, 0, 5) === 'Plug\\' OR $plug = 'Plug\\' . $plug;
        
        if (Plug::enable($plug)) {
            Dot::ok('Success!');
        }
        else {
            Dot::err('Failed!');
        }
    }

    public function action_disable($plug)
    {
        substr($plug, 0, 5) === 'Plug\\' OR $plug = 'Plug\\' . $plug;

        if (Plug::disable($plug)) {
            Dot::ok('Success!');
        }
        else {
            Dot::err('Failed!');
        }
    }

    public function action_reset($plug)
    {
        substr($plug, 0, 5) === 'Plug\\' OR $plug = 'Plug\\' . $plug;

        if (Plug::disable($plug) && Plug::enable($plug)) {
            Dot::ok('Success!');
        }
        else {
            Dot::err('Failed!');
        }
    }

    public function action_help()
    {
        Dot::doc(
            'Plug, plugs management application',
            'Usage: plug [option] [plug name]',
            array(
                'list [all|detail]' => 'List currently enabled plugs.',
                                       'If you pass in [all], will list all available plugs.',
                                       'If you pass in [detail], will list all',
                                       'enabled plugs plus details.',
                'enable <plug>'     => 'Enable particular plug',
                'disable <plug>'    => 'Disable particular plug',
                'reset <plug>'      => 'Disable and enable particular plug',
                'help'              => 'Display this help',
            )
        );
    }
}
