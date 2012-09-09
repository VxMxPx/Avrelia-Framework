<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Curl Class
 * ----
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class cCURL
{
    # CURL handler
    private $handler = false;

    /**
     * Init curl
     * @param string $url
     */
    public function __construct($url=null)
    {
        $this->handler = curl_init($url);
    }

    /**
     * Get url content and response headers. Given a url, follows all 
     * redirections on it and returned content and response headers of final url.
     * http://www.php.net/manual/en/ref.curl.php#93163
     * 
     * @param  string   $url
     * @param  integer  $js_loop
     * @param  integer  $timeout
     * @return array[0] content
     *         array[1] array of response headers
     */
    public function get($url=false, $js_loop=0, $timeout=5)
    {
        $url = str_replace('&amp;', '&', urldecode(trim($url)));

        $cookie = tempnam('/tmp', 'CURLCOOKIE');
        curl_setopt(
            $this->handler, 
            CURLOPT_USERAGENT, 
            'Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1');
        
        if ($url) 
            { curl_setopt($this->handler, CURLOPT_URL, $url); }

        curl_setopt($this->handler, CURLOPT_COOKIEJAR, $cookie);
        curl_setopt($this->handler, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->handler, CURLOPT_ENCODING, '');
        curl_setopt($this->handler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->handler, CURLOPT_AUTOREFERER, true );
        curl_setopt($this->handler, CURLOPT_SSL_VERIFYPEER, false); # required for https urls
        curl_setopt($this->handler, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($this->handler, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($this->handler, CURLOPT_MAXREDIRS, 10);

        $content  = $this->exec();
        $response = $this->get_info();

        if ($response['http_code'] == 301 || $response['http_code'] == 302) {
            ini_set(
                "user_agent", 
                'Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) '.
                'Gecko/20041001 Firefox/0.10.1');

            if ($headers = get_headers($response['url'])) {
                foreach($headers as $value) {
                    if (substr(strtolower($value), 0, 9) == "location:") {
                        return $this->get(trim(substr($value, 9, strlen($value))));
                    }
                }
            }
        }

        if (
            (preg_match("/>[[:space:]]+window\.location\.replace\('(.*)'\)/i", $content, $value) 
                || preg_match("/>[[:space:]]+window\.location\=\"(.*)\"/i", $content, $value)) 
            && $js_loop < 5
        ) {
            return $this->get($value[1], $js_loop+1);
        }
        else {
            return array($content, $response);
        }
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
    {
        return curl_exec($this->handler);
    }

    /**
     * Get information regarding a specific transfer
     * ---
     * @param integer $opt
     *                For available options see: 
     *                http://www.php.net/manual/en/function.curl-getinfo.php
     * @return mixed  String or Array
     */
    public function get_info($opt=0)
    {
        return curl_getinfo($this->handler, $opt);
    }

    /**
     * Set an option for a cURL transfer.
     * For available options see: http://www.php.net/manual/en/function.curl-setopt.php
     * --
     * @param  integer $opt
     * @param  mixed   $value
     * @return boolean
     */
    public function set_opt($opt, $value)
    {
        return curl_setopt($this->handler, $opt, $value);
    }

    /**
     * Will close curl
     * ---
     * @return void
     */
    public function __destruct()
    {
        curl_close($this->handler);
    }
}
