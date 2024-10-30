<?php

namespace Ofey\Logan22\model\lang;

readonly class langStruct
{

    public function __construct(
        private string $lang = "",
        private string $name = "",
        private bool   $isActive = false
    ) {}
    public function getLang(): string{
        return $this->lang;
    }
    public function getName(): string{
        return $this->name;
    }
    public function getIsActive(): bool{
        return $this->isActive;
    }
    public function IsActive(): bool{
        return $this->isActive;
    }
}