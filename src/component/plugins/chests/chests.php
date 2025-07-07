<?php

namespace Ofey\Logan22\component\plugins\chests;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\image\client_icon;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\request\request;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\item\item;
use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;


class chests
{

    public function __construct()
    {
        tpl::addVar('setting', plugin::getSetting("chests"));
        tpl::addVar("pluginName", "chests");
        tpl::addVar("pluginActive", (bool)plugin::getPluginActive("chests") ?? false);
    }

    public function show()
    {
        if (!plugin::getPluginActive("chests")) {
            redirect::location("/main");
        }
        tpl::addVar('last_history_winner', $this->last_history_winner());
        tpl::displayPlugin("/chests/tpl/chests.html");
    }

    public function setting()
    {
        validation::user_protection("admin");
        $box_names = include "box_names.php";
        tpl::addVar([
            "box_names" => $box_names,
        ]);
        tpl::displayPlugin("chests/tpl/setting.html");
    }

    /**
     * Получение информации о выигравших пользователях
     */
    private function last_history_winner()
    {
        // Здесь можно реализовать получение последних победителей из базы данных
        // Для примера вернем пустой массив
        return [];
    }

    /**
     * Получение всех кейсов для административной панели
     */
    public function getAllCases()
    {
        validation::user_protection(["admin"]);
        $cases = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->getCache("chests");
        if (empty($cases)) {
            // Возвращаем пустой массив, если нет кейсов
            board::alert([
                'ok' => true,
                'cases' => []
            ]);
            return;
        }

        // Формируем массив для отображения, сохраняя порядок кейсов в массиве
        $formattedCases = [];
        foreach ($cases as $caseName => $case) {
            $formattedCase = [
                'id' => $caseName, // Используем название кейса как ID
                'name' => $caseName,
                'type' => $case['type'] ?? 'No Use',
                'cost' => $case['price'] ?? 0,
                'icon' => $case['icon'] ?? 1,
                'sort' => $case['sort'] ?? 999, // Это поле оставляем для совместимости
                'items' => []
            ];

            // Обрабатываем предметы в кейсе
            if (isset($case['items']) && is_array($case['items'])) {
                foreach ($case['items'] as $item) {
                    try {
                        $itemInfo = item::getItem($item['id']);
                        $formattedItem = [
                            'itemId' => $itemInfo->getItemId(),
                            'minCount' => $item['count'],
                            'maxCount' => $item['count'],
                            'enchant' => $item['enchant'] ?? 0,
                            'chance' => $item['chance'] ?? 0,
                            'name' => $itemInfo->getItemName() ?? "Предмет {$item['id']}",
                            'add_name' => $itemInfo->getAddName() ?? '',
                            'icon' => $itemInfo->getIcon(),
                        ];
                        $formattedCase['items'][] = $formattedItem;
                    } catch (\Exception $e) {
                        error_log("Ошибка при обработке предмета {$item['id']}: " . $e->getMessage());
                        // Добавляем минимальные данные о предмете
                        $formattedCase['items'][] = [
                            'itemId' => $item['id'],
                            'minCount' => $item['count'],
                            'maxCount' => $item['count'],
                            'enchant' => $item['enchant'] ?? 0,
                            'chance' => $item['chance'] ?? 0,
                            'name' => "Предмет {$item['id']}",
                            'icon' => '/uploads/images/icon/NOIMAGE.webp',
                        ];
                    }
                }
            }

            $formattedCases[] = $formattedCase;
        }

        board::alert([
            'ok' => true,
            'cases' => $formattedCases
        ]);
    }


    /**
     * Обновление порядка сортировки кейсов
     */
    public function updateCasesOrder()
    {
        validation::user_protection(["admin"]);

        // Проверяем наличие данных
        if (empty($_POST['cases_order'])) {
            board::error("Не получены данные о порядке кейсов");
            return;
        }

        // Получаем данные из запроса
        $casesOrder = $_POST['cases_order'];

        // Получаем текущие данные кейсов
        $cases = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->getCache("chests");
        if (!$cases || !is_array($cases)) {
            board::error("Настройки кейсов не найдены");
            return;
        }

        // Создаем новый массив с правильным порядком
        $orderedCases = [];
        foreach ($casesOrder as $position => $caseName) {
            if (isset($cases[$caseName])) {
                $orderedCases[$caseName] = $cases[$caseName];
            }
        }

        // Добавляем кейсы, которые не были включены в сортировку
        foreach ($cases as $caseName => $caseData) {
            if (!isset($orderedCases[$caseName])) {
                $orderedCases[$caseName] = $caseData;
            }
        }

        // Сохраняем обновленные данные
        \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->getCache("chests", null, $orderedCases);

        board::alert([
            'ok' => true,
            'message' => "Порядок кейсов успешно обновлен! Обновлено " . count($casesOrder) . " кейсов."
        ]);
    }

    /**
     * Получение информации о конкретном кейсе
     */
    public function getCase()
    {
        validation::user_protection(["admin"]);
        $id = $_POST['id'] ?? null;

        if (!$id) {
            board::error("Не указан идентификатор кейса");
            return;
        }

        $cases = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->getCache("chests");

        if (!$cases || !isset($cases[$id])) {
            board::error("Кейс не найден");
            return;
        }

        $case = $cases[$id];
        $case['id'] = $id;
        $case['name'] = $id; // Название кейса совпадает с ID

        // Получим информацию о предметах
        if (isset($case['items']) && is_array($case['items'])) {
            foreach ($case['items'] as &$item) {
                $itemInfo = client_icon::get_item_info($item['id']);
                $item['name'] = $itemInfo->getItemName() ?? 'Unknown Item';
                $item['add_name'] = $itemInfo->getAddName() ?? '';
                $item['icon'] = $itemInfo->getIcon() ?? '/uploads/images/icon/NOIMAGE.webp';
                // Преобразуем данные для совместимости с интерфейсом
                $item['itemId'] = $item['id'];
                $item['minCount'] = $item['count'];
                $item['maxCount'] = $item['count'];
            }
        }

        board::alert([
            'ok' => true,
            'case' => $case
        ]);
    }

    /**
     * Удаление кейса
     */
    public function deleteCase()
    {
        validation::user_protection(["admin"]);

        $id = $_POST['id'] ?? null;

        if (!$id) {
            board::error("Не указан идентификатор кейса");
            return;
        }

        $cases = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->getCache("chests");

        $response = server::sendCustom("/api/plugin/chests/delete", [
            "name" => $id,
            "serverId" => \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->getId(),
        ])->show()->getResponse();
        if (isset($response['success'])) {
            // Удаляем кейс из массива
            unset($cases[$id]);

            // Сохраняем обновленные данные
            \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->setCache("chests", $cases);

            board::alert([
                'ok' => true,
                'message' => 'Кейс успешно удален'
            ]);
        }

        if (!$cases || !isset($cases[$id])) {
            board::error("Кейс не найден");
            return;
        }

        board::error('Произошла ошибка при удалении кейса');

    }


    /**
     * Сохранение настроек кейса (создание или обновление)
     */
    public function save()
    {
        validation::user_protection(["admin"]);
        $name = $_POST['name'] ?? '';
        $originalName = $_POST['original_name'] ?? $name;
        $icon = $_POST['icon'] ?? 1;
        $cost = $_POST['cost'] ?? 0;
        $type = $_POST['type'] ?? 'Middle';
        $items = $_POST['items'] ?? [];
        $sort = $_POST['sort'] ?? null;
        $background = $_POST['background'] ?? null;

        // Валидация данных
        if (!$name) {
            board::error("Не указано название кейса");
            return;
        }

        if ($cost < 0) {
            board::error("Стоимость кейса не может быть отрицательной");
            return;
        }

        if (empty($items)) {
            board::error("Кейс должен содержать хотя бы один предмет");
            return;
        }

        $cases = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->getCache("chests");

        // Проверка на дубликаты названий (только при создании нового кейса или при изменении имени)
        if (($originalName != $name) && isset($cases[$name])) {
            board::error("Кейс с таким названием уже существует. Пожалуйста, выберите другое название.");
            return;
        }

        // Форматируем данные предметов для сохранения
        $formattedItems = [];
        foreach ($items as $item) {
            if (!isset($item['itemId']) || $item['itemId'] <= 0) {
                continue;
            }

            $formattedItem = [
                'id' => (int)$item['itemId'],
                'count' => (int)$item['minCount'],
                'chance' => (float)$item['chance'],
            ];

            // Добавляем enchant только если он больше 0
            if (isset($item['enchant']) && (int)$item['enchant'] > 0) {
                $formattedItem['enchant'] = (int)$item['enchant'];
            }

            $formattedItems[] = $formattedItem;
        }

        // Собираем данные кейса
        $case = [
            'icon' => (int)$icon,
            'price' => (float)$cost,
            'type' => $type,
            'items' => $formattedItems,
            'sort' => (int)$sort,
            'background' => $background,
        ];

        if ($originalName == "" or empty($originalName)) {
            $originalName = $name;
        }

        foreach ($cases as $key => $value) {
            if ($key == $originalName) {
                unset($cases[$key]);
            }
        }

        // Используем название кейса как ключ в массиве
        $cases[$name] = $case;

        // Сохраняем обновленные настройки
        \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->setCache("chests", $cases);

        server::sendCustom("/api/plugin/chests/save", [
            'name' => $originalName,
            'name_old' => $name,
            "serverId" => user::self()->getServerId(),
            "case" => $case,
        ]);

        // Логирование операции
        $actionType = isset($cases[$originalName]) && $originalName == $name ? "Редактирование кейса" : "Создание кейса";

        board::alert([
            'ok' => true,
            'message' => $actionType . ' успешно выполнено',
            'id' => $name
        ]);
    }


    /**
     * Метод для обработки callback-запросов при открытии кейса
     */
    public function callback(): void
    {
        // Проверка пользователя на авторизацию
        validation::user_protection();
        if (!plugin::getPluginActive("chests")) {
            board::error("disabled");
            return;
        }

        $case_name = request::validateString('chest_id', 'Не указан ID кейса');
        $case_count_open = isset($_POST['count_open']) ? (int)$_POST['count_open'] : 1;

        try {
            // Начало транзакции
            sql::beginTransaction();

            $caseServer = server::sendCustom("/api/plugin/chests/open", [
                "serverId" => user::self()->getServerId(),
                "name" => htmlspecialchars_decode($case_name, ENT_QUOTES),
                "countOpen" => $case_count_open,
            ])->show()->getResponse();
            $case = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->getCache("chests");
            $case = $case[$case_name];

            // Преобразование цены в целое число
            $price = (float)$case['price'];
            if ($price < 0) {
                throw new \Exception("Некорректная цена кейса");
            }
            $items = [];
            if($case_count_open == 1) {
                $item = $caseServer['item'];

                $canAffordPurchase = user::self()->canAffordPurchase($price);
                if (!$canAffordPurchase) {
                    throw new \Exception(sprintf("Для покупки у Вас не хватает %s SphereCoin", $price - user::self()->getDonate()));
                }

                user::self()->donateDeduct($price);

                $itemInfo = item::getItem($item['id'], \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->getKnowledgeBase());
                if (!$itemInfo || !$itemInfo->isExists()) {
                    throw new \Exception("Предмет не найден");
                }
                $item['itemInfo'] = $itemInfo;

                $enchant = isset($item['enchant']) ? (int)$item['enchant'] : 0;
                $result = user::self()->addToWarehouse(
                    user::self()->getServerId(),
                    (int)$item['id'],
                    (int)$item['count'],
                    $enchant,
                    'chest_win'
                );

                if (!$result['success']) {
                    throw new \Exception("Ошибка при добавлении предмета в склад");
                }

                user::self()->addLog(\Ofey\Logan22\model\log\logTypes::LOG_CHEST_WIN, "chest_win", [
                    'chest_id' => $case_name,
                    'item_id' => $item['id'],
                    'count' => $item['count'],
                    'enchant' => $enchant,
                    'price' => $price,
                ]);
            } else {
                $items = $caseServer['items'];

                $canAffordPurchase = user::self()->canAffordPurchase($price * $case_count_open);
                if (!$canAffordPurchase) {
                    throw new \Exception(sprintf("Для покупки у Вас не хватает %s SphereCoin", $price * $case_count_open - user::self()->getDonate()));
                }

                user::self()->donateDeduct($price * $case_count_open);

                foreach ($items as $item) {
                    $itemInfo = item::getItem($item['id'], \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->getKnowledgeBase());
                    if (!$itemInfo || !$itemInfo->isExists()) {
                        throw new \Exception("Предмет не найден");
                    }
                    $item['itemInfo'] = $itemInfo;

                    $enchant = isset($item['enchant']) ? (int)$item['enchant'] : 0;
                    $result = user::self()->addToWarehouse(
                        user::self()->getServerId(),
                        (int)$item['id'],
                        (int)$item['count'],
                        $enchant,
                        'chest_win'
                    );

                    if (!$result['success']) {
                        throw new \Exception("Ошибка при добавлении предмета в склад");
                    }

                    user::self()->addLog(\Ofey\Logan22\model\log\logTypes::LOG_CHEST_WIN, "chest_win", [
                        'chest_id' => $case_name,
                        'item_id' => $item['id'],
                        'count' => $item['count'],
                        'enchant' => $enchant,
                        'price' => $price,
                    ]);
                }
            }


            sql::commit();

            user::self()->getWarehouse(true);

            if($case_count_open == 1) {
                board::alert([
                    'ok' => true,
                    'item' => $item,
                    'warehouse' => user::self()->getWarehouseToArray(),
                    'countWarehouseItems' => user::self()->countWarehouseItems(),
                ]);
            }else{
                board::alert([
                    'ok' => true,
                    'items' => $items,
                    'warehouse' => user::self()->getWarehouseToArray(),
                    'countWarehouseItems' => user::self()->countWarehouseItems(),
                ]);
            }

        } catch (\Exception $e) {
            // В случае ошибки откатываем транзакцию
            if (sql::isError() || sql::getException() !== null) {
                sql::rollBack();
            }
            board::error($e->getMessage());
        }
    }

}