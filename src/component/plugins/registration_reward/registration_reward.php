<?php

namespace Ofey\Logan22\component\plugins\registration_reward;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\model\user\userModel;
use Ofey\Logan22\template\tpl;

class registration_reward
{
    public function __construct()
    {
        tpl::addVar('setting', plugin::getSetting("registration_reward"));
        tpl::addVar("pluginName", "registration_reward");
        tpl::addVar("pluginActive", (bool)plugin::getPluginActive("registration_reward") ?? false);
    }

    /**
     * Правильное преобразование значения в boolean
     * Обрабатывает строки "true", "1", "on", "yes" как true
     * Обрабатывает "false", "0", "off", "no", пустые строки как false
     * 
     * @param mixed $value Значение для преобразования
     * @return bool
     */
    private static function parseBoolean($value)
    {
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_int($value)) {
            return $value !== 0;
        }
        
        if (is_string($value)) {
            return in_array(strtolower($value), ['true', '1', 'on', 'yes'], true);
        }
        
        return (bool)$value;
    }

    /**
     * Получение информации о предмете (имя и иконка)
     * 
     * @param int $itemId ID предмета
     * @param mixed $knowledgeBase База знаний сервера
     * @return array ['name' => string, 'icon' => string]
     */
    private static function getItemInfo($itemId, $knowledgeBase)
    {
        $itemName = 'Item #' . $itemId;
        $itemIcon = '/uploads/images/icon/NOIMAGE.webp';
        
        try {
            $itemData = \Ofey\Logan22\component\image\client_icon::get_item_info(
                $itemId,
                false,
                false,
                $knowledgeBase
            );

            if ($itemData && is_object($itemData)) {
                $itemName = $itemData->getItemName() ?? $itemName;
                $itemIcon = $itemData->getIcon() ?? $itemIcon;
            }
        } catch (\Exception $e) {
            error_log("Error fetching item info for item #" . $itemId . ": " . $e->getMessage());
        }
        
        return ['name' => $itemName, 'icon' => $itemIcon];
    }

    /**
     * Формирование данных выигранного предмета
     * 
     * @param array $item Данные предмета из конфига
     * @param int $count Количество предметов
     * @param array $itemInfo Информация о предмете (name, icon)
     * @return array
     */
    private static function createWinnedItem($item, $count, $itemInfo)
    {
        return [
            'itemId' => $item['itemId'],
            'name' => $itemInfo['name'],
            'count' => $count,
            'enchant' => $item['enchant'] ?? 0,
            'icon' => $itemInfo['icon'],
        ];
    }

    /**
     * Инициализация массива сессии для сервера если его нет
     * 
     * @param int $serverId ID сервера
     */
    private static function initializeSessionServer($serverId)
    {
        if (!isset($_SESSION['registration_reward_winned'])) {
            $_SESSION['registration_reward_winned'] = [];
        }
        if (!isset($_SESSION['registration_reward_winned'][$serverId])) {
            $_SESSION['registration_reward_winned'][$serverId] = [];
        }
    }

    /**
     * Выбор взвешенных случайных предметов без дубликатов (когда нужно)
     * 
     * @param array $items Массив предметов с полями itemId и chance
     * @param int $count Количество предметов для выбора
     * @param bool $allowDuplicates Разрешить ли дубликаты
     * @return array Выбранные предметы
     */
    private static function selectWeightedItems($items, $count, $allowDuplicates = false)
    {
        if (empty($items) || $count <= 0) {
            return [];
        }

        // Если разрешены дубликаты, используем простой выбор
        if ($allowDuplicates) {
            return self::selectWeightedItemsWithDuplicates($items, $count);
        }

        // Для уникальных предметов используем взвешенный выбор без замены
        return self::selectWeightedItemsUnique($items, $count);
    }

    /**
     * Выбор взвешенных случайных предметов с дубликатами
     * 
     * @param array $items Массив предметов
     * @param int $count Количество для выбора
     * @return array
     */
    private static function selectWeightedItemsWithDuplicates($items, $count)
    {
        $selected = [];

        for ($i = 0; $i < $count; $i++) {
            $random = mt_rand(0, 100000) / 1000; // 0-100 с точностью до 0.001
            $cumulative = 0;
            $selectedItem = null;

            foreach ($items as $item) {
                $cumulative += $item['chance'];
                if ($random <= $cumulative) {
                    $selectedItem = $item;
                    break;
                }
            }

            // Если не выбран (случайное число больше суммы шансов), берём последний
            if ($selectedItem === null && !empty($items)) {
                $selectedItem = end($items);
            }

            if ($selectedItem !== null) {
                $selected[] = $selectedItem;
            }
        }

        return $selected;
    }

    /**
     * Выбор взвешенных случайных предметов БЕЗ дубликатов
     * Использует алгоритм взвешенного выбора без замены (weighted sampling without replacement)
     * 
     * @param array $items Массив предметов
     * @param int $count Количество для выбора
     * @return array
     */
    private static function selectWeightedItemsUnique($items, $count)
    {
        // Ограничиваем количество на случай если запрошено больше уникальных предметов чем доступно
        $count = min($count, count($items));
        
        $selected = [];
        $availableItems = $items; // Копия доступных предметов
        
        for ($i = 0; $i < $count; $i++) {
            if (empty($availableItems)) {
                break;
            }

            // Вычисляем сумму всех шансов
            $totalChance = 0;
            foreach ($availableItems as $item) {
                $totalChance += $item['chance'];
            }

            // Если нет шансов, выбираем случайный из оставшихся
            if ($totalChance <= 0) {
                $randomIndex = mt_rand(0, count($availableItems) - 1);
                $keys = array_keys($availableItems);
                $selectedItem = $availableItems[$keys[$randomIndex]];
                $selected[] = $selectedItem;
                unset($availableItems[$keys[$randomIndex]]);
                continue;
            }

            // Генерируем случайное число от 0 до суммы шансов
            $random = (mt_rand(0, 1000000) / 1000000) * $totalChance;
            $cumulative = 0;
            $selectedItem = null;
            $selectedKey = null;

            foreach ($availableItems as $key => $item) {
                $cumulative += $item['chance'];
                if ($random <= $cumulative) {
                    $selectedItem = $item;
                    $selectedKey = $key;
                    break;
                }
            }

            // Если не выбран, берём последний из доступных
            if ($selectedItem === null) {
                $keys = array_keys($availableItems);
                $selectedKey = end($keys);
                $selectedItem = $availableItems[$selectedKey];
            }

            if ($selectedItem !== null) {
                $selected[] = $selectedItem;
                unset($availableItems[$selectedKey]);
            }
        }

        return $selected;
    }

    /**
     * Отображение страницы настроек
     */
    public function setting()
    {
        validation::user_protection("admin");
        
        $servers = server::getServerAll();
        $serverSettings = [];
        
        if ($servers) {
            foreach ($servers as $srv) {
                $config = $srv->getPluginSetting("registration_reward") ?? [];
                $serverSettings[$srv->getId()] = [
                    'serverId' => $srv->getId(),
                    'serverName' => $srv->getName(),
                    'items' => $config['items'] ?? [],
                    'itemsCount' => $config['itemsCount'] ?? 1,
                    'allowDuplicates' => $config['allowDuplicates'] ?? false,
                    'allowClearRewards' => $config['allowClearRewards'] ?? false,
                ];
            }
        }
        
        tpl::addVar([
            'servers' => $servers ?? [],
            'serverSettings' => $serverSettings,
        ]);
        tpl::displayPlugin("registration_reward/tpl/setting.html");
    }

    /**
     * Сохранение настроек
     */
    public function save()
    {
        validation::user_protection("admin");

        // Получить JSON данные если они есть
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
        } else {
            $input = $_POST;
        }

        if (empty($input['serverId'])) {
            board::error(lang::get_phrase("error_select_server"));
        }

        $serverId = (int)($input['serverId'] ?? 0);
        $itemsCount = (int)($input['itemsCount'] ?? 1);
        
        // Правильный парсинг boolean значений
        $allowDuplicates = self::parseBoolean($input['allowDuplicates'] ?? false);
        $allowClearRewards = self::parseBoolean($input['allowClearRewards'] ?? false);
        $items = $input['items'] ?? [];

        // Валидация
        if ($itemsCount < 1) {
            board::error(lang::get_phrase("error_items_count_min"));
        }

        if ($itemsCount > 100) {
            board::error(lang::get_phrase("error_items_count_max"));
        }

        // Обработка и валидация предметов
        $processedItems = [];
        $totalChance = 0;

        foreach ($items as $item) {
            if (empty($item['itemId'])) {
                continue;
            }

            $processedItem = [
                'itemId' => (int)$item['itemId'],
                'minCount' => max(1, (int)($item['minCount'] ?? 1)),
                'maxCount' => max(1, (int)($item['maxCount'] ?? 1)),
                'chance' => (float)($item['chance'] ?? 0),
            ];

            // Проверка диапазона
            if ($processedItem['maxCount'] < $processedItem['minCount']) {
                $processedItem['maxCount'] = $processedItem['minCount'];
            }

            $totalChance += $processedItem['chance'];
            $processedItems[] = $processedItem;
        }

        // Проверка вероятности
        if ($totalChance > 100) {
            board::error(lang::get_phrase("error_chance_exceeds", number_format($totalChance, 2)));
        }

        if ($totalChance < 100 && !empty($processedItems)) {
            board::alert([
                'warning' => true,
                'message' => lang::get_phrase("warning_chance_less_than_100", number_format($totalChance, 2)),
            ]);
        }

        // Сохранение на сервере
        $srv = server::getServer($serverId);
        if ($srv) {
            $srv->setPluginSetting("registration_reward", [
                'items' => $processedItems,
                'itemsCount' => $itemsCount,
                'allowDuplicates' => $allowDuplicates,
                'allowClearRewards' => $allowClearRewards,
            ]);
        }

        board::success(lang::get_phrase(581)); // "Параметры сохранены"
    }

    /**
     * Выдача награды при регистрации
     * 
     * @return bool
     */
    public static function giveRegistrationReward(userModel $user)
    {
        if (!plugin::getPluginActive("registration_reward")) {
            return false;
        }

        // Проверка сессии: если нет выигранных предметов, сгенерировать их
        if (!isset($_SESSION['registration_reward_winned']) || empty($_SESSION['registration_reward_winned'])) {
            self::generateRewardsForAllServers();
        }
        
        // Выдача предметов из сессии для всех серверов
        return self::distributeRewardsFromSession($user);
    }

    /**
     * Генерация награды для всех серверов
     */
    private static function generateRewardsForAllServers()
    {
        foreach (server::getServerAll() as $srv) {
            $serverId = $srv->getId();
            $config = $srv->getPluginSetting("registration_reward");

            if (empty($config) || empty($config['items'])) {
                continue;
            }

            self::initializeSessionServer($serverId);
            
            $items = $config['items'];
            $itemsCount = $config['itemsCount'] ?? 1;
            $allowDuplicates = $config['allowDuplicates'] ?? false;

            $selectedItems = self::selectWeightedItems($items, $itemsCount, $allowDuplicates);

            foreach ($selectedItems as $item) {
                $count = mt_rand($item['minCount'], $item['maxCount']);
                $itemInfo = self::getItemInfo($item['itemId'], $srv->getKnowledgeBase());
                $winnedItem = self::createWinnedItem($item, $count, $itemInfo);
                $_SESSION['registration_reward_winned'][$serverId][] = $winnedItem;
            }
        }
    }

    /**
     * Распределение наград из сессии пользователю
     * 
     * @param userModel $user
     * @return bool
     */
    private static function distributeRewardsFromSession(userModel $user)
    {
        $hasRewards = false;
        
        foreach (server::getServerAll() as $server) {
            $serverId = $server->getId();       
            if (!isset($_SESSION['registration_reward_winned'][$serverId]) || empty($_SESSION['registration_reward_winned'][$serverId])) {
                continue;
            }

            try {
                foreach ($_SESSION['registration_reward_winned'][$serverId] as $item) {
                    $user->addToWarehouse($serverId, $item['itemId'], $item['count'], $item['enchant'], 'registration_bonus');
                }
                $hasRewards = true;
                unset($_SESSION['registration_reward_winned'][$serverId]);
            } catch (\Exception $e) {
                error_log("Error in registration reward plugin: " . $e->getMessage());
                return false;
            }
        }

        return $hasRewards;
    }
 
    /**
     * Попытка выиграть предметы через API
     * Сохраняет результат в сессии пользователя
     */
    public function tryWin()
    {
        if (!plugin::getPluginActive("registration_reward")) {
            board::alert(['ok' => false, 'message' => lang::get_phrase("plugin_disabled")]);
            exit;
        }

        self::initializeSessionServer(0);
        
        $serverId = (int)($_POST['serverId'] ?? null);
        $srv = server::getServer($serverId);
        
        if (!$srv) {
            board::alert(['ok' => false, 'message' => lang::get_phrase("server_not_found")]);
            exit;
        }

        $serverId = $srv->getId();
        $config = $srv->getPluginSetting("registration_reward");
        
        if (empty($config) || empty($config['items'])) {
            board::alert(['ok' => false, 'message' => lang::get_phrase("no_items_configured")]);
            exit;
        }

        // Очистка предыдущих выигрышей
        $_SESSION['registration_reward_winned'][$serverId] = [];

        $items = $config['items'];
        $itemsCount = $config['itemsCount'] ?? 1;
        $allowDuplicates = $config['allowDuplicates'] ?? false;
        $selectedItems = self::selectWeightedItems($items, $itemsCount, $allowDuplicates);

        $winnedData = [];
        foreach ($selectedItems as $item) {
            $count = mt_rand($item['minCount'], $item['maxCount']);
            $itemInfo = self::getItemInfo($item['itemId'], $srv->getKnowledgeBase());
            $winnedItem = self::createWinnedItem($item, $count, $itemInfo);
            
            $winnedData[] = $winnedItem;
            $_SESSION['registration_reward_winned'][$serverId][] = $winnedItem;
        }

        board::alert(['ok' => true, 'items' => $winnedData]);
        exit;
    }

    /**
     * Очистка выигранных предметов из сессии для конкретного сервера
     */
    public function clearWinned()
    {
        if (!plugin::getPluginActive("registration_reward")) {
            board::alert([
                'ok' => false,
                'message' => lang::get_phrase("plugin_disabled")
            ]);
            exit;
        }

        $serverId = (int)($_POST['serverId'] ?? 0);
        if ($serverId !== 0) {
            $srv = server::getServer($serverId);
            if ($srv) {
                $config = $srv->getPluginSetting("registration_reward");
                if (empty($config) || !($config['allowClearRewards'] ?? false)) {
                    board::alert([
                        'ok' => false,
                        'message' => lang::get_phrase("clear_rewards_disabled")
                    ]);
                    exit;
                }
            }
        }
        
        // Очистка предметов для конкретного сервера
        if ($serverId !== 0 && isset($_SESSION['registration_reward_winned'][$serverId])) {
            $_SESSION['registration_reward_winned'][$serverId] = [];
        } else {
            // Если serverId = 0, очищаем всё
            $_SESSION['registration_reward_winned'] = [];
        }
        
        board::alert([
            'ok' => true,
            'message' => lang::get_phrase("rewards_cleared")
        ]);
        exit;
    }

    /**
     * Get list of all items for a specific server with their configuration data
     */
    public function getItemsList()
    {
        $serverId = user::self()->getServerId();
        
        if ($serverId === 0) {
            board::alert(['ok' => false, 'message' => lang::get_phrase("error_select_server")]);
            exit;
        }

        $srv = server::getServer($serverId);
        if (!$srv) {
            board::alert(['ok' => false, 'message' => lang::get_phrase("server_not_found")]);
            exit;
        }

        $config = $srv->getPluginSetting("registration_reward") ?? [];
        $items = $config['items'] ?? [];

        if (empty($items)) {
            board::alert(['ok' => true, 'items' => []]);
            exit;
        }

        // Format items with their full data from item API
        $formattedItems = [];
        foreach ($items as $item) {
            $itemId = $item['itemId'] ?? 0;
            $itemInfo = self::getItemInfo($itemId, $srv->getKnowledgeBase());
            
            $formattedItems[] = [
                'itemId' => $itemId,
                'minCount' => $item['minCount'] ?? 1,
                'maxCount' => $item['maxCount'] ?? 1,
                'enchant' => $item['enchant'] ?? 0,
                'chance' => $item['chance'] ?? 0,
                'name' => $itemInfo['name'],
                'icon' => $itemInfo['icon'],
            ];
        }

        board::alert([
            'ok' => true,
            'serverId' => $serverId,
            'serverName' => $srv->getName(),
            'itemsCount' => $config['itemsCount'] ?? 1,
            'allowDuplicates' => $config['allowDuplicates'] ?? false,
            'allowClearRewards' => $config['allowClearRewards'] ?? false,
            'items' => $formattedItems,
            'totalItems' => count($formattedItems),
        ]);
        exit;
    }

}