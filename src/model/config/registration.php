<?php

namespace Ofey\Logan22\model\config;

use Ofey\Logan22\model\db\sql;

class registration
{

    private bool $enablePrefix = false;

    private string $prefixType = 'prefix';

    private bool $massRegistration = true;

    private bool $enableLoadFileRegistration = true;

    private string $phraseRegistrationDownloadFile = 'text_registration_account';

    public function __construct()
    {
        $configData = sql::getRow("SELECT * FROM `settings` WHERE `key` = '__config_registration__'");
        if($configData){
            $setting            = json_decode($configData['setting'], true);
            $this->enablePrefix = filter_var( $setting['enablePrefix'], FILTER_VALIDATE_BOOLEAN );
            $this->prefixType = $setting['prefixType'] ?? 'prefix';
            $this->massRegistration = filter_var($setting['massRegistration'],FILTER_VALIDATE_BOOLEAN );
            $this->enableLoadFileRegistration = filter_var($setting['enableLoadFileRegistration'],FILTER_VALIDATE_BOOLEAN );
            $this->phraseRegistrationDownloadFile = $setting['phraseRegistrationDownloadFile'] ?? 'text_registration_account';
        }
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
        $prefix = '';
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $rand_sizi = mt_rand(1, 3);
        for ($i = 0; $i < $rand_sizi; $i++) {
            $prefix .= $chars[rand(0, strlen($chars) - 1)];
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