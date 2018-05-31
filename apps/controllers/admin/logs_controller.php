 <?php

class LogsController extends AdminController {
	public $models = 'AdminLog AdminUser';

	function index($params) {
		$list = new ListHelper($params);
		$list->add_filter('admin_user_id',$this->AdminUser->find_all(null,'username ASC'),'select','username');
		$list->add_column('created_at');
		$list->add_column('ip');
		$list->add_column('controller');
		$list->add_column('action');
		$list->add_column('object_pk');
		$list->add_column('message');
		$list->add_column('admin_user_id');

		$data = $this->AdminLog->find_all($list->to_sql(),'created_at DESC',100);

		$list->hide_main_actions = true;
		$list->data($data);
		$this->render($list);
	}

	function getList_admin_user_id($v) {
		return $this->AdminUser->find($v)->username;
	}

	function getList_action($v) {
		if($v=='index') {
			return '-';
		} else {
			return $v;
		}
	}
}

?>