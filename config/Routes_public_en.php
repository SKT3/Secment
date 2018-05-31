<?php

$lang = 'bg';
Router()->add('error404', '404', array('controller' => 'application', 'action' => 'error404'));
Router()->add('preview', '/pages/preview/:id', array('controller' => 'pages', 'action' => 'preview', 'id' => COMPULSORY));
Router()->add('pages_ajax','/pages/xhr/:method', array('controller'=>'pages', 'action'=>'xhr', 'method'=>COMPULSORY));

load_routers_from_models();

Router()->add('basket', '/basket/xhr/:method', array(
    'controller' => 'basket',
    'action' => 'xhr',
    'method' => COMPULSORY,
), array(
    'method' => 'manipulate'
));

Router()->add('basket', '/basket/:action', array(
    'controller' => 'basket',
    'action' => COMPULSORY,
), array(
    'action' => '/step[2-5]/'
));

Router()->add('basket', '/basket/complete', array(
    'controller' => 'basket',
    'action' => 'complete',
));

Router()->add('basket_index', '/basket', array(
    'controller' => 'basket'
));

Router()->add('product_view', '/product/:slug', array(
    'controller' => 'products',
    'app' => 'products',
    'action' => 'view',
    'slug' => COMPULSORY
));

Registry()->MAX_CATEGORIES_LEVEL = (new CategoriesModel())->max_all('lvl');
$path = '/products';

for ($i = 0; $i <= Registry()->MAX_CATEGORIES_LEVEL; ++$i) {
    $path .= '/:cat'.($i+1);
}

// History View
Router()->add('profile_history_view', '/profile/history_view/:id' , array(
    'controller' => 'profiles',
    'action' => 'history_view'
));


// Address Edit
Router()->add('profile_address_edit', '/profile/address/:id' , array(
    'controller' => 'profiles',
    'action' => 'edit_address',
    'id' => COMPULSORY
));

// Address Delete
Router()->add('profile_address_delete', '/profile/address-delete/:id' , array(
    'controller' => 'profiles',
    'action' => 'delete_address',
    'id' => COMPULSORY
));

//Promotions
$page = new Page();

$promotion_pages = Registry()->PROMOTION_PAGES ? Registry()->PROMOTION_PAGES : array();

$promotion_pages[$lang] = $page->find_by_page_type('promotions');

if ($promotion_pages[$lang]) {
    Router()->add('products_promo', $promotion_pages[$lang]->slug, array(
        'app' => 'products',
        'controller' => 'products',
        'action' => 'promotions'
    ));
}

Registry()->PROMOTION_PAGES = $promotion_pages;
unset($page, $promotion_pages);



// History
Router()->add('profile_history', '/profile/history' , array(
    'controller' => 'profiles',
    'action' => 'history'
));


// Address Add
Router()->add('profile_addresses', '/profile/address' , array(
    'controller' => 'profiles',
    'action' => 'add_address'
));

// Password Change
Router()->add('profile_password_change', '/profile/change_password' , array(
    'controller' => 'profiles',
    'action' => 'change_password'
));

// Subscription
Router()->add('profile_subscription', '/profile/subscription' , array(
    'controller' => 'profiles',
    'action' => 'subscription'
));


// Product list
Router()->add('products_list', $path, array(
    'controller' => 'products',
    'app' => 'products',
));


// Profile
Router()->add('profile', '/profile' , array(
    'controller' => 'profiles',
));


Router()->add('pages_default', '/:lvl1/:lvl2/:lvl3/:lvl4/:lvl5/:lvl6/:lvl7/:lvl8/:lvl9/:lvl10/:id', array('controller' => 'pages', 'action' => 'view', 'lvl1' => COMPULSORY));
Router()->add('index', '', array('controller' => 'pages', 'action' => 'index'));


