<?php
require_once Config()->LIB_PATH . 'mailchimp/Mailchimp.php';
class Newsletter {
		/**
	 * API List ID reference
	 * @var char
	 */
	private $list_id = null;

	/**
	 * MailChimp API object loaded representation
	 * @var object
	 */
	protected $persistant_object;

	/**
	 * Load instance of the MailChimp API on creation
	 */
	public function __construct() {
		try {
			$this->persistant_object = new \Mailchimp(Config()->MAILCHIMP_API_KEY);
		} catch(\Exception $e) {
			return $e->getMessage();
		}
	}

	public function getLists($filters = array()) {
		$items = [];

		try {
			$items = $this->persistant_object->lists->getList($filters);
		} catch(\Exception $e) {
			return $e->getMessage();
		}

		return $items;
	}

	public function setListId($list_id = null) {
		if(!$list_id) {
			$lists = $this->getLists();
			!empty($lists) && $this->list_id = $lists['data'][0]['id'];
		} else {
			$this->list_id = $list_id;
		}
	}

	public function getListId() {
		!$this->list_id && $this->setListId();
		return  $this->list_id;
	}

	public function subscribe($email) {
		if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			throw new \Exception('Invalid email supplied ' . $email);
		}
		
		try {
			$this->persistant_object->lists->subscribe($this->getListId(), ['email' => $email]);
		} catch(\Exception $e) {
			return $e->getMessage();
		}

		return true;
	}
}