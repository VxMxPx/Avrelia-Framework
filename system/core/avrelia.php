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
	const VERSION   = '1.00';
	const NAME      = 'Avrelia Framework';
	const AUTHOR    = 'Avrelia';
	const FOUNDER   = 'Marko Gajšt';
	const WEBSITE   = 'http://framework.avrelia.com';
	const COPYRIGHT = '2010-2012';

	/**
	 * @var	array	Items which needs to be autoloaded...
	 */
	private $Load = array(
		'cfg', 'http', 'model', 'v_array', 'dispatcher', 'event', 'input', 'output',
		'v_boolean', 'benchmark', 'file_system', 'language', 'loader', 'plug', 'view',
		'log', 'util', 'v_string',
	);


	/**
	 * Will init the framework
	 * --
	 * @return	Dispatcher
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

		# Load both, default system's and application's config
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
		Log::Add('PHP version: ' . PHP_VERSION . ' | Framework version: ' . self::VERSION, 'INF');

		# Error Handling
		set_error_handler('avreliaErrorHandler');

		# Init the input
		Input::Init();

		# Set default language
		Language::SetDefaults(Cfg::Get('system/languages'));

		# MUST be called before we can use Plugs.
		Plug::Init(Cfg::Get('plug/enabled'));

		# Now scan and autoload plugs
		if (Cfg::Get('plug/enabled')) {
			Plug::Inc(Cfg::Get('plug/auto_load'));
		}

		# Trigger event after framework initialization
		Event::Trigger('avrelia.after.init');

		return new Dispatcher();
	}
	//-

	/**
	 * Executed at the very end of everything
	 * --
	 * @return	void
	 */
	public function __destruct()
	{
		# Final event
		Event::Trigger('avrelia.before.destruct');

		# Write final log
		if (Cfg::Get('log/enabled') && Cfg::Get('log/write_individual') === false) {
			Log::WriteAll(false);
		}
	}
	//-
}
//--
