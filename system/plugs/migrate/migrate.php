<?php namespace Avrelia\Plug; if (!defined('AVRELIA')) die('Access is denied!');

use Avrelia\Core\Plug as Plug;
use Avrelia\Core\FileSystem as FileSystem;
use Avrelia\Core\Cfg as Cfg;
use Avrelia\Core\Log as Log;
use Avrelia\Core\JSON as JSON;
use Avrelia\Core\Arr as Arr;

/**
 * Migrate Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Migrate
{
    # Current status
    protected $status = false;

    public function __construct()
    {
        # Load config
        Plug::get_config(__FILE__);

        # Try to load file with current status
        $filename = Cfg::get('plugs/migrate/status_file');
        if (file_exists($filename)) {
            $this->status = JSON::decode_file($filename, true);
        }
        else {
            $this->status = array(
                'current_version' => 0,
                'latest_version'  => $this->_find_latest()
            );
            $this->_save_status();
        }
    }

    /**
     * Save status $this->status to file.
     * @return boolean
     */
    protected function _save_status()
    {
        return JSON::encode_file(
            Cfg::get('plugs/migrate/status_file'),
            $this->status);
    }

    /**
     * Create new migration, with tasks and optional name
     * @param  array  $tasks
     * @param  string $name
     * @return mixed  False if there was an error and integer, with version if
     *                
     */
    public function create($tasks, $name=null)
    {
        // MIGRATE UP
        $string = '-- MIGRATE UP';
        $name and $string .= ': ' . $name;

        if (!Arr::is_empty($tasks)) {
            foreach ($tasks as $task) {
                $string .= "\n--TASK: {$task}";
            }
        }

        // MIGRATE DOWN
        $string .= "\n\n--MIGRATE DOWN";
        $name and $string .= ': ' . $name;

        if (!Arr::is_empty($tasks)) {
            foreach ($tasks as $task) {
                $string .= "\n--TASK: Undo {$task}";
            }
        }

        // Save
        if (FileSystem::Write(
            $string, 
            ds(
                Cfg::get('plugs/migrate/directory'), 
                (int)$this->status['latest_version'] + 1 . '.sql'
            )
        )) {
            $this->status['latest_version'] += 1;
            return $this->_save_status();
        }
    }

    /**
     * Get current migration version
     * @return integer
     */
    public function get_current()
    {
        return $this->status['current_version'];
    }

    /**
     * Get latest migration version
     * @return integer
     */
    public function get_latest()
    {
        return $this->status['latest_version'];
    }

    /**
     * Scan migrations directory to find latest version of migrations. This
     * is only in case if _status.json file got lost somehow.
     * --
     * @return integer
     */
    protected function _find_latest()
    {
        $dir = Cfg::get('plugs/migrate/directory');
        $contents = scandir($dir);
        $latest   = 0;

        if (is_array($contents)) {
            foreach ($contents as $file) {
                if (substr($file, -4, 4) === '.sql') {
                    $version = substr($file, 0, -4);
                    if (!is_numeric($version)) { continue; }
                    $version = (int) $version;
                    $latest = $version > $latest 
                                ? $version 
                                : $latest;
                }
            }
        }

        return $latest;
    }

    public static function _on_enable_()
    {
        # Plug need the following:
        Plug::need('Avrelia\\Plug\\Database');

        # Load config
        Plug::get_config(__FILE__);

        # Create migrations directory
        $directory = Cfg::get('plugs/migrate/directory');
        if (!is_dir($directory)) 
            { FileSystem::MakeDir($directory, true, 0777); }
        else 
            { Log::war("Migrations directory already exists: `{$directory}`."); }

        return is_dir($directory);
    }

    public static function _on_disable_()
    {
        # Load config
        Plug::get_config(__FILE__);

        # Create migrations directory
        $directory = Cfg::get('plugs/migrate/directory');

        if (!is_dir($directory)) 
            { FileSystem::Remove($directory); } 
        else 
            { Log::war("directory can't be erased: `{$directory}`."); }

        return true;
    }
}