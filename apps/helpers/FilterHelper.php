<?php

class FilterHelper extends BaseHelper
{
    function __construct()
    {

    }

    function filter_prices(){

    }

    function filter_brand($objects){
        $arr = array();
        foreach($objects as $product){
            $brand = $product->brand();
            $arr[$brand->id] = $brand->title;
        }
        return $arr;
    }

    function filter_manufacturer($objects){
        $arr = array();
        foreach($objects as $product){
            $brand = $product->brand();
            $arr[$brand->id] = $brand->title;
        }
        return $arr;
    }

    function filter_types($objects){
        $arr = array();

        foreach ($objects as $product){
            $product_characteristics[$product->id] = (new CharacteristicsModel())->find_all([
                'select' => 'characteristics_i18n.title, characteristic_products_values.value',
                'joins' => 'INNER JOIN characteristic_products_values ON characteristic_products_values.characteristic_id = characteristics.id',
                'conditions' => "characteristic_products_values.value != '' AND characteristic_products_values.product_id = " . $product->id . " AND characteristic_products_values.characteristic_id = 1",
            ]);
            if($product_characteristics[$product->id][0]->value){
                array_push($arr,$product_characteristics[$product->id][0]->value);
            }
        }
        $arr = array_unique($arr);
        return $arr;
    }

}