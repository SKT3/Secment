<?php

	/**
	 * Localizer class
	 *
	 * @package Sweboo
	 */
	class Localizer
	{
		/**
		 * Reference to the current instance of the Request object
		 *
		 * @var object
		 * @access private
		 */
		private static $instance = array();

		/**
		 * Current controller
		 *
		 * @var string
		 */
		private $controller = null;

		/**
		 * Labels container
		 *
		 * @var array
		 */
		private $labels = array();

		/**
		 * Constructor
		 *
		 * @param string $controller_name
		 */
		private function __construct($controller_name){
			$this->controller = $controller_name;
		}

	   	/**
		 * Returns an instance of the Localizer object
		 *
		 * @param string $controller_name
		 * @return Localizer
		 */
	    static function getInstance($controller_name) {
	    	if (empty(self::$instance)) {
	    		require_once(Config()->LIB_PATH . 'yaml/yaml.php');
	    	}
	    	if(is_null(self::$instance[$controller_name])) {
	    		self::$instance[$controller_name] = new Localizer($controller_name);
	    		Registry::getInstance()->localizer = self::$instance[$controller_name];
	    	}

	    	return self::$instance[$controller_name];
	    }

	    /**
	     * Load i18n label locales for given locale
	     *
	     * @param unknown_type $locale
	     */
		public function load($locale) {
			if(!Registry()->app_is_default) {
				$locale = 'bg-BG';
			}
			$file = Config()->ROOT_PATH.'cache/locales/'.$locale.'/'.Registry()->app_system.'_'.$this->controller.'.cache';
			if(!Config()->DEVELOPMENT && is_file($file)) {
				$_cached_labels = array();
				include_once($file);
				$labels = $_cached_labels;
			} else {
				$locales_path = Config()->LOCALES_PATH;
				require_once(Config()->LIB_PATH.'Files.php');

				$global_locales = $locales_path . '/' . $locale . '/' . '_global.yaml';
				$labels_locales = $locales_path . '/' . $locale . '/' . '_labels.yaml';
				$controller_locales = $locales_path . '/' . $locale . '/' . $this->controller . '_labels.yaml';
				/* Load modules locales */
				$modules = Config()->MODULES;
				foreach($modules as $module){
					$module_locale = Config()->MODULES_PATH .  basename($module) . '/locales/' . $locale . '/_labels.yaml';
					if(is_file($module_locale)) {
						$labels_module[basename($module)] = Yaml::loadFile($module_locale);
						settype($labels_module[basename($module)]['DB_FIELDS'],'array');
						settype($labels_module[basename($module)]['BREADCRUMBS_ACTIONS'],'array');
						settype($labels_module[basename($module)]['MAIN_ACTIONS'],'array');
					}
				}

				$labels_global = array();
				$labels_general = array();
				$labels_controller = array();

				if(is_file($global_locales)) {
					$labels_global = Yaml::loadFile($global_locales);
				}
				if(is_file($labels_locales)) {
					$labels_general = Yaml::loadFile($labels_locales);
				}
				if(is_file($controller_locales)) {
					$labels_controller = Yaml::loadFile($controller_locales);
				}

				if($labels_module){
					$keys = array_merge(array_keys($labels_general), array_keys($labels_controller), array_keys($labels_module));
				}else{
					$keys = array_merge(array_keys($labels_general), array_keys($labels_controller));
				}

				$labels = array();
				foreach ($keys as $key) {
					if(isset($labels_general[$key]) && isset($labels_controller[$key]) && is_array($labels_general[$key])) {
						$labels[$key] = array_merge($labels_general[$key], $labels_controller[$key]);
					} elseif( isset($labels_controller[$key])) {
						$labels[$key] = $labels_controller[$key];
					} elseif( isset($labels_module[$key]) ) {
						$labels[$key] = $labels_module[$key];
					} else {
						$labels[$key] = $labels_general[$key];
					}
				}

				if(Registry()->controller->module && is_array($labels_module[Registry()->controller->module])){
					$labels['DB_FIELDS'] = array_merge($labels['DB_FIELDS'], $labels_module[Registry()->controller->module]['DB_FIELDS']);
					$labels['BREADCRUMBS_ACTIONS'] = array_merge($labels['BREADCRUMBS_ACTIONS'], $labels_module[Registry()->controller->module]['BREADCRUMBS_ACTIONS']);
					$labels['MAIN_ACTIONS'] = array_merge($labels['MAIN_ACTIONS'], $labels_module[Registry()->controller->module]['MAIN_ACTIONS']);

				}

				if($labels_module){
					$labels = array_merge($labels, $labels_global, $labels_module);
				}else{
					$labels = array_merge($labels, $labels_global);
				}

				$_cached_content = "<?php\n\$_cached_labels = ".var_export($labels, true).";\n?>";
				Files::file_put_contents($file, $_cached_content);
			}

			$this->labels = $labels;
		}

		/**
		 * Get labels array
		 *
		 * @return array
		 */
		public function get_labels() {
			return $this->labels;
		}

		/**
		 * Get translated label
		 *
		 * @param string $key
		 * @return string
		 */
		function __get($key) {
			if(substr($key, -6) == '_label') {
				return $this->get(substr($key, 0, -6));
			}
			return $this->get($key);
		}

		/**
		 * Get translated label
		 *
		 * @param string $key
		 * @param string $sub
		 * @return string
		 */
		function get($key, $subkey = null) {
			if(isset($this->labels[$key])) {
				$string = is_null($subkey) ? $this->labels[$key] : (isset($this->labels[$key][$subkey]) ? $this->labels[$key][$subkey] : $subkey);
			} else if(!is_null($subkey)){
				$string = $subkey;
			} else {
				$string = $key;
			}
			return $string;
		}

		/**
		 * Get translated label
		 *
		 * @param string $key
		 * @param string $sub
		 * @return string
		 */
		function get_label($key, $subkey = null) {
			return $this->get($key, $subkey);
		}
	}

function Localizer($controller_name = null) {
	return Localizer::getInstance($controller_name);
}
?>