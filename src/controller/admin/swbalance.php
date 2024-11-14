<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\template\tpl;

class swbalance
{

    //Продление лицензии
    static public function renewLicense(): void
    {
        $months = filter_var($_POST['months'], FILTER_VALIDATE_INT);
        $data = server::send(type::RENEW_LICENSE, [
            'months' => (int)$months
        ])->show()->getResponse();
        if(isset($data['success']) AND $data['success']){
            board::reload();
            board::success('Лицензия продлена на '.$months.' месяцев');
        }else{
            board::error('Случилась непредвиденная проблема');
        }
    }

    static public function pay()
    {
        tpl::display("admin/balance_pay.html");
    }

    //Пользователь хочет пополнить баланс
    static public function payInvoice(): void
    {
        $amount = $_POST['amount'] ?? board::error("Укажите сумму");
        // Проверяем, что это число, и приводим его к float
        if (is_numeric($amount)) {
            $amount = (float)$amount;
        } else {
            board::error("Указанная сумма должна быть числом");
        }
        // Проверка, сумма должна быть положительной
        if($amount <= 0){
            board::error("Сумма должна быть положительной");
        }

        $systemPayName = $_POST['systemPayName'] ?? null;
        if ($systemPayName == null){
            board::error("Не определена платежная система");
        }

        $donate = server::send(type::SPHERE_DONATE, [
            'systemPayName' => $systemPayName,
            'amount' => $amount,
        ])->show()->getResponse();
        if(isset($donate['success'])){
            if($donate['success']){
                board::redirect($donate['link']);
                board::success("Переход по ссылке на оплату");
            } else {
                board::error($donate['message']);
            }
        }

    }

    static public function get()
    {
        $sphereAPIError = null;

        $info = server::send(type::SERVER_FULL_INFO)->show(false)->getResponse();
        if(isset($info['error']) OR $info===null){
            $sphereAPIError = true;
            $info['servers'] = [];
        }
        if(!$sphereAPIError){
            tpl::addVar([
              "info" => $info,
              "services" => $info['services'] ?? null,
              "launcher" => $info['launcher'] ?? null,
              "balance" => (float)$info['balance'] ?? 0,
              "servers" => $info['servers'],
              "sphere_last_commit" => $info['last_commit'],
            ]);
        }

        tpl::display("/admin/balance.html");
    }

    public static function historyPay() {
        tpl::display("/admin/balance_history.html");
    }


}