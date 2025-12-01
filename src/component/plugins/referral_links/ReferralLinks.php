<?php

namespace Ofey\Logan22\component\plugins\referral_links;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\session\session;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class ReferralLinks
{
    private string $pluginName = "referral_links";
    private string $configFilePath;

    public function __construct()
    {
        // Путь к файлу конфигурации в custom папке
        $this->configFilePath = fileSys::get_dir('src/component/plugins/referral_links/config.php');
        
        // Создаем файл конфигурации если его еще нет
        $this->ensureConfigFileExists();
    }

    /**
     * Убедиться, что файл конфигурации существует
     */
    private function ensureConfigFileExists(): void
    {
        if (!file_exists($this->configFilePath)) {
            // Создаем директорию если её нет
            $dir = dirname($this->configFilePath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            // Создаем файл конфигурации с пустым массивом
            $this->saveConfig([]);
        }
    }

    /**
     * Получить конфигурацию из PHP файла
     */
    private function loadConfig(): array
    {
        if (!file_exists($this->configFilePath)) {
            return [];
        }
        
        try {
            $config = include $this->configFilePath;
            return is_array($config) ? $config : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Сохранить конфигурацию в PHP файл
     */
    private function saveConfig(array $config): void
    {
        // Создаем директорию если её нет
        $dir = dirname($this->configFilePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        
        $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        file_put_contents($this->configFilePath, $content);
    }

    /**
     * Администраторская панель управления кастомными ссылками
     */
    public function show(): void
    {
        validation::user_protection("admin");
        
        // Устанавливаем переменные для шаблона
        tpl::addVar('setting', plugin::getSetting($this->pluginName));
        tpl::addVar("pluginName", $this->pluginName);
        tpl::addVar("pluginActive", (bool)plugin::getPluginActive($this->pluginName) ?? false);

        $links = $this->getAllLinks();

        tpl::addVar([
            "getReferralLinks" => $links,
        ]);
        tpl::displayPlugin("/referral_links/tpl/admin.html");
    }

    /**
     * Валидация URL (поддерживает как полные URL, так и относительные пути с query параметрами)
     */
    private function validateUrl(string $url): bool
    {
        // Относительный путь (начинается с /)
        if (strpos($url, '/') === 0) {
            // Разрешаем буквы, цифры, _, -, /, ?, =, &, %, +, . для поддержки query параметров (utm_source и т.д.)
            return preg_match('/^\/[a-zA-Z0-9_\-\/\?=&%+\.]*$/', $url) > 0;
        }

        // Полный URL
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Добавление новой кастомной ссылки
     */
    public function addLink(): void
    {
        validation::user_protection("admin");

        $linkPath = trim($_POST['link_path'] ?? "");
        $redirectUrl = trim($_POST['redirect_url'] ?? "");
        $linkDescription = trim($_POST['description'] ?? "");

        // Валидация
        if (empty($linkPath)) {
            board::error(lang::get_phrase("referral_link_path_empty"));
            return;
        }

        if (empty($redirectUrl)) {
            board::error(lang::get_phrase("referral_redirect_url_empty"));
            return;
        }

        // Очистка пути от слешей (удаляем только ведущие и завершающие слеши)
        $linkPath = trim($linkPath, '/');

        // Проверка что ссылка не содержит недопустимые символы
        // Разрешаем буквы, цифры, _, -, /, ?, =, &, %, +, . чтобы можно было использовать
        // конструкции типа "promo?utm_source=telegram" или "folder/promo" в качестве пути.
        if (!preg_match('/^[a-zA-Z0-9_\-\/\?=&%+\.]+$/', $linkPath)) {
            board::error(lang::get_phrase("referral_invalid_path_format"));
            return;
        }

        // Валидация URL (поддерживает как полные, так и относительные)
        if (!$this->validateUrl($redirectUrl)) {
            board::error(lang::get_phrase("referral_invalid_redirect_url"));
            return;
        }

        // Получаем конфигурацию
        $config = $this->loadConfig();

        // Проверка на дубликаты
        if (isset($config[$linkPath])) {
            board::error(lang::get_phrase("referral_link_already_exists"));
            return;
        }

        // Защита от бесконечного цикла: redirect_url не должен указывать на сам короткий путь
        $publicRelative = '/' . ltrim($linkPath, '/');
        // Извлекаем path+query из redirectUrl для корректного сравнения
        $redirectPathQuery = $redirectUrl;
        if (stripos($redirectUrl, 'http') === 0) {
            $parts = parse_url($redirectUrl);
            $redirectPathQuery = ($parts['path'] ?? '') . (isset($parts['query']) ? '?' . $parts['query'] : '');
        }

        if ($redirectPathQuery === $publicRelative) {
            board::error(lang::get_phrase("referral_redirect_loop"));
            return;
        }

        // Добавляем новую ссылку
        $config[$linkPath] = [
            'redirect_url' => $redirectUrl,
            'description' => $linkDescription,
            'track_for' => $_POST['track_for'] ?? 'all',
            'date_create' => date('Y-m-d H:i:s')
        ];

        $this->saveConfig($config);
        board::success(lang::get_phrase("referral_link_added"));
    }

    /**
     * Обновление кастомной ссылки
     */
    public function updateLink(): void
    {
        validation::user_protection("admin");
        // Если запрос пришёл полностью пустой (нет $_POST и нет сырого тела), игнорируем его.
        $rawInput = @file_get_contents('php://input');
        if (empty($_POST) && ($rawInput === false || $rawInput === '')) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true, 'message' => 'empty request ignored']);
            return;
        }
        


        $oldLinkPath = trim($_POST['old_link_path'] ?? "");
        $linkPath = trim($_POST['link_path'] ?? "");
        $redirectUrl = trim($_POST['redirect_url'] ?? "");
        $linkDescription = trim($_POST['description'] ?? "");

        // Если по какой-то причине скрытое поле старого пути не передано, используем текущий путь
        if (empty($oldLinkPath)) {
            $oldLinkPath = $linkPath;
        }

        if (empty($linkPath)) {
            board::error(lang::get_phrase("referral_link_path_empty"));
            return;
        }

        if (empty($redirectUrl)) {
            board::error(lang::get_phrase("referral_redirect_url_empty"));
            return;
        }

        // Валидация URL (поддерживает как полные, так и относительные)
        if (!$this->validateUrl($redirectUrl)) {
            board::error(lang::get_phrase("referral_invalid_redirect_url"));
            return;
        }

        // Очистка путей
        $oldLinkPath = trim($oldLinkPath, '/');
        $linkPath = trim($linkPath, '/');

        // Получаем конфигурацию
        $config = $this->loadConfig();

        // Проверяем что старая ссылка существует
        if (!isset($config[$oldLinkPath])) {
            board::error(lang::get_phrase("referral_invalid_id"));
            return;
        }

        // Проверка на дубликаты (если путь изменился)
        if ($oldLinkPath !== $linkPath && isset($config[$linkPath])) {
            board::error(lang::get_phrase("referral_link_already_exists"));
            return;
        }

        // Защита от бесконечного цикла: redirect_url не должен указывать на сам короткий путь
        $publicRelative = '/' . ltrim($linkPath, '/');
        $redirectPathQuery = $redirectUrl;
        if (stripos($redirectUrl, 'http') === 0) {
            $parts = parse_url($redirectUrl);
            $redirectPathQuery = ($parts['path'] ?? '') . (isset($parts['query']) ? '?' . $parts['query'] : '');
        }

        if ($redirectPathQuery === $publicRelative) {
            board::error(lang::get_phrase("referral_redirect_loop"));
            return;
        }

        // Обновляем ссылку
        $oldData = $config[$oldLinkPath];
        if ($oldLinkPath !== $linkPath) {
            unset($config[$oldLinkPath]);
        }

        $config[$linkPath] = [
            'redirect_url' => $redirectUrl,
            'description' => $linkDescription,
            'track_for' => $_POST['track_for'] ?? 'all',
            'date_create' => $oldData['date_create'] ?? date('Y-m-d H:i:s')
        ];

        $this->saveConfig($config);
        board::success(lang::get_phrase("referral_link_updated"));
    }

    /**
     * Удаление кастомной ссылки
     */
    public function deleteLink(): void
    {
        validation::user_protection("admin");

        $linkPath = trim($_POST['link_path'] ?? "");

        if (empty($linkPath)) {
            board::error(lang::get_phrase("referral_invalid_id"));
            return;
        }

        // Очистка пути
        $linkPath = trim($linkPath, '/');

        // Получаем конфигурацию
        $config = $this->loadConfig();

        // Проверяем что ссылка существует
        if (!isset($config[$linkPath])) {
            board::error(lang::get_phrase("referral_invalid_id"));
            return;
        }

        // Удаляем ссылку
        unset($config[$linkPath]);
        $this->saveConfig($config);
        board::success(lang::get_phrase("referral_link_deleted"));
    }

    /**
     * Обработка публичного доступа по кастомной ссылке
     * Неавторизованный пользователь будет перенаправлен
     * с сохранением реферала
     */
    public function handleCustomLink($linkPath): void
    {
        // Получаем конфигурацию
        $config = $this->loadConfig();
        // Попытаемся использовать полную строку запроса (path + query), если она сохранена как ключ
        // Например админ мог сохранить "promo?utm_source=telegram" как `link_path`.
        $requested = ltrim($_SERVER['REQUEST_URI'] ?? '', '/');
        // Убираем возможный якорь
        if (($p = strpos($requested, '#')) !== false) {
            $requested = substr($requested, 0, $p);
        }

        // Сначала пробуем точное совпадение полного запроса (без ведущего слеша)
        if (isset($config[$requested])) {
            $link = $config[$requested];
        } elseif (isset($config[$linkPath])) {
            // Затем пробуем ключ, переданный роутером
            $link = $config[$linkPath];
        } else {
            // Ссылка не найдена
            redirect::location("/main");
            return;
        }
        $redirectUrl = $link['redirect_url'] ?? null;

        if (!$redirectUrl) {
            // Неверная конфигурация
            redirect::location("/main");
            return;
        }

        // Проверяем параметры отслеживания
        $trackFor = $link['track_for'] ?? 'all';
        $shouldTrack = false;

        if ($trackFor === 'all') {
            $shouldTrack = true;
        } elseif ($trackFor === 'auth' && user::self()->isAuth()) {
            $shouldTrack = true;
        } elseif ($trackFor === 'unauth' && !user::self()->isAuth()) {
            $shouldTrack = true;
        }

        // Если нужно отслеживать - выполняем отслеживание
        if ($shouldTrack) {
            session::domainViewsCounter($linkPath);
            $_SESSION['HTTP_REFERER'] = $linkPath;
        }

        // Перенаправляем на целевой URL
        redirect::location($redirectUrl);
    }

    /**
     * Получить все кастомные ссылки
     */
    private function getAllLinks(): array
    {
        $config = $this->loadConfig();
        $links = [];

        foreach ($config as $linkPath => $data) {
            $links[] = [
                'link_path' => $linkPath,
                'redirect_url' => $data['redirect_url'] ?? '',
                'description' => $data['description'] ?? '',
                'track_for' => $data['track_for'] ?? 'all',
                'date_create' => $data['date_create'] ?? ''
            ];
        }

        // Сортируем по дате создания (новые первыми)
        usort($links, function($a, $b) {
            return strtotime($b['date_create'] ?? '0') - strtotime($a['date_create'] ?? '0');
        });

        return $links;
    }
}
