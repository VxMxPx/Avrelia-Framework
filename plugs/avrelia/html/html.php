<?php

namespace Plug\Avrelia;

use Avrelia\Core\Plug  as Plug;
use Avrelia\Core\Event as Event;

/**
 * HTML Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class HTML
{
    private static $headers = array();  # array
    private static $footers = array();  # array

    /**
     * Will init the object
     *
     * @return boolean
     */
    public static function _on_include_()
    {
        Plug::get_language(__FILE__);
        return true;
    }

    /**
     * Add Something To The Heeader
     *
     * @param   string  $content  What we want to add to header? |
     *                            If false, header will be removed.
     * @param   mixed   $key      False for no key.
     * @return  void
     */
    public static function add_header($content, $key=false)
    {
        if ($key === false) {
            self::$headers[] = $content;
        }
        else {
            if ($content === false) {
                if (isset(self::$headers[$key])) {
                    unset(self::$headers[$key]);
                }
            }
            else {
                self::$headers[$key] = $content;
            }
        }
    }

    /**
     * Return Headers
     *
     * @param   boolean $echo   Do we need to echo headers?
     * @return  string
     */
    public static function get_headers($echo=true)
    {
        Event::trigger('/plug/html/get_headers', self::$headers);

        $return = '';

        if (!empty(self::$headers)) {
            foreach(self::$headers as $header) {
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

    /**
     * Add Something To The Footer
     *
     * @param   string  $content    If false, footer will be removed.
     * @param   mixed   $key        False for no key.
     * @return  void
     */
    public static function add_footer($content, $key=false)
    {
        if ($key === false) {
            self::$footers[] = $content;
        }
        else {
            if ($content === false) {
                if (isset(self::$footers[$key])) {
                    unset(self::$footers[$key]);
                }
            }
            else {
                self::$footers[$key] = $content;
            }
        }
    }

    /**
     * Return Footers
     *
     * @param   boolean $echo   Do we need to echo footers?
     * @return  string
     */
    public static function get_footers($echo=true)
    {
        Event::trigger('/plug/html/get_footers', self::$footers);

        $return = '';

        if (!empty(self::$footers)) {
            foreach(self::$footers as $footer) {
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

    /**
     * Create tabs toolbar
     *
     * @param   array   $items       Arrax('uri/path' => 'Title', 'http://absolute.address' => 'Title')
     *                               OR array('uri' => array('attributes' => 'class="something", title="My title"))
     *                               OR array(':right' => 'costume_code') // will not act as url item
     * @param   boolean $prefix_zero Will prefix zero element to URL
     * @param   string  $main_class  Main element class (may be more than one)
     * @param   string  $main_id     Main element id (false for none)
     * @return  string
     */
    public static function tabs($items, $prefix_zero=false, $main_class='tabs', $main_id=false)
    {
        # Create base open div element
        $return =
            '<div'
            .($main_class ? ' class="'.$main_class.'"' : '')
            .($main_id ? ' id="'.$main_id.'"' : '').'>';

        foreach ($items as $url => $item)
        {
            # Do we have any other item?
            if (substr($url, 0, 1) == ':') {
                $return .= $item;
                continue;
            }

            # Genera url first
            if (strpos($url,'://') === false) {
                $url = url($url, $prefix_zero);
            }

            # Is array?
            if (is_array($item)) {
                $attributes = $item['attributes'];
                $title      = $item['title'];
            }
            else {
                $attributes = 'class=""';
                $title      = $item;
            }

            # Replace class to selected in case of same URL
            if ($url == url(Input::get(false))) {
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

    /**
     * Will highlight particular text. Return full string with all highlights.
     *
     * @param   string  $haystack
     * @param   mixed   $needle     List of words to highlight (string/array)
     * @param   string  $wrap       Tag into which we wrap the needle
     * @return  string
     */
    public static function hightlight($haystack, $needle, $wrap='<span class="highlight">%s</span>')
    {
        if (!$needle || !$haystack) {
            return $haystack;
        }

        if (is_array($needle)) {
            foreach ($needle as $ndl) {
                //if (!empty($ndl) && strlen($ndl) > 2) {
                $haystack = self::hightlight($haystack, $ndl, $wrap);
                //}
            }

            return $haystack;
        }

        $needle   = trim(str_replace('/', '', $needle));
        if (!empty($needle)) {
            $haystack = preg_replace_callback(
                '/'.preg_quote($needle).'/i',
                create_function(
                    '$Matches',
                    'return str_replace(\'%s\', $Matches[0], \''
                        .str_replace("'", "\'", $wrap).'\');'
                ),
                $haystack);
        }

        return $haystack;
    }

    /**
     * Create Pagination
     *
     * @param   integer $now            Current page
     * @param   integer $per_page       How many items per page
     * @param   string  $url            Full url, with variable %current_page%
     *                                  (where current page will be inserted)
     * @param   integer $all            Number of all items
     * @param   integer $display_num    How many (if any) of number items to the left
     *                                  and right we wanna show e.g.: 2 - will produce
     *                                  4 5 [6] 7 8 (if 6 is current page)
     * @param   boolean $diaply_next    Display links Next, and Previous
     * @param   boolean $display_first  Display links Fist and Last
     * @return  string
     */
    public static function pagination(
        $now,
        $per_page,
        $url,
        $all,
        $display_num=4,
        $diaply_next=true,
        $display_first=true
    ) {
        # Will contain all pagination elements
        $pagination = array();

        # Now must be more than zero
        if (!$now || $now < 1) { $now = 1; }

        # Per Page
        if (!$per_page || $per_page < 1)
            { throw new \Avrelia\Exception\ValueError("Per page must be more than zero!"); }

        # All Avilable
        if ($all && $all > $per_page) {
            $topPage = $all / $per_page;
            $topPage = ceil($topPage);
        }
        else
            { return false; }

        # Link To First Page
        if ($display_first && $now > 1) {
            $u = str_replace('%current_page%', '1', $url);
            $pagination[] = '<a href="'.$u.'" title="'.l('BACK_TO_FRIST_PAGE').'">&laquo;</a>';
        }
        elseif ($display_first) {
            $u = str_replace('%current_page%', '1', $url);
            $pagination[] = '<span class="pagination_now pag_first"><a href="'.$u.'" title="'.l('BACK_TO_FRIST_PAGE').'">&laquo;</a><span>&laquo;</span></span>';
        }

        # Link To Previous Page
        if ($diaply_next && $now > 1) {
            $u = str_replace('%current_page%', $now-1, $url);
            $pagination[] = '<a href="'.$u.'" title="'.l('BACK_TO_PREVI_PAGE').'">&lsaquo;</a>';
        }
        elseif ($diaply_next) {
            $u = str_replace('%current_page%', $now, $url);
            $pagination[] = '<span class="pagination_now pag_previous"><a href="'.$u.'" title="'.l('BACK_TO_PREVI_PAGE').'">&lsaquo;</a><span>&lsaquo;</span></span>';
        }

        # Make Number Links
        if ($display_num) {
            # Negative
            for($i=$display_num; $i > 0; $i--) {
                $current = $now - $i;
                if ($current > 0) {
                    $u = str_replace('%current_page%', $current, $url);
                    $pagination[] = '<a href="'.$u.'" title="'.l('GO_TO_PAGE', $current).'">'.$current.'</a>';
                }
            }

            # Current Page
            $u = str_replace('%current_page%', $now, $url);
            $pagination[] = '<span class="pagination_now pag_num"><a href="'.$u.'" title="'.l('CURRENT_PAGE', $now).'">'.$now.'</a><span>'.$now.'</span></span>';

            # Positive
            if ($topPage) {
                for($i=1; $i <= $display_num; $i++) {
                    $current = $now + $i;
                    if ($topPage AND $current <= $topPage) {
                        $u = str_replace('%current_page%', $current, $url);
                        $pagination[] = '<a href="'.$u.'" title="'.l('GO_TO_PAGE', $current).'">'.$current.'</a>';
                    }
                }
            }
        }

        # Link To Next Page
        if ($diaply_next && $topPage && $now < $topPage) {
            $u = str_replace('%current_page%', $now+1, $url);
            $pagination[] = '<a href="'.$u.'" title="'.l('GO_TO_NEXT_PAGE').'">&rsaquo;</a>';
        }
        elseif ($diaply_next && $topPage && $now == $topPage) {
            $u = str_replace('%current_page%', $now, $url);
            $pagination[] = '<span class="pagination_now pag_next"><a href="'.$u.'" title="'.l('GO_TO_NEXT_PAGE').'">&rsaquo;</a><span>&rsaquo;</span></span>';
        }

        # Link To Last Page
        if ($display_first && $topPage && $topPage > $now) {
            $u = str_replace('%current_page%', $topPage, $url);
            $pagination[] = '<a href="'.$u.'" title="'.l('GO_TO_LAST_PAGE').'">&raquo;</a>';
        }
        elseif ($display_first && $topPage && $topPage == $now) {
            $u = str_replace('%current_page%', $topPage, $url);
            $pagination[] = '<span class="pagination_now pag_last"><a href="'.$u.'" title="'.l('GO_TO_LAST_PAGE').'">&raquo;</a><span>&raquo;</span></span>';
        }

        return implode(' ', $pagination);
    }

    /**
     * Create <a href element
     *
     * @param  string  $caption
     * @param  string  $href       This won't apply any magic like url(...)
     * @param  string  $attributes 'class="someclass" id="some_id"'
     * @return string
     */
    public static function link($caption, $href, $attributes=false)
    {
        $attributes = $attributes ? ' ' . $attributes : '';
        return '<a href="'.$href.'"'.$attributes.'>'.$caption.'</a>';
    }
}
