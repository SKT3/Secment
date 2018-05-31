<?php

function smarty_modifier_tinymce($string,$template)
{
	preg_match_all("#(\s(?:href|src)=[\"'])(?!http://)([^\"'\r\n]+)#i", $string, $matches);
	if (isset($matches[2]) && count($matches[2])) {
		$url_base = Registry()->request->get_protocol() . rtrim(Registry()->request->server('HTTP_HOST'), "/") . rtrim("/".trim(Config()->COOKIE_PATH, "/"), "/");
		foreach ($matches[2] as $match) {
			if (strpos($match, 'richeditor/') !== false) {
				$string = str_replace($match, $url_base . '/web/files/' . substr($match, strpos($match, 'richeditor/')), $string);
			}
		}
	}
	return $string;
}	

?>