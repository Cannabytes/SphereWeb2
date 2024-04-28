<?php

namespace Ofey\Logan22\model\db;

use Exception;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\controller\page\error;
use Ofey\Logan22\template\tpl;
use PDO;
use PDOException;

class sdb {

    public static string $host, $user, $pass, $name;
    public static string $port = "3306";
    /**
     * @var null
     */
    protected static $instance = null;
    /**
     * @var PDO
     */
    private static ?PDO $db = null;
    private static bool $showErrorPage = false;
    private static int $server_id = 0;
    private static string $type;
    private static bool $error = false;
    private static ?string $errorMessage = null;

    public static function get_error() {
        return self::$db[self::get_server_id()][self::get_type()]->errorInfo();
    }

    public static function is_error(): ?string {
        return self::$error ? self::$errorMessage : false;
    }

    public static function get_server_id(): int {
        return self::$server_id;
    }

    public static function set_server_id(int $server_id) {
        self::$server_id = $server_id;
    }

    public static function get_type(): string {
        return self::$type;
    }

    public static function set_type($type = 'login') {
        self::$type = $type;
    }

    public static function set_connect($host, $user, $pass, $name, $port = "3306") {
        self::$host = $host;
        self::$user = $user;
        self::$pass = $pass ?? "";
        self::$name = $name;
        self::$port = $port;
        return self::connect();
    }

    /**
     * DB constructor.
     *
     * @throws Exception
     */
    public static function connect() {
        if (self::$db === null) {
            try {
                $dsn = 'mysql:host=' . self::$host . ';dbname=' . self::$name . ';port=' . self::$port;
                self::$db = new PDO($dsn, self::$user, self::$pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                    PDO::ATTR_TIMEOUT => 1,
                ]);
            } catch (PDOException $e) {
                self::$error = true;
                self::$errorMessage = $e->getMessage();
                return false;
            }
        }
        return self::$db;
    }


    public static function lastInsertId() {
        return self::$db[self::get_server_id()][self::get_type()]->lastInsertId();
    }

    public static function errorMessage() {
        return self::$errorMessage;
    }

    public static function setShowErrorPage(bool $b): void {
        self::$showErrorPage = $b;
    }

    public static function isShowErrorPage(): bool {
        return self::$showErrorPage;
    }

    //Сообщение ошибки

    /**
     * @param       $query
     * @param array $args
     *
     * @return array
     */
    public static function getRows($query, $args = []) {
        return self::run($query, $args)->fetchAll();
    }


    // В случаи ошибки возвращает null и записывает ошибку в $errorMessage
    public static function run($query, $args = [], $notice = true) {
        self::$error = false;
        self::$errorMessage = null;
        if (!self::connect()) {
            self::$error = true;
            self::$errorMessage = "Not connect to DB";
            return null;
        }
        try {
            $stmt = self::prepare($query);
            if ($args) {
                $stmt->execute($args);
            } else {
                $stmt->execute();
            }
            return $stmt;
        } catch (PDOException $e) {
            if(!$notice){
                self::$error = true;
                self::$errorMessage = $e->getMessage();
                return false;
            }
            if (self::isShowErrorPage()) {
                tpl::addVar("title", "Ошибка");
                error::show($e);
            }
            board::notice(false, "Error: " . $e->getMessage());
        }
    }


    public static function prepare($query) {
        self::connect();
        $query = preg_replace('/\s+/', ' ', trim($query));
        return self::$db->prepare($query);
    }


    /**
     * @param       $query
     * @param array $args
     *
     * @return mixed
     */
    public static function getValue($query, $args = []) {
        $result = self::getRow($query, $args);
        if (!empty($result)) {
            $result = array_shift($result);
        }
        return $result;
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
    public static function getColumn($query, $args = []) {
        return self::run($query, $args)->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function sql($query, array $args = []) {
        self::run($query, $args);
    }


}