<?php namespace Avrelia\Plug; if (!defined('AVRELIA')) die('Access is denied!');

use Avrelia\Core\Plug as Plug;
use Avrelia\Core\Cfg  as Cfg;

/**
 * Curl Class
 * ----
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Curl
{
    # CURL handler
    protected $handler = false;

    # User's agent
    protected $user_agent = null;

    # Default timeout used in ::get and ::post
    protected static $timeout = 0;

    # Cookies location used in ::get and ::post
    protected static $cookie_file = null;

    /**
     * Set default timeout
     * @return boolean
     */
    public static function _on_include_()
    {
        Plug::get_config(__FILE__);
        self::$timeout = Cfg::get('plugs/curl/timeout');
        self::$cookie_file = Cfg::get('plugs/curl/cookie_file');

        return true;
    }

    /**
     * Init curl
     * @param string $url
     */
    public function __construct($url)
    {
        $this->handler    = curl_init($url);
        $this->user_agent = Cfg::get('plugs/curl/user_agent');
    }

    /**
     * Get url content and response headers. Given a url, follows all 
     * redirections on it and returned content and response headers of final url.
     * http://www.php.net/manual/en/ref.curl.php#93163
     * 
     * @param  string   $url
     * @return array[0] content
     *         array[1] array of response headers
     */
    public static function get($url)
    {
        $url    = str_replace('&amp;', '&', urldecode(trim($url)));
        $curl   = new Curl($url);

        $curl->set_opt(CURLOPT_USERAGENT,      $curl->user_agent);
        $curl->set_opt(CURLOPT_COOKIEJAR,      self::$cookie_file);
        $curl->set_opt(CURLOPT_COOKIEFILE,     self::$cookie_file);
        $curl->set_opt(CURLOPT_FOLLOWLOCATION, true);
        $curl->set_opt(CURLOPT_RETURNTRANSFER, true);
        $curl->set_opt(CURLOPT_AUTOREFERER,    true);
        $curl->set_opt(CURLOPT_SSL_VERIFYPEER, false); # Required for https urls
        $curl->set_opt(CURLOPT_CONNECTTIMEOUT, self::$timeout);
        $curl->set_opt(CURLOPT_TIMEOUT,        self::$timeout);
        $curl->set_opt(CURLOPT_MAXREDIRS,      10);

        $content  = $curl->exec();
        $response = $curl->get_info();

        unset($curl);
        return array(
            $content,
            $response
        );
    }

    /**
     * Post to url, get content and reposnse headers.
     * @param  string $url
     * @param  array $post
     * @return array[0] content
     *         array[1] array of response headers
     */
    public static function post($url, $post)
    {
        $url = str_replace('&amp;', '&', urldecode(trim($url)));

        $curl = new Curl($url);
        $curl->set_opt(CURLOPT_COOKIEJAR,      self::$cookie_file);
        $curl->set_opt(CURLOPT_COOKIEFILE,     self::$cookie_file);
        $curl->set_opt(CURLOPT_RETURNTRANSFER, true);
        $curl->set_opt(CURLOPT_POST,           true);
        $curl->set_opt(CURLOPT_POSTFIELDS,     $post);

        $curl->set_opt(CURLOPT_USERAGENT,      $curl->user_agent);
        $curl->set_opt(CURLOPT_ENCODING,       '');
        $curl->set_opt(CURLOPT_AUTOREFERER,    true);
        $curl->set_opt(CURLOPT_SSL_VERIFYPEER, false); # required for https urls
        $curl->set_opt(CURLOPT_CONNECTTIMEOUT, self::$timeout);
        $curl->set_opt(CURLOPT_TIMEOUT,        self::$timeout);

        $return = array(
            $curl->exec(),
            $curl->get_info()
        );
        unset($curl);

        return $return;
    }

    /**
     * Execute current curl request.
     * Returns TRUE on success or FALSE on failure.
     * However, if the CURLOPT_RETURNTRANSFER option is set, it will return the
     * result on success, FALSE on failure.
     * 
     * @return mixed
     */
    public function exec()
        { return curl_exec($this->handler); }

    /**
     * Get information regarding a specific transfer
     * ---
     * @param integer $opt
     *                For available options see: 
     *                http://www.php.net/manual/en/function.curl-getinfo.php
     * @return mixed  String or Array
     */
    public function get_info($opt=0)
        { return curl_getinfo($this->handler, $opt); }

    /**
     * Set an option for a cURL transfer.
     * For available options see: http://www.php.net/manual/en/function.curl-setopt.php
     * --
     * @param  integer $opt
     * @param  mixed   $value
     * @return boolean
     */
    public function set_opt($opt, $value)
        { return curl_setopt($this->handler, $opt, $value); }

    /**
     * Will close curl
     * ---
     * @return void
     */
    public function __destruct()
        { curl_close($this->handler); }
}
