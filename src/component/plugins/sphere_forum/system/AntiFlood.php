<?php

namespace Ofey\Logan22\component\plugins\sphere_forum\system;

use Exception;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;

class AntiFlood {
    // Константы для типов действий
    public const TYPE_POST = 'post';
    public const TYPE_THREAD = 'thread';

    private int $userId;
    private string $activityType;
    private ?array $activity = null;
    private ?array $forumSettings = null;

    public function __construct(string $activityType) {
        if (!user::self()->isAuth()) {
            throw new Exception("Необходимо авторизоваться");
        }

        if (!in_array($activityType, [self::TYPE_POST, self::TYPE_THREAD])) {
            throw new Exception("Неверный тип активности");
        }

        $this->userId = user::self()->getId();
        $this->activityType = $activityType;

        // Очищаем устаревшие блокировки
        $this->clearExpiredCooldowns();

        $this->loadUserActivity();
    }

    private function loadUserActivity(): void {
        // Сначала проверим наличие записи
        $activity = sql::getRow(
            "SELECT * FROM forum_user_activity 
         WHERE user_id = ? AND activity_type = ?",
            [$this->userId, $this->activityType]
        );

        if (!$activity) {
            // Если записи нет - создаем новую
            sql::run(
                "INSERT INTO forum_user_activity 
             (user_id, activity_type, last_action_time, actions_count, cooldown_until) 
             VALUES (?, ?, NOW(), 0, NULL)",
                [$this->userId, $this->activityType]
            );

            // Загружаем только что созданную запись
            $activity = sql::getRow(
                "SELECT * FROM forum_user_activity 
             WHERE user_id = ? AND activity_type = ?",
                [$this->userId, $this->activityType]
            );
        }

        $this->activity = $activity;
    }

    /**
     * Получает настройки форума из базы данных
     */
    private function getForumSettings(): array {
        if ($this->forumSettings !== null) {
            return $this->forumSettings;
        }

        $settings = sql::getRow(
            "SELECT setting FROM settings WHERE `key` = '__FORUM_SETTINGS__' LIMIT 1"
        );

        if ($settings && !empty($settings['setting'])) {
            $decoded = json_decode($settings['setting'], true);
            $this->forumSettings = $decoded ?: $this->getDefaultSettings();
        } else {
            $this->forumSettings = $this->getDefaultSettings();
        }

        return $this->forumSettings;
    }

    /**
     * Возвращает настройки по умолчанию для антифлуда
     */
    private function getDefaultSettings(): array {
        return [
            'post_max_per_minute' => 10,
            'post_max_per_hour' => 180,
            'post_min_interval' => 5,
            'post_cooldown' => 300,
            'thread_max_per_minute' => 3,
            'thread_max_per_hour' => 10,
            'thread_min_interval' => 60,
            'thread_cooldown' => 600,
        ];
    }

    /**
     * Получает настройки антифлуда в зависимости от типа активности
     */
    private function getSettings(): array {
        $forumSettings = $this->getForumSettings();
        
        return $this->activityType === self::TYPE_POST ?
            [
                'max_per_minute' => $forumSettings['post_max_per_minute'] ?? 10,
                'max_per_hour' => $forumSettings['post_max_per_hour'] ?? 180,
                'min_interval' => $forumSettings['post_min_interval'] ?? 5,
                'cooldown' => $forumSettings['post_cooldown'] ?? 300,
                'name' => 'сообщений'
            ] :
            [
                'max_per_minute' => $forumSettings['thread_max_per_minute'] ?? 3,
                'max_per_hour' => $forumSettings['thread_max_per_hour'] ?? 10,
                'min_interval' => $forumSettings['thread_min_interval'] ?? 60,
                'cooldown' => $forumSettings['thread_cooldown'] ?? 600,
                'name' => 'тем'
            ];
    }

    public function checkFlood(): void {
        // Пропускаем проверку для администраторов
        if (user::self()->isAdmin()) {
            return;
        }

        // Сначала очищаем устаревшие блокировки
        $this->clearExpiredCooldowns();

        // Проверяем существует ли активность для данного пользователя
        if ($this->activity === null) {
            $this->loadUserActivity();
        }

        $settings = $this->getSettings();

        // Проверяем блокировку
        if (!empty($this->activity['cooldown_until'])) {
            $cooldownTime = strtotime($this->activity['cooldown_until']);
            if ($cooldownTime > time()) {
                $remainingTime = ceil(($cooldownTime - time()) / 60);
                throw new Exception("Вы временно не можете создавать {$settings['name']}. Осталось {$remainingTime} минут.");
            }
        }

        // Для новых тем проверяем только интервал
        if ($this->activityType === self::TYPE_THREAD) {
            // Если у пользователя ещё не было действий — позволяем создать первую тему сразу
            if ((int)($this->activity['actions_count'] ?? 0) === 0) {
                return;
            }

            // Дополнительная надёжная проверка: если у пользователя вообще нет созданных тем в БД,
            // позволяем создать первую тему (на случай, если запись в forum_user_activity некорректна).
            $userThreads = sql::getValue(
                "SELECT COUNT(*) FROM forum_threads WHERE user_id = ?",
                [$this->userId]
            );
            if ((int)$userThreads === 0) {
                return;
            }

            $lastActionTime = strtotime($this->activity['last_action_time']);
            $timePassed = time() - $lastActionTime;
            // На некоторых серверах время БД/PHP может отличаться — не допускаем отрицательного
            // значения elapsed, иначе блокировка срабатывает некорректно.
            if ($timePassed < 0) {
                $timePassed = 0;
            }
            if ($timePassed < $settings['min_interval']) {
                $timeToWait = $settings['min_interval'] - $timePassed;
                throw new Exception("Пожалуйста, подождите {$timeToWait} секунд перед созданием новой темы.");
            }
            return;
        }

        // Для сообщений проверяем количество
        $actionsLastMinute = sql::getValue(
            "SELECT COUNT(*) FROM forum_posts 
         WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)",
            [$this->userId]
        );

        if ($actionsLastMinute >= $settings['max_per_minute']) {
            $this->applyCooldown();
            throw new Exception("Превышен лимит {$settings['name']} в минуту. Подождите немного.");
        }

        $actionsLastHour = sql::getValue(
            "SELECT COUNT(*) FROM forum_posts 
         WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$this->userId]
        );

        if ($actionsLastHour >= $settings['max_per_hour']) {
            $this->applyCooldown();
            throw new Exception("Превышен лимит {$settings['name']} в час. Подождите некоторое время.");
        }
    }

    private function clearExpiredCooldowns(): void {
        // Очищаем все устаревшие блокировки
        sql::run(
            "UPDATE forum_user_activity 
         SET cooldown_until = NULL,
             actions_count = 0
         WHERE cooldown_until IS NOT NULL 
         AND cooldown_until < NOW()"
        );
    }


    private function applyCooldown(): void {
        $settings = $this->getSettings();
        $violations = $this->getViolationsCount();

        // Прогрессивное увеличение времени блокировки
        $cooldownTime = $settings['cooldown'] * pow(2, $violations);

        // Явно указываем время блокировки
        $cooldownUntil = date('Y-m-d H:i:s', time() + $cooldownTime);

        sql::run(
            "UPDATE forum_user_activity 
         SET cooldown_until = ?, 
             actions_count = actions_count + 1
         WHERE user_id = ? AND activity_type = ?",
            [$cooldownUntil, $this->userId, $this->activityType]
        );

        // Обновляем локальные данные
        $this->activity['cooldown_until'] = $cooldownUntil;
        $this->activity['actions_count']++;

        if ($violations >= 3) {
            $this->notifyModerators();
        }
    }

    private function getViolationsCount(): int {
        return sql::getValue(
            "SELECT COUNT(*) FROM forum_user_activity 
            WHERE user_id = ? AND activity_type = ? AND cooldown_until IS NOT NULL",
            [$this->userId, $this->activityType]
        );
    }

    public function updateActivity(): void {
        sql::run(
            "UPDATE forum_user_activity 
            SET last_action_time = NOW(), 
                actions_count = actions_count + 1 
            WHERE user_id = ? AND activity_type = ?",
            [$this->userId, $this->activityType]
        );
    }


    private function notifyModerators(): void {
        // Здесь реализация уведомления модераторов
        // Можно отправлять через внутреннюю систему сообщений или email
    }
}