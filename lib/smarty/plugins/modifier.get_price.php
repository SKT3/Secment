<?php

function smarty_modifier_get_price($price,$br) {
    if($br != 'residential') {
        $price = $price/1.2;
    }
    return sprintf('%.2f', $price);
} 

?>