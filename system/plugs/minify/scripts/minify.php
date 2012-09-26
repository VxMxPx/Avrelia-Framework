<?php if (!defined('AVRELIA')) die('Access is denied!');

class Minify_Cli
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
            'Usage: minify watch',
            array(
                'watch' => 'Will be observing selected files and directories.',
            )
        );
    }

    public function action_watch()
    {
        $directory = ds(get_path_segment(__FILE__, 0, -2), 'libs');
        $shell_dir = escapeshellarg($directory);
        $js = Cfg::get('plugs/minify/javascript');

        if ($js['enabled'] === true) {
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
                dump($command);
                // $this->_fork_command($command);
            }
        }

        //$this->_fork_command('php stylus.build.php ../bugless/sources/stylus/brown.styl ../profile/public/themes/brown');
    }
}