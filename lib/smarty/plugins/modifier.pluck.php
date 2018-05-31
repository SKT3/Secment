<?php

function smarty_modifier_pluck($array, $option = 'id')
{
	if (is_array($array)) {
		$arr = array();
		foreach ($array as $key => $value) {
	    	$arr[] = $value->{$option};
	    }
    	return $arr;
	}
	return false;
}	

?>