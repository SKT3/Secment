<?php
function smarty_function_dual_listbox($params, $smarty)
{
	
	$size = ($params['size']) ? $params['size'] : 10;
	
	settype($params['selected'],'array');
	$selected_options = array();
	foreach($params['selected'] as $k=>$v)
	{
		$selected_options[] = (is_object($v)) ? $v->$params['key'] : $v;
	}
	
	$html = array();
	$html[]='<select name="'.$params['name'].'[]" id="'.$params['name'].'_id" multiple="multiple" size="'.$size.'">';
	
	foreach($params['options'] as $index=>$option)
	{
		$key = (($params['key']) ? $option->{$params['key']} : $index);
		$value = (($params['value']) ? $option->{$params['value']} : $option);
		$selected = (in_array($key,$selected_options)) ? 'selected="selected"' : '';
		$html[]='<option value="'.$key.'"  '.$selected.'>'.$value.'</option>';
	}
	$html[]='</select>';
	
	$html[]='<script type="text/javascript">jQuery().ready(function(){$("#'.$params['name'].'_id").SwebooDualList({addText:"'.Registry()->localizer->get('BUTTONS','add').'",removeText:"'.Registry()->localizer->get('BUTTONS','remove').'"});})</script>';
	return join("\n",$html);
}

?>