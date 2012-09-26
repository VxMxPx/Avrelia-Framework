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
        $list = Dot::get_all_scripts();

        if (!empty($list)) {
            foreach ($list as $script => $path) {
                Dot::inf("  {$script}");
            }
        }
    }
}
