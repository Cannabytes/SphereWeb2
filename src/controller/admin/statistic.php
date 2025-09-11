<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\template\tpl;

class statistic
{

    static public function getStatistic($serverId): void
    {
        tpl::addVar('serverId', $serverId);
        tpl::display('/admin/server_statistic.html',);
    }

    static public function getDonate(): void
    {
        $rows = sql::getRows("SELECT `point`, `message`, `pay_system`, `sphere`, `date` FROM `donate_history_pay` ORDER BY `id` DESC");

        $statsPay = []; // daily
        $monthlyStatsPay = [];
        $donatePoint = 0;
        foreach ($rows as $row) {
            if ($row['sphere'] == 1) continue; // skip sphere internal bonuses
            $p = (float)$row['point'];
            $donatePoint += $p;
            $date = substr($row['date'], 0, 10); // YYYY-MM-DD
            $month = date('Y-m', strtotime($row['date']));
            $statsPay[$date] = ($statsPay[$date] ?? 0) + $p;
            $monthlyStatsPay[$month] = ($monthlyStatsPay[$month] ?? 0) + $p;
        }

        // Pay systems distribution
        $paySystemsRows = sql::getRows("SELECT `pay_system`, SUM(`point`) AS sum_point FROM `donate_history_pay` WHERE `sphere` = 0 GROUP BY `pay_system` ORDER BY sum_point DESC");
        $paySystemLabels = [];
        $paySystemValues = [];
        foreach ($paySystemsRows as $ps) {
            $paySystemLabels[] = $ps['pay_system'];
            $paySystemValues[] = (float)$ps['sum_point'];
        }

        // Heatmap data (day-of-week x hour)
        $heatmapRows = sql::getRows("SELECT (DAYOFWEEK(`date`)+6)%7 AS dow, HOUR(`date`) AS h, COUNT(*) c FROM donate_history_pay WHERE sphere=0 GROUP BY dow,h");
        $heatmap = [];
        foreach ($heatmapRows as $r) {
            $d = (int)$r['dow'];
            $h = (int)$r['h'];
            $heatmap[$d][$h] = (int)$r['c'];
        }

        // Distinct donors count
        $donorsCount = (int)sql::getValue("SELECT COUNT(DISTINCT user_id) FROM donate_history_pay WHERE sphere=0");

        // Timeline series (array of [timestamp_ms, value])
        $timelineSeries = [];
        foreach ($statsPay as $d => $v) {
            $timelineSeries[] = [strtotime($d) * 1000, $v];
        }
        // Sort by time ascending
        usort($timelineSeries, fn($a, $b) => $a[0] <=> $b[0]);

        // Monthly arrays for charts
        ksort($monthlyStatsPay);
        $monthlyMonths = array_keys($monthlyStatsPay); // YYYY-MM
        $monthlyValues = array_values($monthlyStatsPay);

        if (\Ofey\Logan22\model\server\server::get_count_servers() == 0) {
            $dollars = $donatePoint;
        } else {
            $ratio = config::load()->donate()->getRatioUSD() / max(1, config::load()->donate()->getSphereCoinCost());
            $dollars = $donatePoint * $ratio;
        }

        tpl::addVar([
            'dollars' => $dollars,
            'donatePoint' => $donatePoint,
            'donors_count' => $donorsCount,
            'timeline_series' => $timelineSeries,
            'monthly_months' => $monthlyMonths,
            'monthly_values' => $monthlyValues,
            'pay_system_labels' => $paySystemLabels,
            'pay_system_values' => $paySystemValues,
            'heatmap' => $heatmap,
        ]);

        tpl::display("/admin/statistic_donate.html");
    }

    // DataTables server-side endpoint for donation rows
    public static function donateData(): void
    {
        validation::user_protection("admin");
        header('Content-Type: application/json; charset=utf-8');
        $draw   = (int)($_POST['draw'] ?? 0);
        $start  = (int)($_POST['start'] ?? 0);
        $length = (int)($_POST['length'] ?? 50);
        if ($length <= 0 || $length > 500) $length = 50;
        $search = trim($_POST['search']['value'] ?? '');
        $orderColIdx = (int)($_POST['order'][0]['column'] ?? 4); // default date desc
        $orderDir = strtolower($_POST['order'][0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
        // Columns: user, point, message, system, date
        $colMap = [0 => 'd.id', 1 => 'd.point', 2 => 'd.message', 3 => 'd.pay_system', 4 => 'd.date'];
        $orderCol = $colMap[$orderColIdx] ?? 'd.date';

        $where = ['d.sphere=0'];
        $params = [];
        if ($search !== '') {
            if (ctype_digit($search)) {
                $where[] = '(d.user_id = ? OR u.id = ?)';
                $params[] = (int)$search;
                $params[] = (int)$search;
            } else {
                $like = '%' . $search . '%';
                $where[] = '(u.email LIKE ? OR u.name LIKE ? OR d.message LIKE ? OR d.pay_system LIKE ?)';
                array_push($params, $like, $like, $like, $like);
            }
        }
        $whereSQL = 'WHERE ' . implode(' AND ', $where);
        $total = (int)sql::getValue('SELECT COUNT(*) FROM donate_history_pay d WHERE d.sphere=0');
        $filtered = $total;
        if ($search !== '') {
            $filtered = (int)sql::getValue('SELECT COUNT(*) FROM donate_history_pay d JOIN users u ON u.id=d.user_id ' . $whereSQL, $params);
        }
        $sqlRows = 'SELECT d.id,d.user_id,d.point,d.message,d.pay_system,d.date,u.email,u.avatar,u.country FROM donate_history_pay d JOIN users u ON u.id=d.user_id ' . $whereSQL . ' ORDER BY ' . $orderCol . ' ' . $orderDir . ' LIMIT ' . $start . ', ' . $length;
        $rows = sql::getRows($sqlRows, $params);
        $data = [];
        foreach ($rows as $r) {
            $uid = (int)$r['user_id'];
            $avatar = htmlspecialchars($r['avatar'] ?? 'none.jpeg');
            $email = htmlspecialchars($r['email'] ?? '');
            $country = htmlspecialchars($r['country'] ?? '');
            $point = (float)$r['point'];
            $pointHtml = $point;
            $message = htmlspecialchars($r['message'] ?? '');
            $pay = htmlspecialchars($r['pay_system'] ?? '');
            $date = htmlspecialchars($r['date']);
            $userCell = '<div class="d-flex align-items-center"><a href="/admin/user/info/' . $uid . '" class="text-default"><div class="avatar avatar-rounded avatar-md me-3 cover-image" style="background:url(/uploads/avatar/' . $avatar . ') center center;"></div></a><div><a href="/admin/user/info/' . $uid . '" class="text-default">' . $email . '</a><small class="d-block text-muted">Country: ' . $country . '</small></div></div>';
            $data[] = [
                'user' => $userCell,
                'point' => $pointHtml,
                'message' => $message,
                'system' => '<span class="badge bg-light text-dark">' . $pay . '</span>',
                'date' => $date,
            ];
        }
        echo json_encode(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
