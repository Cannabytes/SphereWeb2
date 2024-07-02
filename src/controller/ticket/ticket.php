<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/TrashWeb
 * Date: 17.04.2023 / 16:19:06
 */

namespace Ofey\Logan22\controller\ticket;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\config\config;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\controller\page\error;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\template\async;
use Ofey\Logan22\model\ticket\ticket_model;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class ticket {

    public static function all(): void {
        if(user::self()->isAdmin()){
        }else{
            $ticket = ticket_model::get_ticket_author(user::self()->getId());
            if(!$ticket){
                error::error404();
            }
            $messages = ticket_model::get_ticket_messages($ticket['id']);
            tpl::addVar("messages", $messages);
        }

        tpl::display("ticket.html");
    }

    public static function message()
    {

        $ticketId = $_POST['ticketId'];
        $userId = $_POST['userId'];
        $message = $_POST['message'];

        $ticket_id = 0;
        //Если это первое сообщение от пользователя, тогда регистрируем его
        $ticket_data = sql::sql('SELECT * FROM `ticket_data` WHERE id = ?', [$ticketId])->fetch();
        if(!$ticket_data){
            sql::run("INSERT INTO `ticket_data` ( `user_id`, `date`, `last_message_id`) VALUES (?, ?, 0)", [
                $userId
            ]);
            $ticket_id = sql::lastInsertId();
        }else{
            $ticket_id = $ticket_data['id'];
        }
        sql::run("INSERT INTO `ticket_messages` (`ticket_id`, `user_id`, `message`, `date`) VALUES (?, ?, ?, ?)", [
            $ticket_id,
           $userId,
           $message,
           time::mysql(),
        ]);
        $async = new async("ticket.html");
        $async->block("main-content", "ticket_chat_content", "update", true);
        $async->block("title", "title");
        $async->send();
    }

    public static function get($id) {
        $messages = ticket_model::get_ticket_messages($id);
        tpl::addVar("ticketId", $id);
        tpl::addVar("messages", $messages);
        tpl::display("ticket.html");
        exit;

        //
        if(!config::getEnableTicket()) error::error404("Disabled");
        $ticket = ticket_model::get_info($id);
        if(!$ticket){
            error::error404();
        }
        if($ticket['private'] and auth::get_id() != $ticket['user_id'] and auth::get_access_level() != "admin"){
            error::error404("Тикет скрыт настройками приватности");
        }
        if($ticket === false) {
            error::error404();
        }
        tpl::addVar("ticket", $ticket);
        tpl::display("ticket/read.html");
    }

    public static function search($word = ""): void {
        if(!config::getEnableTicket()) error::error404("Disabled");
        $error = "";
        if(empty($word)) {
            $error = lang::get_phrase(343);
        } elseif(mb_strlen($word) < 3) {
            $error = lang::get_phrase(344);
        } elseif(mb_strlen($word) > 50) {
            $error = lang::get_phrase(345);
        }
        $founds = sql::getRows("SELECT `id`, `user_id`, SUBSTR(`content`, LEAST(50, GREATEST(LOCATE(?, `content`) - 50, 1)), 100) AS `content`, `date`, `close` FROM `tickets` WHERE MATCH (`content`) AGAINST (? IN BOOLEAN MODE);", [
            $word,
            $word,
        ]);
        tpl::addVar("error", $error);
        tpl::addVar("founds", $founds);
        tpl::addVar("word", $word);
        tpl::display("ticket/found.html");
    }

    public static function create(): void {
        if(!config::getEnableTicket()) error::error404("Disabled");
        if(auth::get_ban_ticket()) board::notice(false, "You are not allowed to do this");
        validation::user_protection();
        tpl::display("ticket/create.html");
    }

    public static function edit($ticket_id, $comment_id = null): void {
        if(!config::getEnableTicket()) error::error404("Disabled");
        if(auth::get_ban_ticket()) board::notice(false, "You are not allowed to do this");
        validation::user_protection();
        $ticket = ticket_model::get_ticket($ticket_id);
        if($ticket == null) {
            redirect::location("/ticket");
        }

        tpl::addVar("ticket", ticket_model::get_info($ticket_id, false));
        if($comment_id != null) {

            $comment = ticket_model::get_comment($ticket_id, $comment_id);
            if($comment['user_id'] != auth::get_id() AND auth::get_access_level()!="admin") {
                error::error404("Страница Вам недоступна.");
            }

            tpl::addVar("comment", $comment);
            tpl::display("ticket/editcomment.html");
        }
        tpl::display("ticket/editticket.html");
    }

    public static function removeImage() {
        if(!config::getEnableTicket()) error::error404("Disabled");
        validation::user_protection();
        ticket_model::removeImage();
    }

    public static function add(): void {
        if(!config::getEnableTicket()) error::error404("Disabled");
        if(auth::get_ban_ticket()) board::notice(false, "You are not allowed to do this");
        validation::user_protection();
        ticket_model::add();
    }

    public static function addComment(): void {
        if(!config::getEnableTicket()) error::error404("Disabled");
        if(auth::get_ban_ticket()) board::notice(false, "You are not allowed to do this");
        validation::user_protection();
        ticket_model::addComment();
    }

    public static function close(): void {
        if(!config::getEnableTicket()) error::error404("Disabled");
        if(auth::get_ban_ticket()) board::notice(false, "You are not allowed to do this");
        validation::user_protection();
        $ticket_id = $_POST['ticketID'] ?? null;
        if($ticket_id === null) {
            board::notice(false, lang::get_phrase(347));
        }
        $ticket = sql::getRow("SELECT `user_id`, `close` FROM `tickets` WHERE id=? LIMIT 1", [$ticket_id]);
        if($ticket['user_id'] == auth::get_id() or auth::get_access_level() == "admin") {
            sql::run("UPDATE `tickets` SET `close` = 1 WHERE `id` = ?", [$ticket_id]);
            board::notice(true);
        } else {
            board::notice(false, lang::get_phrase(346));
        }
    }

    public static function open(): void {
        if(!config::getEnableTicket()) error::error404("Disabled");
        validation::user_protection();
        $ticket_id = $_POST['ticketID'] ?? null;
        if($ticket_id === null) {
            board::notice(false, lang::get_phrase(347));
        }
        $ticket = sql::getRow("SELECT `user_id`, `close` FROM `tickets` WHERE id=? LIMIT 1", [$ticket_id]);
        if($ticket['user_id'] == auth::get_id() or auth::get_access_level() == "admin") {
            sql::run("UPDATE `tickets` SET `close` = 0 WHERE `id` = ?", [$ticket_id]);
            board::notice(true);
        } else {
            board::notice(false, lang::get_phrase(348));
        }
    }

    public static function editComment(): void {
        if(!config::getEnableTicket()) error::error404("Disabled");
        if(auth::get_ban_ticket()) board::notice(false, "You are not allowed to do this");
        validation::user_protection();
        ticket_model::editComment();
    }

    public static function editTicket(): void {
        if(!config::getEnableTicket()) error::error404("Disabled");
        if(auth::get_ban_ticket()) board::notice(false, "You are not allowed to do this");
        validation::user_protection();
        ticket_model::editTicket();
    }


    public static function remove(): void {
        if(!config::getEnableTicket()) error::error404("Disabled");
        if(auth::get_ban_ticket()) board::notice(false, "You are not allowed to do this");
        validation::user_protection("admin");
        $ticketID = $_POST['ticketID'] ?? null;
        if(is_numeric($ticketID)){
           if(ticket_model::remove($ticketID)){
               board::alert([
                   "ok" => true,
                   "message"=> lang::get_phrase(146),
               ]);
           }
        }
    }

    public static function removeComment(): void {
        if(!config::getEnableTicket()) error::error404("Disabled");
        if(auth::get_ban_ticket()) board::notice(false, "You are not allowed to do this");
        validation::user_protection("admin");
        $ticketID = $_POST['commentID'] ?? null;
        if(is_numeric($ticketID)){
          if (ticket_model::removeComment($ticketID) ){
              board::alert([
                  "ok" => true,
                  "message"=> lang::get_phrase(146),
              ]);
          }
        }
    }
}