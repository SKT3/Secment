<?php
	/**
	 * Sweboo bootstrap. Initialize the framework and dispatch the request
	 */

	require_once(str_replace('\\', '/', dirname(dirname(__FILE__))) . '/core/sweboo.php');
	Dispatcher::dispatch();
?>