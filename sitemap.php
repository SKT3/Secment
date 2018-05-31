<?php
	header ("Content-Type:text/xml");
	header('Character-encoding: utf-8;');
	$result = array();
	require_once(str_replace('\\', '/', dirname(__FILE__)) . '/core/sweboo.php');
	$host = Config()->BASE_URL . Config()->COOKIE_PATH;

	$result[] = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

	$n = new Page;
	foreach(Config()->LOCALE_SHORTCUTS as $k => $l) {
		$_all = Registry()->db->query('SELECT slug FROM pages LEFT JOIN pages_i18n ON pages.id=pages_i18n.i18n_foreign_key WHERE i18n_locale="' . $l . '" AND visibility=2 AND id>1 ORDER BY lft ASC');
		foreach($_all as $a) {
			$result[] = '<url><loc>http://' . $host . $k . $a->slug . '</loc></url>';
		}
	}

	$result[]= '</urlset>';

	die(join('', $result));
?>