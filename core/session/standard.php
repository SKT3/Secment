<?php

	final class SessionStandard extends Session {

		/**
		 * constructor
		 *
		 * @return Session
		 */
		function __construct() {
			// rewritten HTML tags to include session id if transparent sid enabled
			ini_set('url_rewriter.tags', '');
			// name of the handler which is used for storing and retrieving data
			ini_set('session.save_handler', 'files');
			// name of the handler which is used to serialize/deserialize data
			ini_set('session.serialize_handler', 'php');
			// name of the session which is used as cookie name
			ini_set('session.name', self::_SESSION_NAME);
			// specifies if a new session is started automatically on every request
			ini_set('session.auto_start', 0);
			// lifetime of the cookie - until the browser is closed
	        ini_set('session.cookie_lifetime', 0);
	        // manage probability that the garbage collection routine is started
	        ini_set('session.gc_probability', 1);
	        // the probability (gc_probability/gc_divisor) the gc process is started
	        ini_set('session.gc_divisor', 1);
	        // specifies the number of seconds after which data will be seen as 'garbage'
	        ini_set('session.gc_maxlifetime', self::_SESSION_MAXLIFETIME_MINUTES * 60);
			// transparent sid support is disabled
			ini_set('session.use_trans_sid', 0);
	        // path on the server the cookie will be available on
			session_set_cookie_params(null, Config()->COOKIE_PATH);
			// set the current cache limiter - browser caches page state but not entire page
	        session_cache_limiter("private_no_expire, must-revalidate");
			// send modified header for IE 6.0 Security Policy
            header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
			// set client ip and browser
            self::$ip = $_SERVER['REMOTE_ADDR'];
            self::$user_agent = $_SERVER['HTTP_USER_AGENT'];
	        // start the session
	        $this->start();
		}

		/**
		 * Inits the session and regenerates the id (anti session hijacking)
		 *
		 * @return boolean
		 */
		private function start() {
	        if(!self::$started) {
				session_start();
				self::$id = session_id();
				$this->load_vars();
	            if(!$this->is_valid() || !$this->is_session()) {
	            	$this->destroy_session();
	            	$this->start();
	            	return true;
	            }
	            #session_regenerate_id();

	            self::$id = session_id();
	            self::$started = true;
	            // updates the current session array hash
	            $_SESSION = array($this->get_hash() => $this->vars);
	            $this->update_timestamp();
	        }
	        return true;
	    }

		/**
		 * Check if session is valid and active
		 *
		 * @return boolean
		 */
		public function is_session() {
			return !(isset($this->vars['sweboo']['last_updated']) && $this->vars['sweboo']['last_updated'] < (time() - self::_SESSION_MAXLIFETIME_MINUTES * 60));
		}

		/**
		 * Load variables, associated with a particular session key
		 *
		 * @return boolean
		 */
		protected function load_vars() {
			if (isset($_SESSION[$this->get_hash()])) {
				$this->vars = array_merge($this->vars, $_SESSION[$this->get_hash()]);
			}
			return true;
		}

		/**
		 * Update session expiry time, does nothing for permanent sessions
		 *
		 * @param string $key session key (escaped)
		 * @return boolean	status
		 */
		protected function update_timestamp() {
			$this->set('session_id', self::$id);
			return $this->set('sweboo', array_merge($this->get('sweboo'), array('last_updated' => time())));
		}

		/**
		 * Close session
		 *
		 * @return boolean true
		 */
		protected function destroy_session() {
			session_unset();
			session_destroy();
			$this->vars = array();
			unset($_COOKIE[self::_SESSION_NAME]);
			return true;
		}

		/**
		 * Set a session variable
		 *
		 * @param string $name
		 * @param string $value
		 * @return boolean
		 */
		public function set($name, $value) {
			$_SESSION[$this->get_hash()][$name] = $this->vars[$name] = $value;
			return true;
		}

		/**
		 * Retrieves the value of a session variable
		 *
		 * @param string $name
		 * @return mixed
		 */
		public function get($name) {
			return isset($this->vars[$name]) ? $this->vars[$name] : null;
		}

		/**
		 * Unset a session variable
		 *
		 * @param string $name
		 * @return boolean
		 */
		public function del($name) {
			if (!isset($this->vars[$name])) return false;
			unset($_SESSION[$this->get_hash()][$name]);
			unset($this->vars[$name]);
			return true;
		}

	}
?>
