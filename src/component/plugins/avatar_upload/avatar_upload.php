<?php

namespace Ofey\Logan22\component\plugins\avatar_upload;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\sphere\server as SphereServer;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Ofey\Logan22\model\log\logTypes;

class avatar_upload
{
    public function __construct()
    {
        tpl::addVar('setting', plugin::getSetting("avatar_upload"));
        tpl::addVar("pluginName", "avatar_upload");
        tpl::addVar("pluginActive", (bool)plugin::getPluginActive("avatar_upload") ?? false);
        
        // Создаем необходимые директории при инициализации
        installer::createDirectories();
    }

    /**
     * Страница загрузки аватара для пользователя
     */
    public function show()
    {
        if (!plugin::getPluginActive("avatar_upload")) {
            redirect::location("/main");
        }

        if (!user::self()->isAuth()) {
            redirect::location("/main");
        }

        // Проверка наличия GD библиотеки
        if (!extension_loaded('gd')) {
            board::error(lang::get_phrase('avatar_upload_gd_not_installed'));
            redirect::location("/main");
            return;
        }

        $setting = plugin::getSetting("avatar_upload");

        // Rate limit: не более 5 загрузок аватарки в час
        try {
            $this->checkUploadRate();
        } catch (\Exception $e) {
            board::error($e->getMessage());
            return;
        }
        tpl::addVar('setting', $setting);
        tpl::addVar('currentAvatar', user::self()->getAvatar());
        tpl::displayPlugin("/avatar_upload/tpl/upload.html");
    }

    /**
     * Настройки плагина (админ панель)
     */
    public function setting()
    {
        validation::user_protection("admin");
        
        // Проверяем системные требования
        $status = installer::getStatus();
        tpl::addVar('systemStatus', $status);
        
        tpl::displayPlugin("avatar_upload/tpl/setting.html");
    }

    /**
     * Загрузка и обработка аватара
     */
    public function upload()
    {
        if (!user::self()->isAuth()) {
            board::error(lang::get_phrase('avatar_upload_not_authorized'));
            return;
        }

        if (!plugin::getPluginActive("avatar_upload")) {
            board::error(lang::get_phrase('avatar_upload_plugin_disabled'));
            return;
        }

        // Проверка наличия GD библиотеки
        if (!extension_loaded('gd')) {
            board::error(lang::get_phrase('avatar_upload_gd_not_installed'));
            return;
        }

        $setting = plugin::getSetting("avatar_upload");
        // Rate limit: не более 5 загрузок аватарки в час
        try {
            $this->checkUploadRate();
        } catch (\Exception $e) {
            board::error($e->getMessage());
            return;
        }
        
        // Проверяем, платная ли услуга
        if (!($setting['isFree'] ?? true)) {
            $cost = (float)($setting['cost'] ?? 1);
            // Админы могут загружать даже при недостатке средств
            if (!user::self()->isAdmin() && !user::self()->canAffordPurchase($cost)) {
                board::error(lang::get_phrase('avatar_upload_insufficient_funds', $cost));
                return;
            }
        }

        // Проверяем наличие файла
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            board::error(lang::get_phrase('avatar_upload_file_error'));
            return;
        }

        $file = $_FILES['avatar'];
        
        // Валидация файла
        $validation = $this->validateImage($file);
        if ($validation !== true) {
            board::error($validation);
            return;
        }

        // Получаем данные обрезанного изображения из POST
        $cropData = json_decode($_POST['cropData'] ?? '{}', true);
        if (empty($cropData)) {
            board::error(lang::get_phrase('avatar_upload_crop_data_missing'));
            return;
        }

        try {
            // Обрабатываем изображение
            $filename = $this->processImage($file['tmp_name'], $cropData);
            
            // Списываем деньги, если услуга платная
            if (!($setting['isFree'] ?? true)) {
                $cost = (float)($setting['cost'] ?? 0);
                user::self()->donateDeduct($cost);
            }

            // Обновляем аватар пользователя
            user::self()->setAvatar($filename);

            // Логируем успешную загрузку
            $this->logUpload();

            board::success(lang::get_phrase('avatar_upload_success'));
        } catch (\Exception $e) {
            board::error(lang::get_phrase('avatar_upload_processing_error', $e->getMessage()));
        }
    }

    /**
     * Загрузка и обработка видео аватара
     */
    public function uploadVideo(): void
    {
        if (!user::self()->isAuth()) {
            board::error(lang::get_phrase('avatar_upload_not_authorized'));
            return;
        }

        if (!plugin::getPluginActive("avatar_upload")) {
            board::error(lang::get_phrase('avatar_upload_plugin_disabled'));
            return;
        }

        $setting = plugin::getSetting("avatar_upload");

        $cost = 0.0;
        if (!($setting['isFree'] ?? true)) {
            $cost = (float)($setting['cost'] ?? 1);
            if (!user::self()->isAdmin() && !user::self()->canAffordPurchase($cost)) {
                board::error(lang::get_phrase('avatar_upload_insufficient_funds', $cost));
                return;
            }
        }

        if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
            board::error(lang::get_phrase('avatar_upload_video_file_error'));
            return;
        }

        $file = $_FILES['video'];

        $validation = $this->validateVideo($file);
        if (!is_array($validation)) {
            board::error($validation);
            return;
        }

        $start = isset($_POST['start']) ? (float)$_POST['start'] : 0.0;
        $end = isset($_POST['end']) ? (float)$_POST['end'] : 0.0;
        $cropX = isset($_POST['cropX']) ? (int)$_POST['cropX'] : 0;
        $cropY = isset($_POST['cropY']) ? (int)$_POST['cropY'] : 0;
        $cropSize = isset($_POST['cropSize']) ? (int)$_POST['cropSize'] : 0;

        $minDuration = 1.0;
        $maxDuration = 6.0;
        $clipDuration = $end - $start;

        if ($start < 0 || $end <= 0 || $clipDuration < $minDuration || $clipDuration > $maxDuration || $end <= $start) {
            board::error(lang::get_phrase('avatar_upload_video_duration_invalid'));
            return;
        }

    // Accept the same minimum/max as client-side (100..1024)
    $minCrop = 100;
    $maxCrop = 1024;

        if ($cropSize < $minCrop || $cropSize > $maxCrop || $cropX < 0 || $cropY < 0) {
            board::error(lang::get_phrase('avatar_upload_video_crop_invalid'));
            return;
        }

        $fields = [
            'start' => number_format($start, 2, '.', ''),
            'end' => number_format($end, 2, '.', ''),
            'cropX' => $cropX,
            'cropY' => $cropY,
            'cropSize' => $cropSize,
            'link' => 'true',
            'file' => [
                'path' => $file['tmp_name'],
                'name' => $file['name'],
                'type' => $validation['mime'],
            ],
        ];

        $response = SphereServer::sendMultipart('/api/video/process', $fields, 180);
        $responseData = $response->getResponse();

        if ($responseData === null) {
            $error = SphereServer::isError() ?: lang::get_phrase('avatar_upload_video_processing_error', 'sphere api unreachable');
            board::error($error);
            return;
        }

        if (!empty($responseData['json']) && isset($responseData['json']['error'])) {
            $errorMessage = is_array($responseData['json']['error']) && isset($responseData['json']['error']['Message'])
                ? $responseData['json']['error']['Message']
                : $responseData['json']['error'];
            board::error($errorMessage);
            return;
        }

        $videoBinary = null;
        if (!empty($responseData['json']) && ($responseData['json']['success'] ?? false) && !empty($responseData['json']['url'])) {
            $download = SphereServer::sendCustomDownload($responseData['json']['url']);
            if ($download['http_code'] !== 200 || $download['content'] === false) {
                board::error(lang::get_phrase('avatar_upload_video_processing_error', 'download failed'));
                return;
            }
            $videoBinary = $download['content'];
        } elseif (isset($responseData['content_type']) && str_contains((string)$responseData['content_type'], 'video')) {
            $videoBinary = $responseData['body'];
        } else {
            $decoded = json_decode((string)$responseData['body'], true);
            if (is_array($decoded) && isset($decoded['error'])) {
                $message = is_array($decoded['error']) && isset($decoded['error']['Message'])
                    ? $decoded['error']['Message']
                    : $decoded['error'];
                board::error($message);
            } else {
                board::error(lang::get_phrase('avatar_upload_video_processing_error', 'unknown response'));
            }
            return;
        }

        if ($videoBinary === null || $videoBinary === false) {
            board::error(lang::get_phrase('avatar_upload_video_processing_error', 'empty data'));
            return;
        }

        $uploadDir = 'uploads/avatar/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $userId = user::self()->getId();
        $filename = "user_{$userId}.webm";
        $filePath = $uploadDir . $filename;

        if (file_put_contents($filePath, $videoBinary) === false) {
            board::error(lang::get_phrase('avatar_upload_video_processing_error', 'storage error'));
            return;
        }

        if ($cost > 0) {
            user::self()->donateDeduct($cost);
        }

        user::self()->setAvatar($filename);

        // Логируем успешную загрузку
        $this->logUpload();

        board::alert([
            'type' => 'notice',
            'ok' => true,
            'message' => lang::get_phrase('avatar_upload_video_success'),
            'avatarUrl' => user::self()->getAvatar(),
        ]);
    }

    /**
     * Валидация загруженного изображения
     */
    private function validateImage($file): string|bool
    {
        // Проверка размера файла (макс 5MB)
        $maxSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            return lang::get_phrase('avatar_upload_file_too_large');
        }

        // Проверка типа файла
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            return lang::get_phrase('avatar_upload_invalid_file');
        }

        // Проверка, что это действительно изображение
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return lang::get_phrase('avatar_upload_invalid_image');
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];

        // Проверка минимальных размеров
            // Match client-side minimum (100x100)
            $minSize = 100;
        if ($width < $minSize || $height < $minSize) {
            return lang::get_phrase('avatar_upload_min_size');
        }

        // Проверка пропорций (не должно быть слишком вытянутым)
        $aspectRatio = max($width, $height) / min($width, $height);
        if ($aspectRatio > 3) {
            return lang::get_phrase('avatar_upload_aspect_ratio');
        }

        // Дополнительная проверка на валидность изображения с помощью Intervention Image
        try {
            $manager = new ImageManager(new GdDriver());
            $testImage = $manager->read($file['tmp_name']);
            // Если мы можем прочитать изображение, оно валидно
            unset($testImage);
        } catch (\Exception $e) {
            return lang::get_phrase('avatar_upload_corrupted_file');
        }

        return true;
    }

    /**
     * Валидация загруженного видео файла
     */
    private function validateVideo(array $file): array|string
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return lang::get_phrase('avatar_upload_video_invalid_file');
        }

        $maxSize = 200 * 1024 * 1024; // 200MB
        if (($file['size'] ?? 0) > $maxSize) {
            return lang::get_phrase('avatar_upload_video_file_too_large');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
        if ($finfo) {
            finfo_close($finfo);
        }

        if (!$mimeType) {
            return lang::get_phrase('avatar_upload_video_invalid_file');
        }

        $allowed = [
            'video/mp4',
            'video/webm',
            'video/quicktime',
            'video/x-matroska',
            'video/x-msvideo',
            'video/avi',
            'video/mov',
        ];

        if (!in_array($mimeType, $allowed, true)) {
            return lang::get_phrase('avatar_upload_video_invalid_file');
        }

        return [
            'mime' => $mimeType,
        ];
    }

    /**
     * Обработка и сохранение изображения используя Intervention Image v3
     */
    private function processImage($tmpPath, $cropData): string
    {
        // Создаем менеджер изображений с GD драйвером
        $manager = new ImageManager(new GdDriver());
        
        // Загружаем изображение
        $image = $manager->read($tmpPath);

        // Извлекаем параметры обрезки
        $x = (int)($cropData['x'] ?? 0);
        $y = (int)($cropData['y'] ?? 0);
        $width = (int)($cropData['width'] ?? $image->width());
        $height = (int)($cropData['height'] ?? $image->height());

        // Обрезаем изображение
        $image->crop($width, $height, $x, $y);

    // Изменяем размер до 1024x1024
    $finalSize = 1024;
    $image->scale($finalSize, $finalSize);

        // Определяем путь для сохранения
        $uploadDir = 'uploads/avatar/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $userId = user::self()->getId();
        $filename = "user_{$userId}.webp";
        $filepath = $uploadDir . $filename;

        // Сохраняем в формате WebP с качеством 90
        $image->toWebp(90)->save($filepath);

        return $filename;
    }

    /**
     * Получение текущего аватара пользователя
     */
    public function getCurrentAvatar()
    {
        if (!user::self()->isAuth()) {
            board::error(lang::get_phrase('avatar_upload_not_authorized'));
            return;
        }

        try {
            $rand = bin2hex(random_bytes(2)); // 4 hex chars
        } catch (\Exception $e) {
            $rand = substr(md5(uniqid('', true)), 0, 4);
        }

            board::alert([
                'ok' => true,
                'avatar' => user::self()->getAvatar(),
                'avatarUrl' => user::self()->getAvatar()
            ]);
    }

        /**
         * Проверить лимит загрузок аватара (не более 5 в час)
         *
         * @throws \Exception
         */
        private function checkUploadRate(): void
        {
            // Админам проверка не нужна
            if (user::self()->isAdmin()) {
                return;
            }

            // Получаем логи пользователя по типу LOG_CHANGE_AVATAR и считаем за последний час
            $logs = user::self()->getLogs(logTypes::LOG_CHANGE_AVATAR);
            if (empty($logs)) {
                return;
            }

            $oneHourAgo = strtotime('-1 hour');
            $count = 0;
            foreach ($logs as $log) {
                $time = is_numeric($log['time']) ? (int)$log['time'] : strtotime($log['time']);
                if ($time >= $oneHourAgo) {
                    $count++;
                }
            }

            $limit = 5;
            if ($count >= $limit) {
                throw new \Exception(lang::get_phrase('avatar_upload_rate_limit_exceeded', $limit));
            }
        }

        /**
         * Логировать успешную загрузку аватара
         */
        private function logUpload(): void
        {
            try {
                // Используем общий лог системы
                user::self()->addLog(logTypes::LOG_CHANGE_AVATAR, 'LOG_CHANGE_AVATAR');
            } catch (\Throwable $e) {
                // Не критично — пропускаем ошибку логирования
            }
        }
}
