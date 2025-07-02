<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 02.01.2023 / 3:56:52
 */

namespace Ofey\Logan22\component\request;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\controller\config\config;

/**
 * Класс для обработки входящих POST запросов
 */
class request
{

    static request_config $config;
    public static array $settingValue = [];

    /**
     * @param $value
     * @param $config
     *
     * @return mixed
     *
     * Добавляет настройки
     * Ключ -> конфиг (селф класс)
     */
    public static function setting($key, request_config $config = new request_config()): mixed
    {
        self::$config = $config;
        foreach ($_POST as $name => &$value) {
            if ($name != $key)
                continue;
            self::required($name, $value);
            self::min($name, $value);
            self::max($name, $value);
            self::minValue($name, $value);
            self::maxValue($name, $value);
            self::rules($name, $value);
            self::isEmail($name, $value);
            self::isURL($name, $value);
            return $value;
        }
        board::notice(false, "Не найдено значение реквеста : " . $key);
    }

    public static function checkbox($key): bool
    {
        if (isset($_POST[$key])) {
            return true;
        }
        return false;
    }

    /**
     * @param $key
     * @param array $array
     *
     */
    public static function compare($key, array $array = [])
    {
        if (in_array($_POST[$key], $array)) {
            return $_POST[$key];
        }
        board::notice(false, "Вы не выбрали допустимое значение в поле $key");
    }



    /**
     * Принимает название настройки (импута) и возращает его настройки
     *
     * @return request_config
     */
    public static function request(): request_config
    {
        return self::$config;
    }

    private static function isEmail($name, $value): void
    {
        if (!self::request()->isEmail())
            return;
        if (self::request()->isURL())
            return;
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            board::notice(false, lang::get_phrase(291));
        }
    }

    private static function isURL($name, $value): void
    {
        if (!self::request()->isURL())
            return;
        if (self::request()->isEmail())
            return;
        if (self::request()->isNumber())
            return;
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            board::notice(false, "Адрес ссылки указан неверно");
        }
    }

    private static function maxValue($name, $value): void
    {
        if (self::request()->isEmail())
            return;
        if (!self::request()->isNumber())
            return;
        if (self::request()->isURL())
            return;
        if (is_numeric($value)) {
            if (self::request()->getMaxValue() < $value) {
                board::notice(false, lang::get_phrase(287, $name, self::request()->getMaxValue()));
            }
            $value = settype($value, 'int');
        } else {
            board::notice(false, lang::get_phrase(292, $name));
        }
    }

    private static function minValue($name, $value): void
    {
        if (self::request()->isEmail())
            return;
        if (!self::request()->isNumber())
            return;
        if (self::request()->isURL())
            return;
        if (is_numeric($value)) {
            if (self::request()->getMinValue() > $value) {
                board::notice(false, lang::get_phrase(293, self::request()->getMinValue(), $name));
            }
            $value = settype($value, 'int');
        } else {
            board::notice(false, lang::get_phrase(292, $name));
        }
    }

    private static function max($name, $value): void
    {
        if (self::request()->isEmail())
            return;
        if (self::request()->isNumber())
            return;
        if (self::request()->isURL())
            return;
        if (self::request()->getMax() < mb_strlen($value)) {
            board::notice(false, lang::get_phrase(286, $name, self::request()->getMax()));
        }
    }

    private static function min($name, $value): void
    {
        if (self::request()->isEmail())
            return;
        if (self::request()->isNumber())
            return;
        if (self::request()->isURL())
            return;
        if (self::request()->getMin() > mb_strlen($value)) {
            board::response("notice", ["message" => lang::get_phrase(290, $name, self::request()->getMin()), "ok" => false, "reloadCaptcha" => config::load()->captcha()->isGoogleCaptcha() == false]);
        }
    }

    private static function required($name, $value)
    {
        if (self::request()->isRequired()) {
            if (empty($value)) {
                board::notice(false, lang::get_phrase(288, $name));
            }
        }
    }

    private static function rules($name, $value): void
    {
        if (self::request()->isEmail())
            return;
        if (self::request()->isNumber())
            return;
        if (self::request()->isURL())
            return;
        if (self::request()->getRules() != "") {
            if (!preg_match(self::request()->getRules(), $value)) {
                board::notice(false, lang::get_phrase(289, $name, self::request()->getRules()));
            }
        }
    }

    public static function validateString(string $key, string $errorMessage): string
    {
        $value = filter_input(INPUT_POST, $key, FILTER_UNSAFE_RAW);
        if ($value === null || $value === false || trim($value) === '') {
            board::error($errorMessage);
        }
        return html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function validateInt(string $key, string $errorMessage): int
    {
        $value = filter_input(INPUT_POST, $key, FILTER_VALIDATE_INT);

        if ($value === false || $value === null) {
            board::error($errorMessage);
        }

        return $value;
    }

    public static function validateFloat(string $key, string $errorMessage): float
    {
        $value = filter_input(INPUT_POST, $key, FILTER_VALIDATE_FLOAT);

        if ($value === false || $value === null) {
            board::error($errorMessage);
        }

        return $value;
    }

    public static function validateEmail(string $key, string $errorMessage): string
    {
        $value = filter_input(INPUT_POST, $key, FILTER_VALIDATE_EMAIL);

        if ($value === false || $value === null) {
            board::error($errorMessage);
        }

        return $value;
    }

    public static function validateUrl(string $key, string $errorMessage): string
    {
        $value = filter_input(INPUT_POST, $key, FILTER_VALIDATE_URL);

        if ($value === false || $value === null) {
            board::error($errorMessage);
        }

        return $value;
    }

    public static function validateBool(string $key, string $errorMessage): bool
    {
        $value = filter_input(INPUT_POST, $key, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        if ($value === null) {
            board::error($errorMessage);
        }

        return $value;
    }

    public static function validateArray(string $key, string $errorMessage): array
    {
        $value = filter_input(INPUT_POST, $key, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);

        if ($value === false || $value === null || !is_array($value)) {
            board::error($errorMessage);
        }

        return $value;
    }



}
