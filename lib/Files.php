<?php

class Files {
	static function make_dir($path, $system = false) {
		$root_path = $system ? Config()->SYSTEM_ROOT_PATH : Config()->ROOT_PATH;
		$directory_separator = $system ? DS : '/';

		$path = trim(str_replace(rtrim($root_path, $directory_separator), '',$path) , $directory_separator);
		$path = $root_path . $path;

		if (!is_dir($path)){
			self::make_dir(dirname($path), $system);
			return mkdir($path);
		}
		return false;
	}

	static function file_delete($file_name) {
		$file_name = trim(str_replace(Config()->ROOT_PATH, '',$file_name),'/');
		if (is_file(Config()->ROOT_PATH.$file_name)) {
			return @unlink(Config()->ROOT_PATH.$file_name);
		} else {
			return false;
		}
	}

	static function directory_delete($dir_name) {
		$sucess = true;
		$dir_name = self::_get_restricted_path($dir_name);

		if(empty($dir_name)){
			return false;
		}

		$items = glob(Config()->ROOT_PATH.$dir_name."/*");
		$hidden_items = glob(Config()->ROOT_PATH.$dir_name."/.*");
		$fs_items = $items || $hidden_items ? array_merge((array)$items, (array)$hidden_items) : false;

		if($fs_items) {
			$items_to_delete = array('directories'=>array(), 'files'=>array());

			foreach($fs_items as $fs_item) {
				if($fs_item[strlen($fs_item)-1] != '.') {
					$items_to_delete[ (is_dir($fs_item) ? 'directories' : 'files') ][] = $fs_item;
				}
			}

			foreach ($items_to_delete['files'] as $file) {
				self::file_delete($file);
			}

			foreach ($items_to_delete['directories'] as $directory) {
				$sucess = $sucess ? self::directory_delete($directory) : $sucess;
			}
		}

		return $sucess && is_dir(Config()->ROOT_PATH.$dir_name) ? rmdir(Config()->ROOT_PATH.$dir_name) : $sucess;
	}

	static function copy($from, $to) {
		$sucess = true;

		if(empty($from) || empty($to)) {
			return false;
		}

		$from = self::_get_restricted_path($from);
		$to = self::_get_restricted_path($to);

		$destination = str_replace($from, $to, $from);
		if(is_file(Config()->ROOT_PATH.$from)) {
			return self::file_put_contents(rtrim(Config()->ROOT_PATH, '/').'/'.$to, self::file_get_contents(rtrim(Config()->ROOT_PATH, '/').'/'.$from));
		}

		self::make_dir(rtrim(Config()->ROOT_PATH, '/').'/'.$to);
		if($fs_items = glob(rtrim(Config()->ROOT_PATH, '/').'/'.$from."/*")) {
			$items_to_copy = array('directories'=>array(), 'files'=>array());

			foreach($fs_items as $fs_item) {
				$items_to_copy[(is_dir($fs_item) ? 'directories' : 'files')][] = $fs_item;
			}

			foreach ($items_to_copy['files'] as $file) {
				$destination = str_replace($from, $to, $file);
				$sucess = $sucess ? self::file_put_contents($destination, self::file_get_contents($file)) : $sucess;
			}

			foreach ($items_to_copy['directories'] as $directory) {
				$destination = str_replace($from, $to, $directory);
				$sucess = $sucess ? self::copy($directory, $destination) : $sucess;
			}
		}

		return $sucess;
	}

	static function file_put_contents($file_name, $content) {
		$file_name = trim(str_replace(rtrim(Config()->ROOT_PATH, '/'), '', $file_name),'/');
		if(!is_dir(dirname(rtrim(Config()->ROOT_PATH, '/') . '/' . $file_name))) {
			self::make_dir(dirname(rtrim(Config()->ROOT_PATH, '/') . '/' . $file_name));
		}

		if(!$result = file_put_contents(rtrim(Config()->ROOT_PATH, '/') . '/' . $file_name, $content)) {
			if(!empty($content)){
				trigger_error('Sorry! I dont know what you want to do, but you cant do it!', E_USER_ERROR);
				exit;
			}
		}
		return $result;
	}

	static function file_get_contents($file_name) {
		$file_name = trim(str_replace(rtrim(Config()->ROOT_PATH, '/'), '', $file_name), '/');
		return file_get_contents(rtrim(Config()->ROOT_PATH, '/') . '/' . $file_name);
	}

	private static function _get_restricted_path($path) {
		$path = str_replace('..', '', rtrim($path,'\\/. '));
		$path = trim(str_replace(rtrim(Config()->ROOT_PATH, '/'), '', $path), '/');
		return $path;
	}

	static function mime_content_type($file) {
		require_once(Config()->LIB_PATH . 'mimemagic/MimeMagic.php');
		return MimeMagic::mimetype($file);
	}

	static function create_thumbnail($path,$image_name,$thumb_size,$thumb_name_prefix='thumb_') {
		Files::make_dir($path);
		$image = $path.$image_name;
		Images::crop_and_resize_to_fit($path.$image_name, $path.$thumb_name_prefix.$image_name, $thumb_size, 'MC');
	}

	static function check_extensions($files=array(), $allowed_extensions=array()) {
		if (empty($allowed_extensions)) {
			$allowed_extensions = array(
				'image/jpeg' => 'jpg',
				'image/jpg' => 'jpg',
				'image/pjpeg' => 'jpg',
				'image/gif' => 'gif',
				'image/png' => 'png'
			);
		}

		foreach($files as $key => $file) {
			if(is_file($file)) {
				$extension = self::mime_content_type($file);
				if(!in_array($extension, array_keys($allowed_extensions))) {
					return false;
				}
			}
		}

		return $files;
	}
}

?>