<?php

	abstract class Session {

		/**
	     *  Name of the session (used as cookie name).
	     */
	    const _SESSION_NAME = "THEMAGSSID";

	    /**
	     *  After this number of minutes, stored data will be seen as
	     *  'garbage' and cleaned up by the garbage collection process.
	     */
	    const _SESSION_MAXLIFETIME_MINUTES = 60;

	    /**
	     *  Session id
	     *  @var string
	     */
	    protected static $id = null;

	    /**
	     *  IP Address of client
	     *  @var string
	     */
	    protected static $ip = null;

	    /**
	     *  User Agent (OS, Browser, etc) of client
	     *  @var string
	     */
	    protected static $user_agent = null;

	    /**
	     *  Session started
	     *  @var boolean
	     */
	    protected static $started = false;

		/**
		 * All stored session variables
		 *
		 * @var string[]
		 */
		protected $vars = array();

	    /**
	     *  Check if session is valid (anti session hijacking)
	     *
	     *  @return boolean
	     */
	    function is_valid() {
	    	if (isset($this->vars['sweboo']['ip']) && isset($this->vars['sweboo']['user_agent'])) {
				if (self::$ip == $this->vars['sweboo']['ip'] && self::$user_agent == $this->vars['sweboo']['user_agent']) {
					return true;
				} else {
					return false;
				}
    		} else {
    			$this->vars['sweboo']['ip'] = self::$ip;
    			$this->vars['sweboo']['user_agent'] = self::$user_agent;
    			return true;
    		}
	    }


	    /**
	     * Get key that uniquely identifies this session by calculating
	     * a unique session key based on the session id and user agent, plus
	     * the user's IP address used for anti session hijacking
		 *
		 * @return string
	     */
	    protected function get_hash() {
	        $key = self::$id . self::$user_agent . self::$ip;
	        return md5($key);
	    }

		/**
		 * Check if is set some variable
		 *
		 * @param string $name
		 * @return boolean
		 */
		function __isset($name) {
			return isset($this->vars[$name]);
		}

		/**
		 * Alias of {@link Session::del}
		 *
		 * @param 	string 		$name
		 * @return 	boolean
		 */
		function __unset($name) {
			return $this->del($name);
		}

		/**
		 * Provides an easier way of retrieving session variables
		 *
		 * Alias of {@link Session::get}
		 *
		 * @param string $name
		 * @return string
		 */
		function __get($key) {
			if (isset($this->vars[$key])) return $this->get($key);
			return $this->{$key};
		}

		/**
		 * Provides an easier way of setting session variables
		 *
		 * Alias of {@link Session::set}
		 *
		 * @param string $key
		 * @param string $value
		 * @return boolean
		 */
		function __set($key, $value) {
			return $this->set($key, $value);
		}

		/**
		 * Check if session is valid and active
		 *
		 * @return boolean
		 */
		abstract function is_session();

		/**
		 * Generate unique session key
		 *
		 * @return string $key
		 */
		protected function session_regenerate_id() {
	    	$systemtime = gettimeofday();
	    	$buffer = sprintf("%.15s%ld%ld%0.8f", $_SERVER['REMOTE_ADDR'], $systemtime['sec'], $systemtime['usec'], $this->get_php_combined_lcg() * 10);
	    	return md5($buffer . self::_SESSION_NAME . time());
		}

		/**
		* Generates a php combined lcg
		*
		* @return float
		*/
		private function get_php_combined_lcg(){
			$systemtime = gettimeofday();
			$lcg['s1'] = $systemtime['sec'] ^ (~$systemtime['usec']);
			$lcg['s2'] = rand(1, 20000);
			$var1 = (int) ($lcg['s1'] / 53668);
			$lcg['s1'] = (int) (40014 * ($lcg['s1'] - 53668 * $var1) - 12211 * $var1);
			if ($lcg['s1'] < 0) $lcg['s1'] += 2147483563;
			$var1 = (int) ($lcg['s2'] / 52774);
			$lcg['s2'] = (int) (40692 * ($lcg['s2'] - 52774 * $var1) - 3791 * $var1);
			if ($lcg['s2'] < 0) $lcg['s2'] += 2147483399;
			$var2 = (int) ($lcg['s1'] - $lcg['s2']);
			if ($var2 < 1) $var2 += 2147483562;
			return $var2 * 4.656613e-10;
		}

		/**
		 * Load variables, associated with a particular session key
		 *
		 * @return boolean
		 */
		abstract protected function load_vars();

		/**
		 * Update session expiry time, does nothing for permanent sessions
		 *
		 * @return boolean					status
		 */

		abstract protected function update_timestamp();

		/**
		 * Destroy session
		 *
		 * @return boolean
		 */
		abstract protected function destroy_session();

		/**
		 * Set a session variable
		 *
		 * @param string $name
		 * @param string $value
		 * @return boolean
		 */
		abstract function set($name, $value);

		/**
		 * Retrieves the value of a session variable
		 *
		 * @param string $name
		 * @return mixed
		 */
		abstract function get($name);

		/**
		 * Unset a session variable
		 *
		 * @param string $name variable name
		 * @return boolean true/false
		 */
		abstract function del($name);
	}

	class SessionFactory {

		/**
		 * Holds current Session object handle
		 *
		 * @var unknown_type
		 */
		public static $session;

		/**
		 * Creates new Session handler
		 *
		 * @param string $type
		 * @return object
		 */
		public static function factory($type) {
			if(!is_object(self::$session)) {
				switch($type) {
					case 'standard':
						require_once(Config()->CORE_PATH.'session/standard.php');
						self::$session = new SessionStandard;
						break;
					case 'db':
						require_once(Config()->CORE_PATH.'session/db.php');
						self::$session = new SessionDb;
						break;
					case 'redis':
						require_once(Config()->CORE_PATH.'session/redis.php');
						self::$session = new SessionRedis;
						break;
					default:
						throw new SwebooException('Unrecognized Session Type: <strong>' . $type . '</strong>');
				}
			}
			return self::$session;
		}
	}

?>