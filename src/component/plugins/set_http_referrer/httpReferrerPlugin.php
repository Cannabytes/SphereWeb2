<?php

namespace Ofey\Logan22\component\plugins\set_http_referrer;

use Ofey\Logan22\component\session\session;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\template\tpl;

class httpReferrerPlugin
{

    public function show()
    {
        validation::user_protection("admin");

        $row = sql::getRow("SELECT `data` FROM server_cache WHERE `type` = 'HTTP_REFERER_VIEWS';");
        $getReferrers = json_decode($row["data"], true);

        foreach ($getReferrers as &$getReferrer) {
            $getReferrer['count'] = count($getReferrer['count']);
            $getReferrer['user_count']    = sql::getRow("SELECT COUNT(*) AS count FROM `user_variables` WHERE `val` = ?", [$getReferrer['referer']])['count'] ?: 0;
        }

        $getViews     = $this->getView();
        foreach ($getReferrers as &$getReferrer) {
            foreach ($getViews as $getView) {
                if ($getReferrer['referer'] == $getView['referer']) {
                    $totalCount           = array_sum(array_values($getView['count']));
                    $getReferrer['views'] = $totalCount;
                }
            }
        }
        tpl::addVar([
          "getReferrers" => $getReferrers,
        ]);
        tpl::displayPlugin("/set_http_referrer/tpl/httpreferrer.html");
    }

    public function getView()
    {
        $data = sql::getRow("SELECT `data` FROM server_cache WHERE `type` = 'HTTP_REFERER_VIEWS';");
        if ($data) {
            return json_decode($data['data'], true);
        }

        return null;
    }

    public function get($referer_name, $dateStart = null, $dateEnd = null)
    {
        validation::user_protection("admin");
        $users    = $this->getRefererUsers($referer_name, $dateStart, $dateEnd);
        $getViews = $this->getView();
        $views    = 0;
        foreach ($getViews as $getView) {
            if ($getView['referer'] == $referer_name) {
                $views = array_sum(array_values($getView['count']));
            }
        }
        $user_ids     = array_column($users, "user_id");
        $user_ids_str = implode(',', $user_ids);
        $sql          = "SELECT SUM(point) AS total_points FROM donate_history_pay WHERE user_id IN ({$user_ids_str}) ;";
        $donateHists  = sql::getRow($sql);

        tpl::addVar("countAllDonate", $donateHists['total_points'] ?? 0);
        tpl::addVar("views", $views);
        tpl::addVar("users", $users);
        tpl::addVar("refererName", $referer_name);
        tpl::displayPlugin("/set_http_referrer/tpl/users.html");
    }

    private function getRefererUsers($referer_name, $dateStart = null, $dateEnd = null): array
    {
        if ($dateStart and $dateEnd) {
            if($dateStart == $dateEnd){
                return sql::getRows(
                  "SELECT `user_id`, `date_create` FROM `user_variables` WHERE `val` = ? AND DATE(`date_create`) = ?;",
                  [$referer_name, $dateStart]
                );
            }
            return sql::getRows(
              "SELECT `user_id`, `date_create` FROM `user_variables` WHERE `val` = ? AND `date_create` BETWEEN ? AND ?",
              [$referer_name, $dateStart, $dateEnd]
            );
        }
        return sql::getRows("SELECT `user_id`, `date_create` FROM `user_variables` WHERE `val` = ?", [$referer_name]);
    }

    static public function addReferer(): void {
        $refererName = $_SERVER['REQUEST_URI'];
        $refererName = ltrim($refererName, '/');
        session::domainViewsCounter($refererName);
        $_SESSION['HTTP_REFERER'] = $refererName;
        \Ofey\Logan22\component\redirect::location("/");
    }


    static public function addUserReferer(): void
    {
        $refererName = $_SERVER['REQUEST_URI'];
        $refererName = ltrim($refererName, '/');
        session::domainViewsCounter($refererName);
        $_SESSION['HTTP_REFERER'] = $refererName;
        \Ofey\Logan22\component\redirect::location("/signup/{$refererName}");
    }



}