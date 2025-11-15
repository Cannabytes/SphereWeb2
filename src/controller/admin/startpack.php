<?php

namespace Ofey\Logan22\controller\admin;

use Exception;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class startpack
{

    private static ?array $startpacks = null;

    static public function removePack(): void
    {
        $packId = $_POST['packId'] ?? board::error("Не передан ID пака");
        $row = sql::getRow('SELECT * FROM `startpacks` WHERE `id` = ?', [$packId]);
        if (!$row) {
            board::error(lang::get_phrase(152));
        }
        sql::run("DELETE FROM `startpacks` WHERE `id` = ?", [$packId]);
        board::reload();
        board::success("Удалено");
    }

    static public function purchase(): void
    {
        $startpackId = $_POST['startpackId'] ?? board::error('Не указан id набора');
        $row = sql::getRow('SELECT * FROM `startpacks` WHERE `id` = ?', [$startpackId]);
        if (!$row) {
            board::error(lang::get_phrase(152));
        }

        $totalPrice = $row['cost'];
        $startPackName = $row['name'];
        $serverId = $row['server_id'];

        if (user::self()->getServerId() != $serverId) {
            board::error('Error server id');
        }

        $server = server::getServer($serverId);
        if ($server && !$server->canSendItemsNow()) {
            board::notice(false, $server->getItemsSendLockMessage());
        }

        $db = sql::instance();
        if (!$db) {
            board::error("DB ERROR CONNECT");
            return;
        }
        $db->beginTransaction();
        try {
            $canAffordPurchase = user::self()->canAffordPurchase($totalPrice);
            if (!$canAffordPurchase) {
                board::error(lang::get_phrase("You dont have enough to purchase", $totalPrice - user::self()->getDonate()));
            }

            user::self()->donateDeduct($totalPrice);

            $items = json_decode($row['items'], true);
            $playerName = $_POST['player'] ?? null;
            if ($playerName == null) {
                board::notice(false, lang::get_phrase(148));
            }

            $account = $_POST['account'] ?? null;
            if ($account == null) {
                board::notice(false, lang::get_phrase(148));
            }

            $foundAccount = false;
            foreach (user::self()->getAccounts() as $accountObj) {
                if ($accountObj->getAccount() == $account) {
                    $foundAccount = true;
                }
            }
            if (!$foundAccount) {
                board::notice(false, lang::get_phrase(164));
            }

            $foundPlayer = false;
            foreach (user::self()->getAccounts() as $accountObj) {
                foreach ($accountObj->getCharacters() as $player) {
                    if ($player->getPlayerName() == $playerName) {
                        $foundPlayer = true;
                    }
                }
            }
            if (!$foundPlayer) {
                board::notice(false, lang::get_phrase('Character not found'));
            }

            $arrObjectItems = [];
            foreach ($items as $item) {
                $arrObjectItems[] = [
                    'itemId' => (int)$item['itemId'],
                    'count' => (int)$item['count'],
                    'enchant' => (int)$item['enchant'],
                ];
            }

            if (\Ofey\Logan22\controller\config\config::load()->notice()->isBuyStartPack()) {
                $template = lang::get_other_phrase(\Ofey\Logan22\controller\config\config::load()->notice()->getNoticeLang(), 'notice_start_pack_to_player');
                $msg = strtr($template, [
                    '{email}' => user::self()->getEmail(),
                    '{start_pack_name}' => $startPackName,
                    '{player}' => $playerName,
                ]);
                telegram::sendTelegramMessage($msg, \Ofey\Logan22\controller\config\config::load()->notice()->getBuyStartPackThreadId() );
            }

            $json = \Ofey\Logan22\component\sphere\server::send(type::INVENTORY_TO_GAME, [
                'items' => $arrObjectItems,
                'player' => $playerName,
                'account' => $account,
                'email' => user::self()->getEmail(),
            ])->show()->getResponse();
            if (isset($json['data']) && $json['data'] === true) {
                $db->commit();

                board::alert([
                    'type' => 'notice',
                    'ok' => true,
                    'message' => lang::get_phrase(304),
                    'sphereCoin' => user::self()->getDonate(),
                ]);
            }
            if (isset($json['error']) && $json['error'] !== "") {
                board::error("Произошла чудовищная ошибка");
            }
        } catch (Exception $e) {
            $db->rollback();
            board::error($e->getMessage());
        }
    }

    static public function purchaseWarehouse()
    {
        $startpackId = $_POST['startpackId'] ?? board::error('Не указан id набора');
        $row = sql::getRow('SELECT * FROM `startpacks` WHERE `id` = ?', [$startpackId]);
        if (!$row) {
            board::error(lang::get_phrase(152));
        }

        $totalPrice = $row['cost'];
        $startPackName = $row['name'];
        $serverId = (int)$row['server_id'];

        if (user::self()->getServerId() != $serverId) {
            board::error('Error server id');
        }

        $canAffordPurchase = user::self()->canAffordPurchase($totalPrice);
        if (!$canAffordPurchase) {
            board::error(lang::get_phrase("You dont have enough to purchase", $totalPrice - user::self()->getDonate()));
        }

        if (!user::self()->donateDeduct($totalPrice)) {
            board::error(lang::get_phrase('An error occurred while writing off'));
        }

        $items = json_decode($row['items'], true);
        if(!$items){
            board::error("Ошибка парсинга items");
        }

        foreach ($items as $item) {
            user::self()->addToWarehouse($serverId, (int)$item['itemId'], (int)$item['count'], (int)$item['enchant'], 'starter_pack');
        }

        if (\Ofey\Logan22\controller\config\config::load()->notice()->isBuyStartPack()) {
            $template = lang::get_other_phrase(\Ofey\Logan22\controller\config\config::load()->notice()->getNoticeLang(), 'notice_start_pack_warehouse');
            $msg = strtr($template, [
                '{email}' => user::self()->getEmail(),
                '{start_pack_name}' => $startPackName,
            ]);
            telegram::sendTelegramMessage($msg, \Ofey\Logan22\controller\config\config::load()->notice()->getBuyStartPackThreadId() );
        }

        board::addWarehouseInfo();
        board::success(lang::get_phrase('initial_set_successfully_bought'));

    }

    static public function index()
    {
        tpl::addVar([
            'startpacks' => self::get(),
        ]);
        tpl::display('/admin/startpack.html');
    }

    static public function get(): array
    {
        if (self::$startpacks === null) {
            $startpacks = sql::getRows('SELECT * FROM `startpacks` WHERE `server_id` = ?', [user::self()->getServerId()]);
            foreach ($startpacks as &$startpack) {
                $startpack['items'] = json_decode($startpack['items'], true);
            }
            self::$startpacks = $startpacks;
        }

        return self::$startpacks;
    }

    static public function add(): void
    {
        $name = $_POST['name'] ?? board::error('Введите название набора');
        $cost = $_POST['cost'] ?? board::error('Введите стоимость набора');
        $items = $_POST['items'] ?? board::error('Нет наборов');
        $items = json_encode($items);
        if ($items === false) {
            board::error('Не удалось закодировать наборы');
        }

        sql::run(
            'INSERT INTO `startpacks` (server_id, name, cost, items) VALUES (?, ?, ?, ?)',
            [user::self()->getServerId(), $name, $cost, $items]
        );
        if (sql::lastInsertId()) {
            board::success(lang::get_phrase(243));
        } else {
            board::error('Ошибка добавления набора');
        }
    }

    static public function update(): void
    {
        $packId = $_POST['packId'] ?? board::error('Не указан ID пака');
        $name = $_POST['name'] ?? board::error('Введите название набора');
        $cost = $_POST['cost'] ?? board::error('Введите стоимость набора');
        $items = $_POST['items'] ?? board::error('Нет наборов');
        $items = json_encode($items);
        if ($items === false) {
            board::error('Не удалось закодировать наборы');
        }

        // Проверяем, существует ли пак и принадлежит ли он текущему серверу
        $existingPack = sql::run(
            'SELECT id FROM `startpacks` WHERE id = ? AND server_id = ? LIMIT 1',
            [$packId, user::self()->getServerId()]
        );

        if (!$existingPack || !$existingPack->rowCount()) {
            board::error('Пак не найден или у вас нет прав для его редактирования');
        }

        $result = sql::run(
            'UPDATE `startpacks` SET name = ?, cost = ?, items = ? WHERE id = ? AND server_id = ?',
            [$name, $cost, $items, $packId, user::self()->getServerId()]
        );

        if ($result && $result->rowCount()) {
            board::success(lang::get_phrase(228));
        } else {
            board::error('Ошибка обновления набора или данные не изменились');
        }
    }

}