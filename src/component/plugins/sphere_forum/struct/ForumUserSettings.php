<?php

namespace Ofey\Logan22\component\plugins\sphere_forum\struct;

use Ofey\Logan22\model\user\user;

class ForumUserSettings {
    private const SETTINGS_KEY = 'forum_user_settings';


    private const DEFAULT_SETTINGS = [
        'showCharacters' => true,
        'showPvPPK' => true,
        'showGameTime' => true,
        'showFlagCountry' => true
    ];

    private ?array $settings = null;
    private int $userId;

    public function __construct(int $userId) {
        $this->userId = $userId;
        $this->loadSettings();
    }




    private function loadSettings(): void {
        $user = user::getUserId($this->userId);
        $savedSettings = $user->getVar(self::SETTINGS_KEY);

        if ($savedSettings) {
            $this->settings = json_decode($savedSettings['val'], true);
        } else {
            $this->settings = self::DEFAULT_SETTINGS;
            $this->saveSettings();
        }
    }




    private function saveSettings(): void {
        $user = user::getUserId($this->userId);
        $user->addVar(self::SETTINGS_KEY, json_encode($this->settings));
    }




    public function updateSetting(string $key, bool $value): void {
        if (!array_key_exists($key, self::DEFAULT_SETTINGS)) {
            throw new \InvalidArgumentException("Неизвестная настройка: $key");
        }

        $this->settings[$key] = $value;
        $this->saveSettings();
    }




    public function getSetting(string $key): bool {
        return $this->settings[$key] ?? self::DEFAULT_SETTINGS[$key];
    }




    public function getAllSettings(): array {
        return $this->settings;
    }
}