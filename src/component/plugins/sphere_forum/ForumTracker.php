<?php

namespace Ofey\Logan22\component\plugins\sphere_forum;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\notification\notification;
use Ofey\Logan22\model\user\user;

class ForumTracker {
    /**
     * Отслеживает просмотр темы пользователем
     *
     * @param int $threadId ID темы
     * @param int $lastPostId ID последнего поста в теме
     * @return void
     */
    public static function trackThreadView(int $threadId, int $lastPostId): void {
        if (!user::self()->isAuth()) {
            return;
        }

        $userId = user::self()->getId();

        sql::run(
            "INSERT INTO forum_user_thread_tracks 
            (user_id, thread_id, last_read_post_id, last_visit) 
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                last_read_post_id = GREATEST(last_read_post_id, ?),
                last_visit = NOW()",
            [$userId, $threadId, $lastPostId, $lastPostId]
        );
    }

    /**
     * Создает уведомления о новом ответе в теме
     *
     * @param int $threadId ID темы
     * @param int $postId ID нового поста
     * @param int $replyToPostId ID поста, на который ответили (null если ответ в тему)
     * @return void
     */
    public static function notifyAboutNewPost(int $threadId, int $postId, ?int $replyToPostId = null): void {
        $fromUserId = user::self()->getId();

        // Если это ответ на конкретное сообщение
        if ($replyToPostId !== null) {
            // Получаем автора исходного поста
            $originalPostAuthor = sql::getValue(
                "SELECT user_id FROM forum_posts WHERE id = ?",
                [$replyToPostId]
            );

            if ($originalPostAuthor && $originalPostAuthor !== $fromUserId) {
                self::createNotification(
                    $originalPostAuthor,
                    $threadId,
                    $postId,
                    $fromUserId,
                    'reply_to_post'
                );
            }
        }

        // Уведомляем подписчиков темы
        $subscribers = sql::getRows(
            "SELECT DISTINCT ut.user_id 
            FROM forum_user_thread_tracks ut
            WHERE ut.thread_id = ? 
            AND ut.is_subscribed = 1 
            AND ut.user_id != ?",
            [$threadId, $fromUserId]
        );

        foreach ($subscribers as $subscriber) {
            self::createNotification(
                $subscriber['user_id'],
                $threadId,
                $postId,
                $fromUserId,
                'reply_to_thread'
            );
        }
    }

    /**
     * Создает уведомление
     */
    private static function createNotification(
        int $userId,
        int $threadId,
        int $postId,
        int $fromUserId,
        string $type
    ): void {
        sql::run(
            "INSERT INTO forum_notifications 
            (user_id, thread_id, post_id, from_user_id, notification_type) 
            VALUES (?, ?, ?, ?, ?)",
            [$userId, $threadId, $postId, $fromUserId, $type]
        );

        // Добавляем уведомление в общую систему уведомлений
        $thread = sql::getRow(
            "SELECT title FROM forum_threads WHERE id = ?",
            [$threadId]
        );

        $fromUser = user::getUserId($fromUserId);

        $notificationText = match($type) {
            'reply_to_post' => sprintf(
                "Пользователь %s ответил на ваше сообщение в теме '%s'",
                $fromUser->getName(),
                $thread['title']
            ),
            'reply_to_thread' => sprintf(
                "Новый ответ от %s в теме '%s'",
                $fromUser->getName(),
                $thread['title']
            )
        };

    }

    /**
     * Управление подпиской на уведомления темы
     */
    public static function toggleThreadSubscription(int $threadId, bool $subscribed): void {
        if (!user::self()->isAuth()) {
            return;
        }

        $userId = user::self()->getId();

        sql::run(
            "INSERT INTO forum_user_thread_tracks 
            (user_id, thread_id, last_read_post_id, is_subscribed) 
            VALUES (?, ?, 
                (SELECT COALESCE(MAX(id), 0) FROM forum_posts WHERE thread_id = ?), 
                ?
            )
            ON DUPLICATE KEY UPDATE is_subscribed = ?",
            [$userId, $threadId, $threadId, $subscribed, $subscribed]
        );
        board::success('Обновлено');
    }

    /**
     * Проверяет, есть ли новые сообщения в теме
     */
    public static function hasUnreadPosts(int $threadId): bool {
        if (!user::self()->isAuth()) {
            return false;
        }

        $userId = user::self()->getId();

        $lastReadPostId = sql::getValue(
            "SELECT last_read_post_id 
            FROM forum_user_thread_tracks 
            WHERE user_id = ? AND thread_id = ?",
            [$userId, $threadId]
        );

        if (!$lastReadPostId) {
            return true;
        }

        $newerPostExists = sql::getValue(
            "SELECT 1 
            FROM forum_posts 
            WHERE thread_id = ? AND id > ? 
            LIMIT 1",
            [$threadId, $lastReadPostId]
        );

        return (bool)$newerPostExists;
    }

    /**
     * Получает количество непрочитанных уведомлений
     */
    public static function getUnreadNotificationsCount(): int {
        if (!user::self()->isAuth()) {
            return 0;
        }

        return (int)sql::getValue(
            "SELECT COUNT(*) 
            FROM forum_notifications 
            WHERE user_id = ? AND is_read = 0",
            [user::self()->getId()]
        );
    }

    /**
     * Помечает уведомления как прочитанные
     */
    public static function markNotificationsAsRead(array $notificationIds): void {
        if (empty($notificationIds)) {
            return;
        }

        $placeholders = str_repeat('?,', count($notificationIds) - 1) . '?';

        sql::run(
            "UPDATE forum_notifications 
            SET is_read = 1 
            WHERE id IN ($placeholders) AND user_id = ?",
            [...$notificationIds, user::self()->getId()]
        );
    }

    /**
     * Получает список последних уведомлений пользователя
     *
     * @param int $limit Количество уведомлений
     * @return array Массив уведомлений
     */
    public static function getLatestNotifications(int $limit = 5): array {
        if (!user::self()->isAuth()) {
            return [];
        }

        return sql::getRows(
            "SELECT 
            n.*,
            t.title as thread_title,
            u.name as from_user_name,
            p.content as post_preview
        FROM forum_notifications n
        JOIN forum_threads t ON n.thread_id = t.id
        JOIN users u ON n.from_user_id = u.id
        JOIN forum_posts p ON n.post_id = p.id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT ?",
            [user::self()->getId(), $limit]
        );
    }
}