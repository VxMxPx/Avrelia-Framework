<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Core Functions
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2011-04-01
 */


/**
 * Function which is automatically called in case you are trying to use a class/interface
 * which hasn't been defined yet. By calling this function the scripting engine
 * is given a last chance to load the class before PHP fails with an error.
 * --
 * @param	string	$className
 * --
 * @return	void
 */
function __autoload($className)
{
	return Loader::Get($className);
}
//-

/**
 * Correct Directory Separators
 * --
 * @param	string	$path
 * --
 * @return	string
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
//-

/**
 * Output variable as: <pre> print_r($variable) </pre> (this is only for debuging)
 * --
 * @param	mixed	$variable
 * @param	boolean	$die		Do you wanna -stop- system after output?
 * @param	boolean	$return		Should function return or echo results?
 * --
 * @return	string
 */
function dumpVar($variable, $die=true, $return=false)
{
	if (is_bool($variable)) {
		$bool = $variable ? 'true' : 'false';
	}

	$result  = (!IN_CLI) ? "\n<pre>\n" : "\n";
	$result .= '' . gettype($variable) . (is_string($variable) ? '['.strlen($variable).']' : '') . ': ' . (is_bool($variable) ? $bool : print_r($variable, true));
	$result .= (!IN_CLI) ? "\n</pre>\n" : "\n";

	if ($return) {
		return $result;
	}
	else {
		echo $result;
	}

	if ($die) { die; }
}
//-

/**
 * Error handler
 * --
 * @param	integer	$errno
 * @param	string	$errmsg
 * @param	string	$filename
 * @param	integer	$linenum
 * --
 * @return	void
 */
function avreliaErrorHandler($errno, $errmsg, $filename, $linenum)
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
	$errorTypes = Cfg::Get('log/map');
	$type = isset($errorTypes[$errno]) ? $errorTypes[$errno] : 'WAR';

	# Add error
	Log::Add($errmsg, $type, $linenum, $filename);

	# Fatal error.
	if ($type === 'ERR')
	{
		# Write log to file (fatal)
		if (Cfg::Get('log/enabled') && Cfg::Get('log/write_all_on_fatal')) {
			Log::WriteAll(true);
		}

		# Dump whole log on fatal error.
		if (Cfg::Get('system/debug', false) && !IN_CLI) {
			die('<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Error</title></head><body> ' . Log::Get(2) . '</body></html>');
		}
	}
}
//-

/**
 * Generate full absolute URL
 * For example: http://my-site.dev/my-uri
 * --
 * @param	string	$uri
 * @param	boolean	$prefixZero	Will prefix zero element from uri: main/etc (prefix "main")
 * 								if zero element wasn't set, and you pass in string, then
 * 								that will be used.
 * --
 * @return	string
 */
function url($uri=null, $prefixZero=false)
{
	if ($prefixZero) {
		$zero = Input::Get(0, (is_string($prefixZero) ? $prefixZero : false));
		$uri = $zero . '/' . ltrim($uri, '\/');
	}
	$startUrl = Input::GetUrl();
	return $startUrl . ($uri ? trim($uri, '/') : '');
}
//-

/**
 * Generate full absolute URL
 * For example: http://my-site.dev/my-uri
 * And echo(!) the result
 * --
 * @param	string	$uri
 * @param	boolean	$prefixZero	Will prefix zero element from uri: main/etc (prefix "main")
 * 								if zero element wasn't set, and you pass in string, then
 * 								that will be used.
 * --
 * @return	void
 */
function urle($uri=null, $prefixZero=false)
{
	echo url($uri, $prefixZero);
}
//-

/**
 * This will build url (by replacing existing) from segments / actions.
 * --
 * @param	array	$Uri			Examples: array(0 => 'segment', 1 => 'segment1', 'action' => 'value')
 * @param	boolean	$updateCurrent	Will keep current uri's segments / actions and update them
 * --
 * @return	string
 */
function urlB($Uri, $updateCurrent=true)
{
	$uri = Input::BuildUri($Uri, $updateCurrent);
	return url($uri);
}
//-

/**
 * This will build url (by replacing existing) from segments / actions.
 * And echo(!) the result
 * --
 * @param	array	$Uri			Examples: array(0 => 'segment', 1 => 'segment1', 'action' => 'value')
 * @param	boolean	$updateCurrent	Will keep current uri's segments / actions and update them
 * --
 * @return	void
 */
function urlBe($Uri, $updateCurrent=true)
{
	echo urlB($Uri, $updateCurrent);
}
//-

/**
 * Language helper
 * --
 * @param	string	$string
 * @param	array	$Params
 * --
 * @return	string
 */
function l($string, $Params=array(), $languageKey='general')
{
	return Language::Translate($string, $Params, $languageKey);
}
//-

/**
 * Language helper
 * This will call l() function and echo(!) the result.
 * --
 * @param	string	$string
 * @param	array	$Params
 * --
 * @return	void
 */
function le($string, $Params=array(), $languageKey='general')
{
	echo l($string, $Params);
}
//-

/**
 * Make list (array) of links, to be used in translations.
 * Meaning, they are formatted like <a href="#url">{?}</a>
 * --
 * @return	array
 */
function lu()
{
	$Links = func_get_args();
	$List  = array();

	foreach ($Links as $link) {
		$link   = !empty($link) && strpos('://', $link) !== false ? $link : url($link);
		$List[] = '<a href="' . $link . '">{?}</a>';
	}

	return $List;
}
//-

/**
 * Make list (array) of HTML elements, to be used in translations.
 * Each element can be passed in zen-like formatt: span.dark || strong em
 * For links, with url use: a(uri//url).class#id strong.class em
 */
function lh()
{
	$Elements = func_get_args();
	$List     = array();

	foreach ($Elements as $element) {
		if (strpos($element, ' ') !== false) {
			$Element = explode(' ', $element);
		}
		else {
			$Element = array($element);
		}

		$closeTags = '';
		$openTags  = '';

		foreach ($Element as $tag) {
			# Reset to empty
			$url  = $class = $id = null;

			preg_match('/\((.*?)\)/',         $tag, $url);
			$tag = preg_replace('/\((.*?)\)/', '', $tag);
			preg_match_all('/\.([a-zA-Z0-9_]*)/', $tag, $class);
			preg_match('/\#([a-zA-Z0-9_]*)/', $tag, $id);
			preg_match('/^([a-zA-Z]*)/',      $tag, $tag);

			$openTags .= '<' . $tag[1];

			if (!empty($url)) {
				$url = $url[1];
				$url = strpos('://', $url) !== false ? $url : url($url);
				$openTags .= ' href="' . $url . '"';
			}

			if (!empty($class[1])) {
				$openTags .= ' class="' . implode(' ', $class[1]) . '"';
			}

			if (!empty($id)) {
				$openTags .= ' id="' . $id[1] . '"';
			}

			$openTags .= '>';
			$closeTags = "</{$tag[1]}>" . $closeTags;
		}

		$List[] = $openTags . '{?}' . $closeTags;
	}

	return $List;
}
//-

/**
 * Will Init The Cli
 * --
 * @return	void
 */
function initCli()
{
	if (PHP_SAPI !== 'cli') {
		trigger_error("Must be run from console.", E_USER_ERROR);
	}

	# Load Framework Init
	if (file_exists(SYSPATH . '/core/avrelia.php')) {
		include(SYSPATH . '/core/avrelia.php');
	}
	else {
		trigger_error("Can't load `avrelia.php` file.", E_USER_ERROR);
	}

	if (!class_exists('Avrelia', false)) {
		die('Class not found: `Avrelia`.');
	}

	# We should have Avrelia now...
	$Avrelia = new Avrelia();
	$Avrelia->init();

	# Main cli loop! ===============================================================

	# Load Avrelia CLI
	if (file_exists(SYSPATH . '/core/avrelia_cli.php')) {
		include(SYSPATH . '/core/avrelia_cli.php');
	}
	else {
		trigger_error("Can't load `avrelia_cli.php` file.", E_USER_ERROR);
	}

	$CLI = new AvreliaCli($_SERVER['argv']);
}
//-

/**
 * Replace the last occurrence of a string.
 * --
 * @param	string	$search
 * @param	string	$replace
 * @param	string	$subject
 * --
 * @return	string
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
//-

/**
 * Convert to camel case
 * --
 * @param	string	$string
 * @param	boolean	$ucFirst	Upper case first letter also?
 * --
 * @return	string
 */
function toCamelCase($string, $ucFirst=true)
{
	$string = str_replace('_', ' ', $string);
	$string = ucwords($string);
	$string = str_replace(' ', '', $string);

	if (!$ucFirst) {
		$string = lcfirst($string);
	}

	return $string;
}
//-

/**
 * Convert camel case to underlines
 * --
 * @param	string	$string
 * --
 * @return	string
 */
function toUnderline($string)
{
	preg_match_all('/[A-Z]*[^A-Z]*/', $string, $Result);
	return trim(strtolower(implode('_', $Result[0])), '_');
}
//-
