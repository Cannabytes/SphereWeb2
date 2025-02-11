<?php

namespace Ofey\Logan22\component\plugins\sphere_forum\struct;

use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;

class forum_thread
{
    private int $id;
    private int $categoryId;
    private int $authorId;
    private string $title;
    private string $createdAt;
    private string $updatedAt;
    private int $views;
    private int $replies;
    private ?int $firstMessageId;
    private ?int $lastReplyUserId;
    private ?int $lastPostId;
    private bool $isPinned;
    private bool $isClosed;
    private bool $isApproved;
    public bool $hasUnread = false;
    private ?int $poll_id = null;

    /**
     * Конструктор для инициализации данных темы.
     * @param array|false $thread Данные темы из базы.
     */
    public function __construct(array $thread)
    {
        $this->id = (int)$thread['id'];
        $this->categoryId = (int)$thread['category_id'];
        $this->authorId = (int)$thread['user_id'];
        $this->title = $thread['title'];
        $this->createdAt = $thread['created_at'];
        $this->updatedAt = $thread['updated_at'];
        $this->views = (int)$thread['views'];
        $this->replies = (int)$thread['replies'];
        $this->firstMessageId = $thread['first_message_id'] !== null ? (int)$thread['first_message_id'] : null;
        $this->lastReplyUserId = $thread['last_reply_user_id'] !== null ? (int)$thread['last_reply_user_id'] : null;
        $this->lastPostId = $thread['last_post_id'] !== null ? (int)$thread['last_post_id'] : null;
        $this->isPinned = (bool)$thread['is_pinned'];
        $this->isClosed = (bool)$thread['is_closed'];
        $this->isApproved = (bool)$thread['is_approved'];
        $this->poll_id = $thread['poll_id'] !== null ? (int)$thread['poll_id'] : null;
    }

    // Добавляем геттер и сеттер
    public function hasUnread(): bool {
        return $this->hasUnread;
    }

    public function setHasUnread(bool $hasUnread): void {
        $this->hasUnread = $hasUnread;
    }

    public function isApproved(): bool
    {
        return $this->isApproved;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    public function getAuthorId(): int
    {
        return $this->authorId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getName()
    {
        return $this->title;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function getViews(): int
    {
        return $this->views;
    }

    public function getReplies(): int
    {
        return $this->replies;
    }

    public function getFirstMessageId(): ?int
    {
        return $this->firstMessageId;
    }

    public function getLastReplyUserId(): ?int
    {
        return $this->lastReplyUserId;
    }

    public function getLastPostId(): ?int
    {
        return $this->lastPostId;
    }

    public function isPinned(): bool
    {
        return $this->isPinned;
    }

    public function isClosed(): bool
    {
        return $this->isClosed;
    }

    public function getPageCount(int $postsPerPage = 10): int {
        return (int)ceil(($this->replies) / $postsPerPage);
    }

    /**
     * Проверяет, может ли пользователь удалить тему
     *
     * @param forum_category $category Категория, к которой принадлежит тема
     * @return bool Возвращает true, если тему можно удалить
     */
    public function canUserDeleteOwnThread(forum_category $category): bool {
        // Если пользователь не автор темы - нельзя удалить
        if ($this->authorId !== user::self()->getId()) {
            return false;
        }

        // Если в категории запрещено удаление тем - нельзя удалить
        if (!$category->canUsersDeleteOwnThreads()) {
            return false;
        }

        // Проверяем не истекло ли время для удаления
        $createdTime = strtotime($this->createdAt);
        $currentTime = time();

        // Получаем разрешенное время для удаления в минутах
        $timeoutMinutes = $category->getThreadDeleteTimeoutMinutes();

        // Считаем сколько минут прошло с момента создания
        $minutesPassed = ($currentTime - $createdTime) / 60;

        // Возвращаем true если не превышен лимит времени
        return $minutesPassed <= $timeoutMinutes;
    }

    /**
     * Проверяет наличие непрочитанных сообщений в теме
     * @return bool
     */
    public function hasUnreadPosts(): bool {
        if (!user::self()->isAuth()) {
            return false;
        }

        // Проверяем наличие записи в таблице отслеживания
        $trackInfo = sql::getRow(
            "SELECT last_read_post_id 
            FROM forum_user_thread_tracks 
            WHERE user_id = ? AND thread_id = ?",
            [user::self()->getId(), $this->id]
        );

        // Если нет записи об отслеживании - тема считается непрочитанной
        if (!$trackInfo) {
            return true;
        }

        // Проверяем наличие новых сообщений после последнего прочитанного
        $hasNewer = sql::getValue(
            "SELECT EXISTS(
                SELECT 1 FROM forum_posts 
                WHERE thread_id = ? 
                AND id > ? 
                LIMIT 1
            )",
            [$this->id, $trackInfo['last_read_post_id']]
        );

        return (bool)$hasNewer;
    }

    public function getPoll(): ?ForumPoll {
        if ($this->poll_id === null) {
            return null;
        }

        $pollData = sql::getRow(
            "SELECT * FROM forum_polls WHERE id = ?",
            [$this->poll_id]
        );

        if (!$pollData) {
            return null;
        }

        // Load poll options
        $pollData['options'] = sql::getRows(
            "SELECT id, text, votes_count 
        FROM forum_poll_options 
        WHERE poll_id = ?",
            [$this->poll_id]
        );

        return new ForumPoll($pollData);
    }

    public function getPollId(): ?int {
        return $this->poll_id;
    }
}
