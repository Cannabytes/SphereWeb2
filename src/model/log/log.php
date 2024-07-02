<?php

namespace Ofey\Logan22\model\log;

use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;

class log
{

    public static function getLogs($limit = null): array
    {
        if ($limit == null) {
            $logs = sql::getRows("SELECT * FROM `logs_all` ORDER BY `id` DESC");
        } else {
            $logs = sql::getRows("SELECT * FROM `logs_all` ORDER BY `id` DESC LIMIT ?", [$limit]);
        }

        return self::getLog($logs, $userJson = false);
    }

    public static function getNewLogs(): void
    {
        $logs = sql::getRows("SELECT * FROM `logs_all` WHERE `id` > ? ORDER BY `id` DESC", [$_POST['lastLogId']]);

        echo json_encode(self::getLog($logs, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param   array  $logs
     *
     * @return array
     */
    private static function getLog(array $logs, $userJson = false): array
    {
        if (empty($logs)) {
            return [];
        }


        foreach ($logs as &$log) {
            $user           = user::getUserId($log['user_id']);
            if($userJson) {
                $user = $user->toArray();
            }
            $log['user']    = $user;
            $log['date']    = date("d.m.Y H:i:s", $log['date']);
            $s              = json_decode($log['variables']);
            $values         = is_array($s) ? array_values($s) : [$s];
            $log['message'] = lang::get_phrase($log['phrase'], ...$values);
        }

        return $logs;
    }

}