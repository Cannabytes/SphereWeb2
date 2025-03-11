<?php

namespace Ofey\Logan22\controller\oauth2\google;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\session\session;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\component\time\timezone;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;

class auth
{

    public static function callback()
    {
        $tokenData = $_GET;

        $unique_id = $tokenData['unique_id'];
        $host = $tokenData['host'];
        $getterUrl = "{$host}/google/getter.php";
        $postData = [
            'unique_id' => $unique_id
        ];
        $ch = curl_init($getterUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            echo ('Ошибка при получении токена: HTTP код ' . $httpCode);exit;
        }

        $tokenData = json_decode($response, true);

        if (!isset($tokenData['success']) || !$tokenData['success']) {
           echo 'Ошибка при получении токена';exit;
        }

        if (isset($tokenData['access_token'])) {
            $accessToken = $tokenData['access_token'];
            $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
            $ch = curl_init($userInfoUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken
            ]);
            $userInfoResponse = curl_exec($ch);
            curl_close($ch);
            $userInfo = json_decode($userInfoResponse, true);
            $id = $userInfo['id'];
            $email = $userInfo['email'];
            $name = $userInfo['name'];
            $picture = $userInfo['picture'];

            $emailData = user::getUserByEmail($email);
            if ($emailData != null) {
                session::add('id', $emailData->getId());
                session::add('email', $emailData->getEmail());
                session::add('password', "GOOGLE");
                redirect::location("/main");
                return;
            }
            if (user::getUserByName($name)) {
                $name = "user-" . substr(md5(uniqid()), mt_rand(2, 3), mt_rand(4, 5));
            }

            $get_timezone_ip = timezone::get_timezone_ip($_SERVER['REMOTE_ADDR']);
            if ($get_timezone_ip != null) {
                $insertUserSQL = "INSERT INTO `users` (`email`, `password`, `name`, `ip`, `timezone`, `country`, `city`, `last_activity`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $insertArrays = [
                    $email,
                    "GOOGLE",
                    $name,
                    $_SERVER['REMOTE_ADDR'],
                    $get_timezone_ip['timezone'],
                    $get_timezone_ip['country'],
                    $get_timezone_ip['city'],
                    time::mysql(),
                ];
            }
            $insert = sql::run($insertUserSQL, $insertArrays);
            $userID = sql::lastInsertId();
            if ($insert) {
                session::add('id', $userID);
                session::add('email', $email);
                session::add('password', "GOOGLE");
                redirect::location("/main");
            } else {
                board::notice(false, lang::get_phrase(178));
            }
        } else {
            echo 'Не удалось получить access_token. Проверьте настройки.';
        }

    }

}