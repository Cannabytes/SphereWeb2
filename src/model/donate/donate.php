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
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\admin\userlog;
use Ofey\Logan22\model\bonus\bonus;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\server\serverModel;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\player\player_account;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class donate {

    public static function get_shop_items(): array {
        $server_id = user::self()->getServerId();
        if(!$server_id){
            echo 'Server is not set';
            exit;
        }
        $shopItems = sql::getRows('SELECT * FROM `donate` WHERE server_id = ?', [$server_id]);
        $items = [];
        foreach ($shopItems AS $shopItem){
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
    }

    private static int $COOLDOWN_SECONDS = 5; // Время задержки в секундах до последующей попытки купить что-то

    /**
     * @param $uuid
     * @param $pay_system_name
     * @return mixed
     *
     * Чтение для проверки уже ранее записыванных индификаторов транзакций от платежных системах.
     *
     * Это создано с целью безопастности, на тот случай, если платежная система НЕ предоставляет данных
     * о своих IP и не имеет криптографических ключей для проверки подлинности транзакции
     */
    public static function get_uuid($uuid, $pay_system_name): mixed {
        return sql::getRow("SELECT * FROM `donate_uuid` WHERE uuid = ? AND pay_system = ?", [$uuid, $pay_system_name]);
    }

    /**
     * @param $uuid
     * @param $pay_system_name
     * @return false|\PDOStatement|null
     * @throws Exception
     *
     * Записываем сюда уникальные ID транзакций от платежных системах.
     *
     * Это создано с целью безопастности, на тот случай, если платежная система НЕ предоставляет данных
     * о своих IP и не имеет криптографических ключей для проверки подлинности транзакции
     */
    public static function set_uuid($uuid, $pay_system_name): false|\PDOStatement|null {
        $request = '';
        if(isset($_REQUEST) && !empty($_REQUEST)) {
            $request = json_encode($_REQUEST, JSON_UNESCAPED_UNICODE);
        }
        return sql::sql("INSERT INTO `donate_uuid` (`uuid`, `pay_system`, `ip`, `request`, `date`) VALUES (?, ?, ?, ?, ?);", [$uuid, $pay_system_name, ip::getIp(), $request, time::mysql()]);
    }

    /**
     * @param $uuid - Индификатор
     * @param string $pay_system_name - Название платежной системы
     * @return void
     * @throws Exception
     *
     * Проверяем существование индефикатора и сохраняем его.
     * Если индификатор уже был в системе, тогда останавливаем зачисление.
     */
    public static function control_uuid($uuid = null, string $pay_system_name = 'NoNamePaySystem'): void {
        if($uuid === null){
            return;
        }
        if(self::get_uuid($uuid, $pay_system_name)){
            die('This UUID was previously determined');
        }
        self::set_uuid($uuid, $pay_system_name);
    }

    /**
     * Список товаров для покупки за донат очки
     *
     * @return array
     * @throws Exception
     */
    static public function products() {
        $server_id = auth::get_default_server();
        if (!$server_id) {
            tpl::addVar("message", "Not Server");
            tpl::display("page/error.html");
        }
        $donate = sql::getRows("SELECT * FROM `donate` WHERE server_id = ? ORDER BY id DESC", [
            $server_id,
        ]);
        foreach ($donate as &$item) {
            //Если установлен пак
            if($item['is_pack']){
                $item['pack'] = [];
                $packData = self::get_pack($item['id']);
                foreach ($packData AS $pack_item){
                    $item_info = client_icon::get_item_info($pack_item['item_id'], false, false);
                    $item_info['count'] = $pack_item['count'];
                    $item['pack'][] = $item_info;
                }
            }else{
                $item_info = client_icon::get_item_info($item['item_id'], false, false);
                if(!$item_info){
                    $item_info['item_id'] = $item['id'];
                    $item_info['name'] = "No Item Name";
                    $item_info['icon'] = fileSys::localdir("/uploads/images/icon/NOIMAGE.webp");
                }
                $item = array_merge($item, $item_info);
            }
        }
        return $donate;
    }

    public static function get_pack($pack_id): array {
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
            if(auth::get_donate_point() < 0){
                board::notice(false, "Not enough money");
            }
            $lastUsage = $_SESSION['COOLDOWN_DONATE_TRANSACTION'] ?? 0;
            if (time() - $lastUsage < self::$COOLDOWN_SECONDS) {
                board::error("Покупка разрешена только раз в " . self::$COOLDOWN_SECONDS . " сек.", next: true);
                $db->rollback();
                return;
            }
            $_SESSION['COOLDOWN_DONATE_TRANSACTION'] = time();

            $shopId = $_POST['shopId'] ?? board::error("No Shop ID");
            $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT) ?? 1;
            $shopItems = donate::getShopItems($shopId, false);
            if(!$shopItems){
                board::error("Магазин не найден", next: true);
                $db->rollback();
                return;
            }
            /**
             * Проверка стаковых предметов
             * Если в наборе есть что-то что нельзя стакать, тогда запретить покупать больше 1 покупки за раз.
             */
            $isStackable = true;
            foreach ($shopItems AS $item){
                if(!$item->getItemInfo()->getIsStackable()){
                    $isStackable = false;
                    break;
                }
            }
            if(!$isStackable){
                if($quantity>1){
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
            $totalPrice = $groupPrice * $quantity;
            $canAffordPurchase = user::self()->canAffordPurchase($totalPrice);
            if(!$canAffordPurchase){
                board::error( sprintf("Для покупки у Вас нехватает %s SphereCoin", $totalPrice ), next: true);
                $db->rollback();
                return;
            }

            user::self()->donateDeduct($totalPrice);

            /**
             * Отправляем предметы в склад
             */
            foreach($shopItems AS $item){
                $server_info = server::getServer($item->getServerId());
                if (!$server_info) {
                    board::error(lang::get_phrase(150), next: true);
                    break;
                }
                if($item->getCount()*$quantity >= 2_000_000_000){
                    board::error("Кол-во покупаемых предметов не может быть больше 2ккк", next: true);
                    $db->rollBack();
                    return;
                }
                $data = user::self()->addToInventory($server_info->getId(), $item->getItemId(), $item->getCount()*$quantity, $item->getEnchant(), 123);
                if (!$data['success']) {
                    if(user::self()->isAdmin()){
                        board::error($data['errorInfo']['message'], next: true);
                    }else{
                        board::error( lang::get_phrase(487), next: true);
                    }
                    $db->rollback();
                    return;
                }
            }

            $db->commit();
            board::alert([
                'type' => 'notice',
                'ok' => true,
                'message' => lang::get_phrase(304),
                'donate_bonus' => user::self()->getDonate(),
            ]);
        } catch (Exception $e) {
            $db->rollback();
            board::error($e->getMessage());
        }

    }

    /*
     * Покупка предмета, передача предмета игровому персонажу
     */
    public static function buyShopItem(): void
    {
        if(user::self()->getCountPlayers() == 0){
            board::error("У Вас нет персонажей");
        }
        $db = sql::instance();
        if (!$db) {
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

            $shopId = $_POST['shopId'] ?? board::error("No Shop ID");
            $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT) ?? 1;

            $playerName = $_POST['playerName'] ?? null;
            if ($playerName == null) {
                board::notice(false, lang::get_phrase(148));
            }
            $shopItems = donate::getShopItems($shopId, false);
            if (!$shopItems) {
                board::error("Магазин не найден");
            }

            $server_info = server::getServer($shopItems[0]->getServerId());
            if (!$server_info) {
                board::notice(false, lang::get_phrase(150));
            }

            /**
             * Проверка стаковых предметов
             * Если в наборе есть что-то что нельзя стакать, тогда запретить покупать больше 1 покупки за раз.
             */
            $isStackable = true;
            foreach ($shopItems as $item) {
                if (!$item->getItemInfo()->getIsStackable()) {
                    $isStackable = false;
                    break;
                }
            }
            if (!$isStackable) {
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
            $totalPrice = $groupPrice * $quantity;
            $canAffordPurchase = user::self()->canAffordPurchase($totalPrice);
            if (!$canAffordPurchase) {
                board::error(sprintf("Для покупки у Вас нехватает %s SphereCoin", $totalPrice));
            }


            /**
             * Списываем сумму
             */
            user::self()->donateDeduct($totalPrice);

            /**
             * Зачисляем предметы на персонажа
             */

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

            $player_info = player_account::is_player($server_info, [$playerName]);
            $player_info = $player_info->fetch();
            if (!$player_info){
                board::notice(false, lang::get_phrase(151, $playerName));
            }
            foreach ($shopItems as $item) {
                if (!self::sending_implementation($server_info, $player_info, $playerName, $player_info["player_id"], $item->getItemInfo()->getIsStackable(), $item->getItemId(), $item->getCount())) {
                    $db->rollBack();
                    return;
                }
            }

            $db->commit();
            board::alert([
                'type' => 'notice',
                'ok' => true,
                'message' => lang::get_phrase(304),
                'donate_bonus' => user::self()->getDonate(),
            ]);
        } catch (Exception $e) {
            $db->rollback();
            board::error($e->getMessage());
        }
    }

    //Имлементация отправки на персонажа
    private static function sending_implementation(serverModel $server_info, $player_info, $char_name, $player_id, $is_stack, $itemId, $addToUserItems ){

            //Если для выдачи предмета, персонаж должен быть ВНЕ игры
            if ($server_info->getCollectionSqlBaseName()::need_logout_player_for_item_add()) {
                /**
                 * Проверка чтоб игрок был оффлайн для выдачи предмета
                 */
                if ($player_info["online"]) {
                    return ['success' => false, 'phrase' => lang::get_phrase(153, $char_name)];
                }
                if ($is_stack) {
                    $checkPlayerItem = player_account::check_item_player($server_info, [
                        $itemId,
                        $player_id,
                    ]);
                    $checkPlayerItem = $checkPlayerItem->fetch();
                    //Если предмет есть у игрока
                    if ($checkPlayerItem) {
                        player_account::update_item_count_player($server_info, [
                            ($checkPlayerItem['count'] + $addToUserItems),
                            $checkPlayerItem['object_id'],
                        ]);
                    } else {
                        self::add_item_max_val_id($server_info, $player_id, $itemId, $addToUserItems);
                    }
                } else {
                    self::add_item_max_val_id($server_info, $player_id, $itemId, $addToUserItems);
                }
            } else { //Если персонаж может быть в игре для выдачи предмета
                player_account::add_item($server_info, [
                    $player_id,
                    $itemId,
                    $addToUserItems,
                    0,
                ]);
            }
        return ['success' => true];
    }

    /**
     * Получение информации о предмете из БД
     */
    static private function donate_item_info($item_id, $server_id) {
        return sql::run("SELECT * FROM donate WHERE id = ? AND server_id = ?", [
            $item_id,
            $server_id,
        ])->fetch();
    }

    public static function add_item_max_val_id($server_info, $player_id, $donat_item_id, $addToUserItems, $enchantLevel = 0) {
        $max_obj_id = player_account::max_value_item_object($server_info)->fetch()['max_object_id'];
        player_account::add_item($server_info, [
            $player_id,
            time() - $max_obj_id - $player_id,
            $donat_item_id,
            $addToUserItems,
            $enchantLevel,
            "INVENTORY",
        ]);
    }

    //Уменьшение коинов
    //TODO: деприкейтед используйте donateDeduct or donateAdd
    public static function taking_money($dp, $user_id) {
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

    public static function donate_history_pay_self($user_id = null): array {
        if (!$user_id) {
            $user_id = auth::get_id();
        }
        $pays = sql::getRows("SELECT
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
        ]);
        foreach ($pays AS &$pay){
            if(!empty($pay['id_admin_pay'])){
              $admin = auth::get_user_info($pay['id_admin_pay']);
              $pay['admin_name'] = $admin['name'];
            }
        }
        $trs = sql::getRows("SELECT
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
                                        log_transfer_spherecoin.id DESC", [$user_id, $user_id]);
        $result = array_merge($pays, $trs);
        usort($result, function ($a, $b) {
            return $a['date'] <=> $b['date'];
        });
        return array_reverse($result);
    }

    //Сумма зачисления денег с учетом курса валют конфига
    public static function currency(int|float $sum, string $currency): float|int {
        $config = __config__donate;
        return match ($currency) {
            "RUB" => ($sum / $config['coefficient']['RUB']) * $config['quantity'],
            "UAH" => ($sum / $config['coefficient']['UAH']) * $config['quantity'],
            "EUR" => ($sum / $config['coefficient']['EUR']) * $config['quantity'],
            default => ($sum / $config['coefficient']['USD']) * $config['quantity'],
        };
    }

    //Возвращает процент скидки
    public static function getBonusDiscount($user_id, $table) {
        $amount = sql::run("SELECT SUM(point) AS `count` FROM donate_history_pay WHERE user_id = ? and sphere=0", [$user_id])->fetch()['count'] ?? 0;
        $rangeKey = null;
        $discountValue = null;
        $lastValue = null;
        $keys = array_keys($table);
        $firstKey = reset($keys);
        if ($amount < $firstKey) {
            return 0;
        } else {
            $reversedTable = array_reverse($table, true);
            foreach ($reversedTable as $key => $value) {
                if ($amount >= $key) {
                    $rangeKey = $key;
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

    //Возвращает % скидки
    private static function findValueForN($inputN, $keyValueObject = 0) {
        if (!is_array($keyValueObject)) {
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


    public static function findReplenishmentBonus($amount, $bonusTable) {
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



    /**
     * @return false
     * Выдача бонуса предметом, за N сумму доната единоразвым платежем
     */
    public static function AddDonateItemBonus($user_id, $sphereCoin): bool {
        $item = false;
        $donateConfig = __config__donate;
        if(!$donateConfig['DONATE_BONUS_ITEM_ENABLE']){
            return $item;
        }
        $donateBonusList = $donateConfig['donate_bonus_list'];
        foreach ($donateBonusList as $bonus) {
            if ($sphereCoin >= $bonus['sc']) {
                $item = $bonus;
            }
        }
        if(!$item){
            return false;
        }
        foreach($item['items'] AS $bonus){
            $item_id = $bonus['id'];
            $count = $bonus['count'] ?? 1;
            $enchant = $bonus['enchant'] ?? 0;
            \Ofey\Logan22\model\admin\userlog::expanded($user_id, auth::get_default_server(), "add_to_inventory", "log_bonus_donate", [$enchant, $item_id, $count] );
            bonus::addToInventory(
                $user_id,
                auth::get_default_server(),
                $item_id,
                $count,
                $enchant,
                "add_item_donate_bonus"
            );
        }
        return true;
    }

    //Проверка: Если донат система в тестировании, тогда проверяем, что она доступна только для администратора
    public static function isOnlyAdmin($donateClass): void {
        if(method_exists($donateClass, 'forAdmin')){
            if($donateClass::forAdmin() AND user::self()->getAccessLevel() != 'admin'){
                board::error('Only for Admin');
            }
        }
    }


    /**
     * Получение информации о товарах в магазине.
     * @param int|null $shopId Идентификатор магазина
     * @return shop[]|null Массив содержит объекты класса shop, индексированный ID магазина
     */
    static public function getShopItems(int $shopId = null, $toArray = true): ?array
    {
        $shopInfo = [];

        if($shopId){
            $sql = 'SELECT * FROM `shop_items` WHERE id = ? AND serverId = ?';
            $shop = sql::getRow($sql, [$shopId, user::self()->getServerId()]);
            if (!$shop) {
                return null; // Возвращаем null, если ничего не найдено
            }
            $shop['items'] = json_decode($shop['items'], true);
            if (!is_array($shop['items'])) {
                return null;
            }
            $serverId = $shop['serverId'];
            foreach ($shop['items'] AS $item){
                $shopObj = new shop();
                $shopObj->setId($item['objectId']);
                $shopObj->setServerId($serverId);
                $shopObj->setCost($item['cost']);
                $shopObj->setCount($item['count']);
                $shopObj->setItemId($item['itemId']);
                $shopObj->setEnchant($item['enchant']);
                $shopObj->setItemInfo($item['itemId']);
                if($toArray){
                    $shopInfo[] = $shopObj->toArray();
                }else{
                    $shopInfo[] = $shopObj;
                }
            }
        }else{
            $sql = 'SELECT * FROM `shop_items` WHERE serverId = ?';
            $shop = sql::getRows($sql, [user::self()->getServerId()]);
            if(!$shop){
                return null;
            }
            foreach ($shop as &$row) {
                $row['items'] = json_decode($row['items'], true);
            }
            foreach ($shop AS $items){
                if(!$items['items']){
                    continue;
                }
                $objectID = $items['id'];
                $serverId = $items['serverId'];
                foreach ($items['items'] AS $item){
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

    /*
     * Подсчет N значений в массиве
     */
    /**
     * @param $array
     * @param $methodName
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



}