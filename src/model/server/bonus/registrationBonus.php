<?php

namespace Ofey\Logan22\model\server\bonus;

class registrationBonus
{
    public int $id = 0;
    public int $count = 1;
    public int $enchant = 0;

    public function __construct(
        int $id,
        int $count,
        int $enchant
    ) {
        $this->id = $id;
        $this->count = $count;
        $this->enchant = $enchant;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): void
    {
        $this->count = $count;
    }

    public function getEnchant(): int
    {
        return $this->enchant;
    }

    public function setEnchant(int $enchant): void
    {
        $this->enchant = $enchant;
    }


}