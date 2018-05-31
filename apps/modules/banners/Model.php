<?php

class BannersModel extends Modules {

    public $module = 'banners';
    public $table_name = 'aa';

    protected $is_i18n = false;
    protected $has_mirror = false;

    public $image_extensions = array(
        'image/jpeg'    => 'jpg',
        'image/jpg'     => 'jpg',
        'image/pjpeg'   => 'jpg',
        'image/gif'     => 'gif',
        'image/png'     => 'png'
    );

    protected $has_one = array(
        'image' => array(
            'association_foreign_key' => 'id',
            'foreign_key' => 'module_id',
            'class_name' => 'image',
            'join_table' => 'banners',
            'is_required' => false,
            'conditions' => "module = 'banners' and keyname='image'"
        ));

}
?>