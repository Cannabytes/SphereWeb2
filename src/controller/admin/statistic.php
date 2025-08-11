<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\template\tpl;

class statistic
{

    static public function getStatistic($serverId): void
    {
        tpl::addVar('serverId', $serverId);
        tpl::display('/admin/server_statistic.html',);
    }

    static public function getDonate(): void
    {

        $rows = sql::getRows("SELECT `id`, `user_id`, `point`, `message`, `pay_system`, `id_admin_pay`, `sphere`, `date` FROM `donate_history_pay` ORDER BY `id` DESC ");

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

        if (\Ofey\Logan22\model\server\server::get_count_servers()==0){
            $dollars = $donatePoint;
        }else{
            $dollars = $donatePoint * (config::load()->donate()->getRatioUSD() / config::load()->donate()->getSphereCoinCost());
        }

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