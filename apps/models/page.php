<?php

class Page extends NestedSet {
	public
		$table_name = 'pages',
		$orig_table_name = 'pages';

	public $file_extensions = array(
            'application/pdf' => 'pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/msword' => 'doc',
            'application/vnd.oasis.opendocument.text ' => 'odt'
        );

    public $image_extensions = array(
        'image/jpeg' => 'jpg',
        'image/jpg'  => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/gif' => 'gif',
        'image/png' => 'png'
    );

	protected
		$is_i18n = true,
		$has_mirror = false;

    protected $has_many = array(
            'images' => array(
                'association_foreign_key' => 'id', 
                'foreign_key' => 'module_id', 
                'class_name' => 'image', 
                'join_table' => 'pages', 
                'conditions' => "module = 'pages' and keyname='images'", 
            ),
            'files' => array(
                'association_foreign_key' => 'id', 
                'foreign_key' => 'module_id', 
                'class_name' => 'file', 
                'join_table' => 'pages',
                'conditions' => "module = 'pages' and keyname='files'"
            )
        );
	function __toString() {
		return $this->title;
	}
	
	function cache_menu() {
			$menu = [];
			$pages = (new Page)->find_all([
				'select' => 'in_main_menu, in_topbar, in_footer, slug, title',
				'conditions' => 'visibility = 2 AND (in_main_menu = 1 OR in_topbar = 1 OR in_footer > 0)',
				'order' => 'lft ASC',
			]);

			while ($page = array_shift($pages)) {
				if ($page->in_topbar) {
					$menu['TOPBAR'][] = (object)[
						'slug' => $page->slug,
						'title' => $page->title,
					];
				}

				if ($page->in_main_menu) {
					$menu['MAIN'][] = (object)[
						'slug' => $page->slug,
						'title' => $page->title,
					];
				}

				if ($page->in_footer) {
					$menu['FOOTER'][$page->in_footer][] = (object)[
						'slug' => $page->slug,
						'title' => $page->title,
					];
				}
			}
			
			$content = serialize($menu);
			$fp = fopen(Config()->ROOT_PATH.'cache/pagemenu','w');
			fwrite($fp,$content);
			fclose($fp);
			return $content;
		}
		
		function load_cache_menu() {
			$content = file_get_contents(Config()->ROOT_PATH.'cache/pagemenu');
			if(!$content) {
			//	$content = $this->cache_menu();
			}
			
			return $content;
		}

    /*function __get($k) {

        if($k=='slug') {
            return str_replace('//','/',Config()->COOKIE_PATH.$this->i18n_column_values[$k][$this->get_locale()]);
        }

        return parent::__get($k);
    }*/

	function before_validation() {
		parent::before_validation();
		if(($page = $this->find_by_slug($this->slug)) instanceOf Page && $page->id != $this->id) {
			$this->errors[] = Registry()->localizer->get_label('DB_FIELDS', 'slug')  . ' - ' . Registry()->localizer->get_label('DB_SAVE_ERRORS', 'already_exists');
		}

		$this->placeholders = 1;/// ne se polzva tova pole
	}

	function before_create() {
        parent::before_create();
        $layout = array();
        $visibility = array();
        $title = array();
        $slug = array();
        $page_title = array();
        $options = array();

        foreach (Config()->LOCALE_SHORTCUTS as $value) {
            $layout[$value] = $this->layout;
            $visibility[$value] = 0;
            $title[$value] = $this->title;
            $slug[$value] = $this->slug;
            $page_title[$value] = $this->page_title;
            $options[$value] = $this->options;
        }

        $visibility[Registry()->locale] = $this->visibility;
        $this->visibility = $visibility;

        $this->layout = $layout;
        $this->title = $title;
        $this->slug = $slug;
        $this->page_title = $page_title;
        $this->options = $options;
    }

	function title_path() {
		$all = $this->get_parents();
		$result = array();

		foreach($all as $a) {
			if($a->title) {
				$result[] = $a->title;
			}
		}

		array_pop($result);
		$result = array_reverse($result);

		return join(' / ',$result);
	}
}

?>