<?php

    require_once(str_replace('\\', '/', dirname(__FILE__)).'/core/sweboo.php');


    //ActionController::factory($this->request->recognize())->process(Request::getInstance(), Response::getInstance())
    Registry()->request = Request::getInstance();
    $type = $_GET['type'];
    $id = $_GET['id'];

    $url = url_for(array('app' => 'pages' , 'controller' => 'pages'));

    if ($type == 'product') {
        $product = (new ProductsModel())->find_by_old_id($id);
        if($product){
            $slug = $product->id.'-'.$product->slug;

            $url = $url.'product/'.$slug;
        }
    }
    $url = str_replace('?', '/', $url);


    print_r($url);exit;
    header("HTTP/1.1 301 Moved Permanently");
    header('Location: '.$url);
    exit;
?>