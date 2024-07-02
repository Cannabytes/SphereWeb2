<?php

namespace Ofey\Logan22\controller\account\characters;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;

class relocation
{

    static function playerMove(): void
    {
        $account = $_POST['account'] ?? board::error("No account");
        $player = $_POST['player'] ?? board::error("No player");
        $itemsToWarehouse = isset($_POST['itemsToWarehouse']) && (bool)$_POST['itemsToWarehouse'];
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
            board::success("Relocation success");
        }

    }

}