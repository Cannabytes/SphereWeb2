<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 06.09.2022 / 22:41:41
 */

namespace Ofey\Logan22\component\alert;

use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\model\user\user;

class board
{

    private static $var = [];

    private static $redirectUrl = null;

    private static ?bool $reload = null;

    public static function success(string $message = null, int $flags = 0, bool $next = false): self
    {
        return self::notice(true, $message, $flags, $next);
    }

    /**
     * Использовать для аякс уведомлений, когда нужно вернуть результат и сообщение
     */
    public static function notice(bool $ok = true, string $message = null, int $flags = 0, bool $next = false): self
    {
        // Проверка на аякс запрос
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $data = [
              'type'    => 'notice',
              'ok'      => $ok,
              'message' => $message,
            ];
            if (user::self()->isAuth()) {
                $data['sphereCoin'] = user::self()->getDonate();
            }
            if (self::$redirectUrl) {
                $data['redirect'] = self::$redirectUrl;
            }
            if (self::$reload) {
                $data['reload'] = self::$reload;
            }
            self::alert($data, $flags);
        }
        if ( ! $next) {
            exit;
        }

        return new self();
    }

    /**
     * В функцию передаем массив данных, которые мы будем возвращать JSON хэдэром
     * используется для аякс ответов.
     */
    public static function alert(array $arr = [], int $flags = 0, bool $next = false): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if ( ! $arr) {
            exit(json_encode(lang::get_phrase(255)));
        }
        if (user::self()->isAuth()) {
            $arr['sphereCoin'] = user::self()->getDonate();
        }
        echo json_encode($arr, $flags);
        if ( ! $next) {
            exit;
        }
    }

    public static function error(string $message = null, int $flags = 0, bool $next = false): self
    {
        return self::notice(false, $message, $flags, $next);
    }

    public static function redirect(string $url): self
    {
        self::$redirectUrl = $url;

        return new self();
    }

    public static function html(string $html, string $title = "")
    {
        header('Content-Type: application/json; charset=utf-8');
        $arr = [
          'content' => $html,
          'title'   => $title,
        ];
        $arr = array_merge(self::$var, $arr);
        echo json_encode($arr, JSON_UNESCAPED_UNICODE);
        exit();
    }

    public static function response($type, $arr = [], bool $next = false): void
    {
        $arr['type'] = $type;
        if (self::$redirectUrl) {
            $arr['redirect'] = self::$redirectUrl;
        }
        echo json_encode($arr, JSON_UNESCAPED_UNICODE);
        if ( ! $next) {
            exit;
        }
    }

    public static function reload()
    {
        self::$reload = true;
    }

}
