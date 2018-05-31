<?php

	final class SessionCounter extends Session {

		/**
		 * Table name wich store all uniquie session ids
		 *
		 * @var string
		 * @access private
		 */
		private $table_sessions;
	
		/**
		 * DB object
		 *
		 * @var object
		 * @access private
		 */
		private $db = null;
		
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
			// sql table prefix
			$prefix = Config()->DB_PREFIX;
			// table names
	        $this->table_sessions = $prefix . 'sessions';
	        // check if DB is connected
	        if (isset(Registry()->db)) {
				$this->db = Registry()->db;
			} else {
				$this->db = Registry()->db = SqlFactory::factory(Config()->DSN);
			}	        
			// start the session
	        $this->start();
			// clean old sessions
	        $this->cleanup();
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
				// check if there is active matching session
				if($this->is_session()) {
					if(!$this->is_valid()) {
						session_regenerate_id();
			            self::$id = session_id();
	                	$this->init_session();
	            	} else {
						session_regenerate_id();
						$new_key = session_id();
						$this->db->update($this->table_sessions, array('session_key' => $new_key), 'session_key = "' . self::$id . '"');
						self::$id = $new_key;
						// updates the current session array hash
						$_SESSION = array($this->get_hash() => $this->vars);
						$this->update_timestamp();
						self::$started = true;
						return true;
	            	}
				} else {
					// initialize new session - in this case is_valid() returns always true 
					// but we need it to populate user data
					if ($this->is_valid()) {
						session_regenerate_id();
			            self::$id = session_id();
	                	$this->init_session();
					}
					self::$started = true;
					return true;				
				}				
	        }
		}		

		/**
		 * Init a new session
		 *
		 * @return true
		 */
		private function init_session() {
			// record session data to sql
			$this->db->insert($this->table_sessions, array('session_key' => self::$id, 'last_active' => time()));
			return true;
		}		
		
		/**
		 * Check if session is valid and active
		 *
		 * @return boolean
		 */
		public function is_session() {
			$session = !(isset($this->vars['sweboo']['last_updated']) && $this->vars['sweboo']['last_updated'] < (time() - self::_SESSION_MAXLIFETIME_MINUTES * 60));
			$rs = $this->db->select($this->table_sessions, 'session_key', 'session_key="' . self::$id . '" AND last_active > ' . (time() - self::_SESSION_MAXLIFETIME_MINUTES * 60));
			return ($session && count($rs) == 1) ? true : false;
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
			$timestamp = time();
			$this->set('sweboo', array_merge($this->get('sweboo'), array('last_updated' => $timestamp)));
			$query  = 'UPDATE ' . $this->table_sessions . ' SET last_active = "' . $timestamp . '" WHERE session_key = "' . self::$id . '"';
			return $this->db->query($query) ? true : false;
			
		}

		/**
		 * Close session
		 *
		 * @return boolean true
		 */
		protected function destroy_session() {
			$query  = 'DELETE FROM ' . $this->table_sessions . ' WHERE session_key = "' . self::$id . '"';
			if($this->db->query($query)) {
				session_unset();
				session_destroy();
				$this->vars = array();
				unset($_COOKIE[self::_SESSION_NAME]);
				return true;
			} else {
				return false;
			}
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
		
		/**
		 * Clean up old sessions - delete session information from the 'session' 
		 * table and table relations should take care of the referenced values 
		 * in the session_vars table
		 *
		 * @return boolean
		 */
		private function cleanup() {
			$query  = "DELETE FROM " . $this->table_sessions . " WHERE last_active < ". (time() - self::_SESSION_MAXLIFETIME_MINUTES * 60);
			return $this->db->query($query) ? true : false;
		}
		
	}

?>