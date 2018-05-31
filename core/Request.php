<?php

class Request {

	/**
	 * Reference to the current instance of the Request object
	 *
	 * @var object
	 * @access private
	 */
	private static $instance = null;

	/**
	 * Current controller
	 *
	 * @var string
	 */
	private $controller = null;

	/**
	 * Current action
	 *
	 * @var string
	 */
	private $action = null;

	/**
	 * Current request parameters
	 *
	 * @var array
	 */
	private $params = array();

	/**
	 * Current action parameters
	 *
	 * @var array
	 */
	private $action_params = array();

	/**
	 * Current get parameters
	 *
	 * @var array
	 */
	private $get = array();

	/**
	 * Current post parameters
	 *
	 * @var array
	 */
	private $post = array();

	/**
	 * Current server parameters
	 *
	 * @var array
	 */
	private $server = array();

	/**
	 * Current url parameters
	 *
	 * @var array
	 */
	private $url_parts = array();

	/**
	 * Request method
	 *
	 * @var string
	 */
	private $method;

	/**
	 * The current port for server connection
	 *
	 * @var int
	 */
	public $port;

	/**
	 * Requested URI
	 * @var string
	 */
	public $request_uri;

	/**
	 * Redirect URL
	 *
	 * @var string
	 */
	public $redirect_url;

	/**
	 * Current URL
	 *
	 * @var string
	 */
	public $url;

	/**
	 * List of associated headers
	 *
	 * @var array
	 */
	private $headers = array();

	/**
	 * Holds an instance to the current session object
	 *
	 * @var object
	 */
	private $session = null;

   	/**
	 * Returns an instance of the Request object
	 *
	 * @return Request
	 */
	static function getInstance() {
		if(is_null(self::$instance)) {
			self::$instance = new Request();
			Registry::getInstance()->request = self::$instance;
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @access private
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Clonning of Request is disallowed.
	 *
	 */
	public function __clone() {
		trigger_error(__CLASS__ . ' can\'t be cloned! It is singleton.', E_USER_ERROR);
	}

   /**
	* Initialization method.
	*
	* Initialization method. Use this via the class constructor.
	*
	* @access public
	* @return void
	*/
	function init() {
		/*$current_php_version = phpversion();
		if(version_compare($current_php_version, '5.3.0', 'lt')) {
			header("HTTP/1.1 307 Temporary Redirect");
			header("Status: 307 Temporary Redirect");
			echo '<h1>Incompatible PHP version</h1>';
			echo '<h3>Version has to be greater or equal to: 5.3.0<br />Current version is: '.$current_php_version.'</h3>';
			die();
		}*/

		$this->method= isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
		$this->get = $_GET;
		$this->post = $_POST;
		$this->server = $_SERVER;
		$this->url_parts = parse_url($_SERVER['REQUEST_URI']);
		$this->params = array_merge($this->get, $this->post);

		$this->request_uri = $_SERVER['REQUEST_URI'];
		// $this->redirect_url = $_SERVER['REDIRECT_URL'];
		$this->redirect_url = isset ($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : $_SERVER['REQUEST_URI'] ;
		$this->port = $_SERVER['SERVER_PORT'];

		$this->fix_gpc_magic();
		$this->url_decode();

		// if (substr(php_sapi_name(), 0, 3) !== 'cgi') {
		// 	$url = $this->redirect_url;
		// } else {
			$url = (strpos($this->server('REQUEST_URI'), '?') === false) ? $this->server('REQUEST_URI') : substr($this->server('REQUEST_URI'), 0, strpos($this->server('REQUEST_URI'), '?'));
		// }
		// var_dump($url = (strpos($this->server('REQUEST_URI'), '?') === false) ? $this->server('REQUEST_URI') : substr($this->server('REQUEST_URI'), 0, strpos($this->server('REQUEST_URI'), '?')));exit;
		$this->url = $url;

		$this->session = $this->session_start();
		$this->headers = $this->getallheaders();
	}

	/**
	 * Get get variable with the specified $name
	 *
	 * @return mixed
	 */
	public function get($name = null) {
		return (isset($name)) ? (isset($this->get[$name]) ? $this->get[$name] : null) : $this->get;
	}

	/**
	 * Get post variable with the specified $name
	 *
	 * @return mixed
	 */
	public function post($name = null) {
		return (isset($name)) ? (isset($this->post[$name]) ? $this->post[$name] : null) : $this->post;
	}

	/**
	 * Get server variable with the specified $name
	 *
	 * @return mixed
	 */
	public function server($name = null) {
		return is_string($name) ? $this->server[$name] : $this->server;
	}

	/**
	 * Get url part variable with the specified $name
	 *
	 * @return mixed
	 */
	public function url_part($name = null) {
		return is_string($name) && array_key_exists($name, $this->url_parts) ? $this->url_parts[$name] : null;
	}


	/**
	 * Set app_system for modules
	 */
	public function set_app_system($name) {
		Registry()->app_system = $name;
	}

	/**
	 * Get the controller name
	 *
	 * @return string
	 */
	public function get_controller() {
		return $this->controller;
	}

	/**
	 * Set controller name for modules
	 */
	public function set_controller($name) {
		$this->controller = $name;
	}

	/**
	 * Get the action name
	 *
	 * @return string
	 */
	public function get_action() {
		return $this->action;
	}

	/**
	 * Set action name for modules
	 */
	public function set_action($name) {
		$this->action = $name;
	}

	/**
	 * It adds an array of parameters on this Request
	 *
	 * @param array parameters, parameters name/value pairs
	 * @return void
	 */
	public function set_params(/*Array*/ $parameters=array()) {
		foreach ($parameters as $name=>$value) {
			$this->params[$name] = $value;
		}
	}

	/**
	 * Return all parameters
	 *
	 * @return array
	 */
	public function get_params() {
		return $this->params;
	}

	/**
	 * Get action params
	 *
	 * @return array
	 */
	public function get_action_params() {
		return $this->action_params;
	}

	/**
	 * Init the session
	 *
	 * @param void
	 * @return void
	 */
	function session_start() {
		require_once(Config()->CORE_PATH.'Session.php');
		Registry()->session = SessionFactory::factory(Config()->SESSION_TYPE);
		// set default language for the site
		if(Registry()->session->get('locale')) {
			Registry()->locale = Registry()->session->get('locale');
		} else {
			Registry()->session->set('locale', Config()->DEFAULT_LOCALE);
			Registry()->locale = Config()->DEFAULT_LOCALE;
		}
		return Registry()->session;
	}

	/**
	 * Get the current protocol
	 *
	 * @return string
	 */
	function get_protocol() {
		return $this->is_ssl() ? 'https://' : 'http://';
	}

	/**
	 * Get current host with port number
	 *
	 * @return string
	 */
	function get_host_and_port() {
		return $this->server['SERVER_NAME'] . ($this->port == 80 ? '' : (((int)$this->port) ? ':'.$this->port : ''));
	}

	/**
	* Is this an SSL request?
	*/
	function is_ssl() {
		return $this->server('HTTPS') && ($this->server('HTTPS') === true || $this->server('HTTPS') == 'on');
	}


	/**
	 * Returns the Session Object
	 *
	 * @return object
	 */
	public function get_session() {
		return $this->session;
	}

	/**
	 * Check if this request was made using POST
	 *
	 * @return bool true if it's a POST
	 */
	public function is_post() {
		return $this->method == 'POST';
	}

	/**
	 * Check if this Request was made using GET
	 *
	 * @return bool true if it was GET
	 */
	public function is_get() {
		return $this->method == 'GET';
	}

	/**
	 * Check if this Request was made with an AJAX call (Xhr)
	 *
	 * @return bool true if it was Xhr
	 */
	public function is_xhr() {
	  return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');
	}

	/**
	* Correct double-escaping problems caused by "magic quotes" in some PHP
	* installations.
	*/
	function fix_gpc_magic() {
		if(get_magic_quotes_gpc() && !defined('SWEBOO_GPC_MAGIC_FIXED')){
			array_walk($_GET, array('Request', 'fix_gpc'));
			array_walk($_POST, array('Request', 'fix_gpc'));
			array_walk($_COOKIE, array('Request', 'fix_gpc'));
			define('SWEBOO_GPC_MAGIC_FIXED',true);
		}
	}

	/**
	 * Correct double-escaping problems caused by "magic quotes"
	 *
	 * @param mixed $item
	 */
	static function fix_gpc(&$item) {
		if (is_array($item)) {
			array_walk($item, array('Request', 'fix_gpc'));
		} else {
			$item = stripslashes($item);
		}
		return $item;
	}

	/**
	 * Decodes the url params
	 *
	 */
	function url_decode() {
		if(!defined('SWEBOO_URL_DECODED')){
			array_walk($_GET, array('Request', 'perform_url_decode'));
			define('SWEBOO_URL_DECODED',true);
		}
	}

	/**
	 * Decodes the url params
	 *
	 */
	static function perform_url_decode(&$item) {
		if (is_array($item)) {
			array_walk($item, array('Request', 'perform_url_decode'));
		} else {
			$item = urldecode($item);
		}
		return $item;
	}

	/**
	 * A wrapper around getallheaders apache function that gets a list
	 * of headers associated with this HTTPRequest.
	 *
	 * @return array
	 */
	private function getallheaders() {
		$headers = array();
		if (function_exists('getallheaders')) {
			// this will work only for mod_php!
			$headers = getallheaders();
		} else {
			foreach($_SERVER as $header => $value) {
			  if(preg_match('/HTTP_(.+)/', $header, $hp)) {
				  $h = preg_replace_callback('/(^|_)(.)/', create_function('$matches', 'return $matches[1] ? "-".ucfirst( $matches[2] ) : ucfirst( $matches[2] );'), strtolower($hp[1]));
				  $headers[$h] = $value;
				}
			}
		}
		return $headers;
	}

   /**
	* Determine originating IP address.  REMOTE_ADDR is the standard
	* but will fail if( the user is behind a proxy.  HTTP_CLIENT_IP and/or
	* HTTP_X_FORWARDED_FOR are set by proxies so check for these before
	* falling back to REMOTE_ADDR.  HTTP_X_FORWARDED_FOR may be a comma-
	* delimited list in the case of multiple chained proxies; the first is
	* the originating IP.
	*/
	function get_remote_ip() {
		if(!is_null($this->server('HTTP_CLIENT_IP'))){
			return $this->server('HTTP_CLIENT_IP');
		}
		if(!is_null($this->server('HTTP_X_FORWARDED_FOR'))){
			foreach ((strstr($this->server('HTTP_X_FORWARDED_FOR'),',') ? split(',',$this->server('HTTP_X_FORWARDED_FOR')) : array($this->server('HTTP_X_FORWARDED_FOR'))) as $remote_ip){
				if($remote_ip == 'unknown' ||
					preg_match('/^((25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.){3}(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])$/', $remote_ip) ||
					preg_match('/^([0-9a-fA-F]{4}|0)(\:([0-9a-fA-F]{4}|0)){7}$/', $remote_ip)
				){
					return $remote_ip;
				}
			}
		}
		return is_null($this->server('REMOTE_ADDR')) ? '' : $this->server('REMOTE_ADDR');

	}

	/**
	 * Recognize a Route Based on the Request.
	 *
	 * @param Request request the Request
	 * @return ActionController
	 * @throws RoutingEception
	 */
	public function recognize() {
		$url = '/'.ltrim($this->url, '/');

		$cp = rtrim(Config()->COOKIE_PATH, '/');

		$url = preg_replace('/^'.preg_quote($cp,'/').'(\/|\?|$)?/', '', $url);
		$url = ltrim($url, '/');

		// test the url agains the available application systems
		$systems = Config()->APPLICATIONS;
		if (isset($systems['default'])) {
			unset($systems['default']);
		}

		$systems_exp = join('|',array_keys($systems));
		if (strlen($systems_exp)) {
			$url_temp = preg_replace( '/^('.$systems_exp.')(\/|\?|$)?/', '', $url);
			if (($url_temp = preg_replace( '/^('.$systems_exp.')(\/|\?|$)?/', '', $url)) !== $url) {
				preg_match( '/^('.$systems_exp.')(\/|\?|$)?/', $url, $matches);
				$system_type = $matches[1];
				$system_default = false;
				$url = $url_temp;
			} else {
				$system_type = 'default';
				$system_default = true;
			}
		} else {
			$system_type = 'default';
			$system_default = true;
		}

		$app_system = Config()->APPLICATIONS[$system_type];
		Registry()->{'is_'.$app_system} = true;
		Registry()->app_system = $app_system;
		Registry()->app_system_url = $system_type;
		Registry()->app_is_default = $system_default;

		// find out the current locale and strip it
		preg_match('/^\/?(\w+)\/?/', $url, $locale_matches);
		$locale_shortcuts = Config()->LOCALE_SHORTCUTS;
		if(isset($locale_matches[1]) && isset($locale_shortcuts[$locale_matches[1]])) {
			$url = preg_replace('/^\/?'.$locale_matches[1].'/', '', $url);
			Registry()->locale = $locale_shortcuts[$locale_matches[1]];
		}


		// store the current locale in the session if changed
		if(Registry()->session->locale !== Registry()->locale) {
			Registry()->session->locale = Registry()->locale;
		}

		// load the router
		Router::getInstance()->load_routes();


		// parse the url and route it
		if(Router()->routes_count > 0) {
			$route = Router()->extract_params($url);

			//  extract_params() returns an array if it finds a path that
			//  matches the URL, null if no match found
			if(is_array($route)) {
				//  Matching route found.  Try to get
				//  controller and action from route
				if (isset($route['controller']) && $route['controller'] != '') {
					$controller = $route['controller'];
				} else if ($route['controller'] == '') {
					$controller = Registry()->app_system;

				}

				if ($controller == 'public') {
					$controller = 'application';
				}

				// Find the action from the route
				if(isset($route['action']) && $route['action'] != '') {
					$action = $route['action'];
				} else {
					$action = 'index';
				}

				// Get the rest of the parameters from the route
				$action_params = array_diff_key($route, array('controller' => '', 'action' => ''));

				// quick shortcut to the id parameter
				$id = null;
				if(isset($action_params['id'])) {
					$id = $action_params['id'];
					Registry()->item_id = $id;
				}

				// Parse the query string if there is one
				$action_params = array_merge($action_params, $this->get());

				// Set the vars in the request object
				$this->controller = $controller;
				$this->action = $action;

				if(array_key_exists('module', $route) && $route['module']!='') {
					$this->controller = $route['module'];
					$this->action = $route['maction'];
				}

				if(array_key_exists('app', $route) && $route['app']!='') {
					Registry()->in_app = 1;
				}
				$this->action_params = $action_params;
				return $this->controller;
			} // end if(is_array($route))

		} // end if
	}

}

?>