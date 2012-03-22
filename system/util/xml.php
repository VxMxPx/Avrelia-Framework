<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * XML parser
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2009, Avrelia.com
 * @license    http://avrelia.com/license
 * @link       http://avrelia.com
 * @since      Version 0.80
 * @since      Mon May 16 14:44:58 2011
 */


class uXML
{
	# XML Resource, as string
	private $resource    = false;

	# XML as object
	private $resourceXML = false;

	/**
	 * Load xml from file, or, if you pass in string, from string.
	 * If you pass in file, plase prefix it with "file://"!!
	 * ---
	 * @param string $source
	 * ---
	 * @return uXML
	 */
	public function __construct($source)
	{
		if (substr($source, 0, 7) == 'file://') {
			if (file_exists($source)) {
				$source = FileSystem::Read($source);
			}
			else {
				Log::Add('ERR', "File not found: `{$source}`.", __LINE__, __FILE__);
				return false;
			}
		}

		if (empty($source)) {
			Log::Add('WAR', "Empty source file send in!", __LINE__, __FILE__);
			return false;
		}

		$this->resource = $source;
		$this->reload();
		return true;
	}
	//-

	/**
	 * Reload, xml object
	 * ---
	 * @return true
	 */
	private function reload()
	{
		$this->resourceXML = new SimpleXMLElement($this->resource);
		return true;
	}
	//-

	/**
	 * Pass in XML string, and namespaces ":" will be converted to "_", if you
	 * wanna then convert that XML to Array, namespaces won't cause problems.
	 * Namespaces are silly little thing, mostly causing problems,
	 * insteead of solving them.
	 * ---
	 * @return string
	 */
	public function killNS()
	{
		$this->resource = preg_replace('/(<\/?[a-zA-Z0-9\-_]*?):([a-zA-Z0-9\-_]*?[ >])/', '$1_$2', $this->resource);
		$this->reload();
	}
	//-

	/**
	 * Select nodes by XPath
	 * ---
	 * @param string $path
	 * ---
	 * @return this
	 */
	public function xpath($path)
	{
		return $this->resourceXML->xpath($path);
	}
	//-

	/**
	 * Load xml from file, or, if you pass in string, from string.
	 * If you pass in file, plase prefix it with "file://"!!
	 * This is the same as calling new uXML($source)
	 * ---
	 * @param string $source
	 * ---
	 * @return uXML
	 */
	public static function Get($source)
	{
		return new self($source);
	}
	//-

	/**
	 * Convert an Array to XML
	 * --
	 * @param array $Array
	 * @param string $parent -- if key is numberic, can be named as $parent
	 * --
	 * @return string (XML)
	 */
	public static function FromArray($Array, $parent=null)
	{
		if (!is_array($Array)) {
			return null;
		}

		$XML = '';

		foreach ($Array as $key => $val) {
			if (is_numeric($key) && $parent) {
				$key = $parent;
			}

			if (is_array($val)) {
				// In this case we won't use parent...
				if (isset($val[0])) {
					$XML .= self::arrayToXML($val, $key);
				}
				else {
					$XML .= "<{$key}>\n" . self::arrayToXML($val, null) . "</{$key}>\n";
				}
			}
			else {
				$XML .= "<{$key}>{$val}</{$key}>\n";
			}
		}

		return $XML;
	}
	//-

}
//-
