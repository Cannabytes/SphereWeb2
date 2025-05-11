<?php

namespace Ofey\Logan22\component\plugins\sphere_statistic;

use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\db\sql;

class custom_twig
{

    public function getUserRegistrations(): array
    {
        if (config::load()->enabled()->isEnableEmulation() && \Ofey\Logan22\model\server\server::get_count_servers() == 0) {
            return [];
        }
        return (new statistic())->getUserRegistration();
    }

    public function getUserDonations(): array
    {
        if (config::load()->enabled()->isEnableEmulation() && \Ofey\Logan22\model\server\server::get_count_servers() == 0) {
            return [];
        }
        return sql::getRows("SELECT `point`, `date` FROM `donate_history_pay` ORDER BY `id` DESC");
    }

    public function getStatisticOnline($serverId): array|false
    {
        if (config::load()->enabled()->isEnableEmulation() && \Ofey\Logan22\model\server\server::get_count_servers() == 0) {
            return false;
        }

        $serverStatistic = sql::getRow("SELECT * FROM `statistic_online` WHERE `server_id` = ?", [$serverId]);

        if (!$serverStatistic || time::diff($serverStatistic['time'], time::mysql()) >= 60*10) {
            $response = server::send(type::SERVER_STATISTIC_ONLINE, ['id' => $serverId])->show(false)->getResponse();

            if (isset($response['error'])) {
                $data = [];
            } else {
                $data = $response['data'] ?? [];
            }

            if (!$serverStatistic) {
                sql::run("INSERT INTO `statistic_online` (`server_id`, `count_online_player`, `time`) VALUES (?, ?, ?);",
                    [$serverId, json_encode($data), time::mysql()]);
            } else {
                sql::run("UPDATE `statistic_online` SET `count_online_player` = ?, `time` = ? WHERE `server_id` = ?",
                    [json_encode($data), time::mysql(), $serverId]);
            }
        } else {
            $data = json_decode($serverStatistic['count_online_player'], true);
        }

        if (empty($data) || !is_array($data)) {
            return false;
        }

        return $data;
    }

}