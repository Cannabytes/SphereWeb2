<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 26.02.2026
 */

namespace Ofey\Logan22\controller\user;

use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\template\tpl;

class auth_log {

    /**
     * Отображение истории входов пользователя
     */
    public static function show(): void
    {
        validation::user_protection();
        
        $userId = user::self()->getId();
        
        // Получаем логи входов из БД, исключаем fingerprint и signature
        $authLogs = sql::getRows(
            "SELECT `id`, `user_id`, `ip`, `country`, `city`, `browser`, `date`, `os`, `device`, `user_agent` 
             FROM `user_auth_log` 
             WHERE `user_id` = ? 
             ORDER BY `date` DESC 
             LIMIT 100",
            [$userId]
        );
        
        // Добавляем отформатированные даты для шаблона
        foreach ($authLogs as &$log) {
            $log['date_formatted'] = $log['date'] ? date('d.m.Y H:i:s', strtotime((string)$log['date'])) : '-';
            $log['country_display'] = !empty($log['country']) ? $log['country'] : '-';
            $log['city_display'] = !empty($log['city']) ? $log['city'] : '-';
            $log['browser_display'] = !empty($log['browser']) ? $log['browser'] : '-';
            $log['os_display'] = !empty($log['os']) ? $log['os'] : '-';
            $log['device_display'] = !empty($log['device']) ? $log['device'] : '-';
        }
        
        tpl::addVar([
            'auth_logs' => $authLogs,
            'total_logins' => count($authLogs),
        ]);
        
        tpl::display("/auth_log.html");
    }

}
