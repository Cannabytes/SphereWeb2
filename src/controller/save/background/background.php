<?php

namespace Ofey\Logan22\controller\save\background;

use Intervention\Image\ImageManager;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\db\sql;

class background
{

    // Загрузка изображений
    public static function save()
    {
        if (isset($_FILES['filepond']) && $_FILES['filepond']['error'] == 0) {
            $manager        = ImageManager::gd();
            $file           = $_FILES['filepond']['tmp_name'];
            $image          = $manager->read($file);
            $type           = $_POST['type'] ?? board::error("Нет типа изображения");
            $background_img = "uploads/background/{$type}.webp";
            //Проверка существования папки
            if ( ! file_exists('uploads/background')) {
                $mkdir = mkdir('uploads/background', 0777, true);
            }
            $success = $image->save($background_img);
            if ($success) {
                $background_img = $background_img . "?v=" . uniqid();
                // Найти
                $setting = sql::getRow("SELECT `id`, `setting` FROM `settings` WHERE `key` = '__config_background__'");
                if ($setting) {
                    $data = $setting['setting'];
                    $data = json_decode($data, true);

                    $login        = $data['login'];
                    $registration = $data['registration'];
                    $forget       = $data['forget'];

                    $data = self::getTypeBackground($type, $background_img, $login, $registration, $forget);

                    sql::run("UPDATE `settings` SET `setting` = ?, `dateUpdate` = ? WHERE `key` = '__config_background__'", [
                      json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                      time::mysql(),
                    ]);
                } else {
                    $data = self::getTypeBackground($type, $background_img);

                    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
                    sql::run(
                      "INSERT INTO `settings` (`key`, `setting`, `serverId`, `dateUpdate`) VALUES ('__config_background__', ?, 0, ?)",
                      [$json, time::mysql()]
                    );
                }

                echo json_encode([
                  'status'  => 'success',
                  'message' => 'Image uploaded and processed successfully',
                  'path'    => "/" . $background_img,
                ]);
                exit;
            }
        }

        echo json_encode([
          'status'  => 'error',
          'message' => 'Failed to upload image',
        ]);
    }

    private static function getTypeBackground($type, $background_img, $login = "", $registration = "", $forget = ""): array
    {
        switch ($type) {
            case "login":
                $data['login'] = $background_img;
                $data['registration'] = $registration;
                $data['forget'] = $forget;
                break;
            case "registration":
                $data['login'] = $login;
                $data['registration'] = $background_img;
                $data['forget'] = $forget;
                break;
            case "forget":
                $data['login'] = $login;
                $data['registration'] = $registration;
                $data['forget'] = $background_img;
                break;
        }
        return $data;
    }

}