<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 05.09.2022 / 17:41:12
 */

namespace Ofey\Logan22\model\donate;

use Exception;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\image\client_icon;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\request\ip;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\log\logTypes;
use Ofey\Logan22\model\referral\referral;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;
use PDOStatement;

class donate
{

    private static int $COOLDOWN_SECONDS = 5;

    public static function get_shop_items(): array
    {
        $server_id = user::self()->getServerId();
        if ( ! $server_id) {
            echo 'Server is not set';
            exit;
        }
        $shopItems = sql::getRows('SELECT * FROM `donate` WHERE server_id = ?', [$server_id]);
        $items     = [];
        foreach ($shopItems as $shopItem) {
            $item = new shop();
            $item->setId($shopItem['id']);
            $item->setCost($shopItem['cost']);
            $item->setCount($shopItem['count']);
            $item->setItemId($shopItem['item_id']);
            $item->setIsPack($shopItem['is_pack']);
            $item->setIsPack($shopItem['is_pack']);
            $item->setPackName($shopItem['pack_name']);
            $item->setItemInfo($shopItem['item_id']);
            $items[] = $item;
        }

        return $items;
    } // Время задержки в секундах до последующей попытки купить что-то

    /**
     * @param           $uuid             - Индификатор
     * @param   string  $pay_system_name  - Название платежной системы
     *
     * @return void
     * @throws Exception
     *
     * Проверяем существование индефикатора и сохраняем его.
     * Если индификатор уже был в системе, тогда останавливаем зачисление.
     */
    public static function control_uuid($uuid = null, string $pay_system_name = 'NoNamePaySystem'): void
    {
        if ($uuid === null) {
            return;
        }
        if (self::get_uuid($uuid, $pay_system_name)) {
            die('This UUID was previously determined');
        }
        self::set_uuid($uuid, $pay_system_name);
    }

    /**
     * @param $uuid
     * @param $pay_system_name
     *
     * @return mixed
     *
     * Чтение для проверки уже ранее записыванных индификаторов транзакций от платежных системах.
     *
     * Это создано с целью безопастности, на тот случай, если платежная система НЕ предоставляет данных
     * о своих IP и не имеет криптографических ключей для проверки подлинности транзакции
     */
    public static function get_uuid($uuid, $pay_system_name): mixed
    {
        return sql::getRow("SELECT * FROM `donate_uuid` WHERE uuid = ? AND pay_system = ?", [$uuid, $pay_system_name]);
    }

    /**
     * @param $uuid
     * @param $pay_system_name
     *
     * @return false|\PDOStatement|null
     * @throws Exception
     *
     * Записываем сюда уникальные ID транзакций от платежных системах.
     *
     * Это создано с целью безопастности, на тот случай, если платежная система НЕ предоставляет данных
     * о своих IP и не имеет криптографических ключей для проверки подлинности транзакции
     */
    public static function set_uuid($uuid, $pay_system_name): false|PDOStatement|null
    {
        $request = '';
        if (isset($_REQUEST) && ! empty($_REQUEST)) {
            $request = json_encode($_REQUEST, JSON_UNESCAPED_UNICODE);
        }

        return sql::sql(
          "INSERT INTO `donate_uuid` (`uuid`, `pay_system`, `ip`, `request`, `date`) VALUES (?, ?, ?, ?, ?);",
          [$uuid, $pay_system_name, ip::getIp(), $request, time::mysql()]
        );
    }

    /**
     * Список товаров для покупки за донат очки
     *
     * @return array
     * @throws Exception
     */
    static public function products()
    {
        $server_id = auth::get_default_server();
        if ( ! $server_id) {
            tpl::addVar("message", "Not Server");
            tpl::display("page/error.html");
        }
        $donate = sql::getRows("SELECT * FROM `donate` WHERE server_id = ? ORDER BY id DESC", [
          $server_id,
        ]);
        foreach ($donate as &$item) {
            //Если установлен пак
            if ($item['is_pack']) {
                $item['pack'] = [];
                $packData     = self::get_pack($item['id']);
                foreach ($packData as $pack_item) {
                    $item_info          = client_icon::get_item_info($pack_item['item_id'], false, false);
                    $item_info['count'] = $pack_item['count'];
                    $item['pack'][]     = $item_info;
                }
            } else {
                $item_info = client_icon::get_item_info($item['item_id'], false, false);
                if ( ! $item_info) {
                    $item_info['item_id'] = $item['id'];
                    $item_info['name']    = "No Item Name";
                    $item_info['icon']    = fileSys::localdir("/uploads/images/icon/NOIMAGE.webp");
                }
                $item = array_merge($item, $item_info);
            }
        }

        return $donate;
    }

    public static function get_pack($pack_id): array
    {
        return sql::getRows("SELECT * FROM `donate_pack` WHERE pack_id = ?", [$pack_id]);
    }

    /*
     * При покупке пользователь
     */
    public static function toWarehouse()
    {
        $db = sql::instance(); // Получаем экземпляр вашего класса для работы с БД.
        $db->beginTransaction(); // Начало транзакции
        try {
            if (auth::get_donate_point() < 0) {
                board::notice(false, "Not enough money");
            }
            $lastUsage = $_SESSION['COOLDOWN_DONATE_TRANSACTION'] ?? 0;
            if (time() - $lastUsage < self::$COOLDOWN_SECONDS) {
                board::error("Покупка разрешена только раз в " . self::$COOLDOWN_SECONDS . " сек.", next: true);
                $db->rollback();

                return;
            }
            $_SESSION['COOLDOWN_DONATE_TRANSACTION'] = time();

            $shopId    = $_POST['shopId'] ?? board::error("No Shop ID");
            $quantity  = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT) ?? 1;
            $shopItems = donate::getShopItems($shopId, false);
            if ( ! $shopItems) {
                board::error("Магазин не найден", next: true);
                $db->rollback();

                return;
            }
            /**
             * Проверка стаковых предметов
             * Если в наборе есть что-то что нельзя стакать, тогда запретить покупать больше 1 покупки за раз.
             */
            $isStackable = true;
            foreach ($shopItems as $item) {
                if ( ! $item->getItemInfo()->getIsStackable()) {
                    $isStackable = false;
                    break;
                }
            }
            if ( ! $isStackable) {
                if ($quantity > 1) {
                    board::error("Эта закупка не может быть стакнутой", next: true);
                    $db->rollback();

                    return;
                }
            }
            /**
             * Подсчитываем общую сумму покупки товара
             */
            $groupPrice = self::sumGetValue($shopItems, 'getCost');
            /**
             * Проверяем есть ли у пользователя N денег на аккаунте
             */
            $totalPrice        = $groupPrice * $quantity;
            $canAffordPurchase = user::self()->canAffordPurchase($totalPrice);
            if ( ! $canAffordPurchase) {
                board::error(sprintf("Для покупки у Вас нехватает %s SphereCoin", $totalPrice), next: true);
                $db->rollback();

                return;
            }

            user::self()->donateDeduct($totalPrice);

            /**
             * Отправляем предметы в склад
             */
            foreach ($shopItems as $item) {
                $server_info = server::getServer($item->getServerId());
                if ( ! $server_info) {
                    board::error(lang::get_phrase(150), next: true);
                    break;
                }
                if ($item->getCount() * $quantity >= 2_000_000_000) {
                    board::error("Кол-во покупаемых предметов не может быть больше 2ккк", next: true);
                    $db->rollBack();

                    return;
                }
                $data = user::self()->addToWarehouse(
                  $server_info->getId(),
                  $item->getItemId(),
                  $item->getCount() * $quantity,
                  $item->getEnchant(),
                  "purchase"
                );
                if ( ! $data['success']) {
                    if (user::self()->isAdmin()) {
                        board::error($data['errorInfo']['message'], next: true);
                    } else {
                        board::error(lang::get_phrase(487), next: true);
                    }
                    $db->rollback();

                    return;
                }
            }

            $db->commit();
            board::alert([
              'type'       => 'notice',
              'ok'         => true,
              'message'    => lang::get_phrase(304),
              'sphereCoin' => user::self()->getDonate(),
            ]);
        } catch (Exception $e) {
            $db->rollback();
            board::error($e->getMessage());
        }
    }

    /*
     * Покупка предмета, передача предмета игровому персонажу
     */

    /**
     * Получение информации о товарах в магазине.
     *
     * @param   int|null  $shopId  Идентификатор магазина
     *
     * @return shop[]|null Массив содержит объекты класса shop, индексированный ID магазина
     */
    static public function getShopItems(int $shopId = null, $toArray = true): ?array
    {
        $shopInfo = [];

        if ($shopId) {
            $sql  = 'SELECT * FROM `shop_items` WHERE id = ? AND serverId = ?';
            $shop = sql::getRow($sql, [$shopId, user::self()->getServerId()]);
            if ( ! $shop) {
                return null; // Возвращаем null, если ничего не найдено
            }
            $shop['items'] = json_decode($shop['items'], true);
            if ( ! is_array($shop['items'])) {
                return null;
            }
            $serverId = $shop['serverId'];
            foreach ($shop['items'] as $item) {
                $shopObj = new shop();
                $shopObj->setId($item['objectId']);
                $shopObj->setServerId($serverId);
                $shopObj->setCost($item['cost']);
                $shopObj->setCount($item['count']);
                $shopObj->setItemId($item['itemId']);
                $shopObj->setEnchant($item['enchant']);
                $shopObj->setItemInfo($item['itemId']);
                if ($toArray) {
                    $shopInfo[] = $shopObj->toArray();
                } else {
                    $shopInfo[] = $shopObj;
                }
            }
        } else {
            $sql  = 'SELECT * FROM `shop_items` WHERE serverId = ?';
            $shop = sql::getRows($sql, [user::self()->getServerId()]);
            if ( ! $shop) {
                return null;
            }
            foreach ($shop as &$row) {
                $row['items'] = json_decode($row['items'], true);
            }
            foreach ($shop as $items) {
                if ( ! $items['items']) {
                    continue;
                }
                $objectID = $items['id'];
                $serverId = $items['serverId'];
                foreach ($items['items'] as $item) {
                    $shopObj = new shop();
                    $shopObj->setId($item['objectId']);
                    $shopObj->setServerId($serverId);
                    $shopObj->setCost($item['cost']);
                    $shopObj->setCount($item['count']);
                    $shopObj->setItemId($item['itemId']);
                    $shopObj->setEnchant($item['enchant']);
                    $shopObj->setItemInfo($item['itemId']);
                    $shopInfo[$objectID][] = $shopObj;
                }
            }
        }

        return $shopInfo;
    }

    //Имлементация отправки на персонажа

    /**
     * @param $array
     * @param $methodName
     *
     * @return int
     */
    public static function sumGetValue($array, $methodName): int
    {
        $sum = 0;
        if (is_object($array)) {
            // Преобразуем объект в массив
            $array = [get_object_vars($array)];
        }
        foreach ($array as $item) {
            if (is_object($item) && method_exists($item, $methodName)) {
                $sum += $item->$methodName();
            }
        }

        return $sum;
    }

    public static function buyShopItem(): void
    {
        if (user::self()->getCountPlayers() == 0) {
            board::error("У Вас нет персонажей");
        }
        $db = sql::instance();
        if ( ! $db) {
            board::error("Ошибка подключения к базе данных.");

            return;
        }
        $db->beginTransaction();
        try {
            //Формальная проверка, что у пользователя вообще есть ли деньги.
            if (auth::get_donate_point() < 0) {
                board::notice(false, "Not enough money");
            }
            $lastUsage = $_SESSION['COOLDOWN_DONATE_TRANSACTION'] ?? 0;
            if (time() - $lastUsage < self::$COOLDOWN_SECONDS) {
                board::error("Покупка разрешена только раз в " . self::$COOLDOWN_SECONDS . " сек.");
            }
            $_SESSION['COOLDOWN_DONATE_TRANSACTION'] = time();

            $shopId   = $_POST['shopId'] ?? board::error("No Shop ID");
            $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT) ?? 1;

            $playerName = $_POST['playerName'] ?? null;
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
            //Проверяем существование персонажа
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

            $shopItems = donate::getShopItems($shopId, false);
            if ( ! $shopItems) {
                board::error("Магазин не найден");
            }

            $server_info = server::getServer($shopItems[0]->getServerId());
            if ( ! $server_info) {
                board::notice(false, lang::get_phrase(150));
            }

            /**
             * Проверка стаковых предметов
             * Если в наборе есть что-то что нельзя стакать, тогда запретить покупать больше 1 покупки за раз.
             */
            $isStackable = true;
            foreach ($shopItems as $item) {
                if ( ! $item->getItemInfo()->getIsStackable()) {
                    $isStackable = false;
                    break;
                }
            }
            if ( ! $isStackable) {
                if ($quantity > 1) {
                    board::error("Эта закупка не может быть стакнутой");
                }
            }

            /**
             * Подсчитываем общую сумму покупки товара
             */
            $groupPrice = self::sumGetValue($shopItems, 'getCost');

            /**
             * Проверяем есть ли у пользователя N денег на аккаунте
             */
            $totalPrice        = $groupPrice * $quantity;
            $canAffordPurchase = user::self()->canAffordPurchase($totalPrice);
            if ( ! $canAffordPurchase) {
                board::error(sprintf("Для покупки у Вас нехватает %s SphereCoin", $totalPrice));
            }

            /**
             * Списываем сумму
             */
            user::self()->donateDeduct($totalPrice);

            /**
             * Зачисляем предметы на персонажа
             */

            $arrObjectItems = [];
            foreach ($shopItems as $item) {
                $arrObjectItems[] = [
                  'objectID' => $item->getId(),
                  'count'    => $item->getCount(),
                  'enchant'  => $item->getEnchant(),
                  'itemId'   => $item->getItemId(),
                ];
            }

            $json = \Ofey\Logan22\component\sphere\server::send(type::INVENTORY_TO_GAME, [
              'items'   => $arrObjectItems,
              'player'  => $playerName,
              'account' => $account,
              'email'   => user::self()->getEmail(),
            ])->show()->getResponse();
            if (isset($json['data']) && $json['data'] === true) {
                $objectItems = $json['objects'];
                user::self()->removeWarehouseObjectId($objectItems);
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

            //Проверяем что магазин относится к выбранному пользователем серверу
            //        if(user::self()->getServerId() == $shop)

            //        var_dump($shop);exit();
            //        $donat_info = self::donate_item_info($id, $server_id);
            //        if (!$donat_info) {
            //            board::notice(false, lang::get_phrase(152));
            //        }
            //        if(isset($donat_info['is_pack'])){
            //            $user_value = 1;
            //        }
            //        $donat_info_cost = $donat_info['cost'];
            //        $cost_product = $donat_info_cost * $user_value;

            //Проверка на скидку по товару
            //        $donateInfo = __config__donate;
            //
            //        if ($donateInfo['DONATE_DISCOUNT_TYPE_PRODUCT_ENABLE']) {
            //            $procentDiscount = donate::getBonusDiscount(auth::get_id(), $donateInfo['discount_product']['table']);
            //            $decrease_factor = 1 - ($procentDiscount / 100);
            //            $cost_product *= $decrease_factor;
            //        }
            //
            //        if ($donateInfo['DONATE_DISCOUNT_COUNT_ENABLE']) {
            //            $discount_count_product_table = $donateInfo["discount_count_product"]['table'] ?? [];
            //            $discount_count_product_items = $donateInfo["discount_count_product"]['items'] ?? [];
            //            if (in_array($donat_info['item_id'], $discount_count_product_items) or empty($discount_count_product_items)) {
            //                $procentDiscount = self::findValueForN($user_value, $discount_count_product_table);
            //                if ($procentDiscount) {
            //                    $decrease_factor = 1 - ($procentDiscount / 100);
            //                    $cost_product *= $decrease_factor;
            //                }
            //            }
            //        }
            //        if ((auth::get_donate_point() - $cost_product) < 0) {
            //            board::notice(false, lang::get_phrase(149, $cost_product, auth::get_donate_point()));
            //        }
            //
            //        $addToUserItems = $donat_info['count'] * $user_value;

            //            $player_info = player_account::is_player($server_info, [$playerName]);
            //            $player_info = $player_info->fetch();
            //            if (!$player_info){
            //                board::notice(false, lang::get_phrase(151, $playerName));
            //            }
            //            foreach ($shopItems as $item) {
            //                if (!self::sending_implementation($server_info, $player_info, $playerName, $player_info["player_id"], $item->getItemInfo()->getIsStackable(), $item->getItemId(), $item->getCount())) {
            //                    $db->rollBack();
            //                    return;
            //                }
            //            }

            $db->commit();
            board::alert([
              'type'       => 'notice',
              'ok'         => true,
              'message'    => lang::get_phrase(304),
              'sphereCoin' => user::self()->getDonate(),
            ]);
        } catch (Exception $e) {
            $db->rollback();
            board::error($e->getMessage());
        }
    }

    public static function taking_money($dp, $user_id)
    {
        //        if(auth::get_donate_point() < 0){
        //            board::notice(false, "Not enough money");
        //        }
        //        if(auth::get_donate_point() == 0){
        //            board::notice(false, "Вам необходимо иметь на балансе {$dp} SphereCoin");
        //        }
        //        if ((auth::get_donate_point() - $dp) >= 0) {
        //            sql::run("UPDATE `users` SET `donate_point` = `donate_point`-? WHERE `id` = ?", [
        //                $dp,
        //                $user_id,
        //            ]);
        //            auth::set_donate_point(auth::get_donate_point() - $dp);
        //        }else{
        //            board::error("Ошибка");
        //        }
    }

    //Уменьшение коинов

    public static function donate_history_pay_self($user_id = null): array
    {
        if ( ! $user_id) {
            $user_id = auth::get_id();
        }
        $pays = sql::getRows(
          "SELECT
                                donate_history_pay.point, 
                                donate_history_pay.message,
                                donate_history_pay.pay_system,
                                donate_history_pay.id_admin_pay,
                                donate_history_pay.date
                            FROM
                                donate_history_pay
                            WHERE
                                donate_history_pay.user_id = ?
                            ORDER BY
                                donate_history_pay.id DESC", [
            $user_id,
          ]
        );
        foreach ($pays as &$pay) {
            if ( ! empty($pay['id_admin_pay'])) {
                $admin             = auth::get_user_info($pay['id_admin_pay']);
                $pay['admin_name'] = $admin['name'];
            }
        }
        $trs    = sql::getRows(
          "SELECT
                                        log_transfer_spherecoin.*, 
                                        sender.`name` AS sender_name,
                                        receiver.`name` AS receiver_name
                                    FROM
                                        log_transfer_spherecoin
                                    LEFT JOIN
                                        users AS sender
                                        ON log_transfer_spherecoin.user_sender = sender.id
                                    LEFT JOIN
                                        users AS receiver
                                        ON log_transfer_spherecoin.user_receiving = receiver.id
                                    WHERE
                                        user_sender = ? OR
                                        user_receiving = ?
                                    ORDER BY
                                        log_transfer_spherecoin.id DESC", [$user_id, $user_id]
        );
        $result = array_merge($pays, $trs);
        usort($result, function ($a, $b) {
            return $a['date'] <=> $b['date'];
        });

        return array_reverse($result);
    }

    public static function currency(int|float $sum, string $currency): float|int
    {
        $quantity = config::load()->donate()->getSphereCoinCost();

        return match ($currency) {
            "RUB" => ($sum / config::load()->donate()->getRatioRUB()) * $quantity,
            "UAH" => ($sum / config::load()->donate()->getRatioUAH()) * $quantity,
            "EUR" => ($sum / config::load()->donate()->getRatioEUR()) * $quantity,
            default => ($sum / config::load()->donate()->getRatioUSD()) * $quantity,
        };
    }

    //Сумма зачисления денег с учетом курса валют конфига

    public static function getBonusDiscount($user_id, $table)
    {
        $amount        = sql::run("SELECT SUM(point) AS `count` FROM donate_history_pay WHERE user_id = ? and sphere=0", [$user_id])->fetch(
        )['count'] ?? 0;
        $rangeKey      = null;
        $discountValue = null;
        $lastValue     = null;
        $keys          = array_keys($table);
        $firstKey      = reset($keys);
        if ($amount < $firstKey) {
            return 0;
        } else {
            $reversedTable = array_reverse($table, true);
            foreach ($reversedTable as $key => $value) {
                if ($amount >= $key) {
                    $rangeKey      = $key;
                    $discountValue = $value;
                    break;
                }
                $lastValue = $value;
            }
            if ($rangeKey && $discountValue) {
                return $discountValue;
            } else {
                return $lastValue;
            }
        }
    }

    //Возвращает процент скидки

    public static function findReplenishmentBonus($amount, $bonusTable)
    {
        $matchingKey = 0; // Изначально устанавливаем значение 0
        foreach ($bonusTable as $key => $value) {
            if ($amount >= $key) {
                $matchingKey = $value;
            } else {
                break;
            }
        }

        return $matchingKey;
    }

    // Реализация выдачи бонусов
    public static function addUserBonus($user_id, $sphereCoin)
    {
        $sphereCoin = ceil($sphereCoin);
        // Проверка на выдачу предметов за донат
        if (config::load()->donate()->isRewardForDonatingItems()) {
            self::addDonateItemBonus($user_id, $sphereCoin);
        }
        //Проверка на выдачу единоразных платежей за донат
        if (config::load()->donate()->isEnableOneTimeBonus()) {
            self::AddOneTimeBonus($user_id, $sphereCoin);
        }
        //Выдача бонуса по накопительной системе
        if (config::load()->donate()->isEnableCumulativeDiscountSystem()) {
            self::addCumulativeBonus($user_id, $sphereCoin);
        }

        //Выдача бонусов за рефералку
        if (config::load()->referral()->isEnable()) {
           $masterUser = referral::get_user_leader($user_id);
           if ($masterUser) {
               $bonus = ($sphereCoin * config::load()->referral()->getProcentDonateBonus()) / 100;
               $masterUser->donateAdd($bonus)->AddHistoryDonate($bonus, "Привлеченный Вами игрок пожертвовал и Вы получаете +$bonus бонуса по реферальной системе", "referralBonus");
           }
        }
    }

    private static function addCumulativeBonus($user_id, $sphereCoin)
    {
        $sumDonate    = sql::run("SELECT SUM(point) AS `count` FROM donate_history_pay WHERE user_id = ? and sphere=0", [$user_id])->fetch(
        )['count'] ?? 0;
        $donateConfig = config::load()->donate();
        $bonusTable   = $donateConfig->getTableCumulativeDiscountSystem();
        $percent      = 0;
        foreach ($bonusTable as $row) {
            if ($sumDonate >= $row['coin']) {
                $percent = $row['percent'];
            } else {
                break;
            }
        }
        if ($percent == 0) {
            return;
        }
        $addSphereCoin = ($sphereCoin * $percent / 100);
        //TODO: Добавить логирование о действий пользователя
        user::getUserId($user_id)->donateAdd($addSphereCoin)->AddHistoryDonate($addSphereCoin, "Выдача {$percent}% бонуса ($addSphereCoin) по накопительной системе", "cumulativeBonus");
    }

    /**
     * @return false
     * Выдача бонуса предметом, за N сумму доната единоразвым платежем
     */
    public static function AddDonateItemBonus($user_id, $sphereCoin): bool
    {
        //Был ли выдан бонус
        $isAddBonus   = false;
        $donateConfig = config::load()->donate();
        //Проверяем, нужно ли выдавать бонус предметами
        if ($donateConfig->isRewardForDonatingItems()) {
            //Список предметов для выдачи бонуса
            $bonusItems      = false;
            $donateBonusList = $donateConfig->getTableItemsBonus();
            foreach ($donateBonusList as $cost => $bonus) {
                if ($sphereCoin >= $cost) {
                    $bonusItems = $bonus;
                }
            }
            // Если $item есть, то выдаем бонус в склад
            if ($bonusItems) {
                foreach ($bonusItems as $bonus) {
                    $item_id = (int)$bonus['id'];
                    $count   = (int)$bonus['count'] ?? 1;
                    $enchant = (int)$bonus['enchant'] ?? 0;
                    //TODO: Нужно получать server_id из базы данных, по ссылке, которым генерируется оплата
                    //TODO: Добавить логирование о действий пользователя
                    $serverId = user::getUserId($user_id)->getServerId();
                    user::getUserId($user_id)->addLog(logTypes::LOG_ADD_DONATE_ITEM_BONUS, "LOG_ADD_DONATE_ITEM_BONUS", [$item_id, $count, $enchant, $serverId]);
                    user::getUserId($user_id)->addToWarehouse($serverId, $item_id, $count, $enchant, "add_item_donate_bonus");
                }
                $isAddBonus = true;
            }
        }

        return $isAddBonus;
    }

    private static function AddOneTimeBonus($user_id, $sphereCoin)
    {
        //Список предметов для выдачи бонуса
        $bonusData       = false;
        $donateBonusList = config::load()->donate()->getTableEnableOneTimeBonus();
        foreach ($donateBonusList as $bonus) {
            if ($bonus['coin'] <= $sphereCoin) {
                $bonusData = $bonus;
            }
        }
        //Если бонус есть, тогда добавим процент sphereCoin пользователю
        if ($bonusData) {
            //Сумма бонуса в процентах от суммы доната
            $percent       = $bonusData['percent'];
            $addSphereCoin = ($sphereCoin * $percent / 100);
            //TODO: Добавить логирование о действий пользователя
            user::getUserId($user_id)->donateAdd($addSphereCoin)->AddHistoryDonate($addSphereCoin, "Выдача {$percent}% бонуса (+$addSphereCoin) за единоразовый донат", "oneTimeBonus");
        }

        return $bonusData;
    }

    //Выдача бонуса предметом, за N сумму доната единоразвым платежем

    public static function isOnlyAdmin($donateClass): void
    {
        if (method_exists($donateClass, 'forAdmin')) {
            if ($donateClass::forAdmin() and user::self()->getAccessLevel() != 'admin') {
                board::error('Only for Admin');
            }
        }
    }

    /**
     * Получение информации о предмете из БД
     */
    static private function donate_item_info($item_id, $server_id)
    {
        return sql::run("SELECT * FROM donate WHERE id = ? AND server_id = ?", [
          $item_id,
          $server_id,
        ])->fetch();
    }

    /*
     * Подсчет N значений в массиве
     */

    private static function findValueForN($inputN, $keyValueObject = 0)
    {
        if ( ! is_array($keyValueObject)) {
            return 0;
        }
        $result = null;
        foreach ($keyValueObject as $key => $value) {
            $currentKey = (int)$key;
            if ($currentKey > $inputN) {
                break;
            }
            $result = $value;
        }

        return $result;
    }

}