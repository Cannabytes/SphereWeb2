<?php

return [
  0  =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/main',
      'func'    => 'controller\\main\\main::index',
      'access'  =>
        [
          0 => 'any',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => 'Start page!!',
    ],
  1  =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/user/change/lang/{lang}',
      'func'    => 'controller\\config\\config::setLang',
      'access'  =>
        [
          0 => 'any',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => 'Change lang',
    ],
  2  =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/admin/server/add/new',
      'func'    => 'controller\\admin\\options::new_server',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => null,
      'comment' => null,
    ],
  3  =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/admin/options/server/client/protocol',
      'func'    => 'component\\chronicle\\client::get_base_collection_class',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => null,
      'comment' => null,
    ],
  4  =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/statistic',
      'func'    => null,
      'access'  =>
        [
          0 => 'any',
        ],
      'weight'  => 0,
      'page'    => 'statistic.html',
      'comment' => 'Statistic page',
    ],
  5  =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/shop',
      'func'    => 'controller\\donate\\pay::shop',
      'access'  =>
        [
          0 => 'any',
        ],
      'weight'  => 0,
      'page'    => null,
      'comment' => null,
    ],
  6  =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/admin/route/add',
      'func'    => 'controller\\route\\route::add',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => null,
      'comment' => null,
    ],
  7  =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/admin/route/get/file',
      'func'    => 'controller\\route\\route::getDirFiles',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => null,
      'comment' => null,
    ],
  8  =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/admin/route/update/enable',
      'func'    => 'controller\\route\\route::update_enable',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => null,
      'comment' => null,
    ],
  9  =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/ticket',
      'func'    => 'controller\\ticket\\ticket::all',
      'access'  =>
        [
          0 => 'user',
          1 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => 'Tickets list',
    ],
  10 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/ticket/(\\d+)',
      'func'    => 'controller\\ticket\\ticket::get',
      'access'  =>
        [
          0 => 'admin',
          1 => 'user',
        ],
      'weight'  => 0,
      'page'    => null,
      'comment' => 'Ticket read',
    ],
  11 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/ticket/message',
      'func'    => 'controller\\ticket\\ticket::message',
      'access'  =>
        [
          0 => 'admin',
          1 => 'user',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => 'Ticket send message',
    ],
  12 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/admin/route/edit',
      'func'    => 'controller\\route\\route::edit',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 1,
      'page'    => '',
      'comment' => 'Update route data',
    ],
  13 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/admin/shop',
      'func'    => null,
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => 'admin/shop_add.html',
      'comment' => 'Shop manager',
    ],
  14 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/admin/shop',
      'func'    => 'model\\admin\\donate::add_item',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => 'admin/shop_add.html',
      'comment' => '',
    ],
  15 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/admin/shop/remove/item',
      'func'    => 'model\\admin\\donate::remove_item',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  16 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/admin/shop/get',
      'func'    => 'controller\\donate\\shop::getShopObjectJSON',
      'access'  =>
        [
          0 => 'user',
          1 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => 'Получить информацию о продаваемых предметах',
    ],
  17 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/shop/purchase',
      'func'    => 'controller\\donate\\pay::buyShopItem',
      'access'  =>
        [
          0 => 'user',
          1 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => 'Purchase of goods',
    ],
  18 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/shop/towarehouse',
      'func'    => 'model\\donate\\donate::toWarehouse',
      'access'  =>
        [
          0 => 'admin',
          1 => 'user',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => 'Shop items to warehouse',
    ],
  19 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/user/avatar',
      'func'    => 'controller\\user\\profile\\change::show_avatar_page',
      'access'  =>
        [
          0 => 'admin',
          1 => 'user',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => 'Set user avatar',
    ],
  20 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/admin/forum',
      'func'    => null,
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => 'admin/forum.html',
      'comment' => '',
    ],
  21 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/admin/forum',
      'func'    => 'model\\forum\\forum::saveConfig',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => 'Save forum settings',
    ],
  22 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/admin/phrases',
      'func'    => null,
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '/admin/phrases.html',
      'comment' => 'Set multilang phrases',
    ],
  23 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/admin/phrases',
      'func'    => 'model\\phrases\\phrases::save',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  24 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/admin/function/save',
      'func'    => 'model\\enabled\\enabled::save',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => 'Save status function',
    ],
  25 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/admin/config/save',
      'func'    => 'model\\config\\config::save',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => 'Save Sphere config',
    ],
  26 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/admin/sphere/update',
      'func'    => null,
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '/admin/selfupdate.html',
      'comment' => '',
    ],
  27 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/admin/route',
      'func'    => 'controller\\route\\route::all',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  28 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/news',
      'func'    => null,
      'access'  =>
        [
          0 => 'any',
        ],
      'weight'  => 0,
      'page'    => 'pages.html',
      'comment' => '',
    ],
  29 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/install',
      'func'    => 'controller\\install\\install::rules',
      'access'  =>
        [
          0 => 'any',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  30 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/balance',
      'func'    => 'controller\\donate\\pay::pay',
      'access'  =>
        [
          0 => 'user',
          1 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  31 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/admin/setting',
      'func'    => 'controller\\admin\\options::server_show',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  32 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/admin/pages',
      'func'    => null,
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => 'admin/pages.html',
      'comment' => '',
    ],
  33 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/admin/pages/edit/(\\d+)',
      'func'    => null,
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '/admin/page_edit.html',
      'comment' => '',
    ],
  34 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/admin/pages/create',
      'func'    => null,
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '/admin/page_create.html',
      'comment' => '',
    ],
  35 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/page/(\\d+)',
      'func'    => null,
      'access'  =>
        [
          0 => 'any',
        ],
      'weight'  => 0,
      'page'    => '/page.html',
      'comment' => '',
    ],
  36 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/user/change/avatar',
      'func'    => 'controller\\user\\profile\\change::save_avatar',
      'access'  =>
        [
          0 => 'admin',
          1 => 'user',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  37 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/admin/users',
      'func'    => null,
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '/admin/users.html',
      'comment' => '',
    ],
  38 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/',
      'func'    => 'controller\\main\\main::index',
      'access'  =>
        [
          0 => 'any',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  39 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/auth/logout',
      'func'    => 'controller\\user\\auth\\auth::logout',
      'access'  =>
        [
          0 => 'admin',
          1 => 'user',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  40 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/auth',
      'func'    => 'controller\\user\\auth\\auth::index',
      'access'  =>
        [
          0 => 'any',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  41 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/captcha',
      'func'    => 'component\\captcha\\captcha::defence',
      'access'  =>
        [
          0 => 'any',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  43 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/registration/account',
      'func'    => 'controller\\registration\\account::requestNewAccount',
      'access'  =>
        [
          0 => 'admin',
          1 => 'user',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  46 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/send/to/player',
      'func'    => 'controller\\account\\characters\\inventory::sendToGame',
      'access'  =>
        [
          0 => 'admin',
          1 => 'user',
        ],
      'weight'  => 0,
      'page'    => 'sendtogame.html',
      'comment' => '',
    ],
  47 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/player/account/reload',
      'func'    => 'controller\\account\\characters\\reload::reload',
      'access'  =>
        [
          0 => 'admin',
          1 => 'user',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  48 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/player/account/change/password',
      'func'    => 'controller\\account\\password\\change::password',
      'access'  =>
        [
          0 => 'admin',
          1 => 'user',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => 'Change player password',
    ],
  49 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/admin/option/server/db/connect/select/name',
      'func'    => 'controller\\admin\\options::test_connect_db_selected_name',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => 'Check MySQL Connect',
    ],
  50 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/admin/create/server/new',
      'func'    => 'controller\\admin\\options::create_server',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => 'Add new server',
    ],
  51 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/user/change/server',
      'func'    => 'controller\\user\\default_server::change',
      'access'  =>
        [
          0 => 'any',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => 'Change server',
    ],
  52 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/admin/shop/startpack',
      'func'    => 'controller\\admin\\startpack::index',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '/admin/startpack.html',
      'comment' => '',
    ],
  53 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/admin/client/item/info',
      'func'    => 'component\\image\\client_icon::get_item_info_json',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => 'Item data info',
    ],
  54 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/admin/shop/startpack',
      'func'    => 'controller\\admin\\startpack::add',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  55 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/startpack/purchase',
      'func'    => 'controller\\admin\\startpack::purchase',
      'access'  =>
        [
          0 => 'user',
          1 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  56 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/startpack/purchase/warehouse',
      'func'    => 'controller\\admin\\startpack::purchaseWarehouse',
      'access'  =>
        [
          0 => 'admin',
          1 => 'guest',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  57 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/admin/setting/donate/save',
      'func'    => 'controller\\admin\\options::saveConfigDonate',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  58 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/admin/setting/referral/save',
      'func'    => 'controller\\admin\\options::saveConfigReferral',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  59 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/admin/server/list',
      'func'    => 'controller\\admin\\options::edit_server_show',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  60 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/admin/server/edit/(\\d+)',
      'func'    => 'controller\\admin\\options::edit_server_show',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  61 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/admin/server/edit/save/general',
      'func'    => 'controller\\admin\\options::saveGeneral',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  62 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/admin/server/edit/save/other',
      'func'    => 'controller\\admin\\options::saveOther',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  63 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/admin/server/edit/save/mysql',
      'func'    => 'controller\\admin\\options::saveMySQL',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  64 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/player/relocation',
      'func'    => 'controller\\account\\characters\\relocation::playerMove',
      'access'  =>
        [
          0 => 'admin',
          1 => 'user',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => 'Return player in XYZ',
    ],
  65 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/admin/users/sendToBalance',
      'func'    => 'controller\\admin\\donate::add_bonus_money',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  66 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/account/synchronization',
      'func'    => 'controller\\account\\comparison\\comparison::synchronization',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  67 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/donate/pay',
      'func'    => 'controller\\donate\\pay::pay',
      'access'  =>
        [
          0 => 'admin',
          1 => 'user',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  68 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/admin/setting/logo/save',
      'func'    => 'controller\\logo\\logo::logo',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => 'Set logo Sphere',
    ],
  42 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/referral',
      'func'    => 'controller\\referral\\referral::show',
      'access'  =>
        [
          0 => 'user',
          1 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  44 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/inventory/send',
      'func'    => 'controller\\account\\characters\\inventory::warehouseToGame',
      'access'  =>
        [
          0 => 'user',
          1 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => 'Send items to player',
    ],
  45 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/user/change',
      'func'    => 'controller\\user\\profile\\change::save',
      'access'  =>
        [
          0 => 'admin',
          1 => 'user',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  69 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/admin/setting/favicon/save',
      'func'    => 'controller\\logo\\logo::favicon',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  70 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/referral/bonus',
      'func'    => 'controller\\referral\\referral::bonus',
      'access'  =>
        [
          0 => 'user',
          1 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => 'Получение бонусов за рефералов',
    ],
  71 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/player/reset/hwid',
      'func'    => 'controller\\account\\characters\\hwid::reset',
      'access'  =>
        [
          0 => 'user',
          1 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => 'Reset HWID',
    ],
  72 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/registration/account/prefix',
      'func'    => 'component\\account\\generation::createPrefix',
      'access'  =>
        [
          0 => 'any',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => 'Create Prefix',
    ],
  73 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/admin/server/edit/collection',
      'func'    => 'controller\\admin\\options::updateCollection',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  74 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/admin/setting/email/send/test',
      'func'    => 'component\\mail\\mail::sendTest',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => 'Test send email',
    ],
  75 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/admin/mailing',
      'func'    => null,
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '/admin/mailing.html',
      'comment' => '',
    ],
  76 =>
    [
      'enable'  => true,
      'method'  => 'GET',
      'pattern' => '/admin/logs',
      'func'    => '',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '/admin/logs.html',
      'comment' => 'All logs',
    ],
  77 =>
    [
      'enable'  => true,
      'method'  => 'POST',
      'pattern' => '/admin/logs/update',
      'func'    => 'model\\log\\log::getNewLogs',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  78 =>
    [
      'enable'  => true,
      'method'  => 'GET',
      'pattern' => '/admin/server/statistic/(\\d+)',
      'func'    => 'controller\\admin\\statistic::getStatistic',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => 'Server Statistic',
    ],
  79 =>
    [
      'enable'  => true,
      'method'  => 'GET',
      'pattern' => '/admin/bonuscode/create',
      'func'    => 'controller\\admin\\bonuscode::create_pack',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  80 =>
    [
      'enable'  => true,
      'method'  => 'POST',
      'pattern' => '/admin/bonuscode/create',
      'func'    => 'controller\\admin\\bonuscode::genereate',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  81 =>
    [
      'enable'  => true,
      'method'  => 'POST',
      'pattern' => '/bonus/code',
      'func'    => 'controller\\account\\bonus\\bonus::receiving',
      'access'  =>
        [
          0 => 'user',
          1 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => 'Получение предмета за бонус код',
    ],
  82 =>
    [

      'enable'  => 0,
      'method'  => 'GET',
      'pattern' => '/registration/user',
      'func'    => null,
      'access'  =>
        [
          0 => 'guest',
        ],
      'weight'  => 0,
      'page'    => 'sign-up.html',
      'comment' => '',
    ],
  83 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/registration/user',
      'func'    => 'controller\\registration\\user::add',
      'access'  =>
        [
          0 => 'guest',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  84 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/auth',
      'func'    => 'controller\\user\\auth\\auth::auth_request',
      'access'  =>
        [
          0 => 'guest',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  85 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/login',
      'func'    => null,
      'access'  =>
        [
          0 => 'guest',
        ],
      'weight'  => 0,
      'page'    => 'sign-in.html',
      'comment' => '',
    ],
  86 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/signup',
      'func'    => null,
      'access'  =>
        [
          0 => 'guest',
        ],
      'weight'  => 0,
      'page'    => 'sign-up.html',
      'comment' => 'Registration Page',
    ],
  87 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/signup/{name}',
      'func'    => null,
      'access'  =>
        [
          0 => 'guest',
        ],
      'weight'  => 0,
      'page'    => 'sign-up.html',
      'comment' => 'Registration Page with referral',
    ],
  88 =>
    [

      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/forget',
      'func'    => null,
      'access'  =>
        [
          0 => 'guest',
        ],
      'weight'  => 0,
      'page'    => 'forget.html',
      'comment' => 'Forget password',
    ],
  89 =>
    [

      'enable'  => 1,
      'method'  => 'POST',
      'pattern' => '/forget/create',
      'func'    => 'controller\\user\\forget\\forget::create',
      'access'  =>
        [
          0 => 'guest',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => 'forget email: Create code and send to email',
    ],
  90 =>
    [
      'enable'  => 1,
      'method'  => 'GET',
      'pattern' => '/forget/password/reset/{code}',
      'func'    => 'controller\\user\\forget\\forget::validate',
      'access'  =>
        [
          0 => 'guest',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => 'Valid  code reset password',
    ],
  91 =>
    [
      'enable'  => true,
      'method'  => 'GET',
      'pattern' => '/admin/bonuscode/list',
      'func'    => 'controller\\admin\\bonuscode::show_code',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
  92 =>
    [
      'enable'  => true,
      'method'  => 'POST',
      'pattern' => '/github/update',
      'func'    => 'model\\github\\update::checkNewCommit',
      'access'  =>
        [
          0 => 'admin',
        ],
      'weight'  => 0,
      'page'    => '',
      'comment' => '',
    ],
];
