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

	/**
	 * Will copy (if not found) all files from plug's "public" folder to
	 * actul public folder.
	 * The public folder name will be set based on plug's _id_ (name).
	 * ---
	 * @param string $fullPath -- you can just pass __FILE__
	 * ---
	 * @return bool
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
		$publicPath = ds(PUBPATH . '/' . Cfg::Get('Plug/public_dir', 'plugs'));

		# Full public path
		$fullPublicPath = ds($publicPath.'/'.$comName);

		# Debug mode?
		if (Cfg::Get('Plug/debug') && is_dir($fullPublicPath)) {
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
	 * ---
	 * @param string $fullPath -- you can just pass __FILE__
	 * ---
	 * @return array
	 */
	public static function GetConfig($fullPath)
	{
		Log::Add('INF', "Getting config for: `{$fullPath}`.", __LINE__, __FILE__);
		$path     = dirname($fullPath);
		$name     = FileSystem::FileName($path);
		$cConf    = ds("{$path}/{$name}_config.php");
		$aConf    = ds(APPPATH."/config/plugs/{$name}_config.php");
		$alConf   = ds(APPPATH."/config/plugs/{$name}_config.local.php");
		$variable = toCamelCase("{$name}_config");

		# Include plug's default settings (from plug's folder)
		if (file_exists($cConf)) {
			include $cConf;
		}

		# Include settings for plug from application folder
		if (file_exists($aConf)) {
			include $aConf;
		}

		# Include settings for plug from application folder, local version
		if (file_exists($alConf)) {
			include $alConf;
		}

		# Return variable (if is set)
		return $$variable;
	}
	//-

	/**
	 * Will get language for particular plug
	 * ---
	 * @param string $fullPath   -- full path to plug (including filename for main static class __FILE__)
	 * @param string $language   -- do we need particular language?
	 * @param bool   $getDefault -- get fisr default language, if requested can't be found
	 * ---
	 * @return void
	 */
	public static function GetLanguage($fullPath, $language=false, $getDefault=false)
	{
		Log::Add('INF', "Getting language for: `{$fullPath}`.", __LINE__, __FILE__);
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
	 * ---
	 * @param array $Components   -- list of plugs to initialize
	 * @param bool  $autoInit     -- by default all plugs will be autoinitialize,
	 * 							     set this to false, to avoid this behaviour.
	 * 							     Component need to have static public method "_DoInit".
	 * @param bool  $stopOnFailed -- If one of the plugs, doesn't initialize,
	 *                               should we stop loading?
	 * ---
	 * @return true if successfull and array (list of failed plugs) if not.
	 */
	public static function Inc($Components, $autoInit=true, $stopOnFailed=false)
	{
		if (!is_array($Components)) {
			$Components = array($Components);
		}

		$Failed = array();

		foreach ($Components as $component) {

			if (isset(self::$Included[$component])) {
				continue;
			}
			else {
				self::$Included[$component] = true;
			}

			# Do we have class already?
			# Try to guess(?) class name!
			$classNameOne = 'c' . toCamelCase($component, true);
			$classNameTwo = 'c' . strtoupper($classNameOne);

			if (!class_exists($classNameOne, false) && !class_exists($classNameTwo, false)) {
				# Try to include main class!
				$baseClassFilename = "/plugs/{$component}/{$component}.php";
				$appClassFileName = ds(APPPATH.$baseClassFilename);
				$sysClassFielName = ds(SYSPATH.$baseClassFilename);

				if (file_exists($appClassFileName)) {
					include $appClassFileName;
				}
				elseif (file_exists($sysClassFielName)) {
					include $sysClassFielName;
				}
				else {
					Log::Add('WAR', "Can't find plug: `{$baseClassFilename}`.", __LINE__, __FILE__);
					$Failed[] = $component;
					if ($stopOnFailed) {
						break;
					}
				}
			}

			if ($autoInit) {

				if (class_exists($classNameOne, false)) {
					$className = $classNameOne;
				}
				elseif (class_exists($classNameTwo, false)) {
					$className = $classNameTwo;
				}
				else {
					Log::Add('WAR', "Can't find plug's class: `{$baseClassFilename}`, attempts: `{$classNameOne}` and `{$classNameTwo}`.", __LINE__, __FILE__);
					$Failed[] = $component;
					if ($stopOnFailed) {
						break;
					}
				}

				if (method_exists($className, '_DoInit')) {
					if (!$className::_DoInit()) {
						Log::Add('WAR', "Method: `_DoInit` in `{$className}` failed!", __LINE__, __FILE__);
					}
				}
			}
		}

		return (empty($Failed)) ? true : $Failed;
	}
	//-

	/**
	 * Get full absolute public path + additional
	 * ---
	 * @param string $path
	 * ---
	 * @return string
	 */
	public static function GetPublicPath($path=null)
	{
		return ds(PUBPATH . '/' . Cfg::Get('Plug/public_dir', 'plugs') . '/' . $path);
	}
	//-

	/**
	 * Get full absolute database path + additional
	 * ---
	 * @param string $path
	 * ---
	 * @return string
	 */
	public static function GetDatabasePath($path=null)
	{
		return ds(APPPATH . '/database/' . Cfg::Get('Plug/public_dir', 'plugs') . '/' . $path);
	}
	//-
}
//--
