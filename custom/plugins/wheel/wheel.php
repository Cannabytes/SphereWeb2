<?php

namespace Ofey\Logan22\component\plugins\wheel;

use DateTime;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\image\client_icon;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\log\logTypes;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class wheel
{


    private static int $COOLDOWN_SECONDS = 5;

    public function saveWheel()
    {
        validation::user_protection("admin");
        $object_id = null;
        $wheelName = $_POST['wheel_name'] ?? board::error(lang::get_phrase('Enter the name of your roulette'));
        $wheelCost = $_POST['wheel_cost'] ?? board::error(lang::get_phrase('Enter the scroll cost'));
        $wheelType = $_POST['type'] ?? board::error(lang::get_phrase('No action type'));
        if ($wheelType == "update") {
            $object_id = (int)$_POST['object_id'] ?? board::error(lang::get_phrase('Unable to find roulette indicator'));
        }
        if (!is_numeric($wheelCost)) {
            board::error(lang::get_phrase('Scroll price must be a number'));
        }
        if ($wheelCost < 0) {
            board::error(lang::get_phrase('Scroll price must be greater than 0'));
        }
        if (mb_strlen($wheelName) > 20) {
            board::error(lang::get_phrase('The length of the name cannot be more than 20 characters'));
        }
        if (mb_strlen($wheelName) < 3) {
            board::error(lang::get_phrase('The length of the name must be more than 3 characters'));
        }

        $transformedData = [];
        $totalProbability = 0.00;
        $data = $_POST;
        if (!isset($data['item']) or $data['item'] == "") {
            board::error(lang::get_phrase('You have not filled the array with item IDs'));
        }

        // Получаем количество элементов в массиве 'item'
        $itemCount = count($data['item']);
        //Если больше 20 элементов, то ошибка
        if ($itemCount > 20) {
            board::error(lang::get_phrase('The array with data for creating a roulette contains more than 20 elements'));
        }

        // Проходим по каждому элементу
        for ($i = 0; $i < $itemCount; $i++) {
            $numItem = $i + 1;
            $itemId = $data['item'][$i] ?? null;
            $enchant = $data['enchant'][$i] ?? 0;
            $count = $data['count'][$i] ?? 1;
            $countMin = $data['count_min'][$i] ?? null;
            $countMax = $data['count_max'][$i] ?? null;
            $probability = isset($data['probability'][$i]) ? (float)$data['probability'][$i] : null;
            if (!$itemId) {
                board::error(lang::get_phrase('You have not filled in the item ID', $numItem));
            }
            $countType = $data['way'][$i] ?? board::error(lang::get_phrase('You have not filled in the quantity method', $numItem));

            if (!$probability) {
                board::error(lang::get_phrase('You have not specified the winning percentage', $numItem));
            }

            $totalProbability = round($totalProbability, 2);
            $totalProbability += $probability;
            if ($probability < 0) {
                board::error(lang::get_phrase('The winning percentage must be greater than 0'));
            }

            $itemData = client_icon::get_item_info($itemId);
            if ($itemData == null) {
                board::error(lang::get_phrase('Failed to get item information', $numItem));
            }

            $transformedData[] = [
                'num' => (int)$numItem,
                'item_id' => (int)$itemId,
                'enchant' => (int)$enchant,
                'count_type' => (int)$countType,
                'count' => (int)$count,
                'count_min' => (int)$countMin,
                'count_max' => (int)$countMax,
                'probability' => (float)$probability,
            ];
        }

        if ($totalProbability != 100.00) {
            board::error(lang::get_phrase('The total winning percentage should', $totalProbability));
        }

        $data = [
            'object_id' => $object_id,
            'wheel_name' => $wheelName,
            'items' => $transformedData,
            'type' => $wheelType,
            'cost' => (float)$wheelCost,
        ];

        $response = server::send(type::GAME_WHEEL_SAVE, $data)->show()->getResponse();
        if ($response == null) {
            board::error('Ошибка при сохранении');
        }
        if(!$response['success']){
            board::error($response['message']);
        }

        board::redirect('/fun/wheel/admin');
        board::success(lang::get_phrase(217));
    }

    public function show($name)
    {
        validation::user_protection();
        $stories = sql::getRows(
            "SELECT `id`, `time`, `variables` FROM `logs_all` WHERE type=? AND user_id = ? AND server_id = ? ORDER BY id DESC",
            [
                logTypes::LOG_BONUS_CODE->value,
                user::self()->getId(),
                user::self()->getServerId(),
            ]
        );
        $arrStories = [];
        foreach ($stories as $i=>$story) {
            $time = $story['time'];
            $_story = json_decode($story['variables']);
            $item_id = $_story[0];
            $enchant = $_story[1] ?? "";
            $count = $_story[3] ?? "";
            $info = clone client_icon::get_item_info($item_id);
            $info->setCount($count);
            $info->setEnchant($enchant);
            $info->setDate($time);
            $arrStories[] = $info;
        }
        $stories = $arrStories;

        $response = server::send(type::GET_WHEELS)->show()->getResponse();
        if (isset($response['success']) and !$response['success'] or !$response['success']) {
            redirect::location('/main');
        }

        foreach ($response['wheels'] AS $row){
            if($row['name']==$name){
                $response = $row;
            }
        }

        foreach ($response['items'] as &$item) {
            $itemData = client_icon::get_item_info($item['item_id']);
            $item['icon'] = $itemData->getIcon();
            $item['name'] = $itemData->getItemName();
            $item['add_name'] = $itemData->getAddName();
            $item['description'] = $itemData->getDescription();
            $item['item_type'] = $itemData->getType();
            $item['crystal_type'] = $itemData->getCrystalType();
        }

        tpl::addVar('stories', $stories);
        tpl::addVar('id', (int)$response['id']);
        tpl::addVar('name', $name);
        tpl::addVar('cost', (int)$response['cost']);
        tpl::addVar('items', json_encode($response['items']));
        tpl::displayPlugin("/wheel/tpl/wheel.html");
    }

    public function callback()
    {
        if (isset($_SESSION['last_wheel_spin'])) {
            $timeSinceLastSpin = time() - $_SESSION['last_wheel_spin'];
            if ($timeSinceLastSpin < self::$COOLDOWN_SECONDS) {
                $remainingTime = self::$COOLDOWN_SECONDS - $timeSinceLastSpin;
                board::error(lang::get_phrase('Wait seconds before next use', $remainingTime));
            }
        }
        $id = $_POST['id'] ?? board::error(lang::get_phrase('Failed to get roulette data'));
        $data = [
            'id' => (int)$id,
        ];
        server::setShowError(true);
        $response = server::send(type::GAME_WHEEL, $data)->getResponse();
        if (!$response) {
            board::error(lang::get_phrase('Error while receiving data'));
        }
        if ($response['success']) {
            $itemData = client_icon::get_item_info($response['wheel']['item_id']);
            $response['wheel']['icon'] = $itemData->getIcon();
            $response['wheel']['name'] = $itemData->getItemName();
            $response['wheel']['add_name'] = $itemData->getAddName();
            $response['wheel']['description'] = $itemData->getDescription();
            $response['wheel']['item_type'] = $itemData->getType();
            $response['wheel']['crystal_type'] = $itemData->getCrystalType();

            $item = $response['wheel'];
            $cost = $response['cost'];

            //Если не удалось уменьшить деньги, то выводим ошибку
            if (!user::self()->donateDeduct($cost)) {
                board::error(lang::get_phrase('Insufficient funds'));
            }

            user::self()->addLog(logTypes::LOG_BONUS_CODE, '_LOG_User_Win_Wheel', [$item['item_id'], $item['enchant'], $item['name'], $item['count']]);
            if($item['item_id']==-1){
                user::self()->donateAdd($item['count']);
            }else{
                user::self()->addToWarehouse(0, $item['item_id'], $item['count'], $item['enchant'], 'lucky_wheel');
            }

            $_SESSION['last_wheel_spin'] = time();

            board::alert([
                'success' => true,
                'wheel' => $response['wheel'],
            ]);

        } else {
            board::error($response['message']);
        }
    }

    //Список рулеток

    public function admin()
    {
        validation::user_protection();
        $arr = [];
        $response = server::send(type::GET_WHEELS)->show()->getResponse();
        if ($response['success']) {
            foreach ($response['wheels'] as $wheel) {
                $arr[] = [
                    'id' => $wheel['id'],
                    'name' => $wheel['name'],
                    'spin' => $wheel['spin'] ?? 0,
                    'cost' => $wheel['cost'] ?? 1,
                ];
            }
        }
        $names = array_map(function ($item) {
            return $item['name'];
        }, $arr);

        sql::run("DELETE FROM `server_cache` WHERE `type` = ? AND `server_id` = ?", [
            "__config_fun_wheel__",
            user::self()->getServerId(),
        ]);

        //Сохраним в server_cache для вывода в меню
        sql::run("INSERT INTO `server_cache` (`type`, `data`, `server_id`, `date_create`) VALUES (?, ?, ?, ?)", [
            "__config_fun_wheel__",
            json_encode($names, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            user::self()->getServerId(),
            time::mysql(),
        ]);


        tpl::addVar('wheels', $arr);
        tpl::displayPlugin("/wheel/tpl/admin.html");
    }

    public function edit($id)
    {
        validation::user_protection("admin");
        $response = server::send(type::GET_WHEEL_ITEMS, [
            'id' => (int)$id,
        ])->getResponse();
        //Проверка что
        if (!$response) {
            redirect::location('/fun/wheel/admin');
        }
        if ($response['success']) {
            if (!empty($response['items'])) {

                $items = $response['items'];
                $cost = $response['cost'];
                $name = $response['name'];
                foreach ($items as &$item) {
                    $itemData = client_icon::get_item_info($item['item_id']);
                    $item['icon'] = $itemData->getIcon();
                    $item['name'] = $itemData->getItemName();
                    $item['add_name'] = $itemData->getAddName();
                    $item['description'] = $itemData->getDescription();
                    $item['item_type'] = $itemData->getType();
                    $item['crystal_type'] = $itemData->getCrystalType();
                    $item['count'] = $item['count'] ?? 1;
                    $item['enchant'] = $item['enchant'] ?? 0;
                    $item['probability'] = $item['probability'] ?? 0.00;
                    $item['count_min'] = $item['count_min'] ?? null;
                    $item['count_max'] = $item['count_max'] ?? null;
                    $item['count_type'] = $item['count_type'] ?? null;
                }
                tpl::addVar('object_id', (int)$response['object_id']);
                tpl::addVar('name', $name);
                tpl::addVar('cost', (float)$cost);
                tpl::addVar('wheelsItems', $items ?? []);
            }
        }else{
            redirect::location('/fun/wheel/admin');
        }
        tpl::addVar('title', 'Добавление рулетки');
        tpl::displayPlugin('/wheel/tpl/edit.html');
    }

    public function create()
    {
        validation::user_protection("admin");
        tpl::addVar('title', 'Добавление рулетки');
        tpl::displayPlugin('/wheel/tpl/create.html');
    }

    public function editName()
    {
        $id = $_POST['id'] ?? board::error("no id");
        $old_name = $_POST['old_name'] ?? '';
        $new_name = $_POST['new_name'] ?? '';
        $wheel_cost = (float)$_POST['wheel_cost'] ?? 1;

        if (mb_strlen($new_name) > 20) {
            board::error(lang::get_phrase('The length of the name cannot be more than 20 characters'));
        }
        if (mb_strlen($new_name) < 3) {
            board::error(lang::get_phrase('The length of the name must be more than 3 characters'));
        }
        //Цена прокрутки, может быть float 0.01, но больше нуля
        if ($wheel_cost < 0) {
            board::error(lang::get_phrase('Scroll price must be greater than 0'));
        }

        //Удаление старых данных
        $select = sql::getRows("SELECT * FROM `server_data` WHERE `key` = ? AND `server_id` = ?", [
            "__config_fun_wheel__",
            user::self()->getServerId(),
        ]);

        foreach ($select as $data) {
            $val = json_decode($data['val'], true);
            if ($val['id'] == $id) {
                sql::run("DELETE FROM `server_data` WHERE `id` = ?", [
                    $data['id'],
                ]);
            }
        }

        $data = [
            'id' => (int)$id,
            'name' => $new_name,
            'cost' => $wheel_cost,
        ];

        $sql = "INSERT INTO `server_data` (`key`, `val`, `server_id`) VALUES (?, ?, ?)";
        sql::run($sql, [
            "__config_fun_wheel__",
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            user::self()->getServerId(),
        ]);

        $data = [
            'id' => (int)$id,
            'old_name' => $old_name,
            'new_name' => $new_name,
            'cost' => $wheel_cost,
        ];

        $response = server::send(type::GAME_WHEEL_EDIT_NAME, $data)->show()->getResponse();
        board::alert([
            'ok' => true,
        ]);
    }

    public function remove()
    {
        $id = (int)$_POST['id'] ?? board::error(lang::get_phrase('Failed to get roulette data'));
        $data = [
            'id' => $id,
        ];
        $response = server::send(type::GAME_WHEEL_REMOVE, $data)->show()->getResponse();
        if (isset($response['success'])) {
            if ($response['success']) {
                $rows = sql::getRows("DELETE FROM `server_data` WHERE `key` = ? AND `server_id` = ?", [
                    "__config_fun_wheel__",
                    user::self()->getServerId(),
                ]);
                foreach ($rows as $data) {
                    $val = json_decode($data['val'], true);
                    if ($val['id'] == $id) {
                        sql::run("DELETE FROM `server_data` WHERE `id` = ?", [
                            $data['id'],
                        ]);
                    }
                }

                board::success(lang::get_phrase(146));
            } else {
                board::error($response['error']);
            }
        }
    }

    public function payRoulette()
    {
        $months = $_POST['months'] ?? board::error(lang::get_phrase('Failed to get roulette data'));
        $months = filter_var($months, FILTER_VALIDATE_INT);
        $data = [
            'months' => (int)$months,
        ];
        $response = server::send(type::GAME_WHEEL_PAY_ROULETTE, $data)->show()->getResponse();
        if (isset($response['success'])) {
            if ($response['success']) {
                board::success(lang::get_phrase('Payment was successful'));
            } else {
                board::error($response['error']);
            }
        }
    }

}