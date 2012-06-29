<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Plugs Library
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2009, Avrelia.com
 * @license    http://avrelia.com/license
 * @link       http://avrelia.com
 * @since      Version 0.80
 * @since      Tue Apr 12 14:55:02 2011
 */

class Plug
{
	# List of included plugs
	private static $Included = array();

	# List of all cached models
	private static $Models   = array();

	# List of available plugs
	private static $Available = array();


	/**
	 * This will refresh plugs in particular folder. This method will make sure,
	 * that all plugs which we need are enabled.
	 *
	 * Return list of all plugs, with their statuses (time-stamp if was enabled, false if not).
	 * --
	 * @param	array	$List
	 * --
	 * @return	array
	 */
	public static function Init($List)
	{
		# Load enabled, if there are any.
		if (file_exists(self::GetDatabasePath('plugs.json'))) {
			self::$Available = uJSON::DecodeFile(self::GetDatabasePath('plugs.json'), true);
			self::$Available = is_array(self::$Available) ? self::$Available : array();
		}
		else {
			self::$Available = array();
		}

		# Nothing to see or do here if both are empty...
		if (empty($List) && empty(self::$Available)) {
			return true;
		}

		# Do we have to disable & remove anything?
		foreach (self::$Available as $plug => $status) {
			if ($status) {
				if (!in_array($plug, $List)) {
					self::Disable($plug);
				}
			}
		}

		# See if we have all we require on the list
		foreach ($List as $plug) {
			if (!isset(self::$Available[$plug])) {
				# Enable it then...
				self::Enable($plug);
			}
		}

		return self::$Available;
	}
	//-

	/**
	 * Check if particular plug is enabled.
	 * --
	 * @param	string	$name
	 * --
	 * @return	boolean
	 */
	public static function Has($name)
	{
		return isset(self::$Available[$name]) && self::$Available[$name] ? true : false;
	}
	//-

	/**
	 * Will enable particular plug. This will check for static method _OnEnable,
	 * if method can't be found, we'll return true (no need to enable it).
	 * If method can be found, it will be called and result will be returned.
	 * --
	 * @param	string	$plug
	 * --
	 * @return	boolean
	 */
	public static function Enable($plug)
	{
		$plugPath  = self::CalculatePath($plug);
		$className = self::ClassName($plug);

		if (!$className) {
			include ds("{$plugPath}/{$plug}.php");
		}

		$className = self::ClassName($plug);

		if (!$className) {
			Log::Add('WAR', "Can't enable plug, class not found for: `{$plug}`.", __LINE__, __FILE__);
			return false;
		}

		if (method_exists($className, '_OnEnable')) {
			$return = $className::_OnEnable();
		}
		else {
			Log::Add('INF', "Method `_OnEnable` not found in `{$className}`.", __LINE__, __FILE__);
			$return = true;
		}

		# Add it to the list
		self::$Available[$plug] = time();
		self::SaveList();

		# We Included Class, So We Need to Init it now.
		self::Inc($plug);
		return $return;
	}
	//-

	/**
	 * Will disable particular plug. This will check for static method _OnDisable,
	 * if method can't be found, we'll return true (no need to do anything disable).
	 * If method can be found, it will be called and result will be returned.
	 * --
	 * @param	string	$plug
	 * --
	 * @return	boolean
	 */
	public static function Disable($plug)
	{
		# Remove it from list
		if (isset(self::$Available[$plug])) {
			unset(self::$Available[$plug]);
			self::SaveList();
		}

		$plugPath  = self::CalculatePath($plug);
		$className = self::ClassName($plug);

		if (!$className) {
			include ds("{$plugPath}/{$plug}.php");
		}

		$className = self::ClassName($plug);

		if (!$className) {
			Log::Add('WAR', "Can't disable plug, class not found for: `{$plug}`.", __LINE__, __FILE__);
			return false;
		}

		if (method_exists($className, '_OnDisable')) {
			return $className::_OnDisable();
		}
		else {
			Log::Add('INF', "Method `_OnDisable` no found in `{$className}`.", __LINE__, __FILE__);
			return true;
		}
	}
	//-

	/**
	 * Save list of available plugs
	 * --
	 * @return	void
	 */
	private static function SaveList()
	{
		return FileSystem::Write(
					uJSON::Encode(self::$Available),
					self::GetDatabasePath('plugs.json'),
					false,
					0777
				);
	}
	//-

	/**
	 * Will copy (if not found) all files from plug's "public" folder to
	 * actual public folder.
	 * The public folder name will be set based on plug's _id_ (name).
	 * --
	 * @param	string	$fullPath	You can just pass __FILE__
	 * --
	 * @return	boolean
	 */
	public static function SetPublic($fullPath)
	{
		# Get full plug's path and plug's name
		$fullPath = dirname($fullPath);
		$comName  = basename($fullPath);

		# Full componet's public path
		$fullComPublicPath = ds($fullPath.'/public');

		# Check if plug has public directory
		if (!is_dir($fullComPublicPath)) {
			return true;
		}

		# Define public path
		$publicPath = ds(PUBPATH . '/' . Cfg::Get('plug/public_dir', 'plugs'));

		# Full public path
		$fullPublicPath = ds($publicPath.'/'.$comName);

		# Debug mode?
		if (Cfg::Get('plug/debug') && is_dir($fullPublicPath)) {
			Log::Add('INF', "The debug mode is enabled, will remove folder: `{$fullPublicPath}`.", __LINE__, __FILE__);
			FileSystem::Remove($fullPublicPath);
		}

		# Exists not? :)
		if (!is_dir($fullPublicPath)) {
			Log::Add('INF', "Can't find public copy, creating it: `{$fullPublicPath}` from `{$fullComPublicPath}`.", __LINE__, __FILE__);
			return FileSystem::Copy($fullComPublicPath, $fullPublicPath);
		}

		return true;
	}
	//-

	/**
	 * Will get config for particular plug
	 * --
	 * @param	string	$fullPath	You can just pass __FILE__
	 * --
	 * @return	array
	 */
	public static function GetConfig($fullPath)
	{
		$path     = dirname($fullPath);
		$name     = FileSystem::FileName($path);
		$cConf    = ds("{$path}/{$name}_config.php");
		$aConf    = ds(APPPATH."/config/plugs/{$name}_config.php");
		$alConf   = ds(APPPATH."/config/plugs/{$name}_config.local.php");
		$variable = toCamelCase("{$name}_config");
		$included = array();

		# Include plug's default settings (from plug's folder)
		if (file_exists($cConf)) {
			$included[] = $cConf;
			include $cConf;
		}

		# Include settings for plug from application folder
		if (file_exists($aConf)) {
			$included[] = $aConf;
			include $aConf;
		}

		# Include settings for plug from application folder, local version
		if (file_exists($alConf)) {
			$included[] = $alConf;
			include $alConf;
		}

		# Check if variable is set and return it!
		if (!isset($$variable)) {
			$$variable = false;
		}

		# Log the list of included files
		Log::Add("The following config files were included: \n" . implode("\n", $included), 'INF');

		# Append it to the global config
		$Config['plugs'][$name] = $$variable;
		Cfg::Append($Config);

		return $$variable;
	}
	//-

	/**
	 * Will load driver class for particular plug (+base, +interface if exists)
	 * --
	 * @param	string	$fullPath	Full path to plug (including filename for main static class __FILE__)
	 * @param	string	$type		Which driver do we need
	 * @param	boolean	$construct	If true, the driver will be constructed
	 * @param	mixed	$prefix		In some cases we have more than one driver, and they're prefixed
	 * --
	 * @return	mixed	False if not loaded, object if construct is true, string (class name) if construct is false
	 */
	public static function GetDriver($fullPath, $type, $construct=true, $prefix=false)
	{
		# Get basepath
		$path = dirname($fullPath);
		$plugName = basename($path);

		# Resolve prefix
		if ($prefix) {
			$type = $prefix . '_' . $type;
		}

		# Resolve filenames
		# 1. interface
		$interfaceClass  = 'c' . toCamelCase($plugName) . 'Driver';
		$interfaceClass .= ($prefix) ? toCamelCase($prefix) : '';
		$interfaceClass .= 'Interface';

		$interfaceFile  = $path . '/drivers/';
		$interfaceFile .= ($prefix) ? $prefix.'_' : '';
		$interfaceFile .= 'interface.php';

		# 2. base
		$baseClass  = 'c' . toCamelCase($plugName) . 'Driver';
		$baseClass .= ($prefix) ? toCamelCase($prefix) : '';
		$baseClass .= 'Base';

		$baseFile  = $path . '/drivers/';
		$baseFile .= ($prefix) ? $prefix.'_' : '';
		$baseFile .= 'base.php';

		# 3. driver
		$driverClass  = 'c' . toCamelCase($plugName) . 'Driver';
		$driverClass .= ($prefix) ? toCamelCase($prefix) : '';
		$driverClass .= toCamelCase($type);

		$driverFile  = $path . '/drivers/';
		$driverFile .= ($prefix) ? $prefix.'_' : '';
		$driverFile .= $type.'.php';

		# Load interface if exists
		if (!interface_exists($interfaceClass, false)) {
			if (file_exists($interfaceFile)) {
				include($interfaceFile);
			}
		}

		# Load base if exists
		if (!class_exists($baseClass, false)) {
			if (file_exists($baseFile)) {
				include($baseFile);
			}
		}

		# Get driver's class
		if (!class_exists($driverClass, false)) {
			if (file_exists($driverFile)) {
				include($driverFile);
			}
			else {
				Log::Add("Can't load driver: `{$driverClass}` from `{$driverFile}`, file not found.", 'WAR');
				return false;
			}
		}

		if (!class_exists($driverClass, false)) {
			Log::Add("Class `{$driverClass}` not found in `{$driverFile}`.", 'WAR');
			return false;
		}

		Log::Add("Driver was loaded: `{$driverClass}`.");
		if ($construct) {
			return new $driverClass();
		}
		else {
			return $driverClass;
		}
	}
	//-

	/**
	 * Will get language for particular plug
	 * --
	 * @param	string	$fullPath	Full path to plug (including filename for main static class __FILE__)
	 * @param	string	$language	Do we need particular language?
	 * @param	boolean	$getDefault	Get first default language, if requested can't be found
	 * --
	 * @return	void
	 */
	public static function GetLanguage($fullPath, $language=false, $getDefault=false)
	{
		$language = !$language ? '%' : $language;
		$path     = dirname($fullPath);
		$name     = FileSystem::FileName($path, true);
		$cLang    = ds("{$path}/languages/{$name}.{$language}.lng");
		$aLang    = ds(APPPATH . "/languages/plugs/{$name}.{$language}.lng");

		Language::Load($cLang, $getDefault);
		Language::Load($aLang, $getDefault);
	}
	//-

	/**
	 * Include plug(s).
	 * Return true if successful and array (list of failed plugs) if not.
	 * --
	 * @param	array	$Components		List of plugs to initialize
	 * @param	boolean	$autoInit		By default all plugs will be auto-initialize,
	 * 									set this to false, to avoid this behavior.
	 * 									Plug need to have static public method "_OnInit".
	 * @param	boolean	$stopOnFailed	If one of the plugs, doesn't initialize, should we stop loading?
	 * --
	 * @return	mixed
	 */
	public static function Inc($Components, $autoInit=true, $stopOnFailed=false)
	{
		if (!is_array($Components)) {
			$Components = array($Components);
		}

		$Failed = array();

		foreach ($Components as $component)
		{
			if (isset(self::$Included[$component])) {
				continue;
			}
			else {
				self::$Included[$component] = true;
			}

			# Is it enabled?
			if (!isset(self::$Available[$component]) || self::$Available[$component] == false) {
				# If we're dealing with sub-class_
				if (!self::Guess($component)) {
					trigger_error("Plug `{$component}` isn't enabled, can't continue.", E_USER_ERROR);
					return false;
				}
			}

			# Do we have class already?
			$className = self::ClassName($component);

			if (!$className) {
				# Try to include main class!
				$baseClassFilename = self::CalculatePath($component);
				$fullClassFileName = ds($baseClassFilename."/{$component}.php");

				if (file_exists($fullClassFileName)) {
					include $fullClassFileName;
				}
				else {
					Log::Add('WAR', "Can't find plug: `{$fullClassFileName}`.", __LINE__, __FILE__);
					$Failed[] = $component;
					if ($stopOnFailed) {
						break;
					}
				}
			}

			if ($autoInit)
			{
				$className = self::ClassName($component);

				if (!$className) {
					Log::Add('WAR', "Can't find plug's class for: `{$component}`.", __LINE__, __FILE__);
					$Failed[] = $component;
					if ($stopOnFailed) {
						break;
					}
				}

				if (method_exists($className, '_OnInit'))
				{
					if (!$className::_OnInit()) {
						Log::Add('WAR', "Method: `_OnInit` in `{$className}` failed!", __LINE__, __FILE__);
					}
				}
			}
		}

		return (empty($Failed)) ? true : $Failed;
	}
	//-

	/**
	 * Used when we want to construct sub-class for particular plug before main
	 * class was initialized. For example: cDatabaseQuery (before we called cDatabase,
	 * which would actually included this class).
	 * This will only find main class and initialize it, then if sub-class exists,
	 * return true else false.
	 * --
	 * @param	string	$plug	Sub-class name
	 * --
	 * @return	boolean
	 */
	private static function Guess($plug)
	{
		# If we don't have any _, then we know it's not sub-class
		if (strpos($plug,'_') === false) {
			return false;
		}

		# Check if parent component is enable...
		$Plug = explode('_', $plug);
		$final = '';

		foreach($Plug as $p)
		{
			$final .= trim('_' . $p, '_');

			if (self::Has($final)) {
				# Include it
				if (self::Inc($final)) {
					# Do we have this class now?
					return class_exists('c'.toCamelCase($plug), false);
				}
				else {
					break;
				}
			}
		}

		return false;
	}
	//-

	/**
	 * Get full absolute public path + additional
	 * --
	 * @param	string	$path
	 * --
	 * @return	string
	 */
	public static function GetPublicPath($path=null)
	{
		return ds(PUBPATH . '/' . Cfg::Get('plug/public_dir', 'plugs') . '/' . $path);
	}
	//-

	/**
	 * Get full public url + additional
	 * --
	 * @param	string	$uri
	 * --
	 * @return	string
	 */
	public static function GetPublicUrl($uri=false)
	{
		return url(Cfg::Get('plug/public_dir', 'plugs') . '/' . $uri);
	}
	//-

	/**
	 * Get full absolute database path + additional
	 * --
	 * @param	string	$path
	 * --
	 * @return	string
	 */
	public static function GetDatabasePath($path=null)
	{
		return ds(DATPATH . '/' . Cfg::Get('plug/public_dir', 'plugs') . '/' . $path);
	}
	//-

	/*  ****************************************************** *
	 *          Helper Methods
	 *  **************************************  */

	/**
	 * Will calculate (look into application and system folder) plug's path.
	 * --
	 * @param	string	$plug
	 * --
	 * @return	string
	 */
	public static function CalculatePath($plug)
	{
		$appPath = ds(APPPATH."/plugs/{$plug}");
		$sysPath = ds(SYSPATH."/plugs/{$plug}");

		return is_dir($appPath) ? $appPath : is_dir($sysPath) ? $sysPath : false;
	}
	//-

	/**
	 * Get Class Name from plug's name
	 * --
	 * @param	string	$plug
	 * --
	 * @return	string
	 */
	public static function ClassName($plug)
	{
		# Guess class name :)
		$classNameOne = 'c' . toCamelCase($plug, true);
		$classNameTwo = 'c' . strtoupper($plug);

		if (class_exists($classNameOne, false)) {
			return $classNameOne;
		}
		elseif (class_exists($classNameTwo, false)) {
			return $classNameTwo;
		}
		else {
			return false;
		}
	}
	//-
}
//--
