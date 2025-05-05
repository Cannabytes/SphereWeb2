<?php

namespace Ofey\Logan22\controller\account\characters;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\model\config\config;
use Ofey\Logan22\model\user\user;

class relocation
{

    static function playerMove(): void
    {
        $account = $_POST['account'] ?? board::error("No account");
        $player = $_POST['player'] ?? board::error("No player");

        if(\Ofey\Logan22\model\server\server::getServer()->isResetItemsToWarehouse()){
            $itemsToWarehouse = isset($_POST['itemsToWarehouse']) && (bool)$_POST['itemsToWarehouse'];
        }else{
            $itemsToWarehouse = false;
        }

        $x = 147451;
        $y = 25877;
        $z = -2008;

        $sphere = server::send(type::RELOCATION, [
            "account" => $account,
            "player" => $player,
            "itemsToWarehouse" => $itemsToWarehouse,
            "location" => [
                "x" => $x,
                "y" => $y,
                "z" => $z
            ],
        ])->show()->getResponse();

        if(isset($sphere['ok'])){
            if (\Ofey\Logan22\controller\config\config::load()->notice()->isSendPlayerToVillage()) {
                $template = lang::get_other_phrase(\Ofey\Logan22\controller\config\config::load()->notice()->getNoticeLang(), 'notice_relocation');
                $msg = strtr($template, [
                    '{email}' => user::self()->getEmail(),
                    '{player}' => $player,
                    '{itemsToWarehouse}' => $itemsToWarehouse ? 'Yes' : 'No',
                ]);
                telegram::sendTelegramMessage($msg, \Ofey\Logan22\controller\config\config::load()->notice()->getSendPlayerToVillageThreadId());
            }
            board::success("Relocation success");
        }

    }

}