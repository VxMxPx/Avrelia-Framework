<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Test CLI
 * -----------------------------------------------------------------------------
 * Used for easy testing.
 * ----
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class cliTest
{
    /**
     * Print the console!
     */
    public static function _empty()
    {
        # Main loop...
        do {
            if (function_exists('readline')) {
                $stdin = readline('test> ');
                readline_add_history($stdin);
            }
            else {
                echo "test> ";
                $stdin = fread(STDIN, 8192);
            }
            $stdin    = trim($stdin);
            $continue = ($stdin === 'exit' || $stdin === '\q') ? false : true;

            if ($continue) {
                $commands = vString::ExplodeTrim(' ', $stdin, 2);
                switch ($commands[0]) {
                    case '?':
                    case 'help':
                        self::_help();
                        break;

                    case '--list':
                        $path = isset($commands[1]) ? trim($commands[1]) : false;
                        $path = str_replace('..', '', $path);
                        $path = !empty($path) ? $path : false;
                        $path = ds(TESTPATH.'/'.$path);
                        if (!is_dir($path)) {
                            # Try add phpf at the end
                            $path .= '.phpf';
                            if (!is_dir($path)) {
                                Dot::war('Invalid path: `' . $path . '`.');
                            }
                        }
                        if (!is_dir($path)) {
                            Dot::war('Invalid path: `' . $path . '`.');
                        }
                        else {
                            self::_list($path);
                        }
                        break;

                    case '':
                        Dot::inf('Invalid command, type `?` or `help` for list of available commands.');
                        break;
                    
                    default:
                        if ($commands[0] === '*' || $commands[0] === 'all') {
                            self::_run_tests(TESTPATH, false);
                        }
                        else {
                            # Resolve inputed path...
                            $path = $commands[1];
                            $path = ds(TESTPATH . '/' . $path);

                            # Make tests if path exsits
                            if (!is_dir($path)) {
                                # Try to add .phpt at the end
                                $path_new = $path . '.phpt';
                                if (!is_dir($path_new)) {
                                    # Try to add phpt at the end (it's file!)
                                    $file = get_path_segment($path, -1);
                                    $dir = get_path_segment($path, -2, 1);
                                    $path_pre = get_path_segment($path, 0, -2);
                                    $path_file = ds("{$path_pre}/{$dir}.phpf/{$file}.phpt");

                                    if (!file_exists($path_file)) {
                                        Dot::war('Invalid path: ' . $path_file);
                                    }
                                    else {
                                        self::_list($path_file, true);
                                    }
                                }
                                else {
                                    self::_list($path_new, false);
                                }
                            }
                            else {
                                self::_list($path, false);
                            }
                        }
                        break;
                }
            }
        } while($continue == true);

        # At the end...
        echo "See you!\n";
    }

    public static function _run_tests($path, $file)
    {
        Dot::ok($path);
        $file ? Dot::ok('FILE') : Dot::ok('DIR');
    }

    private static function _list($path)
    {
        $contents = scandir($path);

        foreach ($contents as $file) {
            if ($file === '.' || $file === '..') { continue; }
            if (is_dir(ds($path.'/'.$file))) {
                if (substr($file, -5) === '.phpf') {
                    Dot::ok(
                        substr(
                            ds($path.'/'.$file), 
                            strlen(TESTPATH.'/'), 
                            -5
                        )
                    );
                    self::_list_methods(ds($path.'/'.$file));
                }
                self::_list(ds($path.'/'.$file));
            }
            else {
                if (substr($file, -5) === '.phpt') {
                    Dot::inf("  /".substr($file, 0, -5));
                }
            }
        }
    }

    private static function _help()
    {
        Dot::inf('List of available commands:');
        Dot::inf("*             Run all tests");
        Dot::inf("all           Run all tests");
        Dot::inf("{path}        Run particular test, examples:");
        Dot::inf("                  test system/core/functions");
        Dot::inf("                  test system/core/functions/ds");
        Dot::inf("--list        List available test");
        Dot::inf("--list {path} List available tests in particular path.");
    }
}