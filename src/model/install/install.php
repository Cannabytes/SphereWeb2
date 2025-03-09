<?php

namespace Ofey\Logan22\model\install;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\config\config;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\lang\lang;
use PDO;
use PDOException;

class install
{

    //Мы должны получить host, userModel, pass, name
    //Так же проверка на существования файла подключения к БД, если файл существует, тогда запрет
    public static function saveConfig($host, $port, $user, $password, $name): void
    {
        // Создаем текст для конфигурационного файла
        $phpText = "<?php
const DB_HOST = '{$host}';
const DB_USER = '{$user}';
const DB_PASSWORD = '{$password}';
const DB_NAME = '{$name}';
const DB_PORT = '{$port}';
const CHARSET = 'utf8';
";

        // Указываем путь к файлу конфигурации
        $filePath = "data/db.php";

        // Пытаемся записать данные в файл
        $result = file_put_contents($filePath, $phpText);

        // Проверяем успешность записи
        if ($result === false) {
            board::error("Failed to save configuration to {$filePath}");
            exit();
        }

        // Проверяем, что размер записанного файла соответствует ожидаемому
        if (filesize($filePath) !== strlen($phpText)) {
            board::error("Failed to save configuration to {$filePath}");
            exit();
        }
    }

    public static function exist_admin()
    {
        if ( ! file_exists(fileSys::get_dir('/data/db.php'))) {
            return false;
        }
        $sql  = 'SELECT * FROM users WHERE access_level = "admin"';
        $conn = self::test_connect_mysql(DB_HOST, 3306, DB_USER, DB_PASSWORD, DB_NAME);

        return $conn->query($sql)->fetch();
    }

    public static function test_connect_mysql($host, $port, $user, $password, $name = ""): bool|PDO
    {
        if ($name === "") {
            board::notice(false, "Нет имени базы данных");
        }
        try {
            // Создаем DSN с учетом порта
            $dsn  = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
            $conn = new PDO($dsn, $user, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $conn;
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function add_first_news(): void
    {
        $txt  = lang::get_phrase(158);
        $conn = self::test_connect_mysql(DB_HOST, 3306, DB_USER, DB_PASSWORD, DB_NAME);
        $smt  = $conn->prepare('INSERT INTO `pages` (`is_news`, `name`, `description`) VALUES (1, ?, ?);');
        $smt->execute([
          lang::get_phrase(159),
          $txt,
        ]);
    }

}