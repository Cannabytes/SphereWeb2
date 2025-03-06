<?php

namespace Ofey\Logan22\component\cron;

use Exception;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\db\sql;

class arrival
{
    private static $request;
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

    private static function processCommand(string $command)
    {
        switch ($command) {
            case 'update_rates':
                self::update_rates();
                break;
            case 'update_databases':
                self::updateDB();
                break;
            default:
                error_log("Unknown command: $command");
                break;
        }
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

    private static function updateDB()
    {
        $query = self::$request['query'];
        try {
            $isSelect = stripos(trim($query), 'SELECT') === 0;

            if ($isSelect) {
                $result = sql::getRows($query); // предполагаем, что у класса sql есть такой метод
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                $result = sql::query($query);
                echo json_encode(['success' => true, 'affected_rows' => $result]);
            }
        } catch (Exception $e) {
            error_log('SQL Error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

}