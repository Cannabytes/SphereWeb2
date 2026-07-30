<?php

namespace Ofey\Logan22\component\plugins\sphere_forum\system;

use Exception;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;

class AntiFlood {

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


        $this->clearExpiredCooldowns();

        $this->loadUserActivity();
    }

    private function loadUserActivity(): void {

        $activity = sql::getRow(
            "SELECT * FROM forum_user_activity
         WHERE user_id = ? AND activity_type = ?",
            [$this->userId, $this->activityType]
        );

        if (!$activity) {

            $currentTime = time();
            sql::run(
                "INSERT INTO forum_user_activity
             (user_id, activity_type, last_action_time, actions_count, cooldown_until)
             VALUES (?, ?, ?, 0, NULL)",
                [$this->userId, $this->activityType, $currentTime]
            );


            $activity = sql::getRow(
                "SELECT * FROM forum_user_activity
             WHERE user_id = ? AND activity_type = ?",
                [$this->userId, $this->activityType]
            );
        }

        if (!is_array($activity)) {
            $activity = [
                'id' => null,
                'user_id' => $this->userId,
                'activity_type' => $this->activityType,
                'last_action_time' => time(),
                'actions_count' => 0,
                'cooldown_until' => null,
            ];
        }

        $this->activity = $activity;
    }




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

        if (user::self()->isAdmin()) {
            return;
        }


        $this->clearExpiredCooldowns();


        if ($this->activity === null) {
            $this->loadUserActivity();
        }

        $settings = $this->getSettings();


        if (!empty($this->activity['cooldown_until'])) {
            $cooldownTime = (int)$this->activity['cooldown_until'];
            $currentTime = time();
            if ($cooldownTime > $currentTime) {
                $remainingTime = ceil(($cooldownTime - $currentTime) / 60);
                throw new Exception("Вы временно не можете создавать {$settings['name']}. Осталось {$remainingTime} минут.");
            }
        }


        if ($this->activityType === self::TYPE_THREAD) {

            if ((int)($this->activity['actions_count'] ?? 0) === 0) {
                return;
            }



            $userThreads = sql::getValue(
                "SELECT COUNT(*) FROM forum_threads WHERE user_id = ?",
                [$this->userId]
            );
            if ((int)$userThreads === 0) {
                return;
            }

            $lastActionTime = (int)$this->activity['last_action_time'];
            $timePassed = time() - $lastActionTime;

            if ($timePassed < $settings['min_interval']) {
                $timeToWait = $settings['min_interval'] - $timePassed;
                throw new Exception("Пожалуйста, подождите {$timeToWait} секунд перед созданием новой темы.");
            }
            return;
        }


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

        $currentTime = time();
        sql::run(
            "UPDATE forum_user_activity
         SET cooldown_until = NULL,
             actions_count = 0
         WHERE cooldown_until IS NOT NULL
         AND cooldown_until < ?",
            [$currentTime]
        );
    }


    private function applyCooldown(): void {
        $settings = $this->getSettings();
        $violations = $this->getViolationsCount();


        $cooldownTime = $settings['cooldown'] * pow(2, $violations);


        $cooldownUntil = time() + $cooldownTime;

        sql::run(
            "UPDATE forum_user_activity
         SET cooldown_until = ?,
             actions_count = actions_count + 1
         WHERE user_id = ? AND activity_type = ?",
            [$cooldownUntil, $this->userId, $this->activityType]
        );


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
        $currentTime = time();
        sql::run(
            "UPDATE forum_user_activity
            SET last_action_time = ?,
                actions_count = actions_count + 1
            WHERE user_id = ? AND activity_type = ?",
            [$currentTime, $this->userId, $this->activityType]
        );
    }


    private function notifyModerators(): void {


    }
}