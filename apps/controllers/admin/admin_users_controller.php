<?php

class AdminUsersController extends AdminController {
	public
		$table = 'admin_user',
		$models = 'AdminGroup AdminUser';

	/**
	 * Users list method
	 *
	 * @param array $params
	 */
	function index($params) {
		$list = new ListHelper($params);

		// collect groups data
		$admin_groups = array();
		foreach ($this->AdminGroup->find_all(null, 'title ASC') as $group) {
			$admin_groups[$group->id] = $group->title;
		}

		$list->add_filter('admin_group_id', $admin_groups);
		$list->add_filter('username', null, 'text');

		$list->add_column('id');
		$list->add_column('username');
		$list->add_column('admin_group_id');

		if($this->admin_helper->can('edit')){
			$list->add_action('edit', url_for(array('controller' => 'admin_users', 'action' => 'edit', 'id' => ':id')));
		}
		if($this->admin_helper->can('delete')){
			$list->add_action('delete', 'javascript:confirm_delete(:id);');
		}

		// get users data
		$items_collection = $this->AdminUser->find_all($list->to_sql(), $this->AdminUser->get_order(), 30);
		foreach ($items_collection as $item) {
			$item->admin_group_id = $admin_groups[$item->admin_group_id];
		}
		$list->data($items_collection);

		$this->render($list);
		$this->session->admin_return_to = $this->request->server('REQUEST_URI');
	}

	/**
	 * Add method
	 *
	 * @param array $params
	 */
	function add($params) {
		if ($this->is_post()) {
			if($this->AdminUser->save($this->request->post())) {
				$this->log_action(array('id'=>$this->AdminUser->id,'message' => (string)$this->AdminUser));
				$this->flash_message_helper[] = $this->localizer->get_label('FLASH_MESSAGES', 'add');
				$this->redirect_to($this->session->admin_return_to);
			} else {
				$this->errors = $this->AdminUser->get_errors();
				$this->form_object = (object)$this->request->post();
			}
		}

		// collect groups data
		$this->admin_groups = array();
		foreach ($this->AdminGroup->find_all(null, 'title ASC') as $group) {
			$this->admin_groups[$group->id] = $group->title;
		}
		// permissions
		$this->permissions = $this->admin_helper->get_permissions();
	}

	/**
	 * Edit method
	 *
	 * @param array $params
	 */
	function edit( $params ) {
		$item = $this->AdminUser->find((int)$params['id']);
		if (!$item instanceof AdminUser) {
			$this->redirect_to(url_for(array('controller' => 'admin_users')));
		}
		if ($this->is_post()) {
			if($item->save($this->request->post())) {

				$this->log_action(array('id'=>$item->id,'message' => (string)$item));
				$this->flash_message_helper[] = $this->localizer->get_label('FLASH_MESSAGES', 'edit');
				$this->redirect_to($this->session->admin_return_to);
			} else {
				$this->errors = $item->get_errors();
				$this->form_object = (object)$this->request->post();
			}
		}else {
			$item->permissions = explode('|', trim($item->permissions, '|'));
			$this->form_object = $item;
		}

		// collect groups data
		$this->admin_groups = array();
		foreach ($this->AdminGroup->find_all(null, 'title ASC') as $group) {
			$this->admin_groups[$group->id] = $group->title;
		}
		// permissions
		$this->permissions = $this->admin_helper->get_permissions();
		$this->current_permissions = $item->permissions;
	}

	/**
	 * Delete method
	 *
	 * @param array $params
	 */
	function delete($params) {
		$item = $this->AdminUser->find((int)$params['id']);
		if ($item instanceof AdminUser && (int)$params['id'] != 1) {
			$item->delete();
			$this->log_action(array('id'=>$item->id,'message' => (string)$item));
			$this->flash_message_helper[] = $this->localizer->get_label('FLASH_MESSAGES', 'delete');
		}
		$this->redirect_to($this->session->admin_return_to);
	}

	/**
	 * Ajax Methods
	 */
	protected function get_permissions($params) {
		$group = $this->AdminGroup->find((int)$params['id']);
		echo (string)$group->permissions;
	}
}

?>