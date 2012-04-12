<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Get View Files
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2011-12-20
 */

class View
{
	/**
	 * @var	integer	How many vew rendering is in progress? (for calls from template itself)
	 */
	protected static $viewsProgress = 0;

	/**
	 * @var	array	Variables For Views
	 */
	protected static $ViewsData     = array();

	/**
	 * @var	integer	Amount of all loaded views
	 */
	protected static $viewsLoaded   = 0;


	/**
	 * Add Data To View (at any point)
	 * --
	 * @param	mixed	$key		Key Name - Or Array Of Variables
	 * @param	string	$content	Content (only in case if you didn't provide array as key)
	 * --
	 * @return	void
	 */
	public static function AddVar($key, $content=null)
	{
		if (!is_array($key)) {
			self::$ViewsData[$key] = $content;
		}
		else {
			self::$ViewsData = array_merge(self::$ViewsData, $key);
		}
	}
	//-

	/**
	 * Will Load The View, and output it
	 * --
	 * @param	string	$file	Only filename
	 * @param	array	$Data	List of variables to inclued
	 * --
	 * @return	ViewAssign
	 */
	public static function Get($file, $Data=array())
	{
		$BT     = debug_backtrace();
		if (isset($BT[3]['class']) &&  isset($BT[3]['type']) && isset($BT[3]['function'])) {
			$btType = $BT[3]['class'] . $BT[3]['type'] . $BT[3]['function'];
		}
		else {
			$btType = false;
		}

		$result = self::Render($file, $Data);

		if ($btType !== 'View::Get') {
			$outputKey = 'AvreliaView.'.self::$viewsLoaded.'.'.$file;
			Output::Set($outputKey, $result);
			return new ViewAssign($result, $outputKey);
		}
		else {
			# This mean that call was made from template iself...
			echo $result;
		}
	}
	//-

	/**
	 * Will Load The View, and return it
	 * Return string or boolean - depends if view was found and loaded or not.
	 * --
	 * @param	string	$file	Only filename
	 * @param	array	$Data	List of variables to inclued
	 * --
	 * @return	string
	 */
	public static function Render($file, $Data=array())
	{
		# Add ext?
		$file = ((substr($file,-4,4) == '.php') || (substr($file,-5,5) == '.html')) ? $file : $file . '.php';

		# Absolute path provided?
		if (substr($file,0,1) != '/') {
			$filename = ds(APPPATH.'/views/'.$file);
		}
		else {
			$filename = $file;
		}

		if (!file_exists($filename)) {
			Log::Add('ERR', "File not found: `{$filename}`.", __LINE__, __FILE__);
			return false;
		}

		self::AddVar($Data);
		$Data = self::$ViewsData;

		if (!empty($Data)) {
			foreach($Data as $var => $val) {
				$$var = $val;
			}
		}

		self::$viewsProgress++;
		ob_start();
		include($filename);
		$result = ob_get_contents();
		ob_end_clean();
		self::$viewsProgress--;

		self::$viewsLoaded++;

		return $result;
	}
	//-

	/**
	 * Placeholder for region
	 * --
	 * @param	string	$name
	 * --
	 * @return	void
	 */
	public static function Region($name)
	{
		echo Output::Take('AvreliaView.region.'.$name) . '<!-- Avrelia Framework Region {'.$name.'} -->';
	}
	//-

}
//--


class ViewAssign
{
	# View's content
	private $contents;

	# View's Output key
	private $outputKey;


	/**
	 * Construct ViewAssign
	 * --
	 * @param	string	$contents
	 * @param	string	$outputKey
	 * --
	 * @return	void
	 */
	public function __construct($contents, $outputKey)
	{
		$this->contents  = $contents;
		$this->outputKey = $outputKey;
	}
	//-

	/**
	 * Set current view as master
	 * --
	 * @return	void
	 */
	public function asMaster()
	{
		Output::Set('AvreliaView.master', $this->contents);
		Output::Clear($this->outputKey);
	}
	//-

	/**
	 * Will assign current view as region
	 * --
	 * @param	string	$name	Region's name
	 * --
	 * @return	void
	 */
	public function asRegion($name)
	{
		if (Output::Has('AvreliaView.master')) {
			$master = Output::Take('AvreliaView.master');
			$master = str_replace('<!-- Avrelia Framework Region {'.$name.'} -->',
								  $this->contents . "\n" . '<!-- Avrelia Framework Region {'.$name.'} -->',
								  $master);
			Output::Set('Avrelia.master', $master);
		}
		else {
			Output::Set("AvreliaView.region.{$name}", $this->contents);
		}

		Output::Clear($this->outputKey);
	}
	//-

	/**
	 * Echo view
	 * --
	 * @return	void
	 */
	public function toScreen()
	{
		echo $this->doReturn();
	}
	//-

	/**
	 * Return view
	 * --
	 * @return	string
	 */
	public function doReturn()
	{
		Output::Clear($this->outputKey);
		return $this->contents;
	}
	//-
}
//--
