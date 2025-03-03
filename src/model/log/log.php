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
        if(empty($logs)){
            return [];
        }
        return self::getLog($logs, $userJson = false);
    }

    public static function getNewLogs(): void
    {
        $type = $_POST['type'];
        $lastLogId = $_POST['lastLogId'];
        $direction = $_POST['direction'] ?? 'newer';
        $limit = $_POST['limit'] ?? 50;

        if ($lastLogId == -1) {
            // Если lastLogId равен -1, возвращаем последние логи указанного типа
            if ($type == 0) {
                $logs = sql::getRows("SELECT * FROM `logs_all` ORDER BY `id` DESC LIMIT ?", [$limit]);
            } else {
                $logs = sql::getRows("SELECT * FROM `logs_all` WHERE `type` = ? ORDER BY `id` DESC LIMIT ?", [$type, $limit]);
            }
        } else {
            // Логика получения логов в зависимости от направления
            if ($direction === 'newer') {
                // Получаем более новые логи (с ID > lastLogId)
                if ($type == 0) {
                    $logs = sql::getRows("SELECT * FROM `logs_all` WHERE `id` > ? ORDER BY `id` DESC LIMIT ?", [$lastLogId, $limit]);
                } else {
                    $logs = sql::getRows("SELECT * FROM `logs_all` WHERE `id` > ? AND `type` = ? ORDER BY `id` DESC LIMIT ?", [$lastLogId, $type, $limit]);
                }
            } else {
                // Получаем более старые логи (с ID < lastLogId)
                if ($type == 0) {
                    $logs = sql::getRows("SELECT * FROM `logs_all` WHERE `id` < ? ORDER BY `id` DESC LIMIT ?", [$lastLogId, $limit]);
                } else {
                    $logs = sql::getRows("SELECT * FROM `logs_all` WHERE `id` < ? AND `type` = ? ORDER BY `id` DESC LIMIT ?", [$lastLogId, $type, $limit]);
                }
            }
        }

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
            $s              = json_decode($log['variables']);
            $values         = is_array($s) ? array_values($s) : [$s];
            $log['message'] = lang::get_phrase($log['phrase'], ...$values);
        }

        return $logs;
    }

}