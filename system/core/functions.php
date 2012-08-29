<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * General Functions
 * -----------------------------------------------------------------------------
 * Those are used on intialization / on basic levels of system.
 * ----
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */

/**
 * Function which is automatically called in case you are trying to use 
 * a class/interface which hasn't been defined yet. 
 * By calling this function the scripting engine is given a last chance to load 
 * the class before PHP fails with an error.
 * In our case all hard work is preformed by Loader.
 * --
 * @param  string $className
 * @return void
 */
function __autoload($className) { return Loader::Get($className); }

/**
 * Determine if this is command line interface.
 * --
 * @return boolean
 */
function is_cli() { return php_sapi_name() === 'cli'; }

/**
 * Correct Directory Separators
 * --
 * @param  string  $path
 * @return string
 */
function ds($path)
{
    if ($path) {
        return preg_replace('/[\/\\\\]+/', DIRECTORY_SEPARATOR, $path);
    }
    else {
        return null;
    }
}

/**
 * Return full absolute system, application, public, database path!
 * --
 * @param  string $path Leave empty to get only SYSPATH
 * @return string
 */
function sys_path($path=null) { return ds(SYSPATH.'/'.$path); }
function app_path($path=null) { return ds(APPPATH.'/'.$path); }
function pub_path($path=null) { return ds(PUBPATH.'/'.$path); }
function dat_path($path=null) { return ds(DATPATH.'/'.$path); }

/**
 * Output variable as: <pre>print_r($variable)</pre> (this is only for debuging)
 * --
 * @param  mixed   $variable
 * @param  boolean $die        Do you wanna -stop- system after output?
 * @param  boolean $return     Should function return or echo results?
 * @return string
 */
function dump($variable, $die=true, $return=false)
{
    if (is_bool($variable)) {
        $bool = $variable ? 'true' : 'false';
    }

    $result  = (!is_cli()) ? "\n<pre>\n" : "\n";
    $result .= '' . gettype($variable);
    $result .= (is_string($variable) ? '['.strlen($variable).']' : '');
    $result .=  ': ' . (is_bool($variable) ? $bool : print_r($variable, true));
    $result .= (!is_cli()) ? "\n</pre>\n" : "\n";

    if ($return)
        { return $result; }
    else
        { echo $result; }

    if ($die) { die; }
}

/**
 * Error handler
 * --
 * @param  integer $errno
 * @param  string  $errmsg
 * @param  string  $filename
 * @param  integer $linenum
 * @return void
 */
function avrelia_error_handler($errno, $errmsg, $filename, $linenum)
{
    # Error codes to plain English string.
    $errorToTitle = array
    (
        E_ERROR              => 'Error',
        E_WARNING            => 'Warning',
        E_PARSE              => 'Parsing Error',
        E_NOTICE             => 'Notice',
        E_CORE_ERROR         => 'Core Error',
        E_CORE_WARNING       => 'Core Warning',
        E_COMPILE_ERROR      => 'Compile Error',
        E_COMPILE_WARNING    => 'Compile Warning',
        E_USER_ERROR         => 'User Error',
        E_USER_WARNING       => 'User Warning',
        E_USER_NOTICE        => 'User Notice',
        E_STRICT             => 'Runtime Notice',
        E_RECOVERABLE_ERROR  => 'Catchable Fatal Error',
        E_DEPRECATED         => 'Run-time notice',
        E_USER_DEPRECATED    => 'User-generated warning message',
    );

    # Get error title
    $title = isset($errorToTitle[$errno]) ? $errorToTitle[$errno] : 'Unknown';
    $errmsg = $title . ":\n" . $errmsg;

    # Get error simple type
    $errorTypes = Cfg::get('log/map');
    $type = isset($errorTypes[$errno]) ? $errorTypes[$errno] : 'war';

    # Please note: At this point, we have Log class for sure, as error handler
    # is set after all core classes were loaded.
    Log::add($errmsg, $type);

    # Fatal error.
    if ($type === 'err')
    {
        # Write log to file (fatal)
        if (Cfg::get('log/enabled') && Cfg::get('log/write_all_on_fatal')) {
            Log::save_all(true);
        }

        # Dump whole log on fatal error.
        if (DEBUG && !is_cli()) {
            die('<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Error</title></head><body> ' . Log::as_html() . '</body></html>');
        }
    }
}

/**
 * Generate full URL
 * For example: http://my-site.dev/my-uri
 * --
 * @param  string  $uri
 * @param  boolean $prefix_zero Will prefix zero element from uri: main/etc (prefix "main")
 *                              if zero element wasn't set, and you pass in string, then
 *                              that will be used.
 * @return string
 */
function url($uri=null, $prefix_zero=false)
{
    if ($prefix_zero) {
        $zero = Input::get(0, (is_string($prefix_zero) ? $prefix_zero : false));
        $uri  = $zero . '/' . ltrim($uri, '\/');
    }
    $startUrl = Input::get_url();
    return $startUrl . ($uri ? trim($uri, '/') : '');
}

/**
 * Generate full URL and ECHO the result!
 * For example: http://my-site.dev/my-uri
 * --
 * @param  string  $uri
 * @param  boolean $prefix_zero Will prefix zero element from uri: main/etc (prefix "main")
 *                              if zero element wasn't set, and you pass in string, then
 *                              that will be used.
 * @return void
 */
function urle($uri=null, $prefix_zero=false) { echo url($uri, $prefix_zero); }

/**
 * Build url (by replacing existing) from segments / actions.
 * --
 * @param  array   $uri             Examples: array(0 => 'segment', 1 => 'segment1', 'action' => 'value')
 * @param  boolean $update_current  Will keep current uri's segments / actions and update them
 * @return string
 */
function url_b($uri, $update_current=true)
{
    $uri = Input::build_uri($uri, $update_current);
    return url($uri);
}

/**
 * Build url (by replacing existing) from segments / actions, 
 * and echo the result.
 * --
 * @param  array   $uri            Examples: array(0 => 'segment', 1 => 'segment1', 'action' => 'value')
 * @param  boolean $update_current  Will keep current uri's segments / actions and update them
 * @return void
 */
function url_be($uri, $update_current=true) { echo urlB($uri, $update_current); }

/**
 * Language helper
 * --
 * @param  string  $string
 * @param  array   $params
 * @return string
 */
function l($string, $params=array(), $language_key='general')
{ 
    return Language::Translate($string, $params, $language_key); 
}

/**
 * Language helper
 * This will call l() function and echo(!) the result.
 * --
 * @param  string  $string
 * @param  array   $params
 * @return void
 */
function le($string, $params=array(), $language_key='general')
{
    echo l($string, $params, $language_key);
}

/**
 * Make list (array) of links, to be used in translations.
 * Meaning, they are formatted like <a href="#url">{?}</a>
 * --
 * @return array
 */
function lu()
{
    $links = func_get_args();
    $list  = array();

    foreach ($links as $link) {
        $link   = !empty($link) && strpos('://', $link) !== false ? $link : url($link);
        $list[] = '<a href="' . $link . '">{?}</a>';
    }

    return $list;
}

/**
 * Make list (array) of HTML elements, to be used in translations.
 * Each element can be passed in zen-like formatt: span.dark || strong em
 * For links, with url use: a(uri//url).class#id strong.class em
 */
function lh()
{
    $elements = func_get_args();
    $list     = array();

    foreach ($elements as $element)
    {
        if (strpos($element, ' ') !== false)
            { $element_array = explode(' ', $element); }
        else
            { $element_array = array($element); }

        $close_tags = '';
        $open_tags  = '';

        foreach ($element_array as $tag) 
        {
            # Reset to empty
            $url  = $class = $id = null;

            preg_match('/\((.*?)\)/',         $tag, $url);
            $tag = preg_replace('/\((.*?)\)/', '', $tag);
            preg_match_all('/\.([a-zA-Z0-9_]*)/', $tag, $class);
            preg_match('/\#([a-zA-Z0-9_]*)/', $tag, $id);
            preg_match('/^([a-zA-Z]*)/',      $tag, $tag);

            $open_tags .= '<' . $tag[1];

            if (!empty($url)) {
                $url = $url[1];
                $url = strpos('://', $url) !== false ? $url : url($url);
                $open_tags .= ' href="' . $url . '"';
            }

            if (!empty($class[1])) {
                $open_tags .= ' class="' . implode(' ', $class[1]) . '"';
            }

            if (!empty($id)) {
                $open_tags .= ' id="' . $id[1] . '"';
            }

            $open_tags .= '>';
            $close_tags = "</{$tag[1]}>" . $close_tags;
        }

        $list[] = $open_tags . '{?}' . $close_tags;
    }

    return $list;
}


/**
 * Replace the last occurrence of a string.
 * --
 * @param  string  $search
 * @param  string  $replace
 * @param  string  $subject
 * @return string
 */
function str_lreplace($search, $replace, $subject)
{
    # Find position for string
    $pos = strrpos($subject, $search);

    # If we didn't found anything to replace, then we won't do it...
    if ($pos === false) {
        return $subject;
    }
    else {
        return substr_replace($subject, $replace, $pos, strlen($search));
    }
}

/**
 * Convert to camel case
 * --
 * @param  string  $string
 * @param  boolean $uc_first  Upper case first letter also?
 * @return string
 */
function to_camelcase($string, $uc_first=true)
{
    $string = str_replace('_', ' ', $string);
    $string = ucwords($string);
    $string = str_replace(' ', '', $string);

    if (!$uc_first) {
        $string = lcfirst($string);
    }

    return $string;
}

/**
 * Convert camel case to underlines
 * --
 * @param  string  $string
 * @return string
 */
function to_underline($string)
{
    preg_match_all('/[A-Z]*[^A-Z]*/', $string, $result);
    return trim(strtolower(implode('_', $result[0])), '_');
}
