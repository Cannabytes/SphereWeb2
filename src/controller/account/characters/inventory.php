<?php

namespace Ofey\Logan22\controller\account\characters;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\log\logTypes;
use Ofey\Logan22\model\user\user;

class inventory
{

    public static function warehouseToGame()
    {
        $objectItems = $_POST['items'] ?? board::error("Не переданы предметы");
        $account = $_POST['account'] ?? board::error("Не передан аккаунт");
        $player = $_POST['player'] ?? board::error("Не передан имя игрока");
        $arrObjectItems = [];
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
            board::error("Предметы не найдены в складе");
        }

        //Проверяем наличие аккаунта
        //        if(user::self()->getAccounts($account) === null){
        //            board::error("Нет такого аккаунта");
        //        }
        //        //Проверяем существование игрока в аккаунте
        //        if (!$playerInfo = user::self()->isPlayer($player)) {
        //            board::error("Нет такого игрока в аккаунте");
        //        }
        // Проверяем что персонаж действительно находится на данном аккаунте
        //        if($playerInfo->getAccount() !== $account){
        //            board::error("Данный аккаунт не содержит данного персонажа");
        //        }

        //Все проверки пройдены успешно
        $json = server::send(type::INVENTORY_TO_GAME, [
            'items' => $arrObjectItems,
            'player' => $player,
            'account' => $account,
            'email' => user::self()->getEmail(),
        ])->show()->getResponse();
        if (isset($json['data']) && $json['data'] === true) {

            foreach ($arrObjectItems as $item) {
                user::self()->addLog(logTypes::LOG_INVENTORY_TO_GAME, "LOG_INVENTORY_TO_GAME", [$account, $item['itemId'], $item['count']]);
            }

            $objectItems = $json['objects'];
            user::self()->removeWarehouseObjectId($objectItems);

            board::alert([
                "type" => "notice",
                "ok" => true,
                'sphereCoin' => user::self()->getDonate(),
                "message" => "Передано игроку " . $player,
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

        $account = $_POST['account'] ?? board::error(lang::get_phrase('Account not transferred'));
        $player = $_POST['player'] ?? board::error(lang::get_phrase('Player name not passed'));
        $coins = $_POST['coin'] ?? board::error(lang::get_phrase('Coins not transferred'));

        if (!filter_var($coins, FILTER_VALIDATE_INT)) {
            board::error(lang::get_phrase('Enter an integer'));
            return;
        }

        //Проверяем наличие аккаунта
        if (user::self()->getAccounts($account) === null) {
            board::error(lang::get_phrase('There is no such account'));
        }
        //Проверяем существование игрока в аккаунте
        if (!$playerInfo = user::self()->isPlayer($player)) {
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
            board::error(lang::get_phrase('Enter a multiple value. For example',  (int)$countItemsToGameTransfer * \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate()->getDonateItemToGameTransfer()));
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
            user::self()->addLog(logTypes::LOG_DONATE_COIN_TO_GAME, "LOG_DONATE_COIN_TO_GAME", [$account, \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate()->getItemIdToGameTransfer(), $countItemsToGameTransfer]);
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