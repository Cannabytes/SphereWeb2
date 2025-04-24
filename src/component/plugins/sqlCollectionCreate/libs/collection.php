<?php

namespace Ofey\Logan22\component\plugins\sqlCollectionCreater\libs;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\chronicle\client;
use Ofey\Logan22\component\plugins\sqlCollectionCreate\custom_twig;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\request\clear;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\template\tpl;

class collection
{
    public function show(): void
    {
        validation::user_protection("admin");
        $sqlbases = server::sendCustom("/api/server/collection/get")->getResponse();
        tpl::addVar([
            'sqlbases' => $sqlbases,
        ]);
        tpl::displayPlugin("sqlCollectionCreate/tpl/list.html");
    }

    public function edit($name)
    {
        validation::user_protection("admin");
        $getCollection = server::sendCustom("/api/server/collection/get/queries", ['collection_name' => $name])->getResponse();
        tpl::addVar([
            'getCollection' => $getCollection,
            'client_list_default' => client::all(),
        ]);
        tpl::displayPlugin("sqlCollectionCreate/tpl/sql_editor.html");
    }

    public function checkQuery()
    {
        validation::user_protection("admin");
        $query = $_POST['query'];
        $params = $_POST['params'] ?? null;
        $query_name = $_POST['query_name'];
        $serverId = (int)$_POST['server_id'] ?? null;

        $request = \Ofey\Logan22\component\sphere\server::send(type::GAME_SERVER_REQUEST, [
            'query' => clear::cleanSQLQuery($query),
            'params' => ($params),
            'server_id' => $serverId,
        ])->show(false)->getResponse();

        $struct = null;

        if (in_array($query_name, ['getAccount', 'countOnline', 'getCharactersAccount', 'statistic_castle', 'statistic_clan', 'statistic_exp', 'statistic_online', 'statistic_pk', 'statistic_pvp'])) {
            switch ($query_name) {
                case "getAccount":
                    $struct = custom_twig::struct_get_account();
                    break;
                case "countOnline":
                    $struct = custom_twig::struct_count_online();
                    break;
                case "getCharactersAccount":
                    $struct = custom_twig::struct_characters_account();
                    break;
                case "statistic_castle":
                    $struct = custom_twig::struct_statistic_castle();
                    break;
                case "statistic_clan":
                    $struct = custom_twig::struct_statistic_clan();
                    break;
                case "statistic_exp":
                    $struct = custom_twig::struct_statistic_exp();
                    break;
                case "statistic_online":
                    $struct = custom_twig::struct_statistic_online();
                    break;
                case "statistic_pk":
                    $struct = custom_twig::struct_statistic_pk();
                    break;
                case "statistic_pvp":
                    $struct = custom_twig::struct_statistic_pvp();
                    break;
            }

            if ($struct) {
                $request['struct'] = $struct;
                echo json_encode($request);
            }
        }

    }

    public function save()
    {
        validation::user_protection("admin");
        // Проверка name
        if (!isset($_POST['name']) || !preg_match('/^[a-zA-Z0-9_()!№*]+$/', $_POST['name'])) {
            die('Ошибка: Некорректное имя. Разрешены только буквы, цифры и символы _()!№*');
        }
        $name = $_POST['name'];

        // Проверка hash
        $valid_hashes = ['sha1', 'whirlpool', 'bcrypt', 'pbkdf2'];
        if (!isset($_POST['hash']) || !in_array($_POST['hash'], $valid_hashes, true)) {
            die('Ошибка: Недопустимое значение hash');
        }
        $hash = $_POST['hash'];

        // Проверка protocols (массив чисел)
        if (!isset($_POST['protocols']) || !is_array($_POST['protocols']) ||
            array_filter($_POST['protocols'], fn($v) => !is_numeric($v))) {
            die('Ошибка: protocols должен быть массивом чисел');
        }
        $protocols = $_POST['protocols'];
        foreach ($protocols as &$protocol) {
            $protocol = (int) $protocol;
            if (client::get_chronicles_by_protocol($protocol) === []) {
                die('Ошибка: Недопустимый протокол ' . $protocol);
            }
        }

        // Проверка is_table_delayed
        $is_table_delayed = (bool) filter_var($_POST['is_table_delayed'], FILTER_VALIDATE_BOOLEAN);

        // Проверка query
        if (!isset($_POST['queries'])) {
            die('Ошибка: параметр queries отсутствует в запросе. POST данные: ' . print_r($_POST, true));
        }

        $required_queries = [
            "getAccount", "newAccount", "updatePassword", "addItem", "countOnline",
            "getCharactersAccount", "is_online", "itemsToWarehouse", "relocation",
            "statistic_castle", "statistic_clan", "statistic_exp", "statistic_online",
            "statistic_pk", "statistic_pvp"
        ];

        foreach ($required_queries as $queryName) {
            if (!isset($_POST['queries'][$queryName]) || trim($_POST['queries'][$queryName]) === '') {
                die("Ошибка: Значение {$queryName} не должно быть пустым");
            }
        }

        // Если все проверки пройдены, можно продолжать обработку данных
        $query = $_POST['queries'];

        $data = [
            'name' => $name,
            'hash' => $hash,
            'description' => $_POST['description'],
            'protocols' => $protocols,
            'is_table_delayed' => $is_table_delayed,
            'queries' => $query
        ];

        $response = server::sendCustom("/api/server/collection/save", $data)->show()->getResponse();
        if ($response['success']) {
            board::redirect("/admin/collection");
            board::success("Коллекция успешно сохранена");
        }

    }




}