<?php

class AdminGroup extends ActiveRecord {
	protected $has_many = "admin_user";

	function __toString() {
		return $this->title;
	}

	function before_validation() {
		if($_POST['permissions']) {
			$permissions = $_POST['permissions'];
			natsort($permissions);
			$this->permissions = $permissions;
			$this->permissions = '|' . implode('|', $this->permissions) . '|';

		}

		if($_POST['newsletter_permissions']) {
			$newsletter_permissions = $_POST['newsletter_permissions'];
			natsort($newsletter_permissions);
			$this->newsletter_permissions = $newsletter_permissions;
			$this->newsletter_permissions = '|' . implode('|', $this->newsletter_permissions) . '|';
		}
	}

	function before_save() {
		if (!$this->new_record) {
			$permissions = $this->find_by_id($this->id)->permissions;
			if ($permissions != $this->permissions) {
				$queries = array();

				foreach (explode('|', trim($permissions, '|')) as $permission) {
					$queries[] = "UPDATE " . Config()->DB_PREFIX . "admin_users SET permissions = REPLACE(permissions, '|" . $permission . "|', '|') WHERE admin_group_id = " . $this->id;
				}
				$queries[] = "UPDATE " . Config()->DB_PREFIX . "admin_users SET permissions = CONCAT(permissions, '" . substr($this->permissions, 1) . "') WHERE admin_group_id = " . $this->id;
				foreach ($queries as $query) {
					Registry()->db->query($query);
				}
			}

			$newsletter_permissions = $this->find_by_id($this->id)->newsletter_permissions;
			if ($newsletter_permissions != $this->newsletter_permissions) {
				$queries = array();
				foreach (explode('|', trim($newsletter_permissions, '|')) as $newsletter_permission) {
					$queries[] = 'UPDATE ' . Config()->DB_PREFIX . "admin_users SET newsletter_permissions = REPLACE(newsletter_permissions, '|" . $newsletter_permission . "|', '|') WHERE admin_group_id = " . $this->id;
				}

				$queries[] = 'UPDATE ' . Config()->DB_PREFIX . "admin_users SET newsletter_permissions = CONCAT(newsletter_permissions, '" . substr($this->newsletter_permissions, 1) . "') WHERE admin_group_id = " . $this->id;
				foreach ($queries as $query) {
					Registry()->db->query($query);
				}
			}
		}
	}

}

?>