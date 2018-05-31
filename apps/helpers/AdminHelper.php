<?php
class AdminHelper extends BaseHelper {

	/**
	 * Get permissions for each controller
	 *
	 * @param array $array
	 * @return array $permissions
	 */
	final function get_permissions(array $array=null) {

		$permissions = array();
		$cp = Config()->CONTROLLERS_PATH . 'admin/';
		$magic_methods = array('__construct', '__destruct', '__call', '__get', '__set', '__isset', '__unset', '__sleep', '__wakeup', '__toString', '__set_state', '__clone');

		if($dh = opendir($cp)) {
			while($file = readdir($dh)) {
				if ( $file == '.' || $file == '..' || $file == 'login_controller.php' || $file == 'home_controller.php' || $file == 'admin_controller.php' || is_dir($file))  {
					continue;
				}

				include_once($cp.$file);
				$controller_name = pathinfo($file, PATHINFO_FILENAME);
				$controller_name = Inflector::modulize($controller_name);
				$title = str_replace('Controller', '', $controller_name);
				$ctrl_name = Inflector::underscore($title);
				$title = Inflector::variablize(Inflector::singularize($title));

				$class = new ReflectionClass($controller_name);

				$controller_permissions = array();
				$allowed_controller_actions = $controller_name::getActions();
				foreach ($class->getMethods() as $method) {
					if ($method->getDeclaringClass()->getName() == $controller_name
						&& $method->isPublic()
						&& !in_array($method->name, $magic_methods)
						&& strpos($method->name, 'getFilter_') === false
						&& strpos($method->name, 'getList_') === false
						&& in_array($method->name, $allowed_controller_actions)
					) {
						$controller_permissions[] = $method->name;
					}
				}

				if(is_array($controller_permissions)) {
					if(!is_null($array)) {
						$controller_permissions_modified = array_map(create_function('$action, $controller_name', '
							return $controller_name . "-" . $action;
						'), $controller_permissions, array_fill(0, count($controller_permissions), $ctrl_name) );
						$controller_permissions_modified = array_intersect($controller_permissions_modified, $array);
						$controller_permissions = array_diff_key($controller_permissions, $controller_permissions_modified);

						if(!count($controller_permissions)) {
							continue;
						}
					}

					$permission = &$permissions[$title];
					$permission['title'] = Registry()->localizer->get_label('TOPMENU', $ctrl_name);
					foreach( $controller_permissions AS $controller_permission ) {
						$label = $permission['title'] . ' - ' . Registry()->localizer->get_label('MAIN_ACTIONS', $controller_permission);
						if ($label===$title.'_'.$controller_permission) {
							$label = Registry()->localizer->get_label('PERMISSIONS',$controller_permission);
						}
						$permission['actions'][$ctrl_name.'-'.$controller_permission] = $label;
					}
				}
			}
			closedir($dh);
		}

		foreach(Config()->MODULES as $k=>$v) {
			if($v == 'admin') {
				continue;
			}
			$permissions['module-'.$v] = array('title'=>Registry()->localizer->get($v,'title'));
		}


		return $permissions;
	}

	/**
	 * Checks if the user has permissions for this action/controller
	 *
	 * @param string $action
	 * @param string $controller
	 * @return unknown
	 */
	final function can($action, $controller = null) {
		$controller = (is_null($controller)) ? Registry()->controller->get_controller_name() : $controller;
		if(stripos(Registry()->controller->userinfo->permissions, '|' . $controller . '-' . $action.'|') !== false) {
			return false;
		}
		return true;
	}

	final function module_can($module) {
		if(stripos(Registry()->controller->userinfo->permissions, '|' . 'module-' . $module.'|') !== false) {
			return false;
		}
		return true;
	}

	function generate_main_actions_smarty($params, $smarty) {
		$result = '';
		if(!empty($params['main_actions'])) {
			$sidebar = array();
			foreach ($params['main_actions'] as $action) {
				if (is_array($action)) {
					$sidebar[] = '<li><a href="' . $action['link'] . '" '.(( substr($action['link'], -strlen(Registry()->request->server('REQUEST_URI'))) === Registry()->request->server('REQUEST_URI') || $action['label']=='add') ? 'class="hover"' : '').' title="' . Registry()->localizer->get_label('MAIN_ACTIONS', $action['label']) . '">' . Registry()->localizer->get_label('MAIN_ACTIONS', $action['label']) . '</a></li>';
				} else {

					if(Registry()->controller->module) {
						$url = url_for(array('controller' => 'admin', 'module' => Registry()->controller->module, 'maction' => $action));
					} else {
						$url = url_for(array('controller' => Inflector::underscore(Registry()->controller->get_controller_name()), 'action' => $action));
					}
					$sidebar[] = '<li><a href="' . $url . '" title="' . Registry()->localizer->get_label('MAIN_ACTIONS', $action) . '">' . $this->localizer->get_label('MAIN_ACTIONS', $action) . '</a></li>';
				}
			}

			$result = '<ul id="main_actions">' . join("\n", $sidebar) . '</ul>';
		}

		return $result;
	}
}

?>