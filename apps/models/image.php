<?php

class Image extends Modules {
	public
		$keyname,
		$current_extension = array();

	protected
		$is_i18n = false,
		$has_mirror = false;

	protected $has_one = array(
		'comment' => array(
            'association_foreign_key'=>'id', 
            'class_name' => 'Comment',
            'foreign_key' => 'foreign_id',
            'conditions' => "foreign_model = 'Image'"
        )
    );

	function set_keyname($name){
		$this->keyname = $name;
	}

	function before_validation() {
		if($_FILES && !$_FILES[$this->keyname]['error']) {
			if(is_file($_FILES[$this->keyname]['tmp_name'])) {
				$this->filesize = $_FILES[$this->keyname]['size'];
				$this->current_extension[$_FILES[$this->keyname]['tmp_name']] = Files::mime_content_type($_FILES[$this->keyname]['tmp_name']);

				if(!array_key_exists($this->current_extension[$_FILES[$this->keyname]['tmp_name']], $this->image_extensions)) {
					$this->errors[$this->keyname] = 'Invalid File Type - Possible Formats: (' . join(', ', array_unique($this->image_extensions)) . ')';
				} else {
					$this->filename = str_replace(' ','-',$_FILES[$this->keyname]['name']);
					$this->ord = ($this->max_all('ord', "module='".$this->module."' AND module_id = '".$this->module_id."' AND keyname = '".$this->keyname."'") + 1);

					if(array_key_exists($this->keyname, $this->has_one)){
						$previous = $this->find_by_module_id_and_keyname_and_module($this->module_id, $this->keyname,$this->module);

						if($previous instanceOf Image) {
							$previous->thumbs = $this->thumbs;
							$previous->delete();
						}
					}
				}
			}
		}
	}

	function after_save() {
		$path = $this->get_upload_path();
		Files::make_dir($path);
		chmod($path, 0777);

		// upload image
		if ($_FILES && !$_FILES[$this->keyname]['error']) {
			move_uploaded_file($_FILES[$this->keyname]['tmp_name'], $path . $this->filename);
			if($this->thumbs){
				// if image options is set we do custom operation with thumbs. Defaulkt is create_thumbnail
				// set in your model like so: public $image_options = array('type'=>'fit','bg_color'=>'191919');
				foreach ($this->thumbs as $thumb) {
					$prefix = 'thumb_' . $thumb . '_';
					// add more options if you need...
					if($this->image_options['type'] == 'fit') {
						Images::fit($path.$this->filename, $path . $prefix . $this->filename, explode('x', $thumb), $this->image_options['bg_color']);
					} else {
						Files::create_thumbnail($path, $this->filename, explode('x', $thumb), $prefix);
					}
				}
			}
		}

		if ($this->tmp) {
			$tmppath = $this->get_upload_path(true);
			copy($tmppath . $this->filename, $path . $this->filename);
			if($this->thumbs){
				foreach ($this->thumbs as $thumb) {
					$prefix = 'thumb_' . $thumb . '_';

					if($this->image_options['type'] == 'fit') {
						Images::fit($path.$this->filename, $path . $prefix . $this->filename, explode('x', $thumb), $this->image_options['bg_color']);
					} else {
						Files::create_thumbnail($path, $this->filename, explode('x', $thumb), $prefix);
					}
				}
			}

		}
	}

	function before_delete() {
		$path = $this->get_upload_path();
		unlink($path . $this->filename);

		if($this->thumbs) {
			foreach ($this->thumbs as $thumb) {
				unlink($path . 'thumb_' . $thumb . '_' . $this->filename);
			}
		}
	}

	function copy_temp($key, $module, $obj){
		$tmp = new TempImage();
		$images = $tmp->find_all_by_admin_user_id_and_module_and_keyname(unserialize(Registry()->session->userinfo)->id, $module, $key);

		if(count($images)) {
			foreach($images as $image) {
				$img = new Image();

				$img->id			= 'NULL';
				$img->module_id		= $obj->id;
				$img->module		= $image->module;
				$img->keyname		= $image->keyname;
				$img->thumbs		= $obj->thumbs;
				$img->tmp			= true;
				$img->filename		= $image->filename;
				$img->filesize		= $image->filesize;
				$img->ord			= $image->ord;
				$img->created_at	= $image->created_at;
				$img->image_options	= $obj->image_options;

				$img->save();

				$image->thumbs = $this->thumbs;
				$image->delete();
			}
		}
	}
}

?>