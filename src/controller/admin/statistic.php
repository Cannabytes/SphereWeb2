<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\controller\config\config;
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

        $data = $response['data'];
        $server_time = $data['server_time'] ?? "No set";
        $timeZone = $data['time_zone'] ?? "No set";
        tpl::addVar([
            'serverId' => $serverId,
            'server_time' => $server_time,
            'timeZone' => $timeZone,
            'data' => $data,
        ]);
        tpl::display('/admin/server_statistic.html',);
    }

    static public function getDonate(): void
    {

        $rows = sql::getRows("SELECT `id`, `user_id`, `point`, `message`, `pay_system`, `id_admin_pay`, `sphere`, `date` FROM `donate_history_pay` ORDER BY `id` DESC");

        $statsPay = [];
        $monthlyStatsPay = [];
        $donatePoint = 0;
        foreach ($rows as $row) {
            if ($row['sphere'] != 1) {
                $donatePoint += $row['point'];
                $date = date('Y-m-d', strtotime($row['date']));
                $month = date('F Y', strtotime($row['date']));

                if (isset($statsPay[$date])) {
                    $statsPay[$date] += $row['point'];
                } else {
                    $statsPay[$date] = $row['point'];
                }

                if (isset($monthlyStatsPay[$month])) {
                    $monthlyStatsPay[$month] += $row['point'];
                } else {
                    $monthlyStatsPay[$month] = $row['point'];
                }
            }
        }

        $dollars = $donatePoint * (config::load()->donate()->getRatioUSD() / config::load()->donate()->getSphereCoinCost());

        tpl::addVar([
            'dollars' => $dollars,
            'donatePoint' => $donatePoint,
            'donate_users' => $rows,
            'statistic_pay' => $statsPay,
            'monthly_statistic_pay' => $monthlyStatsPay,
            'donate_history_pay' => $rows,
        ]);

        tpl::display("/admin/statistic_donate.html");
    }


}