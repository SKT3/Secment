<?php

class LoginController extends AdminController {
	protected $layout = 'login';

	function index($params) {
		if ($this->is_post()) {
			$user = new AdminUser;
			$user = $user->find_by_username_and_userpass($this->request->post('user'), md5($this->request->post('pass')));

			if ($user instanceof AdminUser) {
				$this->session->logged_in = 1;
				$this->session->is_admin = 1;
				$this->session->userinfo = serialize($user);
				$this->log_action(array('id'=>$user->id,'message' => $user->username));
				$this->redirect_to(!empty($params['redirect_to']) && strpos($params['redirect_to'], Config()->COOKIE_PATH) === 0 ? $params['redirect_to'] : url_for(array('controller' => 'home')));
			} else {
				$this->log_action(array('message' => 'Invalid Login. User "'.$this->request->post('user').'" / Password "'.$this->request->post('pass').'"'));
				$this->invalid_login = $this->localizer->get('KEYWORDS', 'invalid_login');
			}
		} else {
			if(isset($this->userinfo) && $this->userinfo instanceof AdminUser) {
				$this->redirect_to(url_for(array('controller' => 'home')));
			}
		}

	}

	function logout() {
		unset($this->session->is_admin);
		unset($this->session->logged_in);
		unset($this->session->userinfo);
		$this->log_action(array('id'=>$this->userinfo->id,'message' => (string)$this->userinfo));
		$this->redirect_to(url_for(array('controller' => 'login')));
	}
}

?>