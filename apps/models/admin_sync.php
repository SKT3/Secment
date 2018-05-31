<?php


class AdminSync extends ActiveRecord {

    public $module = 'admin_sync';
    public $table_name = 'syncs';

    public function after_create() {
        parent::after_create();
        $this->sendMail();
    }

    public function sendMail() {

        $sync = $this->find_by_id($this->id);
        $sync->new = json_decode($sync->new);
        $sync->deactivated = json_decode($sync->deactivated);
        $sync->no_update = json_decode($sync->no_update);

        Registry()->tpl->assign('form_object', $sync );
        $from = array(Registry()->localizer->get_label('CONTACTS_LABELS','sync_mail_from_name'),'stefan.karadjov@themags.com');
        $to = 'web-1cepu@mail-tester.com';
        $subject = 'Информация Цинхронизация';
        $text = Registry()->tpl->fetch(Config()->VIEWS_PATH.'/public/partials/sync_email.htm');

        //send_php_mail(array('from' => $from, 'mail' => $to, 'subject' => $subject, 'html' => $text));
    }
	
}

?>
