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
class test_Cli
{
    # History of tests
    protected $history = array();

    public function __construct($params)
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
                $commands = Str::explode_trim(' ', $stdin, 2);
                $command = $commands[0];
                $param   = isset($commands[1]) ? trim($commands[1]) : false;

                if (is_numeric($command)) {
                    $this->_run_history($command);
                    continue;
                }

                switch ($command) {
                    case '?':
                    case 'help':
                        $this->_help();
                        break;

                    case 'log':
                        $this->_log($param);
                        break;

                    case 'edit':
                        $this->_edit($param);
                        break;

                    case 'list':
                        $this->_list($param);
                        break;

                    case '':
                        Dot::inf(
                            'Invalid command, type `?` or `help` '.'
                            for list of available commands.');
                        break;
                    
                    default:
                        $command = in_array($command, array('all', '*'))
                                        ? TESTPATH
                                        : ds(TESTPATH.'/'.$command);
                        $this->_run($command);
                        break;
                }
            }
        } while($continue == true);

        Dot::inf('See you!');
    }

    protected function _log($num)
    {
        # Index of log we wanna access
        $num = ((int) $num) -1;

        if (isset($this->history[$num]))
        {
            if (isset($this->history[$num]['path'])) 
            {
                $file = $this->history[$num]['path'];
                $file = substr($file, 0, -4) . 'log';

                if (file_exists($file)) 
                {
                    $contents = FileSystem::Read($file);
                    Dot::inf($contents);
                    return;
                }
                else {
                    Dot::war('File not found: ' . $file);
                    return;
                }
            }
        }

        Dot::war('Log not found: ' . $num);
    }

    protected function _edit($num)
    {
        # History index
        $num = ((int) $num) -1;

        if (isset($this->history[$num])) 
        {
            if (isset($this->history[$num]['path']))
            {
                $file = $this->history[$num]['path'];
                if (file_exists($file)) {
                    $command = str_replace(
                        '%s', 
                        $file, 
                        Cfg::get('core/tests/editor_command'
                    ));
                    exec($command);
                    return;
                }
                else {
                    Dot::war('File not found: ' . $file);
                    return;
                }
            }
        }

        Dot::war('Invalid line number: ' . $num);
    }    

    protected function _run($path)
    {
        # Make tests if path exsits
        if (!is_dir($path)) 
        {
            # Try to add .phpt at the end
            $path_new = $path . '.phpf';
            if (!is_dir($path_new)) {
                # Try to add phpt at the end (it's file!)
                $file = get_path_segment($path, -1);
                $dir = get_path_segment($path, 0, -1);
                $path_file = ds("{$dir}.phpf/{$file}.phpt");

                if (!file_exists($path_file)) {
                    Dot::war('Invalid path: ' . $path_file);
                    return;
                }
                else {
                    $this->_run_resolver($path_file);
                }
            }
            else {
                $this->_run_resolver($path_new);
            }
        }
        else {
            $this->_run_resolver($path);
        }
    }

    protected function _run_resolver($path)
    {
        # Very simple, do we have a paricular file?
        if (substr($path, -5) === '.phpt') {
            return $this->_run_test($path);
        }

        # Do we have test folder..?
        if (substr($path, -5) === '.phpf') {
            # Look for test files...
            $files = scandir($path);
            foreach ($files as $file) {
                if (substr($file, -5) === '.phpt') {
                    $this->_run_test(ds($path.'/'.$file));
                }
            }
            return;
        }

        # Do we have regular folder?
        if (is_dir($path)) {
            $files = scandir($path);
            foreach ($files as $file) {
                if (substr($file, 0, 1) === '.') { continue; }
                $this->_run_resolver(ds($path.'/'.$file));
            }
        }
    }

    protected function _run_test($path)
    {
        $dir = get_path_segment($path, 0, -1);
        $file = get_path_segment($path, -1);
        exec('cd ' . $dir . ' && pear run-tests ' . $file, $output);

        $output['path'] = $path;
        $this->history[] = $output;
        $count = count($this->history);
        $line = $count . ': ' . $output[1];

        if (substr($output[1], 0, 4) === 'FAIL') {
            Dot::err($line);
        }
        elseif (substr($output[1], 0, 4) === 'PASS') {
            Dot::ok($line);
        }
        else {
            Dot::inf($line);
        }
    }

    public function _run_history($num)
    {
        # History index
        $num = ((int) $num) -1;

        if (isset($this->history[$num])) 
        {
            if (isset($this->history[$num]['path']))
            {
                $file = $this->history[$num]['path'];
                if (file_exists($file)) {
                    $this->_run_test($file);
                    return;
                }
                else {
                    Dot::war('File not found: ' . $file);
                    return;
                }
            }
        }

        Dot::war('Invalid line number: ' . $num);
    }

    protected function _list($path)
    {
        $path = trim($path);
        $path = str_replace('..', '', $path);
        $path = !empty($path) ? $path : false;
        $path = ds(TESTPATH.'/'.$path);
        if (!is_dir($path)) {
            $path .= '.phpf';
            if (!is_dir($path)) {
                Dot::war('Invalid path: `' . $path . '`.');
                return false;
            }
        }
        
        self::_list_out($path);
    }

    protected function _list_out($path)
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
                }
                $this->_list_out(ds($path.'/'.$file));
            }
            else {
                if (substr($file, -5) === '.phpt') {
                    Dot::inf("  /".substr($file, 0, -5));
                }
            }
        }
    }

    protected function _help()
    {
        Dot::inf('List of available commands:');
        Dot::inf('*|all         Run all tests');
        Dot::inf('{path}        Run particular test, examples:');
        Dot::inf('                  test system/core/functions');
        Dot::inf('                  test system/core/functions/ds');
        Dot::inf('list          List available test');
        Dot::inf('list {path}   List available tests in particular path.');
        Dot::inf('edit {index}  Edit particular test.');
        Dot::inf('log {index}   Log of particular test, if failed.');
    }
}