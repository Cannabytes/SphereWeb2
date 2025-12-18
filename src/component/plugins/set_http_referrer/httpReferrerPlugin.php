<?php

namespace Ofey\Logan22\component\plugins\set_http_referrer;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\session\session;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\template\tpl;

class httpReferrerPlugin
{

    /**
     * Миграция данных из `server_cache` (поле `HTTP_REFERER_VIEWS`) в PHP-файлы по датам.
     * После успешной миграции поле `data` заменяется на строку 'file'.
     *
     * @return bool
     */
    public static function convertHttpRefererViewsToFiles(): bool
    {
        if (!file_exists(fileSys::get_dir('/data/db.php'))) {
            return false;
        }

        $row = sql::getRow("SELECT `data` FROM server_cache WHERE `type` = 'HTTP_REFERER_VIEWS'");
        if (!$row) {
            return false;
        }

        // If already converted, nothing to do
        if ($row['data'] === 'file') {
            return true;
        }

        $decoded = json_decode($row['data'], true);
        if (!is_array($decoded)) {
            sql::run("UPDATE server_cache SET `data` = ? WHERE `type` = 'HTTP_REFERER_VIEWS'", ['file']);
            return true;
        }

        foreach ($decoded as $entry) {
            $referer = $entry['referer'] ?? null;
            $counts = $entry['count'] ?? [];
            if (!$referer || !is_array($counts)) {
                continue;
            }

            foreach ($counts as $oldDate => $cnt) {
                $ts = strtotime($oldDate);
                $fileDate = $ts === false ? $oldDate : date('d-m-Y', $ts);
                session::saveReferrerToFile(mb_strtolower($referer), $fileDate, (int)$cnt);
            }
        }

        sql::run("UPDATE server_cache SET `data` = ? WHERE `type` = 'HTTP_REFERER_VIEWS'", ['file']);

        return true;
    }


    public function show(): void
    {
        validation::user_protection("admin");

        
        // TODO : Удалить в будущем, когда у всех все данные будут перенесены в файлы
        self::convertHttpRefererViewsToFiles();

        $rawReferrers = $this->getView();

        if (empty($rawReferrers) || !is_array($rawReferrers)) {
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
        $dir = fileSys::get_dir('/uploads/views');
        if (!is_dir($dir)) {
            return [];
        }

        $files = glob(rtrim($dir, "\/\\") . DIRECTORY_SEPARATOR . '*.php');
        if (!$files) {
            return [];
        }

        $referers = [];

        foreach ($files as $file) {
            $dateStr = basename($file, '.php'); // expected d-m-Y
            $dt = \DateTime::createFromFormat('d-m-Y', $dateStr);
            if ($dt === false) {
                // try with other separators
                $dt = \DateTime::createFromFormat('d.m.Y', $dateStr);
            }
            if ($dt === false) {
                continue;
            }
            $dateYmd = $dt->format('Y-m-d');

            $data = @include $file;
            if (!is_array($data)) {
                continue;
            }

            foreach ($data as $host => $count) {
                $host = mb_strtolower((string)$host);
                if (!isset($referers[$host])) {
                    $referers[$host] = ['referer' => $host, 'count' => []];
                }
                $referers[$host]['count'][$dateYmd] = (int)$count;
            }
        }

        return array_values($referers);
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

    public function showDeletePage(): void
    {
        validation::user_protection("admin");

        $deleteResult = null;
        if (!empty($_SESSION['http_referrer_delete_result'])) {
            $deleteResult = json_decode($_SESSION['http_referrer_delete_result'], true);
            unset($_SESSION['http_referrer_delete_result']);
        }

        tpl::addVar([
            'deleteResult' => $deleteResult,
        ]);
        tpl::displayPlugin("/set_http_referrer/tpl/delete.html");
    }

    public function deleteByDate(): void
    {
        validation::user_protection("admin");

        $start = $_POST['date_start'] ?? null;
        $end = $_POST['date_end'] ?? null;
        $result = [
            'deleted' => [],
            'not_found' => [],
            'errors' => [],
        ];

        if (!$start || !$end) {
            $msg = lang::get_phrase('http_referrer_err_dates');
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            if ($isAjax) {
                echo json_encode(['ok' => false, 'errors' => [$msg]], JSON_UNESCAPED_UNICODE);
                return;
            }
            board::error($msg);
            \Ofey\Logan22\component\redirect::location('/admin/statistic/http/referral/delete');
            return;
        }

        try {
            $dtStart = new \DateTime($start);
            $dtEnd = new \DateTime($end);
        } catch (\Exception $e) {
            $msg = lang::get_phrase('http_referrer_err_date_format');
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            if ($isAjax) {
                echo json_encode(['ok' => false, 'errors' => [$msg]], JSON_UNESCAPED_UNICODE);
                return;
            }
            board::error($msg);
            \Ofey\Logan22\component\redirect::location('/admin/statistic/http/referral/delete');
            return;
        }

        if ($dtStart > $dtEnd) {
            // swap
            $tmp = $dtStart;
            $dtStart = $dtEnd;
            $dtEnd = $tmp;
        }

        $dir = fileSys::get_dir('/uploads/views');
        if (!is_dir($dir)) {
            $msg = lang::get_phrase('http_referrer_err_dir_not_found');
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            if ($isAjax) {
                echo json_encode(['ok' => false, 'errors' => [$msg]], JSON_UNESCAPED_UNICODE);
                return;
            }
            board::error($msg);
            \Ofey\Logan22\component\redirect::location('/admin/statistic/http/referral/delete');
            return;
        }

        $period = new \DatePeriod($dtStart, new \DateInterval('P1D'), $dtEnd->modify('+1 day'));
        foreach ($period as $day) {
            $d1 = $day->format('d-m-Y');
            $d2 = $day->format('d.m.Y');
            $f1 = rtrim($dir, "\/\\") . DIRECTORY_SEPARATOR . $d1 . '.php';
            $f2 = rtrim($dir, "\/\\") . DIRECTORY_SEPARATOR . $d2 . '.php';

            $foundAny = false;

            if (is_file($f1)) {
                $foundAny = true;
                if (@unlink($f1)) {
                    $result['deleted'][] = basename($f1);
                } else {
                    $result['errors'][] = "Не удалось удалить " . basename($f1);
                }
            }

            if (is_file($f2)) {
                $foundAny = true;
                if (@unlink($f2)) {
                    $result['deleted'][] = basename($f2);
                } else {
                    $result['errors'][] = "Не удалось удалить " . basename($f2);
                }
            }

            if (!$foundAny) {
                // neither format exists — report once per date
                $result['not_found'][] = $d1 . '.php';
            }
        }

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjax) {
            echo json_encode(['ok' => true, 'result' => $result], JSON_UNESCAPED_UNICODE);
            return;
        }

        $_SESSION['http_referrer_delete_result'] = json_encode($result);
        \Ofey\Logan22\component\redirect::location('/admin/statistic/http/referral/delete');
    }



}