<?php

namespace Ofey\Logan22\model\item;

class warehouse
{
    private int $id, $serverId, $userId, $itemId, $count, $enchant;
    private item $item;
    private string|int $phrase;

    /**
     * @return item
     */
    public function getItem(): item
    {
        return $this->item;
    }

    /**
     * @param item $item
     * @return warehouse
     */
    public function setItem(item $item): warehouse
    {
        $this->item = $item;
        return $this;
    }
    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return warehouse
     */
    public function setId(int $id): warehouse
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getServerId(): int
    {
        return $this->serverId;
    }

    /**
     * @param int $serverId
     * @return warehouse
     */
    public function setServerId(int $serverId): warehouse
    {
        $this->serverId = $serverId;
        return $this;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return warehouse
     */
    public function setUserId(int $userId): warehouse
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return int
     */
    public function getItemId(): int
    {
        return $this->itemId;
    }

    /**
     * @param int $itemId
     * @return warehouse
     */
    public function setItemId(int $itemId): warehouse
    {
        $this->itemId = $itemId;
        return $this;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @param int $count
     * @return warehouse
     */
    public function setCount(int $count): warehouse
    {
        $this->count = $count;
        return $this;
    }

    /**
     * @return int
     */
    public function getEnchant(): int
    {
        return $this->enchant;
    }

    /**
     * @param int $enchant
     * @return warehouse
     */
    public function setEnchant(int $enchant): warehouse
    {
        $this->enchant = $enchant;
        return $this;
    }

    /**
     * @return int
     */
    public function getPhrase(): string|int
    {
        return $this->phrase;
    }

    /**
     * @param string|int $phrase
     * @return warehouse
     */
    public function setPhrase(string|int $phrase): warehouse
    {
        $this->phrase = $phrase;
        return $this;
    }
}