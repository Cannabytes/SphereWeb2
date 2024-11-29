<?php

namespace Ofey\Logan22\component\plugins\items_increase;

use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class items_increase
{
    private function sqlInstall(): void
    {
        sql::run("CREATE TABLE IF NOT EXISTS `items_increase` (
            `id` int NOT NULL AUTO_INCREMENT,
            `date` datetime NULL DEFAULT NULL,
            `data` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
            `server_id` int NULL DEFAULT NULL,
            PRIMARY KEY (`id`) USING BTREE
        ) ENGINE = InnoDB 
          AUTO_INCREMENT = 1 
          CHARACTER SET = utf8mb4 
          COLLATE = utf8mb4_general_ci 
          ROW_FORMAT = Dynamic;");
    }

    public function show()
    {
        validation::user_protection("admin");
        $getItems = $this->getItems(user::self()->getServerId());

        $chartData = [];
        foreach ($getItems as $entry) {
            $date = $entry['date'];
            $items = json_decode($entry['data'], true);
            foreach ($items as $item) {
                $chartData[$item['ItemId']][] = [
                    'date' => $date,
                    'TotalCount' => $item['TotalCount'],
                    'TopOwnerId' => $item['TopOwnerId'],
                    'TopOwnerName' => $item['TopOwnerName'],
                    'TopOwnerItemCount' => $item['TopOwnerItemCount'],
                    'TopOwnerIp' => $item['TopOwnerIp'],
                ];
            }
        }

// Формируем данные для передачи в TWIG
        $series = [];
        foreach ($chartData as $itemId => $dataPoints) {
            $series[$itemId] = [
                'ID' => "ID: $itemId",
                'data' => $dataPoints
            ];
        }

        tpl::addVar([
            'getItems' => $series,
        ]);
        tpl::displayPlugin("/items_increase/tpl/show.html");
    }

    public function save()
    {
        // Получаем данные из тела запроса
        $jsonData = file_get_contents('php://input');

        // Преобразуем JSON в массив (если необходимо)
        $data = json_decode($jsonData, true);
        sql::run("INSERT INTO `items_increase` (`date`, `data`, `server_id`) VALUES (?, ?, ?)",[
            time::mysql(),
            json_encode($data['items']),
            $data['server_id'],
        ]);

        // Логируем полученные данные
        file_put_contents('increase.log', '_POST: ' . print_r($data, true) . PHP_EOL, FILE_APPEND);
    }

    public function getItems($serverId): array
    {
        // данные приходят раз в минуту, соответственно мы выведем данные за 7 дней
        return sql::getRows("SELECT `date`, `data` FROM `items_increase` WHERE server_id = ? LIMIT 10080",[
            $serverId,
        ]);
    }



}