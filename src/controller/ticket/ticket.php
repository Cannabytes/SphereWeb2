<?php
/**
 * Created by Logan22
 */

namespace Ofey\Logan22\controller\ticket;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\ticket\ticketModel;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class ticket
{

    //TODO в будущем удалить.
    // Сделано для тех у кого нет этих таблиц.
    public static function install() {

        $queryCreateTickets = "
                CREATE TABLE `tickets`  (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `user_id` int NULL DEFAULT NULL,
                  `last_message_id` int NULL DEFAULT NULL,
                  `last_user_id` int NULL DEFAULT NULL,
                  `is_closed` int NULL DEFAULT 0,
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`) USING BTREE
                ) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;
                ";
      $sqlCreateTicketMessage = "
                CREATE TABLE `tickets_message`  (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `ticket_id` int NULL DEFAULT NULL,
                  `user_id` int NULL DEFAULT NULL,
                  `is_file` int NULL DEFAULT 0,
                  `message` varchar(4000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
                  `read` int NULL DEFAULT 0,
                  `date` datetime NULL DEFAULT NULL,
                  PRIMARY KEY (`id`) USING BTREE
                ) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;
                ";

      $sqlCreateTicketFiles = "
                CREATE TABLE `tickets_file`  (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `ticket_id` int NULL DEFAULT NULL,
                  `message_id` int NULL DEFAULT NULL,
                  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
                  `user_id` int NULL DEFAULT NULL,
                  PRIMARY KEY (`id`) USING BTREE
                ) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;
                ";

        $data = sql::getRow("SHOW TABLES LIKE 'tickets';");
        if(!$data){
            sql::run($queryCreateTickets);
            sql::run($sqlCreateTicketMessage);
            sql::run($sqlCreateTicketFiles);
        }
    }

    public static function all(): void
    {
        if ( ! \Ofey\Logan22\controller\config\config::load()->enabled()->isEnableTicket()) {
            redirect::location("/main");
        }

        //TODO: Удалить в будущем
        //Проверка существования таблицы tickets
        self::install();

        if (user::self()->isAdmin()) {
            tpl::addVar('lastTicketsList', ticketModel::lastTicketsList());
            tpl::display("/admin/ticket_admin_chat.html");
        } else {
            self::ticketUser();
        }
    }

    private static function ticketUser(): void
    {
        if ( ! \Ofey\Logan22\controller\config\config::load()->enabled()->isEnableTicket()) {
            redirect::location("/main");
        }

        $ticketData = sql::getRow("SELECT * FROM `tickets` WHERE user_id = ?", [user::self()->getId()]);
        $id         = $ticketData['id'];

        $chat = sql::getRows("SELECT * FROM `tickets_message` WHERE ticket_id = ? ORDER BY id DESC LIMIT 30", [$id]);
        //Обратная сортировка chat в обратном порядке
        $chat                      = array_reverse($chat);
        $last_element              = end($chat);
        $last_element_id           = $last_element['id'];
        $owner_last_message_author = end($chat)['user_id'];

        $chat = self::stackMessages($chat);
        foreach ($chat as &$ticket) {
            foreach ($ticket['messages'] as &$message) {
                if ($message['is_file']) {
                    $message['files'] = sql::getRows("SELECT `filename` FROM `tickets_file` WHERE message_id = ?", [$message['id']]);
                }
            }
        }

        tpl::addVar('owner_last_message_author', $owner_last_message_author);
        tpl::addVar('chatMessage', $chat);
        tpl::addVar("id", $id);
        tpl::addVar("last_element_id", $last_element_id);
        tpl::addVar("getTicket", $ticketData);

        tpl::display("chat.html");
    }

    static public function stackMessages($messages): array
    {
        $result          = [];
        $currentUserId   = null;
        $currentMessages = [];

        foreach ($messages as $message) {
            if ($currentUserId === null || $currentUserId === $message['user_id']) {
                $currentMessages[] = [
                  'id'      => $message['id'],
                  'message' => $message['message'],
                  'date'    => $message['date'],
                  'is_file' => $message['is_file'],
                ];
            } else {
                $result[]        = [
                  'user_id'  => $currentUserId,
                  'messages' => $currentMessages,
                ];
                $currentMessages = [
                  [
                    'id'      => $message['id'],
                    'message' => $message['message'],
                    'date'    => $message['date'],
                    'is_file' => $message['is_file'],
                  ],
                ];
            }
            $currentUserId = $message['user_id'];
        }

        if ( ! empty($currentMessages)) {
            $result[] = [
              'user_id'  => $currentUserId,
              'messages' => $currentMessages,
            ];
        }

        return $result;
    }

    public static function ticketAdmin($id): void
    {
        if ( ! \Ofey\Logan22\controller\config\config::load()->enabled()->isEnableTicket()) {
            redirect::location("/main");
        }

        $lastTicketsList = ticketModel::lastTicketsList();
        if (count($lastTicketsList) > 0) {
            sql::run("UPDATE tickets_message SET `read` = 1 WHERE ticket_id = ? AND `read` = 0", [$id]);
        }

        tpl::addVar('getTicket', ticketModel::getTicketInfo($id));

        $chat            = sql::getRows("SELECT * FROM `tickets_message` WHERE ticket_id = ? ORDER BY id DESC LIMIT 30", [$id]);
        $chat            = array_reverse($chat);
        $last_element    = end($chat);
        $last_element_id = $last_element['id'];
        $chat            = self::stackMessages($chat);
        foreach ($chat as &$ticket) {
            foreach ($ticket['messages'] as &$message) {
                if ($message['is_file']) {
                    $message['files'] = sql::getRows("SELECT `filename` FROM `tickets_file` WHERE message_id = ?", [$message['id']]);
                }
            }
        }

        tpl::addVar('chatMessage', $chat);
        tpl::addVar("id", $id);
        tpl::addVar('owner_last_message_author', end($chat)['user_id']);
        tpl::addVar("last_element_id", $last_element_id);
        tpl::addVar('lastTicketsList', $lastTicketsList);
        tpl::display("/admin/ticket_admin_chat.html");
    }

    //Загрузка последних сообщений

    public static function getNewMessage()
    {
        if ( ! \Ofey\Logan22\controller\config\config::load()->enabled()->isEnableTicket()) {
            redirect::location("/main");
        }

        $id              = $_POST['id'];
        $last_element_id = $_POST['last_element_id'];
        $chat            = sql::getRow("SELECT * FROM `tickets` WHERE id = ?", [$id]);
        if ($chat['last_message_id'] == $last_element_id) {
            board::alert([
              "new_message" => false,
            ]);
        }

        $chatMsgTicket = sql::getRows("SELECT * FROM `tickets_message` WHERE ticket_id = ? AND id > ?", [$id, $last_element_id]);

        $owner_last_message_author = end($chatMsgTicket)['user_id'] == user::self()->getId() ? "true" : "false";
        $stackMessages             = self::stackMessages($chatMsgTicket);

        foreach ($stackMessages as &$message) {
            $user                    = user::getUserId($message['user_id']);
            $message['ownerMessage'] = user::self()->getId() == $user->getId();
            $message['userInfo']     = [
              'email'  => $user->getEmail(),
              'name'   => $user->getName(),
              'avatar' => $user->getAvatar(),
              'id'     => $user->getId(),
            ];
        }

        foreach ($stackMessages as &$ticket) {
            foreach ($ticket['messages'] as &$message) {
                if ($message['is_file']) {
                    $message['files'] = sql::getRows("SELECT `filename` FROM `tickets_file` WHERE message_id = ?", [$message['id']]);
                }
            }
        }

        echo json_encode([
          "new_message"               => true,
          'chatMessage'               => $stackMessages,
          'last_element_id'           => $chat['last_message_id'],
          'owner_last_message_author' => $owner_last_message_author,
        ], JSON_UNESCAPED_UNICODE);
    }

    public static function message(): void
    {
        if ( ! \Ofey\Logan22\controller\config\config::load()->enabled()->isEnableTicket()) {
            redirect::location("/main");
        }

        if ( ! user::self()->isAdmin()) {
            $floodCheck = self::checkFloodLimits(user::self()->getId());
            if ( ! $floodCheck['canSendMessage']) {
                board::error($floodCheck['message']);
            }
        }

        $newLastElementId = ticketModel::AddMessage();

        if ( ! user::self()->isAdmin()) {
            self::updateUserMessageInfo(user::self()->getId());
        }

        echo json_encode([
          'last_element_id' => $newLastElementId,
        ]);
    }

    private static function checkFloodLimits($userId): array
    {
        $userVarData = sql::getRow("SELECT val FROM user_variables WHERE user_id = ? AND var = 'ticket_message_flood_control'", [$userId]);
        if ( ! $userVarData) {
            // Если записи нет, создаем новую
            $initialData = [
              'last_message_time'   => 0,
              'messages_per_minute' => 0,
              'minute_start_time'   => 0,
            ];
            sql::run(
              "INSERT INTO user_variables (user_id, var, val) VALUES (?, 'ticket_message_flood_control', ?)",
              [$userId, json_encode($initialData)]
            );
            $floodControlData = $initialData;
        } else {
            $floodControlData = json_decode($userVarData['val'], true);
        }

        $currentTime = time();

        if ($currentTime - $floodControlData['last_message_time'] < 1) {
            return ['canSendMessage' => false, 'message' => lang::get_phrase("wait_for_next_message")];
        }

        if ($currentTime - $floodControlData['minute_start_time'] > 60) {
            $floodControlData['messages_per_minute'] = 0;
            $floodControlData['minute_start_time']   = $currentTime;
        }
        $maxMessagesPerMinute = 15;
        if ($floodControlData['messages_per_minute'] >= $maxMessagesPerMinute) {
            return ['canSendMessage' => false, 'message' => lang::get_phrase('block_max_messages', $maxMessagesPerMinute)];
        }

        return ['canSendMessage' => true, 'message' => ''];
    }

    private static function updateUserMessageInfo($userId): void
    {
        $userVarData      = sql::getRow(
          "SELECT val FROM user_variables WHERE user_id = ? AND var = 'ticket_message_flood_control'",
          [$userId]
        );
        $floodControlData = json_decode($userVarData['val'], true);

        $currentTime = time();

        // Обновляем время последнего сообщения
        $floodControlData['last_message_time'] = $currentTime;

        // Обновляем счетчик сообщений в минуту
        if ($currentTime - $floodControlData['minute_start_time'] > 60) {
            $floodControlData['messages_per_minute'] = 1;
            $floodControlData['minute_start_time']   = $currentTime;
        } else {
            $floodControlData['messages_per_minute']++;
        }

        // Сохраняем обновленные данные
        sql::run(
          "UPDATE user_variables SET val = ?, date_update = NOW() WHERE user_id = ? AND var = 'ticket_message_flood_control'",
          [json_encode($floodControlData), $userId]
        );
    }

    //Блокировка / разблокировка пользователю тикет
    public static function blockTicket(): void
    {
        if ( ! user::self()->isAdmin()) {
            board::error(lang::get_phrase("access_is_denied"));
        }
        if ( ! isset($_POST['id'])) {
            board::error(lang::get_phrase("wrong_data"));
        }
        if ( ! isset($_POST['setClosed'])) {
            board::error(lang::get_phrase("wrong_data"));
        }
        $id        = $_POST['id'];
        $setClosed = $_POST['setClosed'] == 0 ? 1 : 0;
        sql::run("UPDATE tickets SET is_closed = ? WHERE id = ?", [$setClosed, $id]);
    }

    public static function clearDiaglog()
    {
        if ( ! user::self()->isAdmin()) {
            board::error(lang::get_phrase("access_is_denied"));
        }
        if ( ! isset($_POST['id'])) {
            board::error(lang::get_phrase("wrong_data"));
        }
        $id = $_POST['id'];

        ticketModel::clearTicketMessages($id);

        board::redirect("/ticket");
        board::success(lang::get_phrase("messages_deleted"));
    }

    //Асинхронный запрос на получение списка новых тикетов
    public static function getUpdateTicketList()
    {
        if ( ! user::self()->isAdmin()) {
            board::error(lang::get_phrase("access_is_denied"));
        }

        echo json_encode([
          'user_id'         => user::self()->getId(),
          'lastTicketsList' => ticketModel::lastTicketsList(),
        ], JSON_UNESCAPED_UNICODE);
    }

    public static function fileLoad(): void
    {
        if ( ! \Ofey\Logan22\controller\config\config::load()->enabled()->isEnableTicket()) {
            board::error(lang::get_phrase("disabled"));
        }

        if ( ! user::self()->isAdmin()) {
            $floodCheck = self::checkFloodLimits(user::self()->getId());
            if ( ! $floodCheck['canSendMessage']) {
                board::error($floodCheck['message']);
            }
        }

        $ticketId = $_POST['ticket_id'] ?? board::error(lang::get_phrase("wrong_data"));
        ticketModel::saveFile($ticketId);

        if ( ! user::self()->isAdmin()) {
            self::updateUserMessageInfo(user::self()->getId());
        }
    }

}