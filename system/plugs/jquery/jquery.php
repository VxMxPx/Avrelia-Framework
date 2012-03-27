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
 * ---
 * @property	array	$Config
 * @property	string	$link
 * @property	string	$tag
 */
class cJquery
{
	private static $Config;
	private static $link;
	private static $tag = '<script src="{{%link}}"></script>';

	/**
	 * Will add jQuery to cHTML footer.
	 * --
	 * @return	boolean
	 */
	public static function _DoInit()
	{
		self::$Config = Plug::GetConfig(__FILE__);

		if (self::$Config['local']) {
			# We have some public material
			Plug::SetPublic(__FILE__);

			# Link
			self::$link = url(Cfg::Get('Com/public_dir', 'components').'/jquery/jquery-'.self::$Config['version'].'.min.js');
		}
		else {
			# Link
			self::$link = 'http://ajax.googleapis.com/ajax/libs/jquery/'.self::$Config['version'].'/jquery.min.js';
		}

		self::$tag = str_replace('{{%link}}', self::$link, self::$tag);

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
