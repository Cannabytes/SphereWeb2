<?php

namespace Ofey\Logan22\component\plugins\sphere_forum\struct;

use Ofey\Logan22\model\db\sql;

class forum_category
{
    private int $id;
    private ?int $parentId;
    private string $name;
    private string $description;
    private string $createdAt;
    private string $updatedAt;
    private ?int $lastReplyUserId;
    private ?int $lastPostId;
    private ?int $lastThreadId;
    private int $postCount;
    private int $viewCount;
    private int $threadCount;
    private false|string $iconSvg;
    private ?string $link;
    private bool $canCreateTopics;
    private bool $canReplyTopics;
    private bool $canViewTopics;
    private bool $isModerated;
    private int $sortOrder = 0;

    /** @var forum_category[] */
    private array $subcategories = [];
    private null|false|forum_thread $thread = null;
    private bool $isHidden;
    private bool $canUsersDeleteOwnThreads;
    private bool $canUsersDeleteOwnPosts;
    private int $editTimeoutMinutes;
    private bool $notifyTelegram;
    private int $maxPostLength;
    private int $threadDeleteTimeoutMinutes;
    private bool $hideLastTopic;
    private string $titleColor;

    /**
     * Конструктор категории.
     * @param array $category Данные категории.
     */
    public function __construct(array $category)
    {
        $this->id = (int)$category['id'];
        $this->parentId = $category['parent_id'] !== null ? (int)$category['parent_id'] : null;
        $this->name = $category['name'];
        $this->description = $category['description'];
        $this->createdAt = $category['created_at'];
        $this->updatedAt = $category['updated_at'];
        $this->lastReplyUserId = isset($category['last_reply_user_id']) ? (int)$category['last_reply_user_id'] : null;
        $this->lastPostId = isset($category['last_post_id']) ? (int)$category['last_post_id'] : null;
        $this->lastThreadId = isset($category['last_thread_id']) ? (int)$category['last_thread_id'] : null;
        $this->postCount = (int)$category['post_count'];
        $this->viewCount = (int)$category['view_count'];
        $this->threadCount = (int)$category['thread_count'];
        $this->iconSvg = $category['icon_svg'] !== null ? $category['icon_svg'] : false;
        $this->link = $category['link'] ?? null;
        $this->isHidden = (bool)($category['is_hidden'] ?? false);
        $this->canCreateTopics = (bool)($category['can_create_topics'] ?? true);
        $this->canReplyTopics = (bool)($category['can_reply_topics'] ?? true);
        $this->canViewTopics = (bool)($category['can_view_topics'] ?? true);
        $this->isModerated = (bool)($category['is_moderated'] ?? true);
        $this->sortOrder = (int)($category['sort_order'] ?? 0);
        $this->canUsersDeleteOwnThreads = (bool)($category['can_users_delete_own_threads'] ?? false);
        $this->canUsersDeleteOwnPosts = (bool)($category['can_users_delete_own_posts'] ?? false);
        $this->editTimeoutMinutes = (int)($category['edit_timeout_minutes'] ?? 30);
        $this->notifyTelegram = (bool)($category['notify_telegram'] ?? false);
        $this->maxPostLength = (int)($category['max_post_length'] ?? 10000);
        $this->threadDeleteTimeoutMinutes = (int)($category['thread_delete_timeout_minutes'] ?? 30);
        $this->hideLastTopic = (bool)($category['hide_last_topic'] ?? false);
        $this->titleColor = $category['title_color'] ?? 'dark';
    }


    public function getTitleColor(): string {
        return $this->titleColor ?? 'dark';
    }

    public function isHidden(): bool
    {
        return $this->isHidden;
    }

    /**
     * Определяет, нужно ли скрывать информацию о последней теме
     * @return bool
     */
    public function shouldHideLastTopic(): bool
    {
        return $this->hideLastTopic;
    }

    public function isModerated(): bool
    {
        return $this->isModerated;
    }

    public function canViewTopics(): bool
    {
        return $this->canViewTopics;
    }

    public function canReplyTopics(): bool
    {
        return $this->canReplyTopics;
    }

    public function canCreateTopics(): bool
    {
        return $this->canCreateTopics;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    /**
     * Связывает подкатегории.
     * @param forum_category[] $allCategories Все категории для поиска подкатегорий.
     */
    public function loadSubcategories(array $allCategories): void
    {
        $this->subcategories = [];
        foreach ($allCategories as $category) {
            if ($category->getParentId() === $this->id) {
                $this->subcategories[] = $category;
            }
        }

        // Сортируем подкатегории по sortOrder
        usort($this->subcategories, function($a, $b) {
            return $a->getSortOrder() - $b->getSortOrder();
        });
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    /**
     * Возвращает список подкатегорий.
     * @return array|false
     */
    public function getSubcategories(): array|false
    {
        return $this->subcategories !== [] ? $this->subcategories : false;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function getLastReplyUserId(): ?int
    {
        return $this->lastReplyUserId;
    }

    public function getLastPostId(): ?int
    {
        return $this->lastPostId;
    }

    public function getLastThread(): null|false|forum_thread
    {
        if ($this->thread !== null) {
            return $this->thread;
        }

        $thread = sql::getRow("SELECT * FROM `forum_threads` WHERE `id` = ? LIMIT 1", [$this->getLastThreadId()]);
        if (!$thread) {
            return $this->thread = false;
        }
        return $this->thread = new forum_thread($thread);
    }

    public function getLastThreadId(): ?int
    {
        return $this->lastThreadId;
    }

    public function getPostCount(): int
    {
        return $this->postCount;
    }

    public function getViewCount(): int
    {
        return $this->viewCount;
    }

    public function getThreadCount(): int
    {
        return $this->threadCount;
    }

    public function getIconSvg(): false|string
    {
        return $this->iconSvg;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function canUsersDeleteOwnThreads(): bool
    {
        return $this->canUsersDeleteOwnThreads;
    }

    public function canUsersDeleteOwnPosts(): bool
    {
        return $this->canUsersDeleteOwnPosts;
    }

    public function getEditTimeoutMinutes(): int
    {
        return $this->editTimeoutMinutes;
    }

    public function shouldNotifyTelegram(): bool
    {
        return $this->notifyTelegram;
    }

    public function getMaxPostLength(): int
    {
        return $this->maxPostLength;
    }

    /**
     * Возвращает время в минутах, в течение которого можно удалить тему
     * @return int
     */
    public function getThreadDeleteTimeoutMinutes(): int
    {
        return $this->threadDeleteTimeoutMinutes;
    }
}