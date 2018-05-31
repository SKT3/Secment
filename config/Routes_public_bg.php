<?php

$lang = 'bg';
Router()->add('error404', '404', array('controller' => 'application', 'action' => 'error404'));
Router()->add('preview', '/pages/preview/:id', array('controller' => 'pages', 'action' => 'preview', 'id' => COMPULSORY));
Router()->add('pages_ajax','/pages/xhr/:method', array('controller'=>'pages', 'action'=>'xhr', 'method'=>COMPULSORY));

load_routers_from_models();

//Promotions
$page = new Page();

Router()->add('pages_default', '/:lvl1/:lvl2/:lvl3/:lvl4/:lvl5/:lvl6/:lvl7/:lvl8/:lvl9/:lvl10/:id', array('controller' => 'pages', 'action' => 'view', 'lvl1' => COMPULSORY));
Router()->add('index', '', array('controller' => 'pages', 'action' => 'index'));


