<?php

namespace Ofey\Logan22\model\ticket;

use Intervention\Image\ImageManager;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;

class ticketModel
{

    //Список последних тикетов
    public static function lastTicketsList(): array
    {
        $lastTicketsList = sql::getRows(
          "SELECT
                      tickets.id, 
                      tickets.user_id, 
                      tickets.is_closed, 
                      LEFT ( tickets_message.message, 40 ) AS message, 
                      tickets_message.`date`, 
                      tickets_message.user_id AS last_author_id, 
                      tickets_message.`read`, 
                      ( SELECT COUNT(*) FROM tickets_message WHERE tickets_message.ticket_id = tickets.id AND tickets_message.`read` = 0 ) AS unread_count, 
                      users.`name`, 
                      users.avatar, 
                      tickets_message.is_file
                    FROM
                      tickets
                      INNER JOIN
                      tickets_message
                      ON 
                        tickets.last_message_id = tickets_message.id
                      INNER JOIN
                      users
                      ON 
                        tickets.user_id = users.id
                    ORDER BY
                      tickets.id DESC
                    LIMIT 100;"
        );

        foreach ($lastTicketsList as &$ticket) {
            $ticket['avatar'] = user::getUserId($ticket['user_id'])->getAvatar();
        }

        return $lastTicketsList;
    }

    static public function getTicketInfo($id){
        return sql::getRow("SELECT * FROM `tickets` WHERE id = ?", [$id]);
    }

    static public function saveFile($ticketId){

        //Проверяем что тикет пренадлежит пользователю
        $ticketData = ticketModel::getTicketInfo($ticketId);
        if(!$ticketData){
            return;
        }

        if($ticketData['user_id'] != user::self()->getId() AND !user::self()->isAdmin()){
            board::error(lang::get_phrase("access_is_denied"));
            return;
        }

        if (isset($_FILES['filepond']) && $_FILES['filepond']['error'] == 0) {
            $manager = ImageManager::gd();
            $file    = $_FILES['filepond']['tmp_name'];
            $image   = $manager->read($file);

            // Изменение размера изображения
            $image->resize(1600, 1200, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $imageTicket = uniqid() . '.png';

            // Проверка существования папки
            if ( ! file_exists('uploads/ticket')) {
                $mkdir = mkdir('uploads/ticket', 0777, true);
            }

            $success = $image->save('uploads/ticket/' . $imageTicket);

            if ($success) {
                sql::run(
                  "INSERT INTO tickets_message (ticket_id, user_id, is_file, message, date) VALUES (?, ?, ?, ?, ?)",
                  [$ticketId, user::self()->getId(), 1, '', time::mysql()]
                );
                $newLastElementId = sql::lastInsertId();

                sql::run("INSERT INTO `tickets_file` (`ticket_id`, `message_id`, `filename`, `user_id`) VALUES (?, ?, ?, ?)", [
                  $ticketId,
                  $newLastElementId,
                  $imageTicket,
                  user::self()->getId(),
                ]);

                sql::run(
                  "UPDATE tickets SET last_message_id = ?, last_user_id = ? WHERE id = ?",
                  [$newLastElementId, user::self()->getId(), $ticketId]
                );
            }

            if ($success) {
                echo json_encode([
                  'status'  => 'success',
                  'message' => 'Image uploaded, resized and processed successfully',
                  'user_id' => user::self()->getId(),
                  'avatar'  => user::self()->getAvatar(),
                  'name'    => user::self()->getName(),
                  'path'    => $imageTicket,
                ]);
            }else{
                echo json_encode([
                  'status'  => 'error',
                  'message' => 'Error uploading image',
                ]);
            }
        }
    }

    static public function AddMessage(){

        $message         = $_POST['message'] ?? board::error("Empty message");
        $ticketId        = $_POST['id'];
        $last_element_id = $_POST['last_element_id'];

        $ticket = null;
        if ($ticketId != 0) {
            $ticket = sql::getRow("SELECT * FROM tickets WHERE id = ?", [$ticketId]);
            if ($ticket['is_closed'] and ! user::self()->isAdmin()) {
                board::error(lang::get_phrase("block_write_to_ticket"));
            }
        }
        if ($ticket === null) {
            sql::run(
              "INSERT INTO tickets (user_id, last_user_id) VALUES (?, ?)",
              [user::self()->getId(), user::self()->getId()]
            );
            $ticketId = sql::lastInsertId();
        }

        sql::run(
          "INSERT INTO tickets_message (ticket_id, user_id, message, date) VALUES (?, ?, ?, ?)",
          [$ticketId, user::self()->getId(), $message, time::mysql()]
        );
        $newLastElementId = sql::lastInsertId();
        sql::run(
          "UPDATE tickets SET last_message_id = ?, last_user_id = ? WHERE id = ?",
          [$newLastElementId, user::self()->getId(), $ticketId]
        );
        return $newLastElementId;
    }

    static public function clearTicketMessages($id): void
    {
        sql::run("DELETE FROM tickets WHERE id = ?", [$id]);
        sql::run("DELETE FROM tickets_message WHERE ticket_id = ?", [$id]);
        sql::run("DELETE FROM tickets_file WHERE ticket_id = ?", [$id]);
    }

}