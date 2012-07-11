<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Dispatcher
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-07-10
 */
class Dispatcher
{
	# Full raw requested URI
	protected $requestUri;

	# Controllers cache
	protected $Controllers;


	/**
	 * Set request URI
	 */
	public function __construct()
	{
		$this->requestUri = trim(Input::GetRequestUri(false), '/');
	}
	//-

	/**
	 * Resolve routes and call appropriate controller
	 * --
	 * @return	void
	 */
	public function boot()
	{
		Event::Trigger('avrelia.before.boot');

		# Do we have any action before regular route is called?
		$this->beforeDispatch();

		# Dispatch
		if (!$this->findUri()) {
			$this->do404();
		}

		# After dispatch
		$this->afterDispatch();

		Event::Trigger('avrelia.after.boot');
	}
	//-

	/**
	 * Check if there's any action which should be executed
	 * before we dispatch.
	 * --
	 * @return	void
	 */
	protected function beforeDispatch()
	{
		if (Cfg::Get('system/routes/<before>', false)) {
			if (!$this->resolveUri(Cfg::Get('system/routes/<before>'))) {
				Log::Add(
					'Before is set in config, but can\'t find method: `'.
					Cfg::Get('system/routes/<before>', false).'`.', 
					'WAR');
			}
		}
	}
	//-

	/**
	 * Check if there's any action which should be executed
	 * after we dispatch.
	 * --
	 * @return	void
	 */
	protected function afterDispatch()
	{
		# Do we have after?
		if (Cfg::Get('system/routes/<after>', false)) {
			if (!$this->resolveUri(Cfg::Get('system/routes/<after>'))) {
				Log::Add(
					'After is set in config, but can\'t find method: `'.
					Cfg::Get('system/routes/<after>', false).'`.', 
					'WAR');
			}
		}
	}
	//-

	/**
	 * Trigger 404 error
	 * --
	 * @return	void
	 */
	protected function do404()
	{
		HTTP::Status404_NotFound();
		Log::Add("We have 404 on `{$this->requestUri}`.", 'INF');

		if (Cfg::Get('system/routes/<404>')) {
			if (!$this->resolveUri(Cfg::Get('system/routes/<404>'))) {
				echo '404: ' . Cfg::Get('system/routes/<404>');
			}
		}
	}
	//-

	/**
	 * Will check current URI
	 * --
	 * @return	boolean
	 */
	protected function findUri()
	{
		# In case we have no uri
		if (empty($this->requestUri)) {
			if (Cfg::Get('system/routes/<index>')) {
				return $this->resolveUri(Cfg::Get('system/routes/<index>'));
			}
			else {
				return false;
			}
		}

		# Loop to check for uri
		$Routes = Cfg::Get('system/routes');

		# Unser all system routes
		unset($Routes['<index>'], $Routes['<404>'], $Routes['<before>'], $Routes['<after>']);

		foreach($Routes as $routeRegEx => $routeCall)
		{
			# Set patterns to empty
			$patterns = null;

			# Resolve route regular expression
			$routeRegEx = $this->resolveRoute($routeRegEx);

			# If route match our current url, then we'll dispatch it
			if (preg_match_all($routeRegEx, $this->requestUri, $patterns, PREG_SET_ORDER)) {
				$Patterns = $patterns[0];
				unset($Patterns[0]);

				# Call route...
				return $this->resolveUri($routeCall, $Patterns);
			}
		}
	}
	//-

	/**
	 * Resolve the route, if it's not the regular expression format yet,
	 * convert it now.
	 * --
	 * @param	string	$route
	 * --
	 * @return	string
	 */
	protected function resolveRoute($route)
	{
		# It means we're having regular expression already
		if (substr($route, 0, 1) === '/') {
			return $route;
		}

		# Split route to pieces
		$route = explode('/', $route);

		# Loop through
		$optional = false; // Which particle is optional?

		foreach ($route as $i => $routeSegment) {
			# Set the particle to be optional
			if (substr($routeSegment, 0, 1) === '?') {
				if ($optional !== false) {
					# It seems we already set one particle to be optional, 
					# so all that follows should be too...
					Log::Add(
						"Segment `{$optional}` was already set to be optional.\n".
						"All segments following that one will be also optional.\n".
						"Setting to optional another (latter) segment `{$i}` is unnecessary.", 'WAR');
				}
				else {
					$optional = $i;
				}

				$routeSegment = substr($routeSegment, 1);
			}

			# Check if we have simple copy pattern
			if (preg_match('/^\<([1-9])\>$/', $routeSegment, $match)) {
				$k = (int) $match[1] - 1;
				if ($k >= $i) {
					trigger_error("Referencing route particle which wasn't set yet: `{$k}` from `{$i}`.", E_USER_ERROR);
				}
				$route[$i] = $route[$k];
				continue;
			}

			# Match all our home-cooked patterns :)
			$routeSegment = preg_replace_callback('/\<(.*?)\>/', array($this, 'resolveRouteHelper'), $routeSegment);
			$route[$i] = $routeSegment;
		}

		$finalPattern = '/^';
		foreach ($route as $i => $routeSegment) 
		{
			if ($optional !== false && $optional <= $i) {
				$finalPattern .= '(?:';
			}
			
			if ($i > 0) {
				$finalPattern .= '\/';
			}
			
			$finalPattern .= '(' . $routeSegment . ')';

			if ($optional !== false && $optional <= $i) {
				$finalPattern .= ')?';
			}
		}

		$finalPattern .= '$/';

		return $finalPattern;
	}
	//-

	/**
	 * Help resolve tags in route <az> etc..
	 * --
	 * @param	array	$match
	 * --
	 * @return	string
	 */
	protected function resolveRouteHelper($match)
	{
		$match = $match[1];

		# If we have [] then just return it
		if (substr($match, 0, 1) === '[') {
			return $match;
		}

		# If we have *
		if ($match === '*') {
			$match = Cfg::Get('system/route_all_tag');
		}
		else {
			# Escape it
			$match = preg_quote($match);

			# First resolve all small all bit letters
			$match = str_replace('aZ', 'a-zA-Z', $match);

			# Resolve numeric ranges
			$match = preg_replace('/(([0-9])([0-9]))/', '$2-$3', $match);
			# Small letters
			$match = preg_replace('/(([a-z])([a-z]))/', '$2-$3', $match);
			# Big letters
			$match = preg_replace('/(([A-Z])([A-Z]))/', '$2-$3', $match);
		}

		return '['.$match.']*';
	}
	//-

	/**
	 * Will resolve particular route (URI)
	 * --
	 * @param	string	$route
	 * @param	array	$uriCapture
	 * --
	 * @return	boolean
	 */
	protected function resolveUri($route, $uriCapture=array())
	{
		# _POST + URI segments
		if (!is_array($uriCapture)) { $uriCapture = array(); }
		if (!is_array($_POST))      { $_POST      = array(); }
		$variables = vArray::Merge($uriCapture, $_POST);

		Log::Add("Route: {$route}, variables: " . print_r($variables, true), 'INF');

		# Get controller
		$routeHelper = vString::ExplodeTrim('->', $route, 2);
		$controller  = $routeHelper[0];
		if (in_array(substr($controller, 0, 1), array(':', '%'))) {
			$controller = $this->resolveParams($controller, $variables);
			$controller = $controller[0];
		}

		# Get method
		$routeHelper = vString::ExplodeTrim('(', $routeHelper[1], 2);
		$method      = $routeHelper[0];
		if (in_array(substr($method, 0, 1), array(':', '%'))) {
			$method = $this->resolveParams($method, $variables);
			$method = $method[0];
		}

		# Get parameters
		$parameters = substr($routeHelper[1], 0, -1);
		# Encode strings
		$parameters = vString::EncodeRegion($parameters, array('"', '"'));
		$parameters = vString::ExplodeTrim(',', $parameters);
		$parameters = vString::DecodeRegion($parameters);

		# Set parameters
		if (!empty($parameters)) {
			$parameters = $this->resolveParams($parameters, $variables);
		}

		# Dispatch now!
		return $this->dispatch($controller, $method, $parameters);
	}
	//-

	/**
	 * Will resolve route parameters
	 * --
	 * @param	mixed	$parameters	string | array
	 * @param	array	$variables
	 * --
	 * @return	array
	 */
	protected function resolveParams($parameters, $variables)
	{
		# Set empty params-values
		$paramsValues = array();

		# If not array
		if (!is_array($parameters)) {
			$parameters = array($parameters);
		}

		foreach ($parameters as $param) {
			# Check if we need to convert it
			$convert = false;
			if (substr($param, 1, 4) === 'str ') { $convert = 'string'; }
			if (substr($param, 1, 4) === 'int ') { $convert = 'integer'; }
			if (substr($param, 1, 5) === 'bool ') { $convert = 'boolean'; }
			if (substr($param, 1, 6) === 'float ') { $convert = 'float'; }

			# Clear convert prefix now
			if ($convert) {
				$param = explode(' ', $param, 2);
				$param = substr($param[0], 0, 1) . $param[1];
			}

			# Check if we have default
			if (strpos($param, '|') !== false) {
				$param = explode('|', $param, 2);
				$default = trim($param[1]);
				$param = trim($param[0]);
				$defaultIsSet = true;

				if (substr($default, 0, 1) === '"') {
					# Default is string
					$default = trim($default, '"');
				}
				elseif (in_array(strtolower($default), array('true', 'false'))) {
					# Default is boolean
					$default = strtolower($default) === 'true' ? true : false;
				}
				elseif (strpos($default, '.')) {
					# Default is float
					$default = (float) $default;
				}
				else {
					# Default is integer
					$default = (int) $default;
				}
			}
			else {
				$defaultIsSet = false;
			}

			# Check if we need date from _POST or URI
			if (substr($param, 0, 1) === '%') {
				$param = (int) substr($param, 1);
			}
			else {
				$param = substr($param, 1);
			}

			# Get actual key
			$currentVal    = false;
			if (isset($variables[$param])) {
				$currentVar = $variables[$param];
			}
			else {
				if ($defaultIsSet) {
					$currentVar = $default;
				}
				else {
					continue;
				}
			}

			# Do we need to convert type?
			if ($convert) {
				switch ($convert) {
					case 'string':
						$currentVar = (string) $currentVar;
						break;
					case 'integer':
						$currentVar = (int) $currentVar;
						break;
					case 'boolean':
						$currentVar = vBoolean::Parse($currentVar);
						break;
					case 'float':
						$currentVar = (float) $currentVar;
						break;
				}
			}

			$paramsValues[] = $currentVar;
		}

		# Return params-values
		return $paramsValues;
	}
	//-

	/**
	 * Call appropriate controller
	 * --
	 * @param	string	$controller	Class name
	 * @param	string	$method		Method's name
	 * @param	array	$params		Those will be send to the destination
	 * --
	 * @return	boolean
	 */
	protected function dispatch($controller, $method, $params=array())
	{
		# Just an informational log entry
		Log::Add("Dispatch: {$controller}->{$method}(), variables: " . print_r($params, true), 'INF');

		# Get object
		$controller = $this->getController($controller.'Controller');

		if (!$controller) {
			return false;
		}

		# Call the function if exists
		if (is_callable(array($controller, $method))) {
			$r = call_user_func_array(array($controller, $method), $params);
			if (Cfg::Get('system/dispatcher_check_response')) {
				return $r === false ? false : true;
			}
			else {
				return true;
			}
		}
		else {
			return false;
		}
	}
	//-

	/**
	 * Get appropriate controller
	 * --
	 * @param	string	$className
	 * --
	 * @return	object	or false
	 */
	protected function getController($className)
	{
		if (!$this->Controllers[$className]) {
			if (!class_exists($className, false)) {
				if (!Loader::GetMC($className, 'controllers')) {
					$this->Controllers[$className] = false;
				}
			}
			
			$this->Controllers[$className] = new $className();
		}
		
		return $this->Controllers[$className];
	}
	//-

	/**
	 * Check if application is off-line
	 * --
	 * @return	void
	 */
	protected function isOffline()
	{
		if (Cfg::Get('system/offline') === true) {
			$message = Cfg::Get('system/offline_message');
			if (substr($message,0,5) == 'view:') {
				$message = View::Get(substr($message,5))->doReturn();
			}
			HTTP::Status503_ServiceUnavailable($message);
		}
	}
	//-
}
//--
