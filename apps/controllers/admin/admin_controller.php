<?php

class AdminController extends ApplicationController {
	protected $layout = 'index'; 

	protected $exempt = array(
		'login/index',
	);

	function __construct() {
		parent::__construct();
		$this->add_before_filter('check_logged');
		$this->add_before_filter('check_permission');
		$this->add_helper('fields');
		$this->add_helper('admin');
		$this->add_helper('tree');
		$this->add_helper('hausmeister');
	}

	protected function check_logged() {
		// if the user is already logged mark him and save his data
		if(isset($this->session->logged_in) && $this->session->logged_in && $this->session->is_admin) {
			$this->userinfo = unserialize($this->session->userinfo);
			$this->logged_in = true;
		}
		// if the user is not logged and not in the exempt - redirect to login page
		else if(isset($this->exempt) && !in_array($this->get_controller_name() . '/' . $this->get_action_name(), $this->exempt)) {
			$this->redirect_to(url_for(array("controller" => "login", "redirect_to" => $this->get_controller_name() == 'admin' ? null : $_SERVER['REQUEST_URI'])));
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

	function index($params) {
		$this->redirect_to(url_for(array("controller" => "home")));
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

	static function getActions() {
		return array('index', 'add', 'edit', 'delete');
	}
}

?>