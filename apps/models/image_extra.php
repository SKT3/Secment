<?php

class ImageExtra extends Image {
	public
		$table_name = 'images_extra';

	protected
		$is_i18n = true,
		$has_mirror = false;


	function copy_temp($key, $module, $obj){
		$tmp = new TempImage();
		$images = $tmp->find_all_by_admin_user_id_and_module_and_keyname(unserialize(Registry()->session->userinfo)->id, $module, $key);

		if(count($images)) {
			foreach($images as $image) {
				$img = new ImageExtra();

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
				$img->caption 		= $image->caption;

				$img->save();

				$image->thumbs = $this->thumbs;
				$image->delete();
			}
		}
	}

	function get_caption(){
		return $this->caption;
	}
}