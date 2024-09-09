<?php

namespace Ofey\Logan22\controller\admin;

use Exception;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class startpack
{

    static public function removePack(): void
    {
        $packId = $_POST['packId'] ?? board::error("Не передан ID пака");
        $row         = sql::getRow('SELECT * FROM `startpacks` WHERE `id` = ?', [$packId]);
        if ( ! $row) {
            board::error('Набор не найден');
        }
        sql::run("DELETE FROM `startpacks` WHERE `id` = ?", [$packId]);
        board::reload();
        board::success("Удалено");
    }

    private static ?array $startpacks = null;

    static public function purchase(): void
    {
        $startpackId = $_POST['startpackId'] ?? board::error('Не указан id набора');
        $row         = sql::getRow('SELECT * FROM `startpacks` WHERE `id` = ?', [$startpackId]);
        if ( ! $row) {
            board::error('Набор не найден');
        }

        $totalPrice = $row['cost'];
        $serverId   = $row['server_id'];

        if (user::self()->getServerId() != $serverId) {
            board::error('Вы не можете купить наборы на другого сервера');
        }

        $db = sql::instance();
        if ( ! $db) {
            board::error("Ошибка подключения к базе данных.");

            return;
        }
        $db->beginTransaction();
        try {
            $canAffordPurchase = user::self()->canAffordPurchase($totalPrice);
            if ( ! $canAffordPurchase) {
                board::error(sprintf("Для покупки у Вас нехватает %s SphereCoin", $totalPrice - user::self()->getDonate()));
            }

            user::self()->donateDeduct($totalPrice);

            $items      = json_decode($row['items'], true);
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
            if ( ! $foundAccount) {
                board::notice(false, "Аккаунт не найден");
            }

            $foundPlayer = false;
            foreach (user::self()->getAccounts() as $accountObj) {
                foreach ($accountObj->getCharacters() as $player) {
                    if ($player->getPlayerName() == $playerName) {
                        $foundPlayer = true;
                    }
                }
            }
            if ( ! $foundPlayer) {
                board::notice(false, "Персонаж не найден");
            }

            $arrObjectItems = [];
            foreach ($items as $item) {
                $arrObjectItems[] = [
                  'itemId'  => (int)$item['itemId'],
                  'count'   => (int)$item['count'],
                  'enchant' => (int)$item['enchant'],
                ];
            }

            $json = \Ofey\Logan22\component\sphere\server::send(type::INVENTORY_TO_GAME, [
              'items'   => $arrObjectItems,
              'player'  => $playerName,
              'account' => $account,
              'email'   => user::self()->getEmail(),
            ])->show()->getResponse();
            if (isset($json['data']) && $json['data'] === true) {
                $db->commit();
                board::alert([
                  'type'       => 'notice',
                  'ok'         => true,
                  'message'    => lang::get_phrase(304),
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
        $row         = sql::getRow('SELECT * FROM `startpacks` WHERE `id` = ?', [$startpackId]);
        if ( ! $row) {
            board::error('Набор не найден');
        }

        $totalPrice = $row['cost'];
        $serverId   = (int)$row['server_id'];

        if (user::self()->getServerId() != $serverId) {
            board::error('Вы не можете купить наборы на другого сервера');
        }

        $canAffordPurchase = user::self()->canAffordPurchase($totalPrice);
        if ( ! $canAffordPurchase) {
            board::error(sprintf("Для покупки у Вас нехватает %s SphereCoin", $totalPrice - user::self()->getDonate()));
        }

        user::self()->donateDeduct($totalPrice);

        $items      = json_decode($row['items'], true);
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
        if ( ! $foundAccount) {
            board::notice(false, "Аккаунт не найден");
        }

        $foundPlayer = false;
        foreach (user::self()->getAccounts() as $accountObj) {
            foreach ($accountObj->getCharacters() as $player) {
                if ($player->getPlayerName() == $playerName) {
                    $foundPlayer = true;
                }
            }
        }
        if ( ! $foundPlayer) {
            board::notice(false, "Персонаж не найден");
        }

        foreach ($items as $item) {
            user::self()->addToWarehouse($serverId, (int)$item['itemId'], (int)$item['count'], (int)$item['enchant'], 148);
        }

        board::alert([
          'type'       => 'notice',
          'ok'         => true,
          'message'    => "Предметы успешно добавлены в склад",
          'sphereCoin' => user::self()->getDonate(),
        ]);

        board::error("Произошла чудовищная ошибка");

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

    static public function add()
    {
        $name  = $_POST['name'] ?? board::error('Введите название набора');
        $cost  = $_POST['cost'] ?? board::error('Введите стоимость набора');
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
            board::success('Набор успешно добавлен');
        } else {
            board::error('Ошибка добавления набора');
        }
    }

}