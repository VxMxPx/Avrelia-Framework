<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Will All jQuery Library To cHTML Footers
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-02-19
 */
class cJquery
{
	private static $Config;	# string	Plug's configs
	private static $link;	# string	Actual full link to script (either local or googleapis)
	private static $tag;	# string	Tag template

	/**
	 * Will add jQuery to cHTML footer.
	 * --
	 * @return	boolean
	 */
	public static function _OnInit()
	{
		self::$Config = Plug::GetConfig(__FILE__);

		if (self::$Config['local']) {
			# We have some public material
			Plug::SetPublic(__FILE__);

			# Link
			self::$link = url(Cfg::Get('plug/public_dir', 'plugs').'/jquery/jquery-'.self::$Config['version'].'.min.js');
		}
		else {
			# Link
			self::$link = 'http://ajax.googleapis.com/ajax/libs/jquery/'.self::$Config['version'].'/jquery.min.js';
		}

		self::$tag = '<script src="'.self::$link.'"></script>';

		# Add footer tag
		cHTML::AddFooter(self::$tag, 'cjquery');

		return true;
	}
	//-

	/**
	 * Add jQuery to cHTML footer.
	 * --
	 * @return	void
	 */
	public static function Add()
	{
		cHTML::AddFooter(self::$tag, 'cjquery');
	}
	//-

	/**
	 * Remove jQuery from cHTML footer.
	 * --
	 * @return	void
	 */
	public static function Remove()
	{
		cHTML::AddFooter(false, 'cjquery');
	}
	//-

	/**
	 * Get only url
	 * --
	 * @return	string
	 */
	public static function Url()
	{
		return self::$link;
	}
	//-
}
//-
