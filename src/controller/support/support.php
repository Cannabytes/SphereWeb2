<?php

namespace Ofey\Logan22\controller\support;

use Exception;
use Intervention\Image\ImageManager;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\controller\page\error;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;
use PDO;

class support
{

    private static ?array $sections = null;

    /**
     * @url /support/thread/1
     * @param $id
     * @return void
     */
    static function showThread($id): void
    {
        self::isEnable();
        tpl::addVar([
            'sections' => self::sections(),
            'threads' => self::getThreads($id),
            'isUserModerator' => self::isUserModerator(),
        ]);
        tpl::display("support/index.html");
    }

    static function isEnable(): void
    {
        if (!\Ofey\Logan22\controller\config\config::load()->enabled()->isEnableSupport()) {
            if (self::isUserModerator()) {
                return;
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST' || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')) {
                board::error("Техническая поддержка отключена");
            } else {
                redirect::location("/main");
            }
        }
    }

    private static function isUserModerator(): bool
    {
        self::sections();
        if (user::self()->isAdmin()) {
            return true;
        }
        foreach (self::$sections as $section) {
            foreach ($section['moderators'] as $moderator) {
                if ($moderator == user::self()->getEmail()) {
                    return true;
                }
            }
        }
        return false;
    }

    private static function sections(): array
    {
        if (self::$sections !== null) {
            return self::$sections;
        }
        self::$sections = [];
        foreach (sql::getRows("SELECT * FROM `support_thread_name`") as $row) {
            if ($row['moderators'] != null) {
                $row['moderators'] = json_decode($row['moderators'], true);
            }
            self::$sections[$row['id']] = $row;
        }
        return self::$sections;
    }

    private static function getThreads($id = null): array
    {
        if ($id != null) {
            return sql::getRows("SELECT 
                                            support_thread.id, 
                                            support_thread.thread_id, 
                                            support_thread.owner_id, 
                                            support_thread.last_user_id, 
                                            support_thread.date_update, 
                                            support_message.message,
                                            support_thread.private,
                                            support_thread.is_close
                                        FROM 
                                            support_thread 
                                        INNER JOIN 
                                            support_message 
                                        ON 
                                            support_thread.last_message_id = support_message.id 
                                        WHERE 
                                            support_thread.thread_id = ? 
                                        ORDER BY 
                                            support_thread.date_update DESC;
                                        ", [$id]);
        }
        return sql::getRows("SELECT support_thread.id, support_thread.thread_id, support_thread.owner_id, support_thread.last_user_id, support_thread.date_update, support_message.message, support_thread.private, support_thread.is_close FROM support_thread INNER JOIN support_message ON support_thread.last_message_id = support_message.id ORDER BY support_thread.date_update DESC");
    }

    public static function getSection(int $threadId): ?array
    {
        return self::sections()[$threadId] ?? null;
    }

    public static function create(): void
    {
        self::isEnable();
        tpl::addVar([
            'sections' => self::sections(),
        ]);
        tpl::display("support/create.html");
    }

    public static function requestCreate(): void
    {
        self::isEnable();
        if (self::lastTimeMessage() <= 10) {
            board::error("Отправка сообщений не чаще чем раз в 10 сек.");
        }
        $message = self::postMessage();
        $section = (int)$_POST['section'] ?? 1;
        $private = filter_input(INPUT_POST, 'private', FILTER_VALIDATE_BOOL);
        $screens = null;
        if (isset($_POST['screens'])) {
            foreach ($_POST['screens'] as $screen) {
                if (file_exists('uploads/support/' . $screen)) {
                    $screens[] = '/uploads/support/' . $screen;
                }
            }
            if ($screens != null) {
                $screens = json_encode($screens);
            }
        }
        sql::run("INSERT INTO `support_thread` ( `thread_id`, `owner_id`, `last_user_id`, `private`, `date_update`, `date_create`) VALUES (?, ?, ?, ?, ?, ?)", [
            $section,
            \Ofey\Logan22\model\user\user::self()->getId(),
            \Ofey\Logan22\model\user\user::self()->getId(),
            $private,
            time::mysql(),
            time::mysql(),
        ]);
        $support_thread_id = sql::lastInsertId();

        try {
            sql::run("INSERT INTO `support_message` (`thread_id`, `user_id`, `message`, `screens`, `date_update`, `date_create`) VALUES (?, ?, ?, ?, ?, ?)", [
                $support_thread_id, \Ofey\Logan22\model\user\user::self()->getId(), $message, $screens ?? PDO::PARAM_NULL, time::mysql(), time::mysql(),
            ]);
            $support_message_id = sql::lastInsertId();

            sql::run("UPDATE `support_thread` SET `last_message_id` = ? WHERE `id` = ?", [$support_message_id, $support_thread_id]);
            self::incMessage($section);

            board::redirect("/support/read/" . $support_thread_id);
            board::success("Создано");

        } catch (Exception $exception) {
            error::show($exception);
        }

    }

    private static function lastTimeMessage(): ?int
    {
        if (user::self()->isAdmin()) {
            return 9999999;
        }
        $userId = user::self()->getId();
        $time = sql::getRow("SELECT MAX(date_update) AS last_message_time FROM support_message WHERE user_id = ? LIMIT 1;", [$userId]);
        if ($time && !empty($time['last_message_time'])) {
            $lastMessageTime = strtotime($time['last_message_time']);
            $currentTime = time();
            return $currentTime - $lastMessageTime;
        }
        return 9999999;
    }

    private static function postMessage(): string
    {
        if (empty($_POST['message'])) {
            board::error("Message is required");
        }
        $message = trim($_POST['message']);
        if (mb_strlen($message) < 1) {
            board::error("Message must be at least 20 characters long");
        }
        $allowedTags = '<b><i><strong><em><u><p><br><ul><ol><li><p><br></p>';
        $message = str_replace(['<p><br></p>'], '', $message);
        $message = strip_tags($message, $allowedTags);
        if ($message == "") {
            board::error("Нельзя отправить пустое сообщение");
        }
        return $message;
    }

    private static function incMessage($section): void
    {
        sql::run("UPDATE `support_thread_name` SET `thread_count` = thread_count+1 WHERE `id` = ?; ", [
            $section,
        ]);
    }

    /**
     * @url /support
     * @return void
     */
    static function show(): void
    {
        self::isEnable();
        tpl::addVar([
            'main' => true,
            'sections' => self::sections(),
            'threads' => self::getThreads(),
            'isUserModerator' => self::isUserModerator(),
        ]);
        tpl::display("support/index.html");
    }

    public static function requestReply(): void
    {
        self::isEnable();
        if (self::lastTimeMessage() <= 10) {
            board::error("Отправка сообщений не чаще чем раз в 10 сек.");
        }
        $message = self::postMessage();
        $support_thread_id = (int)$_POST['id'];
        $support_thread = sql::getRow('SELECT `owner_id`, `is_close` FROM `support_thread` WHERE id = ?', [$support_thread_id]);
        $owner_id = $support_thread['owner_id'];

        $screens = null;
        if (isset($_POST['screens'])) {
            if (!is_array($_POST['screens'])) {
                board::error("Ошибка");
            }
            if (count($_POST['screens']) > 6) {
                board::error('Не более 6 изображений можно прикрепить к сообщению.');
            }
            foreach ($_POST['screens'] as $screen) {
                if (file_exists('uploads/support/' . $screen)) {
                    $screens[] = '/uploads/support/' . $screen;
                }
            }
            $screens = json_encode($screens);
        }

        if (!self::isSendMessage($owner_id, $support_thread)) {
            board::error("У Вас нет прав отвечать в данном диалоге");
        }

        sql::run("INSERT INTO `support_message` (`thread_id`, `user_id`, `message`, `screens`, `date_update`, `date_create`) VALUES (?, ?, ?, ?, ?, ?)", [
            $support_thread_id, \Ofey\Logan22\model\user\user::self()->getId(), $message,
            $screens ?? PDO::PARAM_NULL,
            time::mysql(), time::mysql(),
        ]);
        $support_message_id = sql::lastInsertId();
        sql::run("UPDATE `support_thread` SET `last_message_id` = ?, `last_user_id` = ? WHERE `id` = ?", [
            $support_message_id, \Ofey\Logan22\model\user\user::self()->getId(), $support_thread_id]);
        board::reload();
        board::success("Добавлен");
    }

    private static function isSendMessage($owner_id, $support_thread): bool
    {
        //Может ли пользователь отвечать
        if (user::self()->isAdmin() or self::isUserModerator()) {
            return true;
        } else {
            if ($owner_id == user::self()->getId()) {
                if (!$support_thread['is_close']) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function addSection(): void
    {
        self::isEnable();
        $phraseId = $_POST['phraseId'] ?? board::error("No phrase");
        if ($phraseId == "") {
            board::error("Phrase ID is empty");
        }
        sql::run("INSERT INTO `support_thread_name` (`thread_name`) VALUES (?);", [$phraseId]);
        board::reload();
        board::success("Добавлено");
    }

    public static function deleteSection(): void
    {
        self::isEnable();
        $ids = $_POST['ids'] ?? board::error("error");

        if (empty($ids)) {
            board::error("IDs empty");
        }

        // Проверяем, что $ids - это массив
        if (!is_array($ids)) {
            board::error("Invalid data format");
        }

        // Экранируем и готовим ID для безопасного запроса
        $ids = array_map('intval', $ids); // Преобразуем в целые числа для безопасности
        $idsList = implode(',', $ids);   // Преобразуем массив в строку через запятую

        // SQL-запрос на удаление
        $query = "DELETE FROM `support_thread_name` WHERE `id` IN ($idsList)";
        sql::run($query);

        $query = "DELETE FROM `support_thread` WHERE `thread_id` IN ($idsList)";
        sql::run($query);

        board::reload();
        board::success("Удалено");
    }

    public static function deleteTopic(): void
    {
        self::isEnable();
        $id = $_POST['id'] ?? board::error("No ID");
        sql::run("DELETE FROM support_thread WHERE `id` = ?", [$id]);
        $rows = sql::getRows("SELECT * FROM `support_message` WHERE `thread_id` = ?", [$id]);

        foreach ($rows as $row) {
            $screens = json_decode($row['screens']);
            foreach ($screens as $screen) {
                // Получаем базовое имя файла
                $screenBaseName = basename($screen);

                // Удаляем запись из базы данных
                sql::run("DELETE FROM support_message_screen WHERE `filename` = ?", [$screenBaseName]);

                $fullPath = realpath(ltrim($screen, '/'));
                if ($fullPath && file_exists($fullPath)) {
                    unlink($fullPath);
                    $lastDotPos = mb_strrpos($fullPath, '.');
                    if ($lastDotPos !== false) {
                        $baseName = substr($fullPath, 0, $lastDotPos); // Часть до точки
                        $extension = substr($fullPath, $lastDotPos + 1); // Часть после точки
                        $thumbnailPath = $baseName . '_thumb.' . $extension;
                        unlink($thumbnailPath);
                    }
                }
            }
        }

        // Удаляем сообщения и саму тему
        sql::run("DELETE FROM support_message WHERE `thread_id` = ?", [$id]);
        board::redirect("/support");
        board::success("Диалог удален");
    }

    public static function deletePost(): void
    {
        if (self::isUserModerator() or user::self()->isAdmin()) {
            $postId = $_POST['postId'] ?? board::error("Нет ID объекта");
            $row = sql::getRow("SELECT * FROM `support_message` WHERE `id` = ? LIMIT 1", [$postId]);
            if ($row['screens']) {
                $screens = json_decode($row['screens'], true);
                if ($screens) {
                    foreach ($screens as $screen) {
                        $screenBaseName = basename($screen);
                        sql::run("DELETE FROM support_message_screen WHERE `filename` = ?", [$screenBaseName]);
                        $fullPath = realpath(ltrim($screen, '/'));
                        if ($fullPath && file_exists($fullPath)) {
                            unlink($fullPath);
                            $lastDotPos = mb_strrpos($fullPath, '.');
                            if ($lastDotPos !== false) {
                                $baseName = substr($fullPath, 0, $lastDotPos);
                                $extension = substr($fullPath, $lastDotPos + 1);
                                $thumbnailPath = $baseName . '_thumb.' . $extension;
                                unlink($thumbnailPath);
                            }
                        }
                    }
                }
            }
            sql::run("DELETE FROM support_message WHERE `id` = ?", [$postId]);
            board::reload();
            board::success("Удалено");
        } else {
            board::error("Запрещенное действие");
        }
    }

    public static function updateModeratorsPrivilege(): void
    {
        self::isEnable();
        $data = $_POST['data'];
        foreach ($data as $id => $info) {
            $sectionId = $info['id'];
            $moderators = json_encode($info['moderators']);
            sql::run("UPDATE `support_thread_name` SET `moderators` = ? WHERE `id` = ?; ", [
                $moderators,
                $sectionId,
            ]);
        }
        board::reload();
        board::success("Обновлено");
    }

    public static function closeTopic(): void
    {
        self::isEnable();
        $support_thread_id = (int)$_POST['id'] ?? board::error("No ID");
        $support_thread = sql::getRow('SELECT `owner_id`, `is_close` FROM `support_thread` WHERE id = ?', [$support_thread_id]);
        $owner_id = $support_thread['owner_id'];
        $statusClose = $support_thread['is_close'];

        if ($owner_id == user::self()->getId() or user::self()->isAdmin() or self::isUserModerator()) {
            sql::run("UPDATE `support_thread` SET `is_close` = ? WHERE `id` = ?;", [
                !$statusClose,
                $support_thread_id,
            ]);
            board::reload();
            if ($statusClose) {
                board::success("Тема была открыта");
            } else {
                board::success("Тема была закрыта");
            }
        } else {
            board::error("У Вас нет прав на выполнение этого действия");
        }
    }

    static public function fileLoad()
    {
        self::isEnable();
        if (isset($_FILES['filepond'])) {
            $manager = ImageManager::gd();

            $time = time() - 600;
            $row = sql::getRow("SELECT count(*) AS `count` FROM `support_message_screen` WHERE date_create > ? AND user_id = ?", [
                date('Y-m-d H:i:s', $time),
                user::self()->getId(),
            ]);
            if ($row['count'] > 6) {
                echo json_encode([
                    'type' => 'notice',
                    'ok' => false,
                    'status' => 'error',
                    'message' => 'Вы загрузили больше 6 изображений за последние 10 минут.'
                ]);
                exit;
            }

            // Преобразование структуры $_FILES для удобной обработки
            $files = $_FILES['filepond'];
            $fileCount = is_array($files['tmp_name']) ? count($files['tmp_name']) : 1;

            if ($fileCount > 6) {
                echo json_encode([
                    'type' => 'notice',
                    'ok' => false,
                    'status' => 'error',
                    'message' => 'Одновременно можно загрузить не более 6 изображений'
                ]);
                exit;
            }

            $screenUploaded = ""; // Список успешно загруженных файлов

            for ($i = 0; $i < $fileCount; $i++) {
                // Подготовка данных для обработки одного файла
                $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];

                if ($error == 0) {
                    $image = $manager->read($tmpName);

                    // Изменение размера изображения
                    $image->resize(1800, 1200, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });

                    // Генерация уникального имени для основного файла
                    $screen = mt_rand(1, PHP_INT_MAX) . '.png';

                    // Проверка существования папки
                    if (!file_exists('uploads/support')) {
                        mkdir('uploads/support', 0777, true);
                    }

                    // Сохранение основного изображения
                    $success = $image->save('uploads/support/' . $screen);
                    if ($success) {
                        // Создание миниатюры
                        $thumbImage = $image->resize(300, 200); // Размер миниатюры 300x200
                        $thumb = pathinfo($screen, PATHINFO_FILENAME) . '_thumb.png'; // Имя миниатюры с суффиксом _thumb

                        // Сохранение миниатюры
                        $thumbSuccess = $thumbImage->save('uploads/support/' . $thumb);

                        if (!$thumbSuccess) {
                            echo json_encode([
                                'status' => 'error',
                                'message' => 'Error saving thumbnail: ' . $thumb,
                            ]);
                            exit;
                        }

                        // Вставка записи в базу данных
                        sql::run("INSERT INTO `support_message_screen` (`filename`, `user_id`, `date_create`) VALUES (?, ?, ?)", [
                            $screen,
                            user::self()->getId(),
                            time::mysql(),
                        ]);

                        $screenUploaded = [
                            'screen' => "/uploads/support/" . $screen,
                            'thumbnail' => "/uploads/support/" . $thumb,
                        ];
                    } else {
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'Error saving image: ' . $screen,
                        ]);
                        exit;
                    }
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Error uploading file with index: ' . $i,
                    ]);
                    exit;
                }
            }

            // Возврат списка успешно загруженных файлов
            echo json_encode([
                'status' => 'success',
                'screen' => $screenUploaded['screen'],
                'thumbnail' => $screenUploaded['thumbnail'],
            ]);
            exit;
        }

        echo json_encode([
            'status' => 'error',
            'message' => 'No files uploaded',
        ]);
        exit;
    }

    /**
     * @url /support/read/(\d+)
     * @param $id
     * @return void
     */
    static function read($id = null): void
    {
        self::isEnable();
        $support_thread = sql::getRow('SELECT `owner_id`, `private`, `is_close` FROM `support_thread` WHERE id = ?', [$id]);
        if (!$support_thread) {
            redirect::location("/support");
        }
        $owner_id = $support_thread['owner_id'];
        if ($support_thread['private'] === 1) {
            $isNotOwner = $owner_id !== user::self()->getId();
            $isNotPrivileged = !self::isUserModerator() && !user::self()->isAdmin();

            if ($isNotOwner && $isNotPrivileged) {
                redirect::location("/support");
            }
        }
        tpl::addVar([
            'isSendMessage' => self::isSendMessage($owner_id, $support_thread),
            'thread' => $support_thread,
            'sections' => self::sections(),
            'posts' => self::getMessages($id),
            'id' => $id,
            'isUserModerator' => self::isUserModerator(),
        ]);
        tpl::display("support/read.html");
    }

    private static function getMessages($id): array
    {
        return sql::getRows("SELECT id, thread_id, user_id, message, screens, date_update, date_create FROM support_message WHERE thread_id = ?", [$id]);
    }

    public static function toMove(): void
    {
        self::isEnable();
        if (!self::isUserModerator()) {
            board::error("Запрещено");
        }
        $id = $_POST['id'];
        $toMove = $_POST['toMove'] ?? 1;
        sql::run("UPDATE `support_thread` SET `thread_id` = ? WHERE `id` = ?;", [
            $toMove, $id
        ]);
        self::decMessage($id);
        self::incMessage($toMove);
        board::reload();
        board::success("Перемещено");
    }

    private static function decMessage($support_thread_id): void
    {
        sql::run("UPDATE `support_thread_name` SET `thread_count` = thread_count-1 WHERE `id` = ?;", [
            $support_thread_id,
        ]);
    }

}