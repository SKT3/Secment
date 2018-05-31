<?php

class File extends Modules {
	public
		$keyname,
		$current_extension = array();

	protected
		$is_i18n = false,
		$has_mirror = false;

	function set_keyname($name){
		$this->keyname = $name;
	}

	function before_validation(){
		if($_FILES && !$_FILES[$this->keyname]['error']) {
			if(is_file($_FILES[$this->keyname]['tmp_name'])) {
				$this->filesize = $_FILES[$this->keyname]['size'];
				$this->current_extension[$_FILES[$this->keyname]['tmp_name']] = Files::mime_content_type($_FILES[$this->keyname]['tmp_name']);
				if(!array_key_exists($this->current_extension[$_FILES[$this->keyname]['tmp_name']], $this->file_extensions)){
					$this->errors[$this->keyname] = 'Invalid File Type - Possible Formats: (' . join(', ', array_unique($this->file_extensions)) . ') - '.$_FILES[$this->keyname]['type'];
				} else {
					$this->filename = $this->filename = str_replace(' ','-',$_FILES[$this->keyname]['name']);
					$this->ord = ($this->max_all('ord', "module_id = '".$this->module_id."' AND keyname = '".$this->keyname."'") + 1);
					if(array_key_exists($this->keyname, $this->has_one)){
						$previous = $this->find_by_module_id_and_keyname($this->module_id, $this->keyname);

						if($previous instanceOf File){
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
		}

		if ($this->tmp) {
			$tmppath = $this->get_upload_path(true);
			copy($tmppath . $this->filename, $path . $this->filename);
		}
	}

	function before_delete() {
		$path = $this->get_upload_path();
		unlink($path . $this->filename);
	}

	function file_extension() {
		return substr($this->filename, strrpos($this->filename, '.')+1);
	}

	function humanize_file() {
		return Inflector::humanize(str_replace('.'.$this->file_extension(),'',$this->filename));
	}

	function copy_temp($key, $module, $obj) {
		$tmp = new TempFile();
		$files = $tmp->find_all_by_admin_user_id_and_module_and_keyname(unserialize(Registry()->session->userinfo)->id, $module, $key);

		if(count($files)) {
			foreach($files as $file) {
				$f = new File();

				$f->id			= 'NULL';
				$f->module_id	 = $obj->id;
				$f->module		= $file->module;
				$f->keyname		 = $file->keyname;
				$f->tmp			 = true;
				$f->filename		= $file->filename;
				$f->filesize		= $file->filesize;
				$f->ord			 = $file->ord;
				$f->created_at	= $file->created_at;

				$f->save();
				$file->delete();
			}
		}
	}
}

?>