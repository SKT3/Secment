<?php

class Dispatcher {

	/**
	 * Reference to the current instance of the Request object
	 *
	 * @var object
	 * @access private
	 */
	private static $instance = null;

	/**
	 *  Instance of Request
	 *
	 * 	@var object
	 */
	private $request;

	/**
	 *  Instance of Response
	 *
	 * 	@var object
	 */
	private $response;

	/**
	 * Instance of the Controller
	 *
	 * @var object
	 */
	private $controller = null;

	/**
	 * Constructor
	 *
	 * @access private
	 */
	private function __construct() {
		$this->request = Request::getInstance();
		$this->response = Response::getInstance();
		try {
			ActionController::factory($this->request->recognize())->process($this->request, $this->response)->output();
		} catch (Exception $exception) {
			ActionController::process_with_exception($this->request, $this->response, $exception)->output();
		}
	}

	/**
	 * Returns an instance of the Dispatcher object
	 *
	 * @return Dispatcher
	 */
	static function dispatch() {
		if(self::$instance == null) {
			return self::$instance = new Dispatcher;
		}
	}
}

?>
