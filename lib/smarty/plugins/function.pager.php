<?php
	/**
	 * Public Pager
	 *
	 * @param array $params
	 * @param array $smarty
	 * @return string
	 */
	function smarty_function_pager($params, $smarty) {
		$html = array();

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
			'url_param' => 'page',
			'order' => null
		);

		// merge the default options with the params to get a composite
		// parameters array
		$options = array_merge($options, $params);

		// Holds the paginator object
		$paginator = $options['from'];
		$url_param = $options['url_param'];

		// build query string
		unset($_GET[$url_param]);
		$add_to_query = html_entity_decode(http_build_query($_GET));
		if(!empty($add_to_query)) {
			$add_to_query = '&' . $add_to_query;
		}
		$add_to_query = str_replace('&','&amp;',$add_to_query);

		// if there is no paginator exit
		if(empty($paginator) || !is_object($paginator) || !$paginator instanceof Paginator || $paginator->pages_count == 0) return '';

		// find out pages range to render
		$range = isset($options['range']) ? (int) $options['range'] : 1;
		$pages_to_render = $range ? $paginator->range($range) : array('first' => $paginator->first(), 'last' => $paginator->last());

		// if only one page return
		if ($pages_to_render['last']->page_number == 1) {
			return '';
		}

		$current_page = $paginator->current();

		$html[] = '<ul>';
		// if the current page is not the first page
		if(!$current_page->first()) {
			//$first_link = $url . '?' . $url_param . '=1' . $add_to_query;
			$previous_link = $url . '?' . $url_param . '=' . $paginator->prev()->page_number . $add_to_query;

			//$html[] = '<li><a href="' . $first_link . '" title="' . $options['first_text'] . '">&laquo;</a></li>';
			$html[] = '<li class="prev"><a href="' . $previous_link . '" title="' . $options['previous_text'] . '"></a></li>';
		}

		if ($pages_to_render['first']->page_number - 1 > 1) {
            $page_link =  $url . '?' . $url_param . '=' . 1 . $add_to_query;
            $html[] = '<li><a href="'.$page_link.'">1</a></li>';
		    $html[] = '<li><span>...</span></li>';
        } else if ($pages_to_render['first']->page_number - 1 == 1) {
            $page_link =  $url . '?' . $url_param . '=' . 1 . $add_to_query;
            $html[] = '<li><a href="'.$page_link.'">1</a></li>';
        }

		// Render page list
		for($i = $pages_to_render['first']->page_number; $i <= $pages_to_render['last']->page_number; $i++) {
			$page_link =  $url . '?' . $url_param . '=' . $i . $add_to_query;
			$class = ($i == $current_page->page_number) ? ' class="selected"' : '';
			$html[] = '<li'.$class.'><a href="'.$page_link.'">'.$i.'</a></li>';
		}

		if ($pages_to_render['last']->page_number + 1 < $paginator->pages_count) {
            $page_link =  $url . '?' . $url_param . '=' . $paginator->pages_count . $add_to_query;
            $html[] = '<li><span>...</span></li>';
            $html[] = '<li><a href="'.$page_link.'">'.$paginator->pages_count.'</a></li>';
        } else if ($pages_to_render['last']->page_number == $paginator->pages_count - 1) {
            $page_link =  $url . '?' . $url_param . '=' . $paginator->pages_count . $add_to_query;
            $html[] = '<li><a href="'.$page_link.'">'.$paginator->pages_count.'</a></li>';
        }

		// If the current page is not the last page
		if(!$current_page->last()) {
			$next_link = $url . '?' . $url_param . '=' . $paginator->next()->page_number . $add_to_query;
			$last_link = $url . '?' . $url_param . '=' . $paginator->pages_count . $add_to_query;

			$html[] = '<li class="next"><a href="' . $next_link . '" title="' . $options['next_text'] . '"></a></li>';
			//$html[] = '<li><a href="' . $last_link . '" title="' . $options['last_text'] . '">&raquo;</a></li>';
		}

		$html[] = '</ul>';
		return '<nav class="paging">'.join("\n", $html).'</nav>';
	}
?>