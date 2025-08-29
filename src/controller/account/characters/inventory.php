<?php

namespace Ofey\Logan22\controller\account\characters;

use Exception;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\item\item;
use Ofey\Logan22\model\log\logTypes;
use Ofey\Logan22\model\user\user;

class inventory
{
    /**
     * Разбитие предмета инвентаря
     */
    public static function splitItem(): void
    {
        $objectItem = $_POST['objectId'] ?? board::error(lang::get_phrase('no_items_provided'));
        $count = $_POST['quantity'] ?? board::error(lang::get_phrase('no_quantity_provided'));

        // Проверяем $_POST['quantity'] на валидность INT значению
        if (!filter_var($count, FILTER_VALIDATE_INT) || $count <= 0) {
            board::error(lang::get_phrase('Enter an integer'));
            return;
        }

        // Также проверяем objectItem на валидность INT значению
        if (!filter_var($objectItem, FILTER_VALIDATE_INT) || $objectItem <= 0) {
            board::error(lang::get_phrase('Enter an integer'));
            return;
        }

        $objectItem = (int)$objectItem;
        $count = (int)$count;
        $userId = user::self()->getId();
        $cooldownKey = 'split_item_' . $userId;
        $currentTime = time();
        $cooldownTime = 2; // секунд

        // Проверяем cooldown
        if (isset($_SESSION[$cooldownKey])) {
            $timeSinceLastSplit = $currentTime - $_SESSION[$cooldownKey];
            if ($timeSinceLastSplit < $cooldownTime) {
                $remainingTime = $cooldownTime - $timeSinceLastSplit;
                board::error("Слишком частое разбитие предметов. Подождите еще {$remainingTime} сек.");
                return;
            }
        }

        $_SESSION[$cooldownKey] = $currentTime;

        $db = sql::instance();
        if (!$db) {
            board::error("DB ERROR CONNECT");
            return;
        }

        // Создаем уникальный ключ для блокировки
        $lockKey = 'split_item_' . $userId . '_' . $objectItem;

        $db->beginTransaction();
        try {
            // Устанавливаем именованную блокировку
            $lockResult = sql::getValue("SELECT GET_LOCK(?, 5)", [$lockKey]);
            if ($lockResult != 1) {
                board::error('Операция разбития уже обрабатывается. Подождите немного.');
                return;
            }

            // Проверка что пользователь ввел от двух предметов
            if ($count < 1) {
                board::error(lang::get_phrase('minimum_one_item'));
                return;
            }

            $item = null;
            foreach (user::self()->getWarehouse() as $object) {
                if ($object->getId() == $objectItem) {
                    $item = $object;
                    break;
                }
            }

            if ($item == null) {
                board::error(lang::get_phrase('item_not_found'));
                return;
            }

            // Проверяем что предмет можно стаковать/растаковать
            if (!$item->getItem()->getIsStackable()) {
                board::error(lang::get_phrase('item_not_stackable'));
                return;
            }

            $isOk = false;
            // Проверяем что можно это делать
            if (\Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->stackableItem()->isAllowAllItemsSplitting()) {
                $isOk = true;
            }

            if (!$isOk) {
                foreach (\Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->stackableItem()->getSplittableItems() as $splitableItem) {
                    if ($item->getItemId() == $splitableItem) {
                        $isOk = true;
                        break;
                    }
                }
            }

            if (!$isOk) {
                board::error(lang::get_phrase('item_magic_protected'));
                return;
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
                return;
            }

            // Проверяем количество предметов
            if ($item->getCount() <= $count) {
                board::error(lang::get_phrase('not_enough_items'));
                return;
            }

            // Выполняем разбитие
            user::self()->removeWarehouseObjectId($objectItem);
            $item_split_1 = $item->getCount() - $count;
            user::self()->addToWarehouse(0, $item->getItemId(), $item_split_1, $item->getEnchant(), $item->getPhrase());
            user::self()->addToWarehouse(0, $item->getItemId(), $count, $item->getEnchant(), $item->getPhrase());
            user::self()->addLog(logTypes::LOG_ITEM_SPLITED, "item_split", [$item->getItemId(), $item_split_1, $count]);

            $db->commit();

            user::self()->getWarehouse(true);
            board::alert([
                "ok" => true,
                "message" => lang::get_phrase('item_split_success'),
                "warehouse" => user::self()->getWarehouseToArray(),
                "isAllowAllItemsSplitting" => \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->stackableItem()->isAllowAllItemsSplitting(),
                "splittableItems" => \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->stackableItem()->getSplittableItems(),
            ]);

        } catch (Exception $e) {
            $db->rollback();
            board::error("Ошибка при разбитии предмета: " . $e->getMessage());
        } finally {
            // Освобождаем блокировку в любом случае
            sql::run("SELECT RELEASE_LOCK(?)", [$lockKey]);
        }
    }

    private static function validateIntArray(string $key, string $errorMessage): array
    {
        $input = filter_input(INPUT_POST, $key, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);

        if (empty($input) || !is_array($input)) {
            board::error($errorMessage);
        }

        foreach ($input as $value) {
            if (filter_var($value, FILTER_VALIDATE_INT) === false || (int)$value <= 0) {
                board::error($errorMessage);
            }
        }

        return array_map('intval', $input);
    }

    public static function warehouseToGame(): void
    {
        $objectItems = $_POST['items'] ?? board::error(lang::get_phrase('no_items_provided'));
        $account = $_POST['account'] ?? board::error(lang::get_phrase('no_account_provided'));
        $player = $_POST['player'] ?? board::error(lang::get_phrase('no_player_name_provided'));

        $userId = user::self()->getId();
        $cooldownKey = 'warehouse_to_game_' . $userId;
        $currentTime = time();
        $cooldownTime = 5; // секунд

        // Проверяем cooldown
        if (isset($_SESSION[$cooldownKey])) {
            $timeSinceLastTransfer = $currentTime - $_SESSION[$cooldownKey];
            if ($timeSinceLastTransfer < $cooldownTime) {
                $remainingTime = $cooldownTime - $timeSinceLastTransfer;
                board::error("Слишком частые переводы. Подождите еще {$remainingTime} сек.");
                return;
            }
        }

        $_SESSION[$cooldownKey] = $currentTime;

        $db = sql::instance();
        if (!$db) {
            board::error("DB ERROR CONNECT");
            return;
        }

        // Создаем уникальный ключ для блокировки
        $lockKey = 'warehouse_transfer_' . $userId . '_' . md5($account . $player . serialize($objectItems));

        $db->beginTransaction();
        try {
            // Устанавливаем именованную блокировку
            $lockResult = sql::getValue("SELECT GET_LOCK(?, 5)", [$lockKey]);
            if ($lockResult != 1) {
                board::error('Перевод уже обрабатывается. Подождите немного.');
                return;
            }

            self::validateIntArray('items', lang::get_phrase('no_items_provided'));

            // Проверяем существование аккаунта
            $foundAccount = false;
            foreach (user::self()->getAccounts() as $accountObj) {
                if ($accountObj->getAccount() === $account) {
                    $foundAccount = true;
                    break;
                }
            }
            if (!$foundAccount) {
                board::error(lang::get_phrase('There is no such account'));
                return;
            }

            // Проверяем существование игрока
            if (!user::self()->isPlayer($player)) {
                board::error(lang::get_phrase('There is no such player in the account'));
                return;
            }

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
                board::error(lang::get_phrase('items_not_found_in_warehouse'));
                return;
            }

            $itemTxt = "";
            foreach ($arrObjectItems as $item) {
                $itemData = item::getItem($item['itemId']);
                $itemTxt .= "+" . $item['enchant'] . " " . $itemData->getItemName() . " (" . $item['count'] . ")<br>";
            }


            // Отправляем предметы в игру
            $json = server::send(type::INVENTORY_TO_GAME, [
                'items' => $arrObjectItems,
                'player' => $player,
                'account' => $account,
                'email' => user::self()->getEmail(),
            ])->show()->getResponse();
            // file_put_contents('test.json', $json);
            if (isset($json['data']) && $json['data'] === true) {
                // Удаляем предметы со склада только после успешной отправки
                $objectItems = $json['objects'];
                user::self()->removeWarehouseObjectId($objectItems);

                // Логируем операции
                foreach ($arrObjectItems as $item) {
                    user::self()->addLog(logTypes::LOG_INVENTORY_TO_GAME, "LOG_INVENTORY_TO_GAME", [$account, $item['itemId'], $item['count']]);
                }

                // Отправка уведомления в Telegram
                if (\Ofey\Logan22\controller\config\config::load()->notice()->isSendWarehouseToGame()) {
                    $itemTxt = trim($itemTxt, "<br>");
                    $template = lang::get_other_phrase(
                        \Ofey\Logan22\controller\config\config::load()->notice()->getNoticeLang(),
                        'notice_warehouse_to_player'
                    );
                    $msg = strtr($template, [
                        '{email}' => user::self()->getEmail(),
                        '{player}' => $player,
                        '{items}' => $itemTxt,
                    ]);
                    telegram::sendTelegramMessage($msg, config::load()->notice()->getSendWarehouseToGameThreadId());
                }

                $db->commit();

                board::alert([
                    "type" => "notice",
                    "ok" => true,
                    'sphereCoin' => user::self()->getDonate(),
                    "message" => lang::get_phrase("Transferred to player", $player),
                    "removeObject" => $arrObjectItems,
                    "countWarehouseItems" => user::self()->countWarehouseItems(),
                ]);
                return;
            }

            // Обработка ошибок
            if (isset($json['error']) && $json['error'] !== "") {
                board::error("Ошибка сервера: " . $json['error']);
                return;
            }

            board::error("No Data Error");

        } catch (Exception $e) {
            $db->rollback();
            board::error("Ошибка при переводе: " . $e->getMessage());
        } finally {
            // Освобождаем блокировку в любом случае
            sql::run("SELECT RELEASE_LOCK(?)", [$lockKey]);
        }
    }

    public static function sendToGame(): void
    {
        if (!config::load()->enabled()->isEnableSendBalanceGame()) {
            board::error("Disabled");
            return;
        }

        $requiredParams = [
            'account' => 'Account not transferred',
            'player' => 'Player name not passed',
            'coin' => 'Coins not transferred'
        ];

        foreach ($requiredParams as $param => $errorMsg) {
            if (empty($_POST[$param])) {
                board::error(lang::get_phrase($errorMsg));
                return;
            }
        }

        $account = $_POST['account'];
        $player = $_POST['player'];
        $coins = $_POST['coin'] ?? null;

        if (!filter_var($coins, FILTER_VALIDATE_INT) || $coins <= 0) {
            board::error(lang::get_phrase('Enter an integer'));
            return;
        }

        $coins = (int)$coins;
        $userId = user::self()->getId();
        $cooldownKey = 'send_to_game_' . $userId;
        $currentTime = time();
        $cooldownTime = 10; // секунд

        // Проверяем cooldown
        if (isset($_SESSION[$cooldownKey])) {
            $timeSinceLastTransfer = $currentTime - $_SESSION[$cooldownKey];
            if ($timeSinceLastTransfer < $cooldownTime) {
                $remainingTime = $cooldownTime - $timeSinceLastTransfer;
                board::error("Слишком частые переводы. Подождите еще {$remainingTime} сек.");
                return;
            }
        }

        $_SESSION[$cooldownKey] = $currentTime;

        $db = sql::instance();
        if (!$db) {
            board::error("DB ERROR CONNECT");
            return;
        }

        // Создаем уникальный ключ для блокировки
        $lockKey = 'money_transfer_' . $userId . '_' . md5($account . $player . $coins);

        $db->beginTransaction();
        try {
            // Устанавливаем именованную блокировку
            $lockResult = sql::getValue("SELECT GET_LOCK(?, 10)", [$lockKey]);
            if ($lockResult != 1) {
                board::error('Перевод уже обрабатывается. Подождите немного.');
                return;
            }

            // Проверяем наличие аккаунта
            if (user::self()->getAccounts($account) === null) {
                board::error(lang::get_phrase('There is no such account'));
                return;
            }

            // Проверяем существование игрока в аккаунте
            if (!user::self()->isPlayer($player)) {
                board::error(lang::get_phrase('There is no such player in the account'));
                return;
            }

            $serverDonate = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate();
            $countItemsToGameTransfer = $serverDonate->getCountItemsToGameTransfer() * $coins / $serverDonate->getDonateItemToGameTransfer();

            if (fmod($countItemsToGameTransfer, 1) !== 0.0) {
                board::error(lang::get_phrase('Enter a multiple value. For example', (int)$countItemsToGameTransfer * $serverDonate->getDonateItemToGameTransfer()));
                return;
            }

            // КРИТИЧНО: Атомарно списываем деньги ПЕРЕД отправкой в игру
            $stmt = sql::run(
                'UPDATE users SET donate_point = donate_point - ? WHERE id = ? AND donate_point >= ?',
                [$coins, $userId, $coins]
            );

            if ($stmt->rowCount() === 0) {
                // Если не удалось списать деньги, значит баланс недостаточен
                board::error(lang::get_phrase('Not enough coins'));
                return;
            }

            $items[] = [
                'objectId' => 0,
                'itemId' => $serverDonate->getItemIdToGameTransfer(),
                'count' => $countItemsToGameTransfer,
                'enchant' => 0,
            ];

            // Отправляем предметы в игру ПОСЛЕ списания денег
            $json = server::send(type::INVENTORY_TO_GAME, [
                'items' => $items,
                'player' => $player,
                'account' => $account,
                'email' => user::self()->getEmail(),
            ])->show()->getResponse();

            if (isset($json['error']) && $json['error'] !== "") {
                // Если отправка не удалась, ВОЗВРАЩАЕМ деньги
                sql::run(
                    'UPDATE users SET donate_point = donate_point + ? WHERE id = ?',
                    [$coins, $userId]
                );
                board::error("Ошибка сервера: " . $json['error']);
                return;
            }

            if (isset($json['data']) && $json['data'] === true) {
                // Отправка уведомления в Telegram
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

                user::self()->addLog(logTypes::LOG_DONATE_COIN_TO_GAME, "LOG_DONATE_COIN_TO_GAME", [$player, $serverDonate->getItemIdToGameTransfer(), $countItemsToGameTransfer]);

                $db->commit();

                // Получаем актуальный баланс
                $currentDonate = sql::getValue('SELECT donate_point FROM users WHERE id = ?', [$userId]);

                board::alert([
                    "type" => "notice",
                    "ok" => true,
                    "message" => lang::get_phrase("Transferred to player", $player),
                    'sphereCoin' => $currentDonate,
                ]);
                return;
            }

            sql::run(
                'UPDATE users SET donate_point = donate_point + ? WHERE id = ?',
                [$coins, $userId]
            );
            board::error("No Data Error");

        } catch (Exception $e) {
            $db->rollback();
            board::error("Ошибка при переводе: " . $e->getMessage());
        } finally {
            sql::run("SELECT RELEASE_LOCK(?)", [$lockKey]);
        }
    }
}