<?php

namespace Ofey\Logan22\controller\oauth2\telegram;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\links\http;
use Ofey\Logan22\component\plugins\registration_reward\registration_reward;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\session\session;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\component\time\timezone;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\controller\config\config;

class telegram
{
    /**
     * Генерация случайного токена от 5 до 8 символов
     * @return string
     */
    private static function generateToken(): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $charactersLength = strlen($characters);
        $length = rand(5, 8); // Случайная длина от 5 до 8 символов
        $token = '';
        
        for ($i = 0; $i < $length; $i++) {
            $token .= $characters[rand(0, $charactersLength - 1)];
        }
        
        return $token;
    }

    /**
     * Проверка и создание таблицы telegram_auth_sessions при необходимости
     * @return void
     */
    private static function ensureTableExists(): void
    {
        sql::run("
        CREATE TABLE IF NOT EXISTS `telegram_auth_sessions` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `token` varchar(8) NOT NULL,
            `telegram_id` bigint(20) NOT NULL,
            `telegram_username` varchar(255) DEFAULT NULL,
            `first_name` varchar(255) NOT NULL,
            `last_name` varchar(255) DEFAULT NULL,
            `language_code` varchar(10) DEFAULT NULL,
            `expires_at` timestamp NOT NULL,
            `created_at` timestamp NULL DEFAULT NULL,
            `updated_at` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `telegram_auth_sessions_token_unique` (`token`),
            KEY `telegram_auth_sessions_token_index` (`token`),
            KEY `telegram_auth_sessions_telegram_id_index` (`telegram_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }

    /**
     * Обработка авторизации по токену из URL
     * @param string $token Токен из URL
     */
    public static function auth($token)
    {
        if (!config::load()->other()->isOAuth()){
            die('oauth disabled');
        }
        // Проверяем и создаем таблицу при необходимости
        self::ensureTableExists();

        if (empty($token)) {
            board::notice(false, "Токен не указан");
        }

        // Удаляем все истекшие токены (старше 120 секунд)
        sql::run("DELETE FROM `telegram_auth_sessions` WHERE TIMESTAMPDIFF(SECOND, `created_at`, NOW()) > 120");

        // Проверяем существование токена в базе данных
        $sessionData = sql::getRow(
            "SELECT * FROM `telegram_auth_sessions` WHERE `token` = ? AND `expires_at` > NOW() LIMIT 1",
            [$token]
        );

        if (!$sessionData) {
            board::notice(false, "Токен не найден или истек срок действия");
        }

        // Проверяем, что токен не истек (120 секунд)
        $createdAt = strtotime($sessionData['created_at']);
        $currentTime = time();
        $timeDiff = $currentTime - $createdAt;

        if ($timeDiff > 120) {
            // Удаляем истекшую сессию
            sql::run("DELETE FROM `telegram_auth_sessions` WHERE `token` = ?", [$token]);
            board::notice(false, "Время действия токена истекло");
        }

        $telegramId = $sessionData['telegram_id'];
        $telegramUsername = $sessionData['telegram_username'];
        $firstName = $sessionData['first_name'];
        $lastName = $sessionData['last_name'];
        $languageCode = $sessionData['language_code'];

        if ($telegramUsername == null) {
            $telegramUsername = "{$telegramId}";
        }

        // Используем telegram_username как email, если он есть, иначе используем telegram_id
        $email = "TG:@{$telegramUsername}";
        
        // Проверяем существование пользователя по email
        $existingUser = user::getUserByEmail($email);

        if ($existingUser) {
            // Пользователь уже существует - авторизуем его
            \Ofey\Logan22\model\user\auth\auth::addAuthLog($existingUser->getId(), "TELEGRAM");
            session::add('id', $existingUser->getId());
            session::add('email', $existingUser->getEmail());
            session::add('password', "TELEGRAM");
            session::add("oauth2", true);
            
            // Удаляем использованную сессию
            sql::run("DELETE FROM `telegram_auth_sessions` WHERE `token` = ?", [$token]);
            
            redirect::location("/main");
            return;
        }

        
        $get_timezone_ip = timezone::get_timezone_ip($_SERVER['REMOTE_ADDR']);
        
        // Формируем имя пользователя
        $userName = $firstName ?: ($firstName ?: "user-" . substr(md5(uniqid()), mt_rand(2, 3), mt_rand(4, 5)));
        
        // Сократить имя пользователя
        $userName = mb_substr($userName, 0, 22);


        if ($get_timezone_ip != null) {
            $insertUserSQL = "INSERT INTO `users` (`email`, `password`, `name`, `ip`, `timezone`, `country`, `city`, `last_activity`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $insertArrays = [
                $email,
                "TELEGRAM",
                $userName,
                $_SERVER['REMOTE_ADDR'],
                $get_timezone_ip['timezone'],
                $get_timezone_ip['country'],
                $get_timezone_ip['city'],
                time::mysql(),
            ];
        } else {
            $insertUserSQL = "INSERT INTO `users` (`email`, `password`, `name`, `ip`, `last_activity`) VALUES (?, ?, ?, ?, ?)";
            $insertArrays = [
                $email,
                "TELEGRAM",
                $userName,
                $_SERVER['REMOTE_ADDR'],
                time::mysql(),
            ];
        }

        $insert = sql::run($insertUserSQL, $insertArrays);
        $userID = sql::lastInsertId();

        if ($insert) {
            \Ofey\Logan22\model\user\auth\auth::addAuthLog($userID, "TELEGRAM");
            session::add('id', $userID);
            session::add('email', $email);
            session::add('password', "TELEGRAM");
            session::add("oauth2", true);

            $user = user::getUserId($userID);

            // Выдаем бонусы при регистрации
            foreach (server::getServerAll() as $server) {
                if ($server->bonus()->isRegistrationBonus()) {
                    $items = $server->bonus()->getRegistrationBonusItems();
                    $ifIssueAllItems = $server->bonus()->isIssueAllItems();
                    // Если выдаем все предметы
                    if ($ifIssueAllItems) {
                        foreach ($items as $item) {
                            $user->addToWarehouse($server->getId(), $item->getId(), $item->getCount(), $item->getEnchant(), 'registration_bonus');
                        }
                    } else {
                        // выбираем рандомный предмет
                        $item = $items[array_rand($items)];
                        $user->addToWarehouse($server->getId(), $item->getId(), $item->getCount(), $item->getEnchant(), 'registration_bonus');
                    }
                }
            }

            // Выдаем подарки через плагин "Вознаграждение за регистрацию"
            registration_reward::giveRegistrationReward($user);

            // Удаляем использованную сессию
            sql::run("DELETE FROM `telegram_auth_sessions` WHERE `token` = ?", [$token]);

            redirect::location("/main");
        } else {
            board::notice(false, lang::get_phrase(178));
        }
    }

    /**
     * Создание токена авторизации для Telegram
     * Принимает POST запрос с данными пользователя Telegram
     * Поддерживает как application/x-www-form-urlencoded, так и application/json
     */
    public static function authenticateByToken(array $inputData)
    {
        if (!config::load()->other()->isOAuth()){
            die('oauth disabled');
        }
        // Проверяем и создаем таблицу при необходимости
        self::ensureTableExists();

        // Получаем данные из запроса
        $telegramId = $inputData['telegram_id'] ?? null;
        $telegramUsername = $inputData['telegram_username'] ?? null;
        $firstName = $inputData['first_name'] ?? '';
        $lastName = $inputData['last_name'] ?? null;
        $languageCode = $inputData['language_code'] ?? null;

        // Валидация обязательных полей
        if (empty($telegramId)) {
            header('Content-Type: application/json');
            echo json_encode([
                'ok' => false,
                'message' => 'telegram_id not found'
            ]);
            exit;
        }

        // Генерируем случайный токен от 5 до 8 символов
        $token = self::generateToken();

        // Проверяем уникальность токена
        $existingToken = sql::getRow(
            "SELECT `token` FROM `telegram_auth_sessions` WHERE `token` = ? LIMIT 1",
            [$token]
        );

        // Если токен уже существует, генерируем новый (максимум 100 попыток для избежания бесконечного цикла)
        $attempts = 0;
        while ($existingToken && $attempts < 100) {
            $token = self::generateToken();
            $existingToken = sql::getRow(
                "SELECT `token` FROM `telegram_auth_sessions` WHERE `token` = ? LIMIT 1",
                [$token]
            );
            $attempts++;
        }

        // Если не удалось сгенерировать уникальный токен за 100 попыток
        if ($existingToken) {
            header('Content-Type: application/json');
            echo json_encode([
                'ok' => false,
                'message' => 'Не удалось сгенерировать уникальный токен'
            ]);
            exit;
        }

        // Вычисляем время истечения (120 секунд от текущего момента)
        $expiresAt = date('Y-m-d H:i:s', time() + 120);
        $createdAt = time::mysql();

        // Сохраняем данные в таблицу telegram_auth_sessions
        $insertSQL = "INSERT INTO `telegram_auth_sessions` 
            (`token`, `telegram_id`, `telegram_username`, `first_name`, `last_name`, `language_code`, `expires_at`, `created_at`, `updated_at`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $insert = sql::run($insertSQL, [
            $token,
            $telegramId,
            $telegramUsername,
            $firstName,
            $lastName,
            $languageCode,
            $expiresAt,
            $createdAt,
            $createdAt
        ]);

        if ($insert) {
            // Используем telegram_username как email, если он есть, иначе используем telegram_id
            $email = $telegramUsername ?: "telegram_{$telegramId}@telegram.local";
            $existingUser = user::getUserByEmail($email);
            $userId = $existingUser ? $existingUser->getId() : $telegramId;

            // Возвращаем токен в ответе
            header('Content-Type: application/json');
            echo json_encode([
                'ok' => true,
                'token' => $token,
                'user_id' => $userId
            ]);
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'ok' => false,
                'message' => 'Ошибка при создании токена'
            ]);
            exit;
        }
    }
}