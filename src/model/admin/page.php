<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 13.11.2022 / 9:29:45
 */

namespace Ofey\Logan22\model\admin;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\db\sql;
use Verot\Upload\Upload;

class page
{
    public static function create(): void
    {
        // Получение и фильтрация данных запроса
        $title          = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
        $content        = filter_input(INPUT_POST, 'content', FILTER_SANITIZE_STRING);
        $is_news        = $_POST['type'] == 'news' ? 1 : 0;
        $enable_comment = 0;
        $lang           = $_POST['lang'];
        $link           = $_POST['link'] ?? null;

        // Проверка данных
        self::check_data($title, $content);
        $poster = "";

        // Создание директории для изображений, если её нет
        $imageDir = "uploads/images/news";
        if (!is_dir($imageDir)) {
            mkdir($imageDir, 0777, true);
        }

        // Обработка и загрузка изображения
        if (isset($_FILES['file']['name']) && is_array($_FILES['file']) && count($_FILES) === 1) {
            $poster = self::processImage($_FILES['file']);
        }

        // Запись в базу
        $request = sql::run(
          'INSERT INTO `pages` (`is_news`, `name`, `description`, `comment`, `date_create`, `lang`, `poster`, `link`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
          [
            $is_news,
            $title,
            $content,
            $enable_comment,
            time::mysql(),
            $lang,
            $poster,
            $link,
          ]
        );

        // Проверка результата вставки на ошибку исключения возвращает
        if (sql::isError()) {
            board::notice(false, "ERROR: " . $request->getMessage());
        }

        // Проверка результата вставки
        if ($request) {
            board::alert([
              'type'     => 'notice',
              'ok'       => true,
              'message'  => "Добавлено",
              'redirect' => "/admin/pages",
            ], 0);
        }

        board::notice(false, 'Произошла ошибка');
    }

    private static function processImage($file)
    {
        $handle = new Upload($file['tmp_name']);
        if ($handle->uploaded) {
            $handle->allowed       = ['image/*'];
            $handle->mime_check    = true;
            $handle->file_max_size = 5 * 1024 * 1024; // Разрешенная максимальная загрузка 4mb

            $filename = md5(mt_rand(1, 100000) + time());

            $handle->file_new_name_body = $filename;
            $handle->image_resize       = true;
            $handle->image_x            = 450;
            $handle->image_ratio_y      = true;
            $handle->file_name_body_pre = 'thumb_';
            $handle->image_convert      = 'webp';
            $handle->webp_quality       = 95;
            $handle->process('uploads/images/news');
            if (!$handle->processed) {
                board::notice(false, $handle->error);
            }

            $handle->file_new_name_body = $filename;
            $handle->image_resize       = true;
            $handle->image_x            = 1200;
            $handle->image_ratio_y      = true;
            $handle->image_convert      = 'webp';
            $handle->webp_quality       = 95;
            $handle->process('uploads/images/news');
            if ($handle->processed) {
                $handle->clean();
                return $filename . ".webp";
            }
            if ($handle->error) {
                $fileName = $file['name'];
                $msg      = lang::get_phrase(455) . " '" . $fileName . "'\n" . lang::get_phrase(456) . " : " . $handle->error;
                board::notice(false, $msg);
            }
        } else {
            board::notice(false, $handle->error);
        }
        return "";
    }

    // Обновление данных

    public static function update()
    {
        $title          = trim($_POST['title']);
        $content        = trim($_POST['content']);
        $is_news        = $_POST['type'] == 'news' ? 1 : 0;
        $enable_comment = 0;
        $id             = $_POST['id'];
        $lang           = $_POST['lang'];

        // Проверка данных
        self::check_data($title, $content);

        // Обработка и загрузка изображения
        $poster = "";
        if (isset($_FILES['file']['name']) && is_array($_FILES['file']) && count($_FILES) === 1) {
            $poster = self::processImage($_FILES['file']);
        }

        // Запись в базу
        $request = sql::run('UPDATE `pages` SET `is_news` = ?, `name` = ?, `description` = ?, `comment` = ?, `lang` = ?  WHERE `id` = ?', [
          $is_news,
          $title,
          $content,
          $enable_comment,
          $lang,
          $id,
        ]);

        // Проверка результата вставки на ошибку исключения возвращает
        if (sql::isError()) {
            board::notice(false, "ERROR: " . $request->getMessage());
        }

        // Проверка результата вставки
        if ($request) {
            board::alert([
              'type'     => 'notice',
              'text'     => lang::get_phrase(144),
              'ok'       => true,
              'redirect' => fileSys::localdir("/page/" . $id),
            ]);
        }
        board::notice(false, 'Произошла ошибка');
    }

    private static function check_data($title, $content)
    {
        //Предельные символы
        $mix_title_len   = 4;
        $max_title_len   = 140; // Максимум 600 символов. Рекомендую оставить 140.
        $min_content_len = 20;
        $max_content_len = pow(2, 24) - 1; //До 16 мб текста...

        //Проверка данных
        if ( ! validation::min_len($title, $mix_title_len)) {
            board::notice(false, lang::get_phrase(140, $mix_title_len));
        }
        if ( ! validation::max_len($title, $max_title_len)) {
            board::notice(false, lang::get_phrase(141, $max_title_len));
        }
        if ( ! validation::min_len($content, $min_content_len)) {
            board::notice(false, lang::get_phrase(142, $min_content_len));
        }
        if ( ! validation::max_len($content, $max_content_len)) {
            board::notice(false, lang::get_phrase(143, $max_content_len));
        }
    }


    //Отправить в корзину новость

    public static function get_page($id)
    {
        return sql::run("SELECT * FROM `pages` WHERE id=?", [$id])->fetch();
    }

    public static function trash_send($id)
    {
        sql::run('DELETE FROM `pages` WHERE `id` = ?', [$id]);
        redirect::location("/admin/pages");
        die();
    }

    public static function show_page()
    {
        return sql::run("SELECT * FROM `pages` ORDER by id DESC")->fetchAll();
    }

    public static function show_pages_short($max_desc_len = 300, $trash = false)
    {
        if ($trash == true) {
            return sql::run(
              "SELECT `id`, `name`, LEFT(content, $max_desc_len) AS `content`, `trash`, `date_create` FROM `pages` WHERE trash = 1;"
            )->fetchAll();
        }

        return sql::run("SELECT `id`, `name`, LEFT(content, $max_desc_len) AS `content`, `trash`, `date_create` FROM `pages`;")->fetchAll();
    }

}