<?php

class PagesController extends AdminController {
	public
		$models = 'Page',
		$mobules = array(),
		$layouts = array(),
		$slug_suffix = '',
		$page_icons = array();

	protected
		$_restricted_page_ids = array(0);

	public function __construct() {
		parent::__construct();
		$this->get_layouts();
	}

	function index($params) {
		$this->page = $this->Page->find_first('parent_id=0');
	}

	function add($params) {
		if(isset($params['section'])) {
			$this->section = $this->Page->find_by_id((int)$params['section']);
			$this->pages = $this->section->get_children();
		} else {
			$this->section = $this->Page->find_first('parent_id=0');
			$this->pages = $this->section->get_children();
		}

		if ($this->is_post()) {
			settype($_POST['active'], 'int');
			settype($_POST['custom_slug'], 'int');
			if ($this->Page->save($_POST)) {
				$this->log_action(array('id'=>$this->Page->id,'message' => (string)$this->Page));
				$this->flash_message_helper[] = $this->localizer->get_label('FLASH_MESSAGES', __FUNCTION__);
				if($params['section']){
					$this->redirect_to(url_for(array('action' => 'index', 'section' => $params['section'])));
				} else{
					$this->redirect_to(url_for(array('action' => 'index')));
				}
			} else {
				$this->form_object = (object) $_POST;
			}
		}

		$this->include_css('blocks');
	}

	function edit($params) {
		if(isset($params['section'])) {
			$this->section = $this->Page->find_by_id((int)$params['section']);
			$this->pages = $this->section->get_children();
		} else {
			$this->section = $this->Page->find_first('parent_id=0');
			$this->pages = $this->section->get_children();
		}

		$root = $this->Page->find_first('parent_id=0');
		$this->all_pages = $root->get_children();
		$this->form_object = $this->Page->find((int) $params['id']);
		$this->options = unserialize($this->form_object->options);

		if($this->is_post()) {
			settype($_POST['active'], 'int');
			settype($_POST['banners_items'], 'array');
			settype($_POST['carousel_items'], 'array');

			settype($_POST['custom_slug'], 'int');

			foreach($_POST['options']['order'] as $k => $id) {
				if(!in_array($id, $_POST['options']['banners'])) {
					unset($_POST['options']['order'][$k]);
				}
			}
			foreach($_POST['options']['banners'] as $id) {
				if(!in_array($id, $_POST['options']['order'])){
					$_POST['options']['order'][] = $id;
				}
			}

			foreach($_POST['options']['order_carousels'] as $k => $id) {
				if(!in_array($id, $_POST['options']['carousels'])) {
					unset($_POST['options']['order_carousels'][$k]);
				}
			}
			foreach($_POST['options']['carousels'] as $id) {
				if(!in_array($id, $_POST['options']['order_carousels'])){
					$_POST['options']['order_carousels'][] = $id;
				}
			}

			$_POST['options'] = serialize($_POST['options']);
			if ($this->form_object->save($_POST)) {
				$this->log_action(array('id'=>$this->form_object->id,'message' => (string)$this->form_object));
				$this->flash_message_helper[] = $this->localizer->get_label('FLASH_MESSAGES', __FUNCTION__);
				$this->redirect_to(url_for(array('action' => 'index')));
			} else {
				$this->form_object = (object) $_POST;
				$this->options = unserialize($_POST['options']);
			}
		}




		// Fancytree
		$this->include_javascript('../lib/fancytree/jquery.fancytree-all-modified');
		$this->include_css('../../js/lib/fancytree/skin-lion/ui.fancytree', 'blocks');
	}

	function delete($params) {
		if(!in_array($params['id'], $this->_restricted_page_ids)) {
			$item = $this->Page->find((int)$params['id']);
			if ($item instanceof Page) {
				$this->log_action(array('id'=>$item->id,'message' => (string)$item->id));
				$item->delete();
				$this->flash_message_helper[] = $this->localizer->get_label('FLASH_MESSAGES', 'delete');
			}
		}
		$this->redirect_to(url_for(array('action' => 'index')));
	}

	function page_content($params) {
		$this->form_object = $this->Page->find_by_id((int)$params['id']);
		$this->layout = '../public/'.$this->form_object->layout;
		$this->class = $this->form_object->css_class;
		$this->include_javascript('tinymce4/tinymce.min');
	}




	// Get template data only for module Template
	protected function get_template($params) {
		$object = $this->Block->find_by_id((int)$params['block']);
		$widget_name = Inflector::camelize($object->module).'Widget';
		if($object->module_id > 0){
			$model_name = Inflector::camelize($object->module).'Model';
			$model = new $model_name;
			$obj = $model->find_by_id($object->module_id);
		}else{
			$model_name = Inflector::camelize($object->module).'Model';
			$obj = new $model_name;
		}
		$options = array(
			'template'   => $params['template'],
			'obj'	   => $obj
		);
		echo $widget_name::getInstance()->render_template($options);
		exit;
	}


	protected function getContent() {
		foreach($_POST['widgets'] as $w) {
			$id = str_replace('widget_', '', $w['id']);
			$object = $this->Block->find_by_id($id);
			$widget_name = Inflector::camelize($object->module) . 'Widget';
			$widget = $widget_name::getInstance();
			$response[$w['id']] = $widget->{$object->action}($object);
		}
		echo json_encode($response);
	}

	protected function savePageSettings() {
		$page = $this->Page->find((int)$_POST['id']); // Important not to be find_by_id !!!

		if($page instanceof Page) {

			if(isset($_POST['options'])) {
				foreach($_POST['options']['order'] as $k => $id) {
					if(!in_array($id, $_POST['options']['banners'])) {
						unset($_POST['options']['order'][$k]);
					}
				}
				foreach($_POST['options']['banners'] as $id) {
					if(!in_array($id, $_POST['options']['order'])){
						$_POST['options']['order'][] = $id;
					}
				}

				foreach($_POST['options']['order_carousels'] as $k => $id) {
					if(!in_array($id, $_POST['options']['carousels'])) {
						unset($_POST['options']['order_carousels'][$k]);
					}
				}
				foreach($_POST['options']['carousels'] as $id) {
					if(!in_array($id, $_POST['options']['order_carousels'])){
						$_POST['options']['order_carousels'][] = $id;
					}
				}


				$_POST['options'] = serialize($_POST['options']);
			} else {
				$_POST['options'] = serialize(array());
			}

			settype($_POST['custom_slug'], 'int');
			if($page->save($_POST)){
				echo json_encode(array('success'=>1));
			} else {
				foreach($this->errors as $field => $error){
					$errorMsg .= $field . ' : ' . $error . '<br />';
				}
				echo json_encode(array('errors' => $errorMsg));
			}
		} else {
			foreach($this->errors as $field => $error) {
				$errorMsg .= $field . ' : ' . $error . '<br />';
			}
			echo json_encode(array('errors' => $errorMsg));
		}
	}

	protected function savePagePlaceholders($params) {
		$page = $this->Page->find((int)$_POST['id']); // Important not to be find_by_id !!!
		if($page instanceof Page) {
			if($params['type'] == 'delete') {
				$blocks = $this->Block->find_all_by_page_id_and_position($page->id, $params['placeholder']);
				foreach ($blocks as $block) {
					$block->delete();
				}
				 if(Registry()->db->query("UPDATE pages_i18n SET placeholders = placeholders - 1 WHERE i18n_locale = '".Registry()->locale."' AND i18n_foreign_key = ".$page->id)) {
					echo json_encode(array('success'=>1));
				}
			} else {
				 if(Registry()->db->query("UPDATE pages_i18n SET placeholders = placeholders + 1 WHERE i18n_locale = '".Registry()->locale."' AND i18n_foreign_key = ".$page->id)) {
					echo json_encode(array('success'=>1));
				}
			}
		} else {
			echo json_encode(array('error' =>1));
		}
	}

	protected function saveMenu($params) {
		$page = $this->Page->find((int)$params['object_id']);
		if($params['prev_id']) {
			$prev_page = $this->Page->find((int)$params['prev_id']);
			if($page->parent_id==$prev_page->parent_id) {
				$page->move_after($prev_page);
			}

		} elseif($params['next_id']) {
			$next_page = $this->Page->find((int)$params['next_id']);
			if($page->parent_id==$next_page->parent_id) {
				$page->move_before($next_page);

			}
		}

		$page->after_save();

	}

	function get_layouts() {
		/*$dir = Config()->LAYOUTS_PATH . 'public';
		$layouts = scandir($dir);
		foreach($layouts as $layout) {
			if($layout != 'xhr_layout.htm') {
				if(substr($layout, -11) == '_layout.htm') {
					$this->layouts[] = substr($layout, 0, strlen($layout) - 11);
				}
			}
		}*/

		$this->layouts[] = 'index';
	}

	protected function generate_slug() {
		if(!empty($_POST['parent_slug'])) {
			$url = '/' . trim($_POST['parent_slug'], '/') . '/';
		} else {
			$url = '';
		}

		$url = str_replace('//','/',$url);
		echo str_replace($this->slug_suffix, '', $url . Inflector::slugalize($_POST['slug'])) . $this->slug_suffix;
	}

	protected function get_tree() {
		$root = $this->Page->find_first('parent_id=0');
		// Fetch the flat tree
		$rawtree = $root->get_tree();

		// Init variables needed for the array conversion
		$tree = array();
		$node =& $tree;
		$depth = 0;
		$position = array();
		$lastitem = '';

		foreach($rawtree as $rawitem) {
			// If its a deeper item, then make it subitems of the current item
			if ($rawitem->get_level_value() > $depth) {
				$position[] =& $node; //$lastitem;
				$depth = $rawitem->get_level_value();
				$node =& $node[$lastitem]['children'];
			}
			// If its less deep item, then return to a level up
			else {
				while ($rawitem->get_level_value() < $depth) {
					end($position);
					$node =& $position[key($position)];
					array_pop($position);
					$depth = $node[key($node)]['node']->get_level_value();
				}
			}

			// Add the item to the final array
			$node[$rawitem->id]['node'] = $rawitem;
			$node[$rawitem->id]['title'] = $rawitem->title;
			$node[$rawitem->id]['key'] = $rawitem->id;
			$node[$rawitem->id]['image'] = $rawitem->image;
			if($rawitem->get_children()) {
				$node[$rawitem->id]['folder'] = true;
			}
			// save the last items' name
			$lastitem = $rawitem->id;
		}

		// we don't care about the root node
		reset($tree);
		$tree = $tree[key($tree)]['children'];

		$test = self::array_unset_recursive($tree, 'node');

		echo $this->render_partial('get_tree', $test[1]['children']);
		exit;

	}

	function array_unset_recursive(&$array, $remove) {
		foreach ($array as $key => &$value) {
			if ($key == $remove) {
				unset($array[$key]);
			} else if (is_array($value)) {
				self::array_unset_recursive($value, $remove);
			}
		}
		return $array;
	}

	protected function page_icons() {
		return $icons = array(
			'0' => 'Моля изберете иконка',
			'xe907' => '&#xe907;',
			'xe906' => '&#xe906;',
			'xe905' => '&#xe905;',
			'xe904' => '&#xe904;',
			'xe903' => '&#xe903;',
			'xe902' => '&#xe902;',
		);
	}

}

?>