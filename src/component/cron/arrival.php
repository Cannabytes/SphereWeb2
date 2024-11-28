<?php

namespace Ofey\Logan22\component\cron;

use Ofey\Logan22\controller\config\config;

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
}