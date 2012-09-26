<?php namespace Avrelia\Plug; if (!defined('AVRELIA')) die('Access is denied!');

use Avrelia\Core\Plug as Plug;
use Avrelia\Core\FileSystem as FileSystem;
use Avrelia\Core\Cfg as Cfg;
use Avrelia\Core\Log as Log;
use Avrelia\Core\JSON as JSON;
use Avrelia\Core\Arr as Arr;
use Avrelia\Core\Str as Str;
use Avrelia\Core\Event as Event;

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
     * --
     * @param  array  $tasks
     * @param  string $name
     * --
     * @return mixed  False if there was an error and integer, with version if           
     */
    public function create($tasks, $name=null)
    {
        // MIGRATE UP
        $string = '-- MIGRATE UP';
        $name and $string .= ': ' . $name;

        if (!Arr::is_empty($tasks)) {
            foreach ($tasks as $task) {
                $string .= "\n-- TASK: {$task}";
            }
        }

        // MIGRATE DOWN
        $string .= "\n\n-- MIGRATE DOWN";
        $name and $string .= ': ' . $name;

        if (!Arr::is_empty($tasks)) {
            foreach ($tasks as $task) {
                $string .= "\n-- TASK: Undo {$task}";
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
     * Migrate to the latest version
     * --
     * @return boolean
     */
    public function migrate()
    {
        if ($this->status['current_version'] === $this->status['latest_version']) {
            Event::inf(
                '/plug/avrelia/migrate',
                "Already at the latest version!");
            return true;
        }

        return $this->to($this->status['latest_version']);
    }

    /**
     * Migrate one version up
     * --
     * @return boolean
     */
    public function up()
    {
        if ($this->status['current_version'] === $this->status['latest_version']) {
            Event::inf(
                '/plug/avrelia/migrate',
                "Can't go up, we're at the latest version!");
            return true;
        }

        $version = $this->status['current_version'] + 1;
        return $this->to($version);
    }

    /**
     * Migrate one version down
     * --
     * @return boolean
     */
    public function down()
    {
        if ($this->status['current_version'] === 1) {
            Event::inf(
                '/plug/avrelia/migrate',
                "Can't go down, we're at the first version!");
            return true;
        }

        $version = $this->status['current_version'] - 1;
        return $this->to($version);
    }

    /**
     * Jump to particular version
     * --
     * @param  integer $version
     * --
     * @return boolean
     */
    public function to($version)
    {
        $version = (int) $version;
        if ($version < 1) { 
            Log::war("Version must be more than one: `{$version}`.");
            return false; 
        }
        if ($version > $this->status['latest_version']) { 
            Log::war("Version must not be more than latest: ".
                     "`{$this->status['latest_version']}`, required was: ".
                     "`{$version}`.");
            return false; 
        }

        if ($version == $this->status['current_version']) {
            Log::inf("Version is already set to: `{$version}`.");
            return true;
        }

        $type = $version < $this->status['current_version']
                    ? 'DOWN'
                    : 'UP';

        // Create the versions range
        $start = $type === 'UP'
                    ? $this->status['current_version'] + 1
                    : $this->status['current_version'];

        $end = $type === 'UP'
                ? $version
                : $version + 1;

        $range = range($start, $end, 1);

        // Start the loop
        foreach ($range as $version_step) {

                $contents  = $this->_get_version_file($version_step) 
            and $migration = $this->_prepare_migration($contents);

            $about = $migration[$type]['about']
                        ? ", `{$migration[$type]['about']}`"
                        : '';

            Event::inf(
                '/plug/avrelia/migrate/to/before',
                "Will {$type} to version {$version_step}.");

            try {
                $this->_execute_migration($migration, $type);
            }
            catch (\Avrelia\Exception\Database $e) {
                throw new \Avrelia\Exception\Database(
                    "Failed to execute action `{$type}` for version ".
                    "`{$version_step}`{$about}.\n".
                    "Reason: " . $e->getMessage());
            }

            // Update version
            $this->status['current_version'] = $type === 'DOWN'
                                                ? $version_step - 1
                                                : $version_step;
            $this->_save_status();
            Event::inf(
                '/plug/avrelia/migrate/to/success', 
                "Done: {$type} to version {$this->status['current_version']}{$about}.");
        }

        return true;
    }

    /**
     * Execute particular migration (all tasks for it)
     * --
     * @param  array   $migration
     * @param  string  $type       UP | DOWN
     * --
     * @return boolean
     */
    protected function _execute_migration($migration, $type)
    {
        $tasks = $migration[$type]['tasks'];

        foreach ($tasks as $task) {
            $task_id = $task['about']
                        ? ": {$task['about']}"
                        : null;

            if (Database::execute($task['statement'])->succeed()) {
                Event::inf(
                    '/plug/avrelia/migrate/task/success', 
                    "Task done{$task_id}");
            }
            else {
                throw new \Avrelia\Exception\Database(
                    "Failed to execute task{$task_id}");
            }
        }

        return true;
    }

    /**
     * Prepare migration, take raw string input, and split it so that it returns
     * migration array ready for execution.
     * --
     * @param  string $raw_string
     * @return array
     */
    protected function _prepare_migration($raw_string)
    {
        $list = preg_split(
            '/-- MIGRATE ((?:UP|DOWN)(?:\: .*)?)/', 
            $raw_string,
            null,
            PREG_SPLIT_DELIM_CAPTURE);

        // We know that 1 and 3 will be keyword and 2 and 4 will be tasks
        // See if we have all required keys...
        if (!Arr::has_keys($list, array(1, 2, 3, 4))) {
            Log::war("Invalid migration: `{$version}`.");
            return false;
        }
        $list[1] = Str::explode_trim(':', $list[1], 2);
        $list[3] = Str::explode_trim(':', $list[3], 2);

        return array(
            $list[1][0] => array(
                'about' => isset($list[1][1]) ? trim($list[1][1]) : null,
                'tasks' => $this->_prepare_tasks($list[2]),
            ),
            $list[3][0] => array(
                'about' => isset($list[3][1]) ? trim($list[3][1]) : null,
                'tasks' => $this->_prepare_tasks($list[4]),
            )
        );
    }

    /**
     * Prepare tasks, takes raw string input, and split it so, that it returns
     * tasks array ready for execution.
     * --
     * @param  string $raw_tasks
     * @return array
     */
    protected function _prepare_tasks($raw_tasks)
    {
        $list = preg_split(
            '/-- (TASK(?:\: .*)?)/', 
            $raw_tasks,
            null,
            PREG_SPLIT_DELIM_CAPTURE);

        $final = array();
        // We need the array to have some value of course...
        if (!Arr::is_empty($list)) {
            $length = count($list);
            for ($pos=0; $pos < $length; $pos++) {
                $element = $list[$pos];

                if (substr($element, 0, 4) === 'TASK') {
                    // Increase position, as we'll take next item by default as
                    // statement.
                    $pos += 1;

                    // Get description if any...
                    $element = Str::explode_trim(':', $element, 2);

                    // Get next element with statement
                    $statement = $list[$pos];

                    // Add task to final list
                    $final[] = array(
                        'about'     => isset($element[1]) ? trim($element[1]) : null,
                        'statement' => trim($statement)
                    );
                }
            }
        }
        
        return $final;
    }

    /**
     * Load particular version's SQL if exists, and return it as string. Return
     * false if file was not found.
     * @param  integer $version
     * @return string
     */
    protected function _get_version_file($version)
    {
        // See if file exists
        $filename = ds(Cfg::get('plugs/migrate/directory'), $version.'.sql');
        if (file_exists($filename)) {
            $contents = FileSystem::Read($filename);
            $contents = Str::standardize_line_endings($contents);

            return $contents;
        }
        else {
            Log::war("File not found: `{$filename}`.");
            return false;
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