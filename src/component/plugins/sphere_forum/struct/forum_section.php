<?php

namespace Ofey\Logan22\component\plugins\sphere_forum\struct;

use JsonSerializable;

class forum_section implements JsonSerializable
{

    private int $categoryId;
    private int $id = -1;
    private string $name = "None";
    private int $posts = 0;
    private int $views = 0;
    private int $lastPostId = 0;
    private int $lastPostUserId = 0;
    private int $lastPostTime = 0;
    private int $authorId = 0;
    private int $authorPostId = 0;
    private int $authorPostTime = 0;
    private string $createdAt;
    private string $updatedAt;

    public function __construct(array $topic = [])
    {
        $this->id = $topic['id'] ?? -1;
        $this->name = $topic['name'] ?? "None";
        $this->posts = $topic['posts'] ?? 0;
        $this->views = $topic['views'] ?? 0;
        $this->lastPostId = $topic['last_post_id'] ?? 0;
        $this->authorId = $topic['user_id'] ?? 0;
        $this->authorPostId = $topic['author_post_id'] ?? 0;
        $this->createdAt = $topic['created_at'] ?? "";
        $this->updatedAt = $topic['updated_at'] ?? "";
    }


    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'posts' => $this->posts,
            'views' => $this->views,
            'lastPostId' => $this->lastPostId,
            'authorId' => $this->authorId,
            'authorPostId' => $this->authorPostId,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function getPosts(): int
    {
        return $this->posts;
    }

    public function setPosts(int $posts): void
    {
        $this->posts = $posts;
    }

    public function getViews(): int
    {
        return $this->views;
    }

    public function getLastPostId(): int
    {
        return $this->lastPostId;
    }

    public function setLastPostId(int $lastPostId): void
    {
        $this->lastPostId = $lastPostId;
    }

    public function getLastPostUserId(): int
    {
        return $this->lastPostUserId;
    }

    public function setLastPostUserId(int $lastPostUserId): void
    {
        $this->lastPostUserId = $lastPostUserId;
    }

    public function getLastPostTime(): int
    {
        return $this->lastPostTime;
    }

    public function setLastPostTime(int $lastPostTime): void
    {
        $this->lastPostTime = $lastPostTime;
    }

    public function getAuthorId(): int
    {
        return $this->authorId;
    }

    public function setAuthorId(int $authorId): void
    {
        $this->authorId = $authorId;
    }

    public function getAuthorPostId(): int
    {
        return $this->authorPostId;
    }

    public function setAuthorPostId(int $authorPostId): void
    {
        $this->authorPostId = $authorPostId;
    }

    public function getAuthorPostTime(): int
    {
        return $this->authorPostTime;
    }

    public function setAuthorPostTime(int $authorPostTime): void
    {
        $this->authorPostTime = $authorPostTime;
    }

}