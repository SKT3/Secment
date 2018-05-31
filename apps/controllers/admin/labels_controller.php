<?php
class LabelsController extends AdminController {
	function index($params) {
		$dirs = $this->yaml_files = $this->files_relation = array();
		$dirs[] = Config()->LOCALES_PATH . Registry()->locale.'/';

		foreach(Config()->MODULES as $m) {
			$dirs[] = Config()->MODULES_PATH . $m . '/locales/' . Registry()->locale . '/';
		}

		foreach($dirs as $d) {
			if(is_dir($d)) {
				$dh  = opendir($d);
				while (false !== ($filename = readdir($dh))) {
					if(substr($filename, -4, 4) == 'yaml') {
						$f = $d . $filename;
						$this->yaml_files[md5($f)] = Yaml::loadFile($f);
						$this->files_relation[md5($f)] = $f;
					}
				}

				closedir($dh);
			}
		}

		if($this->is_post()) {
			foreach($_POST as $k => $v) {
				Files::file_put_contents($this->files_relation[$k], Yaml::dump($v));
			}

			$cache_dir = Config()->ROOT_PATH . 'cache/locales/' . Registry()->locale . '/';
			$dh  = opendir($cache_dir);
			while (false !== ($filename = readdir($dh))) {
				if(substr($filename, -5, 5) == 'cache') {
					unlink($cache_dir . $filename);
				}
			}

			closedir($dh);

			$this->redirect_to(url_for(array('controller' => 'labels')));
		}
	}
}
?>