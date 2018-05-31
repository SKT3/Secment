<?php

class HomeController extends AdminController {
	function index($params) {
		$this->modules = Config()->MODULES;

		$exludes = array('admin');

		foreach($exludes as $e) {
			$exlude = array_search($e,$this->modules);
			if($exlude!==false) {
				unset($this->modules[$exlude]);
			}
		}


		foreach($this->modules as $m) {
			$module_configurations = array();
			if(is_file($module_configurations_yaml = Config()->MODULES_PATH . $m . DIRECTORY_SEPARATOR . 'Config.yaml')) {
				$module_configurations = Yaml::loadFile($module_configurations_yaml);
			}

			if(!$module_configurations || !$module_configurations['skip_module_indexing']) {
//				d($m);
//				d(Inflector::pluralize(Inflector::modulize($m)));
				$model_name = Inflector::pluralize(Inflector::modulize($m)).'Model';
				$model = new $model_name;
				if(method_exists($model, 'find_all')) {
					$this->last_edited[$m] = $model->find_all(null,'updated_at DESC, created_at DESC',10);
				}
//                d($model_name);
			} else {
				unset($this->modules[array_search($m, $this->modules)]);
			}
		}

	}

	function restricted() { }
}

?>
