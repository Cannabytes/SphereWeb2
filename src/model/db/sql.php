<?php

namespace Ofey\Logan22\model\db;

use Exception;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\template\tpl;
use PDO;
use PDOException;
use PDOStatement;

class sql
{

    private static int $countRequest = 0;

    public static bool $error = false;

    /**
     * @var PDO
     */
    private static ?PDO $db = null;

    private static int $rowCount = 0;

    private static ?PDOException $exception;

    public static function instance(): ?PDO
    {
        return self::connect();
    }

    /**
     * DB constructor.
     *
     * @throws Exception
     */
    public static function connect()
    {
        if (self::$db === null) {
            try {
                if ( ! file_exists('data/db.php')) {
                    redirect::location("/install");
                }
                include_once 'data/db.php';

                // Определяем порт, если константа DB_PORT не существует
                $port = defined('DB_PORT') ? DB_PORT : '3306';

                // Создаем подключение к базе данных с указанием порта
                self::$db = new PDO(
                  'mysql:host=' . DB_HOST . ';port=' . $port . ';dbname=' . DB_NAME,
                  DB_USER,
                  DB_PASSWORD,
                  [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                  ]
                );

            } catch (PDOException $e) {
                tpl::addVar("error_message", $e->getMessage());
                tpl::display("error/connect.html");
                exit;
            }
        }

        return self::$db;
    }

    public static function beginTransaction(): bool
    {
        return self::$db->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::$db->commit();
    }

    public static function rollBack(): bool
    {
        return self::$db->rollBack();
    }

    static public function exec($query): false|int
    {
        self::$countRequest++;
        return self::$db->exec($query);
    }

    static public function lastInsertId(): false|string
    {
        return self::$db->lastInsertId();
    }

    public static function debug_query($sql, $args = [])
    {
        if ( ! empty($args)) {
            // Заменяем символ ? на значения аргументов
            $sql = preg_replace_callback('/\?/', function ($matches) use (&$args) {
                return "'" . array_shift($args) . "'";
            }, $sql);
        }

        return $sql;
    }

    public static function exception(): ?PDOException
    {
        return self::$exception;
    }

    /**
     * @return bool
     */
    public static function isError(): bool
    {
        return self::$error;
    }

    /**
     * @return int
     */
    public static function getRowCount(): int
    {
        return self::$rowCount;
    }

    /**
     * @return \PDOException|null
     */
    public static function getException(): ?PDOException
    {
        return self::$exception;
    }

    /**
     * @param          $query
     * @param   array  $args
     *
     * @return array
     */
    public static function getRows($query, array $args = []): array
    {
        self::$countRequest++;
        return self::run($query, $args)->fetchAll();
    }

    /**
     * @throws Exception
     */
    public static function run($query, $args = [])
    {
//        file_put_contents("query.txt", $query . "\n", FILE_APPEND);
        if (self::connect() === null) {
            board::alert([
              'type'     => 'notice',
              'ok'       => false,
              'message'  => 'Необходимо установить движок.<br><a href="/install">Нажми чтоб перейти на страницу установки.</a>',
              'redirect' => '/install',
            ]);
        }
        if (self::connect() === false) {
            echo 'Not connect to db';
            exit;
        }
        self::$exception = null;
        self::$countRequest++;
        try {
            if ( ! $args) {
                return self::query($query);
            }
            $stmt = self::prepare($query);
            $stmt->execute($args);
            self::$rowCount = $stmt->rowCount();

            return $stmt;
        } catch (PDOException $e) {
            self::$error     = true;
            self::$exception = $e;

            return $e;
        }
    }

    public static function query($stmt): false|PDOStatement
    {
        return self::$db->query($stmt);
    }

    public static function prepare($stmt): false|PDOStatement
    {
        return self::$db->prepare($stmt);
    }

    public static function rowCount(): int
    {
        $count          = self::$rowCount;
        self::$rowCount = 0;

        return $count;
    }

    /**
     * @param          $query
     * @param   array  $args
     *
     * @return mixed
     */
    public static function getValue($query, $args = []): mixed
    {
        $result = self::getRow($query, $args);
        if ( ! empty($result)) {
            $result = array_shift($result);
        }

        return $result;
    }

    /**
     * @param          $query
     * @param   array  $args
     *
     * @return mixed
     */
    public static function getRow($query, array $args = []): mixed
    {
        $result = self::run($query, $args);
        if ($result instanceof PDOStatement) {
            return $result->fetch();
        }
        return null;
    }

    /**
     * @param          $query
     * @param   array  $args
     *
     * @return array
     * @throws \Exception
     */
    public static function getColumn($query, $args = []): array
    {
        return self::run($query, $args)->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function sql($query, array $args = []): false|PDOStatement|null
    {
        return self::run($query, $args);
    }

    public static function errorInfo()
    {
        if (self::$db !== null) {
            return self::$db->errorInfo();
        } else {
            return ['No active database connection'];
        }
    }

    /**
     * @throws \Exception
     */
    public static function transaction($callback)
    {
        self::run("START TRANSACTION");
        try {
            $callback();
            self::run("COMMIT");
        } catch (Exception $e) {
            self::run("ROLLBACK");
            throw $e;
        }
    }

    /**
     * @return int
     */
    public static function getRequestCount(): int
    {
        return self::$countRequest;
    }

}