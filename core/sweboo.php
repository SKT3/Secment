<?php

error_reporting(E_ALL);
// set_time_limit(0);

/**
 * Sets internal character encoding to encoding
 */
mb_internal_encoding("UTF-8");
/**
 * Sets the timezone
 */
date_default_timezone_set('Europe/Sofia');
/**
 * Load core modules
 */
$core_components = array('Exceptions', 'Config', 'Request', 'Response', 'Router', 'Dispatcher', 'ActionController', 'Inflector', 'Registry', 'Localizer', 'Sql', 'Cache');
$dirname = dirname(__FILE__);

foreach($core_components as $file ) {
	require_once($dirname . '/' . $file . ".php");
}

/**
 * Collect list of modules reading module's directory (those who are not set to be skipped) and merge them with the one from Config
 *
 */
$modules = array();
$dh = opendir(Config()->MODULES_PATH);
if($dh) {
	require_once(Config()->LIB_PATH . 'yaml/yaml.php');

	while(($file = readdir($dh)) !== false) {
		if($file != '.' && $file != '..' && is_dir(Config()->MODULES_PATH . $file)) {
			/*
			$module_configurations = array();
			if(is_file($module_configurations_yaml = Config()->MODULES_PATH . $file . DIRECTORY_SEPARATOR . 'Config.yaml')) {
				$module_configurations = Yaml::loadFile($module_configurations_yaml);
			}

			(!$module_configurations || !$module_configurations['skip_module_indexing']) && $modules[] = $file;
			*/
			$modules[] = $file;
		}
	}

	closedir($dh);
}
Config()->MODULES = array_unique(array_merge(Config()->MODULES, $modules));

// free up some resources
$modules = $dh = null;
unset($modules, $dh);

/* TODO : Cache this !*/
function load_routers_from_models() {
	foreach(Config()->MODULES as $module) {
		if($module == 'admin') {
			continue;
		}

		$file = Config()->MODULES_PATH . $module . DIRECTORY_SEPARATOR . 'Routes_' . substr(Registry()->locale, 0, 2) . '.php';
		if(file_exists($file)) {
			include_once($file);
		}
	}
}

/**
 * Prints human-readable information about a variable
 */
function d($what) {
	print '<pre>';
	print_r($what);
	print '</pre>';
}

/**
 * Dump executed queries
 */
function ds() {
	echo Registry()->db->get_stored_queries();
}

function send_php_mail($options = array()) {
    $defaults = array(
        'host' => 'smtp.mail.themags.com',
        'port' => 465,
        'from' => Config()->EMAILS_FROM,
        'mail' => '',
        'html' => null,
        'subject'=> '',
    );

    // extract SMTP options
	if($smtp_options = parse_url(Config()->SMTP)) {
		$defaults['type'] = $smtp_options['scheme'];
		$defaults['host'] = $smtp_options['host'];
		$defaults['port'] = $smtp_options['port'];
		$defaults['user'] = $smtp_options['user'];
		$defaults['pass'] = $smtp_options['pass'];
		$defaults['hello'] = ltrim($smtp_options['path'], '/');
	}
	$options = array_merge($defaults, $options);

	require_once(Config()->ROOT_PATH . 'lib/phpmailer/class.phpmailer.php');
    $mailer = new PHPMailer();
    $mailer->IsSMTP();
    //$mailer->SMTPDebug = 10;
    //$mailer->SMTPSecure = 'ssl';
    $mailer->SMTPAuth = true;
    $mailer->Username = $options['user'];
    $mailer->Password = $options['pass'];
    $mailer->Host = $options['host'];
    $mailer->Port = $options['port'];
    $mailer->SetFrom($options['from'][1], $options['from'][0]);
    $mailer->AddAddress($options['mail']);
    $mailer->CharSet = 'UTF-8';
    $mailer->Body = $options['html'];
    $mailer->IsHTML(true);
    $mailer->Subject = $options['subject'];


    foreach($options['attachments'] as $a) {
		$mailer->AddAttachment($a['path'], $a['name']);
	}

	return $mailer->Send();
}

function is_ssl() {
	return isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https';
}

/**
 * Autoload function automatically called in case you are trying to
 * use a class which hasnt been defined yet.
 *
 * @throws ClassMissingException if $class_name not found
 */
function __autoload($class_name) {
	$file_as_model 			= Config()->MODELS_PATH . Inflector::underscore($class_name).'.php';
	$file_as_controller 	= Config()->CONTROLLERS_PATH . Inflector::underscore($class_name).'.php';
	$file_as_helper 		= Config()->HELPERS_PATH . $class_name . '.php';
	$file_as_class 			= Config()->CORE_PATH . $class_name . '.php';
	$file_as_lib_class 		= Config()->LIB_PATH . $class_name . '.php';
	$file_as_smarty_class	= Config()->LIB_PATH . 'smarty/sysplugins/'. strtolower($class_name) . '.php';

	// IF we call NewsModel or HtmlModel or AnythingModel - Search the model in module folder
	if(strpos($class_name, 'Model') !== false) {
		$key = array_search(Inflector::underscore(str_replace('Model', '', $class_name)), (array)Config()->MODULES);
		if($key) {
			$file = Config()->MODULES_PATH . Config()->MODULES[$key] . DIRECTORY_SEPARATOR .'Model.php';
			if(file_exists($file)) {
				include_once($file);
				return true;
			}
		}
	}

	// IF we call NewsWidget or HtmlWidget or AnythingWidget - Search the widget in module folder
	if(strpos($class_name, 'Widget') !== false) {
		$key = array_search(Inflector::underscore(str_replace('Widget', '', $class_name)),(array)Config()->MODULES);
		if($key) {
			$file = Config()->MODULES_PATH . Config()->MODULES[$key] . DIRECTORY_SEPARATOR .'Widgets.php';
			if(file_exists($file)) {
				include_once($file);
				return true;
			}
		}
	}

	if(file_exists($file_as_model)) {
		include_once($file_as_model);
	}
	elseif(file_exists($file_as_helper)) {
		include_once($file_as_helper);
	}
	elseif(file_exists($file_as_lib_class)) {
		include_once($file_as_lib_class);
	}
	elseif(file_exists($file_as_class)) {
		include_once($file_as_class);
	}
	elseif(file_exists($file_as_controller)) {
		include_once($file_as_controller);
	}
	elseif(file_exists($file_as_smarty_class)) {
		include_once($file_as_smarty_class);
	}
	else {
		//throw new ClassMissingException($class_name);
	}
}

?>