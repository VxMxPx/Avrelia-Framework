<?php if (!defined('AVRELIA')) die('Access is denied!');

class minify_Cli
{
    /**
     * Will run new command (new process) and output the results
     * --
     * @param  string $command
     * --
     * @return void
     */
    protected function _fork_command($command)
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            die('Failed to create new process. Reason: pcntl_fork.');
        }
        elseif ($pid) {
            # We are the parent
            system($command);
            pcntl_wait($status); # Protect against Zombie children
        }
    }

    public function __construct()
    {
        $file = (realpath(dirname(__FILE__).'/../minify.php'));
        Plug::get_config($file);
    }

    public function action_none()
    {
        Dot::doc(
            'Minify, JavaScript and CSS compressor',
            'Usage: minify watch <option>',
            array(
                'watch <css|js>' => 'Will be observing selected files and directories.',
            )
        );
    }

    public function action_watch($param=false)
    {
        $directory = ds(get_path_segment(__FILE__, 0, -2), 'libs');
        $shell_dir = escapeshellarg($directory);
        $js  = Cfg::get('plugs/minify/javascript');
        $css = Cfg::get('plugs/minify/css');

        if ($js['enabled'] === true && (!$param || $param === 'js')) {
            $command  = "cd {$shell_dir} && ";
            $command .= "php javascript.build.php ";

            $input  = escapeshellarg($js['input']);
            $output = escapeshellarg($js['output']);
            $command .= "{$input} {$output}";

            if ($js['coffee'] && $js['uglify'])
                { $command .= ' coffee+uglify'; }
            else if ($js['coffee'])
                { $command .= ' coffee'; }
            else if ($js['uglify'])
                { $command .= ' uglify'; }
            else
                { $command = false; }

            if ($command) {
                $this->_fork_command($command);
            }
        }

        if ($css['enabled'] === true && (!$param || $param === 'css')) {
            $command  = "cd {$shell_dir} && ";
            $command .= "php stylus.build.php ";

            $input  = escapeshellarg($css['input']);
            $output = escapeshellarg($css['output']);
            $command .= "{$input} {$output}";
            
            $this->_fork_command($command);
        }
    }
}