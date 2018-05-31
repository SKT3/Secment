<?php

class TempImage extends Modules {
	public
		$keyname,
		$current_extension = array();

	protected
		$is_i18n = false,
		$has_mirror = false;

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
					$this->filename = $_FILES[$this->keyname]['name'];
					$this->ord = ($this->max_all('ord', "admin_user_id = '". unserialize(Registry()->session->userinfo)->id ."' AND module = '".$this->module.'" AND keyname = "'.$this->keyname."'") + 1);
					$this->admin_user_id = unserialize(Registry()->session->userinfo)->id;

					if(array_key_exists($this->keyname, $this->has_one)) {
						$previous = $this->find_by_admin_user_id_and_module_and_keyname(unserialize(Registry()->session->userinfo)->id, $this->module, $this->keyname);

						if($previous instanceOf TempImage) {
							$previous->thumbs = $this->thumbs;
							$previous->delete();
						}
					}
				}
			}
		}
	}

	function after_save() {
		$path = $this->get_upload_path(true);
		Files::make_dir($path);
		chmod($path, 0777);

		// upload image
		if ($_FILES && !$_FILES[$this->keyname]['error']) {
			move_uploaded_file($_FILES[$this->keyname]['tmp_name'], $path . $this->filename);
		}
	}

	function before_delete(){
		$path = $this->get_upload_path(true);
		unlink($path . $this->filename);
	}
}

?>