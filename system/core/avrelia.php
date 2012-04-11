<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Init Framework
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2011-04-01
 */
class Avrelia
{
	const VERSION   = '0.80';
	const NAME      = 'Avrelia Framework';
	const AUTHOR    = 'Avrelia';
	const FOUNDER   = 'Marko Gajšt';
	const WEBSITE   = 'http://framework.avrelia.com';
	const COPYRIGHT = '2010';

	/**
	 * @var	array	Items which needs to be autoloaded...
	 */
	private $Load = array(
		'cfg', 'http', 'model', 'v_array', 'event', 'input', 'output',
		'v_boolean', 'benchmark', 'file_system', 'language', 'plug', 'view',
		'cache', 'log', 'util', 'v_string',
	);


	/**
	 * Will init the framework
	 * --
	 * @return	void
	 */
	public function init()
	{
		# Check If Is 5.0 Or More...
		if (!defined('PHP_VERSION') || ((float) PHP_VERSION < 5)) {
			trigger_error('You need PHP version 5.0 or more.', E_USER_ERROR);
		}

		# Magic Quotes To Off
		if ((float)PHP_VERSION < 5.3) {
			set_magic_quotes_runtime(0);
			ini_set('magic_quotes_gpc', 0);
			ini_set('magic_quotes_sybase', 0);
		}

		# Backward Compatibility
		if (!defined('E_RECOVERABLE_ERROR')) define('E_RECOVERABLE_ERROR', 4096); # 5.2.0
		if (!defined('E_DEPRECATED'))        define('E_DEPRECATED',        8192); # 5.3.0
		if (!defined('E_USER_DEPRECATED'))   define('E_USER_DEPRECATED',  16384); # 5.3.0

		# Load Core Functions
		if (!function_exists('avreliaErrorHandler')) {
			include(realpath(SYSPATH . '/core/functions.php'));
		}

		# Load all level 1 files
		foreach($this->Load as $l1Load) {
			if (file_exists(ds(SYSPATH.'/core/'.$l1Load.'.php'))) {
				include(ds(SYSPATH.'/core/'.$l1Load.'.php'));
			}
			else {
				trigger_error("Can't load core file: " . ds(SYSPATH.'/core/'.$l1Load.'.php'), E_USER_ERROR);
			}
		}

		# Init Config
		Cfg::Load(ds(SYSPATH . '/config/main.php'));
		Cfg::Load(ds(APPPATH . '/config/main.php'));

		# Reset Error Reporting...
		ini_set('display_errors', Cfg::Get('system/debug'));

		# Default timezone
		date_default_timezone_set(Cfg::Get('system/timezone', 'GMT'));

		# Set Timer...
		Benchmark::SetTimer('System');

		# Init The Log
		Log::Init();

		# First Log Entry...
		Log::Add('INF', 'PHP version: ' . PHP_VERSION . ' | Framework version: ' . self::VERSION, __LINE__, __FILE__);

		# Error Handling
		set_error_handler("avreliaErrorHandler");

		# Trigger event after framework initialization
		Event::Trigger('avrelia.after.init');

		return $this;
	}
	//-

	/**
	 * Will boot the system
	 * --
	 * @return	void
	 */
	public function boot()
	{
		# We will decide where to go from here...
		Event::Trigger('avrelia.before.boot');

		# Init the input
		Input::Init();

		# Set default language
		Language::SetDefaults(Cfg::Get('system/languages'));

		# Is application offline?
		if (Cfg::Get('system/offline') === true) {
			$message = Cfg::Get('system/offline_message');
			if (substr($message,0,5) == 'view:') {
				$message = View::Get(substr($message,5))->doReturn();
			}
			HTTP::Status503_ServiceUnavailable($message);
		}

		# MUST be called before we can use Plugs.
		Plug::Init(Cfg::Get('plug/enabled'));

		# Now scan and autoload plugs
		if (Cfg::Get('plug/enabled')) {
			Plug::Inc(Cfg::Get('plug/auto_load'));
		}

		$requestUri = trim(Input::GetRequestUri(false), '/');
		$routeCall  = '';
		$found      = false;

		# Do we have before?
		if (Cfg::Get('system/routes/before', false)) {
			if (!$this->routeCall(Cfg::Get('system/routes/before'))) {
				Log::Add('WAR', "Before is set in config, but can't find method: `".Cfg::Get('system/routes/before', false)."`.", __LINE__, __FILE__);
			}
		}

		# In case we have no uri
		if (empty($requestUri)) {
			if (Cfg::Get('system/routes/0')) {
				$this->routeCall(Cfg::Get('system/routes/0'));
				$found = true;;
			}
		}
		else
		{
			# Loop to check for uri
			$Routes = Cfg::Get('system/routes');
			unset($Routes[0], $Routes[404], $Routes['before'], $Routes['after']);

			foreach($Routes as $routeRegEx => $routeCall) {
				$patterns = '';
				if (preg_match_all($routeRegEx, $requestUri, $patterns, PREG_SET_ORDER)) {
					$Patterns = $patterns[0];
					unset($Patterns[0]);

					# Call route...
					$found = $this->routeCall($routeCall, $Patterns);
					break;
				}
			}

		}

		# Call 404 if we have false
		if (!$found) {
			HTTP::Status404_NotFound();
			Log::Add('INF', "We have 404 on `{$requestUri}`.", __LINE__, __FILE__);

			if (Cfg::Get('system/routes/404')) {
				$found = $this->routeCall(Cfg::Get('system/routes/404'));
			}

			# Still not found?
			if (!$found) {
				echo '404: ' . $requestUri;
			}
		}

		# Do we have after?
		if (Cfg::Get('system/routes/after', false)) {
			if (!$this->routeCall(Cfg::Get('system/routes/after'))) {
				Log::Add('WAR', "After is set in config, but can't find method: `".Cfg::Get('system/routes/after', false)."`.", __LINE__, __FILE__);
			}
		}

		Event::Trigger('avrelia.after.boot');
	}
	//-

	/**
	 * This will resolve route call (example: /controller->method(params))
	 * --
	 * @param	string	$callName
	 * @param	array	$Patterns
	 * --
	 * @return	void
	 */
	private function routeCall($callName, $Patterns=false)
	{
		# Safely Escape Route
		$callName = str_replace(
						array('/', '->', '(', ',', ')'),
						array(' {#!<<PATH!#} ', ' {#!<<CONTROLLER!#} ', ' {#!<<METHOD!#} ', ' {#!<<PARAM!#} ', ''),
						$callName
					);

		# Set patterns
		if ($Patterns && is_array($Patterns)) {
			foreach($Patterns as $key => $pat) {
				$callName = str_replace('%'.$key, $pat, $callName);
			}
		}

		# Get Path
		$Params   = array_reverse(explode(' {#!<<PATH!#} ', $callName, 2), false);
		$path     = isset($Params[1]) ? vString::Clean(strtolower($Params[1]), 100, 'a 1 c', '_') : '';
		//$path     = str_replace('-', '_', $path);
		$callName = $Params[0];

		# Get Controller
		$Params      = array_reverse(explode(' {#!<<CONTROLLER!#} ', $callName, 2), false);
		$controller  = isset($Params[1]) ? vString::Clean(strtolower($Params[1]), 100, 'a 1 c', '_') : '';
		//$controller  = str_replace('-', '_', $controller);
		$callName    = $Params[0];

		# Method
		$Params   = array_reverse(explode(' {#!<<METHOD!#} ', $callName, 2), false);
		$method   = isset($Params[1]) ? vString::Clean($Params[1], 100, 'a A 1 c', '_-') : false;
		$method   = str_replace('-', '_', $method);
		$callName = $Params[0];

		# Params
		$Params   = explode(' {#!<<PARAM!#} ', $callName);
		vArray::Trim($Params);

		if (!class_exists($controller.'Controller', false)) {
			$includePath = ds(APPPATH.'/controllers/'.str_replace('.', '', $path).'/'.str_replace('.', '', $controller).'.php');
			if (file_exists($includePath)) {
				include($includePath);
			}
		}

		if (!class_exists($controller.'Controller', false)) {
			return false;
		}

		# Create new controller
		$controller = $controller . 'Controller';
		$Controller = new $controller();

		# Call the function if exists
		if (method_exists($Controller, $method)) {
			call_user_func_array(array($Controller, $method), $Params);
			return true;
		}
		else {
			return false;
		}
	}
	//-

	/**
	 * Say your prayers, you're going down after this.
	 * This is the last method after execution. It's even after boot, routing, etc...
	 * --
	 * @return	void
	 */
	public function __destruct()
	{
		# Final event
		Event::Trigger('avrelia.before.destruct');

		# Write log (fatal)
		if (Cfg::Get('log/enabled') && Cfg::Get('log/write_individual') === false) {
			Log::WriteAll(false);
		}
	}
	//-
}
//--
