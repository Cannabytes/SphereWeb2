<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\item\item;
use Ofey\Logan22\model\template\async;
use Ofey\Logan22\model\user\auth\user;
use Ofey\Logan22\template\tpl;

class users
{

    public static function getUserInfo($id): void
    {

        $userInfo = \Ofey\Logan22\model\user\user::getUserId($id);
        if (!$userInfo->isFoundUser()) {
            board::error("User not found");
        }
        tpl::addVar("userInfo", $userInfo);

        $logs = sql::getRows("SELECT `id`, `time`, phrase, `variables` FROM logs_all WHERE user_id = ? ORDER BY id DESC LIMIT 1000", [$id]);

        foreach ($logs as &$log) {
            $s = json_decode($log['variables']);
            $values = is_array($s) ? array_values($s) : [$s];
            $log['message'] = lang::get_phrase($log['phrase'], ...$values);
        }

        tpl::addVar("logs", $logs);

        $donate_history_pay = sql::getRows("SELECT id, point, message, pay_system, id_admin_pay, `date` FROM donate_history_pay WHERE user_id = ? ORDER BY id DESC;", [$id]);
        tpl::addVar("donate_history_pay", $donate_history_pay);

        tpl::display("/admin/user_profile.html");
    }

    public static function showAll($pageParam = null): void
    {
        validation::user_protection("admin");

        $perPage = 100;
        $page = 1;

        if ($pageParam !== null && is_numeric($pageParam)) {
            $page = (int)$pageParam;
        } elseif (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int)$_GET['page'];
        }

        if ($page < 1) {
            $page = 1;
        }

        $totalUsers = (int)sql::getValue("SELECT COUNT(*) FROM users");
        $totalPages = max(1, (int)ceil($totalUsers / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $perPage;

        $users = sql::getRows(
            "SELECT id, email, name, donate_point, avatar, date_create, last_activity, access_level FROM users ORDER BY date_create DESC, id DESC LIMIT ?, ?",
            [$offset, $perPage]
        );

        foreach ($users as &$user) {
            $user['avatar'] = $user['avatar'] ?: 'none.jpeg';
            $user['date_create_human'] = $user['date_create'] ? date('d.m.Y H:i', strtotime((string)$user['date_create'])) : '-';
            $user['last_activity_human'] = $user['last_activity'] ? date('d.m.Y H:i', strtotime((string)$user['last_activity'])) : '-';
        }
        unset($user);

        $rangeStart = $totalUsers > 0 ? $offset + 1 : 0;
        $rangeEnd = $totalUsers > 0 ? min($offset + count($users), $totalUsers) : 0;

        $windowSize = 7;
        $startPage = max(1, $page - intdiv($windowSize, 2));
        $endPage = min($totalPages, $startPage + $windowSize - 1);
        $startPage = max(1, $endPage - $windowSize + 1);
        $pages = range($startPage, $endPage);

        $pagination = [
            'current' => $page,
            'total' => $totalPages,
            'per_page' => $perPage,
            'total_users' => $totalUsers,
            'range_start' => $rangeStart,
            'range_end' => $rangeEnd,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages,
            'prev_page' => $page > 1 ? $page - 1 : null,
            'next_page' => $page < $totalPages ? $page + 1 : null,
            'pages' => $pages,
            'base_path' => '/admin/users',
        ];

        tpl::addVar([
            'users' => $users,
            'pagination' => $pagination,
        ]);

        tpl::display("/admin/users.html");
    }

    public static function searchByEmail(): void
    {
        validation::user_protection("admin");
        header('Content-Type: application/json; charset=utf-8');

        $query = trim((string)($_POST['query'] ?? $_GET['query'] ?? ''));
        $minLength = 2;
        $queryLength = $query === '' ? 0 : (function_exists('mb_strlen') ? mb_strlen($query) : strlen($query));

        if ($queryLength < $minLength) {
            echo json_encode([
                'ok' => true,
                'data' => [],
                'message' => 'No results',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $like = '%' . $query . '%';
        $limit = 20;

        try {
            $rows = sql::getRows(
                "SELECT id, email, name, avatar, last_activity FROM users WHERE email LIKE ? ORDER BY email ASC LIMIT $limit",
                [$like]
            );
        } catch (\Throwable $e) {
            echo json_encode([
                'ok' => false,
                'message' => 'Database error',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $data = array_map(static function (array $row): array {
            $lastActivity = null;
            if (!empty($row['last_activity'])) {
                $timestamp = strtotime((string)$row['last_activity']);
                if ($timestamp) {
                    $lastActivity = date('d.m.Y H:i', $timestamp);
                }
            }

            return [
                'id' => (int)$row['id'],
                'email' => (string)($row['email'] ?? ''),
                'name' => (string)($row['name'] ?? ''),
                'avatar' => $row['avatar'] ? (string)$row['avatar'] : 'none.jpeg',
                'last_activity' => $lastActivity,
            ];
        }, $rows ?: []);

        echo json_encode([
            'ok' => true,
            'data' => $data,
            'query' => $query,
            'count' => count($data),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Server-side data endpoint for Users DataTable
     * Accepts DataTables POST params: draw, start, length, search[value], order[0][column], order[0][dir]
     * Returns JSON with rows containing lightweight HTML to minimize template rendering overhead.
     */
    public static function data(): void
    {
        validation::user_protection("admin");
        header('Content-Type: application/json; charset=utf-8');

        $draw   = (int)($_POST['draw'] ?? 0);
        $start  = (int)($_POST['start'] ?? 0);
        $length = (int)($_POST['length'] ?? 50);
        if ($length <= 0 || $length > 1000) $length = 50; // sane limits
        $search = trim($_POST['search']['value'] ?? '');

        // Ordering
        $orderColIdx = (int)($_POST['order'][0]['column'] ?? 0);
        $orderDir = strtolower($_POST['order'][0]['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
        $columnsMap = [0 => 'u.id', 1 => 'u.email', 3 => 'u.date_create', 4 => 'u.last_activity', 5 => 'u.donate_point'];
        $orderCol = $columnsMap[$orderColIdx] ?? 'u.id';

        // Base query fragments
        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = '(u.email LIKE ? OR u.name LIKE ? OR u.id = ? )';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = (int)$search;
        }
        $whereSQL = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Total counts
        $total = (int)\Ofey\Logan22\model\db\sql::getValue("SELECT COUNT(*) FROM users u");
        $filtered = $total;
        if ($whereSQL) {
            $filtered = (int)\Ofey\Logan22\model\db\sql::getValue("SELECT COUNT(*) FROM users u $whereSQL", $params);
        }

        $sql = "SELECT u.id, u.email, u.name, u.donate_point, u.avatar, u.date_create, u.last_activity FROM users u $whereSQL ORDER BY $orderCol $orderDir LIMIT $start, $length";
        $rows = sql::getRows($sql, $params);

        $data = [];
        foreach ($rows as $r) {
            $uid = (int)$r['id'];

            $avatar = htmlspecialchars($r['avatar'] ?? '');
            $email  = htmlspecialchars($r['email'] ?? '');
            $name   = htmlspecialchars($r['name'] ?? '');
            $dateCreate = htmlspecialchars(date('d.m.Y H:i', strtotime((string)$r['date_create'])));
            $lastActive = $r['last_activity'] ? htmlspecialchars(date('d.m.Y H:i', strtotime((string)$r['last_activity']))) : '';
            $donate = (int)$r['donate_point'];

            $userHtml = '<div class="d-flex align-items-center mt-auto">'
                . '<div class="avatar avatar-md me-1 cover-image" style="background:url(/uploads/avatar/' . $avatar . ') center center;"></div>'
                . '<div>'
                . '<a href="/admin/user/info/' . $uid . '" class="text-default">' . $email . '<br>' . $name . '</a>'
                . '<small class="d-block text-muted">' . $dateCreate . '</small>'
                . '</div></div>';

            $donateHtml = '<span data-user-id="' . $uid . '" data-user-balance="' . $donate . '" class="reply-btn btn btn-sm btn-success sendToBalance"><i class="ri-add-circle-line"></i></span><br>'
                . '<span id="user_id_count_' . $uid . '" data-user-balance="' . $donate . '">' . $donate . '</span>';

            $data[] = [
                'id' => $uid,
                'user' => $userHtml,
                'created' => $dateCreate,
                'last_active' => $lastActive,
                'donate' => $donateHtml,
            ];
        }

        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function searchLite(): void
    {
        validation::user_protection("admin");
        header('Content-Type: application/json; charset=utf-8');

        $query = trim((string)($_POST['q'] ?? ''));
        $page = (int)($_POST['page'] ?? 1);
        $limit = (int)($_POST['limit'] ?? 20);

        if ($page < 1) {
            $page = 1;
        }
        if ($limit < 1) {
            $limit = 20;
        }
        if ($limit > 100) {
            $limit = 100;
        }

        $offset = ($page - 1) * $limit;

        $params = [];
        $whereParts = [];
        if ($query !== '') {
            $like = '%' . $query . '%';
            $whereParts[] = '(u.email LIKE ? OR u.name LIKE ?' . (ctype_digit($query) ? ' OR u.id = ?' : '') . ')';
            $params[] = $like;
            $params[] = $like;
            if (ctype_digit($query)) {
                $params[] = (int)$query;
            }
        }
        $whereSql = $whereParts ? ('WHERE ' . implode(' AND ', $whereParts)) : '';

        try {
            $total = (int)sql::getValue('SELECT COUNT(*) FROM users u ' . $whereSql, $params);
            $offset = max(0, $offset);
            $sql = 'SELECT u.id, u.name, u.email, u.avatar FROM users u ' . $whereSql . ' ORDER BY u.name ASC, u.id ASC LIMIT ' . $offset . ', ' . $limit;
            $rows = sql::getRows($sql, $params);
        } catch (\Throwable $e) {
            echo json_encode([
                'ok' => false,
                'message' => 'Database error',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $rows = $rows ?: [];
        $data = array_map(static function(array $row): array {
            return [
                'id' => (int)$row['id'],
                'name' => (string)($row['name'] ?? ''),
                'email' => (string)($row['email'] ?? ''),
                'avatar' => (string)($row['avatar'] ?? ''),
            ];
        }, $rows);

        $hasMore = (($page - 1) * $limit + count($data)) < $total;

        echo json_encode([
            'ok' => true,
            'data' => $data,
            'page' => $page,
            'perPage' => $limit,
            'total' => $total,
            'hasMore' => $hasMore,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function edit(): void
    {
        validation::user_protection("admin");

        $id = $_POST["id"] ?? board::error("No POST id");
        $email = $_POST["email"] ?? board::error("No POST email");
        $name = $_POST["name"] ?? board::error("No POST name");
        $donate = $_POST["donate"] ?? board::error("No POST donate");
        $password = $_POST["password"] ?? "";
        $group = $_POST["group"] ?? "user";

        //Проверка Email на валидацию
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            board::error("Invalid email");
        }

        if (!is_numeric($donate)) {
            board::error("Invalid donate");
        }

        $user = \Ofey\Logan22\model\user\user::getUserId($id);

        if ($password != "") {
            $user->setPassword($password);
        }

        $sql = "UPDATE users SET email = ?, name = ?, donate_point = ?, access_level = ? WHERE id = ?";
        $ok = sql::sql($sql, [$email, $name, $donate, $group, $id]);
        if ($ok) {
            board::success("User edited");
        } else {
            board::error("Failed to edit user");
        }
    }

    //Выдача предмета пользователю от администратора
    static public function addItemUserToWarehouse(): void
    {
        // Проверка прав пользователя
        validation::user_protection("admin");

        // Проверка обязательных параметров
        $serverId = $_POST['serverId'] ?? null;
        $userId = $_POST["userId"] ?? null;
        $itemId = $_POST["itemId"] ?? null;

        // Если какой-либо обязательный параметр отсутствует или пустой
        if (empty($serverId) || empty($userId) || empty($itemId)) {
            board::error("Не все обязательные параметры переданы или они пустые");
            return;
        }

        // Преобразование параметров, если необходимо
        $count = isset($_POST["count"]) ? (int)$_POST["count"] : 1;
        $enchant = isset($_POST["enchant"]) ? (int)$_POST["enchant"] : 0;

        // Проверка, что параметры count и enchant являются целыми числами
        if (!is_int($count) || $count < 1) {
            board::error("Неверное количество предметов");
            return;
        }

        if (!is_int($enchant) || $enchant < 0) {
            board::error("Неверный уровень зачарования");
            return;
        }

        // Добавление предмета в инвентарь пользователя
        $ok = \Ofey\Logan22\model\user\user::getUserId($userId)->addToWarehouse($serverId, $itemId, $count, $enchant, 'issued_by_the_administration');

        if (!$ok['success']) {
            board::error($ok['errorInfo']['message']);
            return;
        }

        board::reload();
        board::success("Предмет выдан");
    }

    /**
     * Удаление предмета из warehouse пользователя
     * @return void
     */
    static public function deleteItemUserToWarehouse(): void
    {
        // Проверка прав пользователя
        validation::user_protection("admin");

        // Получение ID объекта для удаления
        $objectId = $_POST["id"] ?? null;

        // Проверка наличия ID
        if (empty($objectId)) {
            board::error("Не указан ID предмета для удаления");
            return;
        }

        // Проверка существования предмета в warehouse
        $item = sql::getRow("SELECT * FROM `warehouse` WHERE `id` = ?", [$objectId]);
        if (!$item) {
            board::error("Предмет не найден");
            return;
        }

        // Получаем информацию о пользователе, которому принадлежит предмет
        $userId = $item['user_id'];
        $userObj = \Ofey\Logan22\model\user\user::getUserId($userId);

        if (!$userObj->isFoundUser()) {
            board::error("Пользователь не найден");
            return;
        }

        // Удаление предмета из warehouse
        try {
            $userObj->removeWarehouseObjectId($objectId);
            board::success("Предмет успешно удален");
        } catch (\Exception $e) {
            board::error("Ошибка при удалении предмета: " . $e->getMessage());
        }
    }
}
