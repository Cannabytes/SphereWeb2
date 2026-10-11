<?php
/**
 * Класс установщик
 */

namespace Ofey\Logan22\controller\install;

use Exception;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\plugins\sphere_forum\custom_twig;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\component\version\version;
use Ofey\Logan22\model\config\sphereApi;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;
use Ofey\Logan22\component\lang\lang;
use PDO;
use PDOException;

class install
{

    private static bool $allow_install = true;

    private static function ensureNativeSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
    
    /**
     * Установка, вывод правил, соглашения
     */
    public static function rules($lang = null): void
    {
        self::ensureNativeSession();
        if (file_exists(fileSys::get_dir('/data/db.php'))) {
            redirect::location("/");
            die();
        }
        $isHtaccess = file_exists(".htaccess");
        if (!$isHtaccess) {
            self::$allow_install = false;
        }
        tpl::addVar([
            "need_min_version_php" => version::MIN_PHP_VERSION(),
            "need_mysql_version" => version::MIN_MYSQL_VERSION(),
            "dir_permissions" => self::checkFolderPermissions(["/data", "/uploads",]),
            "htaccess" => $isHtaccess,
            "isLinux" => "Linux" == php_uname('s'),
            "isLocalhost" => self::is_local_environment(),
            "php_informations" => [
                [
                    "name" => "PHP_VERSION",
                    "get" => PHP_VERSION,
                    "min" => version::MIN_PHP_VERSION(),
                    "allow" => PHP_VERSION >= version::MIN_PHP_VERSION(),
                ],
                [
                    "name" => "upload_max_filesize",
                    "get" => ini_get("upload_max_filesize"),
                    "min" => "2M",
                    "allow" => self::compareUploadSizes(ini_get("upload_max_filesize"), "2M"),
                ],

            ],
            "extensions" => [
                [
                    "name" => "gd",
                    "allow" => self::isExtension(extension_loaded('gd') || function_exists('gd_info')),
                ],
                [
                    "name" => "curl",
                    "allow" => self::isExtension(extension_loaded('curl')),
                ],
                [
                    "name" => "pdo_mysql",
                    "allow" => self::isExtension(extension_loaded('pdo_mysql')),
                ],
                [
                    "name" => "mbstring",
                    "allow" => self::isExtension(extension_loaded('mbstring')),
                ],
            ],
            "allow_install" => self::$allow_install,
        ]);

        if ($lang == null || $lang == "") {
            $lang = user::self()->getLang();
        }
        if (!file_exists("src/template/sphere/install/install_{$lang}.html")) {
            $lang = "en";
        }
        $_SESSION['lang'] = $lang;
        tpl::display("install/install_{$lang}.html");
    }

    private static function checkFolderPermissions($dir = []): array
    {
        $dirPer = [];
        foreach ($dir as $folder) {
            $permissions = fileperms(fileSys::get_dir($folder));
            $ownerPermissions = ($permissions & 0o700) >> 6;
            $groupPermissions = ($permissions & 0o070) >> 3;
            $otherPermissions = $permissions & 0o007;
            if ($ownerPermissions >= 7 && $groupPermissions >= 5 && $otherPermissions >= 5) {
                $dirPer[] = [
                    "path" => $folder,
                    "per" => true,
                ];
            } else {
                if (php_uname('s') == "Windows NT") {
                    $dirPer[] = [
                        "path" => $folder,
                        "per" => true,
                    ];
                } else {
                    $dirPer[] = [
                        "path" => $folder,
                        "per" => false,
                    ];
                    self::set_allow_install(false);
                }
            }
        }

        return $dirPer;
    }

    private static function set_allow_install($b): void
    {
        if (!self::$allow_install) {
            return;
        }
        if (!$b) {
            self::$allow_install = false;
        }
    }

    private static function is_local_environment(): bool
    {
        $client_ip = $_SERVER['REMOTE_ADDR'];
        $local_ips = [
            '127.0.0.1',        // localhost
            '::1',              // IPv6 localhost
            '192.168.',         // локальная сеть
            '10.',              // локальная сеть
            '172.',              // локальная сеть
        ];
        foreach ($local_ips as $local_ip) {
            if (str_starts_with($client_ip, $local_ip)) {
                return true;
            }
        }

        return false;
    }

    private static function compareUploadSizes($size1, $size2): bool
    {
        $unit1 = strtoupper(substr($size1, -1));
        $unit2 = strtoupper(substr($size2, -1));
        $value1 = (int)substr($size1, 0, -1);
        $value2 = (int)substr($size2, 0, -1);
        switch ($unit1) {
            case 'G':
                $value1 *= 1024;
            case 'M':
                $value1 *= 1024;
            case 'K':
                $value1 *= 1024;
        }
        switch ($unit2) {
            case 'G':
                $value2 *= 1024;
            case 'M':
                $value2 *= 1024;
            case 'K':
                $value2 *= 1024;
        }
        $r = ($value1 - $value2) >= 0;
        self::set_allow_install($r);

        return $r;
    }

    private static function isExtension($v)
    {
        if (!$v) {
            self::set_allow_install(false);
        }

        return $v;
    }

    public static function db()
    {
        version::check_version_php();
        if (file_exists(fileSys::get_dir('/data/db.php'))) {
            redirect::location("/install/admin");
            die();
        }
        tpl::display("install/install_db.html");
    }

    //Проверка соединения с базой данных
    public static function db_connect()
    {
        version::check_version_php();
        if (file_exists(fileSys::get_dir('/data/db.php'))) {
            redirect::location("/");
            die();
        }
        self::connect_test_db(false);
    }

    public static function connect_test_db($only_admin = true)
    {
        if ($only_admin) {
            if (!user::self()->isAdmin()) {
                echo json_encode([
                    "type" => "notice",
                    "ok" => true,
                    "message" => 'Access is denied',
                ]);
                exit;
            }
        }
        $host = $_POST['host'];
        $port = $_POST['port'] ?? 3306;
        $user = $_POST['user'];
        $password = $_POST['password'];
        $name = $_POST['name'];
        $pdo = \Ofey\Logan22\model\install\install::test_connect_mysql($host, $port, $user, $password, $name);
        if ($pdo) {
            $ver = $pdo->query("SELECT VERSION()")->fetchColumn();
            $ver = preg_replace('/^(\d+\.\d+).*$/', '$1', $ver);
            $arr = json_encode([
                "type" => "notice",
                "ok" => true,
                "mysqlVersion" => $ver,
                'message' => 'Next install',
            ]);
            echo $arr;
            exit;
        } else {
            $data = [
                'type' => 'notice',
                'ok' => false,
                'message' => 'Database connection error',
            ];
            echo json_encode($data);
            exit;
        }
    }

    public static function admin()
    {
        if (\Ofey\Logan22\model\install\install::exist_admin()) {
            redirect::location("/");
            die();
        }
        tpl::display("install/install_admin.html");
    }

    public static function startInstall(): void
    {
        self::ensureNativeSession();
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_POST['email']) || empty($_POST['nickname']) || empty($_POST['adminPassword']) ||
            empty($_POST['host']) || empty($_POST['user']) || empty($_POST['password']) || empty($_POST['name'])) {
            echo json_encode([
                "type" => "notice",
                "ok" => false,
                "message" => 'Missing required parameters',
            ]);
            exit;
        }

        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        $nickname = htmlspecialchars($_POST['nickname']);
        $adminPassword = password_hash($_POST['adminPassword'], PASSWORD_BCRYPT);

        $host = $_POST['host'];
        $port = $_POST['port'] ?? 3306;
        $user = $_POST['user'];
        $password = $_POST['password'];
        $name = $_POST['name'];
        if (!$email) {
            echo json_encode([
                "type" => "notice",
                "ok" => false,
                "message" => 'Invalid email format',
            ]);
            exit;
        }

        /**
         * Создаем проверочный файл
         */
        $installChallenge = bin2hex(random_bytes(32));
        $filenameCheck = "sphere-install-" . $installChallenge . ".txt";

        if(!file_exists(fileSys::get_dir('/data/token.php'))){
            try {
                $file = file_put_contents($filenameCheck, "SPHERE_INSTALL_VERIFY:" . $installChallenge);
                if ($file === false) {
                    throw new Exception("Failed to create check file");
                }

                $link = new sphereApi();
                server::setInstallLink("{$link->getIp()}:{$link->getPort()}");
                server::tokenDisable(true);

                $response = server::send(type::SPHERE_INSTALL, [
                    'filename' => $filenameCheck,
                    'challenge' => $installChallenge,
                ])->show(false)->getResponse();
                if (!$response['success']) {
                    throw new Exception("Failed to install sphere");
                }
                $token = $response['token'];
                file_put_contents("data/token.php", "<?php const __TOKEN__ = " . var_export($token, true) . ";\n");
                unlink($filenameCheck);
            } catch (Exception $e) {
                echo json_encode([
                    "type" => "notice",
                    "ok" => false,
                    "message" => "Installation error: " . $e->getMessage(),
                ]);
                exit();
            }
        }

        \Ofey\Logan22\model\install\install::saveConfig($host, $port, $user, $password, $name);

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Устанавливаем структуру базы данных
            self::install_sql_struct($pdo, fileSys::get_dir("/uploads/sql/struct/*.sql"));
            $lastCommitData = self::getLastCommitData();
            if ($lastCommitData) {
                $query = $pdo->prepare(
                    "INSERT INTO `github_updates` (`sha`, `author`, `url`, `message`, `date`, `date_update`) 
                 VALUES (?, ?, ?, ?, ?, ?)"
                );
                $query->execute([
                    $lastCommitData['hash'],
                    $lastCommitData['author'],
                    $lastCommitData['url'],
                    $lastCommitData['message'],
                    time::mysql(),
                    time::mysql(),
                ]);
            }

            // Вставляем данные администратора
            $ip = $_SERVER['REMOTE_ADDR'];
            $smt = $pdo->prepare(
                "INSERT INTO `users` (`name`, `password`, `email`, `ip`, `access_level`)
             VALUES (?, ?, ?, ?, ?)"
            );

            $smt->execute([ $nickname, $adminPassword, $email, $ip, 'admin', ]);

            $adminId = (int)$pdo->lastInsertId();

            $_SESSION['id'] = $adminId;
            $_SESSION['email'] = $email;
            $_SESSION['password'] = $adminPassword;

            $sessionId = $_COOKIE['sphere_session'] ?? bin2hex(random_bytes(32));
            if (!preg_match('/^[a-f0-9]{64}$/', $sessionId)) {
                $sessionId = bin2hex(random_bytes(32));
            }

            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['REQUEST_SCHEME'] ?? '') === 'https');
            setcookie('sphere_session', $sessionId, [
                'expires' => time() + (86400 * 365),
                'path' => '/',
                'domain' => '',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            $sessionPayload = json_encode($_SESSION, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($sessionPayload === false) {
                $sessionPayload = json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $lastActivity = time();

            $sessionStmt = $pdo->prepare("
                INSERT INTO `sessions`
                    (`session_id`, `user_id`, `ip_address`, `user_agent`, `last_activity`, `data`)
                VALUES
                    (:session_id, :user_id, :ip_address, :user_agent, :last_activity, :data)
                ON DUPLICATE KEY UPDATE
                    `user_id` = VALUES(`user_id`),
                    `ip_address` = VALUES(`ip_address`),
                    `user_agent` = VALUES(`user_agent`),
                    `last_activity` = VALUES(`last_activity`),
                    `data` = VALUES(`data`)
            ");

            $sessionStmt->execute([
                ':session_id' => $sessionId,
                ':user_id' => $adminId,
                ':ip_address' => $ip,
                ':user_agent' => $userAgent,
                ':last_activity' => $lastActivity,
                ':data' => $sessionPayload,
            ]);

            $welcomeThread = self::initializeSphereForum($pdo, $adminId);
            $redirectUrl = $welcomeThread['url'] ?? '/forum';
 
            echo json_encode([
                "type" => "notice",
                'redirect' => $redirectUrl,
                'ok' => true,
                'message' => lang::phrase("installed"),
            ]);

            if(file_exists("uploads/sql.php")){
                unlink("uploads/sql.php");
            }
            exit();
        } catch (PDOException|Exception $e) {
            echo json_encode([
                "type" => "notice",
                "ok" => false,
                "message" => "Installation error: " . $e->getMessage(),
            ]);
            exit();
        }

    }

    private static function initializeSphereForum(PDO $pdo, int $adminId): array
    {
        $now = time::mysql();
        $startedTransaction = false;
        $welcomeThread = ['threadId' => null, 'url' => null];

        try {
            if (!$pdo->inTransaction()) {
                $pdo->beginTransaction();
                $startedTransaction = true;
            }

            self::ensurePluginActivation($pdo, $now);
            self::ensurePluginSettings($pdo, $now);
            $welcomeThread = self::ensureForumContent($pdo, $adminId, $now);

            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->commit();
            }

            return $welcomeThread;
        } catch (Exception $e) {
            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new Exception("Forum initialization error: " . $e->getMessage(), 0, $e);
        }
    }

    private static function ensurePluginActivation(PDO $pdo, string $now): void
    {
        $plugins = [];

        $stmt = $pdo->prepare("SELECT `setting` FROM `settings` WHERE `key` = '__PLUGIN__' AND `serverId` = 0 LIMIT 1");
        $stmt->execute();
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing && !empty($existing['setting'])) {
            $decoded = json_decode($existing['setting'], true);
            if (is_array($decoded)) {
                $plugins = $decoded;
            }
        }

        if (!in_array('sphere_forum', $plugins, true)) {
            $plugins[] = 'sphere_forum';
        }

        $plugins = array_values(array_unique($plugins));

        $pdo->prepare("DELETE FROM `settings` WHERE `key` = '__PLUGIN__' AND `serverId` = 0")->execute();

        $insert = $pdo->prepare("INSERT INTO `settings` (`key`, `setting`, `serverId`, `dateUpdate`) VALUES ('__PLUGIN__', :setting, 0, :date)");
        $insert->execute([
            ':setting' => json_encode($plugins, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':date' => $now,
        ]);
    }

    private static function ensurePluginSettings(PDO $pdo, string $now): void
    {
        $settings = [];

        $stmt = $pdo->prepare("SELECT `setting` FROM `settings` WHERE `key` = '__PLUGIN__sphere_forum' AND `serverId` = 0 LIMIT 1");
        $stmt->execute();
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing && !empty($existing['setting'])) {
            $decoded = json_decode($existing['setting'], true);
            if (is_array($decoded)) {
                $settings = $decoded;
            }
        }

        $settings = array_merge($settings, [
            'showMainPage' => true,
            'addToMenu' => true,
        ]);

        $pdo->prepare("DELETE FROM `settings` WHERE `key` = '__PLUGIN__sphere_forum' AND `serverId` = 0")->execute();

        $insert = $pdo->prepare("INSERT INTO `settings` (`key`, `setting`, `serverId`, `dateUpdate`) VALUES ('__PLUGIN__sphere_forum', :setting, 0, :date)");
        $insert->execute([
            ':setting' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':date' => $now,
        ]);
    }
 
    private static function ensureForumContent(PDO $pdo, int $adminId, string $now): array
    {
        $newsCategoryId = self::ensureCategory(
            $pdo,
            lang::phrase('news'),
            null,
            lang::phrase('current_announcements_and_updates'),
            'dark',
            false,
            0,
            $now
        );

        $sphereCategoryId = self::ensureCategory(
            $pdo,
            'SphereWeb',
            $newsCategoryId,
            lang::phrase('sphereweb_news_and_team_chat'),
            'primary',
            true,
            0,
            $now
        );

        self::ensureCategory(
            $pdo,
            lang::phrase('project_news'),
            $newsCategoryId,
            lang::phrase('game_server_news'),
            'primary',
            true,
            0,
            $now
        );

        self::ensureCategory(
            $pdo,
            lang::phrase('updates_and_innovations'),
            $newsCategoryId,
            lang::phrase('server_updates_and_features'),
            'primary',
            true,
            0,
            $now
        );

        $stmt = $pdo->prepare("SELECT `id` FROM `forum_threads` WHERE `category_id` = :category AND `title` = :title LIMIT 1");
        $stmt->execute([
            ':category' => $sphereCategoryId,
            ':title' => lang::phrase('sphereweb_installation_success'),
        ]);

        $threadId = (int)($stmt->fetchColumn() ?: 0);
        $threadData = null;

        if (!$threadId) {
            $threadData = self::createWelcomeThread($pdo, $sphereCategoryId, $newsCategoryId, $adminId, $now);
            $threadId = $threadData['threadId'];
        } else {
            $customTwig = new custom_twig();
            $translit = $customTwig->transliterateToEn(lang::phrase('sphereweb_installation_success'));
            $threadData = [
                'threadId' => $threadId,
                'url' => "/forum/topic/{$translit}.{$threadId}",
            ];
        }

        $ClanCategoryId = self::ensureCategory(
            $pdo,
            lang::phrase('clan_section'),
            null,
            lang::phrase('clan_discussion_recruitment'),
            'dark',
            false,
            0,
            $now
        );

        self::ensureCategory(
            $pdo,
            lang::phrase('clan_recruitment'),
            $ClanCategoryId,
            lang::phrase('clans_seeking_members'),
            'primary',
            true,
            0,
            $now
        );

        self::ensureCategory(
            $pdo,
            lang::phrase('clan_search'),
            $ClanCategoryId,
            lang::phrase('players_seeking_clan'),
            'primary',
            true,
            0,
            $now
        );

        $flameCategoryId = self::ensureCategory(
            $pdo,
            lang::phrase('flame'),
            null,
            lang::phrase('general_discussion'),
            'dark',
            false,
            0,
            $now
        );

        self::ensureCategory(
            $pdo,
            lang::phrase('offtopic_conversations'),
            $flameCategoryId,
            '',
            'primary',
            true,
            0,
            $now
        );
        
        self::ensureCategory(
            $pdo,
            lang::phrase('creativity_hobbies_fun'),
            $flameCategoryId,
            '',
            'primary',
            true,
            0,
            $now
        );

        self::ensureCategory(
            $pdo,
            lang::phrase('archive'),
            $flameCategoryId,
            '',
            'danger',
            true,
            0,
            $now
        );

        return $threadData;
    }

    private static function ensureCategory(
        PDO $pdo,
        string $name,
        ?int $parentId,
        string $description,
        string $titleColor,
        bool $canCreateTopics,
        int $sortOrder,
        string $now
    ): int {
        if ($parentId === null) {
            $stmt = $pdo->prepare("SELECT `id` FROM `forum_categories` WHERE `name` = :name AND `parent_id` IS NULL LIMIT 1");
            $stmt->execute([':name' => $name]);
        } else {
            $stmt = $pdo->prepare("SELECT `id` FROM `forum_categories` WHERE `name` = :name AND `parent_id` = :parent LIMIT 1");
            $stmt->execute([
                ':name' => $name,
                ':parent' => $parentId,
            ]);
        }

        $existingId = $stmt->fetchColumn();
        if ($existingId) {
            return (int)$existingId;
        }

        $insert = $pdo->prepare(
            "INSERT INTO `forum_categories` 
            (`parent_id`, `name`, `description`, `created_at`, `updated_at`, `last_reply_user_id`, `last_post_id`, `last_thread_id`, `post_count`, `view_count`, `thread_count`, `is_close`, `icon_svg`, `link`, `is_hidden`, `can_create_topics`, `can_reply_topics`, `can_view_topics`, `is_moderated`, `sort_order`, `can_users_delete_own_threads`, `can_users_delete_own_posts`, `edit_timeout_minutes`, `notify_telegram`, `max_post_length`, `thread_delete_timeout_minutes`, `hide_last_topic`, `title_color`)
            VALUES (:parent_id, :name, :description, :created_at, :updated_at, NULL, NULL, NULL, 0, 0, 0, 0, NULL, NULL, 0, :can_create_topics, 1, 1, 0, :sort_order, 0, 0, 30, 0, 20000, 30, 0, :title_color)"
        );

        $insert->execute([
            ':parent_id' => $parentId,
            ':name' => $name,
            ':description' => $description,
            ':created_at' => $now,
            ':updated_at' => $now,
            ':can_create_topics' => $canCreateTopics ? 1 : 0,
            ':sort_order' => $sortOrder,
            ':title_color' => $titleColor,
        ]);

        return (int)$pdo->lastInsertId();
    }

    private static function createWelcomeThread(PDO $pdo, int $categoryId, int $rootCategoryId, int $adminId, string $now): array
    {
        $threadTitle = lang::phrase('sphereweb_installation_success');
        $threadContentRU = '<h1>✨ Поздравляем!</h1><p><br></p><p><strong>SphereWeb</strong> успешно установлена — теперь у вас есть мощная платформа для управления игровым сервером <strong>Lineage 2</strong>.</p><p>Это современный веб-движок, созданный специально для администрирования игровых серверов и удобного взаимодействия с игроками.</p><p><br></p><h3>🔑 Основные возможности</h3><ul><li><strong>Личный кабинет игрока</strong> — регистрация, авторизация, смена пароля, привязка к игровому аккаунту, управление предметами и персонажами.</li><li><strong>Административная панель</strong> — публикация новостей и страниц, управление магазинами, бонус-кодами, логами, статистикой, донатами и огромным другим функционалом.</li><li><strong>Плагины</strong>— подключайте плагины и расширяйте возможности проекта.</li><li><strong>Шаблоны</strong> — встроенный шаблонизатор и поддержка адаптации HTML.</li><li><strong>Безопасность и обновления</strong> — регулярные улучшения и поддержка актуальных стандартов.</li></ul><p><br></p><h3>🚀 Первые шаги после установки</h3><ol><li>Войдите в <strong>админ-панель</strong> и <a href="/admin/setting" class="text-primary fw-semibold">настройте основные параметры</a> (язык, шаблон, меню, фон, логотип...).</li><li>Проверьте <a href="/admin/extensions/paid" class="text-primary fw-semibold">список <strong>плагинов</strong></a> и активируйте только те, что нужны именно вашему проекту.</li><li>Убедитесь, что <strong>права на файлы и директории</strong> выставлены корректно (рекомендуется <code>755</code>).</li></ol><blockquote>На популярных хостингах (L2UP, Reg.ru) всё настроено по умолчанию, не требует вмешательства.</blockquote><p><br></p><ol><li>Настройте внешний вид сайта: подключите <strong>шаблон</strong>, измените стили и структуру меню.</li><li>Подключите свой <strong>игровой сервер</strong>: укажите версию клиента, сборку сервера и данные для подключения к MySQL.</li><li>Проведите тест: регистрация, авторизация, покупка через донаты, выдача предметов.</li><li>Подпишитесь на <a href="https://t.me/shpereweb" class="text-primary fw-semibold" target="_blank" rel="noopener noreferrer"><strong>Telegram-канал SphereWeb</strong></a>, чтобы получать обновления и новости проекта.</li></ol><p><br></p>';
        $threadContentEN = '<h1>✨ Congratulations!</h1><p><br></p><p><strong>SphereWeb</strong> has been successfully installed — now you have a powerful platform to manage your <strong>Lineage 2</strong> game server.</p><p>This is a modern web engine, created specifically for server administration and convenient interaction with players.</p><p><br></p><h3>🔑 Key Features</h3><ul><li><strong>Player’s personal account</strong> — registration, login, password reset, game account linking, item and character management.</li><li><strong>Admin panel</strong> — publish news and pages, manage shops, bonus codes, logs, statistics, donations, and a huge set of other tools.</li><li><strong>Plugins</strong> — connect plugins and expand your project’s functionality.</li><li><strong>Templates</strong> — built-in templating system with HTML adaptation support.</li><li><strong>Security and updates</strong> — regular improvements and compliance with modern standards.</li></ul><p><br></p><h3>🚀 First Steps After Installation</h3><ol><li>Log into the <strong>admin panel</strong> and <a href="/admin/setting" class="text-primary fw-semibold">configure the main settings</a> (language, template, menu, background, logo...).</li><li>Check the <a href="/admin/extensions/paid" class="text-primary fw-semibold"><strong>plugin list</strong></a> and activate only those required for your project.</li><li>Make sure <strong>file and directory permissions</strong> are set correctly (recommended <code>755</code>).</li></ol><blockquote>On popular hosting providers (L2UP, Reg.ru), everything is configured by default and requires no changes.</blockquote><p><br></p><ol><li>Customize the site’s appearance: apply a <strong>template</strong>, adjust styles, and edit menu structure.</li><li>Connect your <strong>game server</strong>: specify client version, server build, and MySQL connection details.</li><li>Run a test: registration, login, donation purchase, item delivery.</li><li>Subscribe to the <a href="https://t.me/shpereweb" class="text-primary fw-semibold" target="_blank" rel="noopener noreferrer"><strong>SphereWeb Telegram channel</strong></a> to get project updates and news.</li></ol><p><br></p>';
        $threadContentES = '<h1>✨ ¡Felicidades!</h1><p><br></p><p><strong>SphereWeb</strong> se ha instalado correctamente — ahora tienes una potente plataforma para gestionar tu servidor de <strong>Lineage 2</strong>.</p><p>Es un motor web moderno, creado específicamente para la administración de servidores y la interacción cómoda con los jugadores.</p><p><br></p><h3>🔑 Funcionalidades principales</h3><ul><li><strong>Cuenta personal del jugador</strong> — registro, inicio de sesión, cambio de contraseña, vinculación con la cuenta del juego, gestión de objetos y personajes.</li><li><strong>Panel de administración</strong> — publicar noticias y páginas, gestionar tiendas, códigos de bonificación, registros, estadísticas, donaciones y muchas otras funciones.</li><li><strong>Plugins</strong> — conecta plugins y amplía las capacidades del proyecto.</li><li><strong>Plantillas</strong> — sistema de plantillas integrado y soporte para adaptación HTML.</li><li><strong>Seguridad y actualizaciones</strong> — mejoras regulares y cumplimiento con los estándares actuales.</li></ul><p><br></p><h3>🚀 Primeros pasos después de la instalación</h3><ol><li>Inicia sesión en el <strong>panel de administración</strong> y <a href="/admin/setting" class="text-primary fw-semibold">configura los parámetros principales</a> (idioma, plantilla, menú, fondo, logo...).</li><li>Revisa la <a href="/admin/extensions/paid" class="text-primary fw-semibold"><strong>lista de plugins</strong></a> y activa solo los necesarios para tu proyecto.</li><li>Asegúrate de que los <strong>permisos de archivos y directorios</strong> estén configurados correctamente (recomendado <code>755</code>).</li></ol><blockquote>En los hostings populares (L2UP, Reg.ru), todo está configurado por defecto y no requiere intervención.</blockquote><p><br></p><ol><li>Personaliza la apariencia del sitio: aplica una <strong>plantilla</strong>, modifica estilos y estructura del menú.</li><li>Conecta tu <strong>servidor de juego</strong>: especifica versión del cliente, compilación del servidor y datos de conexión MySQL.</li><li>Realiza pruebas: registro, inicio de sesión, compras mediante donaciones, entrega de objetos.</li><li>Suscríbete al <a href="https://t.me/shpereweb" class="text-primary fw-semibold" target="_blank" rel="noopener noreferrer"><strong>canal de Telegram SphereWeb</strong></a> para recibir actualizaciones y noticias del proyecto.</li></ol><p><br></p>';
        $threadContentUA = '<h1>✨ Вітаємо!</h1><p><br></p><p><strong>SphereWeb</strong> успішно встановлено — тепер у вас є потужна платформа для керування ігровим сервером <strong>Lineage 2</strong>.</p><p>Це сучасний веб-движок, створений спеціально для адміністрування серверів та зручної взаємодії з гравцями.</p><p><br></p><h3>🔑 Основні можливості</h3><ul><li><strong>Особистий кабінет гравця</strong> — реєстрація, авторизація, зміна пароля, прив’язка до ігрового акаунту, управління предметами та персонажами.</li><li><strong>Адміністративна панель</strong> — публікація новин та сторінок, управління магазинами, бонус-кодами, логами, статистикою, донатами та іншими функціями.</li><li><strong>Плагіни</strong> — підключайте плагіни та розширюйте можливості проєкту.</li><li><strong>Шаблони</strong> — вбудований шаблонізатор і підтримка адаптації HTML.</li><li><strong>Безпека та оновлення</strong> — регулярні покращення та відповідність сучасним стандартам.</li></ul><p><br></p><h3>🚀 Перші кроки після встановлення</h3><ol><li>Увійдіть в <strong>адмін-панель</strong> та <a href="/admin/setting" class="text-primary fw-semibold">налаштуйте основні параметри</a> (мова, шаблон, меню, фон, логотип...).</li><li>Перевірте <a href="/admin/extensions/paid" class="text-primary fw-semibold"><strong>список плагінів</strong></a> та активуйте лише ті, що потрібні вашому проєкту.</li><li>Переконайтеся, що <strong>права на файли та директорії</strong> встановлені правильно (рекомендується <code>755</code>).</li></ol><blockquote>На популярних хостингах (L2UP, Reg.ru) все налаштовано за замовчуванням і не потребує втручання.</blockquote><p><br></p><ol><li>Налаштуйте зовнішній вигляд сайту: підключіть <strong>шаблон</strong>, змініть стилі та структуру меню.</li><li>Підключіть свій <strong>ігровий сервер</strong>: вкажіть версію клієнта, збірку сервера та дані для підключення до MySQL.</li><li>Проведіть тест: реєстрація, авторизація, покупки через донати, видача предметів.</li><li>Підпишіться на <a href="https://t.me/shpereweb" class="text-primary fw-semibold" target="_blank" rel="noopener noreferrer"><strong>Telegram-канал SphereWeb</strong></a>, щоб отримувати оновлення та новини проєкту.</li></ol><p><br></p>';
        $threadContentGR = '<h1>✨ Συγχαρητήρια!</h1><p><br></p><p><strong>Το SphereWeb</strong> εγκαταστάθηκε με επιτυχία — τώρα έχετε μια ισχυρή πλατφόρμα για τη διαχείριση του διακομιστή παιχνιδιού <strong>Lineage 2</strong>.</p><p>Είναι μια σύγχρονη web μηχανή, δημιουργημένη ειδικά για τη διαχείριση διακομιστών και την εύκολη αλληλεπίδραση με τους παίκτες.</p><p><br></p><h3>🔑 Κύρια χαρακτηριστικά</h3><ul><li><strong>Προσωπικός λογαριασμός παίκτη</strong> — εγγραφή, σύνδεση, αλλαγή κωδικού, σύνδεση με τον λογαριασμό παιχνιδιού, διαχείριση αντικειμένων και χαρακτήρων.</li><li><strong>Πίνακας διαχείρισης</strong> — δημοσίευση ειδήσεων και σελίδων, διαχείριση καταστημάτων, κωδικών μπόνους, logs, στατιστικών, δωρεών και πολλών άλλων λειτουργιών.</li><li><strong>Plugins</strong> — προσθέστε plugins και επεκτείνετε τις δυνατότητες του έργου.</li><li><strong>Templates</strong> — ενσωματωμένο σύστημα προτύπων με υποστήριξη HTML.</li><li><strong>Ασφάλεια και ενημερώσεις</strong> — τακτικές βελτιώσεις και συμμόρφωση με τα τρέχοντα πρότυπα.</li></ul><p><br></p><h3>🚀 Πρώτα βήματα μετά την εγκατάσταση</h3><ol><li>Συνδεθείτε στον <strong>πίνακα διαχείρισης</strong> και <a href="/admin/setting" class="text-primary fw-semibold">ρυθμίστε τις βασικές παραμέτρους</a> (γλώσσα, template, μενού, φόντο, λογότυπο...).</li><li>Ελέγξτε τη <a href="/admin/extensions/paid" class="text-primary fw-semibold"><strong>λίστα των plugins</strong></a> και ενεργοποιήστε μόνο αυτά που χρειάζεστε για το έργο σας.</li><li>Βεβαιωθείτε ότι τα <strong>δικαιώματα αρχείων και φακέλων</strong> έχουν οριστεί σωστά (συνιστάται <code>755</code>).</li></ol><blockquote>Στους δημοφιλείς hosting providers (L2UP, Reg.ru), όλα είναι ρυθμισμένα από προεπιλογή και δεν απαιτείται παρέμβαση.</blockquote><p><br></p><ol><li>Προσαρμόστε την εμφάνιση του site: εφαρμόστε ένα <strong>template</strong>, αλλάξτε στυλ και δομή μενού.</li><li>Συνδέστε τον <strong>διακομιστή παιχνιδιού</strong>: καθορίστε την έκδοση του client, το build του server και τα στοιχεία σύνδεσης MySQL.</li><li>Κάντε ένα τεστ: εγγραφή, σύνδεση, αγορά μέσω δωρεών, παράδοση αντικειμένων.</li><li>Εγγραφείτε στο <a href="https://t.me/shpereweb" class="text-primary fw-semibold" target="_blank" rel="noopener noreferrer"><strong>Telegram κανάλι SphereWeb</strong></a> για να λαμβάνετε ενημερώσεις και νέα του έργου.</li></ol><p><br></p>';
        $threadContentPT = '<h1>✨ Parabéns!</h1><p><br></p><p><strong>SphereWeb</strong> foi instalada com sucesso — agora você tem uma poderosa plataforma para gerenciar seu servidor de <strong>Lineage 2</strong>.</p><p>É um motor web moderno, criado especificamente para administração de servidores e interação prática com os jogadores.</p><p><br></p><h3>🔑 Principais recursos</h3><ul><li><strong>Conta pessoal do jogador</strong> — registro, login, troca de senha, vinculação à conta do jogo, gerenciamento de itens e personagens.</li><li><strong>Painel administrativo</strong> — publicar notícias e páginas, gerenciar lojas, códigos de bônus, logs, estatísticas, doações e muitas outras funcionalidades.</li><li><strong>Plugins</strong> — conecte plugins e expanda as funcionalidades do projeto.</li><li><strong>Templates</strong> — sistema de templates integrado com suporte à adaptação HTML.</li><li><strong>Segurança e atualizações</strong> — melhorias regulares e conformidade com padrões atuais.</li></ul><p><br></p><h3>🚀 Primeiros passos após a instalação</h3><ol><li>Entre no <strong>painel administrativo</strong> e <a href="/admin/setting" class="text-primary fw-semibold">configure os parâmetros principais</a> (idioma, template, menu, fundo, logo...).</li><li>Verifique a <a href="/admin/extensions/paid" class="text-primary fw-semibold"><strong>lista de plugins</strong></a> e ative apenas os necessários para seu projeto.</li><li>Certifique-se de que as <strong>permissões de arquivos e diretórios</strong> estejam corretas (recomendado <code>755</code>).</li></ol><blockquote>Em provedores de hospedagem populares (L2UP, Reg.ru), tudo está configurado por padrão e não requer intervenção.</blockquote><p><br></p><ol><li>Personalize a aparência do site: aplique um <strong>template</strong>, altere estilos e estrutura do menu.</li><li>Conecte seu <strong>servidor de jogo</strong>: informe a versão do cliente, build do servidor e dados de conexão MySQL.</li><li>Realize testes: registro, login, compras via doações, entrega de itens.</li><li>Inscreva-se no <a href="https://t.me/shpereweb" class="text-primary fw-semibold" target="_blank" rel="noopener noreferrer"><strong>canal SphereWeb no Telegram</strong></a> para receber atualizações e notícias do projeto.</li></ol><p><br></p>';

        $insertThread = $pdo->prepare(
            "INSERT INTO `forum_threads`
            (`category_id`, `user_id`, `title`, `created_at`, `updated_at`, `views`, `replies`, `first_message_id`, `last_reply_user_id`, `last_post_id`, `is_pinned`, `is_closed`, `is_approved`, `approved_by`, `approved_at`, `poll_id`)
            VALUES (:category_id, :user_id, :title, :created_at, :updated_at, 0, 0, 0, NULL, NULL, 0, 0, 1, NULL, NULL, NULL)"
        );
        $insertThread->execute([
            ':category_id' => $categoryId,
            ':user_id' => $adminId,
            ':title' => $threadTitle,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $threadId = (int)$pdo->lastInsertId();

        $insertPost = $pdo->prepare(
            "INSERT INTO `forum_posts`
            (`thread_id`, `user_id`, `content`, `created_at`, `updated_at`, `reply_to_id`)
            VALUES (:thread_id, :user_id, :content, :created_at, :updated_at, NULL)"
        );

        $lang = strtolower($_SESSION['lang'] ?? 'en');
        $welcomeContent = match ($lang) {
            'ru' => $threadContentRU,
            'es' => $threadContentES,
            'ua' => $threadContentUA,
            'gr', 'rg' => $threadContentGR,
            'pt' => $threadContentPT,
            default => $threadContentEN,
        };

        $insertPost->execute([
            ':thread_id' => $threadId,
            ':user_id' => $adminId,
            ':content' => $welcomeContent,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $postId = (int)$pdo->lastInsertId();

        $updateThread = $pdo->prepare(
            "UPDATE `forum_threads`
            SET `views` = 1, `replies` = 1, `first_message_id` = :post_id, `last_reply_user_id` = :user_id, `last_post_id` = :post_id, `updated_at` = :updated_at
            WHERE `id` = :thread_id"
        );
        $updateThread->execute([
            ':post_id' => $postId,
            ':user_id' => $adminId,
            ':updated_at' => $now,
            ':thread_id' => $threadId,
        ]);

        $updateCategory = $pdo->prepare(
            "UPDATE `forum_categories`
            SET `post_count` = `post_count` + 1,
                `thread_count` = `thread_count` + 1,
                `last_reply_user_id` = :user_id,
                `last_post_id` = :post_id,
                `last_thread_id` = :thread_id,
                `updated_at` = :updated_at
            WHERE `id` = :category_id"
        );
        $updateCategory->execute([
            ':user_id' => $adminId,
            ':post_id' => $postId,
            ':thread_id' => $threadId,
            ':updated_at' => $now,
            ':category_id' => $categoryId,
        ]);

        if ($rootCategoryId !== $categoryId) {
            $updateParent = $pdo->prepare(
                "UPDATE `forum_categories`
                SET `post_count` = `post_count` + 1,
                    `thread_count` = `thread_count` + 1,
                    `last_reply_user_id` = :user_id,
                    `last_post_id` = :post_id,
                    `last_thread_id` = :thread_id,
                    `updated_at` = :updated_at
                WHERE `id` = :category_id"
            );
            $updateParent->execute([
                ':user_id' => $adminId,
                ':post_id' => $postId,
                ':thread_id' => $threadId,
                ':updated_at' => $now,
                ':category_id' => $rootCategoryId,
            ]);
        }

        $customTwig = new custom_twig();
        $translit = $customTwig->transliterateToEn($threadTitle);
        $threadUrl = "/forum/topic/{$translit}.{$threadId}";

        return [
            'threadId' => $threadId,
            'url' => $threadUrl,
        ];
    }

    private static function install_sql_struct($pdo, $dir): void
    {
        $files = glob($dir);
        foreach ($files as $file) {
            $sql = file_get_contents($file);
            $pdo->query($sql);
        }
    }

    private static function getLastCommitData(): ?array
    {
        function getLastCommitDetails($gitDir = '.git'): array
        {
            // Получаем последний хеш коммита
            $commitHash = getLastCommitHash($gitDir);

            // Определяем путь к объекту коммита в .git/objects
            $objectPath = sprintf('%s/objects/%s/%s', $gitDir, substr($commitHash, 0, 2), substr($commitHash, 2));

            if (!file_exists($objectPath)) {
                throw new Exception("Object file not found: $objectPath");
            }

            // Читаем и распаковываем содержимое файла объекта коммита
            $objectContent = file_get_contents($objectPath);
            $objectContent = gzuncompress($objectContent);

            if (!$objectContent) {
                throw new Exception("Unable to decompress object content");
            }

            // Извлекаем данные коммита
            $commitDetails = parseCommitObject($commitHash, $objectContent);

            return $commitDetails;
        }

        function getLastCommitHash($gitDir): string
        {
            $headFile = $gitDir . '/HEAD';
            if (!file_exists($headFile)) {
                throw new Exception('HEAD file not found in .git directory');
            }

            $headContent = file_get_contents($headFile);
            if (strpos($headContent, 'ref: ') === 0) {
                $refPath = trim(str_replace('ref: ', '', $headContent));
                $commitHashFile = $gitDir . '/' . $refPath;
            } else {
                return trim($headContent);
            }

            if (!file_exists($commitHashFile)) {
                throw new Exception("Commit hash file not found: $commitHashFile");
            }

            return trim(file_get_contents($commitHashFile));
        }

        function parseCommitObject($commitHash, $objectContent): array
        {
            // Разбиваем данные по строкам
            $lines = explode("\n", $objectContent);

            $commitInfo = [
                'hash' => $commitHash,
                'author' => '',
                'date' => '',
                'message' => '',
            ];

            foreach ($lines as $line) {
                if (str_starts_with($line, 'author ')) {
                    // Извлекаем автора и дату
                    preg_match('/author (.*) <.*> (\d+) ([+-]\d{4})/', $line, $matches);
                    $commitInfo['author'] = $matches[1];
                    $commitInfo['date'] = time::mysql();
                }
                if (empty($line)) {
                    // Следующая строка будет сообщением коммита
                    $commitInfo['message'] = trim(implode("\n", array_slice($lines, array_search($line, $lines) + 1)));
                    break;
                }
            }

            return $commitInfo;
        }

        try {
            return getLastCommitDetails();
        } catch (Exception $e) {
            return self::requestGetLastCommitData();
        }
    }

    private static function requestGetLastCommitData()
    {
        $url = "https://api.github.com/repos/Cannabytes/SphereWeb2/commits";

        // Инициализируем cURL
        $ch = curl_init();

        // Устанавливаем опции
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PHP script'); // GitHub требует User-Agent

        // Выполняем запрос и получаем ответ
        $output = curl_exec($ch);

        // Закрываем cURL
        curl_close($ch);

        // Декодируем JSON ответ
        $commits = json_decode($output, true);

        // Получаем хэш последнего коммита
        if (isset($commits[0]['sha'])) {
            $commit = $commits[0];

            return [
                'hash' => $commit['sha'],
                'author' => $commit['commit']['author']['name'],
                'url' => $commit['html_url'],
                'message' => $commit['commit']['message'],
                'date' => $commit['commit']['author']['date'],
            ];
        } else {
            return null;
        }
    }

}