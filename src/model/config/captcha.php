<?php

namespace Ofey\Logan22\model\config;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\captcha\google;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\model\db\sql;
use SimpleCaptcha\Builder;

class captcha
{
    private bool $defaultCaptcha = false;
    private bool $googleCaptcha = false;
    private string $googleClientKey;
    private string $googleServerKey;


    public function __construct()
    {
        $configData = sql::getRow("SELECT * FROM `settings` WHERE `key` = '__config_captcha__'");
        $setting = json_decode($configData['setting'], true);
        $this->defaultCaptcha = filter_var($setting['defaultCaptcha'], FILTER_VALIDATE_BOOLEAN);
        $this->googleCaptcha = filter_var($setting['googleCaptcha'], FILTER_VALIDATE_BOOLEAN);
        $this->googleClientKey = $setting['googleClientKey'];
        $this->googleServerKey = $setting['googleServerKey'];
    }

    /**
     * Возвращает название капчи, которая будет использоваться.
     * @return string
     */
    public function getCaptcha(): string
    {
        if($this->defaultCaptcha){
            return "default";
        }
        if($this->googleCaptcha){
            if(empty($this->googleClientKey) or empty($this->googleServerKey)){
                return "default";
            }
            return "google";
        }
        return "default";
    }

    public function isDefaultCaptcha(): bool
    {
        return $this->defaultCaptcha;
    }

    public function isGoogleCaptcha(): bool
    {
        return $this->googleCaptcha;
    }

    public function getGoogleClientKey(): string
    {
        return $this->googleClientKey;
    }

    public function getGoogleServerKey(): string
    {
        return $this->googleServerKey;
    }

    public function validator(): void
    {
        if ($this->getCaptcha() == "google") {
            $g_captcha = google::check($_POST['captcha'] ?? null);
            if (isset($g_captcha['success'])) {
                if (!$g_captcha['success']) {
                    board::notice(false, $g_captcha['error-codes'][0]);
                }
            } else {
                board::notice(false, "Google recaptcha не вернула ответ");
            }
        } elseif ($this->getCaptcha() =="default" ) {
            $builder = new Builder();
            $captcha = $_POST['captcha'] ?? false;
            if (!$builder->compare(trim($captcha), $_SESSION['captcha'])) {
                board::response("notice", ["message" => lang::get_phrase(295), "ok"=>false, "reloadCaptcha" => true]);
            }
        }
    }

}