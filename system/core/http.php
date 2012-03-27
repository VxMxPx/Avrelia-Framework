<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * HTTP
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://avrelia.com/license
 * @link       http://avrelia.com
 * @since      Version 0.80
 * @since      Date sre maj 19 23:22:42 2010
 */


class HTTP
{

	/*  ****************************************************** *
	 *          Redirect
	 *  **************************************  */

	/**
	 * Will redirect (if possible/allowed) withour any special status code.
	 * ----
	 * @param string $url -- full url address
	 * @param bool $force -- force redirection, even if is set to _off_ in configurations
	 * ----
	 * @return void
	 */
	public static function Redirect($url, $force=false)
	{
		# Is allowed?
		if (!self::isAllowed($url, $force)) { return false; }

		# Trigger Event Before Redirect
		Event::Trigger('Avrelia.Before.Redirect', $url);

		if(headers_sent($file, $line)) {
			trigger_error("Sorry: Can't redirect to: `{$url}`, since output has already started in file: `{$file}`, on line: `{$line}`.", E_USER_WARNING);
			die();
		}

		header('Expires: Mon, 16 Apr 1984 02:40:00 GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Cache-Control: no-cache, must-revalidate, max-age=0');
		header('Pragma: no-cache');
		header("Location: $url");
		die();
	}
	//-


	/*  ****************************************************** *
	 *          Status Codes
	 *  **************************************  */

	/**
	 * Set costum header code.
	 * ---
	 * @param string $code
	 * ---
	 * @return void
	 */
	public static function StatusCostum($code)
	{
		header($code);
	}
	//-

	/**
	 * Standard response for successful HTTP requests.
	 * ---
	 * @return void
	 */
	public static function Status200_OK()
	{
		header("HTTP/1.1 200 OK");
	}
	//-

	/**
	 * The server successfully processed the request, but is not returning any content.
	 * ---
	 * @return void
	 */
	public static function Status204_NoContent()
	{
		header("HTTP/1.1 204 No Content");
	}
	//-

	/**
	 * This and all future requests should be directed to the given URI.
	 * This method will ignore directive in configurations you must provide *full* URL.
	 * ---
	 * @param string $url
	 * ---
	 * @return void
	 */
	public static function Status301_MovedPermanently($url)
	{
		# Is allowed?
		if (!self::isAllowed($url)) { return false; }

		header("HTTP/1.1 301 Moved Permanently");
		header("Location: {$url}");
		die();
	}
	//-

	/**
	 * In this occasion, the request should be repeated with another URI, but future requests can still use the original URI.
	 * In contrast to 303, the request method should not be changed when reissuing the original request.
	 * For instance, a POST request must be repeated using another POST request.
	 * It will ignore directive in configurations you must provide *full* URL.
	 * ---
	 * @param string $url
	 * ---
	 * @return void
	 */
	public static function Status307_TemporaryRedirect($url)
	{
		# Is allowed?
		if (!self::isAllowed($url)) { return false; }

		header("HTTP/1.1 307 Temporary Redirect");
		header("Location: {$url}");
		die();
	}
	//-

	/**
	 * The request contains bad syntax or cannot be fulfilled.
	 * ---
	 * @param bool $die
	 * ---
	 * @return void
	 */
	public static function Status400_BadRequest($die=true)
	{
		header("HTTP/1.1 400 Bad Request");

		# Will die!
		if ($die) {
			die($die);
		}
	}
	//-

	/**
	 * The request requires user authentication.
	 * ---
	 * @param bool $die
	 * ---
	 * @return void
	 */
	public static function Status401_Unauthorized($die=true)
	{
		header("HTTP/1.1 401 Unauthorized");

		# Will die!
		if ($die) {
			die($die);
		}
	}
	//-

	/**
	 * The request was a legal request, but the server is refusing to respond to it.
	 * Unlike a 401 Unauthorized response, authenticating will make no difference.
	 * ---
	 * @param bool $die
	 * ---
	 * @return void
	 */
	public static function Status403_Forbidden($die=true)
	{
		header("HTTP/1.1 403 Forbidden");
		if ($die) {
			die($die);
		}
	}
	//-

	/**
	 * The requested resource could not be found but may be available again in the future.
	 * Subsequent requests by the client are permissible.
	 * ---
	 * @param string $die -- if not false, the message will be outputed.
	 * ---
	 * @return void
	 */
	public static function Status404_NotFound($die=false)
	{
		header("HTTP/1.0 404 Not Found");
		if ($die) {
			die($die);
		}
	}
	//-

	/**
	 * Indicates that the resource requested is no longer available and will not be available again.
	 * This should be used when a resource has been intentionally removed; however, it is not necessary
	 * to return this code and a 404 Not Found can be issued instead.
	 * Upon receiving a 410 status code, the client should not request the resource again in the future.
	 * Clients such as search engines should remove the resource from their indexes.
	 * ---
	 * @param bool $die
	 * ---
	 * @return void
	 */
	public static function Status410_Gone($die=true)
	{
		header("HTTP/1.0 410 Gone");
		if ($die) {
			die($die);
		}
	}
	//-

	/**
	 * The server is currently unavailable (because it is overloaded or down for maintenance).
	 * Generally, this is a temporary state.
	 * ---
	 * @param bool $die
	 * ---
	 * @return void
	 */
	public static function Status503_ServiceUnavailable($die=true)
	{
		header("HTTP/1.0 503 Service Unavailable");
		if ($die) {
			die($die);
		}
	}
	//-

	/**
	 * Return JSON response and DIE!
	 * ---
	 * @param mixed  $Value    -- The value being encoded. Can be any type except a resource . This function only works with UTF-8 encoded data.
	 * @param int    $options  -- Bitmask consisting of JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP, JSON_HEX_APOS, JSON_FORCE_OBJECT.
	 * ---
	 * @param mixed $Value
	 */
	public static function JsonResponse($Value, $options=0)
	{
		header("Content-type: application/json");
		echo uJSON::Encode($Value, $options);
		die();
	}
	//-

	/**
	 * Check if redirects are allowed at all...
	 *
	 * @param string $url -- for log
	 * @param boolean $force -- was used force?
	 *
	 * @return boolean
	 */
	private static function isAllowed($url, $force=false)
	{
		if (!Cfg::Get('system/allow_redirects', true) && !$force) {
			Log::Add('WAR', "Redirects to `{$url}` failed. Redirects aren't allowed in your config!", __LINE__, __FILE__);
			return false;
		}
		else {
			if ($force) {
				Log::Add('INF', "Redirecting to `{$url}` by force. Redirect isn't allowed in your config, but here it was called by force!", __LINE__, __FILE__);
			}
			return true;
		}
	}
	//-

}
//--
