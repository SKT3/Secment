<?php

class FileExtra extends File {
	public
		$table_name = 'files_extra';

	protected
		$is_i18n = false,
		$has_mirror = false;


	function copy_temp($key, $module, $obj) {
		$tmp = new TempFile();
		$files = $tmp->find_all_by_admin_user_id_and_module_and_keyname(unserialize(Registry()->session->userinfo)->id, $module, $key);

		if(count($files)) {
			foreach($files as $file) {
				$f = new FileExtra();

				$f->id			= 'NULL';
				$f->module_id	= $obj->id;
				$f->module		= $file->module;
				$f->keyname		= $file->keyname;
				$f->tmp			= true;
				$f->filename	= $file->filename;
				$f->caption		= $file->caption;
				$f->filesize	= $file->filesize;
				$f->ord			= $file->ord;
				$f->created_at	= $file->created_at;
				$f->published_at	= $file->published_at ? $file->published_at : $file->created_at;

				$f->save();
				$file->delete();
			}
		}
	}
}