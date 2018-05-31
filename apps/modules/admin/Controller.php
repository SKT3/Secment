<?php

class AdminController extends ModulesController {
	public
		$module = 'admin',
		$models = 'AdminGroup';

	protected
		$config,
		$modules = array(),
		$is_module = false;

	function __construct(){
		parent::__construct();
		$this->add_before_filter('get_modules');
		$this->add_before_filter('configuration');
		$this->add_before_filter('dispatch');
	}

	protected function get_modules() {
		$this->params = $this->get_action_params();
		$modules = glob(Config()->MODULES_PATH . '*', GLOB_ONLYDIR);

		foreach($modules as $module) {
			if(basename($module) != 'admin') {
				$this->modules[]['name'] = basename($module);
				$this->modulenames[] = basename($module);
			}
		}

		if(!empty($this->params['module']) && $this->params['module'] != 'index'){
			$this->is_module = true;
		}
	}

	protected function dispatch() {
		if($this->is_module && $this->params['maction'] != 'index' && !in_array($this->params['maction'], $this->config['ACTIONS'])) {
			if(method_exists($this, $this->params['maction']) && $this->config['ADMIN'][$this->params['maction']] == '1'){
				$this->{$this->params['maction']}($this->params);
			} else {
				die('No access');
			}
		}
	}

	/* TODO : To be put in a helper */
	protected function configuration($module) {
		if($this->is_module) {
			if(in_array($this->params['module'],$this->modulenames)) {
				$this->config = Yaml::loadFile(Config()->MODULES_PATH . $this->params['module'] .'/Config.yaml');
			} else {
				die('No module');
			}
		}

		if($module) {
			return Yaml::loadFile(Config()->MODULES_PATH . $module['name'] .'/Config.yaml');
		}
	}

	function index($params) {
		foreach($this->modules as $mk => $module) {
			$module['config'] = $this->configuration($module);

			if($module['config']['ADMIN'][__FUNCTION__] == '1') {
				$this->list[] = $module;
			}
		}
	}
}

?>