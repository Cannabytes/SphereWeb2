<?php

namespace Ofey\Logan22\model\server;

use Ofey\Logan22\model\server\bonus\registrationBonus;

class serverBonus
{
    public function __construct($bonus = null){
        if ($bonus == null) {
            return;
        }

        if(isset($bonus['registration_bonus'])) {
            $this->setRegistrationBonusItems($bonus['registration_bonus']['enabled'], $bonus['registration_bonus']['isIssueAllItems'], $bonus['registration_bonus']['items']);
        }
    }

    private bool $registrationBonusEnabled = false;
    private bool $issueAllItems = false;
    /** @var registrationBonus[] */
    private array $registrationBonusItems = [];

    public function setRegistrationBonusItems($enable, $issueAllItems, $items): void
    {
        $this->registrationBonusEnabled = filter_var($enable, FILTER_VALIDATE_BOOL);
        $this->issueAllItems = filter_var($issueAllItems, FILTER_VALIDATE_BOOL);
        $this->registrationBonusItems = []; // Сбрасываем массив перед заполнением
        foreach ($items as $item) {
            $itemId = filter_var($item['item_id'], FILTER_VALIDATE_INT);
            $count = filter_var($item['count'], FILTER_VALIDATE_INT);
            $enchant = filter_var($item['enchant'], FILTER_VALIDATE_INT);
            $this->registrationBonusItems[] = new registrationBonus($itemId, $count, $enchant);
        }
    }

    public function isRegistrationBonus(): bool
    {
        return $this->registrationBonusEnabled;
    }

    public function isIssueAllItems(): bool
    {
        return $this->issueAllItems;
    }

    public function getRegistrationBonusItems(): array
    {
        return $this->registrationBonusItems;
    }

    public function getRegistrationBonusToArray(): array
    {

        $items = [];
        foreach ($this->registrationBonusItems as $item) {
            $items[] = [
                'item_id' => $item->getId(),
                'count' => $item->getCount(),
                'enchant' => $item->getEnchant()
            ];
        }

        return [
            'enabled' => $this->registrationBonusEnabled,
            'isIssueAllItems' => $this->issueAllItems,
            'items' => $items
        ];
    }

    public function toArray(): array
    {
        return [
            'registration_bonus' => $this->getRegistrationBonusToArray()
        ];
    }


}