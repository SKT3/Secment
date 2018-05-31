<?php

class ApplicationController extends ActionController {
    public $breadcrumbs = array();

	private $_newsletter_autologin_secret = null;
	protected $layout = array(
		'404' => array(':only' => array('error404')),
		'xhr' => array(':only' => array('xhr')),
		'index' => array(':except' => array('xhr'))
	);

	function __construct() {
		parent::__construct();

		$this->add_helper('thumbnail');
		if(Registry()->is_public) {
			$this->add_before_filter('_loadNeededVariables');
			$this->add_after_filter('assign_public_vars');
            $this->add_after_filter('_setCurrentUrl');
		} else {
			//$this->add_before_filter('_prepareNewsletterForCMS');
		}
	}


    /**
     * Assign DEVELOPMENT config variable.
     * Used in public layout to know whether to include google analytics code or not.
     */
    protected function assign_public_vars() {
		Registry()->tpl->assign('IN_DEVELOPMENT', Config()->DEVELOPMENT);
    }

    protected function _loadNeededVariables() {

	}


	protected function _setCurrentUrl() {
		$request = Request::getInstance();
		$current_url = sprintf(
			'%s%s%s',
			$request->get_protocol(),
			$request->get_host_and_port(),
			$request->url
		);

		$this->url_for_og = $current_url;
	}


	function index($params) {}

	function error404() {
		$this->layout = 404;
		$this->_view = '__no_view';
		$this->_is404 = true;
		//http_response_code(404);
	}

	function xhr($params) {
        if ($this->is_xhr()) {
			$this->layout = 'xhr';

			if(!empty($_POST)) {
				$params = $params + $_POST;
			}

			$method = $params['method'];
			unset($params['method']);
			
			if(Registry()->is_public && !empty($params['module'])) {
				if(!$module_key = array_search($params['module'], (array)Config()->MODULES)) {
					goto response404;
				}

				if(!isset($params['class'])) {
					$module = Inflector::camelize($params['module']) . 'Widget';
					$widget = new $module;
				} else {
					if(!file_exists($module_file = Config()->MODULES_PATH . Config()->MODULES[$module_key] . DIRECTORY_SEPARATOR .'Widgets.php')) {
						goto response404;
					}

					include_once($module_file);

					$module = Inflector::camelize($params['class']);
					if(!$widget = new $module) {
						$module = Inflector::camelize($params['module']);
						$widget = new $module;
					}

					unset($params['class']);
				}

				unset($module, $params['module']);


				if(!method_exists($widget, $method)) {
					goto response404;
				}

				$this->content_for_layout = $widget->{$method}($params);
				empty($this->content_for_layout) && ($this->content_for_layout = ' ');
			} else {
				if(!method_exists($this, $method)) {
					goto response404;
				}

				$this->{$method}($params);	
			}

			goto responseSuccess;


			response404: {
				//http_response_code(404);
				$this->content_for_layout = 'Method `' . $method . '` not found in: ' . get_class($this);
			}

			responseSuccess: {}
		} else {
			$this->error404();
		}
	}

	/*
	 Handlers for automatic file and image uploads
	 */
	protected function upload_image($params) {
		if($params['model']){
			$model_name = Inflector::modulize($params['model']);
			$model = new $model_name;
		} else {
			$model = new $this->models[0];
		}

		if(isset($params['id']) && $params['id'] > 0) {
			$image = new Image();
		} else {
			$image = new TempImage();
			$tmp = true;
		}

		$module = $model->table_name ? $model->table_name : $this->module;

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

	protected function get_images($params) {
		$model = !empty($params['model']) ? $params['model'] : $this->models[0];
		$model = new $model;
		$module = $model->table_name ? $model->table_name : $this->module;

		if(isset($params['id']) && $params['id']) {
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
				'delete_url' => url_for(array('action'=>'xhr', 'method'=>'delete_image', 'id'=>$image->id, 'temp' => $tmp))
			);
		}

		if(!empty($params['return'])) {
			return $response;
		} else {
			echo json_encode($response);
			exit;
		}
	}

	protected function delete_image($params) {
		$model = new $this->models[0];

		if($params['temp']){
			$image = new TempImage();
		} else {
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
		} else {
			$image = new Image();
		}

		foreach ($params['f'] as $key => $id) {
			Registry()->db->query('UPDATE '.$image->table_name.' SET ord='.(int)$key.' WHERE id='.$id.'');
		}
		exit;
	}

	protected function upload_file($params){
		if($params['model']) {
			$model = new $params['model'];
		} else {
			$model = new $this->models[0];
		}

		if(isset($params['id']) && $params['id'] > 0) {
			$file = new File();
		} else {
			$file = new TempFile();
			$tmp = true;
		}

		$module = $model->table_name ? $model->table_name : $this->module;

		$file->has_many = $model->get_has_many();
		$file->has_one = $model->get_has_one();
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
		$model = new $model;
		$module = $model->module ?: $this->module;
		$module = $model->table_name ? $model->table_name : $this->module;

		if(isset($params['id']) && $params['id']) {
			$handler = new File();
			$files = $handler->find_all_by_module_id_and_keyname_and_module((int)$params['id'], $params['keyname'], $module, 'ord ASC');
		} else {
			$handler = new TempFile();
			$files = $handler->find_all_by_admin_user_id_and_module_and_keyname(unserialize(Registry()->session->userinfo)->id, $module, $params['keyname'], 'ord ASC');
			$tmp = true;
		}

		$response = array();
		foreach($files as $file) {
			$response['files'][] = array(
				'id'		 => $file->id,
				'name'		 => $file->filename,
				'file'		 => $file->get_file_path($tmp) . $file->filename,
				'size'	 	 => $file->pretty_size(),
				'keyname'	 => $file->keyname,
				'delete_url' => url_for(array('action'=>'xhr', 'method'=>'delete_file', 'id'=>$file->id, 'temp' => $tmp))
			);
		}

		if(!empty($params['return'])) {
			return $response;
		} else {
			echo json_encode($response);
			exit;
		}
	}

	protected function delete_file($params) {
		if($params['temp']) {
			$handler = new TempFile();
		} else {
			$handler = new File();
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

	function handle_uploads($obj, $module) {
		$has_many = $obj->get_has_many();
		$has_one = $obj->get_has_one();

		if($has_many) {
			foreach($has_many as $keyname => $conditions) {
				if(in_array($conditions['class_name'], array('image', 'file'))) {
					$handler_name = Inflector::camelize($conditions['class_name']);
					$handler = new $handler_name();
					$handler->copy_temp($keyname, $module, $obj);
				}
			}
		}

		if($has_one) {
			foreach($has_one as $keyname => $conditions) {
				if(in_array($conditions['class_name'], array('image', 'file'))) {
					$handler_name = Inflector::camelize($conditions['class_name']);
					$handler = new $handler_name();
					$handler->copy_temp($keyname, $module, $obj);
				}
			}
		}

	}


	protected function send_contact_message($params) {
		$this->_sendMessage($params, new Contact(), 'CONTACT_EMAIL_TEMPLATE');
	}

	private function _sendMessage($params, $object, $label_key) {
		header('Content-type: application/json');

		$result = array('status' => 'error');

		if($object->send_message($params + ['captcha_confirmation' => Registry()->session->captcha, 'label_key' => $label_key])) {
			$result['status'] = 'success';
			$result['message'] = $this->localizer->get_label($label_key, 'status')['success'];
		} else {
			$result['errors'] = $object->get_errors();
		}

		$this->content_for_layout = json_encode($result);
	}


    protected function _convertSlugToPagesFormat($slug) {
        $slug = trim($slug, '/');
        $slug_parts = explode('/', $slug);

        $return = array();

        foreach ($slug_parts as $idx => $slug_part) {
            $return['lvl'.($idx + 1)] = $slug_part;
        }

        return $return;
    }
}
?>