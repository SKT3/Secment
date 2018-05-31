<?php

	/**
	 * TODO: Document this API
	 *
	 * @param array $params
	 * @param object $smarty
	 * @return mixxed
	 */
	function smarty_function_dynfields($params, $smarty) {

		if (!isset($params['field']) || !array_key_exists('items', $params)) {
			return false;
		}		
		
		// HELPERS:
		$localizer = Registry()->localizer;	
		$controller = Registry()->controller;
		d($controller);	
		
		$options = array('sortable' => false, 'prepend' => false);
		$options = array_merge($options, $params);
		
		$html = array();
		$html[] = '<div id="' . Inflector::pluralize($options['field']) . '_holder" class="whitebox">';
		$html[] = '<ul id="' . Inflector::pluralize($options['field']) . '" class="' . ($options['sortable'] == true ? 'sortable' : '') . ($options['prepend'] == true ? ' prepend' : '') . '">';
		
		if (!is_array($options['items'])) {
			$item = new stdClass();
			$item->id = 1;
			$item->new_record = true;
			$options['items'] = array(1 => $item);
		} 
		
		foreach ($options['items'] as $key => $item) {
			$smarty->assign('key', $key);
			$smarty->assign('field', $item);
			$html[] = '<li id="' . Inflector::pluralize($options['field']) . '_field_' . $item->id . '" class="field_item border_top">';
			$html[] = '<input type="hidden" name="images[]" value="" />';
			$html[] = $smarty->fetch(Config()->VIEWS_PATH.'/' . Registry()->app_system . '/' . strtolower(str_replace('Controller', '', get_class($controller))) .'/_add_' . $options['field'] . '.htm');
			if (!$item->new_record) {
				$html[] = '<input type="hidden" name="images_existing[]" value="' . $item->id . '" />';
			}
			$html[] = '</li>';
		}
		
		$html[] = '</ul>';
		$html[] = '<button type="button" class="field_add">' . $localizer->get_label('BUTTONS', 'add') . '</button>';
		$html[] = '</div>';
		
		return join("\n", $html);
	}
	
?>