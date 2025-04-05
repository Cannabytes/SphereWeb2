<?php

namespace Ofey\Logan22\model\donate;

use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\model\user\userModel;

class pay_abstract {

    function sphereCoinSmartCalc(float $count, float $ratio, float $sphereCoinCost): float
    {
        // Если sphereCoinCost ≥ 1, используем деление, иначе — умножение
        if ($sphereCoinCost >= 1.0) {
            return $count * ($ratio / $sphereCoinCost);
        } else {
            return $count * ($ratio * $sphereCoinCost);
        }
    }

    /**
     * Используется для определения конфигурации платежной системы
     *
     * @param   string  $methodName
     *
     * @return string|int
     * @throws \Exception
     */
    public static function getConfigValue(string $methodName): string|int
    {
        return \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate()->get(get_called_class())->getInput($methodName);
    }

    public static function getDescription(): ?array {
        return static::$description ?? null;
    }

    public static function isEnable(): bool{
        return static::$enable;
    }

    public static function forAdmin(): bool{
        return static::$forAdmin;
    }

    public static function getWebhook()
    {
        return static::$webhook ?? null;
    }

    /**
     * @param userModel|null $user - объект пользователя
     * @param $invoice_amount - сумма платежа
     * @param $currency - валюта
     * @param $amount - кол-во внутренних валют
     * @param $paySystem - название платежной системы
     * @return void
     */
    public static function telegramNotice(null|userModel $user, $invoice_amount, $currency, $amount, $paySystem): void
    {
        if(!config::load()->notice()->isTelegramEnable()) {
            return;
        }
        if (config::load()->notice()->isDonationCrediting()) {
            if($user == null){
                $user = user::self();
                $user->setEmail("NoEmail");
                $user->setName("NoName");
            }

            $template = lang::get_other_phrase(config::load()->notice()->getNoticeLang(), 'notice_user_donate');
            $msg = strtr($template, [
                '{name}' => $user->getName(),
                '{email}' => $user->getEmail(),
                '{invoice_amount}' => $invoice_amount,
                '{currency}' => $currency,
                '{amount}' => $amount,
                '{paySystem}' => $paySystem,
            ]);
            telegram::sendTelegramMessage($msg, config::load()->notice()->getDonationCreditingThreadId());
        }
    }

}