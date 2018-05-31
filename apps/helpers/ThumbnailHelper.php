<?php

set_time_limit(0);
require(Config()->LIB_PATH.'Images.php');

class ThumbnailHelper extends BaseHelper {
	protected $_smarty_register = array(
		'functions' => array('thumb', 'get_file_path'),
		'modifiers' => array('to_thumb_size'),
	);

	function __construct() {
		parent::__construct();
		Registry()->tpl->register_modifier('get_file_path', array($this, 'get_file_path'));
	}

	static function generate($params) {
		static $instance = null;
		if($instance === null) {
			$instance = new self();
		}
		return $instance->thumb_smarty($params, Registry()->tpl);
	}

	/**
	 * Generate thumb using MagImage & return the url
	 * @param unknown_type $params
	 */
	function get($params) {
		if(!empty($params['size'])) {
			list($thumb_width, $thumb_height) = sscanf($params['size'], '%ux%u');
		} else {
			$thumb_width	= empty($params['width']) ? 0 : (int)$params['width'];
			$thumb_height	= empty($params['height']) ? 0 : (int)$params['height'];
		}

		$gravity = 'TC';

		if(!empty($params['gravity']) && in_array($params['gravity'], array("TL", "TC", "TR", "ML", "MC", "MR", "BL", "BC", "BR"))) {
			$gravity = $params['gravity'];
		}

		if(!empty($params['mask_args']) && is_array($params['mask_args'])) {
			$mask_args = $params['mask_args'];
		}



		//$file_path = Config()->SYSTEM_ROOT_PATH . ltrim(str_replace('/', DS, $params['file']), DS); // not correct because of the different cookie path set here
		$file_path = implode(DS, array_unique(array_merge(explode(DS, rtrim(Config()->SYSTEM_ROOT_PATH, DS)), explode(DS, ltrim(str_replace('/', DS, $params['file']), DS)))));
		if(!is_file($file_path) || !($thumb_width || $thumb_height)) {
		    return;
		}

		$file_url			= $params['file'];
		$file_info			= pathinfo($file_path);
		$file_dir			= $file_info['dirname'] . DS;
		$file_name			= $file_info['filename'];
		$file_extension		= $file_info['extension'];
		$resize_method		= !empty($params['method']) && in_array($params['method'], array('crop', 'fit', 'thumbnail', 'crop_and_resize_to_fit', 'pad')) ? $params['method'] : 'crop_and_resize_to_fit';


		$params_for_uniqname = array_intersect_key($params, array(
			'background' => null,
			'x' => null,
			'y' => null,
		));

		$thumb_name			= $file_name . '_' . $thumb_width . 'x' . $thumb_height . '_' . $resize_method . '_' . substr(md5(serialize($params_for_uniqname)), -10) . '.' . $file_extension;
		$thumb_path			= $file_dir . $thumb_name;

		$thumb_path = str_replace(Config()->SYSTEM_FILES_ROOT, Config()->SYSTEM_ROOT_PATH . 'web' . DS . 'thumbs' . '/' . $thumb_width . 'x' . $thumb_height . '/', $thumb_path);
		$thumb_url = (empty($params['absolute_url']) ? null : 'http'.(is_ssl()?'s':'') . '://' . $_SERVER['HTTP_HOST']) . Config()->PUBLIC_URL . str_replace(DS, '/', str_replace(Config()->SYSTEM_ROOT_PATH . 'web' . DS, '', $thumb_path));

		if (file_exists($thumb_path)) {
         	return $thumb_url;
		}


		Files::make_dir(dirname($thumb_path), true);

		if ($resize_method == 'fit' && !($thumb_width && $thumb_height)) {
			return '';
		}

		try{
			switch($resize_method) {
				case 'crop':
					$x = !empty($params['x']) ? $params['x'] : null;
					$y = !empty($params['y']) ? $params['y'] : null;
					Images::crop($file_path, $thumb_path, $thumb_width, $thumb_height, ($gravity ?: 'TC'));
					break;
				case 'fit':
					$background = !empty($params['background']) ? $params['background'] : '#ffffff';
					Images::fit($file_path, $thumb_path, array($thumb_width, $thumb_height), $background);
					break;
				case 'pad':
					// resave image from jpg to png in order to set transparent crop
					/*$ext = pathinfo($thumb_path, PATHINFO_EXTENSION);
					if($ext == 'jpg' || $ext == 'jpeg') {
						$thumb_path = preg_replace('#\.' . $ext . '$#', '.png', $thumb_path);
						$thumb_url = preg_replace('#\.' . $ext . '$#', '.png', $thumb_url);
					}
					unset($ext);*/

					$background = !empty($params['background']) ? $params['background'] : '#ffffff';
					Images::pad($file_path, $thumb_path, $thumb_width, $thumb_height, $background);
					break;
				case 'thumbnail':
					Images::thumbnail($file_path, $thumb_path, array($thumb_width, $thumb_height));
					break;
				case 'crop_and_resize_to_fit':
				default:
					Images::crop_and_resize_to_fit($file_path, $thumb_path, array($thumb_width, $thumb_height), ($gravity ?: 'TC'));
					break;
			}

		} catch(Exception $e){
		}

		return $thumb_url;
	}

	/**
	 * Method for calling thumb generation true the template
	 * Ex.: {thumb size=150x0 file=$obj->get_file_path() method=crop}
	 * @param array		$params
	 * @param object	$smarty
	 *
	 * return image path
	 */
	function thumb_smarty($params, Smarty_Internal_Template $smarty) {
		$src = $this->get($params);
		if(!isset($params['as_src'])) {
			if($src) {
				$additional_params = array();
				if(!empty($params['data_source'])) {
					$additional_params['data-source'] = $params['data_source'];
				}
				$f = Config()->ROOT_PATH . str_replace('/', DS, preg_replace('#^' . Config()->COOKIE_PATH . '#', '', $src));
				$size = is_file($f) ? getimagesize($f) : array(0 => '', 1 => '');
				return $this->tag('img', array('src' => $src, 'width' => $size[0], 'height' => $size[1], 'class' => $params['class'], 'alt' => $params['alt'] ?: pathinfo($src, PATHINFO_FILENAME)) + $additional_params);
			}
		} else {
			unset($params['as_src']);
			$src = $this->get($params);
			if($src) {
				$f = Config()->ROOT_PATH . str_replace('/', DS, preg_replace('#^' . Config()->COOKIE_PATH . '#', '', $src));
			}

			return $src;
		}
	}

	/**
	 * Extends $obj method get_file_path()
	 * @param object		$for
	 * @param image name	$meta
	 */
	function get_file_path($for, $meta = 'image') {
		if(!is_object($for) || !method_exists($for, 'get_file_path')) {
			return null;
		}

		return $for->get_file_path($meta);
	}

	function get_file_path_smarty($params, Smarty $smarty) {
		if(!isset($params['for']) || !is_object($params['for']) || method_exists($params['for'], 'get_file_path')) {
			return null;
		}

		if(empty($params['meta'])) {
			return $this->get_file_path($params['for']);
		}
		else{
			return $this->get_file_path($params['for'], $params['meta']);
		}
	}

	function to_thumb_size_modifier($file, $place = 'internal_pages') {
		$file_path = Config()->FILES_ROOT.str_replace('/', DS, $file);
		list($width,$height) =  getimagesize($file_path);

		switch ($place) {
			case 'gallery':
				if(($width / $height) > 2)
					{ return '595x180'; }
				else
					{ return '286x180'; }
				break;

			default:
				if(($width / $height) > 2 )
					{ return '349x135'; }
				else
					{ return '170x135'; }
				break;
		}
	}

}

?>
