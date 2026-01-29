<?php

namespace Ofey\Logan22\model\item;

use AllowDynamicProperties;
use DateTime;
use JsonSerializable;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\image\client_icon;
use Ofey\Logan22\model\user\user;

#[AllowDynamicProperties] class item implements JsonSerializable {

    private int $id = 0;

    private int $enchant = 0;

    private bool $exists = false;

    private string|DateTime|null $date = null;

    public function isExists(): bool
    {
        return $this->exists;
    }

    public function setExists(bool $exists): void
    {
        $this->exists = $exists;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): item
    {
        $this->id = $id;
        return $this;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function setItemId(int $itemId): item
    {
        $this->itemId = $itemId;
        return $this;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): item
    {
        $this->count = $count;
        return $this;
    }

    public function getCost(): int
    {
        return $this->cost;
    }

    public function setCost(int $cost): item
    {
        $this->cost = $cost;
        return $this;
    }

    private int $itemId = 0;
    private int $count = 0;
    private int $cost = 0;

    private ?string $icon;
    private ?string $itemName = "NoItemName";
    private ?string $add_name = "";
    private ?string $description = "";

    public function getAddName(): ?string
    {
        return $this->add_name ?? "";
    }

    public function setAddName(?string $add_name): item
    {
        $this->add_name = $add_name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description ?? "";
    }

    public function setDescription(?string $description): item
    {
        $this->description = $description;
        return $this;
    }

    public function getPrice(): int
    {
        return $this->price ?? 0;
    }

    public function setPrice(bool $price): item
    {
        $this->price = $price;
        return $this;
    }

    public function getIsTradable(): bool
    {
        return $this->is_tradable ?? false;
    }

    public function setIsTradable(bool $is_tradable): item
    {
        $this->is_tradable = $is_tradable;
        return $this;
    }

    public function getIsDropable(): ?string
    {
        return $this->is_dropable;
    }

    public function setIsDropable(bool $is_dropable): item
    {
        $this->is_dropable = $is_dropable;
        return $this;
    }

    public function getIsSellable(): ?string
    {
        return $this->is_sellable;
    }

    public function setIsSellable(bool $is_sellable): item
    {
        $this->is_sellable = $is_sellable;
        return $this;
    }

    public function getIsDepositable(): ?string
    {
        return $this->is_depositable;
    }

    public function setIsDepositable(bool $is_depositable): item
    {
        $this->is_depositable = $is_depositable;
        return $this;
    }

    public function getIsStackable(): ?string
    {
        return $this->is_stackable;
    }

    public function setIsStackable(bool $is_stackable): item
    {
        $this->is_stackable = $is_stackable;
        return $this;
    }

    private ?int $price = 0;
    private bool $is_tradable = false;
    private bool $is_dropable = false;
    private ?string $type = null;
    private bool $is_sellable  = false;
    private bool $is_depositable  = false;
    private bool $is_stackable = true;
    private ?string $crystal_type = null;
    private ?string $bodypart = null;
    private ?string $etcitem_type = null;
    private ?int $crystal_count = null;
    private ?int $soulshots = null;
    private ?int $spiritshots = null;
    private ?int $enchant_enabled = null;
    private ?array $stats = null;

    public function getSoulshots(): ?int
    {
        return $this->soulshots;
    }

    public function setSoulshots(?int $soulshots): void
    {
        $this->soulshots = $soulshots;
    }

    public function getSpiritshots(): ?int
    {
        return $this->spiritshots;
    }

    public function setSpiritshots(?int $spiritshots): void
    {
        $this->spiritshots = $spiritshots;
    }

    public function getEnchantEnabled(): ?int
    {
        return $this->enchant_enabled;
    }

    public function setEnchantEnabled(?int $enchant_enabled): void
    {
        $this->enchant_enabled = $enchant_enabled;
    }

    public function getStats(): ?array
    {
        return $this->stats;
    }

    public function setStats(?array $stats): void
    {
        $this->stats = $stats;
    }

    public function getCrystalType(): ?string
    {
        return $this->crystal_type;
    }

    public function setCrystalType(?string $crystal_type): void
    {
        $this->crystal_type = $crystal_type;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): void
    {
        $this->icon = self::icon($icon);
    }

    public function getItemName(): ?string
    {
        return $this->itemName;
    }

    public function setItemName(?string $itemName): void
    {
        $this->itemName = $itemName;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getBodyPart(): ?string {
        return $this->bodypart;
    }

    public function setBodyPart(?string $bodypart): void {
        $this->bodypart = $bodypart;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function setEtcitemType(?string $etcitemType): void {
        $this->etcitem_type = $etcitemType;
    }

    public function getEtcitemType(): ?string {
        return $this->etcitem_type;
    }

    public function getCrystalCount(): ?int
    {
        return $this->crystal_count;
    }

    public function setCrystalCount(?int $crystal_count): void
    {
        $this->crystal_count = $crystal_count;
    }

    public static function icon($fileIcon = null, $object = "icon")
    {
        // Проверяем, задан ли файл и имеет ли он расширение 'webp'
        if ($fileIcon !== null && pathinfo($fileIcon, PATHINFO_EXTENSION) === 'webp') {
            // Извлекаем имя файла без расширения, если это формат 'webp'
            $fileIcon = pathinfo($fileIcon, PATHINFO_FILENAME);
        }

        // Формируем путь к иконке (без начального слеша)
        $iconPath = "uploads/images/{$object}/" . $fileIcon . ".webp";

        // Проверяем, существует ли файл и задано ли имя файла
        if ($fileIcon !== null && file_exists($iconPath)) {
            return "/" . $iconPath; // Добавляем начальный слеш при возврате URL
        }

        // Возвращаем путь к изображению по умолчанию
        return "/uploads/images/icon/NOIMAGE.webp";
    }



    private static array $arrItems = [];

    public static function getItem($id, $dbVersion = null): ?item
    {
        if (isset(self::$arrItems[$id])) {
            return self::$arrItems[$id];
        }
        $file = client_icon::includeFileByRange($id, dbVersion: $dbVersion);
        if (!$file) {
            $itemObject = new item();
            $itemObject->setItemId($id);
            $itemObject->setType("etcitem");
            $itemObject->setItemName("NoItemName[id:$id]");
            $itemObject->setIcon("etc_l2_i00.webp");
            $itemObject->setExists(false);
            self::$arrItems[$id] = $itemObject;
            return $itemObject;
        }
        $itemArr = require $file;
        if (isset($itemArr[$id])) {
            $item = $itemArr[$id];
            $itemObject = new item();
            $itemObject->setItemId($id);
            $itemObject->setType($item['type']);
            $itemObject->setItemName($item['name']);
            $itemObject->setAddName($item['add_name']);
            $itemObject->setDescription($item['description']);
            $itemObject->setBodyPart($item['bodypart'] ?? null);
            $itemObject->setCrystalType($item['crystal_type'] ?? null);
            $itemObject->setIsDropable($item['is_dropable'] ?? false);
            $itemObject->setIsSellable($item['is_sellable'] ?? false);
            $itemObject->setIsTradable($item['is_tradable'] ?? false);
            $itemObject->setIsStackable($item['is_stackable'] ?? false);
            $itemObject->setIsDepositable($item['is_depositable'] ?? false);
            $itemObject->setPrice($item['price'] ?? 0);
            $itemObject->setExists(true);
            $itemObject->setEtcitemType($item['etcitem_type'] ?? null);
            $itemObject->setCrystalCount($item['crystal_count'] ?? null);
            $itemObject->setSoulshots($item['soulshots'] ?? null);
            $itemObject->setSpiritshots($item['spiritshots'] ?? null);
            $itemObject->setEnchantEnabled($item['enchant_enabled'] ?? null);
            $itemObject->setStats($item['stats'] ?? null);

            if(file_exists("uploads/images/icon/{$id}.webp")){
                $itemObject->setIcon("{$id}.webp");
            }else{
                $itemObject->setIcon($item['icon']??null);
            }

            self::$arrItems[$id] = $itemObject;
            return $itemObject;
        }

        return null;

    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'itemId' => $this->itemId,
            'count' => $this->count,
            'cost' => $this->cost,
            'icon' => $this->icon,
            'crystal_type' => $this->crystal_type,
            'itemName' => $this->itemName,
            'addName' => $this->add_name,
            'description' => $this->description,
            'bodyPart' => $this->bodypart,
            'price' => $this->price,
            'isTradable' => $this->is_tradable,
            'isDropable' => $this->is_dropable,
            'type' => $this->type,
            'isSellable' => $this->is_sellable,
            'isDepositable' => $this->is_depositable,
            'isStackable' => $this->is_stackable,
            'isExists' => $this->exists,
            'etcitemType' => $this->etcitem_type,
            'crystalCount' => $this->crystal_count,
            'soulshots' => $this->soulshots,
            'spiritshots' => $this->spiritshots,
            'enchantEnabled' => $this->enchant_enabled,
            'stats' => $this->stats,
        ];
    }

    public function setEnchant(int $enchant): void
    {
        $this->enchant = $enchant;
    }

    public function getEnchant(): int
    {
        return $this->enchant;
    }

    public function setDate(DateTime|string $date): void
    {
        $this->date = $date;
    }

    public function getDate()
    {
        if($this->date instanceof DateTime) {
            return $this->date->format('Y-m-d H:i:s');
        }
        return $this->date;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

}