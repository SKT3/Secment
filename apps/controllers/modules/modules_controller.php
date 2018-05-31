<?php

class ModulesController extends ApplicationController {
	public
		$slug_suffix = '',
		$chained_module = null;

	protected $layout = 'index';

	protected $exempt = array(
		'login/index',
		'news/uploadify',
	);

	function __construct() {
		parent::__construct();
		$this->add_helper('fields');
		$this->add_before_filter('check_logged');
		$this->add_before_filter('check_permission');
		$this->add_helper('admin');
	}

	public static function block($obj) {
		$model_name = Inflector::camelize($obj->module).'Controller';
		$model = new $model_name;
		$model->{$obj->action}($obj);
	}

	protected function check_logged() {
		// if the user is already logged mark him and save his data
		if(isset($this->session->logged_in) && $this->session->logged_in && $this->session->is_admin) {
			$this->userinfo = unserialize($this->session->userinfo);
			$this->logged_in = true;
		}
		// if the user is not logged and not in the exempt - redirect to login page
		else if(isset($this->exempt) && !in_array($this->get_controller_name() . '/' . $this->get_action_name(), $this->exempt)) {
			$this->redirect_to(url_for(array("controller" => "login", "appsys" => "admin", "redirect_to" => $_SERVER['REQUEST_URI'])));
			return false;
		}
		// the third case is if the user is not logged but in the exempt - we do nothing
	}

	protected function check_permission() {
		$controller = $this->get_controller_name();
		if($controller == 'login') {
			return true;
		}
		$action = $this->get_action_name();
		if (empty($action)) {
			$action = 'index';
		}
		if(stripos($this->userinfo->permissions, '|' . $controller . '-' . $action.'|') !== false) {
			$this->view = '../home/restricted';
			return false;
		}

		if(stripos($this->userinfo->admin_group->permissions, '|' . $controller . '-' . $action.'|') !== false) {
			$this->view = '../home/restricted';
			return false;
		}
	}

	function getList_br($v) {
		return $this->localizer->get('br_values',$v);
	}

	/*
	* IF you want to use your own INDEX function - redefine it in your app
	*
	*/
	function index($params) {
		$model = $this->_get_model($params);

		$obj = new $model;
		$list = new ListHelper($params);
		if($this->admin_helper->can('edit')){
			$list->add_action('edit', url_for(array('controller'=>'admin','module' => $this->module,'maction'=>'edit', 'id' => ':id')));
		}
		if($this->admin_helper->can('delete')){
			$list->add_action('delete', 'javascript:confirm_delete(:id);');
		}

		$list->add_filter('title','null','text');

		$list->add_column('id');
		$list->add_column('title');

		$items = $obj->find_all($list->to_sql(), $obj->get_order(), $obj->rows_per_page_default);
		$list->data($items);
		$this->render($list);
		$this->session->admin_return_to = $this->request->server('REQUEST_URI');
	}

	/*
	* IF you want to use your own ADD function - redefine it in your app
	*
	*/
	function add($params) {
		$model = $this->_get_model($params);
		$obj = new $model;

		if($this->is_post()) {
			$response = new stdClass;
			$response->ok = 0;
			$response->errors = array();
			$response->redirect_to = '';

			if($obj->save($_POST)) {
				$this->handle_uploads($obj, ($obj->module ?: $this->module));
				$this->log_action(array('id' => $obj->id, 'message' => (string)$obj));
				$this->flash_message_helper[] = $this->localizer->get_label('FLASH_MESSAGES', __function__);
				$response->ok = 1;
				$response->redirect_to = url_for(array('controller' => 'admin', 'module' => $this->module, 'maction' => (!empty($params['chained_module']) ? $params['chained_module'] : 'index')));
			} else {
				$response->errors = $obj->errors;
			}
			$this->layout = 'json_save_callback';
			$this->content_for_layout = json_encode($response);
		} else {
			$this->view = 'admin_form';
			$this->fields = $this->fields_helper->generate($this->module, $obj);
		}
	}

	/*
	* IF you want to use your own EDIT function - redefine it in your app
	*
	*/
	function edit($params) {
		$model = $this->_get_model($params);

		$obj = new $model;
		$object = $obj->find((int)$params['id']);
		if($object) {
			if($this->is_post()) {
				$response = new stdClass;
				$response->ok = 0;
				$response->errors = array();
				$response->redirect_to = '';

				if($object->save($_POST)) {
					$this->log_action(array('id' => $object->id, 'message' => (string)$object));
					$this->flash_message_helper[] = $this->localizer->get_label('FLASH_MESSAGES', __function__);
					$response->ok = 1;
					$response->redirect_to = url_for(array('controller' => 'admin', 'module' => $this->module, 'maction' => (!empty($params['chained_module']) ? $params['chained_module'] : 'index')));
				} else {
					$response->errors = $object->errors;
				}
				$this->layout = 'json_save_callback';
				$this->content_for_layout = json_encode($response);
			}

			$this->form_object = $object;
			$this->view = 'admin_form';
			$this->fields = $this->fields_helper->generate($this->module, $object);
		} else {
			$this->redirect_to(url_for(array('controller' => 'admin', 'module' => $this->module, 'maction' => 'index')));
		}
	}

	/*
	* IF you want to use your own DELETE function - redefine it in your app
	*
	*/
	function delete($params) {
		$model = $this->_get_model($params);

		$obj = new $model;
		$object = $obj->find((int)$params['id']);
		if($object) {
			$object->delete();
			$this->log_action(array('id' => $object->id, 'message' => (string)$object));
			$this->flash_message_helper[] = $this->localizer->get_label('FLASH_MESSAGES', __function__);
			$this->redirect_to($this->session->admin_return_to);
		} else {
			$this->redirect_to(url_for(array('controller' => 'admin', 'module' => $this->module, 'maction' => (!empty($params['chained_module']) ? $params['chained_module'] : 'index'))));
		}
	}

	protected function belongs_to_autocomplete($params) {
		$obj_name = Inflector::camelize($params['object']);
		$obj = new $obj_name;
		$where = array();
		$where[] = "UPPER(".$params['column'].") LIKE '%".mb_strtoupper(Registry()->db->escape($params['q']))."%'";
		if ($obj->have_i18n()){
			$fields = 'i18n_foreign_key as id,'.Registry()->db->escape($params['column']);
			$where[] = "i18n_locale='".Registry()->locale."'";
			$table_name = $obj->table_name.'_i18n';
		} else {
			$fields = 'id,'.Registry()->db->escape($params['column']);
			$table_name = $obj->table_name;
		}
		if($params['conditions']){
			$where[] = $params['conditions'];
		}


		$all = Registry()->db->select($table_name, $fields, join(' AND ', $where));
		foreach($all as $a) {
			echo $a->id.'_::_'.$a->{$params['column']}."\n";
		}
	}

	protected function habtm_autocomplete($params) {
		$obj_name = Inflector::camelize($params['class_name']);
		$obj = new $obj_name;
		$where = array();
		$where[] = "UPPER(".$params['column'].") LIKE '%".mb_strtoupper(Registry()->db->escape($params['q']))."%'";

		if ($obj->have_i18n()) {
			$fields = 'i18n_foreign_key as id,'.Registry()->db->escape($params['column']);
			$where[] = "i18n_locale='".Registry()->locale."'";
			$table_name = $obj->table_name.'_i18n';
		} else {
			$fields = 'id,'.Registry()->db->escape($params['column']);
			$table_name = $obj->table_name;
		}

		if($params['conditions']){
			$where[] = $params['conditions'];
		}

		$all = Registry()->db->select($table_name,$fields,join(' AND ', $where));
		foreach($all as $a) {
			echo $a->id.'_::_'.$a->{$params['column']}."\n";
		}
	}

	protected function habtm_save($params) {
		$this->habtm_delete($params);

		$foreign_key = str_replace('_id','',$params['foreign_key']);
		$association_foreign_key = str_replace('_id','',$params['association_foreign_key']);
		$tables = array();
		$tables['one'] = Inflector::tableize($foreign_key);
		$tables['two'] = Inflector::tableize($association_foreign_key);
		asort($tables);

		Registry()->db->insert(join('_',$tables), array(
			$params['foreign_key']=>(int)$params['foreign_value'],
			$params['association_foreign_key']=>(int)$params['association_foreign_value']
		));

	}

	protected function habtm_delete($params) {
		$foreign_key = str_replace('_id','',$params['foreign_key']);
		$association_foreign_key = str_replace('_id','',$params['association_foreign_key']);
		$tables = array();
		$tables['one'] = Inflector::tableize($foreign_key);
		$tables['two'] = Inflector::tableize($association_foreign_key);
		asort($tables);

		Registry()->db->delete(join('_',$tables), array(
			$params['foreign_key']=>(int)$params['foreign_value'],
			$params['association_foreign_key']=>(int)$params['association_foreign_value']
		));

	}

	protected function log_action($params) {
		 $controller = $this->get_controller_name();
		 $action = $this->get_action_name();

		 if($action == 'xhr') {
			 $action_params = $this->get_action_params();
			 $action = $action_params['method'];
		 }

		 $log = new AdminLog();
		 $log->controller = $controller;
		 $log->action = $action;
		 $log->object_pk = (int)$params['id'];
		 $log->message = $params['message'];
		 $log->ip = $this->request->server('REMOTE_ADDR');
		 $log->admin_user_id = (int)$this->userinfo->id;
		 $log->save();
	}

	protected function upload_file($params){
		if($params['model']) {
			$model = new $params['model'];
		} else {
			$model = new $this->models[0];
		}

		if(isset($params['id']) && $params['id'] > 0) {
			if(isset($params['file_model'])) {
				$file = new $params['file_model']();
				$file->caption = '';
			} else {
				$file = new File();
			}
			
		} else {
			$file = new TempFile();
			$tmp = true;
		}

		$module = $model->module ?: $this->module;

		$file->has_many = $model->get_has_many();

		if(get_class($file) == 'FileExtra' && $module == 'partners') {
			//if we have has_one image and image is_required == true
		} else {
			$file->has_one = $model->get_has_one();	
		}
	
		$file->module = $module;
		$file->module_id = (int)$params['id'];
		$file->keyname = $params['keyname'];
		$file->file_extensions = $model->file_extensions;
		$file->save();

		$response = new stdClass();
		$response->id = $file->id;
		$response->name = $file->filename;
		$response->file = $file->get_file_path($tmp) . $file->filename;
		$response->size = $file->pretty_size();
		$response->keyname = $file->keyname;



		if($file->errors) {
			$response->error = $file->errors;
		}
		
		$response->delete_url = url_for(array('action' => 'xhr', 'method' => 'delete_file', 'id' => $file->id, 'temp' => $tmp));

		echo json_encode($response);
		exit;
	}

	protected function get_files($params) {
		$model = !empty($params['model']) ? $params['model'] : $this->models[0];
		$file_model = ($params['file_model']) ? : 'File';
		
		

		$model = new $model;
		$module = $model->module ?: $this->module;
		if(isset($params['id'])) {
			$handler = new $file_model();
			$files = $handler->find_all_by_module_id_and_keyname_and_module((int)$params['id'], $params['keyname'], $module, 'ord ASC');
		} else {
			$handler = new TempFile();
			$files = $handler->find_all_by_admin_user_id_and_module_and_keyname(unserialize(Registry()->session->userinfo)->id, $module, $params['keyname'], 'ord ASC');
			$tmp = true;
		}
		$response = array();
		foreach($files as $file) {
			$delete_url_arr = [];
			$delete_url_arr = array('action'=>'xhr', 'method'=>'delete_file', 'id'=>$file->id, 'temp' => $tmp);		
		
			if($params['file_model']) {
				$delete_url_arr += array("file_model" => $params['file_model']); 
			}

			$response['files'][] = array(
				'id'		 => $file->id,
				'name'		 => $file->filename,
				'file'		 => $file->get_file_path($tmp) . $file->filename,
				'size'	 	 => $file->pretty_size(),
				'keyname'	 => $file->keyname,
				'caption'	=> ($file->caption) ? ($file->caption) : '',
				'delete_url' => url_for($delete_url_arr)
			);
		}

		echo json_encode($response);
		exit;
	}

	protected function delete_file($params) {
		if($params['temp']) {
			$handler = new TempFile();
		} else {
			if($params['file_model']) {
				$handler = new $params['file_model'];
			} else {
				$handler = new File();
			}
		}

		$file = $handler->find((int)$params['id']);
		$file->delete();
		exit;
	}

	protected function sort_files($params) {
		if($params['temp']) {
			$handler = new TempFile();
		} else {
			$handler = new File();
		}

		foreach ($params['f'] as $key => $id) {
			Registry()->db->query('UPDATE '.$handler->table_name.' SET ord='.(int)$key.' WHERE id='.$id.'');
		}
		exit;
	}

	protected function files_extra_attributes($params) {
		if(isset($params['id'])) {
			if($params['action_type'] == edit) {
				$file_obj = new $params['file_model'];
			} else {
				$file_obj = new TempFile();
			}
		}
	
		$file = $file_obj->find($params['id']);
		$file->caption = $params['caption'];
		$file->save();
	
		if($file->errors) {
			$response->error = $file->errors;
		} else {
			$response->success = true;
		}
		echo json_encode($response);
		exit;
	}


	function handle_uploads($obj, $module) {
		$has_many = $obj->get_has_many();
		$has_one = $obj->get_has_one();

		if($has_many) {
			foreach($has_many as $keyname => $conditions) {
				if(in_array($conditions['class_name'], array('image', 'file', 'image_extra', 'file_extra'))) {
					$handler_name = Inflector::camelize($conditions['class_name']);
					$handler = new $handler_name();
					$handler->copy_temp($keyname, $module, $obj);
				}
			}
		}

		if($has_one) {
			foreach($has_one as $keyname => $conditions) {
				if(in_array($conditions['class_name'], array('image', 'file', 'image_extra', 'file_extra'))) {
					$handler_name = Inflector::camelize($conditions['class_name']);
					$handler = new $handler_name();
					$handler->copy_temp($keyname, $module, $obj);
				}
			}
		}

	}

	protected function generate_slug() {
		echo Inflector::slugalize($_POST['slug']);
	}

	protected function _get_model($params) {
		$chained_model = null;
		if(!empty($params['chained_module']) && in_array(Inflector::camelize($params['chained_module']) . 'Model', $this->models)) {
			// check if chained model is in separate file or his parent isn't already loaded
			try{
				$chained_model = Inflector::camelize($params['chained_module']) . 'Model';
				new $chained_model();
			} catch(Exception $e) {
				// if not, then load parent model in which the chained module is created
				new $this->models[0];
			}
		}
		return !empty($this->models) ? ($chained_model ? $chained_model : $this->models[0]) : new stdClass();
	}

	protected function _add_list_controllers_for_main_actions(ListHelper $list) {
		if(!empty($this->_main_action_controllers)) {
			foreach ($this->_main_action_controllers as $action => $label) {
				$list->add_main_action(array('link' => url_for(array('controller' => 'admin', 'module' => $this->module, 'maction' => $action)), 'label' => $label));
			}
		}
	}

	protected function save_order($params) {
		if($model = !empty($params['model']) && strstr($this->models, $params['model']) ? $params['model'] : is_array($this->models) ? $this->models[0] : (preg_match('#^[^\s]+#', $this->models, $matches) ? $matches[0] : false)) {
			$model::updateOrdering(explode(',', $_POST['ids']));
			$this->log_action();
			echo $this->localizer->get_label('KEYWORDS', 'order_saved');
		}
	}

	function getList_active($v){
		return $this->localizer->get('yesno',(int)$v);
	}

	protected function upload_image($params) {

		if($params['model']){
			$model_name = Inflector::modulize($params['model']);
			$model = new $model_name;
		} else {
			$model = new $this->models[0];
		}

		if(isset($params['id']) && $params['id'] > 0) {
			if(isset($params['image_model'])) {
				$image = new $params['image_model']();
				$image->caption = '';
			} else {
				$image = new Image();
			}
		} else {
			$image = new TempImage();
			$tmp = true;
		}

		$module = $model->module ?: $this->module;

		$image->id = 'NULL';
		$image->has_many = $model->get_has_many();
		$image->has_one = $model->get_has_one();
		$image->module = $module;
		$image->module_id = (int)$params['id'];
		$image->keyname = $params['keyname'];
		$image->thumbs = $model->thumbs;
		$image->image_options = $model->image_options;
		$image->image_extensions = $model->image_extensions;
		$image->save();

		$response = new stdClass();
		$response->id = $image->id;
		$response->name = $image->filename;
		$response->img = $image->get_file_path($tmp) . $image->filename;
		$response->size = $image->pretty_size();
		$response->keyname = $image->keyname;

		if($image->errors) {
			$response->error = $image->errors;
		}

		$response->delete_url = url_for(array('action'=>'xhr', 'method'=>'delete_image', 'id'=>$image->id, 'temp' => $tmp));

		echo json_encode($response);
		exit;
	}
	
	protected function images_extra_attributes($params) {
	
		if(isset($params['id'])) {
			if($params['action_type'] == edit) {
				$image_obj = new $params['image_model'];
			} else {
				$image_obj = new TempImage();
			}
		}
	
		$image = $image_obj->find($params['id']);
		$image->caption = $params['caption'];
		$image->save();
	
		if($image->errors) {
			$response->error = $image->errors;
		} else {
			$response->success = true;
		}
		echo json_encode($response);
		exit;
	}

	/*protected function get_images($params) {
		$model = !empty($params['model']) ? $params['model'] : $this->models[0];
		$model = new $model;
		$module = $model->module ?: $this->module;

		if(isset($params['id'])) {
			$image = new Image();
			$images = $image->find_all_by_module_id_and_module_and_keyname((int)$params['id'], $module, $params['keyname'], 'ord ASC');
		} else {
			$image = new TempImage();
			$images = $image->find_all_by_admin_user_id_and_module_and_keyname(unserialize(Registry()->session->userinfo)->id, $module, $params['keyname'], 'ord ASC');
			$tmp = true;
		}

		$response = array();
		foreach($images as $image) {
			$response['images'][] = array(
				'id'		 => $image->id,
				'name'		 => $image->filename,
				'img'		 => $image->get_file_path($tmp) . $image->filename,
				'size'	 	 => $image->pretty_size(),
				'keyname'	 => $image->keyname,
				'image_title'		 => $image->image_title,
				'delete_url' => url_for(array('action'=>'xhr', 'method'=>'delete_image', 'id'=>$image->id, 'temp' => $tmp))
			);
		}
		echo json_encode($response);
		exit;
	}
	*/
	protected function get_images($params) {
		$model = !empty($params['model']) ? $params['model'] : $this->models[0];
		$model = new $model;
		$module = $model->module ?: $this->module;
		if(isset($params['id'])) {
			if(isset($params['image_model'])) {
				$image = new $params['image_model']();
			} else {
				$image = new Image();
			}
			$images = $image->find_all_by_module_id_and_module_and_keyname((int)$params['id'], $module, $params['keyname'], 'ord ASC');
		} else {
			$image = new TempImage();
			$images = $image->find_all_by_admin_user_id_and_module_and_keyname(unserialize(Registry()->session->userinfo)->id, $module, $params['keyname'], 'ord ASC');
			$tmp = true;
		}
	
		$response = array();
		foreach($images as $image) {
			$response['images'][] = array(
					'id'		 => $image->id,
					'name'		 => $image->filename,
					'img'		 => $image->get_file_path($tmp) . $image->filename,
					'size'	 	 => $image->pretty_size(),
					'keyname'	 => $image->keyname,
					'caption'	=> ($image->caption) ? ($image->caption) : '',
					'delete_url' => url_for(array('action'=>'xhr', 'method'=>'delete_image', 'id'=>$image->id, 'temp' => $tmp, 'image_extra' => get_class($image) == 'ImageExtra' ? true : null)),
			);
		}
	
		echo json_encode($response);
		exit;
	}

	protected function delete_image($params) {
		$model = new $this->models[0];

		if($params['temp']){
			$image = new TempImage();
		} elseif ($params['image_extra']){
			$image = new ImageExtra();
		}
		 else {
			$image = new Image();
		}

		$object = $image->find((int)$params['id']);
		$object->thumbs = $model->thumbs;
		$object->delete();
		exit;
	}

	protected function sort_images($params) {
		if($params['temp']) {
			$image = new TempImage();
		} elseif ($params['f']) {
            $image = new Image();
		}
		 else {
             $image = new ImageExtra();
		}

		foreach ($params['f'] as $key => $id) {
			Registry()->db->query('UPDATE '.$image->table_name.' SET ord='.(int)$key.' WHERE id='.$id.'');
		}
		exit;
	}

	protected function get_tree($params) {
		$tree = [];
		$tree['selected'] = [(int)$params['category_id']];
		
		$_all  = Registry()->db->query('SELECT id,parent_cat_id,title,lvl FROM categories LEFT JOIN categories_i18n ON categories.id=categories_i18n.i18n_foreign_key AND i18n_locale="'.Registry()->locale.'" ORDER BY lvl DESC');
		foreach($_all as $a) {
			$tree[$a->parent_cat_id]['items'][$a->id] = $a->title;
			$tree['parents'][$a->id] = $a->parent_cat_id;
			if($a->id == $tree['selected'][0] && $a->parent_cat_id>0) {
				array_unshift($tree['selected'], $a->parent_cat_id);
			}
		}

		$response = new stdClass;
		$response->tree = $tree;
		echo json_encode($response);
		exit;
	}
}

?>