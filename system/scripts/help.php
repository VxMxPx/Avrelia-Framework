<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * help CLI
 * -----------------------------------------------------------------------------
 * Simple help cli
 * ----
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class help_Cli
{
    public function __construct($params)
    {
        Dot::inf("Available commands:");

        if (is_dir(sys_path('scripts'))) 
            { $list_sys = scandir(sys_path('scripts')); }
        else 
            { $list_sys = array(); }

        if (is_dir(app_path('scripts')))
            { $list_app = scandir(app_path('scripts')); }
        else
            { $list_app = array(); }

        $list_all = array_merge($list_sys, $list_app);

        if (!empty($list_all)) {
            foreach ($list_all as $comm) {
                if (substr($comm, -4, 4) != '.php') { continue; }
                Dot::inf("  " . substr($comm, 0, -4));
            }
        }
    }
}
