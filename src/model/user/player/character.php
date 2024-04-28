<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 26.08.2022 / 20:33:18
 */

namespace Ofey\Logan22\model\user\player;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\cache\cache;
use Ofey\Logan22\component\cache\dir;
use Ofey\Logan22\component\image\crest;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\restapi\restapi;
use Ofey\Logan22\controller\statistic\statistic;
use Ofey\Logan22\model\db\sdb;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\user;

class character {

    private static array $is_forbidden_array = [];
    private static array $is_not_find = [];

    public static function is_forbidden(&$charnames, $char_name_table) {
        $player = $charnames[$char_name_table];
        $row = sql::getRow("SELECT `forbidden` FROM `player_forbidden` WHERE `player` = ?", [$player]);
        $charnames['player_forbidden'] = $row['forbidden'];
    }


    //возвращает всех персонажей пользователя

    /**
     * @param string $email
     * @param int $serverId
     * @return player[]|null
     */
    public static function get_account_players(string $email, int $serverId): ?array {
        $player_accounts = sql::getRows("SELECT id, login, `password`, email, ip, server_id, password_hide, date_create, date_update FROM player_accounts WHERE email = ? ORDER BY date_create", [
            $email,
        ]);
        if (!$player_accounts) {
            return null;
        }
        $players = [];
        foreach ($player_accounts as &$account) {
            $player = new player();
            $player->setId($account['id']);
            $player->setEmail($account['email']);
            $player->setAccount($account['login']);
            $player->setPassword($account['password']);
            $player->setPasswordHide($account['password_hide']);
            $player->setServerId($serverId);
            $login = $account['login'];
            $characters = character::all_characters($login, $serverId);

            foreach($characters AS $character){
                $char = new characters();
                $char->setPlayerId($character['player_id']);
                $char->setAccountName($character['account_name']);
                $char->setPlayerName($character['player_name']);
                $char->setLevel($character['level']);
                $char->setClassId($character['class_id']);
                $char->setOnline($character['online']);
                $char->setPvp($character['pvp']);
                $char->setPk($character['pk']);
                $char->setSex($character['sex']);
                $char->setClanId($character['clanid'] ?? null);
                $char->setClanName($character['clan_name']);
                $char->setTitle($character['title']);
                $char->setTimeInGame($character['time_in_game']);
                $char->setClanCrest($character['clan_crest']);
                $char->setAllianceCrest($character['alliance_crest']);
                $char->setIsBase($character['isBase']);
                $char->setCreateTime($character['createtime']);

                $player->setCharacters($char);
            }
            $players[] = $player;
            if (sdb::is_error()){
                return null;
            }
        }
        return $players;
    }

    public static function all_characters($login, $server_id = 0) {
        if ($server_id == 0) {
            $server_id = user::self()->getServerId();
        }
        $cache = cache::read(dir::characters->show_dynamic($server_id, $login), second: 60);
        if ($cache)
            return $cache;
        if (server::getServer($server_id)->getRestApiEnable()) {
            $data = restapi::Send(
                server::getServer($server_id),
                "account_players",
                $login,
            );
            if ($data == "false") {
                return false;
            }
            $players = json_decode($data, true);
        } else {
            $players = player_account::extracted("account_players", server::getServer($server_id), [$login], false);
            if (sdb::is_error()){
                return [];
            }
            if ($players != null) {
                $players = $players->fetchAll();
            } else {
                $players = [];
            }
        }
        if ($players != null) {
            crest::conversion($players, rest_api_enable: server::getServer($server_id)->getRestApiEnable());
        } else {
            $players = [];
        }
        cache::save(dir::characters->show_dynamic($server_id, $login), $players);
        return $players;
    }

    public static function get_characters($login, $server_id) {
        $info = server::get_server_info($server_id);
        if (!$info) {
            return false;
        }
        $players = player_account::extracted("account_players", $info, [$login]);
        $players = $players->fetchAll();
        crest::conversion($players, rest_api_enable: $info['rest_api_enable']);
        return $players;
    }

    public static function get_player($char_name, $server_id) {
        $server_info = server::get_server_info($server_id);
        if (!$server_info) {
            board::notice(false, lang::get_phrase(150));
        }
        $reQuest = server::db_info_id($server_info['db_id']);
        $my_chars = self::player($reQuest, [$char_name]);
        $user_characters = $my_chars->fetch();
        crest::conversion($user_characters, rest_api_enable: $server_info['rest_api_enable']);
        return $user_characters;
    }

    private static function player($info, $prepare) {
        return player_account::extracted("is_characters_name", $info, $prepare);
    }

    public static function get_items($login, $server_id) {
        $server_info = server::get_server_info($server_id);
        if (!$server_info) {
            board::notice(false, lang::get_phrase(150));
        }
        $reQuest = server::db_info_id($server_info['db_id']);
        $items = self::items($reQuest, [$login]);
        return $items->fetchAll();
    }

    private static function items($info, $prepare) {
        return player_account::extracted("all_player_items", $info, $prepare);
    }

    public static function get_subclasses($char_id, $server_id) {
        $server_info = server::get_server_info($server_id);
        if (!$server_info) {
            board::notice(false, lang::get_phrase(150));
        }
        $reQuest = server::db_info_id($server_info['db_id']);
        $subclasses = self::subclasses($reQuest, [$char_id]);
        return $subclasses->fetchAll();
    }

    private static function subclasses($info, $prepare) {
        return player_account::extracted("player_subclasses", $info, $prepare);
    }
}