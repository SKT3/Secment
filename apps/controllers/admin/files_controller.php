<?php

class FilesController extends AdminController {
	public
		$allowed_image_types = array(
			'image/jpeg',
			'image/jpg',
			'image/pjpeg',
			'image/png',
			'image/gif'
		),
		$allowed_doc_types = array(
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'application/vnd.ms-excel',
			'application/vnd.ms-powerpoint',
			'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'application/msword',
			'application/pdf',
			'video/mp4',
		),
		$allowed_archive_types = array(
			'application/x-7z-compressed',
			'application/x-rar',
			'application/zip'
		),
		$allowed_video_types = array(),
		$allowed_music_types = array(),
		$max_image_preview_size = 1500000; // do 1.5 mb

	function index($params) {
		$this->include_css('filemanager');
		$this->include_javascript('filemanager');

		$this->path = Config()->UPLOADED_IMAGES_ROOT;
		$this->url_path = Config()->UPLOADED_IMAGES_URL;

		$mime_types = array_merge($this->allowed_image_types, $this->allowed_doc_types, $this->allowed_archive_types, $this->allowed_video_types, $this->allowed_music_types);
		$this->mime_types = implode(",", $mime_types);

		// Get upload MB
		$max_upload = (int)(ini_get('upload_max_filesize'));
		$max_post = (int)(ini_get('post_max_size'));
		$memory_limit = (int)(ini_get('memory_limit'));

		$this->upload_mb = min($max_upload, $max_post, $memory_limit);

		if ($params['path']) {
			if (is_dir($this->path.$params['path']) AND !$this->is_post() AND !$this->is_xhr()) {
				$this->structure = $this->get_folder_list($this->path, $params['path']);
			}

			$explode = explode("/", $params['path']);
			if (count($explode) > 1) {
				array_pop($explode);
				$this->parent_dir = implode("/", $explode);
			}
		}

		if (!isset($this->structure)) {
			$this->structure = $this->get_folder_list($this->path);
		}

		if (isset($params['tinymce']) AND in_array($params['tinymce'], array('videos', 'images', 'all'))) {
			if ($this->structure) {
				if ($params['tinymce'] == 'images') {
					foreach($this->structure as $key => $val) {
						if (!in_array($val['type'], array('image', 'folder'))) {
							unset($this->structure[$key]);
						}
					}

					$this->mime_types = implode(",", $this->allowed_image_types);
				}
				elseif ($params['tinymce'] == 'videos') {
					foreach($this->structure as $key => $val) {
						if (!in_array($val['type'], array('video', 'folder'))) {
							unset($this->structure[$key]);
						}
					}

					$this->mime_types = implode(",", $this->allowed_video_types);
				}
			}

			$this->layout = 'widget';
		}

		if ($this->is_post() AND $_FILES) {
			if (isset($_FILES['files']) AND !empty($_FILES['files']['tmp_name'])) {
				// $finfo = finfo_open(FILEINFO_MIME_TYPE);

				foreach($_FILES['files']['tmp_name'] as $key => $file) {
					if (is_file($file)) {
						// $extension = finfo_file($finfo, $file);
						$extension = Files::mime_content_type($file);

						if (in_array($extension, $this->allowed_image_types)
								|| in_array($extension, $this->allowed_doc_types)
								|| in_array($extension, $this->allowed_archive_types)
								|| in_array($extension, $this->allowed_video_types)
								|| in_array($extension, $this->allowed_music_types)
						) {

							$path = $this->path;
							if ($params['path']) {
								$path = $path.$params['path']."/";
							}

							$name = str_replace(" ", "-", strtolower(Inflector::latinize($_FILES['files']['name'][$key])));
							$name = str_replace("_", "-", $name);
							move_uploaded_file($file, $path.$name);


							$this->log_action(array('message' => 'Files > Upload file '.$name.'.'));
						}
					}
				}

				// finfo_close($finfo);
			}

			$post = $_POST;
			if (isset($post['directory']) AND $post['directory'] != '') {
				$post['directory'] = str_replace(" ", "-", strtolower(Inflector::latinize($post['directory'])));
				$post['directory'] = str_replace("_", "-", $post['directory']);
				$post['directory'] = str_replace("/", "-", $post['directory']);

				if ($params['path'] AND is_dir($this->path.$params['path'])) {
					$dir = $this->path.$params['path']."/".$post['directory'];
				} else {
					$dir = $this->path.$post['directory'];
				}

				Files::make_dir($dir);
				$this->log_action(array('message' => 'Files > Create directory '.$dir.'.'));
			}

			$this->redirect_to(array('action' => 'index', 'path' => $params['path'], 'editor' => $params['editor'], 'tinymce' => $params['tinymce'], 'target' => $params['target'], 'place' => $params['place']));
		}
		elseif ($this->is_xhr()) {
			if (isset($params['delete'])) { // One file
				$path = $this->path;

				$delete = $path.$params['delete'];
				if ($params['path']) {
					$delete = $path.$params['path']."/".$params['delete'];
				}

				if (is_dir($delete)) {
					Files::directory_delete($delete);
					$this->log_action(array('message' => 'Files > Delete directory '.$delete.'.'));
				}
				else if (is_file($delete)) {
					Files::file_delete($delete);
					$this->log_action(array('message' => 'Files > Delete file '.$delete.'.'));
				}
			}
			elseif (isset($params['delete_files'])) { // Multiple files
				$files = json_decode($params['delete_files']);
				if ($files AND is_array($files) AND count($files) > 0) {
					$path = $this->path;

					foreach($files as $file) {
						$details = explode("_", $file);

						if (count($details) == 1 AND is_dir($path.$details[0])) { // Dir
							Files::directory_delete($path.$details[0]);

							$this->log_action(array('message' => 'Files > Delete directory '.$path.$details[0].'.'));
						}
						elseif (count($details) == 2 AND is_file($path.$details[1]."/".$details[0])) { // File
							$delete = $path.$details[0];

							if ($details[1] != '') {
								$delete = $path.$details[1]."/".$details[0];
							}

							Files::file_delete($delete);
							$this->log_action(array('message' => 'Files > Delete file '.$delete.'.'));
						}
					}
				}
			}
			elseif (isset($params['rename_folder'], $params['new_name'])) {
				$return = new stdClass();
				$return->error = true;

				$name = str_replace(" ", "-", strtolower(Inflector::latinize($params['new_name'])));
				$name = str_replace("_", "-", $name);
				$name = str_replace("/", "-", $name);

				$folder = $this->path.$params['rename_folder'];
				if (is_dir($folder) AND $name != '') {
					$new_name = explode("/", $folder);
					array_pop($new_name);
					$new_name = implode("/", $new_name)."/".$name;

					if (rename($folder, $new_name)) {
						$return->error = false;
						$this->log_action(array('message' => 'Files > Rename folder '.$folder.' > '.$new_name.'.'));
					}
				}

				echo json_encode($return);
			}
			elseif (isset($params['rename_file'], $params['new_name'])) {
				$return = new stdClass();
				$return->error = true;

				$name = str_replace(" ", "-", strtolower(Inflector::latinize($params['new_name'])));
				$name = str_replace("/", "-", $name);

				if (is_file($params['rename_file']) AND $name != '') {
					$new_name = explode("/", $params['rename_file']);
					array_pop($new_name);
					$new_name = implode("/", $new_name)."/".$name;

					$extension = explode(".", $params['rename_file']);
					$extension = array_pop($extension);

					if ($extension) {
						if (rename($params['rename_file'], $new_name.".".$extension)) {
							$return->error = false;
							$this->log_action(array('message' => 'Files > Rename file.'));
						}
					}
				}

				echo json_encode($return);
			}

			exit;
		}
	}

	private function get_folder_list($path, $subpath="", $subfolders = false) {
		$base = $path;

		if ($subpath != "") {
			$path = $base.$subpath."/";
		}

		$tree  = scandir($path);
		usort ($tree, create_function ('$a,$b', '
			return  is_dir ($a)
				? (is_dir ($b) ? strnatcasecmp ($a, $b) : -1)
				: (is_dir ($b) ? 1 : (
					strcasecmp (pathinfo ($a, PATHINFO_EXTENSION), pathinfo ($b, PATHINFO_EXTENSION)) == 0
					? strnatcasecmp ($a, $b)
					: strcasecmp (pathinfo ($a, PATHINFO_EXTENSION), pathinfo ($b, PATHINFO_EXTENSION))
				))
			;
		'));

		// $finfo = finfo_open(FILEINFO_MIME_TYPE);

		foreach ($tree as $key => $el) {
			if ($el != '.' AND $el != '..') {
				if (is_dir($path.$el)) {
					$tree[$key] = array(
						'name' => $el,
						'type' => 'folder',
						'short_name' => $el
					);

					if ($subpath != "") {
						$tree[$key]['name'] = $subpath."/".$el;
					}

					if ($subfolders == true) {
						$tree[$key]['content'] = $this->get_folder_list($path.$el."/");
					}
				} else {
					if (is_file($path.$el)) {
						// $extension = finfo_file($finfo, $path.$el);
						$extension = Files::mime_content_type($path.$el);

						$short_name = explode(".", $el);
						array_pop($short_name);
						$short_name = implode(".", $short_name);

						if (in_array($extension, $this->allowed_image_types)) {
							$tree[$key] = array(
								'name' => $el,
								'type' => 'image',
								'short_name' => $short_name
							);

							if (filesize($path.$el) > $this->max_image_preview_size) {
								$tree[$key]['hide_preview'] = true;
							}
						} else if (in_array($extension, $this->allowed_doc_types)) {
							$tree[$key] = array(
								'name' => $el,
								'type' => 'doc',
								'short_name' => $short_name
							);
						} else if (in_array($extension, $this->allowed_archive_types)) {
							$tree[$key] = array(
								'name' => $el,
								'type' => 'archive',
								'short_name' => $short_name
							);
						} else if (in_array($extension, $this->allowed_video_types)) {
							$tree[$key] = array(
								'name' => $el,
								'type' => 'video',
								'short_name' => $short_name
							);
						} else if (in_array($extension, $this->allowed_music_types)) {
							$tree[$key] = array(
								'name' => $el,
								'type' => 'music',
								'short_name' => $short_name
							);
						} else {
							unset($tree[$key]);
						}
					}
				}
			} else {
				unset($tree[$key]);
			}
		}

		// finfo_close($finfo);

		return $tree;
	}
}

?>