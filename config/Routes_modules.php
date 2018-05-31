<?php
Router()->add('newsletter', '/:controller/:action/:id', array('controller' => '/admin/', 'action' => '/login/'));

// Error 404
Router()->add('error404', '404', array('controller' => 'application', 'action' => 'error404'));

Router()->add('xhr', '/:controller/xhr', array('action'=>'xhr'));

Router()->add('admin', '/:controller/:module/:maction/:id', array('controller' => 'modules'));
Router()->add('default', '/:controller/:action/:id');
Router()->add('modules_admin', '/:controller/:module/:maction/:id', array('controller' => 'modules'));
?>