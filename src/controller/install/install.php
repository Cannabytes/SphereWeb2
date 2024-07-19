<?php
/**
 * Класс установщик
 */

namespace Ofey\Logan22\controller\install;

use Exception;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\component\version\version;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\template\tpl;
use PDO;

class install
{

    private static bool $allow_install = true;

    /**
     * Установка, вывод правил, соглашения
     */
    public static function rules(): void
    {
        if (file_exists(fileSys::get_dir('/data/db.php'))) {
            redirect::location("/");
            die();
        }
        $isHtaccess = file_exists(".htaccess");
        if ( ! $isHtaccess) {
            self::$allow_install = false;
        }
        tpl::addVar([
          "need_min_version_php" => version::MIN_PHP_VERSION(),
          "need_mysql_version"   => version::MIN_MYSQL_VERSION(),
          "dir_permissions"      => self::checkFolderPermissions(["/data", "/uploads",]),
          "htaccess"             => $isHtaccess,
          "isLinux"              => "Linux" == php_uname('s'),
          "php_informations"     => [
            [
              "name"  => "PHP_VERSION",
              "get"   => PHP_VERSION,
              "min"   => version::MIN_PHP_VERSION(),
              "allow" => PHP_VERSION >= version::MIN_PHP_VERSION(),
            ],
            [
              "name"  => "upload_max_filesize",
              "get"   => ini_get("upload_max_filesize"),
              "min"   => "2M",
              "allow" => self::compareUploadSizes(ini_get("upload_max_filesize"), "2M"),
            ],

          ],
          "extensions"           => [
            [
              "name"  => "gd",
              "allow" => self::isExtension(extension_loaded('gd') || function_exists('gd_info')),
            ],
            [
              "name"  => "curl",
              "allow" => self::isExtension(extension_loaded('curl')),
            ],
            [
              "name"  => "pdo_mysql",
              "allow" => self::isExtension(extension_loaded('pdo_mysql')),
            ],
            [
              "name"  => "mbstring",
              "allow" => self::isExtension(extension_loaded('mbstring')),
            ],

              //                                      ["name" => "fileinfo",
              //                                       "allow" => self::isExtension(extension_loaded('fileinfo')),
              //                                      ],
          ],
          "allow_install"        => self::$allow_install,
        ]);
        tpl::display("install.html");
    }

    private static function checkFolderPermissions($dir = []): array
    {
        $dirPer = [];
        foreach ($dir as $folder) {
            $permissions      = fileperms(fileSys::get_dir($folder));
            $ownerPermissions = ($permissions & 0o700) >> 6;
            $groupPermissions = ($permissions & 0o070) >> 3;
            $otherPermissions = $permissions & 0o007;
            if ($ownerPermissions >= 7 && $groupPermissions >= 5 && $otherPermissions >= 5) {
                $dirPer[] = [
                  "path" => $folder,
                  "per"  => true,
                ];
            } else {
                if (php_uname('s') == "Windows NT") {
                    $dirPer[] = [
                      "path" => $folder,
                      "per"  => true,
                    ];
                } else {
                    $dirPer[] = [
                      "path" => $folder,
                      "per"  => false,
                    ];
                    self::set_allow_install(false);
                }
            }
        }

        return $dirPer;
    }

    private static function set_allow_install($b): void
    {
        if ( ! self::$allow_install) {
            return;
        }
        if ( ! $b) {
            self::$allow_install = false;
        }
    }

    private static function compareUploadSizes($size1, $size2): bool
    {
        $unit1  = strtoupper(substr($size1, -1));
        $unit2  = strtoupper(substr($size2, -1));
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
        if ( ! $v) {
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
            if (auth::get_access_level() != "admin") {
                echo json_encode([
                  "type"    => "notice",
                  "ok"      => true,
                  "message" => 'Access is denied',
                ]);
                exit;
            }
        }
        $host     = $_POST['host'];
        $port     = $_POST['port'] ?? 3306;
        $user     = $_POST['user'];
        $password = $_POST['password'];
        $name     = $_POST['name'];
        $pdo      = \Ofey\Logan22\model\install\install::test_connect_mysql($host, $port, $user, $password, $name);
        if ($pdo) {
            $ver = $pdo->query("SELECT VERSION()")->fetchColumn();
            $ver = preg_replace('/^(\d+\.\d+).*$/', '$1', $ver);
            $arr = json_encode([
              "type"         => "notice",
              "ok"           => true,
              "mysqlVersion" => $ver,
              'message'      => 'Next install',
            ]);
            echo $arr;
            exit;
        } else {
            $data = [
              'type'    => 'notice',
              'ok'      => false,
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

    public static function add_admin()
    {
        \Ofey\Logan22\model\install\install::add_user_admin();
    }

    public static function startInstall(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $email         = $_POST['email'];
        $nickname      = $_POST['nickname'];
        $adminPassword = password_hash($_POST['adminPassword'], PASSWORD_BCRYPT);

        $host     = $_POST['host'];
        $port     = $_POST['port'] ?? 3306;
        $user     = $_POST['user'];
        $password = $_POST['password'];
        $name     = $_POST['name'];

        /**
         * Создаем проверочный файл
         */
        $filenameCheck = substr(bin2hex(random_bytes(10)), 0, (10)) . ".txt";
        $file          = file_put_contents($filenameCheck, "OK");
        if ($file) {
            server::tokenDisable(true);
            $response = server::send(type::SPHERE_INSTALL, [
              'filename' => $filenameCheck,
            ])->show()->getResponse();
            if ($response['success']) {
                $token = $response['token'];
                file_put_contents(
                  fileSys::get_dir("/data/token.php"),
                  "<?php
const __TOKEN__ = \"$token\";\n"
                );
                unlink($filenameCheck);
            }
        }

        \Ofey\Logan22\model\install\install::saveConfig($host, $port, $user, $password, $name);

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        self::install_sql_struct($pdo, fileSys::get_dir("/uploads/sql/struct/*.sql"));

        $lastCommitData = self::getLastCommitData();
        if ($lastCommitData) {
            $query = $pdo->prepare(
              "INSERT INTO `github_updates` (`sha`, `author`, `url`, `message`, `date`, `date_update`) VALUES (?, ?, ?, ?, ?, ?)"
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

        $ip = $_SERVER['REMOTE_ADDR'];

        $smt = $pdo->prepare("INSERT INTO `users` (`name`, `password`, `email`, `ip`, `access_level`) VALUES (?, ?, ?, ?, ?)",);
        if ($smt->execute([
          $nickname,
          $adminPassword,
          $email,
          $ip,
          'admin',
        ])) {
            echo json_encode([
              "type"     => "notice",
              "ok"       => true,
              "message"  => "Gooooood job!",
              "redirect" => "/main",
            ]);
            exit;
        }
        echo json_encode([
          "type"    => "notice",
          "ok"      => false,
          "message" => "Не удалось создать администратора",
        ]);
        exit;
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

            if ( ! file_exists($objectPath)) {
                throw new Exception("Object file not found: $objectPath");
            }

            // Читаем и распаковываем содержимое файла объекта коммита
            $objectContent = file_get_contents($objectPath);
            $objectContent = gzuncompress($objectContent);

            if ( ! $objectContent) {
                throw new Exception("Unable to decompress object content");
            }

            // Извлекаем данные коммита
            $commitDetails = parseCommitObject($commitHash, $objectContent);

            return $commitDetails;
        }

        function getLastCommitHash($gitDir): string
        {
            $headFile = $gitDir . '/HEAD';
            if ( ! file_exists($headFile)) {
                throw new Exception('HEAD file not found in .git directory');
            }

            $headContent = file_get_contents($headFile);
            if (strpos($headContent, 'ref: ') === 0) {
                $refPath        = trim(str_replace('ref: ', '', $headContent));
                $commitHashFile = $gitDir . '/' . $refPath;
            } else {
                return trim($headContent);
            }

            if ( ! file_exists($commitHashFile)) {
                throw new Exception("Commit hash file not found: $commitHashFile");
            }

            return trim(file_get_contents($commitHashFile));
        }

        function parseCommitObject($commitHash, $objectContent): array
        {
            // Разбиваем данные по строкам
            $lines = explode("\n", $objectContent);

            $commitInfo = [
              'hash'    => $commitHash,
              'author'  => '',
              'date'    => '',
              'message' => '',
            ];

            foreach ($lines as $line) {
                if (str_starts_with($line, 'author ')) {
                    // Извлекаем автора и дату
                    preg_match('/author (.*) <.*> (\d+) ([+-]\d{4})/', $line, $matches);
                    $commitInfo['author'] = $matches[1];
                    $commitInfo['date']   = time::mysql();
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
              'hash'     => $commit['sha'],
              'author'  => $commit['commit']['author']['name'],
              'url'     => $commit['html_url'],
              'message' => $commit['commit']['message'],
              'date'    => $commit['commit']['author']['date'],
            ];
        } else {
            return null;
        }
    }

}