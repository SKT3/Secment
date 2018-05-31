<?php
	// Error 404
	Router()->add('error404', '404', array('controller' => 'application', 'action' => 'error404'));
	// Default route
	Router()->add('default', '/:controller/:action/:id/:mid');
	// Modules Route - modified router to get by Route Name
	Router()->add('modules_admin', '/:controller/:module/:maction/:id', array('controller' => 'admin'));
?>