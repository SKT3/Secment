<?php
function smarty_function_generate_admin_menu($params, $smarty) {
	$controller_name = Registry()->controller->get_controller_name();
	$action_name = Registry()->controller->get_action_name();
	$action_params = Registry()->controller->get_action_params();
	$newsletter_autologin_secret = Registry()->controller->get_newsletter_autologin_secret();
	$id = Registry()->item_id;

	if(Registry()->app_system_url == 'modules') {
		$app_system_url = 'admin';
	} else {
		$app_system_url = Registry()->app_system_url;
	}

	$admin_url = Config()->COOKIE_PATH . $app_system_url . '/' . substr(Registry()->locale, 0, 2) . '/';
	$xml = simplexml_load_file(Config()->LAYOUTS_PATH . '/' . $app_system_url . '/menu.xml');
	// first level menu


	$return = '<div class="sub">';
	$links = '<div class="parent"><ul>';
	$breadcrumb = array();
	$breadcrumb[] = array(Registry()->localizer->get_label('home'), url_for(array('controller' => 'home', 'appsys' => $app_system_url)));

	$sub_number = 1;
	$parent_number = 1;

	foreach($xml->MAIN as $main_key => $main_value)
	{
		$attributes = $main_value->attributes();
		if($attributes['appsys']) {
			$app_system_url = $attributes['appsys'];
		}else{
			$app_system_url = 'admin';
		}
		$label_main = (string)$attributes['label'];
		$label_first = htmlspecialchars(Registry()->localizer->get_label('TOPMENU', $label_main));
		$url_for = array('controller' => (string)$attributes['controller'], 'action' => (string)$attributes['action'], 'id' => (string)$attributes['id'], 'appsys' => $app_system_url);
		if(isset($attributes['module'])){
			$url_for['module'] = (string)$attributes['module'];
		}
		if(isset($attributes['maction'])){
			$url_for['maction'] = (string)$attributes['maction'];
		}
		if(isset($attributes['route_name'])){
			$url_for['route_name'] = (string)$attributes['route_name'];
		}
		if(isset($attributes['section'])){
			$url_for['section'] = (string)$attributes['section'];
		}
		if(isset($attributes['vendor'])) {
			$url_for['vendor'] = (string)$attributes['vendor'];
		}
		if(isset($attributes['secret'])) {
			$url_for['secret'] = $newsletter_autologin_secret ?: (string)$attributes['secret'];
		}
		$href_first = ($attributes['controller'] == 'none') ? 'javascript:;' : url_for($url_for);
		$add_class_first = null;

		if (property_exists($attributes, 'action') && property_exists($attributes, 'id')) {
			if ($controller_name == (string)$attributes['controller'] && $action_name == (string)$attributes['action'] && $id == (string)$attributes['id']) {
				$add_class_first = ' current selected';
				$breadcrumb[] = array($label_first, $href_first);
			}
		}
		else if (property_exists($attributes, 'action') && !property_exists($attributes, 'id')) {
			if ($controller_name == (string)$attributes['controller'] && $action_name == (string)$attributes['action']) {
				$add_class_first = ' current selected';
				$breadcrumb[] = array($label_first, $href_first);
			}
		}
		else if (!property_exists($attributes, 'action') && !property_exists($attributes, 'id')) {
			if ($controller_name == (string)$attributes['controller']) {
				$add_class_first = ' current selected';
				$breadcrumb[] = array($label_first, $href_first);
			}
		}

		$sub = '';
		// second level menu
		if (count($main_value->SUB)) {
			$sub_menu_items = array();
			$sub_selected = false;
			foreach ($main_value->SUB as $sub_key => $sub_value)
			{
				$attributes = $sub_value->attributes();

				$label_sub = htmlspecialchars(Registry()->localizer->get_label('TOPMENU', (string)$attributes['label']));
				$url_for_sub = array('controller' => (string)$attributes['controller'], 'action' => (string)$attributes['action'], 'id' => (string)$attributes['id'], 'appsys' => $app_system_url);
				if(isset($attributes['module'])){
					$url_for_sub['module'] = (string)$attributes['module'];
					$label_sub = htmlspecialchars(Registry()->localizer->get_label((string)$attributes['module'], 'title'));
				}
				if(isset($attributes['maction'])){
					$url_for_sub['maction'] = (string)$attributes['maction'];
					if((string)$attributes['maction'] != 'index'){
						$labels = htmlspecialchars(Registry()->localizer->get_label((string)$attributes['module'], 'ACTIONS'));
						$label_sub = htmlspecialchars($labels[(string)$attributes['maction']]);
					}
				}
				if(isset($attributes['route_name'])){
					 $url_for_sub['route_name'] = (string)$attributes['route_name'];
				}
				if(isset($attributes['section'])){
					$url_for_sub['section'] = (string)$attributes['section'];
				}

				$href_sub = ($attributes['controller'] == 'none') ? 'javascript:;' : url_for($url_for_sub);

				$sub_menu_items[] = '<li><a href="' . $href_sub . '">' . $label_sub . '</a></li>';
				if (!property_exists($attributes, 'section')) {
					if(property_exists($attributes, 'module')){
						if($action_params['module'] == $attributes['module']){
							$breadcrumb[] = array($label_first, $href_first);
							$breadcrumb[] = array($label_sub, $href_sub);
							$add_class_first = ' selected current';
							$sub_selected =  true;
							if ($action_name !== 'index') {
								$label = Registry()->localizer->get_label($attributes['module'], 'ACTIONS');
								if(!is_array($label) || !isset($label[$action_name])){
									$action_label = Registry()->localizer->get_label('BREADCRUMBS_ACTIONS', "{$action_name}");
								}else{
									$action_label = $label[$action_name];
								}
								$breadcrumb[] = array($action_label, $href_sub);
							}
						}

					}
					elseif (property_exists($attributes, 'action') && property_exists($attributes, 'id')) {
						if ($controller_name == $attributes['controller'] && $action_name == $attributes['action'] && $id == $attributes['id']) {
							$breadcrumb[] = array($label_first, $href_first);
							$breadcrumb[] = array($label_sub, $href_sub);
							$add_class_first = ' selected current';
							$sub_selected =  true;
							if ($action_name !== 'index') {
								$breadcrumb[] = array(Registry()->localizer->get_label('BREADCRUMBS_ACTIONS', "{$action_name}"), $href_sub);
							}

						}
					}
					else if (property_exists($attributes, 'action') && !property_exists($attributes, 'id')) {
						if ($controller_name == $attributes['controller'] && $action_name == $attributes['action']) {
							$breadcrumb[] = array($label_first, $href_first);
							$breadcrumb[] = array($label_sub, $href_sub);
							$add_class_first = ' selected current';
							$sub_selected =  true;
							if ($action_name !== 'index') {
								$breadcrumb[] = array(Registry()->localizer->get_label('BREADCRUMBS_ACTIONS', "{$action_name}"), $href_sub);
							}

						}
					}
					else if (!property_exists($attributes, 'action') && !property_exists($attributes, 'id')) {
						if ($controller_name == $attributes['controller']) {
							$breadcrumb[] = array($label_first, $href_first);
							$breadcrumb[] = array($label_sub, $href_sub);
							$add_class_first = ' selected current';
							$sub_selected =  true;
							if ($action_name !== 'index') {
								$breadcrumb[] = array(Registry()->localizer->get_label('BREADCRUMBS_ACTIONS', "{$action_name}"), $href_sub);
							}

						}
					}
				}else{
					if ($controller_name == $attributes['controller'] && $action_name == $attributes['action'] && $action_params['section'] == $attributes['section']) {
						$breadcrumb[] = array($label_first, $href_first);
						$breadcrumb[] = array($label_sub, $href_sub);
						$add_class_first = ' selected current';
						$sub_selected =  true;
						if ($action_name !== 'index') {
							$breadcrumb[] = array(Registry()->localizer->get_label('BREADCRUMBS_ACTIONS', "{$action_name}"), $href_sub);
						}

					} elseif($controller_name == $attributes['controller'] && $action_params['section'] == $attributes['section']) {
							$breadcrumb[] = array($label_first, $href_first);
							$breadcrumb[] = array($label_sub, $href_sub);
							$add_class_first = ' selected current';
							$sub_selected =  true;
							if ($action_name !== 'index') {
								$breadcrumb[] = array(Registry()->localizer->get_label('BREADCRUMBS_ACTIONS', "{$action_name}"), $href_sub);
							}

					}
				}

			}
			if ($sub_selected) {
				// $sub .= '<ul id="sub_'.$sub_number.'" style="display: block;" class="active">';
				$sub .= '<ul id="sub_'.$sub_number.'" style="display: none;">';
			} else {
				$sub .= '<ul id="sub_'.$sub_number.'" style="display: none;">';
			}
			$sub .= join("\n", $sub_menu_items);
			$sub .= '</ul>';
			$add_class_first .= ' has_subs';
		}
		else if( $add_class_first && $action_name !== 'index')
		{
			$breadcrumb[] = array(Registry()->localizer->get_label('BREADCRUMBS_ACTIONS', "{$action_name}"), null);
		}

		$links .= '<li><a class="'.$add_class_first.'" id="parent_'.$parent_number.'" href="' . $href_first . '">
		<span>' . $label_first . '</span></a>';
		$links .= '<div class="sub">'.$sub.'</div>';
		$links .= '</li>';

		$sub_number++;
		$parent_number++;

		// $return .= $sub;
		//$return .= '</div>';
	}

	$links .= '</ul></div>';
	$return .= '</div>';

	$return = $links;//.$return;

	//$breadcrumb =array_unique($breadcrumb);
	$bcr = array($breadcrumb[0]);
	for($i=1; $i < count($breadcrumb); $i++)
	{
		$m = false;
		for($j = 0; $j < count($bcr); $j++)
		{
			if( $breadcrumb[$i][0] == $bcr[$j][0]
				&& $breadcrumb[$i][1] == $bcr[$j][1])
				{
					$m = true;
					break;
				}
		}
		if(!$m) {
			$bcr[] = $breadcrumb[$i];
		}
	}

	$smarty->assign('_breadcrumbs', $bcr);
	return $return;
}
?>