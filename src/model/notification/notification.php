<?php

namespace Ofey\Logan22\model\notification;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\lang\lang;
use Ofey\Logan22\model\statistic\statistic;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\user;

use function Symfony\Component\String\s;

class notification {

    public static function add($user_id, $phrase, $link): void {
        $sql = "INSERT INTO `notification` (`user_id`, `phrase`, `link`, `date`) VALUES (?, ?, ?, ?)";
        $params = [$user_id, $phrase, $link, date('Y-m-d H:i:s')];
        sql::run($sql, $params);
    }

    private static ?array $notification = null;
    public static function get_self_notification(): array
    {
        if (self::$notification !== null) {
            return self::$notification;
        }
        $sql = "SELECT * FROM `notification` WHERE `user_id` = ? AND `read` = 0 ORDER BY `date` DESC LIMIT 4";
        self::$notification = sql::getRows($sql, [user::self()->getId()]);
        return self::$notification;
    }

    public static function get_new_notification(): void
    {
        if (self::$notification !== null) {
            echo json_encode(['notifications' =>self::$notification, 'id' => $_SESSION['notification']],  JSON_UNESCAPED_UNICODE);
            return;
        }
        $sql = "SELECT * FROM `notification` WHERE `read` = 0 ORDER BY `id` ASC";
        self::$notification = sql::getRows($sql, []);
        foreach (self::$notification as &$notification) {
            $notification['phrase'] = \Ofey\Logan22\component\lang\lang::get_phrase($notification['phrase']);
        }
        $_SESSION['notification'] = end(self::$notification)['id'];
        echo json_encode(['notifications' =>self::$notification, 'id' => $_SESSION['notification']],  JSON_UNESCAPED_UNICODE);
    }

    public static function notification_mark_read(): void {
        $sql = "UPDATE `notification` SET `read` = 1 WHERE `user_id` = ?";
        $i = sql::run($sql, [auth::get_id()]);
        if($i->rowCount()>0){
            board::success("Notification marked as read");
        }else{
            board::error("No notification to mark as read");
        }
    }

    public static function toAdmin($phrase, $link){
        $admins = sql::getRows("SELECT * FROM `users` WHERE `access_level` = 'admin' OR 'moderator';");
        foreach ($admins as $admin){
            self::add($admin['id'], $phrase, $link);
        }
    }

}