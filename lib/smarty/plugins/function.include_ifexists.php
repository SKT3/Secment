<?php

	/**
	 * Include smarty template if it's existing
	 *
	 * @param array $params
	 * @param object $smarty
	 * @return mixxed
	 */
	function smarty_function_include_ifexists($params, $smarty) {
		if (!isset($params['file'])) return false;
		if (is_file(Config()->VIEWS_PATH . '/' . $params['file'])) return $smarty->fetch(Config()->VIEWS_PATH . '/' . $params['file']);
		return false;
	}
	
?>