<?php

	class Registry
	{
		/**
		 * Reference to the current instance of the Registry object
		 *
		 * @var object
		 * @access private
		 */
		private static $instance = null;
	
		/**
		 * Keeps all object references
		 *
		 * @var array
		 * @access private
		 */
	    private $store = array();
	
		/**
		 * Keeps current controller object reference
		 *
		 * @var object instance of BaseController
		 * @access private
		 */
		private $controller = null;
	
		/**
		 * Constructor, does nothing
		 *
		 * @access private
		 */
	    private function __construct() {}
	
		/**
		 * Clonning of Registry is disallowed.
		 *
		 */
		public function __clone() {
			trigger_error(__CLASS__ . ' can\'t be cloned! It is singleton.', E_USER_ERROR);
		}
	
	    /**
		 * Returns an instance of the registry object
		 *
		 * @return Registry
		 */
		public static function getInstance() {
	        if(self::$instance == null) {
	            self::$instance = new Registry();
	        }
	        return self::$instance;
	    }
	
	    /**
		 * Magic method. Alias of set
		 */
	    public function __set($label, $object) {
	    	$this->set($label, $object);
	    }
	
	    /**
		 * Registers an object with the registry
		 *
		 * @access public
		 * @param string $label
		 * @param object $object
		 */
	    public function set($label, &$object) {
	    	$this->store[$label] = &$object;
	    }
	
	    /**
		 * Magic method. Unregisters an object from the registry
		 *
		 * @param string $label
		 */
	    public function __unset($label) {
	        if(isset($this->store[$label])) {
	            unset($this->store[$label]);
	        }
	    }
	
	    /**
		 * Magic method. Returns a reference to an object in the registry
		 *
		 * @param string $label
		 * @return object
		 */
	    public function __get($label) {
	        if(isset($this->store[$label])) {
	            return $this->store[$label];
	        }
	        return false;
	    }
	
	    /**
		 * Checks if there's an object registered under a specific label
		 *
		 * @param string $label
		 * @return boolean
		 */
	    public function __isset($label) {
	        if(isset($this->store[$label])) {
	            return true;
	        }
	        return false;
	    }
	}
	
	/**
	 * For easier access to Registry
	 */
	function Registry()
	{
		return Registry::getInstance();
	}

?>