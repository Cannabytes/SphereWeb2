<?php

namespace Ofey\Logan22\component\sphere;

enum type
{

    case SPHERE_INSTALL;
    case REGISTRATION;
    case STATISTIC;
    case STATISTIC_ALL;
    case ACCOUNT_PLAYERS;
    case ACCOUNT_PLAYER_CHANGE_PASSWORD;
    case INVENTORY_TO_GAME;
    case STARTPACK_TO_GAME;
    case RELOCATION;

    //Получение списка коллекций запросов
    case SERVER_COLLECTIONS;
    //Соединение с базой данных
    case CONNECT_DB;
    //Обновление базы данных
    case CONNECT_DB_UPDATE;
    //Добавление нового соединения с БД
    case ADD_NEW_CONNECT_DB;
    //Добавление нового сервера
    case ADD_NEW_SERVER;
    //Обновление статуса сервера
    case UPDATE_STATUS_SERVER;
    //Получение статуса сервера
    case GET_STATUS_SERVER;
    case GET_STATUS_SERVER_ALL;
    case UPDATE_COLLECTION;
    case DELETE_SERVER;
    //Отправка запроса на синхронизацию внутренней базы данных
    case SYNCHRONIZATION;
    //Статистика для админов, о онлайне
    case SERVER_STATISTIC_ONLINE;
    case SERVER_LIST;
    case SERVER_FULL_INFO;

    case SET_SERVER_ENABLED;

    //Отправка запроса на сервер игровой
    case GAME_SERVER_REQUEST;

    // Игра в рулетку
    case GAME_WHEEL_SAVE;
    case GAME_WHEEL;
    case GET_WHEEL_ITEMS;
    case GET_WHEELS;
    case GAME_WHEEL_EDIT_NAME;
    case GAME_WHEEL_REMOVE;
    case GAME_WHEEL_PAY_ROULETTE;

    //Дополнительные настройки
    // Сброс HWID
    case RESET_HWID;

    case GET_COMMIT_LAST;
    case GET_COMMIT_FILES;

    // Реконнект для отключенного сервера
    case SERVER_RECONNECT;

    // Создание токена для лаунчера
    case LAUNCHER_CREATE_TOKEN;

    // Данные для оплаты SphereWeb
    case SPHERE_DONATE;

    // Список баз
    case GET_DATABASE_LIST;
    // Удаление БД
    case DELETE_DATABASE;
    case CONNECTION_QUALITY_DATABASE;
    case PORT_QUALITY_DATABASE;
    case DONATE_STATISTIC;

    case GET_GAME_SERVERS; //DEPRECATED
    case GET_LOGIN_SERVERS; //DEPRECATED
    case GET_LOGIN_SERVERS_DATA;
    case DELETE_LOGINSERVER;
    case UPDATE_LOGINSERVER;
    case UPDATE_GAMESERVER;

    // Супер пользователь
    case CREATE_SUPER_USER_EMAIL_CHECK;
    case AUTH_SUPER_USER;
    case CHECK_SUPER_USER_EMAIL_CONFIRM;

    case ERROR_REPORT;
    case CLEAR_ERRORS;

    case RENEW_LICENSE;

    case DOWNLOAD_ACCOUNTS;
    case LOAD_ACCOUNTS;
    case LOAD_ACCOUNTS_PROGRESS;
    case GET_ERRORS;

    case EXCHANGER;

    case ITEM_INCREASE_ADD;
    case ITEM_INCREASE_DELETE;
    case ITEM_INCREASE_ITEMS;
    case ITEM_INCREASE_PAY;

    case FILE_SCANNER;

    case BUY_BALANCE_PACK;

    case GEO_IP;

    static function url(type $type): string
    {
        return match ($type) {
            self::SPHERE_INSTALL => '/api/admin/install',
            self::REGISTRATION => '/api/user/registration',
            self::STATISTIC => '/api/statistic',
            self::STATISTIC_ALL => '/api/statistic/all',
            self::ACCOUNT_PLAYERS => '/api/user/player/account',
            self::ACCOUNT_PLAYER_CHANGE_PASSWORD => '/api/user/player/account/change/password',
            self::INVENTORY_TO_GAME => '/api/user/player/item/add',
            self::STARTPACK_TO_GAME => '/api/user/player/startpack/add',
            self::RELOCATION => '/api/user/player/relocation',

            self::SERVER_COLLECTIONS => '/api/server/collections',
            self::CONNECT_DB => '/api/server/mysql/connection',
            self::CONNECT_DB_UPDATE => '/api/server/update/mysql',
            self::ADD_NEW_CONNECT_DB => '/api/server/add/db',
            self::ADD_NEW_SERVER => '/api/server/add',
            self::UPDATE_STATUS_SERVER => '/api/server/update/status',
            self::GET_STATUS_SERVER => '/api/server/status',
            self::GET_STATUS_SERVER_ALL => '/api/server/status/all',
            self::UPDATE_COLLECTION => '/api/server/update/collection',
            self::DELETE_SERVER => '/api/server/delete',
            self::SERVER_LIST => '/api/server/list',
            self::SERVER_FULL_INFO => '/api/server/full/info',
            self::GAME_SERVER_REQUEST => '/api/server/request/mysql',

            self::GET_DATABASE_LIST => '/api/server/get/databases',
            self::DELETE_DATABASE => '/api/server/delete/db',
            self::CONNECTION_QUALITY_DATABASE => '/api/server/quality/db',
            self::PORT_QUALITY_DATABASE => '/api/server/quality/port',
            self::GET_GAME_SERVERS => '/api/server/get/gameservers',
            self::GET_LOGIN_SERVERS => '/api/server/get/loginservers',
            self::GET_LOGIN_SERVERS_DATA => '/api/server/get/loginservers/data',
            self::DOWNLOAD_ACCOUNTS => '/api/server/download/accounts',
            self::LOAD_ACCOUNTS => '/api/server/load/accounts',
            self::LOAD_ACCOUNTS_PROGRESS => '/api/server/load/accounts/progress',
            self::GET_ERRORS => '/api/server/get/errors',
            self::CLEAR_ERRORS => '/api/server/errors/clear',

            self::DELETE_LOGINSERVER => '/api/server/delete/loginservers',
            self::UPDATE_LOGINSERVER => '/api/server/update/loginserver',
            self::UPDATE_GAMESERVER => '/api/server/update/gameserver',

            self::SYNCHRONIZATION => '/api/user/accounts/synchronization',
            self::SERVER_STATISTIC_ONLINE => '/api/server/statistic/online',
            self::SERVER_RECONNECT => '/api/server/reconnect',

            self::GAME_WHEEL_SAVE => '/api/game/wheel/save',
            self::GAME_WHEEL => '/api/game/wheel/start',
            self::GET_WHEEL_ITEMS => '/api/game/wheel/items',
            self::GET_WHEELS => '/api/game/wheel/list',
            self::GAME_WHEEL_EDIT_NAME => '/api/game/wheel/edit/name',
            self::GAME_WHEEL_REMOVE => '/api/game/wheel/remove',
            self::GAME_WHEEL_PAY_ROULETTE => '/api/game/wheel/pay',

            self::RESET_HWID => '/api/user/player/reset/hwid',

            self::GET_COMMIT_LAST => '/api/github/last/commit',
            self::GET_COMMIT_FILES => '/api/github/commit/files',

            self::LAUNCHER_CREATE_TOKEN => '/api/launcher/create/token',

            self::SPHERE_DONATE => "/api/donate",
            self::DONATE_STATISTIC => "/api/statistic/server/donate",

            self::CREATE_SUPER_USER_EMAIL_CHECK => "/api/user/global/add/email/check",
            self::AUTH_SUPER_USER => "/api/user/global/auth",
            self::CHECK_SUPER_USER_EMAIL_CONFIRM => '/api/user/global/add/get/check',

            self::ERROR_REPORT => '/api/error/report',

            self::RENEW_LICENSE => '/api/license/renew',

            self::EXCHANGER => '/api/exchanger',

            self::SET_SERVER_ENABLED => '/api/server/set/enabled',

            self::ITEM_INCREASE_ADD => '/api/item/increase/add',
            self::ITEM_INCREASE_DELETE => '/api/item/increase/delete',
            self::ITEM_INCREASE_ITEMS => '/api/item/increase/items',
            self::ITEM_INCREASE_PAY => '/api/item/increase/pay',

            self::FILE_SCANNER => '/api/file/scanner',

            self::BUY_BALANCE_PACK => '/api/balance/buy/pack',

            self::GEO_IP => '/api/geo/ip',

            default => null,
        };
    }

}
