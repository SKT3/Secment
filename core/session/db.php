<?php

	/* Use following code to create DB Tables
	 
		CREATE TABLE IF NOT EXISTS `sessions` (
			`session_key` varchar(32) NOT NULL,
			`last_active` int(11) NOT NULL,
			PRIMARY KEY (`session_key`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		
		CREATE TABLE IF NOT EXISTS `session_vars` (
			`session_key` varchar(32) NOT NULL,
			`private_key` varchar(32) NOT NULL,
			`name` varchar(255) NOT NULL,
			`value` text NOT NULL,
			KEY `session_key` (`session_key`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		
		ALTER TABLE `session_vars`  ADD CONSTRAINT `session_vars_ibfk_1` FOREIGN KEY (`session_key`) REFERENCES `session_vars` (`session_key`) ON DELETE CASCADE ON UPDATE CASCADE;
	*/
	final class SessionDb extends Session {
	
		/**
		 * Table name wich store all uniquie session ids
		 *
		 * @var string
		 * @access private
		 */
		private $table_sessions;
	
		/**
		 * Table name wich store all session variables
		 *
		 * @var string
		 * @access private
		 */
		private $table_session_vars;

		/**
		 * DB object
		 *
		 * @var object
		 * @access private
		 */
		private $db = null;
		
		/**
		 * Constructor
		 *
		 * @return Session
		 */
		function __construct() {
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
	        $this->table_session_vars = $prefix . 'session_vars';
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
			if (!self::$started) {
				self::$id = $this->db->escape($_COOKIE[self::_SESSION_NAME]);
				// check if there is active matching session
				if($this->is_session()) {
					// load the variables of the session
					$this->load_vars();
					if(!$this->is_valid()) {
						$this->destroy_session();
						self::$id = $this->session_regenerate_id();
	                	$this->init_session();
	            	}
	            	// regenerate session id - change the session key and hash into the db, and set new cookie
	            	// (do we really need it? if so - uncomment following lines)
	            	// $new_key = $this->session_regenerate_id();
	            	// $this->db->update($this->table_sessions, array('session_key' => $new_key), 'session_key = "' . self::$id . '"');
	            	// self::$id = $new_key;
	            	// $this->db->update($this->table_session_vars, array('private_key' => $this->get_hash()), 'session_key = "' . self::$id . '"');
					// setcookie(self::_SESSION_NAME, self::$id, false, Config()->COOKIE_PATH);
					
					// update timestam
	            	$this->update_timestamp();
	            	self::$started = true;
	            	return true;
				}
				// initialize new session - in this case is_valid() returns always true 
				// but we need it to populate user data
				if ($this->is_valid()) {
					self::$id = $this->session_regenerate_id();
					$this->init_session();
				}
				self::$started = true;
				return true;
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
			// records user data into session
			$this->set('sweboo', $this->vars['sweboo']);
			// cookies
			setcookie(self::_SESSION_NAME, self::$id, false, Config()->COOKIE_PATH);
			return true;
		}
	
		/**
		 * Check if session exists and is active
		 *
		 * @return boolean
		 */
		public function is_session() {
			$rs = $this->db->select($this->table_sessions, 'session_key', 'session_key="' . self::$id . '" AND last_active > ' . (time() - self::_SESSION_MAXLIFETIME_MINUTES * 60));
			return (count($rs) == 1) ? true : false;
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
	
		/**
		 * Load variables
		 *
		 * @return boolean
		 */
		protected function load_vars() {
			$this->vars = array();
			$query  = 'SELECT name, value FROM ' . $this->table_session_vars . ' WHERE session_key = "' . self::$id . '" AND private_key = "' . $this->get_hash() . '"';
			$result = $this->db->query($query);
			foreach ($result as $row) {
				$this->vars[$row->name] = unserialize($row->value);
			}
			return true;
		}
	
		/**
		 * Update session expiry time, does nothing for permanent sessions
		 *
		 * @return boolean
		 */
		protected function update_timestamp() {
			$query  = 'UPDATE ' . $this->table_sessions . ' SET last_active = "' . time() . '" WHERE session_key = "' . self::$id . '"';
			return $this->db->query($query) ? true : false;
		}
	
		/**
		 * Close session
		 *
		 * @return boolean
		 */
		protected function destroy_session() {
			$query  = 'DELETE FROM ' . $this->table_sessions . ' WHERE session_key = "' . self::$id . '"';
			if($this->db->query($query)) {
				$this->vars = array();
				self::$id = null;
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
			// overwrite old value if the variable exists
			if(isset($this->vars[$name])) $this->del($name, $key);
			$this->db->insert($this->table_session_vars, array('session_key' => self::$id, 'private_key' => $this->get_hash(), 'name' => $name, 'value' => serialize($value)));
			$this->vars[$name] = $value;
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
			return $this->db->delete($this->table_session_vars, array('session_key' => self::$id, 'private_key' => $this->get_hash(), 'name' => $name));
		}
		
	}
?>
