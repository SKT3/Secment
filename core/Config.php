<?php

	/**
	 * DefaultConfig Class
	 *
	 * @package Sweboo
	 */
	final class DefaultConfig {

		/**
		 * Directory separator -> \ -> windows, / -> linux
		 *
		 * @var string
		 * @access private
		 */
		private $DIR_SEP = DIRECTORY_SEPARATOR;

		/******************************************************************
		 * PUBLIC and LOCAL PATHS
		 ******************************************************************/

		/**
		 * Base url of the application on the server.
		 *
		 * @var string
		 * @access private
		 */
		private $BASE_URL;

		/**
		 * Local path of the application on the server.
		 *
		 * @var string
		 * @access private
		 */
		private $ROOT_PATH;

		/**
		 * Local path of the freamework core on the server.
		 *
		 * @var string
		 * @access private
		 */
		private $CORE_PATH;

		/**
		 * Local path of configuration.
		 *
		 * @var string
		 * @access private
		 */
		private $CONFIG_PATH;

		/**
		 * Local path of the application library on the server.
		 *
		 * @var string
		 * @access private
		 */
		private $LIB_PATH;

		/**
		 * Local path of the application controllers on the server.
		 *
		 * @var string
		 * @access private
		 */
		private $CONTROLLERS_PATH;

		/**
		 * Local path of the application models on the server.
		 *
		 * @var string
		 * @access private
		 */
		private $MODELS_PATH;

		/**
		 * Local path of the application helpers on the server.
		 *
		 * @var string
		 * @access private
		 */
		private $HELPERS_PATH;

		/**
		 * Local path of the application locales on the server.
		 *
		 * @var string
		 * @access private
		 */
		private $LOCALES_PATH;

		/**
		 * Local path of the application modules on the server.
		 *
		 * @var string
		 * @access private
		 */
		private $MODULES_PATH;

		/******************************************************************
		 * TEMPLATE SETTINGS
		 ******************************************************************/

		/**
		 * Local path of the application templates on the server.
		 *
		 * @var string
		 * @access private
		 */
		private $VIEWS_PATH;

		/**
		 * Local path of the application compiled templates on the server.
		 *
		 * @var string
		 * @access private
		 */
		private $VIEWS_COMPILE_PATH;

		/**
		 * Local path of the application cached templates on the server.
		 *
		 * @var string
		 * @access private
		 */
		private $VIEWS_CACHE_PATH;

		/**
		 * Local path of the application layouts on the server.
		 *
		 * @var string
		 * @access private
		 */
		private $LAYOUTS_PATH;

		/**
		 * Public path of the the cookie.
		 *
		 * @var string
		 * @access private
		 */
		private $COOKIE_PATH;

		/**
		 * Local path of the files for all uploaded files.
		 *
		 * @var string
		 * @access private
		 */
		private $FILES_ROOT;

		/**
		 * Public path of the files for all uploaded files.
		 *
		 * @var string
		 * @access private
		 */
		private $FILES_URL;

		/**
		 * Local root path of the application uploaded files on the server by rich editor.
		 *
		 * @var string
		 * @access private
		 */
		private $UPLOADED_IMAGES_ROOT;

		/**
		 * Public root path of the application uploaded files on the server by rich editor.
		 *
		 * @var string
		 * @access private
		 */
		private $UPLOADED_IMAGES_URL;

		/**
		 * Public root url of the application.
		 *
		 * @var string
		 * @access private
		 */
		private $PUBLIC_URL;

		/**
		 * Reference to the current instance of the Config object
		 *
		 * @var object
		 * @access private
		 */
		private static $instance = null;

		/**
		 * Set all variables
		 *
		 * @access private
		 */
		private function __construct() {
			$this->ROOT_PATH = str_replace( '\\', '/', dirname( dirname( __FILE__ ) ) ) . '/';
			$this->CORE_PATH = $this->ROOT_PATH . 'core/';
			$this->CONFIG_PATH = $this->ROOT_PATH . 'config/';
			$this->LIB_PATH = $this->ROOT_PATH . 'lib/';
			$this->CONTROLLERS_PATH = $this->ROOT_PATH . 'apps/controllers/';
			$this->MODELS_PATH = $this->ROOT_PATH . 'apps/models/';
			$this->HELPERS_PATH = $this->ROOT_PATH . 'apps/helpers/';
			$this->LOCALES_PATH = $this->ROOT_PATH . 'locales/';
			$this->MODULES_PATH = $this->ROOT_PATH . 'apps/modules/';
			$this->LAYOUTS_PATH = $this->ROOT_PATH . 'apps/views/layouts/';
			$this->VIEWS_PATH = $this->ROOT_PATH . 'apps/views';
			$this->VIEWS_COMPILE_PATH = $this->ROOT_PATH . 'cache/smarty/compiled';
			$this->VIEWS_CACHE_PATH = $this->ROOT_PATH . 'cache/smarty/cache';

			$this->COOKIE_PATH = str_replace(rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/'), '', $this->ROOT_PATH );

			$this->FILES_ROOT = $this->ROOT_PATH . 'web/files/';

			// ADD BASE URL functionality for getting the current domain
			if(isset($_SERVER['HTTPS'])){
				$protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http";
			}
			else{
				$protocol = 'http';
			}
			$this->BASE_URL = $protocol . "://" . $_SERVER['HTTP_HOST'];

			$this->PUBLIC_URL = $this->COOKIE_PATH . 'web/';
			$this->FILES_URL = $this->PUBLIC_URL . 'files/';
			$this->MODULES_URL = $this->BASE_URL . $this->COOKIE_PATH . 'modules/';

			$this->UPLOADED_IMAGES_ROOT = $this->FILES_ROOT . 'richeditor/';
			$this->UPLOADED_IMAGES_URL = $this->PUBLIC_URL . 'files/richeditor/';

			$this->SYSTEM_ROOT_PATH = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
			$this->SYSTEM_CORE_PATH = $this->SYSTEM_ROOT_PATH . 'core' . DIRECTORY_SEPARATOR;
			$this->SYSTEM_LIB_PATH = $this->SYSTEM_ROOT_PATH . 'lib' . DIRECTORY_SEPARATOR;
			$this->SYSTEM_VIEWS_PATH = $this->SYSTEM_ROOT_PATH . 'apps' .DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;
			$this->SYSTEM_FILES_ROOT = $this->SYSTEM_ROOT_PATH . 'web' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR;

			require_once($this->CONFIG_PATH . 'Configuration.php');

			$vars = get_object_vars(new Config);
			foreach($vars as $var=>$value) {
				$this->$var = $value;
			}
			return true;
		}

		/**
		 * Returns an instance of the config object
		 *
		 * @static
		 * @final
		 * @return Config
		 *
		 */
		static final function getInstance() {
			if ( self::$instance == null ) {
				self::$instance = new DefaultConfig();
			}
			return self::$instance;
		}

		/**
		 * Magic method. Returns a value to some private value
		 *
		 * @throws UndefinedVariable, if $key isnt found
		 * @param string $key
		 * @return mixed
		 */
		function __get($key) {
			if (!isset($this->$key)) {
				throw new UndefinedVariable(__CLASS__, $key);
			}
			return $this->$key;
		}
	}

	/**
	 * For easier access to Config
	 *
	 * @return Config
	 */
	function Config() {
		return DefaultConfig::getInstance();
	}

?>