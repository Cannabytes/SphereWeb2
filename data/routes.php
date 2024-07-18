<?php

return array (
  0 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/main',
    'func' => 'controller\\main\\main::index',
    'access' => 
    array (
      0 => 'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Start page!!',
  ),
  1 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/user/change/lang/{lang}',
    'func' => 'controller\\config\\config::setLang',
    'access' => 
    array (
      0 => 'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Change lang',
  ),
  2 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/admin/server/add/new',
    'func' => 'controller\\admin\\options::new_server',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => NULL,
    'comment' => NULL,
  ),
  3 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/options/server/client/protocol',
    'func' => 'component\\chronicle\\client::get_base_collection_class',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => NULL,
    'comment' => NULL,
  ),
  4 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/statistic',
    'func' => NULL,
    'access' => 
    array (
      0 => 'any',
    ),
    'weight' => 0,
    'page' => 'statistic.html',
    'comment' => 'Statistic page',
  ),
  5 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/shop',
    'func' => 'controller\\donate\\pay::shop',
    'access' => 
    array (
      0 => 'any',
    ),
    'weight' => 0,
    'page' => NULL,
    'comment' => NULL,
  ),
  6 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/route/add',
    'func' => 'controller\\route\\route::add',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => NULL,
    'comment' => NULL,
  ),
  7 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/route/get/file',
    'func' => 'controller\\route\\route::getDirFiles',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => NULL,
    'comment' => NULL,
  ),
  8 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/route/update/enable',
    'func' => 'controller\\route\\route::update_enable',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => NULL,
    'comment' => NULL,
  ),
  9 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/ticket',
    'func' => 'controller\\ticket\\ticket::all',
    'access' => 
    array (
      0 => 'user',
      1 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Tickets list',
  ),
  10 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/ticket/(\\d+)',
    'func' => 'controller\\ticket\\ticket::get',
    'access' => 
    array (
      0 => 'admin',
      1 => 'user',
    ),
    'weight' => 0,
    'page' => NULL,
    'comment' => 'Ticket read',
  ),
  11 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/ticket/message',
    'func' => 'controller\\ticket\\ticket::message',
    'access' => 
    array (
      0 => 'admin',
      1 => 'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Ticket send message',
  ),
  12 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/route/edit',
    'func' => 'controller\\route\\route::edit',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 1,
    'page' => '',
    'comment' => 'Update route data',
  ),
  13 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/admin/shop',
    'func' => NULL,
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => 'admin/shop_add.html',
    'comment' => 'Shop manager',
  ),
  14 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/shop',
    'func' => 'model\\admin\\donate::add_item',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => 'admin/shop_add.html',
    'comment' => '',
  ),
  15 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/shop/remove/item',
    'func' => 'model\\admin\\donate::remove_item',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  16 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/shop/get',
    'func' => 'controller\\donate\\shop::getShopObjectJSON',
    'access' => 
    array (
      0 => 'user',
      1 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Получить информацию о продаваемых предметах',
  ),
  17 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/shop/purchase',
    'func' => 'controller\\donate\\pay::buyShopItem',
    'access' => 
    array (
      0 => 'user',
      1 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Purchase of goods',
  ),
  18 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/shop/towarehouse',
    'func' => 'model\\donate\\donate::toWarehouse',
    'access' => 
    array (
      0 => 'admin',
      1 => 'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Shop items to warehouse',
  ),
  19 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/user/avatar',
    'func' => 'controller\\user\\profile\\change::show_avatar_page',
    'access' => 
    array (
      0 => 'admin',
      1 => 'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Set user avatar',
  ),
  20 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/admin/forum',
    'func' => NULL,
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => 'admin/forum.html',
    'comment' => '',
  ),
  21 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/forum',
    'func' => 'model\\forum\\forum::saveConfig',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Save forum settings',
  ),
  22 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/admin/phrases',
    'func' => NULL,
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '/admin/phrases.html',
    'comment' => 'Set multilang phrases',
  ),
  23 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/phrases',
    'func' => 'model\\phrases\\phrases::save',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  24 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/function/save',
    'func' => 'model\\enabled\\enabled::save',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Save status function',
  ),
  25 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/config/save',
    'func' => 'model\\config\\config::save',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Save Sphere config',
  ),
  26 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/admin/sphere/update',
    'func' => NULL,
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '/admin/selfupdate.html',
    'comment' => '',
  ),
  27 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/admin/route',
    'func' => 'controller\\route\\route::all',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  28 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/news',
    'func' => NULL,
    'access' => 
    array (
      0 => 'any',
    ),
    'weight' => 0,
    'page' => 'pages.html',
    'comment' => '',
  ),
  29 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/install',
    'func' => 'controller\\install\\install::rules',
    'access' => 
    array (
      0 => 'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  30 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/balance',
    'func' => 'controller\\donate\\pay::pay',
    'access' => 
    array (
      0 => 'user',
      1 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  31 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/admin/setting',
    'func' => 'controller\\admin\\options::server_show',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  32 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/admin/pages',
    'func' => NULL,
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => 'admin/pages.html',
    'comment' => '',
  ),
  33 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/admin/pages/edit/(\\d+)',
    'func' => NULL,
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '/admin/page_edit.html',
    'comment' => '',
  ),
  34 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/admin/pages/create',
    'func' => NULL,
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '/admin/page_create.html',
    'comment' => '',
  ),
  35 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/page/(\\d+)',
    'func' => NULL,
    'access' => 
    array (
      0 => 'any',
    ),
    'weight' => 0,
    'page' => '/page.html',
    'comment' => '',
  ),
  36 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/user/change/avatar',
    'func' => 'controller\\user\\profile\\change::save_avatar',
    'access' => 
    array (
      0 => 'admin',
      1 => 'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  37 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/admin/users',
    'func' => NULL,
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '/admin/users.html',
    'comment' => '',
  ),
  38 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/',
    'func' => 'controller\\promo\\promo::index',
    'access' => 
    array (
      0 => 'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  39 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/auth/logout',
    'func' => 'controller\\user\\auth\\auth::logout',
    'access' => 
    array (
      0 => 'admin',
      1 => 'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  40 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/auth',
    'func' => 'controller\\user\\auth\\auth::index',
    'access' => 
    array (
      0 => 'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  41 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/captcha',
    'func' => 'component\\captcha\\captcha::defence',
    'access' => 
    array (
      0 => 'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  43 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/registration/account',
    'func' => 'controller\\registration\\account::requestNewAccount',
    'access' => 
    array (
      0 => 'admin',
      1 => 'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  46 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/send/to/player',
    'func' => 'controller\\account\\characters\\inventory::sendToGame',
    'access' => 
    array (
      0 => 'admin',
      1 => 'user',
    ),
    'weight' => 0,
    'page' => 'sendtogame.html',
    'comment' => '',
  ),
  47 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/player/account/reload',
    'func' => 'controller\\account\\characters\\reload::reload',
    'access' => 
    array (
      0 => 'admin',
      1 => 'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  48 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/player/account/change/password',
    'func' => 'controller\\account\\password\\change::password',
    'access' => 
    array (
      0 => 'admin',
      1 => 'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Change player password',
  ),
  49 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/option/server/db/connect/select/name',
    'func' => 'controller\\admin\\options::test_connect_db_selected_name',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Check MySQL Connect',
  ),
  50 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/create/server/new',
    'func' => 'controller\\admin\\options::create_server',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Add new server',
  ),
  51 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/user/change/server',
    'func' => 'controller\\user\\default_server::change',
    'access' => 
    array (
      0 => 'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Change server',
  ),
  52 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/admin/shop/startpack',
    'func' => 'controller\\admin\\startpack::index',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '/admin/startpack.html',
    'comment' => '',
  ),
  53 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/client/item/info',
    'func' => 'component\\image\\client_icon::get_item_info_json',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Item data info',
  ),
  54 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/shop/startpack',
    'func' => 'controller\\admin\\startpack::add',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  55 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/startpack/purchase',
    'func' => 'controller\\admin\\startpack::purchase',
    'access' => 
    array (
      0 => 'user',
      1 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  56 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/startpack/purchase/warehouse',
    'func' => 'controller\\admin\\startpack::purchaseWarehouse',
    'access' => 
    array (
      0 => 'admin',
      1 => 'guest',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  57 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/setting/donate/save',
    'func' => 'controller\\admin\\options::saveConfigDonate',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  58 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/setting/referral/save',
    'func' => 'controller\\admin\\options::saveConfigReferral',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  59 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/admin/server/list',
    'func' => 'controller\\admin\\options::edit_server_show',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  60 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/admin/server/edit/(\\d+)',
    'func' => 'controller\\admin\\options::edit_server_show',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  61 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/server/edit/save/general',
    'func' => 'controller\\admin\\options::saveGeneral',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  62 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/server/edit/save/other',
    'func' => 'controller\\admin\\options::saveOther',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  63 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/server/edit/save/mysql',
    'func' => 'controller\\admin\\options::saveMySQL',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  64 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/player/relocation',
    'func' => 'controller\\account\\characters\\relocation::playerMove',
    'access' => 
    array (
      0 => 'admin',
      1 => 'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Return player in XYZ',
  ),
  65 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/users/sendToBalance',
    'func' => 'controller\\admin\\donate::add_bonus_money',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  66 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/account/synchronization',
    'func' => 'controller\\account\\comparison\\comparison::synchronization',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  67 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/donate/pay',
    'func' => 'controller\\donate\\pay::pay',
    'access' => 
    array (
      0 => 'admin',
      1 => 'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  68 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/setting/logo/save',
    'func' => 'controller\\logo\\logo::logo',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Set logo Sphere',
  ),
  42 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/referral',
    'func' => 'controller\\referral\\referral::show',
    'access' => 
    array (
      0 => 'user',
      1 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  44 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/inventory/send',
    'func' => 'controller\\account\\characters\\inventory::warehouseToGame',
    'access' => 
    array (
      0 => 'user',
      1 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Send items to player',
  ),
  45 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/user/change',
    'func' => 'controller\\user\\profile\\change::save',
    'access' => 
    array (
      0 => 'admin',
      1 => 'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  69 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/setting/favicon/save',
    'func' => 'controller\\logo\\logo::favicon',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  70 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/referral/bonus',
    'func' => 'controller\\referral\\referral::bonus',
    'access' => 
    array (
      0 => 'user',
      1 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Получение бонусов за рефералов',
  ),
  71 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/player/reset/hwid',
    'func' => 'controller\\account\\characters\\hwid::reset',
    'access' => 
    array (
      0 => 'user',
      1 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Reset HWID',
  ),
  72 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/registration/account/prefix',
    'func' => 'component\\account\\generation::createPrefix',
    'access' => 
    array (
      0 => 'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Create Prefix',
  ),
  73 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/server/edit/collection',
    'func' => 'controller\\admin\\options::updateCollection',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  74 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/setting/email/send/test',
    'func' => 'component\\mail\\mail::sendTest',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Test send email',
  ),
  75 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/admin/mailing',
    'func' => NULL,
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '/admin/mailing.html',
    'comment' => '',
  ),
  76 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/logs',
    'func' => '',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '/admin/logs.html',
    'comment' => 'All logs',
  ),
  77 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/logs/update',
    'func' => 'model\\log\\log::getNewLogs',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  78 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/server/statistic/(\\d+)',
    'func' => 'controller\\admin\\statistic::getStatistic',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Server Statistic',
  ),
  79 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/bonuscode/create',
    'func' => 'controller\\admin\\bonuscode::create_pack',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  80 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/bonuscode/create',
    'func' => 'controller\\admin\\bonuscode::genereate',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  81 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/bonus/code',
    'func' => 'controller\\account\\bonus\\bonus::receiving',
    'access' => 
    array (
      0 => 'user',
      1 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Получение предмета за бонус код',
  ),
  82 => 
  array (
    'enable' => 0,
    'method' => 'GET',
    'pattern' => '/registration/user',
    'func' => NULL,
    'access' => 
    array (
      0 => 'guest',
    ),
    'weight' => 0,
    'page' => 'sign-up.html',
    'comment' => '',
  ),
  83 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/registration/user',
    'func' => 'controller\\registration\\user::add',
    'access' => 
    array (
      0 => 'guest',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  84 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/auth',
    'func' => 'controller\\user\\auth\\auth::auth_request',
    'access' => 
    array (
      0 => 'guest',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  85 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/login',
    'func' => NULL,
    'access' => 
    array (
      0 => 'guest',
    ),
    'weight' => 0,
    'page' => 'sign-in.html',
    'comment' => '',
  ),
  86 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/signup',
    'func' => NULL,
    'access' => 
    array (
      0 => 'guest',
    ),
    'weight' => 0,
    'page' => 'sign-up.html',
    'comment' => 'Registration Page',
  ),
  87 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/signup/{name}',
    'func' => NULL,
    'access' => 
    array (
      0 => 'guest',
    ),
    'weight' => 0,
    'page' => 'sign-up.html',
    'comment' => 'Registration Page with referral',
  ),
  88 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/forget',
    'func' => NULL,
    'access' => 
    array (
      0 => 'guest',
    ),
    'weight' => 0,
    'page' => 'forget.html',
    'comment' => 'Forget password',
  ),
  89 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/forget/create',
    'func' => 'controller\\user\\forget\\forget::create',
    'access' => 
    array (
      0 => 'guest',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'forget email: Create code and send to email',
  ),
  90 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/forget/password/reset/{code}',
    'func' => 'controller\\user\\forget\\forget::validate',
    'access' => 
    array (
      0 => 'guest',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Valid  code reset password',
  ),
  91 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/bonuscode/list',
    'func' => 'controller\\admin\\bonuscode::show_code',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  92 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/github/update',
    'func' => 'model\\github\\update::checkNewCommit',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  93 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/server/delete',
    'func' => 'controller\\admin\\options::delete_server',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  94 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin',
    'func' => 'controller\\admin\\index::index',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  95 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/extensions/paid',
    'func' => 'controller\\admin\\plugin::show',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  96 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/github/update/progress',
    'func' => 'model\\github\\update::getUpdateProgress',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  97 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/pages/create',
    'func' => 'controller\\admin\\page::create_news',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  98 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/pages/edit',
    'func' => 'controller\\admin\\page::update_news',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  99 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/pages/trash',
    'func' => 'controller\\admin\\page::trash_send',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  100 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/user/info/(.*)',
    'func' => 'controller\\admin\\users::getUserInfo',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '/admin/user_profile.html',
    'comment' => '',
  ),
  101 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/user/edit',
    'func' => 'controller\\admin\\users::edit',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
);
