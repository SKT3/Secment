<?php

function smarty_modifier_slugalize($string)
{
    return Inflector::slugalize($string);
}	

?>