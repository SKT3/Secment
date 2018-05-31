<?php

class ListHelper extends BaseHelper {
	/**
	 * Array containg the fields which the list helper will show
	 *
	 * @access private
	 * @var array
	 */
	private $columns = array();

	/**
	 * To hide the 'Action' block set this to true
	 *
	 * @var unknown_type
	 */
	public $hide_main_actions = false;

	/**
	 * if you want to hide the default main_action and do yours custom, you've got to set this as true
	 *
	 * @var bool
	 */
	public $hide_default_main_actions = false;

	/**
	 * Array containg the actions available for the listed items
	 *
	 * @access private
	 * @var array
	 */
	private $actions = array();

	/**
	 * Array containing groups actions below the table list
	 *
	 * @var array
	 */
	private $group_actions = array();

	/**
	 * Array containg the actions available for the main_actions block
	 *
	 * @access private
	 * @var array
	 */
	private $main_actions = array();

	/**
	 * Sets whether the generated list is user sortable enabled
	 * (via dragging) or has ordering and paging options.
	 *
	 * @access private
	 * @var boolean
	 */
	private $is_draggable_list = false;

	/**
	 * Stores the filters. If set - it generates the filters for the listing
	 *
	 * @access private
	 * @var array
	 */
	private $filters = array();

	/**
	 * Sets whether to skip displaying list actions for predefined ids
	 *
	 * @example:
	 * // action => ids
	 * _skup_actions = array('edit' => array(1,2,5)
	 *
	 * @var array
	 */
	protected $skip_actions = array();

	/**
	 * Should we escape the provided data
	 *
	 * @var boolean
	 */
	public $escape_data = true;

	/**
	 * Provider data array
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * The instance of Localizer object
	 *
	 * @var object
	 */
	private $localizer = null;

	/**
	 * Generated HTML for the listing
	 *
	 * @var array
	 */
	private $html = array();

	/**
	 * Constructor. if $params is numeric - it sets the items per page count
	 * if $params is boolean - it sets that the list is user sortable - all
	 * records are displayed on the page enabling the list to be sorted by drag and drop
	 *
	 * @access public
	 * @param mixed $params
	 * @return ListHelper
	 */
	public function __construct($params = null) {
		parent::__construct();
		$this->params = $params;
		if ($params === true || $params['sortable'] === true) $this->is_draggable_list = true;
		$this->localizer = Registry()->localizer;
	}

	/**
	 * Set list data
	 *
	 * @param array $data
	 */
	function data(array $data) {
		$this->data = $data;
	}

	/**
	 * Adds a column to show to the default list of items
	 *
	 * @access public
	 * @param string $field
	 * @param integer $width
	 * @param boolean $sortable
	 * @param string $label
	 * @param string $align
	 */
	public function add_column($field, $width = null, $sortable = true, $options = array()) {
		$width = (!count($this->columns) && $field == 'id' && (int)$width == 0) ? 50 : $width;
		$this->columns[$field] = array_merge(array('field' => $field, 'width' => $width, 'sortable' => $sortable), $options);
	}

	/**
	 * Adds a filter to the listing.
	 *
	 * @access public
	 * @param string $field
	 * @param mixed $values
	 * @param string $type
	 * @param string $value_field
	 * @return void
	 */
	public function add_filter($field, $values, $type = 'select', $value_field = '') {
		$this->filters[] = array('field' => $field, 'values' => $values, 'type' => $type, 'value_field' => $value_field);
	}

	/**
	 * Adds an action for the listed items (i.e. edit, delete, etc)
	 *
	 * @access public
	 * @param string $label
	 * @param string $link
	 */
	public function add_action($label, $link,$target=null) {
		$this->action_names[] = array($label, $link,$target);
	}

	/**
	 * Adds a group action
	 *
	 * @param string $action
	 * @param string $url
	 */
	function add_group_action($action, $url) {
		$this->group_actions[$action] = $url;
	}

	/**
	 * Set skip action filters
	 *
	 * @param array $filter
	 */
	function set_skip_action_filters($filter) {
		(is_string($filter)) && ($filter = array_filter(explode(',', preg_replace('#\s#', ',', $filter)), 'trim'));
		$this->skip_actions = (array)$filter;
	}

	/**
	 * Adds an action for the listed items (i.e. edit, delete, etc)
	 *
	 * @access public
	 * @param string $label
	 * @param string or array $action
	 */
	public function add_main_action($action)
	{
		$this->main_actions[] = $action;
	}

	/**
	 * Traverses the data and generates a listing of the items with filters,
	 * option for ordering the results or user enabled sorting, paging, etc
	 *
	 * @access public
	 * @return string
	 */
	public function generate() {
		if($this->action_name=='index'){
			if(Registry()->app_system=='admin') {
				$this->html[] = '<h2>' . $this->localizer->get_label('TOPMENU', Inflector::underscore($this->controller_name)) . '</h2>';
			} else {
				$this->html[] = '<h2>' . $this->localizer->get_label(Inflector::underscore($this->controller_name), 'title') . '</h2>';
			}

		}else{
			$this->html[] = '<h2>' . $this->localizer->get_label(Inflector::underscore($this->controller_name), 'title') . ' > ' . $this->localizer->get_label('MAIN_ACTIONS', Inflector::underscore($this->action_name)) . '</h2>';
		}

		if($this->filters) {
			$this->html[] = $this->generate_filters();
		}

		// generate main actions if set
		if(!$this->hide_main_actions) {
			$sidebar = array();
			// $sidebar[] = '<li><strong>' . $this->localizer->get_label('main_actions') . '</strong></li>';

			if (!$this->hide_default_main_actions) {
				if(Registry()->controller->module) {
					$url = url_for(array('controller'=>'admin','module' => Registry()->controller->module,'maction'=>'add'));
				} else {
					$url = url_for(array('controller' => Inflector::underscore($this->controller_name), 'action' => 'add'));
				}

				$sidebar[] = '<li><a href="' . $url . '" title="' . $this->localizer->get_label('MAIN_ACTIONS','add') . '">' . $this->localizer->get_label('MAIN_ACTIONS','add') . '</a></li>';
			}

			foreach ($this->main_actions as $action) {
				if (is_array($action)) {
					$sidebar[] = '<li><a href="' . $action['link'] . '" ' . (( substr($action['link'], -strlen(Registry()->request->url_part('path'))) === Registry()->request->url_part('path') || $action['label']=='add') ? 'class="hover"' : '').' title="' . $this->localizer->get_label('MAIN_ACTIONS', $action['label']) . '">' . $this->localizer->get_label('MAIN_ACTIONS', $action['label']) . '</a></li>';
				} else {

					if(Registry()->controller->module) {
						$url = url_for(array('controller' => 'admin', 'module' => Registry()->controller->module, 'maction' => $action));
					} else {
						$url = url_for(array('controller' => Inflector::underscore($this->controller_name), 'action' => $action));
					}
					$sidebar[] = '<li><a href="' . $url . '" title="' . $this->localizer->get_label('MAIN_ACTIONS', $action) . '">' . $this->localizer->get_label('MAIN_ACTIONS', $action) . '</a></li>';
				}
			}

			$this->html[] = '<ul id="main_actions">' . join("\n", $sidebar) . '</ul>';
		}
		$this->html[] = '<div>';
		// traverse the data and generate the table listing
		if (!empty($this->data)) {
			if(!count($this->filters)) {
				$this->html[] = '<table cellspacing="0" cellpadding="0" id="list" class="list no-filter">';
			}else{
				$this->html[] = '<table cellspacing="0" cellpadding="0" id="list" class="list">';
			}

			// generate in one pass columns and headers for the table
			if (count($this->columns)) {
				$columns = array();
				$headers = array();

				if (count($this->group_actions)) {
					$columns[] = '<col width="1" />';
					$headers[] = '<th class="notsortable"><input type="checkbox" id="ga_trigger" /></th>';
				}

				$filters = array();
				if($this->filters) {
					$filters = $this->to_query_array();
				}

				$order = array('field' => '', 'type' => '');
				$parts = explode('+', urldecode(strtolower((string)$this->params['order'])));
				$parts = explode(' ',current($parts));
				$order['field'] = current($parts);
				$order['type'] = end($parts);


				foreach ($this->columns as $field => $options) {
					// add columns
					$columns[] = '<col ' . ((int)$options['width'] ? 'width="' . $options['width'] . '" ' : '') . '/>';

					// add table headers
					$labels = $this->localizer->get_label('DB_FIELDS');
					array_key_exists($field, $labels);

					if(Registry()->controller->module) {
						$labels = $this->localizer->get_label(Registry()->controller->module, 'DB_FIELDS');
						if(is_array($labels) && array_key_exists($field, $labels)) {
							$custom_label = $labels[$field];
						} else {
							$custom_label = $this->localizer->get_label('DB_FIELDS', $field);
						}
						$label = is_null($options['label']) ? $custom_label : $options['label'];
					} else {
						$label = is_null($options['label']) ? $this->localizer->get_label('DB_FIELDS', $field) : $options['label'];
					}

					$align = array_key_exists('align', $options) ? 'align="' . $options['align'] . '"' : '';
					if ($this->is_draggable_list || !$options['sortable']) {
						$headers[] = '<th ' . $align . ' class="notsortable">' . $label . '</th>';
					} else {
						if ($field == $order['field'] && in_array($order['type'], array('asc', 'desc'))) {
							$ord = $field . ($order['type'] == 'asc' ? ' DESC' : ' ASC');

							$class = 'class="sorted sorted-' . $order['type'] . '"';
						} else {
							$ord = $field . ' DESC';
							$class = '';
						}

						if(Registry()->controller->module) {
							$url = url_for(array_merge(array('controller'=>'admin','module' => Registry()->controller->module,'maction'=>$this->action_name ,'id'=>$this->current_id, 'order' => $ord), $filters));
						} else {
							$url = url_for(array_merge(array('controller' => Inflector::underscore($this->controller_name),'action'=>Inflector::underscore($this->action_name),'id'=>$this->current_id, 'order' => $ord), $filters));
						}

						$headers[] = '<th ' . $class . ' ' . $align . '	onclick="window.location=\'' . $url . '\'">' . $label . '</th>';
					}
				}

				if (count($this->action_names)) {
					$columns = array_merge($columns, array_fill(0, count($this->action_names), '<col width="1" />'));
					$headers[] = '<th class="actions" colspan="' . count($this->action_names) . '">' . $this->localizer->get_label('actions') . '</th>';
				}

				$this->html[] = join("\n", $columns) . '<thead><tr>' . join("\n", $headers) . '</tr></thead>';
			}

			$this->html[] = '<tbody id="listing">';

			$original_order = array();
			foreach ($this->data as $data) {
				//$data = (array)$data;
				$row = array();
				$row[] = '<tr id="listing_' . $data->id . '">';

				if (count($this->group_actions)) {
					$row[] = '<td><input type="checkbox" class="ga_checkbox" value="' . $data->id . '" /></td>';
				}

				foreach ($this->columns as $field => $options) {
					$align = array_key_exists('align', $options) ? 'align="' . $options['align'] . '"' : '';
					$value = $data->$field;
					$class_name = ($this->is_draggable_list && $field == 'id') ? 'orderable' : '';
					if ($field == 'id') {
						$item_id = $data->$field;
						// save the original order of the items
						$original_order[] = $item_id;
					}
					if(method_exists(Registry()->controller, 'getList_' . $field)) {
						$value = Registry()->controller->{'getList_' . $field}($value, $data);
						$value = str_replace('{:id}',$data->id,$value);
					}
					$row[] = '<td '.$align.' class="' . $class_name . '">' . $value . '</td>';
				}

				foreach($this->action_names as $action) {
					$row[] = '<td>' . $this->generate_action($action, (array)$data) . '</td>';
				}

				$row[] = '</tr>';
				$this->html[] = join("\n", $row);
			}

			$this->html[] = '</tbody>';
			$this->html[] = '</table>';

			if ($this->group_actions) {
				$this->html[] = '<div id="ga_container">';
				$this->html[] = '<button id="ga_check_all">' . $this->localizer->get_label('check_all') . '</button>';
				$this->html[] = '<button id="ga_uncheck_all">' . $this->localizer->get_label('uncheck_all') . '</button>';
				$this->html[] = '<select id="ga_select">';
				$this->html[] = '<option value="0">' . $this->localizer->get_label('with_selected') . '</option>';
				foreach ($this->group_actions as $action => $url) {
					$this->html[] = '<option value="' . $url . '">' . $this->localizer->get_label('GROUP_ACTIONS', $action) . '</option>';
				}
				$this->html[] = '</select>';
				$this->html[] = '</div>';
				$this->html[] = '<script type="text/javascript">var ga_confirm="' . $this->localizer->get_label('confirm_action') . ';</script>';
			}

			if ((int)current($this->data)->pages > 1 && !$this->is_draggable_list) {
				$page = $this->params['page'];
				Registry()->tpl->assign('pages', new Paginator((int)current($this->data)->pages, isset($page) ? $page : 1));
				if(count($this->filters)){
					Registry()->tpl->assign('has_filter', true);
				}
				$this->html[] = Registry()->tpl->fetch('layouts/'.Registry()->app_system.'/paginator_layout.htm');
			}

			if ($this->is_draggable_list) {
				$this->html[] = '<div id="save_restore_order"><button id="restore_order" class="button">' . $this->localizer->get_label('BUTTONS', 'restore_order') . '</button>';
				$this->html[] = '<button id="save_order" onclick="saveOrder();" class="button" rel="'.$this->action_name.'">' . $this->localizer->get_label('BUTTONS', 'save_order') . '</button></div>';
			}

		} else {
			$this->html[] = '<div class="no_results">' . Inflector::humanize($this->localizer->get_label('no_results_found')) . '</div>';
		}

		$this->html[] = '</div>';



		return join("\n", $this->html);
	}

	/**
	 * Generates a link for the action specified
	 *
	 * @access private
	 * @param array $action
	 * @param array $data
	 * @return string
	 */
	private function generate_action(array $action, $data) {
		if(empty($this->skip_actions)
			|| (
				!in_array($action[0], $this->skip_actions) && (
					!array_key_exists($action[0], $this->skip_actions) || (
							(!is_array($this->skip_actions[$action[0]]) && $data['id'] != $this->skip_actions[$action[0]])
							|| (is_array($this->skip_actions[$action[0]]) && count($this->skip_actions[$action[0]]) > 0 && !in_array($data['id'], $this->skip_actions[$action[0]]))
						)
					)
				)
			) {
			$link = urldecode($action[1]);
			preg_match_all('/(\/|=|,|\():(.*?)(\/|&|$|\))/', $link, $matches);
			$vars = $matches[2];
			$values = array_intersect_key($data, array_combine($vars, $vars));

			foreach ($vars as $key => $var) {
				$vars[$key] = ':' . $var;
			}

			$link = '<a href="' . str_replace($vars, $values, $link) . '" class="action ' . $action[0] . '" '.($action[2] ? 'target="'.$action[2].'"' : '').' rel="tooltip" title="'.$this->localizer->get_label('MAIN_ACTIONS',$action[0]).'" ></a>';
		} else {
			$link = "-";
		}

		return $link;
	}

	/**
	 * generates filter widgets based on the filter array set
	 *
	 * @return string
	 */
	function generate_filters() {
		$html = array();

		if(count($this->filters)) {
			$html[] = '<form action="" method="get" class="filter">';
			$html[] = '<div class="expandme"></div>';
			$html[] = '<fieldset>';

			if(strlen($this->to_sql()) > 0) {
				$html[] = '<h3>'.$this->localizer->get_label('filter').'<button type="button" onclick="window.location=\'' . Registry()->request->get_protocol() . Registry()->request->get_host_and_port() . Registry()->request->url . '\'">' . $this->localizer->get_label('BUTTONS', 'show_all') . '</button></h3>';
			} else {
				$html[] = '<h3>'.$this->localizer->get_label('filter').'</h3>';
			}

			$html[] = '<legend>' . $this->localizer->get_label('filter') . '</legend>';
			$html[] = '<table>';

			foreach ($this->filters as $count => $filter) {
				$html[] = '<tr>';
				$html[] = '<td>';
				$name = $filter['field'];

				if(Registry()->controller->module) {
					$labels = $this->localizer->get_label(Registry()->controller->module, 'DB_FIELDS');

					if(is_array($labels) && array_key_exists($filter['field'], $labels)) {
						$label = $labels[$filter['field']];
					} else {
						$label = $this->localizer->get_label('DB_FIELDS', $filter['field']);
					}
				} else {
					$label = $this->localizer->get_label('DB_FIELDS', $filter['field']);
				}

				$value = $this->params[$filter['field']];
				$value_field = $filter['value_field'];

				if ($filter['type'] == 'text') {
					$html[] = '<label for="' . $name . '">' . $label . '</label></td><td><input type="text" value="' . $value . '" name="' . $name . '" id="' . $name . '" />';
				}
				if ($filter['type'] == 'checkbox') {
					$html[] = '<label for="' . $name . '">' . $label . '</label></td><td><input type="checkbox" value="1" '.($_GET[$name]==1 ? 'checked="checked"' : '').' name="' . $name . '" id="' . $name . '" />';
				}
				else if ($filter['type'] == 'date') {
					$html[] = '<label for="' . $name . '">' . $label . '</label></td><td><input type="text" class="date-input" value="' . $value . '" name="' . $name . '" id="' . $name . '" readonly="readonly" /><button type="button" class="btn_date">...</button>';
				}
				else if ($filter['type'] == 'datetime') {
					$html[] = '<label for="' . $name . '">' . $label . '</label></td><td><input type="text" class="datetime-input" value="' . $value . '" name="' . $name . '" id="' . $name . '" readonly="readonly" /><button type="button" class="btn_date">...</button>';
				}
				else if ($filter['type'] == 'select') {
					$html[] = '<label for="' . $name . '">' . $label . '</label></td><td><select name="' . $name . '" id="' . $name . '">';
					$html[] = '<option value=""> - ' . $this->localizer->get_label('all') . ' - </option>';
					foreach ($filter['values'] as $key => $option) {
						if (is_array($option)) {
							$html[] = '<optgroup label="'.$key.'">';

							foreach ($option as $subkey => $suboption) {
								$val = is_object($option) ? $option->id : $subkey;
								$title = is_object($option) ? ($value_field ? $suboption->{$value_field} : $suboption) : $suboption;
								$html[] = '<option value="' . $val . '" '.($value == $val ? 'selected="selected"' : '').'>' . $title . '</option>';
							}

							$html[] = '</optgroup>';
						} else {
							$val = is_object($option) ? $option->id : $key;
							$title = is_object($option) ? ($value_field ? $option->{$value_field}: $option) : $option;
							$html[] = '<option value="' . $val . '" '.($value == $val && $value != '' ? 'selected="selected"' : '').'>' . $title . '</option>';
						}
					}
					$html[] = '</select>';
				}

				$html[] = '</td>';
				//$html[] = ((($count + 1) % 3) == 0) ? '</tr><tr>' : '';
				$html[] = '</tr>';

			}

			// submit and clear form buttons
			$html[] = '<tr>';
			$html[] = '<td colspan="2" style="border-bottom:0">';
			$html[] = '<button type="submit">' . $this->localizer->get_label('BUTTONS', 'filter') . '</button>';
			$html[] = '</td>';

			$html[] = '</tr>';
			$html[] = '</table>';
			$html[] = '</fieldset>';
			$html[] = '</form>';
		}

		return join("\n", $html);
	}

	/**
	 * Parses the filter array and generates an sql query
	 *
	 * @return string
	 */
	function to_sql() {
		$filter_array = array();
		foreach($this->filters as $filter) {
			$value = $this->params[$filter['field']];
			if(!isset($value) || $value=='') {
				continue;
			}

			if(method_exists(Registry()->controller, 'getFilter_'.$filter['field'])) {
				$filter_array[] = Registry()->controller->{'getFilter_'.$filter['field']}($value);
			} else {
				if($filter["type"] == 'text') {
					$filter_array[] = "LOWER({$filter['field']}) LIKE '%" . strtolower(Registry()->db->escape($value)) . "%'";
				}
				else {
					$filter_array[] = "{$filter['field']} = '" . Registry()->db->escape($value) . "'";
				}
			}
		}

		return implode(" AND ", $filter_array);
	}

	/**
	 * Generates a filter array
	 *
	 * @return array $filter_array
	 */
	private function to_query_array() {
		$filter_array = array();

		foreach($this->params as $key => $val) {
			if ($key == 'order' || $key == 'page') {
				continue;
			}

			if (!empty($val)) {
				$filter_array[$key] = $val;
			}
		}

		return $filter_array;
	}

	function export_query() {
		$filter_array = array();

		foreach($this->params as $key => $val) {
			if ($key == 'order' || $key == 'page') {
				continue;
			}
			if (!empty($val)) {
				$filter_array[$key] = $val;
			}
		}

		return $filter_array;
	}

	/**
	 * Returns the generated list
	 *
	 * @return string
	 */
	function __toString() {
		return $this->generate();
	}
}

?>