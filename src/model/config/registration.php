<?php

namespace Ofey\Logan22\model\config;

class registration
{

    private bool $enablePrefix = false;

    private string $prefixType = 'prefix';

    private bool $massRegistration = true;

    private bool $enableLoadFileRegistration = true;

    private string $phraseRegistrationDownloadFile = 'text_registration_account';

    private int $maximumNumberOfCharactersRegistrationAccount = 16;

    private int $minimumNumberOfCharactersRegistrationAccount = 2;

    private string $prefixCharacters = '1-3';


    public function __construct($setting)
    {
        $this->enablePrefix = filter_var($setting['enablePrefix'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->prefixType = $setting['prefixType'] ?? 'prefix';
        $this->massRegistration = filter_var($setting['massRegistration'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->enableLoadFileRegistration = filter_var($setting['enableLoadFileRegistration'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $this->phraseRegistrationDownloadFile = !empty($setting['phraseRegistrationDownloadFile']) ? $setting['phraseRegistrationDownloadFile'] : 'text_registration_account';
        $this->minimumNumberOfCharactersRegistrationAccount = filter_var($setting['minimumNumberOfCharactersRegistrationAccount'] ?? 2, FILTER_VALIDATE_INT);
        $this->maximumNumberOfCharactersRegistrationAccount = filter_var($setting['maximumNumberOfCharactersRegistrationAccount'] ?? 16, FILTER_VALIDATE_INT);
        $this->prefixCharacters = $setting['prefixCharacters'] ?? '1-3';;
    }

    public function getMaximumNumberOfCharactersRegistrationAccount(): int
    {
        return $this->maximumNumberOfCharactersRegistrationAccount;
    }

    public function getMinimumNumberOfCharactersRegistrationAccount(): int
    {
        return $this->minimumNumberOfCharactersRegistrationAccount;
    }

    public function getPrefixCharacters(): string
    {
        return $this->prefixCharacters;
    }

    /**
     * Get the value of enablePrefix.
     *
     * @return bool
     */
    public function getEnablePrefix(): bool
    {
        return $this->enablePrefix;
    }

    public function genPrefix(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $prefix = '';
        [$min, $max] = match ($this->prefixCharacters) {
            '1-2' => [1, 2],
            '2-2' => [2, 2],
            '2-3' => [2, 3],
            '3-3' => [3, 3],
            '1'   => [1, 1],
            '2'   => [2, 2],
            default => [1, 2],
        };
        $randSize = random_int($min, $max);
        $maxIndex = strlen($chars) - 1;
        for ($i = 0; $i < $randSize; $i++) {
            $prefix .= $chars[random_int(0, $maxIndex)];
        }
        $_SESSION['account_prefix'] = $prefix;
        return $prefix;
    }


    /**
     * Get the value of prefixType.
     *
     * @return string
     */
    public function getPrefixType(): string
    {
        return $this->prefixType;
    }

    /**
     * Get the value of massRegistration.
     *
     * @return bool
     */
    public function isMassRegistration(): bool
    {
        return $this->massRegistration;
    }

    /**
     * Get the value of enableLoadFileRegistration.
     *
     * @return bool
     */
    public function getEnableLoadFileRegistration(): bool
    {
        return $this->enableLoadFileRegistration;
    }

    /**
     * Get the value of phraseRegistrationDownloadFile.
     *
     * @return string
     */
    public function getPhraseRegistrationDownloadFile(): string
    {
        return $this->phraseRegistrationDownloadFile;
    }
}
