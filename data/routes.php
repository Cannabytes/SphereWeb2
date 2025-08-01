<?php

return array (
  0 => 
  array (
    'enable' => true,
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
    'enable' => true,
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
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/ticket/(\\d+)',
    'func' => 'controller\\ticket\\ticket::ticketAdmin',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Ticket read',
  ),
  11 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/ticket/send/message',
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
    'enable' => true,
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
    'enable' => true,
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
    'enable' => true,
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
      1 => 'user',
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
    'func' => 'controller\\admin\\options::servers_show',
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
    'func' => 'controller\\admin\\options::server_edit',
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
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/account/synchronization',
    'func' => 'controller\\account\\comparison\\comparison::synchronization',
    'access' => 
    array (
      0 => 'admin',
      1 => 'user',
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
    'enable' => false,
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
  102 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/sphereapi',
    'func' => 'controller\\sphereapi\\sphereapi::index',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  103 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/sphereapi/save',
    'func' => 'controller\\sphereapi\\sphereapi::save',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  104 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/sphereapi/check',
    'func' => 'controller\\sphereapi\\sphereapi::check',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  105 => 
  array (
    'enable' => false,
    'method' => 'GET',
    'pattern' => '/logan22',
    'func' => 'component\\plugins\\set_http_referrer\\httpReferrerPlugin::addUserReferer',
    'access' => 
    array (
      0 => 'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  106 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/ticket/get/message',
    'func' => 'controller\\ticket\\ticket::getNewMessage',
    'access' => 
    array (
      0 => 'admin',
      1 => 'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  107 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/ticket/block',
    'func' => 'controller\\ticket\\ticket::blockTicket',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  108 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/ticket/clear/dialog',
    'func' => 'controller\\ticket\\ticket::clearDiaglog',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  109 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/ticket/get/last/list',
    'func' => 'controller\\ticket\\ticket::getUpdateTicketList',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  110 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/ticket/load/file',
    'func' => 'controller\\ticket\\ticket::fileLoad',
    'access' => 
    array (
      0 => 'admin',
      1 => 'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  111 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/github/update/auto',
    'func' => 'model\\github\\update::autoRemoteUpdate',
    'access' => 
    array (
      0 => 'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Тестируемая функция автоматического старта обновлений',
  ),
  112 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/notification/get',
    'func' => 'model\\notification\\notification::get_new_notification',
    'access' => 
    array (
      0 => 'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  113 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/user/add/item/warehouse',
    'func' => 'controller\\admin\\users::addItemUserToWarehouse',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Выдача пользователю предмета в warehouse',
  ),
  114 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/stream',
    'func' => 'controller\\stream\\stream::show',
    'access' => 
    array (
      0 => 'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Страница со списками стримов',
  ),
  115 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/stream/add',
    'func' => 'controller\\stream\\stream::add',
    'access' => 
    array (
      0 => 'user',
      1 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Add new stream',
  ),
  116 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/stream',
    'func' => 'controller\\admin\\stream::show',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  117 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/stream/satisfy',
    'func' => 'controller\\admin\\stream::satisfy',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  120 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/stream/(.*)',
    'func' => 'controller\\stream\\stream::getUserStream',
    'access' => 
    array (
      0 => 'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  121 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/stream/delete',
    'func' => 'controller\\stream\\stream::userDeleteStream',
    'access' => 
    array (
      0 => 'user',
      1 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Удаление записи о стриме пользователем',
  ),
  122 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/stream/set/auto/check',
    'func' => 'controller\\admin\\stream::setAutoCheck',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  123 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/response/request',
    'func' => 'component\\cron\\arrival::receiving',
    'access' => 
    array (
      0 => 'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Приемщик автозапросов, на случай если нужно выполнять какие-то действий.',
  ),
  124 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/stream/remove/auto/check',
    'func' => 'controller\\admin\\stream::removeAutoCheck',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Удаление автообновления статуса трансляций из канала пользователя',
  ),
  125 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/stream/remove',
    'func' => 'controller\\admin\\stream::removeStream',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Полное удаление информации о стриме',
  ),
  126 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/setting/background/save',
    'func' => 'controller\\save\\background\\background::save',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  127 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/bonuscode/delete',
    'func' => 'controller\\admin\\bonuscode::delete',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  129 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/server/reconnect',
    'func' => 'controller\\admin\\server::server_reconnect',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  130 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/balance',
    'func' => 'controller\\admin\\swbalance::get',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  133 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/shop/startpack/remove/pack',
    'func' => 'controller\\admin\\startpack::removePack',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  134 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/phrases/custom',
    'func' => '',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '/admin/phrases_custom.html',
    'comment' => '',
  ),
  135 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/phrases/custom',
    'func' => 'model\\phrases\\phrases::saveCustom',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  136 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/balance/pay',
    'func' => 'controller\\admin\\swbalance::pay',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  137 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/balance/pay/invoice',
    'func' => 'controller\\admin\\swbalance::payInvoice',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  138 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/balance/pay/history',
    'func' => 'controller\\admin\\swbalance::historyPay',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  139 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/server/remove/loginserver',
    'func' => 'controller\\admin\\options::removeLoginserver',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  140 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/template/readme',
    'func' => 'controller\\admin\\options::getTemplateInfo',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  141 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/api/user/global/add/email/check',
    'func' => 'component\\sphere\\superuser::create',
    'access' => 
    array (
      0 => 'admin',
      1 => 'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  144 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/balance/faq',
    'func' => '',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '/admin/balance_faq.html',
    'comment' => '',
  ),
  145 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/plugin/save/activator',
    'func' => 'model\\plugin\\plugin::__save_activator_plugin',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  146 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/plugin/save/config',
    'func' => 'model\\plugin\\plugin::saveSetting',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  147 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/balance/pay/renewlicense',
    'func' => 'controller\\admin\\swbalance::renewLicense',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  148 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/db/add/new/connect',
    'func' => 'controller\\admin\\options::add_new_mysql_connect_to_server',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  149 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/create/server/edit',
    'func' => 'controller\\admin\\options::server_edit_save',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  150 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/server/db',
    'func' => 'controller\\admin\\databases::show',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  151 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/stream/delete/test',
    'func' => 'controller\\admin\\stream::test',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  152 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/database/account/download',
    'func' => 'controller\\admin\\databases::downloadAccounts',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  153 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/database/account/delete',
    'func' => 'controller\\admin\\databases::deleteImportFile',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  154 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/server/errors/(\\d+)',
    'func' => 'controller\\admin\\errors::getErrors',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  155 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/server/errors/clear',
    'func' => 'controller\\admin\\errors::clear',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  156 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/database/delete',
    'func' => 'controller\\admin\\databases::delete',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  157 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/server/change/position',
    'func' => 'controller\\admin\\options::changePositionServer',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  158 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/server/set/donate/(\\d+)',
    'func' => 'controller\\admin\\setDonateServer::show',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  159 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/server/set/default',
    'func' => 'controller\\admin\\options::setDefaultServer',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  160 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/server/statistic/donate',
    'func' => 'controller\\admin\\statistic::getDonate',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  161 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/server/db/quality',
    'func' => 'controller\\admin\\databases::connectionQualityCheck',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  162 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/server/port/quality',
    'func' => 'controller\\admin\\databases::portQualityCheck',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  163 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/database/account/load',
    'func' => 'controller\\admin\\databases::loadAccounts',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  164 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/server/set/referral/(\\d+)',
    'func' => 'controller\\admin\\referral::showOption',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  165 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/support',
    'func' => 'controller\\support\\support::show',
    'access' => 
    array (
      0 => 'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  166 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/support/read/(\\d+)',
    'func' => 'controller\\support\\support::read',
    'access' => 
    array (
      0 => 'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  167 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/support/new',
    'func' => 'controller\\support\\support::create',
    'access' => 
    array (
      0 => 'admin',
      1 => 'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  168 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/support/create/request',
    'func' => 'controller\\support\\support::requestCreate',
    'access' => 
    array (
      0 => 'admin',
      1 => 'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  169 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/support/reply/request',
    'func' => 'controller\\support\\support::requestReply',
    'access' => 
    array (
      0 => 'user',
      1 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  170 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/support/thread/(\\d+)',
    'func' => 'controller\\support\\support::showThread',
    'access' => 
    array (
      0 => 'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  171 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/support/admin/add/section',
    'func' => 'controller\\support\\support::addSection',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  172 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/support/admin/delete/section',
    'func' => 'controller\\support\\support::deleteSection',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  173 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/support/admin/delete/topic',
    'func' => 'controller\\support\\support::deleteTopic',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  174 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/support/admin/update/moderator',
    'func' => 'controller\\support\\support::updateModeratorsPrivilege',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  175 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/support/admin/topic/close',
    'func' => 'controller\\support\\support::closeTopic',
    'access' => 
    array (
      0 => 'admin',
      1 => 'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  176 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/support/load/file',
    'func' => 'controller\\support\\support::fileLoad',
    'access' => 
    array (
      0 => 'admin',
      1 => 'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  177 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/support/admin/delete/post',
    'func' => 'controller\\support\\support::deletePost',
    'access' => 
    array (
      0 => 'admin',
      1 => 'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  178 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/support/admin/move',
    'func' => 'controller\\support\\support::toMove',
    'access' => 
    array (
      0 => 'admin',
      1 => 'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  179 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/telegram/notice/test',
    'func' => 'controller\\admin\\telegram::testSendNotice',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  180 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/server/update/loginserver',
    'func' => 'controller\\admin\\databases::updateLoginserver',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  181 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/server/setting/get',
    'func' => 'controller\\admin\\setDonateServer::getDonateSetting',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  182 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/server/set/enabled',
    'func' => 'controller\\admin\\enabled::setEnabled',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  183 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/balance/pay/pack',
    'func' => 'controller\\admin\\swbalance::buyPack',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  184 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/database/account/load/progress',
    'func' => 'controller\\admin\\databases::pollProgress',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  185 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/google/callback',
    'func' => 'controller\\oauth2\\google\\auth::callback',
    'access' => 
    array (
      0 => 'guest',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  186 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/server/update/gameserver',
    'func' => 'controller\\admin\\databases::updateGameserver',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  187 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/bonuscode/delete_all',
    'func' => 'controller\\admin\\bonuscode::delete_all',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  189 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/bonuscode/delete_general_all_servers',
    'func' => 'controller\\admin\\bonuscode::delete_general_all_servers',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  190 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/shop/startpack/update',
    'func' => 'controller\\admin\\startpack::update',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  191 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/server/func/(\\d+)',
    'func' => 'controller\\admin\\options::getServerFunction',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  192 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/server/func/warehouse/clear',
    'func' => 'controller\\admin\\options::removeItemsWarehouse',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  193 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/server/func/warehouse/list',
    'func' => 'controller\\admin\\options::getAllItemsInWarehouse',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  194 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/user/delete/item/warehouse',
    'func' => 'controller\\admin\\users::deleteItemUserToWarehouse',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  195 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/inventory/warehouse/split-item',
    'func' => 'controller\\account\\characters\\inventory::splitItem',
    'access' => 
    array (
      0 => 'admin',
      1 => 'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  196 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/func/destack',
    'func' => 'controller\\admin\\options::saveStackItems',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  197 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/inventory/warehouse/stack',
    'func' => 'controller\\admin\\options::stackInventoryItems',
    'access' => 
    array (
      0 => 'admin',
      1 => 'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  198 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/player/account/delete',
    'func' => 'controller\\account\\characters\\account::delete',
    'access' => 
    array (
      0 => 'user',
      1 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  199 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/setting/registration/bonus/save',
    'func' => 'controller\\admin\\options::saveRegistrationBonusItems',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  200 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/telegram/notice/get/thread',
    'func' => 'controller\\admin\\telegram::testGetThread',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  201 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/shop/update/category',
    'func' => 'controller\\admin\\donate::change_category_item',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  202 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/telegram/notice/create/topics',
    'func' => 'controller\\admin\\telegram::createNoticeTopics',
    'access' => 
    array (
      0 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  203 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/finger/check',
    'func' => 'component\\finger\\finger::fingerController',
    'access' => 
    array (
      0 => 'user',
      1 => 'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
);
