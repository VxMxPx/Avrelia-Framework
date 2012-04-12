<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Debug Plug
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-04-08
 */
class cDebug
{
	/**
	 * Initialize Debug plug
	 */
	public static function _DoInit()
	{
		# Need cJquery
		if (!Plug::Has('jquery'))
		{
			Log::Add('WAR', "Plug `debug` need `jquery` plug to be enabled function.", __LINE__, __FILE__);
			return false;
		}

		# Need cHTML (if we have jQuery then HTML is almost for sure available too, but just to be sure)
		if (!Plug::Has('html')) {
			Log::Add('WAR', "Plug `debug` need `html` plug to be enabled function.", __LINE__, __FILE__);
			return false;
		}

		# Add jQuery
		cJquery::Add();
		cHTML::AddHeader('<style>'.FileSystem::Read(ds(dirname(__FILE__).'/libraries/debug.css')).'</style>', 'cdebug_css');

		Event::Watch('chtml.before.getfooters', array('cDebug', 'AddPanel'));

		return true;
	}
	//-

	/**
	 * Will add footers
	 * --
	 * @return	void
	 */
	public static function AddPanel()
	{
		cHTML::AddFooter(View::Get(ds(dirname(__FILE__).'/views/panel.php'))->doReturn(), 'cdebugPanel');
		cHTML::AddFooter('<script>'.FileSystem::Read(ds(dirname(__FILE__).'/libraries/debug.js')).'</script>', 'cdebug_js');
	}
	//-
}
//--
