<?php

class Modules extends ActiveRecord {
	protected
		$is_i18n = false,
		$_skip_mirror = false,
		$_skip_order_update = false;

	public
		$thumbs = array(
			'_thumb' => '30x30',
			'_small' => '480x360',
			'_medium' => '768x576',
			'_large' => '1024x768',
			'_extralarge' => '1920x1440'
		),
		$image_extensions = array(
			'image/jpeg'	=> 'jpg',
			'image/jpg'		=> 'jpg',
			'image/pjpeg'	=> 'jpg',
			'image/gif'		=> 'gif',
			'image/png'	=> 'png'
		),
		$file_extensions = array(
			'application/msword'		=> 'doc',
			'application/pdf'			=> 'pdf',
			'application/excel'		=> 'xls',
			'application/powerpoint'	=> 'ppt',
			'application/zip'			=> 'pptx',
			'application/zip'			=> 'xlsx',
			'application/zip'			=> 'docx',
			'video/mp4'			=> 'mp4',
			'application/x-rar'		=> 'rar',
			'video/x-msvideo'		=> 'avi',
			'video/x-flv'				=> 'flv'
		);

	function __construct($attributes = null) {
		$this->orig_table_name = $this->table_name;
		parent::__construct($attributes);
	}

	function __toString() {
		return (string)$this->title;
	}

	function findAllForSmarty(array $find_parameters = array(), $key='id', $title='title', $first = false) {
		$find_parameters['returns'] = 'array';
		$data = $this->find_all($find_parameters);
		return ars($data)->toSmartySelect($key, $title, $first);
	}

	function before_validation() {
		if ($this->column_attribute_exists('email')) {
			if($this->email != '') {
				if(filter_var($this->email, FILTER_VALIDATE_EMAIL) == false) {
					$this->errors['email'] = Registry()->localizer->get('DB_SAVE_ERRORS', 'invalid_email');
				}
			}
		}

		/*if ($this->column_attribute_exists('link')) {
			if($this->link != '') {
				if(filter_var($this->link, FILTER_VALIDATE_URL) == false) {
					$this->errors['link'] = Registry()->localizer->get('DB_SAVE_ERRORS', 'wrong_url_format');
				}
			}
		}*/

		parent::before_validation();
	}

	function before_validation_on_create() {
		if ($this->column_attribute_exists('ord') && $this->ord === null && !$this->_skip_order_update) {
			$this->ord = $this->max_all('ord') + 1;
		}

		if ($this->is_i18n && $this->has_mirror && !$this->_skip_mirror) {
			foreach ($this->content_columns_i18n as $column) {
				if (in_array($column['name'], $this->i18n_reserved_columns)) continue;
				$lang_value = array();

				foreach (Config()->LOCALE_SHORTCUTS as $shortcut => $locale) {
					if ($column['name'] == 'is_active' || $column['name'] == 'active' && $locale != $this->get_locale()) {
						$lang_value[$locale] = 0;
					} else {
						$lang_value[$locale] = $this->{$column['name']};
					}
				}

				$this->{$column['name']} = $lang_value;
			}

		}

		return parent::before_validation_on_create();
	}

	function after_validation() {
		$has_one_relations = $this->get_has_one();
		if($this->module && $has_one_relations && !in_array(Inflector::underscore(get_class($this)), array('image', 'file', 'temp_image', 'temp_file'))) {
			foreach ($has_one_relations as $relation_key => $relation) {
				if(in_array($relation['class_name'], array('image', 'file')) && !empty($relation['is_required'])) {
					if((!$this->id && count(self::$db->select('temp_' . Inflector::tableize($relation['class_name']), 'id', array(
							'keyname' => $relation_key,
							'module' => $this->module,
							'admin_user_id' => unserialize(Registry()->session->userinfo)->id
					))) == 0)
					|| ($this->id && count(self::$db->select(Inflector::tableize($relation['class_name']), 'id', array(
							'keyname' => $relation_key,
							'module' => $this->module,
							'module_id' => $this->id
					))) == 0)) {
						$this->add_error(Inflector::humanize(Registry()->localizer->get_label('DB_SAVE_ERRORS', 'not_empty')), $relation_key);
					}
				}
			}
		}



		return parent::after_validation();
	}

	function after_delete() {
		if ($this->column_attribute_exists('ord') && !$this->_skip_order_update) {
			if(!array_key_exists('ord', $this->content_columns) && $this->is_i18n && array_key_exists('ord', $this->content_columns_i18n)) {
				self::$db->query("UPDATE " . $this->table_name . $this->i18n_table_suffix . " SET ord = ord - 1 WHERE i18n_locale = '" . $this->get_locale() . "' AND ord > " . $this->ord);
			} else {
				$this->update_all('ord = ord - 1', "ord > " . $this->ord);
			}
		}

		return parent::after_delete();
	}

	function get_upload_path($temp = false) {
		if($temp) {
			return  Config()->FILES_ROOT . $this->module . '/tmp/' . $this->keyname . '/' .  unserialize(Registry()->session->userinfo)->id . '/';
		} else {
			return  Config()->FILES_ROOT . $this->module . '/' .  $this->module_id . '/' . $this->keyname . '/';
		}
	}

	function get_file_path($temp = false) {
		if($temp) {
			return  Config()->FILES_URL . $this->module . '/tmp/' . $this->keyname . '/' .  unserialize(Registry()->session->userinfo)->id . '/';
		} else {
			return  Config()->FILES_URL . $this->module . '/' .  $this->module_id . '/' . $this->keyname . '/';
		}
	}

	function get_thumb_url($thumb) {
		return  Config()->FILES_URL . $this->module . '/' .  $this->module_id . '/' . $this->keyname . '/'.'thumb_' . $thumb . '_' . $this->filename;
	}

	function get_file_url() {
		return  Config()->FILES_URL . $this->module . '/' .  $this->module_id . '/' . $this->keyname . '/' . $this->filename;
	}

	function pretty_size() {
		$size = $this->filesize;

		$mod = 1024;

		$units = explode(' ','B KB MB GB TB PB');
		for ($i = 0; $size > $mod; $i++) {
			$size /= $mod;
		}

		return round($size, 2) . ' ' . $units[$i];
	}

	static function updateOrdering($orders) {
		$is_i18n = false;
		$class_name = get_called_class();
		$object = new $class_name();
		$table_name = $object->table_name;

		if(array_key_exists('ord', $object->content_columns_i18n)) {
			$is_i18n = true;
			$table_name =  $object->i18n_table;
		}

		foreach ($orders as $key => $foreign_key) {
			$conditions = $is_i18n ? array('i18n_foreign_key' => $foreign_key, 'i18n_locale' => $object->get_locale()) : array('id' => $foreign_key);

			self::$db->update(
				$table_name,
				array(
					'ord' => $key + 1,
				),
				$conditions
			);
		}
	}

	public function get_items($conditions = null) {
		return $this->find_all($conditions);
	}
}

?>