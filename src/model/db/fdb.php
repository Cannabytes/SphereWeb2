<?php

namespace Ofey\Logan22\model\db;

use Exception;
use Ofey\Logan22\component\time\time;
use PDO;
use PDOException;

class fdb {

    /**
     * @var PDO
     */
    private static ?PDO $db = null;

    private static bool   $error        = false;
    private static string $messageError = '';

    /**
     * DB constructor.
     *
     * @throws Exception
     */
    public static function connect(string $host = '127.0.0.1', string $port = '3306', string $user = 'root', string $pass = '', string $name = 'forum'): ?PDO {
        if (self::$error) {
            return null;
        }
        if (self::$db !== null) {
            return self::$db;
        }
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$name}";
            self::$db = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                PDO::ATTR_TIMEOUT => 1 // Устанавливаем таймаут в 1 секунду
            ]);
        } catch (PDOException $e) {
            sql::sql("DELETE FROM `server_cache` WHERE `type` = 'forum'");
            sql::sql("INSERT INTO `server_cache` (`server_id`, `type`, `data`, `date_create`) VALUES (?, ?, ?, ?)", [
              0, "forum", json_encode(["connect"=>false, "error"=>$e->getMessage()], JSON_UNESCAPED_UNICODE) , time::mysql()
            ]);
            self::setError(true);
            self::setMessageError("Ошибка соединения с БД - " . $e->getMessage());
            return null;
        }
        return self::$db;
    }


    public static function query($stmt) {
        return self::$db->query($stmt);
    }

    public static function prepare($stmt) {
        return self::$db->prepare($stmt);
    }

    static public function exec($query) {
        return self::$db->exec($query);
    }

    static public function lastInsertId() {
        return self::$db->lastInsertId();
    }

    /**
     * @throws Exception
     */
    public static function run($query, $args = []) {
        if(self::isError()){
            return false;
        }
        try {
            if(!$args) {
                return self::query($query);
            }
            $stmt = self::prepare($query);
            $stmt->execute($args);
            return $stmt;
        } catch(PDOException $e) {
            self::$error = true;
            self::$messageError = "Error: {$e->getMessage()}";
            return false;
        }
        return false;
    }

    public static function interpolateQuery($query, $params) {
        $keys = [];
        $values = $params;

        # build a regular expression for each parameter
        foreach($params as $key => $value) {
            if(is_string($key)) {
                $keys[] = '/:' . $key . '/';
            } else {
                $keys[] = '/[?]/';
            }

            if(is_string($value))
                $values[$key] = "'" . $value . "'";

            if(is_array($value))
                $values[$key] = "'" . implode("','", $value) . "'";

            if(is_null($value))
                $values[$key] = 'NULL';
        }

        $query = preg_replace($keys, $values, $query);

        return $query;
    }

    /**
     * @param       $query
     * @param array $args
     *
     * @return mixed
     */
    public static function getRow($query, $args = []) {
        return self::run($query, $args)->fetch();
    }

    /**
     * @param       $query
     * @param array $args
     *
     * @return array
     */
    public static function getRows($query, array $args = []): array
    {
        $result = self::run($query, $args);
        if ($result !== false) {
            return $result->fetchAll();
        } else {
            return [];
        }
    }

    /**
     * @param       $query
     * @param array $args
     *
     * @return mixed
     */
    public static function getValue($query, $args = []) {
        $result = self::getRow($query, $args);
        if(!empty($result)) {
            $result = array_shift($result);
        }
        return $result;
    }

    /**
     * @param       $query
     * @param array $args
     *
     * @return array
     */
    public static function getColumn($query, $args = []) {
        return self::run($query, $args)->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function sql($query, array $args = []) {
        self::run($query, $args);
    }

    public static function isError(): bool
    {
        return self::$error;
    }

    public static function setError(bool $error): void
    {
        self::$error = $error;
    }

    public static function getMessageError(): string
    {
        return self::$messageError;
    }

    public static function setMessageError(string $messageError): void
    {
        self::$messageError = $messageError;
    }
}