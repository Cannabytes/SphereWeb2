<?php

namespace Ofey\Logan22\model\donate;

class donateItemBonus
{
    private $item;
    private $quantity;
    private $enchant;

    public function __construct($item, $quantity, $enchant) {
        $this->item = $item;
        $this->quantity = $quantity;
        $this->enchant = $enchant;
    }

    public function getItem() {
        return $this->item;
    }

    public function getQuantity() {
        return $this->quantity;
    }

    public function getEnchant() {
        return $this->enchant;
    }

}