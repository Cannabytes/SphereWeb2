<?php

namespace Ofey\Logan22\controller\logo;

use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\db\sql;

class logo
{

    /**
     * Загрузка логотипа
     */
    public static function logo()
    {

        if (isset($_FILES['filepond']) && $_FILES['filepond']['error'] == 0) {
            $manager =   ImageManager::gd();
            $file    = $_FILES['filepond']['tmp_name'];
            $image   = $manager->read($file);
            // Получаем текущие размеры изображения
            //Ширина изображения
            $width = $image->width();
            //Высота изображения
            $height = $image->height();
            //            var_dump($width);
            //            var_dump( $height, $height > 280);exit();
            $proportion = null;

            if ($height > 65) {
                $proportion = $height / 65;
                $image->resize(null, $height / $proportion);
            }

            if ($width > 270) {
                if ($proportion != null) {
                    $proportion = $width / 270;
                }
                if ($width / $proportion > 270) {
                    $image->resize(270, null);
                } else {
                    $image->resize($width / $proportion, null);
                }
            }

            $logo = 'uploads/logo/logo.webp';
            //Проверка существования папки
            if ( ! file_exists('uploads/logo')) {
               $mkdir = mkdir('uploads/logo', 0777, true);
            }
            $success = $image->save($logo);
            if ($success) {
                $logo = $logo  . "?v=" . uniqid();
                // Найти
                $favicon = null;
                $setting = sql::getRow("SELECT `id`, `setting` FROM `settings` WHERE `key` = '__config_logo__'");
                if ($setting) {
                    $data    = $setting['setting'];
                    $data    = json_decode($data, true);
                    $favicon = $data['favicon'] ?? null;
                    sql::run("UPDATE `settings` SET `setting` = ?, `dateUpdate` = ? WHERE `key` = '__config_logo__'", [
                      json_encode([
                        'favicon' => $favicon,
                        'logo'    => "/" . $logo,
                      ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                      time::mysql(),
                    ]);
                } else {
                    $json = json_encode([
                      'favicon' => $favicon,
                      'logo'    => "/" . $logo,
                    ], JSON_UNESCAPED_UNICODE);
                    sql::run(
                      "INSERT INTO `settings` (`key`, `setting`, `serverId`, `dateUpdate`) VALUES ('__config_logo__', ?, 0, ?)",
                      [$json, time::mysql()]
                    );
                }

                echo json_encode([
                  'status'  => 'success',
                  'message' => 'Image uploaded and processed successfully',
                  'path'    => "/" . $logo  ,
                ]);
                exit;
            }
        }

        echo json_encode([
          'status'  => 'error',
          'message' => 'Failed to upload image',
        ]);
    }

    public static function favicon()
    {
        if (isset($_FILES['filepond']) && $_FILES['filepond']['error'] == 0) {
            $manager =   ImageManager::gd();
            $file    = $_FILES['filepond']['tmp_name'];
            $image   = $manager->read($file);

            $allowedMimeTypes = ['image/x-icon', 'image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
            $fileMimeType     = mime_content_type($_FILES['filepond']['tmp_name']);

            // Проверка и просим пользователя загрузить изображение в формате ico, png, jpg, jpeg, webp
            if ( ! in_array($fileMimeType, $allowedMimeTypes)) {
                echo json_encode([
                  'status'  => 'error',
                  'message' => 'Файл должен быть в формате ico, png, jpg, jpeg, webp',
                ]);
                exit;
            }
            // Проверяем размер изображения и просим пользователя загрузить изображение не более 256x256
            if ($image->width() < 16 || $image->height() < 16) {
                echo json_encode([
                  'status'  => 'error',
                  'message' => 'Файл должен быть не более 16x16',
                ]);
                exit;
            }

            // Проверка, что изображение квадратное
            if ($image->width() != $image->height()) {
                echo json_encode([
                  'status'  => 'error',
                  'message' => 'The image must be square (width equal to height).',
                ]);

                return;
            }

            // Проверка на допустимые размеры
            $allowedSizes = [16, 32, 48, 64, 128, 256, 512, 1024];
            $originalSize = $image->width();

            if ( ! in_array($originalSize, $allowedSizes)) {
                echo json_encode([
                  'status'  => 'error',
                  'message' => 'The image size must be one of the following: ' . implode(', ', $allowedSizes) . ' pixels.',
                ]);

                return;
            }

            $allowedSizes = array_reverse($allowedSizes);

            //Проверка существования папки
            if ( ! file_exists('uploads/logo')) {
                mkdir('uploads/logo', 0777, true);
            }
            // Сохраняем изображение в различных размерах
            $arrNamesFavicon = [];
            foreach ($allowedSizes as $size) {
                    $savePath = "uploads/logo/favicon_{$size}.png";
                    $image->resize($size, $size)->save($savePath, 94);
                    $arrNamesFavicon[$size] = "/" . $savePath;
            }


            // Найти
            $logo    = null;
            $setting = sql::getRow("SELECT `id`, `setting` FROM `settings` WHERE `key` = '__config_logo__'");
            if ($setting) {
                $data = $setting['setting'];
                $data = json_decode($data, true);
                $logo = $data['logo']   . "?v=" . uniqid() ?? '';
                sql::run("UPDATE `settings` SET `setting` = ?, `dateUpdate` = ? WHERE `key` = '__config_logo__'", [
                  json_encode([
                    'favicon' => $arrNamesFavicon,
                    'logo'    => $logo,
                  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                  time::mysql(),
                ]);
            } else {
                $json = json_encode([
                  'favicon' => $arrNamesFavicon,
                  'logo'    => $logo,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                sql::run(
                  "INSERT INTO `settings` (`key`, `setting`, `serverId`, `dateUpdate`) VALUES ('__config_logo__', ?, 0, ?)",
                  [$json, time::mysql()]
                );
            }

            echo json_encode([
              'status'  => 'success',
              'message' => 'Image uploaded and processed successfully',
              'path'    => "/" . $logo  . "?v=" . uniqid(),
            ]);
            exit;
        }

        echo json_encode([
          'status'  => 'error',
          'message' => 'Failed to upload image',
        ]);
    }

}