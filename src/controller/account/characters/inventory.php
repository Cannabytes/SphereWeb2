<?php

namespace Ofey\Logan22\controller\account\characters;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\item\item;
use Ofey\Logan22\model\log\logTypes;
use Ofey\Logan22\model\user\user;

class inventory
{
    //Разбитие предмета инвентаря
    public static function splitItem()
    {
        $objectItem = $_POST['objectId'] ?? board::error(lang::get_phrase('no_items_provided'));
        $count = $_POST['quantity'] ?? board::error(lang::get_phrase('no_quantity_provided'));
        //Проверяем $_POST['quantity'] на валидность INT значению
        if (!filter_var($count, FILTER_VALIDATE_INT)) {
            board::error(lang::get_phrase('Enter an integer'));
            return;
        }
        // Так же проверяем objectItem на валидность INT значению
        if (!filter_var($objectItem, FILTER_VALIDATE_INT)) {
            board::error(lang::get_phrase('Enter an integer'));
            return;
        }

        //Проверка что пользователь ввел от двух предметов
        if ($count < 1) {
            board::error(lang::get_phrase('minimum_one_item'));
        }

        $item = null;
        foreach (user::self()->getWarehouse() as $object) {
            if ($object->getId() == $objectItem) {
                $item = $object;
            }
        }
        if ($item == null) {
            board::error(lang::get_phrase('item_not_found'));
        }

        // Проверяем что предмет можно стаковать/растаковать
        if (!$item->getItem()->getIsStackable()) {
            board::error(lang::get_phrase('item_not_stackable'));
        }

        $isOk = false;
        //Проверяем что можно это делать
        if (\Ofey\Logan22\model\server\server::getServer()->stackableItem()->isAllowAllItemsSplitting()) {
            $isOk = true;
        }
        if (!$isOk) {
            foreach (\Ofey\Logan22\model\server\server::getServer()->stackableItem()->getSplittableItems() as $splitableItem) {
                if ($item->getItemId() == $splitableItem) {
                    $isOk = true;
                }
            }
        }

        if (!$isOk) {
            board::error(lang::get_phrase('item_magic_protected'));
        }

        // Нужно запретить разбитие предметов, если предметов одного itemId больше 10
        $countItem = 0;
        foreach (user::self()->getWarehouse() as $warehouse) {
            if ($warehouse->getItemId() == $item->getItemId()) {
                $countItem++;
            }
        }
        if ($countItem > 10) {
            board::error(lang::get_phrase('item_quantity_exceeded'));
        }

        foreach (user::self()->getWarehouse() as $item) {
            if ($item->getId() == $objectItem) {
                if ($item->getCount() <= $count) {
                    board::error(lang::get_phrase('not_enough_items'));
                }
                user::self()->removeWarehouseObjectId($objectItem);
                $item_splite_1 = $item->getCount() - $count;
                user::self()->addToWarehouse(0, $item->getItemId(), $item_splite_1, $item->getEnchant(), $item->getPhrase());
                user::self()->addToWarehouse(0, $item->getItemId(), $count, $item->getEnchant(), $item->getPhrase());
                user::self()->addLog(logTypes::LOG_ITEM_SPLITED, "item_split", [$item->getItemId(), $item_splite_1, $count]);
                break;
            }
        }

        user::self()->getWarehouse(true);
        board::alert([
            "ok" => true,
            "message" => lang::get_phrase('item_split_success'),
            "warehouse" => user::self()->getWarehouseToArray(),
            "isAllowAllItemsSplitting" => \Ofey\Logan22\model\server\server::getServer()->stackableItem()->isAllowAllItemsSplitting(),
            "splittableItems" => \Ofey\Logan22\model\server\server::getServer()->stackableItem()->getSplittableItems(),
        ]);

    }

    private static function validateIntArray(string $key, string $errorMessage): array
    {
        $input = filter_input(INPUT_POST, $key, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);

        if (empty($input) || !is_array($input)) {
            board::error($errorMessage);
        }

        foreach ($input as $value) {
            if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                board::error($errorMessage);
            }
        }

        return array_map('intval', $input);
    }

    public static function warehouseToGame()
    {
        $objectItems = $_POST['items'] ?? board::error(lang::get_phrase('no_items_provided'));
        $account = $_POST['account'] ?? board::error(lang::get_phrase('no_account_provided'));
        $player = $_POST['player'] ?? board::error(lang::get_phrase('no_player_name_provided'));
        $arrObjectItems = [];

        self::validateIntArray('items', lang::get_phrase('no_items_provided'));

        foreach (user::self()->getWarehouse() as $warehouse) {
            if (in_array($warehouse->getId(), $objectItems)) {
                $arrObjectItems[] = [
                    'objectId' => $warehouse->getId(),
                    'itemId' => $warehouse->getItemId(),
                    'count' => $warehouse->getCount(),
                    'enchant' => $warehouse->getEnchant(),
                ];
            }
        }
        if (empty($arrObjectItems)) {
            board::error(lang::get_phrase('items_not_found_in_warehouse'));
        }

        $itemTxt = "";
        foreach ($arrObjectItems as $item) {
            $itemData = item::getItem($item['itemId']);
            $itemTxt .= "+" . $item['enchant'] . " " . $itemData->getItemName() . " (" . $item['count'] . ")<br>";
            user::self()->addLog(logTypes::LOG_INVENTORY_TO_GAME, "LOG_INVENTORY_TO_GAME", [$account, $item['itemId'], $item['count']]);
        }

        //Все проверки пройдены успешно
        $json = server::send(type::INVENTORY_TO_GAME, [
            'items' => $arrObjectItems,
            'player' => $player,
            'account' => $account,
            'email' => user::self()->getEmail(),
        ])->show()->getResponse();
        if (isset($json['data']) && $json['data'] === true) {

            $objectItems = $json['objects'];
            user::self()->removeWarehouseObjectId($objectItems);

            if (\Ofey\Logan22\controller\config\config::load()->notice()->isSendWarehouseToGame()) {
                $itemTxt = trim($itemTxt, "<br>");
                $template = lang::get_other_phrase(\Ofey\Logan22\controller\config\config::load()->notice()->getNoticeLang(), 'notice_warehouse_to_player');
                $msg = strtr($template, [
                    '{email}' => user::self()->getEmail(),
                    '{player}' => $player,
                    '{items}' => $itemTxt,
                ]);
                telegram::sendTelegramMessage($msg, config::load()->notice()->getSendWarehouseToGameThreadId());
            }

            board::alert([
                "type" => "notice",
                "ok" => true,
                'sphereCoin' => user::self()->getDonate(),
                "message" => lang::get_phrase("Transferred to player", $player),
                "removeObject" => $arrObjectItems,
                "countWarehouseItems" => user::self()->countWarehouseItems(),
            ]);

        }
        if (isset($json['error']) && $json['error'] !== "") {
            board::error("Произошла чудовищная ошибка");
        }
        board::error("No Data Error");
    }

    public static function sendToGame()
    {

        if (!config::load()->enabled()->isEnableSendBalanceGame()) {
            board::error("Disabled");
        }

        $requiredParams = [
            'account' => 'Account not transferred',
            'player' => 'Player name not passed',
            'coin' => 'Coins not transferred'
        ];

        foreach ($requiredParams as $param => $errorMsg) {
            if (empty($_POST[$param])) {
                board::error(lang::get_phrase($errorMsg));
            }
        }

        $account = $_POST['account'];
        $player = $_POST['player'];
        $coins = (int)$_POST['coin'];

        if (!filter_var($coins, FILTER_VALIDATE_INT)) {
            board::error(lang::get_phrase('Enter an integer'));
            return;
        }

        //Проверяем наличие аккаунта
        if (user::self()->getAccounts($account) === null) {
            board::error(lang::get_phrase('There is no such account'));
        }

        //Проверяем существование игрока в аккаунте
        if (!user::self()->isPlayer($player)) {
            board::error(lang::get_phrase('There is no such player in the account'));
        }

        if (!is_numeric($coins)) {
            board::error(lang::get_phrase('Coins not transferred'));
        }

        if (!user::self()->canAffordPurchase($coins)) {
            board::error(lang::get_phrase('Not enough coins'));
        }


        $countItemsToGameTransfer = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate()->getCountItemsToGameTransfer() * $coins / \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate()->getDonateItemToGameTransfer();

        if (fmod($countItemsToGameTransfer, 1) !== 0.0) {
            board::error(lang::get_phrase('Enter a multiple value. For example', (int)$countItemsToGameTransfer * \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate()->getDonateItemToGameTransfer()));
        }

        if (!user::self()->donateDeduct($coins)) {
            board::error(lang::get_phrase('An error occurred while writing off'));
        }

        $items[] = [
            'objectId' => 0,
            'itemId' => \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate()->getItemIdToGameTransfer(),
            'count' => $countItemsToGameTransfer,
            'enchant' => 0,
        ];

        //Все проверки пройдены успешно
        $json = server::send(type::INVENTORY_TO_GAME, [
            'items' => $items,
            'player' => $_POST['player'],
            'account' => $account,
            'email' => user::self()->getEmail(),
        ])->show()->getResponse();
        if (isset($json['data']) && $json['data'] === true) {

            if (config::load()->notice()->isTranslationGame()) {
                $template = lang::get_other_phrase(config::load()->notice()->getNoticeLang(), 'notice_send_money_to_player');
                $msg = strtr($template, [
                    '{name}' => user::self()->getName(),
                    '{email}' => user::self()->getEmail(),
                    '{coins}' => $coins,
                    '{player}' => $player,
                ]);
                telegram::sendTelegramMessage($msg, config::load()->notice()->getTranslationGameThreadId());
            }

            user::self()->addLog(logTypes::LOG_DONATE_COIN_TO_GAME, "LOG_DONATE_COIN_TO_GAME", [$player, \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate()->getItemIdToGameTransfer(), $countItemsToGameTransfer]);
            board::alert([
                "type" => "notice",
                "ok" => true,
                "message" => lang::get_phrase("Transferred to player", $player),
                'sphereCoin' => user::self()->getDonate(),
            ]);

        }

        if (isset($json['error']) && $json['error'] !== "") {
            board::error(lang::get_phrase(145));
        }

    }

}