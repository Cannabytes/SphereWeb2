<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\template\tpl;

class statistic
{

    static public function getStatistic($serverId): void
    {
        $response = server::send(type::SERVER_STATISTIC_ONLINE)->show()->getResponse();
        if ($response === false) {
            redirect::location("/main");
        }
        $dataStatisticDonate = sql::getRows("SELECT DATE(`date`) AS day, SUM(`point`) AS total_points
FROM `donate_history_pay`
WHERE `sphere` = 0
GROUP BY day
ORDER BY day DESC;");

        tpl::addVar("dataStatisticDonate", $dataStatisticDonate);

        $data        = $response['data'];
        $server_time = $data['server_time'] ?? "No set";
        $timeZone    = $data['time_zone'] ?? "No set";
        tpl::addVar([
          'serverId'    => $serverId,
          'server_time' => $server_time,
          'timeZone'    => $timeZone,
          'data'        => $data,
        ]);
        tpl::display('/admin/server_statistic.html',);
    }

}