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
        $serverId = isset($_POST['serverId']) ? (int)$_POST['serverId'] : 0;
        $resetData = isset($_POST['resetData']) && $_POST['resetData'] === 'true';

        // Если выбран новый сервер и запрос отмечен флагом сброса,
        // возвращаем последние логи без учета lastLogId
        if ($resetData) {
            $lastLogId = -1;
            $direction = 'newer';
        }

        $whereConditions = [];
        $params = [];

        // Добавляем условие для ID в зависимости от направления
        if ($lastLogId != -1) {
            if ($direction === 'newer') {
                $whereConditions[] = "`id` > ?";
                $params[] = $lastLogId;
            } else {
                $whereConditions[] = "`id` < ?";
                $params[] = $lastLogId;
            }
        }

        // Добавляем условие для типа, если он указан и не равен 0
        if ($type != 0) {
            $whereConditions[] = "`type` = ?";
            $params[] = $type;
        }

        // Добавляем условие для server_id, если он указан и не равен 0
        if ($serverId != 0) {
            $whereConditions[] = "`server_id` = ?";
            $params[] = $serverId;
        }

        // Формируем SQL запрос
        $sql = "SELECT * FROM `logs_all`";

        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(" AND ", $whereConditions);
        }

        $sql .= " ORDER BY `id` DESC LIMIT ?";
        $params[] = $limit;

        // Выполняем запрос
        $logs = sql::getRows($sql, $params);

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