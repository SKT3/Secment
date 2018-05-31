<?php

class BannersController extends ModulesController {

    public $module = 'banners';
    public $models = 'BannersModel';


    public function index($params) {
        $obj = new $this->models[0];
        $params['sortable'] = false;
        $list = new ListHelper($params);



        if($this->admin_helper->can('edit')){
            $list->add_action('edit', url_for(array('controller'=>'admin','module' => $this->module,'maction'=>'edit', 'id' => ':id')));
        }

        if($this->admin_helper->can('delete')){
            $list->add_action('delete', 'javascript:confirm_delete(:id);');
        }

        $list->add_main_action(array(
            'link' => url_for(array('controller' => 'admin', 'module' => $this->module, 'maction' => 'banners_items')),
            'label' =>($this->localizer->get_label('MAIN_ACTIONS', 'banners_items'))
        ));


        $list->add_column('id');
        $list->add_column('title');
        $list->add_column('color');
        $list->add_column('created_at');



        $items = $obj->find_all($list->to_sql());

        $list->data($items);
        $this->render($list);
        $this->session->admin_return_to = $this->request->server('REQUEST_URI');

    }
}
?>