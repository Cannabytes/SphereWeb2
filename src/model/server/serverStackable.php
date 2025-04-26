<?php

namespace Ofey\Logan22\model\server;


class serverStackable
{

    private bool $allowAllItemsStacking = false;
    private bool $allowAllItemsSplitting = false;
    private array $stackableItems = [];
    private array $splittableItems = [];

    public function __construct($stackableItems = null)
    {
        if ($stackableItems !== null) {
            $this->allowAllItemsStacking = $stackableItems['allowAllItemsStacking'] ?? false;
            $this->allowAllItemsSplitting = $stackableItems['allowAllItemsSplitting'] ?? false;
            $this->stackableItems = $stackableItems['stackableItems'] ?? [];
            $this->splittableItems = $stackableItems['splittableItems'] ?? [];
        }
    }

    public function set($allowAllItemsStacking = false, $allowAllItemsSplitting = false, $stackableItems = [], $splittableItems = [])
    {
        $this->allowAllItemsStacking = $allowAllItemsStacking;
        $this->allowAllItemsSplitting = $allowAllItemsSplitting;
        $this->stackableItems = $stackableItems;
        $this->splittableItems = $splittableItems;
    }

    public function isAllowAllItemsStacking(): bool
    {
        return $this->allowAllItemsStacking;
    }

    public function setAllowAllItemsStacking(bool $allowAllItemsStacking): void
    {
        $this->allowAllItemsStacking = $allowAllItemsStacking;
    }

    public function isAllowAllItemsSplitting(): bool
    {
        return $this->allowAllItemsSplitting;
    }

    public function setAllowAllItemsSplitting(bool $allowAllItemsSplitting): void
    {
        $this->allowAllItemsSplitting = $allowAllItemsSplitting;
    }

    public function getStackableItems(): array
    {
        return $this->stackableItems;
    }

    public function setStackableItems(array $stackableItems): void
    {
        $this->stackableItems = $stackableItems;
    }

    public function getSplittableItems(): array
    {
        return $this->splittableItems;
    }

    public function setSplittableItems(array $splittableItems): void
    {
        $this->splittableItems = $splittableItems;
    }

    public function toArray(): array
    {
        return [
            'allowAllItemsStacking' => $this->allowAllItemsStacking,
            'allowAllItemsSplitting' => $this->allowAllItemsSplitting,
            'stackableItems' => $this->stackableItems,
            'splittableItems' => $this->splittableItems,
        ];
    }

}