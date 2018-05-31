<?php

class SettingsController extends AdminController {
    public
        $models = 'Setting';

    function index($params) {
        $list = new ListHelper($params);
        $list->add_column('id');
        $list->add_column('setting_key', null, false);
        $list->add_column('val', null, false);

        if($this->admin_helper->can('edit')){
            $list->add_action('edit', url_for(array('controller' => 'settings', 'action' => 'edit', 'id' => ':id')));
        }
        /*if($this->admin_helper->can('delete')){
            $list->add_action('delete', 'javascript:confirm_delete(:id);');
        }*/

        $items_collections = $this->Setting->find_all(null, $this->Setting->get_order(), 30);
        $list->data($items_collections);
        $this->render($list);
        $this->session->admin_return_to = $this->request->server('REQUEST_URI');
    }

    function add() {
        if ($this->is_post()) {
            if($this->Setting->save($this->request->post())) {
                $this->log_action(array('id'=>$this->Setting->id,'message' => (string)$this->Setting));
                $this->flash_message_helper[] = $this->localizer->get_label('FLASH_MESSAGES', 'add');
                $this->redirect_to($this->session->admin_return_to);
            } else {
                $this->errors = $this->Setting->get_errors();
                $this->form_object = (object)$this->request->post();
            }
        }
    }

    function edit($params) {
        $item = $this->Setting->find((int)$params['id']);
        if (!$item instanceof Setting) {
            $this->redirect_to(url_for(array('controller' => 'settings')));
        }
        if ($this->is_post()) {
            if($item->save($_POST)) {
                $this->log_action(array('id'=>$item->id,'message' => (string)$item));
                $this->flash_message_helper[] = $this->localizer->get_label('FLASH_MESSAGES', 'edit');
                $this->redirect_to($this->session->admin_return_to);
            } else {
                $this->errors = $item->get_errors();
                $this->form_object = (object)$this->request->post();
            }
        } else {
            $this->form_object = $item;
        }
    }
}

?>