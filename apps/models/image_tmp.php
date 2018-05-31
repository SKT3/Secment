<?php
class ImageTmp extends Modules {
	protected
		$is_i18n = false,
		$has_mirror = false;

	function __construct(){
		parent::__construct();
	}

	function before_validation() {
		if($_FILES && !$_FILES['picture']['error']) {
			if(is_file($_FILES['picture']['tmp_name'])) {
				if(($this->current_extension[$_FILES['picture']['tmp_name']] = Files::mime_content_type($_FILES['picture']['tmp_name'])) && !array_key_exists($this->current_extension[$_FILES['picture']['tmp_name']], $this->image_extensions)) {
					$this->errors['picture'] = 'Invalid File Type - Possible Formats: (' . join(', ', array_unique($this->image_extensions)) . ')';
				} else {
					$this->picture = 'img.'.$this->image_extensions[$this->current_extension[$_FILES['picture']['tmp_name']]];
				}
			}
		}
	}

	function after_save() {
		$path = $this->get_upload_path();
		Files::make_dir($path);
		chmod($path, 0777);

		// upload image
		if ($_FILES && !$_FILES['picture']['error']) {
			move_uploaded_file($_FILES['picture']['tmp_name'], $path . $this->picture);
			foreach ($this->thumbs as $thumb) {
				Files::create_thumbnail($path, $this->picture, explode('x', $thumb), 'thumb_' . $thumb . '_');
			}
		}
	}
}

?>