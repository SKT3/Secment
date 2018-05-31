<?php

class GroupsController extends AdminController {
	public
		$models = 'AdminGroup',
		$table = 'admin_groups';

	function index($params) {
		$list = new ListHelper($params);
		$list->add_column('id');
		$list->add_column('title', null, false);

		if($this->admin_helper->can('edit')){
			$list->add_action('edit', url_for(array('controller' => 'groups', 'action' => 'edit', 'id' => ':id')));
		}
		if($this->admin_helper->can('delete')){
			$list->add_action('delete', 'javascript:confirm_delete(:id);');
		}

		$items_collections = $this->AdminGroup->find_all(null, $this->AdminGroup->get_order(), 30);
		$list->data($items_collections);
		$this->render($list);
		$this->session->admin_return_to = $this->request->server('REQUEST_URI');
	}

	function add() {
		if ($this->is_post()) {
			if($this->AdminGroup->save($this->request->post())) {
				$this->log_action(array('id'=>$this->AdminGroup->id,'message' => (string)$this->AdminGroup));
				$this->flash_message_helper[] = $this->localizer->get_label('FLASH_MESSAGES', 'add');
				$this->redirect_to($this->session->admin_return_to);
			} else {
				$this->errors = $this->AdminGroup->get_errors();
				$this->form_object = (object)$this->request->post();
			}
		}

		// permissions
		$this->permissions = $this->admin_helper->get_permissions();
	}

	function edit($params) {
		$item = $this->AdminGroup->find((int)$params['id']);
		if (!$item instanceof AdminGroup) {
			$this->redirect_to(url_for(array('controller' => 'groups')));
		}
		if ($this->is_post()) {
			if(empty($_POST['permissions'])){
				$_POST['permissions'] = '';
			}

			if($item->save($_POST)) {
				$this->log_action(array('id'=>$item->id,'message' => (string)$item));
				$this->flash_message_helper[] = $this->localizer->get_label('FLASH_MESSAGES', 'edit');
				$this->redirect_to($this->session->admin_return_to);
			} else {
				$this->errors = $item->get_errors();
				$this->form_object = (object)$this->request->post();
			}
		} else {
			$item->permissions = explode('|', $item->permissions);
			$this->form_object = $item;
		}

		// permissions
		$this->permissions = $this->admin_helper->get_permissions();
		$this->current_permissions = $item->permissions;
	}

	function delete($params) {
		$item = $this->AdminGroup->find((int)$params['id']);
		if ($item instanceof AdminGroup && (int)$params['id'] != 1) {
			$item->delete();
			$this->log_action(array('id'=>$item->id,'message' => (string)$item));
			$this->flash_message_helper[] = $this->localizer->get_label('FLASH_MESSAGES', 'delete');
		}
		$this->redirect_to($this->session->admin_return_to);
	}
}

?>