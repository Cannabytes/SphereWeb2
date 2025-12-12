<?php

namespace Ofey\Logan22\controller\admin;

use Exception;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;
use Ofey\Logan22\model\log\logTypes;


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

        // Применяем настройки стартапа (кулдаун и скидки)
        $originalPrice = (int)$row['cost'];
        $totalPrice = self::computeTotalPriceFromSettings((int)$serverId, $originalPrice);

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

        $originalPrice = (int)$row['cost'];
        $totalPrice = $originalPrice;
        $startPackName = $row['name'];
        $serverId = (int)$row['server_id'];

        if (user::self()->getServerId() != $serverId) {
            board::error('Error server id');
        }

        // Применяем настройки стартапа (кулдаун и скидки)
        $totalPrice = self::computeTotalPriceFromSettings($serverId, $originalPrice);


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

        user::self()->addLog(logTypes::LOG_BUY_START_PACK, "LOG_USER_BUY_STARTPACK", [$startPackName]);

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

    /**
     * Сохранение настроек стартапа в таблицу server_data
     */
    static public function save_settings(): void
    {
        $serverId = user::self()->getServerId();
        if (!$serverId) {
            board::error('Server not found');
        }

        $packLimit = isset($_POST['pack_limit_seconds']) ? (int)$_POST['pack_limit_seconds'] : 0;
        $discountFrom = isset($_POST['discount_from']) ? trim($_POST['discount_from']) : '';
        $discountTo = isset($_POST['discount_to']) ? trim($_POST['discount_to']) : '';
        $discountPercent = isset($_POST['discount_percent']) ? (int)$_POST['discount_percent'] : 0;

        if ($discountPercent < 0 || $discountPercent > 100) {
            board::error('Процент скидки должен быть от 0 до 100');
        }

        $normalizeDate = function ($val) {
            $val = trim((string)$val);
            if ($val === '') {
                return '';
            }
            $val = str_replace('T', ' ', $val);
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $val)) {
                $val .= ':00';
            }
            return $val;
        };

        $discountFrom = $normalizeDate($discountFrom);
        $discountTo = $normalizeDate($discountTo);
        $data = [
            'startpack_limit_seconds' => (string)$packLimit,
            'startpack_discount_from' => $discountFrom,
            'startpack_discount_to' => $discountTo,
            'startpack_discount_percent' => (string)$discountPercent,
        ];
        try {
            $serverModel = server::getServer($serverId);
            if ($serverModel) {
                $serverModel->setServerData('startpack_settings', $data);
            }
        } catch (Exception $e) {
            board::error('Error updating server model: ' . $e->getMessage());
        }

        board::success(lang::get_phrase(581));
    }

    private static function computeTotalPriceFromSettings(int $serverId, int $originalPrice): int
    {
        $totalPrice = $originalPrice;

        $serverModel = server::getServer($serverId);
        if (!$serverModel) {
            return $totalPrice;
        }

        $startpack_settings = $serverModel->getServerData('startpack_settings');
        if ($startpack_settings === null) {
            return $totalPrice;
        }

        $settings = json_decode($startpack_settings->getVal());
        $cooldownSeconds = (int)($settings->startpack_limit_seconds ?? 0);

        $nowTime = (new \DateTime('now', \Ofey\Logan22\component\time\time::getServerTimezone()))->format('Y-m-d H:i:s');

        if ($cooldownSeconds > 0) {
            $log = userlog::get_last_log(user::self()->getId(), logTypes::LOG_BUY_START_PACK, 1);
            if ($log) {
                $timeBuyStartPack = $log['time'];
                $seconds = time::diff($nowTime, $timeBuyStartPack);
                if ($seconds < $cooldownSeconds) {
                    $waitStr = time::timeHasPassed($cooldownSeconds - $seconds, true);
                    board::error(lang::get_phrase('startpack_cooldown', $waitStr));
                }
            }
        }

        // Discounts
        $discountFromRaw = $settings->startpack_discount_from ?? '';
        $discountToRaw = $settings->startpack_discount_to ?? '';
        $discountPercent = isset($settings->startpack_discount_percent) ? (int)$settings->startpack_discount_percent : 0;

        $nowTs = strtotime($nowTime);
        if ($nowTs === false) {
            $nowTs = time();
        }
        $fromTs = ($discountFromRaw !== '') ? strtotime((string)$discountFromRaw) : false;
        $toTs = ($discountToRaw !== '') ? strtotime((string)$discountToRaw) : false;
        $discountActive = false;
        if ($fromTs !== false && $toTs !== false) {
            if ($nowTs >= $fromTs && $nowTs <= $toTs) {
                $discountActive = true;
            }
        } elseif ($fromTs !== false) {
            if ($nowTs >= $fromTs) {
                $discountActive = true;
            }
        } elseif ($toTs !== false) {
            if ($nowTs <= $toTs) {
                $discountActive = true;
            }
        }
        if ($discountActive && $discountPercent > 0) {
            $totalPrice = (int)round($originalPrice * (100 - $discountPercent) / 100);
        }

        return $totalPrice;
    }

}