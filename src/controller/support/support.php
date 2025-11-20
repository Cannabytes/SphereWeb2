<?php

namespace Ofey\Logan22\controller\support;

use Exception;
use Intervention\Image\ImageManager;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\request\url;
use Ofey\Logan22\component\request\XssSecurity;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\controller\page\error;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;
use PDO;

class support
{

    private static ?array $sections = null;
    private static ?int $currentUserId = null;

    /**
     * @url /support/thread/1
     * @param $id
     * @return void
     */
    static function showThread($id): void
    {
        self::isEnable();

        // Если пользователь не администратор и у него нет обращений, редиректим на создание
        if (!user::self()->isAdmin() && !self::isUserModerator() && !self::hasUserThreads()) {
            redirect::location("/support/new");
        }

        tpl::addVar([
            'sections' => self::sections(),
            'threads' => self::getThreads($id),
            'isUserModerator' => self::isUserModerator(),
            'currentSection' => $id,
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
        if (user::self()->isAdmin()) {
            return true;
        }

        // Получаем секции напрямую из базы данных для проверки модераторов
        $sections = sql::getRows("SELECT moderators FROM `support_thread_name` WHERE moderators IS NOT NULL");
        foreach ($sections as $section) {
            if ($section['moderators'] != null) {
                $moderators = json_decode($section['moderators'], true);
                if (is_array($moderators)) {
                    foreach ($moderators as $moderator) {
                        if ($moderator == user::self()->getEmail()) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    private static function sections(): array
    {
        // Проверяем, является ли пользователь администратором или модератором
        $isAdmin = user::self()->isAdmin() || self::isUserModerator();
        $userId = user::self()->getId();

        // Сбрасываем кэш, если пользователь изменился
        if (self::$currentUserId !== null && self::$currentUserId !== $userId) {
            self::$sections = null;
        }

        if (self::$sections !== null) {
            return self::$sections;
        }

        self::$currentUserId = $userId;
        self::$sections = [];
        foreach (sql::getRows("SELECT * FROM `support_thread_name`") as $row) {
            if ($row['moderators'] != null) {
                $row['moderators'] = json_decode($row['moderators'], true);
            }

            // Подсчитываем количество тикетов в категории
            if ($isAdmin) {
                // Для администратора - все тикеты в категории
                $count = sql::getRow("SELECT COUNT(*) as count FROM support_thread WHERE thread_id = ?", [$row['id']]);
            } else {
                // Для обычного пользователя - только его тикеты в категории
                $count = sql::getRow("SELECT COUNT(*) as count FROM support_thread WHERE thread_id = ? AND owner_id = ?", [$row['id'], $userId]);
            }

            $row['thread_count'] = $count['count'];
            self::$sections[$row['id']] = $row;
        }

        return self::$sections;
    }

    private static array $noReadCountThreads = [];
    public static function getThreadsNoReadCount(): int
    {
        $currentUserId = user::self()->getId();
        if (isset(self::$noReadCountThreads[$currentUserId])) {
            return self::$noReadCountThreads[$currentUserId];
        }
        // Проверяем, является ли пользователь администратором
        $isAdmin = user::self()->isAdmin() || self::isUserModerator();
        $readMap = self::getReadMap($currentUserId);

        if ($isAdmin) {
            // Для администратора: все непрочитанные чаты
            $threads = sql::getRows("SELECT id, date_update FROM support_thread");
        } else {
            // Для обычного пользователя: только его чаты
            $threads = sql::getRows("SELECT id, date_update FROM support_thread WHERE owner_id = ?", [$currentUserId]);
        }

        $unreadCount = 0;
        foreach ($threads as $thread) {
            $threadUpdateTs = self::toTimestamp($thread['date_update'] ?? null);
            if (!self::isThreadReadByUser($thread['id'], $threadUpdateTs, $readMap)) {
                $unreadCount++;
            }
        }

        return self::$noReadCountThreads[$currentUserId] = $unreadCount;
    }

    private static function getThreads($id = null): array
    {
        // Если пользователь администратор или модератор, показываем все обращения
        $isAdmin = user::self()->isAdmin() || self::isUserModerator();

        if ($id != null) {
            if ($isAdmin) {
                // Для администратора и модератора - все обращения в категории
                $threads = sql::getRows("
                                    SELECT 
                                        st.id, 
                                        st.thread_id, 
                                        st.owner_id, 
                                        (
                                            SELECT sm.user_id 
                                            FROM support_message sm 
                                            WHERE sm.id = (
                                                SELECT MAX(sm_inner.id) 
                                                FROM support_message sm_inner 
                                                WHERE sm_inner.thread_id = st.id
                                            )
                                        ) AS last_user_id, 
                                        st.date_update, 
                                        (
                                            SELECT SUBSTRING(sm.message, 1, 200) 
                                            FROM support_message sm 
                                            WHERE sm.id = (
                                                SELECT MAX(sm_inner.id) 
                                                FROM support_message sm_inner 
                                                WHERE sm_inner.thread_id = st.id
                                            )
                                        ) AS message, 
                                        st.private, 
                                        st.is_close
                                    FROM 
                                        support_thread st
                                    WHERE 
                                        st.thread_id = ?
                                    ORDER BY 
                                        st.date_update DESC;
                                ", [$id]);
            } else {
                // Для обычного пользователя - только его обращения в категории
                $userId = user::self()->getId();
                $threads = sql::getRows("
                                    SELECT 
                                        st.id, 
                                        st.thread_id, 
                                        st.owner_id, 
                                        (
                                            SELECT sm.user_id 
                                            FROM support_message sm 
                                            WHERE sm.id = (
                                                SELECT MAX(sm_inner.id) 
                                                FROM support_message sm_inner 
                                                WHERE sm_inner.thread_id = st.id
                                            )
                                        ) AS last_user_id, 
                                        st.date_update, 
                                        (
                                            SELECT SUBSTRING(sm.message, 1, 200) 
                                            FROM support_message sm 
                                            WHERE sm.id = (
                                                SELECT MAX(sm_inner.id) 
                                                FROM support_message sm_inner 
                                                WHERE sm_inner.thread_id = st.id
                                            )
                                        ) AS message, 
                                        st.private, 
                                        st.is_close
                                    FROM 
                                        support_thread st
                                    WHERE 
                                        st.thread_id = ? AND st.owner_id = ?
                                    ORDER BY 
                                        st.date_update DESC;
                                ", [$id, $userId]);
            }
        } else {
            if ($isAdmin) {
                // Для администратора и модератора - все обращения
                $threads = sql::getRows("SELECT st.id, st.thread_id, st.owner_id, ( SELECT sm.user_id FROM support_message sm WHERE sm.id = ( SELECT MAX(sm_inner.id) FROM support_message sm_inner WHERE sm_inner.thread_id = st.id ) ) AS last_user_id, st.date_update, ( SELECT SUBSTRING(sm.message, 1, 200)  FROM support_message sm WHERE sm.id = ( SELECT MAX(sm_inner.id) FROM support_message sm_inner WHERE sm_inner.thread_id = st.id ) ) AS message, st.private, st.is_close FROM support_thread st ORDER BY st.date_update DESC; ");
            } else {
                // Для обычного пользователя - только его обращения
                $userId = user::self()->getId();
                $threads = sql::getRows("SELECT st.id, st.thread_id, st.owner_id, ( SELECT sm.user_id FROM support_message sm WHERE sm.id = ( SELECT MAX(sm_inner.id) FROM support_message sm_inner WHERE sm_inner.thread_id = st.id ) ) AS last_user_id, st.date_update, ( SELECT SUBSTRING(sm.message, 1, 200)  FROM support_message sm WHERE sm.id = ( SELECT MAX(sm_inner.id) FROM support_message sm_inner WHERE sm_inner.thread_id = st.id ) ) AS message, st.private, st.is_close FROM support_thread st WHERE st.owner_id = ? ORDER BY st.date_update DESC; ", [$userId]);
            }
        }

        $readMap = self::getReadMap(user::self()->getId());

        foreach ($threads as &$thread) {
            $threadUpdateTs = self::toTimestamp($thread['date_update'] ?? null);
            $thread['is_read'] = self::isThreadReadByUser($thread['id'], $threadUpdateTs, $readMap);
        }

        return $threads;
    }

    public static function getSection(int $threadId): ?array
    {
        return self::sections()[$threadId] ?? null;
    }

    /**
     * Сбрасывает кэш секций
     */
    public static function clearSectionsCache(): void
    {
        self::$sections = null;
        self::$currentUserId = null;
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
        if ($message == "") {
            board::error("Нельзя отправить пустое сообщение");
        }
        $section = (int) $_POST['section'] ?? 1;
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
        sql::run("INSERT INTO `support_thread` (`last_message_id`, `thread_id`, `owner_id`, `last_user_id`, `private`, `date_update`, `date_create`) VALUES (?, ?, ?, ?, ?, ?, ?)", [
            0,
            $section,
            \Ofey\Logan22\model\user\user::self()->getId(),
            \Ofey\Logan22\model\user\user::self()->getId(),
            1,
            time::mysql(),
            time::mysql(),
        ]);
        $support_thread_id = sql::lastInsertId();

        try {
            sql::run("INSERT INTO `support_message` (`thread_id`, `user_id`, `message`, `screens`, `date_update`, `date_create`) VALUES (?, ?, ?, ?, ?, ?)", [
                $support_thread_id,
                \Ofey\Logan22\model\user\user::self()->getId(),
                $message,
                $screens ?? PDO::PARAM_NULL,
                time::mysql(),
                time::mysql(),
            ]);
            $support_message_id = sql::lastInsertId();

            sql::run("UPDATE `support_thread` SET `last_message_id` = ? WHERE `id` = ?", [$support_message_id, $support_thread_id]);
            self::incMessage($section);
            $link = "/support/read/" . $support_thread_id;

            if (!self::isUserModerator() and config::load()->notice()->isTechnicalSupport()) {
                $msg = sprintf("Пользователь %s (%s) обратился в техническую поддержку\n<a href='%s'>Открыть ссылку</a>", user::self()->getEmail(), user::self()->getName(), url::host($link));
                telegram::sendTelegramMessage($msg, config::load()->notice()->getTechnicalSupportThreadId());
            }

            board::redirect($link);
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
        $message = XssSecurity::clean($message);
        return $message;
    }

    private static function incMessage($section): void
    {
        sql::run("UPDATE `support_thread_name` SET `thread_count` = thread_count+1 WHERE `id` = ?; ", [
            $section,
        ]);
    }

    /**
     * Проверяет, есть ли у пользователя обращения в техподдержке
     * @return bool
     */
    private static function hasUserThreads(): bool
    {
        $userId = user::self()->getId();
        $count = sql::getRow("SELECT COUNT(*) as count FROM support_thread WHERE owner_id = ?", [$userId]);
        return $count['count'] > 0;
    }

    /**
     * @url /support
     * @return void
     */
    public static function show(): void
    {
        self::isEnable();
        if (user::self()->isGuest()) {
            redirect::location("/login");
        }

        // Если пользователь не администратор и у него нет обращений, редиректим на создание
        if (!user::self()->isAdmin() && !self::isUserModerator() && !self::hasUserThreads()) {
            redirect::location("/support/new");
        }

        tpl::addVar([
            'main' => true,
            'sections' => self::sections(),
            'threads' => self::getThreads(),
            'isUserModerator' => self::isUserModerator(),
            'currentSection' => null,
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
        $support_thread_id = (int) $_POST['id'];
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

        if ($screens == null and $message == "") {
            board::error("Нельзя отправить пустое сообщение");
        }

        if (!self::isSendMessage($owner_id, $support_thread)) {
            board::error("У Вас нет прав отвечать в данном диалоге");
        }

        sql::run("INSERT INTO `support_message` (`thread_id`, `user_id`, `message`, `screens`, `date_update`, `date_create`) VALUES (?, ?, ?, ?, ?, ?)", [
            $support_thread_id,
            \Ofey\Logan22\model\user\user::self()->getId(),
            $message,
            $screens ?? PDO::PARAM_NULL,
            time::mysql(),
            time::mysql(),
        ]);
        $support_message_id = sql::lastInsertId();
        sql::run("UPDATE `support_thread` SET `last_message_id` = ?, `last_user_id` = ?, `date_update` = ? WHERE `id` = ?", [
            $support_message_id,
            \Ofey\Logan22\model\user\user::self()->getId(),
            time::mysql(),
            $support_thread_id
        ]);

        self::clearUnreadCache();
        self::markThreadAsRead($support_thread_id, \Ofey\Logan22\model\user\user::self()->getId());

        if (!self::isUserModerator() and config::load()->notice()->isTechnicalSupport()) {
            $link = "/support/read/" . $support_thread_id;
            $msg = sprintf("Пользователь %s (%s) написал сообщение в техническую поддержку\n<a href='%s'>Открыть ссылку</a>", user::self()->getEmail(), user::self()->getName(), url::host($link));
            telegram::sendTelegramMessage($msg, config::load()->notice()->getTechnicalSupportThreadId());
        }

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
        
        // XSS защита: очищаем phraseId
        $phraseId = XssSecurity::cleanText($phraseId);
        
        sql::run("INSERT INTO `support_thread_name` (`thread_name`) VALUES (?);", [$phraseId]);
        self::clearSectionsCache();
        board::reload();
        board::success("Добавлено");
    }

    public static function deleteTopic(): void
    {
        self::isEnable();
        $id = $_POST['id'] ?? board::error("No ID");
        sql::run("DELETE FROM support_thread WHERE `id` = ?", [$id]);
        $rows = sql::getRows("SELECT * FROM `support_message` WHERE `thread_id` = ?", [$id]);

        foreach ($rows as $row) {
            $screens = json_decode($row['screens']);
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
                            if (file_exists($thumbnailPath)) {
                                unlink($thumbnailPath);
                            }
                        }
                    }
                }
            }
        }

        sql::run("DELETE FROM support_message WHERE `thread_id` = ?", [$id]);
        board::redirect("/support");
        board::success("Диалог удален");
    }

    public static function deletePost(): void
    {
        if (self::isUserModerator() or user::self()->isAdmin()) {
            $postId = $_POST['postId'] ?? board::error("Нет ID объекта");
            $row = sql::getRow("SELECT * FROM `support_message` WHERE `id` = ? LIMIT 1", [$postId]);
            if ($row) {
                $thread_id = $row['thread_id'];
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

            }
        } else {
            board::error("Запрещенное действие");
        }
    }

    public static function updateModeratorsPrivilege(): void
    {
        self::isEnable();
        $data = $_POST['data'];
        if (!$data) {
            board::error("No data");
        }
        foreach ($data as $id => $info) {
            $sectionId = (int)$info['id'];
            
            // XSS защита: очищаем email адреса модераторов
            if (is_array($info['moderators'])) {
                $cleanModerators = [];
                foreach ($info['moderators'] as $moderator) {
                    // Очищаем каждый email от потенциально опасного содержимого
                    $cleanModerators[] = XssSecurity::cleanText($moderator);
                }
                $moderators = json_encode($cleanModerators);
            } else {
                $moderators = json_encode([]);
            }
            
            sql::run("UPDATE `support_thread_name` SET `moderators` = ? WHERE `id` = ?; ", [
                $moderators,
                $sectionId,
            ]);
        }
        self::clearSectionsCache();
        board::reload();
        board::success("Обновлено");
    }

    public static function closeTopic(): void
    {
        self::isEnable();
        $support_thread_id = (int) $_POST['id'] ?? board::error("No ID");
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

    /**
     * Создание диалога админом/модератором с пользователем
     * @url /support/admin/create/dialog
     */
    public static function createDialogByAdmin(): void
    {
        self::isEnable();
        
        // Проверяем права доступа
        if (!user::self()->isAdmin() && !self::isUserModerator()) {
            board::error(lang::get_phrase("You don't have permission to perform this action"));
        }
        
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        if ($targetUserId <= 0) {
            board::error(lang::get_phrase("User ID not specified"));
        }
        
        // Проверяем, существует ли пользователь
        $targetUser = sql::getRow("SELECT id, email, name FROM users WHERE id = ?", [$targetUserId]);
        if (!$targetUser) {
            board::error(lang::get_phrase("User not found"));
        }
        
        // Проверяем, не существует ли уже открытый диалог с этим пользователем
        $existingThread = sql::getRow(
            "SELECT id FROM support_thread WHERE owner_id = ? AND is_close = 0 ORDER BY date_update DESC LIMIT 1",
            [$targetUserId]
        );
        
        // Если есть открытый диалог, возвращаем его ID
        if ($existingThread) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => true,
                'thread_id' => $existingThread['id'],
                'message' => lang::get_phrase("Existing dialog opened")
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Получаем выбранную категорию или используем первую доступную
        $sectionId = (int)($_POST['section_id'] ?? 0);
        $sections = self::sections();
        
        if ($sectionId <= 0 || !isset($sections[$sectionId])) {
            // Если категория не указана или не существует, используем первую доступную
            if (!empty($sections)) {
                $firstSection = reset($sections);
                $sectionId = $firstSection['id'];
            } else {
                $sectionId = 1; // Fallback на значение по умолчанию
            }
        }
        
        // Создаем новый диалог
        $adminId = user::self()->getId();
        $currentTime = time::mysql();
        
        sql::run("INSERT INTO `support_thread` (`last_message_id`, `thread_id`, `owner_id`, `last_user_id`, `private`, `date_update`, `date_create`) VALUES (?, ?, ?, ?, ?, ?, ?)", [
            0,
            $sectionId,
            $targetUserId, // Владелец диалога - выбранный пользователь
            $adminId, // Последний отправитель - админ
            1, // Приватный диалог
            $currentTime,
            $currentTime,
        ]);
        $support_thread_id = sql::lastInsertId();
        
        // Не создаем приветственное сообщение - админ сам напишет первое сообщение
        self::incMessage($sectionId);
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'thread_id' => $support_thread_id,
            'message' => lang::get_phrase("Dialog created successfully")
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Массовая отправка сообщений выбранным пользователям
     * @url /support/admin/mass/send
     */
    public static function massSendMessages(): void
    {
        self::isEnable();
        
        // Проверяем права доступа
        if (!user::self()->isAdmin() && !self::isUserModerator()) {
            board::error(lang::get_phrase("You don't have permission to perform this action"));
        }
        
        $userIds = $_POST['user_ids'] ?? [];
        if (!is_array($userIds) || empty($userIds)) {
            board::error(lang::get_phrase("No users specified for sending"));
        }
        
        $sectionId = (int)($_POST['section_id'] ?? 0);
        $sections = self::sections();
        
        if ($sectionId <= 0 || !isset($sections[$sectionId])) {
            if (!empty($sections)) {
                $firstSection = reset($sections);
                $sectionId = $firstSection['id'];
            } else {
                $sectionId = 1;
            }
        }
        
        $message = self::postMessage();
        if ($message == "") {
            board::error("Нельзя отправить пустое сообщение");
        }
        
        $adminId = user::self()->getId();
        $currentTime = time::mysql();
        $sentCount = 0;
        $errors = [];
        
        foreach ($userIds as $userId) {
            $targetUserId = (int)$userId;
            if ($targetUserId <= 0) {
                continue;
            }
            
            // Проверяем, существует ли пользователь
            $targetUser = sql::getRow("SELECT id FROM users WHERE id = ?", [$targetUserId]);
            if (!$targetUser) {
                $errors[] = lang::get_phrase("User with ID %s not found", $targetUserId);
                continue;
            }
            
            // Проверяем, не существует ли уже открытый диалог
            $existingThread = sql::getRow(
                "SELECT id FROM support_thread WHERE owner_id = ? AND is_close = 0 ORDER BY date_update DESC LIMIT 1",
                [$targetUserId]
            );
            
            $threadId = null;
            if ($existingThread) {
                // Используем существующий диалог
                $threadId = $existingThread['id'];
            } else {
                // Создаем новый диалог
                sql::run("INSERT INTO `support_thread` (`last_message_id`, `thread_id`, `owner_id`, `last_user_id`, `private`, `date_update`, `date_create`) VALUES (?, ?, ?, ?, ?, ?, ?)", [
                    0,
                    $sectionId,
                    $targetUserId,
                    $adminId,
                    1,
                    $currentTime,
                    $currentTime,
                ]);
                $threadId = sql::lastInsertId();
            }
            
            // Отправляем сообщение
            try {
                sql::run("INSERT INTO `support_message` (`thread_id`, `user_id`, `message`, `screens`, `date_update`, `date_create`) VALUES (?, ?, ?, ?, ?, ?)", [
                    $threadId,
                    $adminId,
                    $message,
                    PDO::PARAM_NULL,
                    $currentTime,
                    $currentTime,
                ]);
                $support_message_id = sql::lastInsertId();
                
                sql::run("UPDATE `support_thread` SET `last_message_id` = ?, `last_user_id` = ?, `date_update` = ? WHERE `id` = ?", [
                    $support_message_id,
                    $adminId,
                    $currentTime,
                    $threadId
                ]);
                
                // Увеличиваем счетчик только для новых диалогов
                if (!$existingThread) {
                    self::incMessage($sectionId);
                }
                
                self::clearUnreadCache();
                self::markThreadAsRead($threadId, $adminId);

                $sentCount++;
            } catch (Exception $e) {
                $errors[] = lang::get_phrase("Error sending to user ID %s", $targetUserId) . ": " . $e->getMessage();
            }
        }
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'sent' => $sentCount,
            'total' => count($userIds),
            'errors' => $errors,
            'message' => lang::get_phrase("Sent %s of %s messages", $sentCount, count($userIds))
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    static public function fileLoad()
    {
        self::isEnable();
        if (isset($_FILES['filepond'])) {
            $manager = ImageManager::gd();

            if (!user::self()->isAdmin()) {
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

                    // Получаем исходные размеры изображения
                    $originalWidth = $image->width();
                    $originalHeight = $image->height();

                    // Генерация уникального имени для основного файла
                    $screen = mt_rand(1, PHP_INT_MAX) . '.png';

                    // Проверка существования папки
                    if (!file_exists('uploads/support')) {
                        mkdir('uploads/support', 0777, true);
                    }

                    // Сохранение основного изображения
                    $success = $image->save('uploads/support/' . $screen);
                    if ($success) {
                        $thumbImage = $image;

                        // Создание миниатюры
                        if ($originalHeight > 300) {
                            $thumbImage = $image->scale(height: 300);
                        }
                        if ($originalWidth > 300) {
                            $thumbImage = $image->scale(width: 300);
                        }
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

        if (!user::self()->isGuest()) {
            self::markThreadAsRead($id, user::self()->getId());
        }

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
            'currentUserId' => \Ofey\Logan22\model\user\user::self()->getId(),
        ]);
        tpl::display("support/read.html");
    }

    private static function getMessages(int $threadId, int $startFromId = 0): array
    {
        $sql = "SELECT id, thread_id, user_id, message, screens, date_update, date_create 
            FROM support_message 
            WHERE thread_id = ?";

        $params = [$threadId];

        if ($startFromId > 0) {
            $sql .= " AND id > ?";
            $params[] = $startFromId;
        }

        $sql .= " ORDER BY id ASC";
        $rows = sql::getRows($sql, $params);
        foreach ($rows as &$row) {
                // Decode HTML entities so that previously-encoded tags like &lt;p&gt; become <p>
                // then sanitize allowing a whitelist of safe tags.
                $decoded = html_entity_decode($row['message'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $row['message'] = XssSecurity::clean($decoded);

 
        }
        return $rows;
    }

    public static function getRefreshMessage()
    {
        header('Content-Type: application/json');

        $threadId = (int)($_POST['threadId'] ?? 0);
        $startFromId = (int)($_POST['startFromId'] ?? 0);

        $thread = sql::getRow("SELECT id, owner_id FROM support_thread WHERE id = ?", [$threadId]);

        if (!$thread) {
            echo json_encode(['error' => 'Thread not found']);
            exit;
        }

        $isOwner = $thread['owner_id'] === user::self()->getId();
        $isAllowed = $isOwner || self::isUserModerator();

        if ($isAllowed) {
            $messages = self::getMessages($thread['id'], $startFromId);

            foreach ($messages as &$message) {
                $userInfo = \Ofey\Logan22\model\user\user::getUserId($message['user_id']);
                $message['user_name'] = $userInfo->getName();
                $message['user_avatar'] = $userInfo->getAvatar();
                $message['user_is_online'] = $userInfo->isOnline();
                $message['user_is_avatar_video'] = $userInfo->isAvatarVideo();
                $message['user_email'] = $userInfo->getEmail();
                $message['user_id'] = $userInfo->getId();
                
                if (is_string($message['message'])) {
                    $message['message'] = XssSecurity::clean($message['message']);
                }
            }

            echo json_encode($messages);
        } else {
            echo json_encode(['error' => 'Not allowed to view messages']);
        }

        exit;
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
            $toMove,
            $id
        ]);
        self::decMessage($id);
        self::incMessage($toMove);
        board::reload();
        board::success("Перемещено");
    }

    private static function markThreadAsRead(int $threadId, int $userId): void
    {
        $readAt = time::mysql();
        $updated = sql::run("UPDATE `support_read_topics` SET `read_at` = ? WHERE `user_id` = ? AND `topic_id` = ?", [
            $readAt,
            $userId,
            $threadId,
        ]);

        if (!($updated instanceof \PDOStatement) || $updated->rowCount() === 0) {
            sql::run("INSERT INTO `support_read_topics` (`user_id`, `topic_id`, `read_at`) VALUES (?, ?, ?)", [
                $userId,
                $threadId,
                $readAt,
            ]);
        }

        self::clearUnreadCache($userId);
    }

    private static function decMessage($support_thread_id): void
    {
        sql::run("UPDATE `support_thread_name` SET `thread_count` = thread_count-1 WHERE `id` = ?;", [
            $support_thread_id,
        ]);
    }

    private static function getReadMap(int $userId): array
    {
        $rows = sql::getRows("SELECT topic_id, read_at FROM `support_read_topics` WHERE user_id = ?", [$userId]);
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['topic_id']] = self::toTimestamp($row['read_at'] ?? null);
        }
        return $map;
    }

    private static function toTimestamp(?string $dateTime): int
    {
        if (!$dateTime) {
            return 0;
        }
        $ts = strtotime($dateTime);
        return $ts === false ? 0 : $ts;
    }

    private static function isThreadReadByUser(int $threadId, int $threadUpdateTs, array $readMap): bool
    {
        $readTs = $readMap[$threadId] ?? null;
        if ($threadUpdateTs === 0) {
            return $readTs !== null;
        }
        return $readTs !== null && $readTs >= $threadUpdateTs;
    }

    private static function clearUnreadCache(?int $userId = null): void
    {
        if ($userId === null) {
            self::$noReadCountThreads = [];
            return;
        }
        unset(self::$noReadCountThreads[$userId]);
    }

}