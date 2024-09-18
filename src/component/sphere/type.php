<?php

namespace Ofey\Logan22\component\sphere;

enum type
{

    case SPHERE_INSTALL;
    case REGISTRATION;
    case STATISTIC;
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
    //Добавление нового сервера
    case ADD_NEW_SERVER;
    //Обновление статуса сервера
    case UPDATE_STATUS_SERVER;
    //Получение статуса сервера
    case GET_STATUS_SERVER;
    case UPDATE_COLLECTION;
    case DELETE_SERVER;
    //Отправка запроса на синхронизацию внутренней базы данных
    case SYNCHRONIZATION;
    //Статистика для админов, о онлайне
    case SERVER_STATISTIC_ONLINE;
    case SERVER_LIST;
    case SERVER_FULL_INFO;

    //Отправка запроса на сервер игровой
    case GAME_SERVER_REQUEST;

    // Игра в рулетку
    case GAME_WHEEL_SAVE;
    case GAME_WHEEL;
    case GET_WHEEL_ITEMS;
    case GET_WHEELS;
    case GAME_WHEEL_EDIT_NAME;
    case GAME_WHEEL_REMOVE;

    //Дополнительные настройки
    // Сброс HWID
    case RESET_HWID;

    case GET_COMMIT_LAST;
    case GET_COMMIT_FILES;

    //Получение информации о стриме по ссылки
    case GET_STREAM_INFO;
    case SET_STREAM_AUTOCHECK;

    // Реконнект для отключенного сервера
    case SERVER_RECONNECT;

    // Создание токена для лаунчера
    case LAUNCHER_CREATE_TOKEN;
    case LAUNCHER_UPDATE_TIME;

    // Сохранить выбранные сервисы
    case SAVE_SERVICE;

    // Данные для оплаты SphereWeb
    case SPHERE_DONATE;

    case GET_LOGIN_SERVERS;
    case GET_LOGIN_SERVERS_DATA;
    case DELETE_LOGINSERVER;

    // Супер пользователь
    case CREATE_SUPER_USER_EMAIL_CHECK;
    case AUTH_SUPER_USER;
    case CHECK_SUPER_USER_EMAIL_CONFIRM;

    CASE ERROR_REPORT;


    static function url(type $type): string
    {
        return match ($type) {
            self::SPHERE_INSTALL => '/api/admin/install',
            self::REGISTRATION => '/api/user/registration',
            self::STATISTIC => '/api/statistic',
            self::ACCOUNT_PLAYERS => '/api/user/player/account',
            self::ACCOUNT_PLAYER_CHANGE_PASSWORD => '/api/user/player/account/change/password',
            self::INVENTORY_TO_GAME => '/api/user/player/item/add',
            self::STARTPACK_TO_GAME => '/api/user/player/startpack/add',
            self::RELOCATION => '/api/user/player/relocation',

            self::SERVER_COLLECTIONS => '/api/server/collections',
            self::CONNECT_DB => '/api/server/mysql/connection',
            self::CONNECT_DB_UPDATE => '/api/server/update/mysql',
            self::ADD_NEW_SERVER => '/api/server/add',
            self::UPDATE_STATUS_SERVER => '/api/server/update/status',
            self::GET_STATUS_SERVER => '/api/server/status',
            self::UPDATE_COLLECTION => '/api/server/update/collection',
            self::DELETE_SERVER => '/api/server/delete',
            self::SERVER_LIST => '/api/server/list',
            self::SERVER_FULL_INFO => '/api/server/full/info',
            self::GAME_SERVER_REQUEST => '/api/server/request/mysql',

            self::GET_LOGIN_SERVERS => '/api/server/get/loginservers',
            self::GET_LOGIN_SERVERS_DATA => '/api/server/get/loginservers/data',

            self::DELETE_LOGINSERVER => '/api/server/delete/loginservers',

            self::SYNCHRONIZATION => '/api/user/accounts/synchronization',
            self::SERVER_STATISTIC_ONLINE => '/api/server/statistic/online',
            self::SERVER_RECONNECT => '/api/server/reconnect',

            self::GAME_WHEEL_SAVE => '/api/game/wheel/save',
            self::GAME_WHEEL => '/api/game/wheel/start',
            self::GET_WHEEL_ITEMS => '/api/game/wheel/items',
            self::GET_WHEELS => '/api/game/wheel/list',
            self::GAME_WHEEL_EDIT_NAME => '/api/game/wheel/edit/name',
            self::GAME_WHEEL_REMOVE => '/api/game/wheel/remove',

            self::RESET_HWID => '/api/user/player/reset/hwid',

            self::GET_COMMIT_LAST => '/api/github/last/commit',
            self::GET_COMMIT_FILES => '/api/github/commit/files',

            self::GET_STREAM_INFO => '/api/stream/check',
            self::SET_STREAM_AUTOCHECK => '/api/stream/setautocheck',

            self::LAUNCHER_CREATE_TOKEN => '/api/launcher/create/token',
            self::LAUNCHER_UPDATE_TIME => '/api/launcher/update/time',

            self::SAVE_SERVICE => "/api/server/service/save",
            self::SPHERE_DONATE => "/api/donate",

            self::CREATE_SUPER_USER_EMAIL_CHECK => "/api/user/global/add/email/check",
            self::AUTH_SUPER_USER => "/api/user/global/auth",
            self::CHECK_SUPER_USER_EMAIL_CONFIRM => '/api/user/global/add/get/check',

            self::ERROR_REPORT => '/api/error/report',

            default => null,
        };
    }

}
