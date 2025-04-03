<?php

namespace Ofey\Logan22\component\plugins\winroll;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\item\item;
use Ofey\Logan22\model\log\logTypes;
use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class winroll
{
    public function __construct()
    {
        tpl::addVar('setting', plugin::getSetting("winroll"));
        tpl::addVar("pluginName", "winroll");
        tpl::addVar("pluginActive", (bool)plugin::getPluginActive("winroll") ?? false);
    }

    public function show()
    {
        if(!plugin::getPluginActive("winroll")){
            redirect::location("/main");
        }
        tpl::addVar([
            'logs' => user::self()->getLogs(logTypes::LOG_WINROW_WIN),
        ]);
        tpl::displayPlugin("winroll/tpl/winroll.html");
    }

    public function setting()
    {
        validation::user_protection("admin");
        $winrolls = server::sendCustom("/api/plugin/winroll/get")->show()->getResponse();
        if($winrolls['status'] == "ok"){
            $chance = array_map(function (array $item): array {
                return [
                    'chance' => (float)($item['chance'] ?? 0)
                ];
            }, $winrolls['data']['items']);
            $totalChance = array_sum(array_column($chance, 'chance'));
        }
        tpl::addVar([
            'winrolls' => $winrolls ?? [],
            'totalChance' => $totalChance ?? 0
        ]);
        tpl::displayPlugin("winroll/tpl/setting.html");
    }

    //Начала розыгрыша
    public function spin()
    {
        validation::user_protection();
        if (!plugin::getPluginActive("winroll")){
            board::error("disabled");
        }
        $winrolls = server::sendCustom("/api/plugin/winroll/spin")->show()->getResponse();
        if (isset($winrolls['status']) && $winrolls['status'] == "success") {
            $cost = $winrolls['cost'];

            $itemId = $winrolls['result']['itemId'];
            $count = $winrolls['result']['count'];
            $enchant = $winrolls['result']['enchant'];
            $itemInfo = item::getItem($winrolls['result']['itemId']);

            if (!user::self()->canAffordPurchase($cost)) {
                board::error(lang::get_phrase('Not enough coins'));
            }
            if(!user::self()->donateDeduct($cost)){
                board::error(lang::get_phrase('An error occurred while writing off'));
            }

            user::self()->addToWarehouse($winrolls['serverId'], $itemId, $count, $enchant, 'your winnings');
            user::self()->addLog(logTypes::LOG_WINROW_WIN, "winroll_action", [$itemId, $enchant, $itemInfo->getItemName(), $count]);
            board::alert([
                'id' => $winrolls['result']['id'],
                'itemId' => $itemId,
                'count' => $count,
                'enchant' => $enchant,
                'item' => $itemInfo,
            ]);

        }
    }

    public function save(): void
    {
        validation::user_protection("admin");

        if (empty($_POST['items']) || !is_array($_POST['items'])) {
            board::error(lang::get_phrase("An unexpected error occurred"));
        }

        $i = 1;
        $items = array_map(function (array $item) use (&$i): array {
            return [
                'id' => $i++,
                'itemId' => (int)($item['itemId'] ?? 0),
                'minCount' => (int)($item['minCount'] ?? 0),
                'maxCount' => (int)($item['maxCount'] ?? 0),
                'enchant' => (int)($item['enchant'] ?? 0),
                'chance' => (float)($item['chance'] ?? 0)
            ];
        }, $_POST['items']);

        $totalChance = array_sum(array_column($items, 'chance'));
        if ($totalChance !== 100.00) {
            Board::error(lang::get_phrase("error_sum_chance", $totalChance));
        }

        $server = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId());
        $server->setPluginSetting("winroll", [
            'items' => $items,
            'cost' => (float)$_POST['cost'],
        ]);
        $ok = server::sendCustom("/api/plugin/winroll/save", ['items' => $items, 'cost' => (float)$_POST['cost']])->show()->getResponse();
        if (isset($ok['status']) && $ok['status'] == "success") {
            board::reload();
            board::success(lang::get_phrase(581));
        } else {
            board::error($ok['error']);
        }

    }

}