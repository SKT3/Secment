<?php
function smarty_modifier_join($string, $concat,$join='|')
{
	if($string) {
		$arr = explode($join,$string);	
		array_push($arr,$concat);
		
		$arr = array_unique($arr);
		return join($join,$arr);
	}
	else {
		return $concat;
	}
}

?>