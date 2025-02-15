<?php

namespace lucera_traders;

use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\request\clear;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\item\item;
use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;
use ReflectionClass;

class lucera_traders
{

    private string|null $nameClass = null;

    public function __construct()
    {
        tpl::addVar([
            'setting' => plugin::getSetting($this->getNameClass()),
            'pluginName' => $this->getNameClass(),
        ]);
    }

    private function getNameClass(): string
    {
        if ($this->nameClass == null) {
            $this->nameClass = (new ReflectionClass($this))->getShortName();
        }

        return $this->nameClass;
    }

    public function include(): void
    {

        tpl::addVar([
            "selllist" => $this->getSellList(),
        ]);

        tpl::displayPlugin("lucera_traders/tpl/include.html");
    }

    public function getSellList()
    {
        $cacheKey = 'selllist';
        $data = sql::getRow("SELECT `data`, `date_create` FROM server_cache WHERE `type` = ? AND server_id = ?;", [
            $cacheKey,
            user::self()->getServerId(),
        ]);
        if ($data) {
            $totalSeconds = time::diff(time::mysql(), $data['date_create']);
//            if ($totalSeconds > 60 * 5) {
            if ($totalSeconds > 1 * 1) {
                sql::run("DELETE FROM server_cache WHERE `type` = ? AND server_id = ?;", [
                    $cacheKey,
                    user::self()->getServerId(),
                ]);
                $data = null;
            } else {
                $selllist = json_decode($data['data'], true);
                if ($selllist) {
                    foreach ($selllist as &$value) {
                        foreach ($value['items'] as &$item) {
                            $itemdata = item::getItem($item['item_id']); // Получаем объект item
                            if ($itemdata !== null) {
                                $itemdata->setCount($item['quantity']);
                                $itemdata->setPrice($item['price']);
                                $itemdata->setEnchant($item['enchant']);
                                $item['itemInfo'] = $itemdata;
                            }
                        }
                    }
                }
            }
        }

        if ($data == null or empty($data['data'])) {
            $selllist = \Ofey\Logan22\component\sphere\server::send(type::GAME_SERVER_REQUEST, [
                'query' => clear::cleanSQLQuery('SELECT
                          characters.obj_id,
                          characters.char_name,
                          cv_selllist.`value`,
                          characters.sex,
                          characters.x,
                          characters.y,
                          character_subclasses.class_id 
                        FROM
                          characters
                          INNER JOIN character_subclasses ON characters.obj_Id = character_subclasses.char_obj_id
                          INNER JOIN character_variables AS cv_offline ON characters.obj_id = cv_offline.obj_id 
                          AND cv_offline.`name` = "offline"
                          INNER JOIN character_variables AS cv_selllist ON characters.obj_id = cv_selllist.obj_id 
                          AND cv_selllist.`name` = "selllist"
                          INNER JOIN character_variables AS cv_storemode ON characters.obj_id = cv_storemode.obj_id 
                          AND cv_storemode.`name` = "storemode" 
                          AND cv_storemode.`value` = 1 
                        WHERE
                          character_subclasses.isBase = 1 
                          AND characters.`online` = 0;'),
            ])->show(false)->getResponse();
            if (isset($selllist['error'])) {
                return [];
            }
            $selllist = $selllist['rows'];
            $itemIds = [];
            foreach ($selllist as $key => &$value) {

                $value['items'] = $this->parseDataString($value["value"]);

                unset($value["value"]);

                // Если нет items, пропускаем эту запись
                if (empty($value['items'])) {
                    unset($selllist[$key]);
                    continue;
                }

                foreach ($value["items"] as $item) {
                    $itemIds[] = $item["id"];
                }
            }

            $storeNames = $this->getStoreName();
            if($storeNames === false){
                return [];
            }
            $storeNames = $storeNames['rows'];
            $itemIds = array_unique($itemIds);

            if (!empty($itemIds)) {
                $itemIdsQuery = implode(',', array_map('intval', $itemIds));
                $itemInfoResponse = \Ofey\Logan22\component\sphere\server::send(type::GAME_SERVER_REQUEST, [
                    'query' => clear::cleanSQLQuery("SELECT
                    items.item_id,
                    items.item_type,
                    items.enchant
                FROM
                    items
                WHERE
                    items.item_id IN ({$itemIdsQuery})"),
                ])->show()->getResponse();

                $itemInfoResponse = $itemInfoResponse['rows'];

                $itemInfoMap = [];
                foreach ($itemInfoResponse as $info) {
                    if (isset($info['item_id'])) {
                        $itemInfoMap[$info['item_id']] = $info;
                    }
                }
                foreach ($selllist as $key => &$value) {
                    $value['x'] = round(116 + ($value['x'] + 107823) / 200);
                    $value['y'] = round(2580 + ($value['y'] - 255420) / 200);

                    foreach ($storeNames as $storeName) {
                        if(!isset($storeName['obj_id'])){
                            var_dump($storeName);exit;
                        }
                        if ($storeName['obj_id'] == $value['obj_id']) {
                            $value['storeName'] = trim($storeName['value']);
                        }
                    }

                    foreach ($value["items"] as $index => &$item) {
                        $itemObjectID = $item["id"];
                        if (isset($itemInfoMap[$itemObjectID])) {
                            $item['item_id'] = $itemInfoMap[$itemObjectID]['item_type'];
                            $item['enchant'] = $itemInfoMap[$itemObjectID]['enchant'];
                            $item['itemInfo'] = item::getItem($item['item_id']);
                        } else {
                            // Удалить элемент, если нет информации о нем
                            unset($value["items"][$index]);
                        }
                    }

                    if (empty($value["items"])) {
                        unset($selllist[$key]);
                    }
                }
            }

            $jsonData = json_encode(array_values($selllist));

            sql::sql("INSERT INTO `server_cache` ( `server_id`, `type`, `data`, `date_create`) VALUES (?, ?, ?, ?)", [
                user::self()->getServerId(),
                $cacheKey,
                $jsonData,
                time::mysql(),
            ]);
        }

        return $selllist;
    }

    public function show(): void
    {
        if (\Ofey\Logan22\model\server\server::get_count_servers() == 0 or (\Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->isDisabled())) {
            redirect::location("/main");
        }
        if (!plugin::getPluginActive($this->getNameClass())) {
            redirect::location("/main");
        }
        tpl::addVar([
            "selllist" => $this->getSellList(),
        ]);
        tpl::displayPlugin("lucera_traders/tpl/traders.html");
    }

    private function parseDataString($dataString): array
    {
        $result = [];

        // Разделяем строку на части по разделителю ':'
        $entries = explode(':', $dataString);

        foreach ($entries as $entry) {
            // Пропускаем пустые элементы
            if (empty($entry)) {
                continue;
            }

            // Разделяем элемент по разделителю ';'
            [$id, $quantity, $price] = explode(';', $entry);

            // Добавляем данные в массив
            $result[] = [
                'id' => $id,
                'quantity' => $quantity,
                'price' => $price,
            ];
        }

        return $result;
    }

    private function getStoreName(): bool|array|null
    {
        $sql = clear::cleanSQLQuery("SELECT
          character_variables.obj_id, 
          character_variables.`value`
        FROM
          character_variables
          INNER JOIN
          characters
          ON 
            character_variables.obj_id = characters.obj_Id
        WHERE
          character_variables.`name` = 'sellstorename';");
        return server::send(type::GAME_SERVER_REQUEST, [
            'query' => $sql,
        ])->show()->getResponse();
    }

    public function setting()
    {
        validation::user_protection("admin");
        tpl::displayPlugin("lucera_traders/tpl/setting.html");
    }

}