<?php

namespace Ofey\Logan22\model\donate;

use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\image\client_icon;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\item\item;

class shop
{
    private string $id;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): shop
    {
        $this->id = $id;
        return $this;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): shop
    {
        $this->count = $count;
        return $this;
    }

    public function getCost(): int
    {
        return $this->cost;
    }

    public function setCost(int|float $cost): shop
    {
        $this->cost = $cost;
        return $this;
    }

    public function getServerId(): int
    {
        return $this->serverId;
    }

    public function setServerId(int $serverId): shop
    {
        $this->serverId = $serverId;
        return $this;
    }

    public function getIsPack(): ?int
    {
        return $this->isPack;
    }

    public function setIsPack(?int $isPack): shop
    {
        $this->isPack = $isPack;
        return $this;
    }

    private int $itemId;
    private int $count;
    private int|float $cost;
    private int $serverId;

    private int $enchant;

    private ?int $isPack;
    private ?string $packName;

    public function getPackName(): ?string
    {
        return $this->packName;
    }

    public function setPackName(?string $packName): void
    {
        $this->packName = $packName;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function setItemId(int $itemId): void
    {
        $this->itemId = $itemId;
    }

    private ?item $item;
    public function setItemInfo(int $itemId): void
    {
       $this->item = item::getItem($itemId);
    }

    public function getItemInfo(): item
    {
        return $this->item;
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
     * @return shop
     */
    public function setEnchant(int $enchant): shop
    {
        $this->enchant = $enchant;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'itemId' => $this->getItemId(),
            'count' => $this->getCount(),
            'cost' => $this->getCost(),
            'serverId' => $this->getServerId(),
            'enchant' => $this->getEnchant(),
            'item' => $this->getItemInfo()->toArray(),
        ];
    }


}