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
 * Determine if this is command line interface.
 * --
 * @return boolean
 */
function is_cli() { return php_sapi_name() === 'cli' || defined('STDIN'); }

/**
 * Correct Directory Separators
 * --
 * @return string
 */
function ds()
{
    $path = func_get_args();
    $path = implode(DIRECTORY_SEPARATOR, $path);

    if ($path) {
        return preg_replace('/[\/\\\\]+/', DIRECTORY_SEPARATOR, $path);
    }
    else {
        return null;
    }
}

/**
 * Return full absolute system, application, public, database, plugs path!
 * --
 * @param  string $path Leave empty to get only SYSPATH
 * @return string
 */
function sys_path($path=null) { return ds(SYSPATH.'/'.$path); }
function app_path($path=null) { return ds(APPPATH.'/'.$path); }
function pub_path($path=null) { return ds(PUBPATH.'/'.$path); }
function dat_path($path=null) { return ds(DATPATH.'/'.$path); }
function plg_path($path=null) { return ds(PLGPATH.'/'.$path); }

/**
 * Output variable as: <pre>print_r($variable)</pre> (this is only for debuging)
 * This will die after dumpign variables on screen.
 */
function dump()
{
    die(call_user_func_array('dump_r', func_get_args()));
}

/**
 * Dump, but don't die - return results instead.
 * --
 * @return string
 */
function dump_r()
{
    $arguments = func_get_args();
    $result = '';

    foreach ($arguments as $variable) 
    {
        if (is_bool($variable)) {
            $bool = $variable ? 'true' : 'false';
        }
        else {
            $bool = false;
        }

        $result .= (!is_cli()) ? "\n<pre>\n" : "\n";
        $result .= '' . gettype($variable);
        $result .= (is_string($variable) ? '['.strlen($variable).']' : '');
        $result .=  ': ' . (is_bool($variable) ? $bool : print_r($variable, true));
        $result .= (!is_cli()) ? "\n</pre>\n" : "\n";
    }

    return $result;
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
    static $template = <<<TEMPLATE
<!DOCTYPE html>
<html lang="en">
<meta charset=utf-8>
<title>Error .at AvreliaFramework</title>
<style>
*       { padding: 0; margin: 0; line-height: 1.5em; }
::selection      { background-color: #47c; color: #eee; }
::-moz-selection { background-color: #47c; color: #eee; }
body    { background-color: #111; color: #bbb; font-size: 16px; font-family: "Sans", sans-serif; }
h1, h2  { font-family: "Serif", serif; font-weight: normal; }
h2      { padding-top: 30px; padding-bottom: 4px; margin-bottom: 4px; border-bottom: 1px dotted #ddd; }
a       { color: #47c; padding: 2px; }
a:hover { background-color: #47c; color: #fff; text-decoration: none; border-radius: 4px; }
code    { font-family: "Monospace", monospace; background-color: #f2f2f2; color: #224; }
.fade   { color: #555; font-style: italic; }
#page   { width: 800px; margin: 20px auto; padding: 20px; }
#log    { padding-top: 10px; margin-top: 5px; }
#log > div { box-shadow: 0 0 8px #060606; border-radius: 4px; }
#log > div > div:first-child { border-radius: 4px 4px 0 0; }
#log > div > div:last-child  { border-radius: 0 0 4px 4px; border-bottom: none !important; }
</style>
<div id=page>
    <h1>Something went wrong, <small class=fade>here's what happened:</small></h1>
    <div id=log>
        {{error_report}}
TEMPLATE;

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

    # Get error simple type, if we have cfg already...
    if (class_exists('Cfg', false)) {
        $errorTypes = Cfg::get('log/map');
        $type = isset($errorTypes[$errno]) ? $errorTypes[$errno] : 'war';
    }
    else 
        { $type = 'err'; }

    # Please note: At this point, we have Log class for sure, as error handler
    # is set after all core classes were loaded.
    if (class_exists('Log', false)) {
        Log::add($errmsg, $type);
    }

    # Fatal error.
    if ($type === 'err')
    {
        # Write log to file (fatal)
        if (class_exists('Cfg', false) && class_exists('Log', false)) {
            if (Cfg::get('log/enabled') && Cfg::get('log/write_all_on_fatal')) {
                Log::save_all(true);
            }
        }

        # Dump whole log on fatal error.
        if (class_exists('Log', false)) {
            if (is_cli()) 
                { $error_report = Log::as_string('war', 'err'); }
            elseif (DEBUG && !is_cli()) 
                { $error_report = Log::as_html(); }
        } 
        else 
            { $error_report = $errmsg; }

        if (is_cli()) {
            die($error_report);
        }
        else {
            die(str_replace(
                array('{{error_report}}', '{{error_no}}'), 
                array($error_report, $errno), 
                $template));
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
function l($string, $params=array())
{ 
    return Language::translate($string, $params); 
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
 * For links, with url use: a(uri or url).class#id strong.class em
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
 * The same as substr for string, only that work with paths. 
 * Note, the path returned is without end slash.
 * Examples:
 *     $path = /home/user/data/book.odt
 *         ($path, 0, 2) => /home/user
 *         ($path, -1) => book.odt
 *         ($path, 2) => data/book.odt
 *         ($path, -3, 1) => user
 *         ($path, 0, -1) => /home/user/data
 * @param  string  $path
 * @param  integer $start
 * @param  integer $length
 * @return string
 */
function get_path_segment($path, $start, $length=null)
{
    if (!$path) { return false; }
    $ds = DIRECTORY_SEPARATOR;
    $path = rtrim(ds($path), $ds);
    $path_particles = explode($ds, $path);
    $particles_count = count($path_particles) - 1;

    if (empty($path_particles[0])) { unset($path_particles[0]); }

    $fixed_particles = array_slice($path_particles, $start, $length);
    $final_path = implode($ds, $fixed_particles);

    # Calculate if we've got first particle
    if ($start < 0) {
        if (($particles_count + $start) < 1) {
            $start = 0;
        }
    }

    if ($start === 0 && substr($path, 0, 1) === $ds) {
        $final_path = $ds . $final_path;
    }

    return $final_path;
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

    # Convert _
    if (strpos($string, '_') !== false) {
        $string = str_replace('_', ' ', $string);
        $string = ucwords($string);
        $string = str_replace(' ', '', $string);
    }

    # Convert backslashes
    if (strpos($string, CHAR_BACKSLASH) !== false) {
        $string = str_replace(CHAR_BACKSLASH, ' ', $string);
        $string = ucwords($string);
        $string = str_replace(' ', CHAR_BACKSLASH, $string);
    }

    # Convert slashes
    if (strpos($string, CHAR_SLASH) !== false) {
        $string = str_replace(CHAR_SLASH, ' ', $string);
        $string = ucwords($string);
        $string = str_replace(' ', CHAR_SLASH, $string);
    }

    if (!$uc_first) {
        $string = lcfirst($string);
    }
    else {
        $string = ucfirst($string);
    }

    return $string;
}

/**
 * Convert camel case to underscores
 * --
 * @param  string  $string
 * @return string
 */
function to_underscore($string)
{
    preg_match_all('/[A-Z]*[^A-Z]*/', $string, $result);
    return trim(strtolower(implode('_', $result[0])), '_');
}
