<?php

namespace Ofey\Logan22\component\cron;

use Exception;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\db\sql;
use PDO;
use PDOException;

class arrival
{
    private static array $request = [];

    //Когда приходит уведомление от сферы АПИ с обновлением каких либо данных
    public static function receiving(): void
    {
        $input = file_get_contents('php://input');
        if(!$input){
            return;
        }
        self::$request = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return;
        }
        require_once "data/token.php";
        if (!isset(self::$request['token']) || !hash_equals(__TOKEN__, self::$request['token'])) {
            return;
        }
        if (isset(self::$request['command'])) {
            self::processCommand(self::$request['command']);
        }
    }

    private static function processCommand(string $command): void
    {
        match ($command) {
            'update_rates' => self::update_rates(),
            'update_databases' => self::updateDB(),
            'clear_errors' => self::clearErrors(),
            'referrals_bonus_check' => self::referrals_bonus_check(),
            default => error_log("Unknown command: $command"),
        };
    }

    //Обновления курса валют
    private static function update_rates()
    {
        if(!config::load()->other()->isExchangeRates()){
            return;
        }
        $rates = self::$request['rates'];
        $other = config::load()->other();
        $other->setRates($rates);
        $other->save();
    }

    public static function executeSqlScript($sql): false|int
    {
        try {
            $pdo = sql::instance();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::MYSQL_ATTR_MULTI_STATEMENTS, true);
            $success = $pdo->exec($sql);
            return $success;
        } catch (PDOException $e) {
            throw new Exception('SQL Error: ' . $e->getMessage());
        }
    }

    private static function updateDB()
    {
        $query = self::$request['query'];
        try {
            $isSelect = stripos(trim($query), 'SELECT') === 0;
            if ($isSelect) {
                $result = sql::getRows($query);
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                $result = self::executeSqlScript($query);
                echo json_encode(['success' => true, 'affected_rows' => $result]);
            }
        } catch (Exception $e) {
            error_log('SQL Error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private static function clearErrors(): void
    {
        $errorFiles = [
            'errors.txt',
            'errors.log',
            'sql_error_log.txt',
        ];

        foreach ($errorFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    private static function referrals_bonus_check()
    {

    }

}