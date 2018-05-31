<?php

function smarty_modifier_inflation($price, $discount = null)
{
    $price = (float)$price;
    $fe = Registry()->controller->front_end_settings;
    if(!$fe) {
    	$fs = new FrontSetting();
       	$fe = current($fs->first);
    }

    $price*= (1+$fe['inflation']/100);

    if($discount=='member') {
      $memeber_discount = $fe['member_discount'];
      $price*= (1-$memeber_discount/100);
    }
    return number_format($price,2,',',' ');
}

?>