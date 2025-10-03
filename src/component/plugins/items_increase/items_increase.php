<?php

namespace Ofey\Logan22\component\plugins\items_increase;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\template\tpl;
use PDOException;

class items_increase
{

    public function show(?int $serverId = null)
    {
        validation::user_protection("admin");
        if ($serverId == null) {
            $serverId = server::getDefaultServer();
        }
        $getItems = $this->getItems($serverId);
        $chartData = [];
        foreach ($getItems as $entry) {
            $itemId = $entry['itemId'];
            $date = $entry['date'];
            $item = json_decode($entry['data'], true);
            $chartData[$itemId][] = [
                'date' => $date,
                'TotalCount' => $item['TotalCount'],
                'TopOwnerId' => $item['TopOwnerId'],
                'TopOwnerName' => $item['TopOwnerName'],
                'TopOwnerItemCount' => $item['TopOwnerItemCount'],
            ];
        }

        $series = [];
        foreach ($chartData as $itemId => $dataPoints) {
            $series[$itemId] = [
                'ID' => "ID: $itemId",
                'data' => $dataPoints
            ];
        }
        $items = \Ofey\Logan22\component\sphere\server::send(type::ITEM_INCREASE_ITEMS, ['serverId' => $serverId])->getResponse();
        tpl::addVar([
            'items' => $items['items'],
            'serverId' => $serverId,
            'getItems' => $series,
            'chronicle' => server::getServer($serverId)?->getKnowledgeBase(),
        ]);
        tpl::displayPlugin("/items_increase/tpl/show.html");
    }

    public function getItems($serverId, $count = 10080): array
    {
        validation::user_protection("admin");
        $result = sql::run("SELECT `id`, `date`, `itemId`, `data` FROM `items_increase` WHERE server_id = ? LIMIT ?", [
            $serverId,
            $count
        ]);
        if ($result instanceof PDOException) {
            if ($result->getCode() == "42S02") {
                $this->sqlInstall();
            }
            return [];
        }
        return $result->fetchAll();
    }

    private function sqlInstall()
    {
        return sql::run("CREATE TABLE IF NOT EXISTS `items_increase` (
            `id` int NOT NULL AUTO_INCREMENT,
            `date` datetime NULL DEFAULT NULL,
            `itemId` int(11) NOT NULL,
            `data` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
            `server_id` int NULL DEFAULT NULL,
            PRIMARY KEY (`id`) USING BTREE
        ) ENGINE = InnoDB 
          AUTO_INCREMENT = 1 
          CHARACTER SET = utf8mb4 
          COLLATE = utf8mb4_general_ci 
          ROW_FORMAT = Dynamic;");
    }

    public function addItem(): void
    {
        validation::user_protection("admin");
        $itemId = $_POST['itemId'];
        $serverId = $_POST['serverId'];
        $response = \Ofey\Logan22\component\sphere\server::send(type::ITEM_INCREASE_ADD, [
            'itemId' => (int)$itemId,
            'serverId' => (int)$serverId,
        ])->getResponse();
        board::success("Добавлено");
    }

    public function DeleteItem(): void
    {
        validation::user_protection("admin");
        $itemId = $_POST['itemId'];
        $serverId = $_POST['serverId'];
        $response = \Ofey\Logan22\component\sphere\server::send(type::ITEM_INCREASE_DELETE, [
            'itemId' => (int)$itemId,
            'serverId' => (int)$serverId,
        ])->getResponse();
        sql::run("DELETE FROM `items_increase` WHERE `itemId` = ?", [$itemId]);
        board::success("delete");
    }

    public function pay()
    {
        $months = $_POST['months'] ?? 1;
        $data = \Ofey\Logan22\component\sphere\server::send(type::ITEM_INCREASE_PAY, ['months' => $months])->getResponse();
        if (isset($data['type']) and $data['type'] == 'success') {
            board::reload();
            board::success('Использование плагина продлено на ' . $months . ' месяцев');
        } else {
            board::error($data['message']);
        }
    }

    public function save()
    {
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);

        $token = $data['token'] ?? "";
        if (!config::isToken($token)) {
            return;
        }

        $server_id = $data['server_id'] ?? null;
        if (!$server_id || !isset($data['items'])) {
            return;
        }

        foreach ($data['items'] as $item) {
            $result = sql::run("INSERT INTO `items_increase` (`date`, `itemId`, `data`, `server_id`) VALUES (?, ?, ?, ?)", [
                time::mysql(),
                $item['itemId'] ?? 0,
                json_encode($item['item'] ?? []),
                $server_id,
            ]);
            if ($result instanceof PDOException) {
                if ($result->getCode() == "42S02") {
                    $this->sqlInstall();
                }
                break;
            }
        }
    }


}