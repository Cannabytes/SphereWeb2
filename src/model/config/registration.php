<?php

namespace Ofey\Logan22\model\config;

use Ofey\Logan22\model\db\sql;

class registration
{

    private bool $enablePrefix = false;

    private string $prefixType = 'prefix';

    private bool $massRegistration = true;

    private bool $enableLoadFileRegistration = true;

    private string $phraseRegistrationDownloadFile = '';

    public function __construct()
    {
        $configData = sql::getRow("SELECT * FROM `settings` WHERE `key` = '__config_registration__'");
        $setting            = json_decode($configData['setting'], true);
        $this->enablePrefix = filter_var( $setting['enablePrefix'], FILTER_VALIDATE_BOOLEAN );
        $this->prefixType = $setting['prefixType'] ?? 'prefix';
        $this->massRegistration = filter_var($setting['massRegistration'],FILTER_VALIDATE_BOOLEAN );
        $this->enableLoadFileRegistration = filter_var($setting['enableLoadFileRegistration'],FILTER_VALIDATE_BOOLEAN );
        $this->phraseRegistrationDownloadFile = $setting['phraseRegistrationDownloadFile'];
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