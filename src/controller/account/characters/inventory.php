<?php

namespace Ofey\Logan22\controller\account\characters;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\log\logTypes;
use Ofey\Logan22\model\user\user;

class inventory
{

    public static function warehouseToGame()
    {
        $objectItems    = $_POST['items'] ?? board::error("Не переданы предметы");
        $account        = $_POST['account'] ?? board::error("Не передан аккаунт");
        $player         = $_POST['player'] ?? board::error("Не передан имя игрока");
        $arrObjectItems = [];
        foreach (user::self()->getWarehouse() as $warehouse) {
            if (in_array($warehouse->getId(), $objectItems)) {
                $arrObjectItems[] = [
                  'objectId' => $warehouse->getId(),
                  'itemId'   => $warehouse->getItemId(),
                  'count'    => $warehouse->getCount(),
                  'enchant'  => $warehouse->getEnchant(),
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
          'items'   => $arrObjectItems,
          'player'  => $player,
          'account' => $account,
          'email'   => user::self()->getEmail(),
        ])->show()->getResponse();
        if (isset($json['data']) && $json['data'] === true) {

            foreach ($arrObjectItems as $item) {
                user::self()->addLog(logTypes::LOG_INVENTORY_TO_GAME, "LOG_INVENTORY_TO_GAME", [$account, $item['itemId'], $item['count']]);
            }

            $objectItems = $json['objects'];
            user::self()->removeWarehouseObjectId($objectItems);

            board::alert([
              "type"       => "notice",
              "ok"         => true,
              'sphereCoin' => user::self()->getDonate(),
              "message"    => "Передано игроку " . $player,
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

        $account = $_POST['account'] ?? board::error("Не передан аккаунт");
        $player  = $_POST['player'] ?? board::error("Не передано имя игрока");
        $coins   = $_POST['coin'] ?? board::error("Не переданы монеты");
        /**
         * Проверка кол-ва монет
         */

        //Проверяем наличие аккаунта
        if (user::self()->getAccounts($account) === null) {
            board::error("Нет такого аккаунта");
        }

        //Проверяем существование игрока в аккаунте
        if (!$playerInfo = user::self()->isPlayer($player)) {
            board::error("Нет такого игрока в аккаунте");
        }

        if ( ! is_numeric($coins)) {
            board::error("Не переданы монеты");
        }
        if ( ! user::self()->canAffordPurchase($coins)) {
            board::error("Недостаточно монет");
        }
        if ( ! user::self()->donateDeduct($coins)) {
            board::error("Произошла ошибка списания");
        }

        $countItemsToGameTransfer = config::load()->donate()->getCountItemsToGameTransfer()*$coins;
        $items[] = [
          'objectId' => 0,
          'itemId'   => config::load()->donate()->getItemIdToGameTransfer(),
          'count'    => $countItemsToGameTransfer,
          'enchant'  => 0,
        ];

        //Все проверки пройдены успешно
        $json = server::send(type::INVENTORY_TO_GAME, [
          'items'   => $items,
          'player'  => $_POST['player'],
          'account' => $account,
          'email'   => user::self()->getEmail(),
        ])->show()->getResponse();
        if (isset($json['data']) && $json['data'] === true) {
            user::self()->addLog(logTypes::LOG_DONATE_COIN_TO_GAME, "LOG_DONATE_COIN_TO_GAME", [$account, config::load()->donate()->getItemIdToGameTransfer(), $countItemsToGameTransfer]);
            board::alert([
              "type"       => "notice",
              "ok"         => true,
              "message"    => "Передано игроку " . $player,
              'sphereCoin' => user::self()->getDonate(),
            ]);
        }

        if (isset($json['error']) && $json['error'] !== "") {
            board::error("Произошла чудовищная ошибка");
        }

    }

}