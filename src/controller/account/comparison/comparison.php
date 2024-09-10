<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 26.08.2022 / 18:14:01
 */

namespace Ofey\Logan22\controller\account\comparison;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\model\user\user;

class comparison
{

    //Синхронизация внутренней БД аккаунтов, реестра аккаунтов АПИ Сферы и реестра аккаунтов игровой базы
    public static function synchronization(): void
    {
        $login = $_POST['login'] ?? board::error("Нет логина");
        $password = $_POST['password'] ?? board::error("Нет пароля");
        $response = server::send(type::SYNCHRONIZATION, [
          'email'=>user::self()->getEmail(),
          'login'=>$login,
          'password'=>$password,
        ])->show(false)->getResponse();
        if($response['success']){
            user::self()->getLoadAccounts(true);
            board::success("Аккаунт добавлен");
        }else{
            //error type
            switch ($response['error']){
                case 1:
                    board::error("Неизвестная ошибка");
                    break;
                case 2:
                    board::error("Аккаунт не найден");
                    break;
                case 3:
                    board::error("Этот аккаунт у Вас уже есть");
                    break;
                case 4:
                    board::error("Этот аккаунт привязан к другому профилю");
                    break;
                case 5:
                    board::error("Случилась неожиданная ошибка");
                    break;
                case 6:
                    board::error("Неверный пароль");
                    break;
                default:
                    board::error("Неизвестная ошибка");
            }
        }
    }

}