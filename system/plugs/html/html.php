<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia CMS
 * ----
 * HTML Library
 * ----
 * @package    AvreliaCMS
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2009, Avrelia.com
 * @license    http://cms.avrelia.com/license
 * @link       http://cms.avrelia.com
 * @since      Version 0.60
 * @since      Date 2009-08-18
 * ---
 * @property	array	$Headers
 * @property	array	$Footers
 */
class cHTML
{
	private static $Headers = array();
	private static $Footers = array();

	/**
	 * Will init the object
	 * --
	 * @return	boolean
	 */
	public static function _DoInit()
	{
		Plug::GetLanguage(__FILE__);
		return true;
	}
	//-

	/**
	 * Add Something To The Heeader
	 * --
	 * @param	string	$content	What we want to add to header? | If false, header will be removed.
	 * @param	mixed	$key		False for no key.
	 * --
	 * @return	void
	 */
	public static function AddHeader($content, $key=false)
	{
		if ($key === false) {
			self::$Headers[] = $content;
		}
		else {
			if ($content === false) {
				if (isset(self::$Headers[$key])) {
					unset(self::$Headers[$key]);
				}
			}
			else {
				self::$Headers[$key] = $content;
			}
		}
	}
	//-

	/**
	 * Return Headers
	 * --
	 * @param	boolean	$echo	Do we need to echo headers?
	 * --
	 * @return	string
	 */
	public static function GetHeaders($echo=true)
	{
		$return = '';

		if (!empty(self::$Headers)) {
			foreach(self::$Headers as $header) {
				$return .= "{$header}\n";
			}
		}

		if ($echo) {
			echo $return;
		}
		else {
			return $return;
		}
	}
	//-

	/**
	 * Add Something To The Footer
	 * --
	 * @param	string	$content	If false, footer will be removed.
	 * @param	mixed	$key		False for no key.
	 * --
	 * @return	void
	 */
	public static function AddFooter($content, $key=false)
	{
		if ($key === false) {
			self::$Footers[] = $content;
		}
		else {
			if ($content === false) {
				if (isset(self::$Footers[$key])) {
					unset(self::$Footers[$key]);
				}
			}
			else {
				self::$Footers[$key] = $content;
			}
		}
	}
	//-

	/**
	 * Return Footers
	 * --
	 * @param	boolean	$echo	Do we need to echo footers?
	 * --
	 * @return	string
	 */
	public static function GetFooters($echo=true)
	{
		$return = '';

		if ( !empty(self::$Footers) ) {
			foreach(self::$Footers as $footer) {
				$return .= "{$footer}\n";
			}
		}

		if ($echo) {
			echo $return;
		}
		else {
			return $return;
		}
	}
	//-

	/**
	 * Create tabs toolbar
	 * --
	 * @param	array	$Items		Arrax('uri/path' => 'Title', 'http://absolute.address' => 'Title')
	 * 								OR array('uri' => array('attributes' => 'class="something", title="My title"))
	 *								OR array(':right' => 'costume_code') // will not act as url item
	 * @param	boolean	$prefixZero	Will prefix zero element to URL
	 * @param	string	$mainClass	Main element class (may be more than one)
	 * @param	string  $mainId		Main element id (false for none)
	 * --
	 * @return	string
	 */
	public static function Tabs($Items, $prefixZero=false, $mainClass='tabs', $mainId=false)
	{
		$return = '<div'.($mainClass ? ' class="'.$mainClass.'"' : '').($mainId ? ' id="'.$mainId.'"' : '').'>';

		foreach ($Items as $url => $Item)
		{
			# Do we have any other item?
			if (substr($url, 0, 1) == ':') {
				$return .= $Item;
				continue;
			}

			# Genera url first
			if (strpos($url,'://') === false) {
				$url = url($url, $prefixZero);
			}

			# Is array?
			if (is_array($Item)) {
				$attributes = $Item['attributes'];
				$title      = $Item['title'];
			}
			else {
				$attributes = 'class=""';
				$title      = $Item;
			}

			# Replace class to selected in case of same URL
			if ($url == url(Input::Get(false))) {
				$attributes = str_replace('class="', 'class="selected ', $attributes);
				$attributes = str_replace(' "', '"', $attributes); // replace stuff life class="selected "
			}
			else {
				$attributes = str_replace('class=""', '', $attributes);
			}

			$attributes = ' ' . $attributes;
			$return    .= '<a href="'.$url.'"'.$attributes.'><span>'.$title.'</span></a>';
		}

		$return .= '</div>';
		return $return;
	}
	//-

	/**
	 * Will highlight particular text. Return full string with all highlights.
	 * --
	 * @param	string	$haystack
	 * @param	mixed	$needle		List of words to highlight (string/array)
	 * @param	string	$wrap		Tag into which we wrap the needle
	 * --
	 * @return	string
	 */
	public static function Hightlight($haystack, $needle, $wrap='<span class="highlight">%s</span>')
	{
		if (!$needle || !$haystack) {
			return $haystack;
		}

		if (is_array($needle)) {
			foreach ($needle as $ndl) {
				//if (!empty($ndl) && strlen($ndl) > 2) {
				$haystack = self::Hightlight($haystack, $ndl, $wrap);
				//}
			}

			return $haystack;
		}

		$needle   = trim(str_replace('/', '', $needle));
		if (!empty($needle)) {
			$haystack = preg_replace_callback('/'.preg_quote($needle).'/i', create_function('$Matches', 'return str_replace(\'%s\', $Matches[0], \''.str_replace("'", "\'", $wrap).'\');'), $haystack);
		}

		return $haystack;
	}
	//-

	/**
	 * Create Pagination
	 * --
	 * @param	integer	$now			Current page
	 * @param	integer	$perPage		How many items per page
	 * @param	string	$url			Full url, with variable %current_page%
	 *									(where current page will be inserted)
	 * @param	integer	$all			Number of all items
	 * @param	integer	$displayNum		How many (if any) of number items to the left
	 * 									and right we wanna show e.g.: 2 - will produce
	 * 									4 5 [6] 7 8 (if 6 is current page)
	 * @param	boolean	$diaplyNext		Display links Next, and Previous
	 * @param	boolean	$displayFirst	Display links Fist and Last
	 * --
	 * @return	string
	 */
	public static function Pagination($now, $perPage, $url, $all=false, $displayNum=4, $diaplyNext=true, $displayFirst=true)
	{
		$Pagination = array();

		if (!$now OR $now == 0 OR $now < 1) {
			$now = 1;
		}

		# Per Page
		if (!$perPage OR !is_numeric($perPage) OR $perPage < 1) {
			return false;
		}

		# All Avilable
		if ($all AND is_numeric($all) AND $all > 0 AND $all > $perPage) {
			$topPage = $all / $perPage;
			$topPage = ceil($topPage);
		}
		else {
			return false;
		}

		# Link To First Page
		if ($displayFirst AND $now > 1) {
			$u = str_replace('%current_page%', '1', $url);
			$Pagination[] = '<a href="'.$u.'" title="'.l('BACK_TO_FRIST_PAGE').'">&laquo;</a>';
		}
		elseif ($displayFirst) {
			$u = str_replace('%current_page%', '1', $url);
			$Pagination[] = '<span class="pagination_now pag_first"><a href="'.$u.'" title="'.l('BACK_TO_FRIST_PAGE').'">&laquo;</a><span>&laquo;</span></span>';
		}

		# Link To Previous Page
		if ($diaplyNext AND $now > 1) {
			$u = str_replace('%current_page%', $now-1, $url);
			$Pagination[] = '<a href="'.$u.'" title="'.l('BACK_TO_PREVI_PAGE').'">&lsaquo;</a>';
		}
		elseif ($diaplyNext) {
			$u = str_replace('%current_page%', $now, $url);
			$Pagination[] = '<span class="pagination_now pag_previous"><a href="'.$u.'" title="'.l('BACK_TO_PREVI_PAGE').'">&lsaquo;</a><span>&lsaquo;</span></span>';
		}

		# Make Number Links
		if ($displayNum) {
			# Negative
			for($i=$displayNum; $i > 0; $i--) {
				$current = $now - $i;
				if ($current > 0) {
					$u = str_replace('%current_page%', $current, $url);
					$Pagination[] = '<a href="'.$u.'" title="'.l('GO_TO_PAGE', $current).'">'.$current.'</a>';
				}
			}

			# Current Page
			$u = str_replace('%current_page%', $now, $url);
			$Pagination[] = '<span class="pagination_now pag_num"><a href="'.$u.'" title="'.l('CURRENT_PAGE', $now).'">'.$now.'</a><span>'.$now.'</span></span>';

			# Positive
			if ($topPage) {
				for($i=1; $i < $displayNum; $i++) {
					$current = $now + $i;
					if ($topPage AND $current <= $topPage) {
						$u = str_replace('%current_page%', $current, $url);
						$Pagination[] = '<a href="'.$u.'" title="'.l('GO_TO_PAGE', $current).'">'.$current.'</a>';
					}
				}
			}
		}

		# Link To Next Page
		if ($diaplyNext AND $topPage AND $now < $topPage) {
			$u = str_replace('%current_page%', $now+1, $url);
			$Pagination[] = '<a href="'.$u.'" title="'.l('GO_TO_NEXT_PAGE').'">&rsaquo;</a>';
		}
		elseif ($diaplyNext AND $topPage AND $now == $topPage) {
			$u = str_replace('%current_page%', $now, $url);
			$Pagination[] = '<span class="pagination_now pag_next"><a href="'.$u.'" title="'.l('GO_TO_NEXT_PAGE').'">&rsaquo;</a><span>&rsaquo;</span></span>';
		}


		# Link To Last Page
		if ($displayFirst AND $topPage AND $topPage > $now) {
			$u = str_replace('%current_page%', $topPage, $url);
			$Pagination[] = '<a href="'.$u.'" title="'.l('GO_TO_LAST_PAGE').'">&raquo;</a>';
		}
		elseif ($displayFirst AND $topPage AND $topPage == $now) {
			$u = str_replace('%current_page%', $topPage, $url);
			$Pagination[] = '<span class="pagination_now pag_last"><a href="'.$u.'" title="'.l('GO_TO_LAST_PAGE').'">&raquo;</a><span>&raquo;</span></span>';
		}

		return implode(' ', $Pagination);
	}
	//-

	/**
	 * Create <a href element
	 * --
	 * @param	string	$caption
	 * @param	string	$href		This won't apply any magic like url(...)
	 * @param	string	$attributes	'class="someclass" id="some_id"'
	 * --
	 * @return string
	 */
	public static function Link($caption, $href, $attributes=false)
	{
		$attributes = $attributes ? ' ' . $attributes : '';
		return '<a href="'.$href.'"'.$attributes.'>'.$caption.'</a>';
	}
	//-
}
//--
