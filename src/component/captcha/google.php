<?php

namespace Ofey\Logan22\component\captcha;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\controller\config\config;

class google
{

    public static function check($token = null)
    {
        if ($token === null) {
            board::notice(false, "Нет токена Google капчи");
        }

        $url    = 'https://www.google.com/recaptcha/api/siteverify';
        $params = [
          'secret'   => config::load()->captcha()->getGoogleServerKey(),
          'response' => $token,
          'remoteip' => $_SERVER['REMOTE_ADDR'],
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $response_data = json_decode($response, true);
        if (!$response_data['success']) {
            board::alert([
              'ok' => false,
              'captcha' => 'error',
            ]);
        }

        return $response_data;
    }


    public static function get_client_key(): string
    {
        return config::load()->captcha()->getGoogleClientKey();
    }

}
