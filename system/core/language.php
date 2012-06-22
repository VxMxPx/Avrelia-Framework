<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Language Class
 * Possible use:
 *
 * MY_KEY	My key
 * MY_LONG ----
 * Hello, this is rather long text, so it's written like that.
 * ----
 * MY_A_KEY	My a key
 *
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2011-11-19
 */


class Language
{
	# All translations
	private static $Dictionary = array();

	# List of loaded files (so that we don't load and parse a file twice)
	private static $Loaded     = array();

	# Default languages
	private static $Defaults   = array();

	/**
	 * Will return language debug (info)
	 * --
	 * @return	array
	 */
	public static function Debug()
	{
		return
			"\nLoaded: \n".
			dumpVar(self::$Loaded, false, true).
			"\nDefaults: \n".
			dumpVar(self::$Defaults, false, true).
			"\nDictionary: \n".
			dumpVar(self::$Dictionary, false, true);
	}
	//-

	/**
	 * Set list of default languages
	 * --
	 * @param	array	$Defaults
	 * --
	 * @return	void
	 */
	public static function SetDefaults($Defaults)
	{
		self::$Defaults = $Defaults;
	}
	//-

	/**
	 * Will load particular language file
	 * --
	 * @param	string	$file			Following options:
	 * 									enter short name: "my_lang", and the path will be calculated automatically: APPPATH/languages/my_lang.lng
	 * 									enter full path: SYSPATH.'/languages/my_lang.lng', to load full path (must be with file extension! .lng)
	 * 									enter % in filename, to auto set the language based on languages list
	 * @param	boolean	$getFistDefault	Get first default language found, in chase if we can't find requested, for example:
	 * 									our request is, to get English or Russian, but none of them can be found, but there is Ukrainian language available,
	 * 									in case $getFistDefault the Ukrainian will be loaded
	 * --
	 * @return	boolean
	 */
	public static function Load($file, $getFistDefault=false)
	{
		# Does it have % in it, meaning default languages?
		if (strpos($file, '%') !== false) {

			$defGetFirstDefault = $getFistDefault;
			$getFistDefault     = false;

			foreach (self::$Defaults as $k => $lng) {
				$newFile = str_replace('%', $lng, $file);

				if ($k == (count(self::$Defaults)-1)) { $getFistDefault = $defGetFirstDefault; }

				if (self::Load($newFile, $getFistDefault)) {
					return true;
				}
			}
			return false;
		}

		# is full path or only filename
		if (substr($file,-4,4) !== '.lng') {
			$file = ds(APPPATH."/languages/{$file}.lng");
		}

		# check if file was already loaded
		if (in_array($file, self::$Loaded)) {
			Log::Add('INF', "File is already loaded, won't load it twice: `{$file}`.", __LINE__, __FILE__);
			return true;
		}
		else {
			self::$Loaded[] = $file;
		}

		# is valid path?
		if (!file_exists($file)) {
			if ($getFistDefault) {
				$dir   = dirname($file);
				$fileN = basename($file);
				$fileN = explode('.', $fileN, 2);
				$fileN = $fileN[0];
				$Files = scandir($dir);
				foreach ($Files as $fileName) {
					if (substr($fileName, 0, strlen($fileN)) == $fileN) {
						$fileNameNew = ds("{$dir}/{$fileName}");
						if (self::Load($fileNameNew)) {
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

		$Result = self::Process($file);

		if (is_array($Result)) {
			self::$Dictionary = array_merge(self::$Dictionary, $Result);
			Log::Add("Language loaded: `{$file}`", 'INF');
			return true;
		}
	}
	//-

	/**
	 * Will process particular file and return an array (of expressions)
	 * --
	 * @param	string	$filename
	 * --
	 * @return	array
	 */
	private static function Process($filename)
	{

		$fileContents = FileSystem::Read($filename);
		$fileContents = vString::StandardizeLineEndings($fileContents);

		# Remove comments
		$fileContents = preg_replace('/^#.*$/m', '', $fileContents);

		# Add end of file notation
		$fileContents = $fileContents . "\n__#EOF#__";

		$Contents = '';
		preg_match_all('/^!([A-Z0-9_]+):(.*?)(?=^![A-Z0-9_]+:|^#|^__#EOF#__$)/sm', $fileContents, $Contents, PREG_SET_ORDER);

		$Result = array();

		foreach($Contents as $Options) {
			if (isset($Options[1]) && isset($Options[2])) {
				$Result[trim($Options[1])] = trim($Options[2]);
			}
		}

		return $Result;
	}
	//-

	/**
	 * Will translate particular string
	 * --
	 * @param	string	$key
	 * @param	array	$Params
	 * @param	string	$languageKey
	 * --
	 * @return	string
	 */
	public static function Translate($key, $Params=array())
	{
		if (isset(self::$Dictionary[$key]))
		{
			$return = self::$Dictionary[$key];

			# Check for any variables {1}, ...
			if ($Params) {
				if (!is_array($Params)) {
					$Params = array($Params);
				}
				//$return = vsprintf($return, $Params);
				foreach ($Params as $key => $param) {
					$key = $key + 1;
					$return = preg_replace('/{'.$key.' ?(.*?)}/', str_replace('{?}', '$1', $param), $return);
				}
			}

			return $return;
		}
		else {
			Log::Add('WAR', "Language key not found: `{$key}`.", __LINE__, __FILE__);
			return $key;
		}
	}
	//-
}
//--
