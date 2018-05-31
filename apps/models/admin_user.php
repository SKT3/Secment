<?php

class AdminUser extends ActiveRecord {

	protected $belongs_to = 'admin_group';

	function __toString() {
		return $this->username;
	}

	function before_validation() {
		if (empty($_POST['userpass'])) {
			$this->userpass = $this->find_by_id($this->id)->userpass;
		} else {
			$this->userpass = md5($this->userpass);
		}

		$group = $this->admin_group();
		if($_POST['permissions']) {
			$permissions = array_unique(array_merge(explode('|', trim($group->permissions, '|')), $_POST['permissions']));
			natsort($permissions);
			$this->permissions = '|' . implode('|', $permissions) . '|';
		} else {
			$this->permissions = $group->permissions;
		}
	}
}

?>