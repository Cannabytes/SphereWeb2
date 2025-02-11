<?php

namespace Ofey\Logan22\component\plugins\sphere_forum\struct;

use DateTime;
use DateTimeZone;
use Exception;
use Ofey\Logan22\model\db\sql;

class forum_post
{
    private int $id;
    private int $threadId;
    private int $userId;
    private string $content;
    private string $createdAt;
    private string $updatedAt;

    private string $thread_title = '';
    private ?int $replyToId = null;
    private ?array $replyData = null;

    public bool $hasUnread = false;

    public function __construct(array $message)
    {
        $this->id = (int)$message['id'];
        $this->threadId = (int)$message['thread_id'];
        $this->userId = (int)$message['user_id'];
        $this->content = $message['content'];
        $this->createdAt = $message['created_at'];
        $this->updatedAt = $message['updated_at'];
        $this->thread_title = $message['thread_title'] ?? '';
        $this->replyToId = $message['reply_to_id'] ?? null;

    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function getThreadId(): int
    {
        return $this->threadId;
    }

    public function getTitle(): string
    {
        return $this->thread_title;
    }

    public function getReplyToId(): ?int
    {
        return $this->replyToId;
    }

    public function getReplyData(): ?array
    {
        if ($this->replyToId === null) {
            return null;
        }

        if ($this->replyData === null) {
            $this->replyData = sql::getRow(
                "SELECT p.*, u.name as user_name 
            FROM forum_posts p 
            LEFT JOIN users u ON p.user_id = u.id 
            WHERE p.id = ?",
                [$this->replyToId]
            );
        }

        return $this->replyData;
    }

    /**
     * Проверяет, можно ли еще редактировать сообщение
     * @param int $timeoutMinutes Таймаут в минутах
     * @return bool True если время редактирования НЕ истекло (прошло меньше timeoutMinutes)
     */
    public function isEditableByTime(int $timeoutMinutes): bool {
        try {
            $created = new DateTime($this->createdAt);
            $now = new DateTime();
            $diff = $now->getTimestamp() - $created->getTimestamp();
            $minutesPassed = floor($diff / 60);
            return $minutesPassed < $timeoutMinutes;
        } catch (Exception $e) {
            error_log("Error in isEditableByTime for post: " . $e->getMessage());
            return false;
        }
    }


}
