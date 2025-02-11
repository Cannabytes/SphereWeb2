<?php

namespace Ofey\Logan22\component\plugins\sphere_forum\system;

use Exception;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;

class AntiFlood {
    // Константы для типов действий
    public const TYPE_POST = 'post';
    public const TYPE_THREAD = 'thread';

    // Настройки для сообщений
    private const POST_MAX_PER_MINUTE = 10;
    private const POST_MAX_PER_HOUR = 60*3;
    private const POST_MIN_INTERVAL = 5;
    private const POST_COOLDOWN = 60*5;

    // Настройки для создания тем
    private const THREAD_MAX_PER_MINUTE = 3;
    private const THREAD_MAX_PER_HOUR = 10;
    private const THREAD_MIN_INTERVAL = 60;
    private const THREAD_COOLDOWN = 60*10;

    private int $userId;
    private string $activityType;
    private ?array $activity = null;

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
             VALUES (?, ?, NOW(), 1, NULL)",
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

    private function getSettings(): array {
        return $this->activityType === self::TYPE_POST ?
            [
                'max_per_minute' => self::POST_MAX_PER_MINUTE,
                'max_per_hour' => self::POST_MAX_PER_HOUR,
                'min_interval' => self::POST_MIN_INTERVAL,
                'cooldown' => self::POST_COOLDOWN,
                'name' => 'сообщений'
            ] :
            [
                'max_per_minute' => self::THREAD_MAX_PER_MINUTE,
                'max_per_hour' => self::THREAD_MAX_PER_HOUR,
                'min_interval' => self::THREAD_MIN_INTERVAL,
                'cooldown' => self::THREAD_COOLDOWN,
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
            $lastActionTime = strtotime($this->activity['last_action_time']);
            $timePassed = time() - $lastActionTime;

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