<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Database Session Driver
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-03-27
 */

class dbSessionDriver implements interfaceSessionDriver
{
	private $Config;

	/**
	 * Will construct the database object.
	 * --
	 * @param	array	$Config
	 * --
	 * @return	void
	 */
	public function __construct($Config)
	{
		$this->Config = $Config;
	}
	//-
}
//--
