<?php

class PagesController extends ApplicationController {
    public
        $models = 'Page',
        $breadcrumbs = array(),
        $slug_suffix = '';

    public $homepages = array(
        'bg-BG' => 55,
    );

    protected
        $layout = 'index';

    function index($params)
    {

        $this->object_banners = (new BannersModel())->find_all();

        $this->view = 'index_content';
    }

    function view($params) {
        foreach ($params as $k => $v) {
            if (substr($k, 0, 3) == 'lvl' && !empty($v)) {
                $slug[] = $v;
            }

            if (is_null($v) || $v == '') {
                unset($params[$k]);
            }
        }



        if (!empty($params['id'])) {
            $this->obj = $this->Page->find_by_id((int) $params['id'], array('conditions' => 'visibility=2'));
        }

        $slug = join('/', $slug);

        // add / parsed slug is a string and does not have the slash "/" ahead
        (strlen($slug) > 1 && (!strstr($slug, '/') || stripos($slug, '/') > 0)) && ($slug = '/' . $slug);
        $params_slug = $slug;
        $this->params = $params;
        if (!$this->obj instanceOf Page) {
            $this->obj = $this->Page->find_by_slug($slug, array('conditions' => 'visibility=2'));
        }

        if (!$this->obj instanceOf Page) {
            Registry()->response->set_status(301);
            /*$this->obj = $this->Page->find_by_old_slug($slug, array('conditions' => 'visibility=2'));
            if ($this->obj instanceOf Page) {
                Registry()->response->set_status(301);
                $this->redirect_to($this->obj->slug);
            }

            $this->obj = $this->Page->find_by_old_slug($slug.'?'.$_SERVER['QUERY_STRING'], array('conditions' => 'visibility=2'));
            if ($this->obj instanceOf Page) {
                Registry()->response->set_status(301);
                $this->redirect_to($this->obj->slug);
            }*/
        }
        if (!$this->obj instanceOf Page) {
            $module_slug = end($params);
            $slug = str_replace('/' . end($params), $this->slug_suffix, $slug);

            $slug && $this->obj = $this->Page->find_by_slug($slug, array('conditions' => 'visibility=2'));

            if (!$this->obj instanceOf Page) {
                $slug = substr($slug, 0, strrpos($slug, '/'));
                $slug && $this->obj = $this->Page->find_by_slug($slug, array('conditions' => 'visibility=2'));
                /*if (!$this->obj instanceOf Page) {
                    $slug && $this->obj = $this->Page->find_by_old_slug($slug, array('conditions' => 'visibility=2'));
                    if ($this->obj instanceOf Page) {
                        Registry()->response->set_status(301);
                        $this->redirect_to($this->obj->slug);
                    }
                }*/

                if (!$this->obj instanceOf Page) {
                    $slug = substr($slug, 0, strrpos($slug, '/'));
                    $params_slug = $slug. str_replace($slug, '', $params_slug);
                    $slug && $this->obj = $this->Page->find_by_slug($params_slug, array('conditions' => 'visibility=2'));
                }
            }
        }

        if (!$this->obj instanceOf Page) {
            //Registry()->response->set_status(301);
            //$this->redirect_to(url_for(array()));
            $this->error404();
        }

        $this->options = unserialize($this->obj->options);

        if ($this->obj->page_type == 'link') {
            if ($this->options['page_type'] == 'link') {
                $linkpage = $this->Page->find_by_id((int) $this->options['page_id']);
                //Registry()->response->set_status(301);
                $this->redirect_to(Config()->COOKIE_PATH . substr(Registry()->locale, 0, 2).'/'.trim($linkpage->slug,'/'));
            } else {
                $mirror = $this->Page->find_by_id((int) $this->options['page_id']);
                if ($mirror instanceOf Page) {
                    $this->mirror_obj = $mirror;
                }
            }
        }

        if ($this->mirror_obj) {
            $this->placeholders = $this->mirror_obj->placeholders;
            $this->meta = array(
                'title' => $this->mirror_obj->meta_title,
                'description' => $this->mirror_obj->meta_description,
                'keywords' => $this->mirror_obj->meta_keywords
            );
            $this->canonical = url_for(array('action' => 'view', 'lvl1' => $this->mirror_obj->slug));
        } else {
            $this->placeholders = $this->obj->placeholders;
            $this->meta = array(
                'title' => $this->obj->meta_title,
                'description' => $this->obj->meta_description,
                'keywords' => $this->obj->meta_keywords
            );
        }

        if($this->obj->layout && $this->obj->layout != 'index') {
            $this->layout = $this->obj->layout;
        }

        /*if($this->obj) {
            $this->sub_menu = $this->obj->get_children(" AND visibility=2 AND exclude_from_menu = 0");
        }*/


        $this->options = unserialize($this->obj->options);
        if($this->options['carousels']) {
            $c = new CarouselsModel;
            $this->carousels = $c->find_all('id IN ('.join(',',$this->options['carousels']).')');
        }

        $this->mirror_page_url = array();
        $_all = Registry()->db->select('pages_i18n','i18n_locale,slug','i18n_foreign_key='.(int)$this->obj->id);
        foreach($_all as $a) {
            $this->mirror_page_url[substr($a->i18n_locale,0,2)]= '/'.substr($a->i18n_locale,0,2).$a->slug;
        }


        switch ($this->obj->page_type) {
            case 'text_page':
                $this->view = $this->obj->page_type;
                $this->layout = 'inner';
                break;
            case 'sitemap':
                $this->object = (new Page())->find_all();
                $categories = (new CategoriesModel())->find_all('parent_cat_id=0');
                foreach ($categories as $key=>$category) {
                    $this->sitemap_cats[$category->id]= array(
                        'title' => $category->title,
                        'slug' => $category->slug,
                    );
                    foreach($category->get_children() as $child) {
                        $this->sitemap_cats[$category->id]['children'][$child->id]['title'] = $child->title;
                        $this->sitemap_cats[$category->id]['children'][$child->id]['slug'] = $child->slug;

                        foreach($child->get_children() as $grandchild) {
                            $this->sitemap_cats[$category->id]['children'][$child->id]['children'][$grandchild->id]['title'] = $grandchild->title;
                            $this->sitemap_cats[$category->id]['children'][$child->id]['children'][$grandchild->id]['slug'] = $grandchild->slug;
                        }
                    }
                }
                $this->view = $this->obj->page_type;
                $this->layout = 'inner';
                break;
        }
        //d($this->obj);

    }


//    public function preview($params) {
//        if (isset($this->session->userinfo)) {
//            Registry()->is_preview = 1;
//            self::view(array('id' => (int) $params['id']));
//        }
//    }
}
