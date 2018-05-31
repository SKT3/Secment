<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.paging.php
 * Type:     function
 * Name:     paging
 * Purpose:  Outputs pagination links.
 * -------------------------------------------------------------
 */
function smarty_function_paging_public($params, $smarty) {
	$html = array();

	$controller = Registry()->controller;

	// HELPERS:
	$localizer = Registry()->localizer;

	// current page url
	$url = Registry()->request->get_protocol() . Registry()->request->get_host_and_port() . Registry()->request->url;

	// DEFAULT OPTIONS:
	$options = array(
		'current_class' => 'current_page',
		'previous_class' => 'prev',
		'next_class' => 'next',
		'next_text' => $localizer->get_label('PAGING', 'next'),
		'previous_text' => $localizer->get_label('PAGING', 'previous'),
		'first_text' => $localizer->get_label('PAGING', 'first'),
		'last_text' => $localizer->get_label('PAGING', 'last'),
		'url_param' => 'page',
		'show_href_url' => false,
		'order' => null
	);

	$url_params = array_merge($controller->get_action_params(), array('controller' => $controller->get_controller_name(), 'action' => $controller->get_action_name()));
	if($url_params['action'] == 'xhr') {
		unset($url_params['method'], $url_params['module']);
	}

	if(!empty($params['controller'])) {
		$url_params['controller'] = $params['controller'];
		unset($params['controller']);
	}
	if(!empty($params['action'])) {
		$url_params['action'] = $params['action'];
		unset($params['action']);
	}
	if(!empty($params['custom_url_params'])) {
		$url_params = array_merge($url_params, $params['custom_url_params']);
		unset($params['custom_url_params']);
	}

	// merge the default options with the params to get a composite
	// parameters array
	$options = array_merge($options, $params);

	// Holds the paginator object
	$paginator = $options['from'];
	$url_param = $options['url_param'];

	// build query string
	unset($_GET[$url_param]);
	if(!empty($url_params[$url_param])) {
		unset($url_params[$url_param]);
	}

	$add_to_query = html_entity_decode(http_build_query($_GET));
	if(!empty($add_to_query)) {
		$add_to_query = '&' . $add_to_query;
	}
	$add_to_query = str_replace('&','&amp;',$add_to_query);

	// if there is no paginator exit
	if(empty($paginator) || !is_object($paginator) || !$paginator instanceof Paginator || $paginator->pages_count == 0) return '';


	// find out pages range to render
	$range = isset($options['range']) ? (int) $options['range'] : false;
	$pages_to_render = $range ? $paginator->range($range) : array('first' => $paginator->first(), 'last' => $paginator->last());

	// if only one page return
	if ($pages_to_render['last']->page_number == 1) {
		return '';
	}

	$current_page = $paginator->current();

	$html[] = '<div class="pagginator"><ul>';
	

	// Render page list
	for($i = $pages_to_render['first']->page_number; $i <= $pages_to_render['last']->page_number; $i++) {
		$page_link =  url_for(array_merge($url_params, array($url_param => $i))); //$url . '?' . $url_param . '=' . $i . $add_to_query;

		$class = ($i == $current_page->page_number) ? ' class="active"' : '';
		$html[] = '<li' . $class . '><a href="' . $page_link  . '">' . $i . '</a></li>';
	}


	$html[] = '</ul></div>';

	return join("\n", $html);
}

?>