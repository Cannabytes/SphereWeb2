<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\statistic\statistic as statistic_model;
use Ofey\Logan22\template\tpl;
use Ofey\Logan22\model\log\logTypes;

class userlog {

    public static function getServerLog($sort = null, $serverId = null) {
        validation::user_protection("admin");

        if ($serverId === null) {
            return false;
        }

        $params = [$serverId];

        if ($sort === null || strtolower($sort) == 'all') {
            $serverLog = sql::getRows("SELECT
            logs_all.*,
            COALESCE(users.avatar, 'none.jpeg') AS `avatar`,
            COALESCE(users.name, '-') AS `name`,
            COALESCE(users.email, '-') AS `email` 
        FROM logs_all
        LEFT JOIN users ON logs_all.user_id = users.id
        WHERE server_id = ?
        ORDER BY logs_all.id DESC LIMIT 50", $params);
        } else {
            $params[] = strtolower($sort);
            $serverLog = sql::getRows("SELECT
            logs_all.*,
            COALESCE(users.avatar, 'none.jpeg') AS `avatar`,
            COALESCE(users.name, '-') AS `name`,
            COALESCE(users.email, '-') AS `email` 
        FROM logs_all
        LEFT JOIN users ON logs_all.user_id = users.id
        WHERE server_id = ? AND type = ?
        ORDER BY logs_all.id DESC LIMIT 50", $params);
        }

        foreach ($serverLog as &$log) {
            $s = json_decode($log['variables']);
            $values = is_array($s) ? array_values($s) : [$s];
            $log['message'] = lang::get_phrase($log['phrase'], ...$values);
        }

        $logs_type = sql::getRows('SELECT DISTINCT type FROM logs_all WHERE server_id = ?', [$serverId]);

        tpl::addVar('total_pages', sql::getRow('SELECT CEIL(COUNT(*) / 50) AS total_pages FROM logs_all WHERE server_id = ?', [$serverId])['total_pages']);
        tpl::addVar('sort_type', $sort);
        tpl::addVar('logs_type', $logs_type);
        tpl::addVar('server_id', $serverId);
        tpl::addVar("server_logs", $serverLog);
        tpl::display("admin/logs/server.html");
    }

    public static function all($sort = null) {
        validation::user_protection("admin");
        if($sort == null OR strtolower($sort) == 'all'){
            $allLog = sql::getRows("SELECT
			logs_all.*,
			COALESCE(users.avatar, 'none.jpeg') AS `avatar`,
			COALESCE(users.name, '-') AS `name`,
			COALESCE(users.email, '-') AS `email` 
		FROM logs_all
		LEFT JOIN users ON logs_all.user_id = users.id
		ORDER BY logs_all.id DESC LIMIT 50");
        }else{
            $allLog = sql::getRows("SELECT
			logs_all.*,
			COALESCE(users.avatar, 'none.jpeg') AS `avatar`,
			COALESCE(users.name, '-') AS `name`,
			COALESCE(users.email, '-') AS `email` 
		FROM logs_all
		LEFT JOIN users ON logs_all.user_id = users.id
		WHERE type=?
		ORDER BY logs_all.id DESC LIMIT 50", [strtolower($sort)]);
        }
        foreach($allLog AS &$log){
            $s = json_decode($log['variables']);
            $values = is_array($s) ? array_values($s) : [$s];
            $log['message'] = lang::get_phrase($log['phrase'], ...$values);
        }
        $logs_type = sql::getRows('SELECT DISTINCT type FROM logs_all;');
        tpl::addVar('total_pages', sql::getRow('SELECT CEIL(COUNT(*) / 50) AS total_pages FROM logs_all;')['total_pages']);
        tpl::addVar('sort_type', $sort);
        tpl::addVar('logs_type', $logs_type);
        tpl::addVar("logs_all", $allLog);
        tpl::display("admin/logs/all.html");
    }

    public static function get_new_message(){
        validation::user_protection("admin");
        $maxObjectId = $_POST['maxObjectId'] ?? false;
        $sort = $_POST['getSort'] ?? null;
        if(!$maxObjectId){
            exit(json_encode([]));
        }
        if($sort == null OR strtolower($sort) == 'all'){
            $sql = "SELECT
                    logs_all.*,
                    COALESCE(users.avatar, 'none.jpeg') AS `avatar`,
                    COALESCE(users.name, '-') AS `name`,
                    COALESCE(users.email, '-') AS `email` 
                FROM 
                    logs_all
                LEFT JOIN 
                    users ON logs_all.user_id = users.id
                WHERE 
                    logs_all.id > ?
                ORDER BY 
                    logs_all.id DESC ;";
            $last_logs = sql::getRows($sql, [$maxObjectId]);
        }else{
            $sql = "SELECT
                logs_all.*,
                COALESCE(users.avatar, 'none.jpeg') AS `avatar`,
                COALESCE(users.name, '-') AS `name`,
                COALESCE(users.email, '-') AS `email` 
            FROM logs_all
            LEFT JOIN users ON logs_all.user_id = users.id
            WHERE logs_all.id > ? AND type = ? 
            ORDER BY logs_all.id DESC LIMIT 50";
            $last_logs = sql::getRows($sql, [$maxObjectId, strtolower($sort)]);
        }
        if(!$last_logs){
            exit(json_encode([]));
        }
        foreach($last_logs AS &$log){
            $avatar = $log['avatar'];
            if (mb_substr($avatar, 0, 5) == "user_") {
                $avatar = "thumb_" . $avatar;
            }
            $log['avatar'] = fileSys::localdir(sprintf("/uploads/avatar/%s", $avatar));
            $s = json_decode($log['variables']);
            $values = is_array($s) ? array_values($s) : [$s];
            $log['message'] = lang::get_phrase($log['phrase'], ...$values);
            $log['time'] = statistic_model::timeHasPassed(time() - strtotime($log['time']), true);
        }
        echo json_encode($last_logs);
    }

    public static function message_logs() {
        validation::user_protection("admin");
        $page = $_POST['page'] ?? 1;
        $sort = $_POST['getSort'] ?? 'all';
        $limit = 20;
        $start = ($page != 1) ? ($page - 1) * $limit : 0;

        if($sort == null OR strtolower($sort) == 'all'){
            $logs_all = sql::getRows("SELECT
                    logs_all.*,
                    COALESCE(users.avatar, 'none.jpeg') AS `avatar`,
                    COALESCE(users.name, '-') AS `name`,
                    COALESCE(users.email, '-') AS `email` 
                FROM 
                    logs_all
                LEFT JOIN 
                    users ON logs_all.user_id = users.id
                ORDER BY 
                    logs_all.id DESC LIMIT ?, ?;", [$start, $limit]);
        }else{
            $sql = "SELECT
                logs_all.*,
                COALESCE(users.avatar, 'none.jpeg') AS `avatar`,
                COALESCE(users.name, '-') AS `name`,
                COALESCE(users.email, '-') AS `email` 
            FROM logs_all
            LEFT JOIN users ON logs_all.user_id = users.id
            WHERE type = ? 
            ORDER BY logs_all.id DESC LIMIT ?, ?";
            $logs_all = sql::getRows($sql, [strtolower($sort), $start, $limit]);
        }

        if(!$logs_all){
            exit(json_encode([]));
        }
        foreach($logs_all AS &$log){
            $avatar = $log['avatar'];
            if (mb_substr($avatar, 0, 5) == "user_") {
                $avatar = "thumb_" . $avatar;
            }
            $log['avatar'] = fileSys::localdir(sprintf("/uploads/avatar/%s", $avatar));
            $s = json_decode($log['variables']);
            $values = is_array($s) ? array_values($s) : [$s];
            $log['message'] = lang::get_phrase($log['phrase'], ...$values);
            $log['time'] = statistic_model::timeHasPassed(time() - strtotime($log['time']), true);
        }
        echo json_encode($logs_all);
    }

    public static function get_last_log($user_id, logTypes $type, int $limit = 1) {
        if ($limit == 1) {
            return sql::getRow("SELECT * FROM logs_all WHERE user_id = ? AND type = ? ORDER BY id DESC LIMIT 1", [$user_id, $type->value]);
        } else {
            return sql::getRows("SELECT * FROM logs_all WHERE user_id = ? AND type = ? ORDER BY id DESC LIMIT ?", [$user_id, $type->value, $limit]);
        }
    }

}