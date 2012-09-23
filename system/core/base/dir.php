<?php namespace Avrelia\Core; if (!defined('AVRELIA')) die('Access is denied!');

/**
 * Dir Base Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Dir
{
    # Default mode when creating new directory
    protected $mode = null;


    public static function _on_include_()
    {
        self::set_mode(Cfg::get('system/fs_default_mode', 0755));
    }

    /**
     * Set default mode for newly created directories
     * @param integer $mode octal number, - leading zero!
     */
    public static function set_mode($mode)
    {
        self::$mode = $mode;
    }

    /**
     * Select particular directory.
     * @param  string       $dir  Full path to the directory.
     * @return DirSelected
     */
    public static function select($dir)
    {
        return new DirSelected($dir);
    }

    /**
     * Create a new directory.
     * @param  string  $dir        Full path to the the directory.
     * @param  integer $on_exists  FILE_* action if directory exists.
     * @return DirSelected or Exception
     */
    public static function create($dir, $on_exists=FILE_DUPLICATE_SILENT)
    {
        # Get path without directory
        $dir = ds($dir);
        $in_path = get_path_segment($dir, 0, -1);
        $new_dir_name = get_path_segment($dir, -1, 1);

        Log::inf("About to create directory: `{$dir}`.");

        # We obviously need to have parent directory
        if (!is_dir($in_path)) {
            throw new \Avrelia\Exception\FileSystem("Parent directory not found!", 10);
        }

        # Need to be writeable
        if (!self::is_writable($in_path)) {
            throw new \Avrelia\Exception\FileSystem("Parent directory is not writeable.", 11);
        }

        # If we came so far, lets try to create this thing now
        try {
            return self::create_tree($dir, $on_exists);
        }
        catch (\Avrelia\Exception\FileSystem $e) {
            throw $e;
        }
    }

    /**
     * Create directory tree, - you can pass in an array of direcotories to be
     * created, examples: 
     * ['dir1', 'dir2', 'dir3']
     * @param  mixed   $directories array or string
     * @param  integer $on_exists   FILE_* action if directory exists.
     * @return array   List of objects, for each created directory one
     */
    public static function create_tree($directories, $on_exists=FILE_DUPLICATE_SILENT)
    {
        # Do we have an array of directories?
        if (is_array($directories)) {
            $collection = array();

            foreach ($directories as $dir) {
                try {
                    $collection[] = self::create_tree($dir, $on_exists);
                }
                catch (\Avrelia\Exception\FileSystem $e) {
                    $collection[] = $e;
                }
            }

            return $collection;
        }
        else {
            # Get path without directory
            $dir = ds($directories);
            $in_path = get_path_segment($dir, 0, -1);
            $new_dir_name = get_path_segment($dir, -1, 1);

            # Does it exists already?
            if (is_dir($dir)) {
                Log::inf("Directory already exists...");
                if ($on_exists === FILE_DUPLICATE_SILENT) {
                    # If we can be silent in case of file exists, then that's it!
                    Log::inf("...passing throug silently.");
                    return new DirSelected($dir);
                }
                elseif ($on_exists === FILE_DUPLICATE_REWRITE) {
                    # Will drop original and create our resource
                    Log::inf("...will try to rewrite it.");
                    try {
                        self::delete($dir);
                    }
                    catch (\Avrelia\Exception\FileSystem $e) {
                        throw $e;
                    }
                }
                elseif ($on_exists === FILE_DUPLICATE_UNIQUE) {
                    $new_dir_name = File::unique_name($new_dir_name, $in_path);
                    Log::inf("...making new unique name for it: `{$new_dir_name}`.");
                }
                elseif ($on_exists === FILE_DUPLICATE_EXCEPTION) {
                    Log::inf("...throwing an exception.");
                    throw new \Avrelia\Exception\FileSystem(
                        "Directory already exists.", 12);
                }
            }

            Log::inf("Creating directory: `{$dir}`.");

            # Try to actually create directory
            if (mkdir($dir, self::$mode, true)) {
                return new DirSelected($dir);
            }
            else {
                throw new \Avrelia\Exception\FileSystem(
                    "Failed to create directory!", 30);
            }
        }
    }

    /**
     * Do we have permission to write?
     * @param  string  $path
     * @return boolean
     */
    public static function is_writable($path)
    {
        $path = ds($path);

        Log::inf("Check if directory is writable: `{$path}`.");

        # Check If Provided Path Is Valid
        if (!is_dir($path)) {
            throw new \Avrelia\Exception\FileSystem(
                "Not a valid directory: `{$path}`.", 20);
        }

        # Default function - if returns false, then we know it isn't writable...
        if (!is_writable($path)) { return false; }

        # In other case, we'll check, by trying create an path.
        do {
            # Must be unique directory for testing...
            $dir = ds($path.'/.avrelia_framework_test_'.rand(0,200).time());
        }
        while(!is_dir($path));

        # Now, try to create it, check if exists, delete it and check if 
        # doesn't exists anymore.
        return
            @mkdir($dir) 
            and is_dir($dir) 
            and @rmdir($dir) 
            and !is_dir($dir)
            ? true
            : false;
    }

    /**
     * Remove directory and all sub/directories / files in it.
     * @param  string  $path
     * @return integer Amount of deleted files / folders
     */
    public static function delete($path)
    {
        # Local copy of ignore list
        $fs_ignore = Cfg::get('system/fs_ignore', array());

        # If we don't have filter we must remove just one file / folder
        if (is_dir($path)) {
            $files = scandir($path);
            $count = 0;
            foreach ($files as $file) {
                # Can't be dot and can't be on ignore list
                if  (
                    $file === '.'
                    or $file === '...'
                    or in_array($file, $fs_ignore)
                ) { continue; }

                $new_path = ds($path . '/' . $file);
                $count = $count + self::delete($new_path);
            }
            Log::inf("I'm about to delete directory: `{$path}`.");
            $count = $count + rmdir($path);
            return $count;
        }
        else {
            return File::delete($path);
        }
    }

    /**
     * Is particular directory empty (contains no files or folders)
     * @param  string  $path
     * @return boolean
     */
    public static function is_empty($path)
    {
        $path = realpath($path);

        # Actually, doesn't exists, so we throw null back, as "no results"
        if (!is_dir($path)) { return null; }

        $contents = scandir($path);
        $contents = implode('', $contents);

        return $contents === '...';
    }

    /**
     * Size of whole directory, all files and sub directories included.
     * Vale returned is presented in bytes.
     * @param  string $path
     * @return integer
     */
    public static function size($path)
    {
        $path = realpath($path);
        $size = 0;

        if (is_dir($path)) {
            $contents = scandir($path);

            foreach ($contents as $file) {
                if ($file === '.' || $file === '..') { continue; }
                $size = $size + self::size(ds($path.'/'.$file));
            }
        }
        else {
            $size = filesize($path);
        }
        return $size;
    }
}

/**
 * Dir Selected Base Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class DirSelected_Base
{
    # Currently selected directory
    protected $selected = null;

    public function __construct($path)
    {
        if (!is_dir($path)) {
            throw new \Avrelia\Exception\FileSystem(
                "The directory `{$path}` doesn't exists!", 10);
        }

        $this->selected = $path;
    }
}