<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\template\tpl;

class errors
{
    static public function getErrors($id = null): void
    {
        if ($id==null OR \Ofey\Logan22\model\server\server::isServer($id)==null){
            redirect::location("/admin");
        }
        $response = \Ofey\Logan22\component\sphere\server::send(type::GET_ERRORS, [
            'id' => (int)$id,
        ])->show(false)->getResponse();
        $errors = $response['data_errors'] ?? false;
        if ($errors){
            foreach ($errors AS &$err){
                switch ($err['name']){
                    case "statistic_pvp" :
                        $err['err_level'] = 'critical error';
                        $err['message'] = 'Отключено получение статистики PvP';
                        $err['desc'] = 'Отключение статистики происходит при выполнении SQL запроса на получение данных статистики PVP игроков, SQL запрос не был выполнен из-за того что база данных гейм-сервера имеет структуру не подходящую под выбранную Вами сборку сервера.';
                        break;
                    case "statistic_pk":
                        $err['err_level'] = 'critical error';
                        $err['message'] = 'Отключено получения статистики PK';
                        $err['desc'] = 'Отключение статистики происходит при выполнении SQL запроса на получение данных статистики PK игроков, SQL запрос не был выполнен из-за того что база данных гейм-сервера имеет структуру не подходящую под выбранную Вами сборку сервера.';
                        break;
                    case "statistic_online":
                        $err['err_level'] = 'critical error';
                        $err['message'] = 'Отключено получения статистики топ онлайна персонажей';
                        $err['desc'] = 'Отключение статистики происходит при выполнении SQL запроса на получение данных статистики Online игроков, SQL запрос не был выполнен из-за того что база данных гейм-сервера имеет структуру не подходящую под выбранную Вами сборку сервера.';
                        break;
                    case "statistic_exp":
                        $err['err_level'] = 'critical error';
                        $err['message'] = 'Отключено получения статистики опыта игрока';
                        $err['desc'] = 'Отключение статистики происходит при выполнении SQL запроса на получение данных статистики EXP игроков, SQL запрос не был выполнен из-за того что база данных гейм-сервера имеет структуру не подходящую под выбранную Вами сборку сервера.';
                        break;
                    case "statistic_clan":
                        $err['err_level'] = 'critical error';
                        $err['message'] = 'Отключение получения статистики кланов';
                        $err['desc'] = 'Отключение статистики происходит при выполнении SQL запроса на получение данных статистики Clan игроков, SQL запрос не был выполнен из-за того что база данных гейм-сервера имеет структуру не подходящую под выбранную Вами сборку сервера.';
                        break;
                    case "statistic_castle":
                        $err['err_level'] = 'critical error';
                        $err['message'] = 'Отключено получения статистики замков';
                        $err['desc'] = 'Отключение статистики происходит при выполнении SQL запроса на получение данных статистики Castle игроков, SQL запрос не был выполнен из-за того что база данных гейм-сервера имеет структуру не подходящую под выбранную Вами сборку сервера.';
                        break;
                    case "getCharactersAccount":
                        $err['err_level'] = 'critical error';
                        $err['message'] = 'Отключение получения данных персонажей аккаунта';
                        $err['desc'] = 'Отключение статистики происходит при выполнении SQL запроса на получение данных персонажей аккаунтов пользователя, SQL запрос не был выполнен из-за того что база данных гейм-сервера имеет структуру не подходящую под выбранную Вами сборку сервера.';
                        break;
                    default:
                        $err['err_level'] = 'no info level error';
                        $err['message'] = 'Незадокументированная ошибка';
                        $err['desc'] = 'Отключение статистики происходит при выполнении SQL запроса на получение данных статистики PVP игроков, SQL запрос не был выполнен из-за того что база данных гейм-сервера имеет структуру не подходящую под выбранную Вами сборку сервера.';
                        break;
                }
            }
        }
        tpl::addVar([
            'id' => $id,
            'data_errors' => $errors,
        ]);
        tpl::display("/admin/server_errors.html");
    }

    static public function clear()
    {
        $id = $_POST['id'] ?? null;
        if ($id==null){
            board::error("Ошибка передачи ID сервера");
        }
        $response = \Ofey\Logan22\component\sphere\server::send(type::CLEAR_ERRORS, ['id'=>(int)$id])->show()->getResponse();
        if($response['success'] ?? false){
            board::success("Очистка списка ошибок выполнена успешно");
        }
    }

}