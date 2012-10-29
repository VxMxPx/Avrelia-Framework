<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * avr CLI
 * -----------------------------------------------------------------------------
 * Interactive shell
 * ----
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class avr_Cli
{
    public function __construct($params)
    {
        # Main loop...
        do {
            if (function_exists('readline')) {
                $stdin = readline('avrelia> ');
                readline_add_history($stdin);
            }
            else {
                echo "avrelia> ";
                $stdin = fread(STDIN, 8192);
            }
            $stdin    = trim($stdin);
            $continue = ($stdin == 'exit' || $stdin == '\q') ? false : true;

            if ($continue) {
                eval(
                    '$val = ' . 
                    (substr($stdin,-1,1) == ';' 
                        ? $stdin 
                        : $stdin . ';') . 
                    ' echo dump_r($val);');
                echo "\n";
            }
        } while($continue == true);

        Dot::inf('See you!');
    }
}
