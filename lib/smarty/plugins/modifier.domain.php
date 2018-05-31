<?php
function smarty_modifier_domain($string) 
{ 
  $string = parse_url($string, PHP_URL_HOST); 
  //$string = substr($string, 0, strrpos($string, '.')); 
  //$string = substr($string, (strrpos($string, '.') ?: -1) +1); 
  return $string; 
}
?>