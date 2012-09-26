<?php namespace Avrelia\Core; if (!defined('AVRELIA')) die('Access is denied!');

/**
 * Language Base Class
 * -----------------------------------------------------------------------------
 * Language Class
 * Possible use:
 *
 * MY_KEY   My key
 * MY_LONG ----
 * Hello, this is rather long text, so it's written like that.
 * ----
 * MY_A_KEY My a key
 * ----
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Language
{
    # All translations
    protected static $dictionary = array();

    # List of loaded files (so that we don't load and parse a file twice)
    protected static $loaded     = array();

    # Default languages
    protected static $defaults   = array();

    public static function _on_include_()
        { self::set_defaults(Cfg::get('system/languages')); }

    /**
     * Will return language debug (info)
     * --
     * @return  array
     */
    public static function debug()
    {
        return
            "\nLoaded: \n".
            dump(self::$loaded, false, true).
            "\nDefaults: \n".
            dump(self::$defaults, false, true).
            "\nDictionary: \n".
            dump(self::$dictionary, false, true);
    }

    /**
     * Set list of default languages
     * --
     * @param   array   $defaults
     * @return  void
     */
    public static function set_defaults($defaults)
        { self::$defaults = $defaults; }

    /**
     * Will load particular language file
     * --
     * @param  string  $file  Following options:
     *     - enter short name: "my_lang", and the path will be calculated 
     *         automatically: APPPATH/languages/my_lang.lng
     *     - enter full path: SYSPATH.'/languages/my_lang.lng', to load full 
     *         path (must be with file extension! .lng)
     *     - enter % in filename, to auto set the language 
     *         based on languages list
     * @param  boolean $get_fist_default Get first default language found, 
     *     in chase if we can't find requested, for example:
     *     our request is, to get English or Russian, but none of them 
     *     can be found, but there is Ukrainian language available, in case 
     *     $get_fist_default the Ukrainian will be loaded
     * @return boolean
     */
    public static function load($file, $get_fist_default=false)
    {
        # Does it have % in it, meaning default languages?
        if (strpos($file, '%') !== false) 
        {
            $def_get_first_default = $get_fist_default;
            $get_fist_default      = false;

            foreach (self::$defaults as $k => $lng) 
            {
                $new_file = str_replace('%', $lng, $file);

                if ($k == (count(self::$defaults)-1)) 
                    { $get_fist_default = $def_get_first_default; }

                if (self::load($new_file, $get_fist_default)) 
                    { return true; }
            }
            return false;
        }

        # is full path or only filename
        if (substr($file,-4,4) !== '.lng') 
            { $file = app_path("languages/{$file}.lng"); }

        # Check if file was already loaded
        if (in_array($file, self::$loaded)) {
            Log::inf("File is already loaded, won't load it twice: `{$file}`.");
            return true;
        }
        else {
            self::$loaded[] = $file;
        }

        # Is valid path?
        if (!file_exists($file)) {
            if ($get_fist_default) {
                $dir   = dirname($file);
                $fileN = basename($file);
                $fileN = explode('.', $fileN, 2);
                $fileN = $fileN[0];
                $Files = scandir($dir);
                foreach ($Files as $fileName) {
                    if (substr($fileName, 0, strlen($fileN)) == $fileN) {
                        $fileNameNew = ds("{$dir}/{$fileName}");
                        if (self::load($fileNameNew)) {
                            $file = $fileNameNew;
                            break;
                        }
                    }
                }
                return false;
            }
            else {
                return false;
            }
        }

        $result = self::_process($file);

        if (is_array($result)) {
            self::$dictionary = array_merge(self::$dictionary, $result);
            Log::inf("Language loaded: `{$file}`");
            return true;
        }
    }

    /**
     * Will process particular file and return an array (of expressions)
     * --
     * @param   string  $filename
     * @return  array
     */
    protected static function _process($filename)
    {
        $file_contents = FileSystem::Read($filename);
        $file_contents = Str::standardize_line_endings($file_contents);

        # Remove comments
        $file_contents = preg_replace('/^#.*$/m', '', $file_contents);

        # Add end of file notation
        $file_contents = $file_contents . "\n__#EOF#__";

        $contents = '';
        if (Cfg::get('system/lang_n_to_br')) {
            $file_contents = str_replace('\n', '<br />', $file_contents);
        }
        preg_match_all(
            '/^!([A-Z0-9_]+):(.*?)(?=^![A-Z0-9_]+:|^#|^__#EOF#__$)/sm', 
            $file_contents, 
            $contents, 
            PREG_SET_ORDER);

        $result = array();

        foreach($contents as $options) {
            if (isset($options[1]) && isset($options[2])) {
                $result[trim($options[1])] = trim($options[2]);
            }
        }

        return $result;
    }

    /**
     * Will translate particular string
     * --
     * @param   string  $key
     * @param   array   $params
     * @return  string
     */
    public static function translate($key, $params=array())
    {
        if (isset(self::$dictionary[$key]))
        {
            $return = self::$dictionary[$key];

            # Check for any variables {1}, ...
            if ($params) {
                if (!is_array($params)) { $params = array($params); }

                foreach ($params as $key => $param) {
                    $key = $key + 1;
                    $return = preg_replace(
                                '/{'.$key.' ?(.*?)}/', 
                                str_replace('{?}', '$1', $param), 
                                $return);
                }
            }

            return $return;
        }
        else {
            Log::war("Language key not found: `{$key}`.");
            return $key;
        }
    }
}
