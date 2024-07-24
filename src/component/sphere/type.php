<?php

namespace Ofey\Logan22\component\sphere;

use Ofey\Logan22\controller\config\config;

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


    static function url(type $type): string
    {


        return  match ($type) {
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

              self::SYNCHRONIZATION => '/api/user/accounts/synchronization',
              self::SERVER_STATISTIC_ONLINE => '/api/server/statistic/online',

              self::GAME_WHEEL_SAVE => '/api/game/wheel/save',
              self::GAME_WHEEL => '/api/game/wheel/start',
              self::GET_WHEEL_ITEMS => '/api/game/wheel/items',
              self::GET_WHEELS => '/api/game/wheel/list',
              self::GAME_WHEEL_EDIT_NAME => '/api/game/wheel/edit/name',
              self::GAME_WHEEL_REMOVE => '/api/game/wheel/remove',

              self::RESET_HWID => '/api/user/player/reset/hwid',

              self::GET_COMMIT_LAST => '/api/github/last/commit',
              self::GET_COMMIT_FILES => '/api/github/commit/files',
              default => null,
          };
    }

}
