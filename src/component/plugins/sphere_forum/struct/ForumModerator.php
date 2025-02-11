<?php

namespace Ofey\Logan22\component\plugins\sphere_forum\struct;

use Ofey\Logan22\model\db\sql;

class ForumModerator implements \JsonSerializable {

    private int $id;
    private string $name;
    private string $categoryName;
    private int $userId;
    private ?int $categoryId;
    private bool $canDeleteThreads;
    private bool $canDeletePosts;
    private bool $canEditPosts;
    private bool $canMoveThreads;
    private bool $canPinThreads;
    private bool $canCloseThreads;
    private bool $canApproveThreads;
    private string $createdAt;
    private int $createdBy;

    /**
     * Проверяет, есть ли у пользователя права модератора на определенную категорию
     *
     * @param int $userId ID пользователя
     * @param int|null $categoryId ID категории или null для всех категорий
     * @return bool
     */
    public static function isUserModerator(int $userId, ?int $categoryId = null): bool {
        // Если категория не указана, проверяем только на глобального модератора
        if ($categoryId === null) {
            $moderator = sql::getRow(
                "SELECT * FROM forum_moderators 
                WHERE user_id = ? AND category_id IS NULL",
                [$userId]
            );
        } else {
            // Если категория указана, проверяем как конкретную категорию, так и глобальные права
            $moderator = sql::getRow(
                "SELECT * FROM forum_moderators 
                WHERE user_id = ? AND (category_id IS NULL OR category_id = ?)",
                [$userId, $categoryId]
            );
        }

        return $moderator !== false && $moderator !== null;
    }

    /**
     * Проверяет конкретное право модератора
     *
     * @param int $userId ID пользователя
     * @param int|null $categoryId ID категории или null для всех категорий
     * @param string $permission Название права
     * @return bool
     */
    public static function hasPermission(int $userId, ?int $categoryId, string $permission): bool {
        // Аналогичная логика для проверки прав
        if ($categoryId === null) {
            $moderator = sql::getRow(
                "SELECT $permission FROM forum_moderators 
                WHERE user_id = ? AND category_id IS NULL",
                [$userId]
            );
        } else {
            $moderator = sql::getRow(
                "SELECT $permission FROM forum_moderators 
                WHERE user_id = ? AND (category_id IS NULL OR category_id = ?)",
                [$userId, $categoryId]
            );
        }

        return (bool)($moderator[$permission] ?? false);
    }

    /**
     * Логирует действие модератора
     */
    public static function logAction(int $moderatorId, string $action, string $targetType, int $targetId, ?string $reason = null): void {
        sql::run(
            "INSERT INTO forum_moderator_log 
            (moderator_id, action, target_type, target_id, reason) 
            VALUES (?, ?, ?, ?, ?)",
            [$moderatorId, $action, $targetType, $targetId, $reason]
        );
    }


    public function getName(): string
    {
        return $this->name;
    }

    public function getCategoryName(): ?string
    {
        return $this->categoryName;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function isCanDeleteThreads(): bool
    {
        return $this->canDeleteThreads;
    }

    public function isCanDeletePosts(): bool
    {
        return $this->canDeletePosts;
    }

    public function isCanEditPosts(): bool
    {
        return $this->canEditPosts;
    }

    public function isCanMoveThreads(): bool
    {
        return $this->canMoveThreads;
    }

    public function isCanPinThreads(): bool
    {
        return $this->canPinThreads;
    }

    public function isCanCloseThreads(): bool
    {
        return $this->canCloseThreads;
    }

    public function isCanApproveThreads(): bool
    {
        return $this->canApproveThreads;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getCreatedBy(): int
    {
        return $this->createdBy;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setCategoryName(?string $categoryName): void {
        $this->categoryName = $categoryName ?? "";
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function setCategoryId(?int $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    public function setCanDeleteThreads(bool $canDeleteThreads): void
    {
        $this->canDeleteThreads = $canDeleteThreads;
    }

    public function setCanDeletePosts(bool $canDeletePosts): void
    {
        $this->canDeletePosts = $canDeletePosts;
    }

    public function setCanEditPosts(bool $canEditPosts): void
    {
        $this->canEditPosts = $canEditPosts;
    }

    public function setCanMoveThreads(bool $canMoveThreads): void
    {
        $this->canMoveThreads = $canMoveThreads;
    }

    public function setCanPinThreads(bool $canPinThreads): void
    {
        $this->canPinThreads = $canPinThreads;
    }

    public function setCanCloseThreads(bool $canCloseThreads): void
    {
        $this->canCloseThreads = $canCloseThreads;
    }

    public function setCanApproveThreads(bool $canApproveThreads): void
    {
        $this->canApproveThreads = $canApproveThreads;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setCreatedBy(int $createdBy): void
    {
        $this->createdBy = $createdBy;
    }

    /**
     * Определяет, как объект должен быть сериализован в JSON
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'categoryId' => $this->categoryId,
            'name' => $this->name,
            'categoryName' => $this->categoryName,
            'canDeleteThreads' => $this->canDeleteThreads,
            'canDeletePosts' => $this->canDeletePosts,
            'canEditPosts' => $this->canEditPosts,
            'canMoveThreads' => $this->canMoveThreads,
            'canPinThreads' => $this->canPinThreads,
            'canCloseThreads' => $this->canCloseThreads,
            'canApproveThreads' => $this->canApproveThreads,
            'createdAt' => $this->createdAt,
            'createdBy' => $this->createdBy
        ];
    }

}