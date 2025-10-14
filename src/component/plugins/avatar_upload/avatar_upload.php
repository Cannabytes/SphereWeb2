<?php

namespace Ofey\Logan22\component\plugins\avatar_upload;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\image\caching;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

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
                user::self()->AddHistoryDonate(-$cost, lang::get_phrase('avatar_upload'), "avatar_upload");
            }

            // Обновляем аватар пользователя
            user::self()->setAvatar($filename);

            board::success(lang::get_phrase('avatar_upload_success'));
        } catch (\Exception $e) {
            board::error(lang::get_phrase('avatar_upload_processing_error', $e->getMessage()));
        }
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
        $minSize = 256;
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

        // Изменяем размер до 512x512
        $finalSize = 512;
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
}
