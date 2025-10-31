<?php

namespace Ofey\Logan22\component\plugins\set_http_referrer;

use Ofey\Logan22\component\session\session;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\template\tpl;

class httpReferrerPlugin
{

    public function show(): void
    {
        validation::user_protection("admin");

        $row = sql::getRow("SELECT `data` FROM server_cache WHERE `type` = 'HTTP_REFERER_VIEWS';");

        if (!$row || empty($row["data"])) {
            tpl::addVar([
                "getReferrers" => [],
                "dailyStats" => json_encode([]),
                "dailyStatsBySources" => json_encode([]),
                "dateRange" => [],
                "totalStats" => [
                    'total_views' => 0,
                    'total_users' => 0,
                    'total_donations' => 0,
                    'unique_sources' => 0,
                ]
            ]);
            tpl::displayPlugin("/set_http_referrer/tpl/httpreferrer.html");
            return;
        }

        $rawReferrers = json_decode($row["data"], true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($rawReferrers)) {
            tpl::addVar([
                "getReferrers" => [],
                "dailyStats" => json_encode([]),
                "dailyStatsBySources" => json_encode([]),
                "dateRange" => [],
                "totalStats" => [
                    'total_views' => 0,
                    'total_users' => 0,
                    'total_donations' => 0,
                    'unique_sources' => 0,
                ]
            ]);
            tpl::displayPlugin("/set_http_referrer/tpl/httpreferrer.html");
            return;
        }

        $processedReferrerDetails = [];
        $dailyStatsGlobal = []; // Глобальная статистика по дням
        $dateRange = [];

        // 1. Обрабатываем каждую запись реферера, получаем данные из SQL
        foreach ($rawReferrers as $rawReferrerEntry) {
            if (!isset($rawReferrerEntry['referer']) || !isset($rawReferrerEntry['count'])) {
                continue;
            }

            $originalRefererUrl = $rawReferrerEntry['referer'];

            $host = parse_url($originalRefererUrl, PHP_URL_HOST);
            if (empty($host) && !preg_match('#^https?://#i', $originalRefererUrl)) {
                $host = parse_url('http://' . $originalRefererUrl, PHP_URL_HOST);
            }

            if (!$host || !filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                continue;
            }

            if ($host === "api.sphereweb.com") {
                continue;
            }

            $currentCount = is_array($rawReferrerEntry['count']) ? count($rawReferrerEntry['count']) : (int)$rawReferrerEntry['count'];

            // Собираем статистику по дням для каждого реферера
            if (is_array($rawReferrerEntry['count'])) {
                foreach ($rawReferrerEntry['count'] as $date => $count) {
                    if (!isset($dailyStatsGlobal[$date])) {
                        $dailyStatsGlobal[$date] = 0;
                    }
                    $dailyStatsGlobal[$date] += $count;
                    $dateRange[] = $date;
                }
            }

            $sqlData = sql::getRows(
                "SELECT (SELECT COUNT(DISTINCT user_id) FROM user_variables WHERE var = 'HTTP_REFERER' AND val = ?) AS total_users,
                    SUM(dhp.point) AS total_points FROM donate_history_pay dhp
                WHERE dhp.user_id IN ( SELECT `user_id` FROM `user_variables` WHERE `var` = 'HTTP_REFERER' AND `val` = ? );",
                [
                    $originalRefererUrl,
                    $originalRefererUrl,
                ]
            );

            $processedReferrerDetails[] = [
                'domain'          => $host,
                'count'           => $currentCount,
                'user_count'      => $sqlData[0]['total_users'] ?? 0,
                'total_donations' => $sqlData[0]['total_points'] ?? 0,
                'daily_data'      => is_array($rawReferrerEntry['count']) ? $rawReferrerEntry['count'] : [],
            ];
        }

        $aggregatedDataByDomain = [];
        foreach ($processedReferrerDetails as $detail) {
            $domain = $detail['domain'];
            if (!isset($aggregatedDataByDomain[$domain])) {
                $aggregatedDataByDomain[$domain] = [
                    'referer'         => $domain,
                    'count'           => 0,
                    'user_count'      => 0,
                    'total_donations' => 0,
                    'views'           => 0,
                    'daily_data'      => [],
                ];
            }
            $aggregatedDataByDomain[$domain]['count']           += $detail['count'];
            $aggregatedDataByDomain[$domain]['user_count']      += $detail['user_count'];
            $aggregatedDataByDomain[$domain]['total_donations'] += $detail['total_donations'];

            // Объединяем daily_data
            foreach ($detail['daily_data'] as $date => $count) {
                if (!isset($aggregatedDataByDomain[$domain]['daily_data'][$date])) {
                    $aggregatedDataByDomain[$domain]['daily_data'][$date] = 0;
                }
                $aggregatedDataByDomain[$domain]['daily_data'][$date] += $count;
            }
        }

        $rawViews = $this->getView();
        $aggregatedViewsByDomain = [];

        if (is_array($rawViews)) {
            foreach ($rawViews as $viewEntry) {
                if (!isset($viewEntry['referer']) || !isset($viewEntry['count'])) {
                    continue;
                }

                $viewRefererUrl = $viewEntry['referer'];

                $host = parse_url($viewRefererUrl, PHP_URL_HOST);
                if (empty($host) && !preg_match('#^https?://#i', $viewRefererUrl)) {
                    $host = parse_url('http://' . $viewRefererUrl, PHP_URL_HOST);
                }

                if (!$host || !filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                    continue;
                }

                if ($host === "api.sphereweb.com") {
                    continue;
                }

                $totalCountForView = 0;
                if (is_array($viewEntry['count'])) {
                    $totalCountForView = array_sum(array_values($viewEntry['count']));
                } elseif (is_numeric($viewEntry['count'])) {
                    $totalCountForView = (int)$viewEntry['count'];
                }

                if (!isset($aggregatedViewsByDomain[$host])) {
                    $aggregatedViewsByDomain[$host] = 0;
                }
                $aggregatedViewsByDomain[$host] += $totalCountForView;
            }
        }

        // Вычисляем дополнительные метрики
        $totalViews = 0;
        $totalUsers = 0;
        $totalDonations = 0;

        foreach ($aggregatedDataByDomain as $domain => &$data) {
            if (isset($aggregatedViewsByDomain[$domain])) {
                $data['views'] = $aggregatedViewsByDomain[$domain];
            }
            
            // Вычисляем conversion rate (регистраций / просмотров)
            $data['conversion_rate'] = $data['views'] > 0 ? round(($data['user_count'] / $data['views']) * 100, 2) : 0;
            
            // Средний донат на пользователя
            $data['avg_donation'] = $data['user_count'] > 0 ? round($data['total_donations'] / $data['user_count'], 2) : 0;
            
            // Считаем общую статистику
            $totalViews += $data['views'];
            $totalUsers += $data['user_count'];
            $totalDonations += $data['total_donations'];
        }
        unset($data);

        // Сортируем по просмотрам
        usort($aggregatedDataByDomain, function($a, $b) {
            return $b['views'] - $a['views'];
        });

        $finalReferrers = array_values($aggregatedDataByDomain);

        // Подготовка данных для графика по дням
        ksort($dailyStatsGlobal);
        $dateRange = array_unique($dateRange);
        sort($dateRange);

        // Подготовка данных по источникам для графика (топ-10)
        $dailyStatsBySources = [];
        $topSources = array_slice($finalReferrers, 0, 10);
        
        foreach ($topSources as $source) {
            $dailyStatsBySources[$source['referer']] = [];
            if (isset($source['daily_data']) && is_array($source['daily_data'])) {
                foreach ($source['daily_data'] as $date => $count) {
                    $dailyStatsBySources[$source['referer']][$date] = $count;
                }
            }
        }

        tpl::addVar([
            "getReferrers" => $finalReferrers,
            "dailyStats" => json_encode($dailyStatsGlobal),
            "dailyStatsBySources" => json_encode($dailyStatsBySources),
            "dateRange" => $dateRange,
            "totalStats" => [
                'total_views' => $totalViews,
                'total_users' => $totalUsers,
                'total_donations' => $totalDonations,
                'unique_sources' => count($aggregatedDataByDomain),
                'avg_conversion' => $totalViews > 0 ? round(($totalUsers / $totalViews) * 100, 2) : 0,
            ]
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

        if($user_ids == []){
            tpl::addVar("countAllDonate", 0);
        }else{
            $user_ids_str = implode(',', $user_ids);
            $sql          = "SELECT SUM(point) AS total_points FROM donate_history_pay WHERE user_id IN ({$user_ids_str}) ;";
            $donateHists  = sql::getRow($sql);
            tpl::addVar("countAllDonate", $donateHists['total_points'] ?? 0);
        }

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