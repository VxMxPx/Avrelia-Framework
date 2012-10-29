<?php namespace Avrelia\Core; if (!defined('AVRELIA')) die('Access is denied!');

/**
 * Http Base Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Http
{
    protected static $headers = '';


    public static function _on_include_()
    {
        self::status_200_ok();
        return true;
    }

    /**
     * Will apply headers.
     * --
     * @return void
     */
    public static function apply_headers()
    {
        if (headers_sent($file, $line)) {
            Log::err("Output was already started: in file : `{$file}`, on line: `{$line}`.");
            return false;
        }

        foreach (self::$headers as $header) {
            header($header);
        }
    }

    /**
     * Add header to the list of existing headers.
     * --
     * @param  mixed $header -- Array or string
     * --
     * @return void
     */
    public static function header($header)
    {
        if (!is_array($header)) { $header = array($header); }

        foreach ($header as $hdr) {
            self::$headers[] = $hdr;
        }
    }

    /**
     * Will replace all headers.
     * --
     * @param  mixed $headers String or array.
     * --
     * @return void
     */
    public static function header_replace($headers)
    {
        if (!is_array($headers)) { $headers = array($headers); }
        self::$headers = $headers;
    }

    /**
     * Return currently set headers as array.
     * --
     * @return array
     */
    public static function as_array()
    {
        return self::$headers;
    }

    /**
     * Will redirect (if possible/allowed) withour any special status code.
     * ---
     * @param   string  $url    Full url address
     * @return  void
     */
    public static function to($url)
    {
        # Is allowed?
        if (!self::_is_allowed($url)) { return false; }

        # Trigger Event Before Redirect
        Event::trigger('/core/http/redirect', $url);

        self::header_replace(array(
            'Expires: Mon, 16 Apr 1984 02:40:00 GMT',
            'Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT',
            'Cache-Control: no-cache, must-revalidate, max-age=0',
            'Pragma: no-cache',
            "Location: $url",
        ));
    }

    /**
     * Standard response for successful HTTP requests.
     * --
     * @return  void
     */
    public static function status_200_ok()
        { self::header_replace("HTTP/1.1 200 OK"); }

    /**
     * The server successfully processed the request, 
     * but is not returning any content.
     * --
     * @return  void
     */
    public static function status_204_no_content()
        { self::header_replace("HTTP/1.1 204 No Content"); }

    /**
     * This and all future requests should be directed to the given URI.
     * This method will ignore directive in configurations you must provide *full* URL.
     * --
     * @param   string  $url
     * @return  void
     */
    public static function status_301_moved_permanently($url)
    {
        # Is allowed?
        if (!self::_is_allowed($url)) { return false; }

        self::header_replace(array(
            'HTTP/1.1 301 Moved Permanently',
            "Location: {$url}"
        ));
    }

    /**
     * In this occasion, the request should be repeated with another URI, 
     * but future requests can still use the original URI.
     * In contrast to 303, the request method should not be changed 
     * when reissuing the original request. For instance, a POST request 
     * must be repeated using another POST request.
     * It will ignore directive in configurations you must provide *full* URL.
     * --
     * @param   string  $url
     * @return  void
     */
    public static function status_307_temporary_redirect($url)
    {
        # Is allowed?
        if (!self::_is_allowed($url)) { return false; }

        self::header_replace(array(
            'HTTP/1.1 307 Temporary Redirect',
            "Location: {$url}"
        ));
    }

    /**
     * The request contains bad syntax or cannot be fulfilled.
     * --
     * @return  void
     */
    public static function status_400_bad_request()
    {
        self::header_replace("HTTP/1.1 400 Bad Request");
    }

    /**
     * The request requires user authentication.
     * --
     * @return void
     */
    public static function status_401_unauthorized()
    {
        self::header_replace("HTTP/1.1 401 Unauthorized");
    }

    /**
     * The request was a legal request, but the server is refusing to respond to it.
     * Unlike a 401 Unauthorized response, authenticating will make no difference.
     * --
     * @return  void
     */
    public static function status_403_forbidden()
    {
        self::header_replace("HTTP/1.1 403 Forbidden");
    }

    /**
     * The requested resource could not be found but may be available again in the future.
     * Subsequent requests by the client are permissible.
     * --
     * @return  void
     */
    public static function status_404_not_found()
    {
        self::header_replace("HTTP/1.0 404 Not Found");
    }

    /**
     * Indicates that the resource requested is no longer available 
     * and will not be available again. This should be used when a resource 
     * has been intentionally removed; however, it is not necessary to return 
     * this code and a 404 Not Found can be issued instead.
     * Upon receiving a 410 status code, the client should not request 
     * the resource again in the future. Clients such as search engines should 
     * remove the resource from their indexes.
     * --
     * @param   string  $message
     * @param   boolean $die
     * @return  void
     */
    public static function status_410_gone()
    {
        self::header_replace("HTTP/1.0 410 Gone");
    }

    /**
     * The server is currently unavailable (because it is overloaded or down 
     * for maintenance). Generally, this is a temporary state.
     * --
     * @return  void
     */
    public static function status_503_service_unavailable()
    {
        self::header_replace("HTTP/1.0 503 Service Unavailable");
    }

    /**
     * Check if redirects are allowed at all...
     * --
     * @param   string  $url    For log
     * @return boolean
     */
    protected static function _is_allowed($url)
    {
        if (!Cfg::get('core/http/allow_redirects', true)) {
            Log::war(
                "Redirects to `{$url}` failed. ".
                "Redirects aren't allowed in config!");
            return false;
        }
    }
}
