<?php

return array (
  0 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/main',
    'func' => 'controller\main\main::index',
    'access' => 
    array (
      'any',
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
    'func' => 'controller\config\config::setLang',
    'access' => 
    array (
      'any',
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
    'func' => 'controller\admin\options::new_server',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  3 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/options/server/client/protocol',
    'func' => 'component\chronicle\client::get_base_collection_class',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  4 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/statistic',
    'func' => NULL,
    'access' => 
    array (
      'any',
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
    'func' => 'controller\donate\pay::shop',
    'access' => 
    array (
      'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  6 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/route/add',
    'func' => 'controller\route\route::add',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  7 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/route/get/file',
    'func' => 'controller\route\route::getDirFiles',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  8 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/route/update/enable',
    'func' => 'controller\route\route::update_enable',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  9 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/ticket',
    'func' => 'controller\ticket\ticket::all',
    'access' => 
    array (
      'user',
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Tickets list',
  ),
  10 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/ticket/(\d+)',
    'func' => 'controller\ticket\ticket::ticketAdmin',
    'access' => 
    array (
      'admin',
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
    'func' => 'controller\ticket\ticket::message',
    'access' => 
    array (
      'admin',
      'user',
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
    'func' => 'controller\route\route::edit',
    'access' => 
    array (
      'admin',
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
      'admin',
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
    'func' => 'model\admin\donate::add_item',
    'access' => 
    array (
      'admin',
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
    'func' => 'model\admin\donate::remove_item',
    'access' => 
    array (
      'admin',
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
    'func' => 'controller\donate\shop::getShopObjectJSON',
    'access' => 
    array (
      'user',
      'admin',
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
    'func' => 'controller\donate\pay::buyShopItem',
    'access' => 
    array (
      'user',
      'admin',
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
    'func' => 'model\donate\donate::toWarehouse',
    'access' => 
    array (
      'admin',
      'user',
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
    'func' => 'controller\user\profile\change::show_avatar_page',
    'access' => 
    array (
      'admin',
      'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Set user avatar',
  ),
  20 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/user/auth-log',
    'func' => 'controller\\user\\auth_log::show',
    'access' => 
    array (
      'admin',
      'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'User authentication login history',
  ),
  21 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/forum',
    'func' => 'model\forum\forum::saveConfig',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Save forum settings',
  ),
  22 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/config/save',
    'func' => 'model\config\config::save',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Save Sphere config',
  ),
  
  25 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/admin/sphere/update',
    'func' => NULL,
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '/admin/selfupdate.html',
    'comment' => '',
  ),
  26 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/route',
    'func' => 'controller\route\route::all',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  27 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/news',
    'func' => NULL,
    'access' => 
    array (
      'any',
    ),
    'weight' => 0,
    'page' => 'pages.html',
    'comment' => '',
  ),
  28 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/install',
    'func' => 'controller\install\install::rules',
    'access' => 
    array (
      'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  29 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/balance',
    'func' => 'controller\donate\pay::pay',
    'access' => 
    array (
      'user',
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  30 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/setting',
    'func' => 'controller\admin\options::server_show',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  31 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/admin/pages',
    'func' => NULL,
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => 'admin/pages.html',
    'comment' => '',
  ),
  32 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/admin/pages/edit/(\d+)',
    'func' => NULL,
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '/admin/page_edit.html',
    'comment' => '',
  ),
  33 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/admin/pages/create',
    'func' => NULL,
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '/admin/page_create.html',
    'comment' => '',
  ),
  34 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/page/(\d+)',
    'func' => NULL,
    'access' => 
    array (
      'any',
    ),
    'weight' => 0,
    'page' => '/page.html',
    'comment' => '',
  ),
  35 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/user/change/avatar',
    'func' => 'controller\user\profile\change::save_avatar',
    'access' => 
    array (
      'admin',
      'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  36 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/admin/users',
    'func' => 'controller\admin\users::showAll',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  37 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/',
    'func' => 'controller\promo\promo::index',
    'access' => 
    array (
      'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  38 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/auth/logout',
    'func' => 'controller\user\auth\auth::logout',
    'access' => 
    array (
      'admin',
      'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  39 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/auth',
    'func' => 'controller\user\auth\auth::index',
    'access' => 
    array (
      'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  40 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/captcha',
    'func' => 'component\captcha\captcha::defence',
    'access' => 
    array (
      'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  41 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/registration/account',
    'func' => 'controller\registration\account::requestNewAccount',
    'access' => 
    array (
      'admin',
      'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  42 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/send/to/player',
    'func' => 'controller\account\characters\inventory::sendToGame',
    'access' => 
    array (
      'admin',
      'user',
    ),
    'weight' => 0,
    'page' => 'sendtogame.html',
    'comment' => '',
  ),
  43 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/player/account/change/password',
    'func' => 'controller\account\password\change::password',
    'access' => 
    array (
      'admin',
      'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Change player password',
  ),
  44 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/option/server/db/connect/select/name',
    'func' => 'controller\admin\options::test_connect_db_selected_name',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Check MySQL Connect',
  ),
  45 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/create/server/new',
    'func' => 'controller\admin\options::create_server',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Add new server',
  ),
  46 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/user/change/server',
    'func' => 'controller\user\default_server::change',
    'access' => 
    array (
      'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Change server',
  ),
  47 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/admin/shop/startpack',
    'func' => 'controller\admin\startpack::index',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '/admin/startpack.html',
    'comment' => '',
  ),
  48 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/client/item/info',
    'func' => 'component\image\client_icon::get_item_info_json',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Item data info',
  ),
  49 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/shop/startpack',
    'func' => 'controller\admin\startpack::add',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  50 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/startpack/purchase',
    'func' => 'controller\admin\startpack::purchase',
    'access' => 
    array (
      'user',
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  51 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/startpack/purchase/warehouse',
    'func' => 'controller\admin\startpack::purchaseWarehouse',
    'access' => 
    array (
      'admin',
      'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  52 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/setting/donate/save',
    'func' => 'controller\admin\options::saveConfigDonate',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  53 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/setting/donate/migrate-to-global',
    'func' => 'controller\admin\options::migrateDonateConfigToGlobal',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  54 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/setting/referral/save',
    'func' => 'controller\admin\options::saveConfigReferral',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  55 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/admin/server/list',
    'func' => 'controller\admin\options::servers_show',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  56 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/admin/server/edit/(\d+)',
    'func' => 'controller\admin\options::server_edit',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  57 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/server/edit/save/general',
    'func' => 'controller\admin\options::saveGeneral',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  58 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/server/edit/save/other',
    'func' => 'controller\admin\options::saveOther',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  59 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/server/edit/save/mysql',
    'func' => 'controller\admin\options::saveMySQL',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  60 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/player/relocation',
    'func' => 'controller\account\characters\relocation::playerMove',
    'access' => 
    array (
      'admin',
      'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Return player in XYZ',
  ),
  61 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/users/sendToBalance',
    'func' => 'controller\admin\donate::add_bonus_money',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  62 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/account/synchronization',
    'func' => 'controller\account\comparison\comparison::synchronization',
    'access' => 
    array (
      'admin',
      'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  63 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/donate/pay',
    'func' => 'controller\donate\pay::pay',
    'access' => 
    array (
      'admin',
      'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  64 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/setting/logo/save',
    'func' => 'controller\logo\logo::logo',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Set logo Sphere',
  ),
  65 => 
  array (
    'enable' => false,
    'method' => 'GET',
    'pattern' => '/referral',
    'func' => 'controller\referral\referral::show',
    'access' => 
    array (
      'user',
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  66 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/inventory/send',
    'func' => 'controller\account\characters\inventory::warehouseToGame',
    'access' => 
    array (
      'user',
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Send items to player',
  ),
  67 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/user/change',
    'func' => 'controller\user\profile\change::save',
    'access' => 
    array (
      'admin',
      'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  68 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/setting/favicon/save',
    'func' => 'controller\logo\logo::favicon',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  69 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/referral/bonus',
    'func' => 'controller\referral\referral::bonus',
    'access' => 
    array (
      'user',
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Получение бонусов за рефералов',
  ),
  70 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/player/reset/hwid',
    'func' => 'controller\account\characters\hwid::reset',
    'access' => 
    array (
      'user',
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Reset HWID',
  ),
  71 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/registration/account/prefix',
    'func' => 'component\account\generation::createPrefix',
    'access' => 
    array (
      'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Create Prefix',
  ),
  72 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/server/edit/collection',
    'func' => 'controller\admin\options::updateCollection',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  73 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/setting/email/send/test',
    'func' => 'component\mail\mail::sendTest',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Test send email',
  ),
  74 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/admin/mailing',
    'func' => NULL,
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '/admin/mailing.html',
    'comment' => '',
  ),
  75 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/logs',
    'func' => '',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '/admin/logs.html',
    'comment' => 'All logs',
  ),
  76 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/logs/update',
    'func' => 'model\log\log::getNewLogs',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  77 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/server/statistic/(\d+)',
    'func' => 'controller\admin\statistic::getStatistic',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Server Statistic',
  ),
  78 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/bonuscode/create',
    'func' => 'controller\admin\bonuscode::create_pack',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  79 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/bonuscode/create',
    'func' => 'controller\admin\bonuscode::genereate',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  80 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/bonus/code',
    'func' => 'controller\account\bonus\bonus::receiving',
    'access' => 
    array (
      'user',
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Получение предмета за бонус код',
  ),
  81 => 
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
  82 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/registration/user',
    'func' => 'controller\registration\user::add',
    'access' => 
    array (
      0 => 'guest',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  83 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/auth',
    'func' => 'controller\user\auth\auth::auth_request',
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
    'pattern' => '/auth/fingerprint',
    'func' => 'controller\user\auth\auth::fingerprintAuth',
    'access' => 
    array (
      0 => 'guest',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Fingerprint / Windows Hello login',
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
    'func' => 'controller\user\forget\forget::create',
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
    'func' => 'controller\user\forget\forget::validate',
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
    'func' => 'controller\admin\bonuscode::show_code',
    'access' => 
    array (
      'admin',
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
    'func' => 'model\github\update::checkNewCommit',
    'access' => 
    array (
      'admin',
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
    'func' => 'controller\admin\options::delete_server',
    'access' => 
    array (
      'admin',
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
    'func' => 'controller\admin\index::index',
    'access' => 
    array (
      'admin',
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
    'func' => 'controller\admin\plugin::show',
    'access' => 
    array (
      'admin',
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
    'func' => 'model\github\update::getUpdateProgress',
    'access' => 
    array (
      'admin',
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
    'func' => 'controller\admin\page::create_news',
    'access' => 
    array (
      'admin',
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
    'func' => 'controller\admin\page::update_news',
    'access' => 
    array (
      'admin',
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
    'func' => 'controller\admin\page::trash_send',
    'access' => 
    array (
      'admin',
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
    'func' => 'controller\admin\users::getUserInfo',
    'access' => 
    array (
      'admin',
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
    'func' => 'controller\admin\users::edit',
    'access' => 
    array (
      'admin',
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
    'func' => 'controller\sphereapi\sphereapi::index',
    'access' => 
    array (
      'admin',
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
    'func' => 'controller\sphereapi\sphereapi::save',
    'access' => 
    array (
      'admin',
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
    'func' => 'controller\sphereapi\sphereapi::check',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  105 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/github/update/auto',
    'func' => 'model\github\update::autoRemoteUpdate',
    'access' => 
    array (
      'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Тестируемая функция автоматического старта обновлений',
  ),
  106 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/notification/get',
    'func' => 'model\notification\notification::get_new_notification',
    'access' => 
    array (
      'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  107 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/user/add/item/warehouse',
    'func' => 'controller\admin\users::addItemUserToWarehouse',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Выдача пользователю предмета в warehouse',
  ),
  108 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/stream',
    'func' => 'controller\stream\stream::show',
    'access' => 
    array (
      'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Страница со списками стримов',
  ),
  109 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/stream/add',
    'func' => 'controller\stream\stream::add',
    'access' => 
    array (
      'user',
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Add new stream',
  ),
  110 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/stream',
    'func' => 'controller\admin\stream::show',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  111 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/stream/satisfy',
    'func' => 'controller\admin\stream::satisfy',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  112 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/stream/(.*)',
    'func' => 'controller\stream\stream::getUserStream',
    'access' => 
    array (
      'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  113 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/stream/delete',
    'func' => 'controller\stream\stream::userDeleteStream',
    'access' => 
    array (
      'user',
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Удаление записи о стриме пользователем',
  ),
  114 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/stream/set/auto/check',
    'func' => 'controller\admin\stream::setAutoCheck',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  115 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/response/request',
    'func' => 'component\cron\arrival::receiving',
    'access' => 
    array (
      'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Приемщик автозапросов, на случай если нужно выполнять какие-то действий.',
  ),
  116 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/stream/remove/auto/check',
    'func' => 'controller\admin\stream::removeAutoCheck',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Удаление автообновления статуса трансляций из канала пользователя',
  ),
  117 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/stream/remove',
    'func' => 'controller\admin\stream::removeStream',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Полное удаление информации о стриме',
  ),
  118 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/setting/background/save',
    'func' => 'controller\save\background\background::save',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  119 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/bonuscode/delete',
    'func' => 'controller\admin\bonuscode::delete',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  120 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/server/reconnect',
    'func' => 'controller\admin\server::server_reconnect',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  121 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/balance',
    'func' => 'controller\admin\swbalance::get',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  122 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/shop/startpack/remove/pack',
    'func' => 'controller\admin\startpack::removePack',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  123 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/phrases/custom',
    'func' => '',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '/admin/phrases_custom.html',
    'comment' => '',
  ),
  124 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/phrases/custom',
    'func' => 'model\phrases\phrases::saveCustom',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  125 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/balance/pay',
    'func' => 'controller\admin\swbalance::pay',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  126 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/balance/pay/invoice',
    'func' => 'controller\admin\swbalance::payInvoice',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  127 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/balance/pay/history',
    'func' => 'controller\admin\swbalance::historyPay',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  128 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/server/remove/loginserver',
    'func' => 'controller\admin\options::removeLoginserver',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  129 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/template/readme',
    'func' => 'controller\admin\options::getTemplateInfo',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  130 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/api/user/global/add/email/check',
    'func' => 'component\sphere\superuser::create',
    'access' => 
    array (
      'admin',
      'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  131 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/balance/faq',
    'func' => '',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '/admin/balance_faq.html',
    'comment' => '',
  ),
  132 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/plugin/save/activator',
    'func' => 'model\plugin\plugin::__save_activator_plugin',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  133 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/plugin/save/config',
    'func' => 'model\plugin\plugin::saveSetting',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  134 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/balance/pay/renewlicense',
    'func' => 'controller\admin\swbalance::renewLicense',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  135 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/db/add/new/connect',
    'func' => 'controller\admin\options::add_new_mysql_connect_to_server',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  136 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/create/server/edit',
    'func' => 'controller\admin\options::server_edit_save',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  137 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/server/db',
    'func' => 'controller\admin\databases::show',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  138 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/stream/delete/test',
    'func' => 'controller\admin\stream::test',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  139 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/database/account/download',
    'func' => 'controller\admin\databases::downloadAccounts',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  140 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/database/account/delete',
    'func' => 'controller\admin\databases::deleteImportFile',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  141 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/server/errors/(\d+)',
    'func' => 'controller\admin\errors::getErrors',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  142 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/server/errors/clear',
    'func' => 'controller\admin\errors::clear',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  143 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/database/delete',
    'func' => 'controller\admin\databases::delete',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  144 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/server/change/position',
    'func' => 'controller\admin\options::changePositionServer',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  145 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/server/set/donate/(\d+)',
    'func' => 'controller\admin\setDonateServer::show',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  146 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/server/set/default',
    'func' => 'controller\admin\options::setDefaultServer',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  147 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/server/statistic/donate',
    'func' => 'controller\admin\statistic::getDonate',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  148 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/server/db/quality',
    'func' => 'controller\admin\databases::connectionQualityCheck',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  149 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/server/port/quality',
    'func' => 'controller\admin\databases::portQualityCheck',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  150 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/database/account/load',
    'func' => 'controller\admin\databases::loadAccounts',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  151 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/server/set/referral/(\d+)',
    'func' => 'controller\admin\referral::showOption',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  152 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/support',
    'func' => 'controller\support\support::show',
    'access' => 
    array (
      'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  153 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/support/read/(\d+)',
    'func' => 'controller\support\support::read',
    'access' => 
    array (
      'admin',
      'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  154 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/support/new',
    'func' => 'controller\support\support::create',
    'access' => 
    array (
      'admin',
      'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  155 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/support/create/request',
    'func' => 'controller\support\support::requestCreate',
    'access' => 
    array (
      'admin',
      'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  156 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/support/reply/request',
    'func' => 'controller\support\support::requestReply',
    'access' => 
    array (
      'user',
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  157 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/support/thread/(\d+)',
    'func' => 'controller\support\support::showThread',
    'access' => 
    array (
      'admin',
      'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  158 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/support/admin/add/section',
    'func' => 'controller\support\support::addSection',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  159 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/support/admin/delete/section',
    'func' => 'controller\support\support::deleteSection',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  160 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/support/admin/delete/topic',
    'func' => 'controller\support\support::deleteTopic',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  161 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/support/admin/update/moderator',
    'func' => 'controller\support\support::updateModeratorsPrivilege',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  162 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/support/admin/topic/close',
    'func' => 'controller\support\support::closeTopic',
    'access' => 
    array (
      'admin',
      'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  163 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/support/load/file',
    'func' => 'controller\support\support::fileLoad',
    'access' => 
    array (
      'admin',
      'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  164 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/support/admin/delete/post',
    'func' => 'controller\support\support::deletePost',
    'access' => 
    array (
      'admin',
      'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  165 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/support/admin/move',
    'func' => 'controller\support\support::toMove',
    'access' => 
    array (
      'admin',
      'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  166 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/telegram/notice/test',
    'func' => 'controller\admin\telegram::testSendNotice',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  167 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/server/update/loginserver',
    'func' => 'controller\admin\databases::updateLoginserver',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  168 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/server/setting/get',
    'func' => 'controller\admin\setDonateServer::getDonateSetting',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  169 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/server/set/enabled',
    'func' => 'controller\admin\enabled::setEnabled',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  170 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/balance/pay/pack',
    'func' => 'controller\admin\swbalance::buyPack',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  171 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/database/account/load/progress',
    'func' => 'controller\admin\databases::pollProgress',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  172 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/google/callback',
    'func' => 'controller\oauth2\google\auth::callback',
    'access' => 
    array (
      0 => 'guest',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  173 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/server/update/gameserver',
    'func' => 'controller\admin\databases::updateGameserver',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  174 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/bonuscode/delete_all',
    'func' => 'controller\admin\bonuscode::delete_all',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  175 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/bonuscode/delete_general_all_servers',
    'func' => 'controller\admin\bonuscode::delete_general_all_servers',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  176 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/shop/startpack/update',
    'func' => 'controller\admin\startpack::update',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  177 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/server/func/(\d+)',
    'func' => 'controller\admin\options::getServerFunction',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  178 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/server/func/warehouse/clear',
    'func' => 'controller\admin\options::removeItemsWarehouse',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  179 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/server/func/warehouse/list',
    'func' => 'controller\admin\options::getAllItemsInWarehouse',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  180 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/user/delete/item/warehouse',
    'func' => 'controller\admin\users::deleteItemUserToWarehouse',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  181 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/inventory/warehouse/split-item',
    'func' => 'controller\account\characters\inventory::splitItem',
    'access' => 
    array (
      'admin',
      'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  182 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/func/destack',
    'func' => 'controller\admin\options::saveStackItems',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  183 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/inventory/warehouse/stack',
    'func' => 'controller\admin\options::stackInventoryItems',
    'access' => 
    array (
      'admin',
      'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  184 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/player/account/delete',
    'func' => 'controller\account\characters\account::delete',
    'access' => 
    array (
      'user',
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  185 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/setting/registration/bonus/save',
    'func' => 'controller\admin\options::saveRegistrationBonusItems',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  186 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/server/items-send-time/save',
    'func' => 'controller\admin\options::saveItemsSendTime',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  187 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/telegram/notice/get/thread',
    'func' => 'controller\admin\telegram::testGetThread',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  188 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/shop/update/category',
    'func' => 'controller\admin\donate::change_category_item',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  189 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/telegram/notice/create/topics',
    'func' => 'controller\admin\telegram::createNoticeTopics',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  190 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/support/message/get/last',
    'func' => 'controller\support\support::getRefreshMessage',
    'access' => 
    array (
      'user',
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  191 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/users/data',
    'func' => 'controller\admin\users::data',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Users DataTables server-side data',
  ),
  192 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/users/search-lite',
    'func' => 'controller\admin\users::searchLite',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Lightweight user search for selectors',
  ),
  193 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/statistic/donate/data',
    'func' => 'controller\admin\statistic::donateData',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Donate DataTables server-side data',
  ),
  194 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/admin/users/page/(\d+)',
    'func' => 'controller\admin\users::showAll',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Users list pagination',
  ),
  195 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/users/search/email',
    'func' => 'controller\admin\users::searchByEmail',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Ajax search users by email',
  ),
  196 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/auth/telegram/([a-zA-Z0-9]+)',
    'func' => 'controller\oauth2\telegram\telegram::auth',
    'access' => 
    array (
      0 => 'guest',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Telegram auth by token',
  ),
  197 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/support/admin/create/dialog',
    'func' => 'controller\support\support::createDialogByAdmin',
    'access' => 
    array (
      'admin',
      'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Create support dialog by admin with user',
  ),
  198 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/support/admin/mass/send',
    'func' => 'controller\support\support::massSendMessages',
    'access' => 
    array (
      'admin',
      'user',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Mass send messages to selected users',
  ),
  199 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/domain/change',
    'func' => 'controller\sphereapi\sphereapi::changeDomain',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Change domain in SphereAPI',
  ),
  200 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/auth/2fa',
    'func' => 'controller\user\auth\twofaController::showVerifyPage',
    'access' => 
    array (
      'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '2FA verification page',
  ),
  201 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/auth/2fa/verify',
    'func' => 'controller\user\auth\twofaController::verify',
    'access' => 
    array (
      'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '2FA code verification',
  ),
  202 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/api/2fa/setup',
    'func' => 'controller\user\auth\twofaController::setup',
    'access' => 
    array (
      'user',
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '2FA setup - generate secret and QR code',
  ),
  203 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/api/2fa/enable',
    'func' => 'controller\user\auth\twofaController::enable',
    'access' => 
    array (
      'user',
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '2FA enable',
  ),
  204 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/api/2fa/disable',
    'func' => 'controller\user\auth\twofaController::disable',
    'access' => 
    array (
      'user',
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '2FA disable',
  ),
  205 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/login',
    'func' => 'controller\user\auth\auth::returnToMain',
    'access' => 
    array (
      'user',
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  206 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/admin/users/(donate|donate_asc|name|name_asc|name_desc|email|email_asc|email_desc|date|date_asc|date_desc|activity|activity_asc|activity_desc|id|id_asc|id_desc)',
    'func' => 'controller\admin\users::showAll',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Users list sorted by parameter',
  ),
  207 => 
  array (
    'enable' => 1,
    'method' => 'GET',
    'pattern' => '/admin/users/(donate|donate_asc|name|name_asc|name_desc|email|email_asc|email_desc|date|date_asc|date_desc|activity|activity_asc|activity_desc|id|id_asc|id_desc)/page/(\d+)',
    'func' => 'controller\admin\users::showAll',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Users list sorted by parameter with pagination',
  ),
  208 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/route/custom',
    'func' => 'controller\route\custom_route::all',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Custom routes manager',
  ),
  209 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/route/custom/add',
    'func' => 'controller\route\custom_route::add',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Add custom route',
  ),
  210 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/route/custom/get/file',
    'func' => 'controller\route\custom_route::getDirFiles',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Get files for custom route',
  ),
  211 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/route/custom/update/enable',
    'func' => 'controller\route\custom_route::update_enable',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Toggle custom route enable/disable',
  ),
  212 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/route/custom/edit',
    'func' => 'controller\route\custom_route::edit',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 1,
    'page' => '',
    'comment' => 'Edit custom route data',
  ),
  213 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/route/custom/delete',
    'func' => 'controller\route\custom_route::delete',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Delete custom route',
  ),
  214 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/route/custom/check/pattern',
    'func' => 'controller\route\custom_route::checkPatternExists',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Check if pattern already exists',
  ),
  215 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/shop/startpack/save',
    'func' => 'controller\admin\startpack::save_settings',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  216 => 
  array (
    'enable' => 1,
    'method' => 'POST',
    'pattern' => '/admin/statistic/donate/clear',
    'func' => 'controller\admin\statistic::donateClear',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Admin clear donate by date range',
  ),
  217 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/statistic/remote',
    'func' => 'controller\statistic\statistic::show_json_stats',
    'access' => 
    array (
      'any',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => '',
  ),
  218 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/donate',
    'func' => 'controller\admin\donateGlobal::showPlugins',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Donate plugins list',
  ),
  219 => 
  array (
    'enable' => true,
    'method' => 'GET',
    'pattern' => '/admin/donate/bonus',
    'func' => 'controller\admin\donateGlobal::show',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Global donate bonus settings page',
  ),
  220 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/donate/bonus/save',
    'func' => 'controller\admin\donateGlobal::save',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Save global donate bonus settings',
  ),
  221 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/donate/bonus/get',
    'func' => 'controller\admin\donateGlobal::getDonateSetting',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Get global donate bonus settings',
  ),
  222 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/donate/bonus/copy',
    'func' => 'controller\admin\donateGlobal::copySettingsFromServer',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Copy donate bonus settings from one server to another',
  ),
  223 => 
  array (
    'enable' => true,
    'method' => 'POST',
    'pattern' => '/admin/donate/plugins/sort',
    'func' => 'controller\admin\donateGlobal::savePaysystemSort',
    'access' => 
    array (
      'admin',
    ),
    'weight' => 0,
    'page' => '',
    'comment' => 'Save sort order for paysystem plugins',
  ),
);
