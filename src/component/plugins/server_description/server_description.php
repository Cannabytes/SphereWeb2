<?php
namespace Ofey\Logan22\component\plugins\server_description;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\image\client_icon;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class server_description
{
    private const DATA_DIR = "uploads/server_description";
    
    public function __construct()
    {
        tpl::addVar('setting', plugin::getSetting("server_description"));
        tpl::addVar("pluginName", "server_description");
        tpl::addVar("pluginActive", (bool)plugin::getPluginActive("server_description") ?? false);
        tpl::addVar('serverId', user::self()->getServerId());
        
        $this->ensureDataDir();
    }

    /**
     * Preserve simple line breaks inside inline-only content.
     * If content already contains block-level tags, return as-is.
     */
    private function preserveLineBreaks(string $content): string {
        // Normalize newlines
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        // If there are adjacent paragraph tags, convert them to <br> so single ENTERs are visible
        $content = preg_replace('/<\/p>\s*<p[^>]*>/i', '<br>', $content);

        // If there are plain newlines (textareas), convert them to <br>
        if (strpos($content, "\n") !== false) {
            $content = nl2br($content);
        }

        // Insert <br> between adjacent inline tags to avoid them collapsing
        $content = preg_replace('/(<\/(?:strong|b|em|i|u|span)\>)(\s*)(<(?:strong|b|em|i|u|span)[^>]*>)/i', '$1<br>$3', $content);

        return $content;
    }

    /**
     * Убедиться что директория для хранения данных существует
     */
    private function ensureDataDir(): void
    {
        if (!is_dir(self::DATA_DIR)) {
            @mkdir(self::DATA_DIR, 0777, true);
        }
    }

    /**
     * Получить путь к файлу данных для сервера
     */
    private function getDataFilePath(): string
    {
        $serverId = user::self()->getServerId() ?? 0;
        return self::DATA_DIR . "/server_{$serverId}.json";
    }

    /**
     * Страница администратора
     */
    public function setting()
    {
        validation::user_protection("admin");
        
        $categories = $this->getCategories();
        $sections = $this->getSections();
        $lang = \Ofey\Logan22\controller\config\config::load()->lang()->lang_user_default();
        
        // Форматируем категории
        foreach ($categories as &$category) {
            if (isset($category['lang_name'][$lang]) && !empty($category['lang_name'][$lang])) {
                $category['name'] = $category['lang_name'][$lang];
            } elseif (isset($category['lang_name']['en']) && !empty($category['lang_name']['en'])) {
                $category['name'] = $category['lang_name']['en'];
            }
        }
        
        // Форматируем разделы
        foreach ($sections as &$section) {
            if (isset($section['lang_name'][$lang]) && !empty($section['lang_name'][$lang])) {
                $section['name'] = $section['lang_name'][$lang];
            } elseif (isset($section['lang_name']['en']) && !empty($section['lang_name']['en'])) {
                $section['name'] = $section['lang_name']['en'];
            }
        }
        
        // Получаем список доступных языков
        $languages = \Ofey\Logan22\controller\config\config::load()->lang()->getAllowLang();
        $langCodes = [];
        foreach ($languages as $langObj) {
            $langCodes[] = $langObj->getLang();
        }
        
        tpl::addVar([
            'categories' => $categories,
            'sections' => $sections,
            'languages' => $langCodes,
        ]);
        tpl::displayPlugin("server_description/tpl/setting.html");
    }

    /**
     * Получить все категории для текущего сервера
     */
    private function getCategories(): array
    {
        try {
            $filePath = $this->getDataFilePath();
            
            if (!file_exists($filePath)) {
                return [];
            }
            
            $content = file_get_contents($filePath);
            $data = json_decode($content, true);
            
            if (!isset($data['categories']) || !is_array($data['categories'])) {
                return [];
            }
            
            // Сортировка по sort
            usort($data['categories'], function($a, $b) {
                return ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0);
            });
            
            return $data['categories'];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Получить все разделы для текущего сервера
     */
    private function getSections(): array
    {
        try {
            $filePath = $this->getDataFilePath();
            
            if (!file_exists($filePath)) {
                return [];
            }
            
            $content = file_get_contents($filePath);
            $data = json_decode($content, true);
            
            if (!isset($data['sections']) || !is_array($data['sections'])) {
                return [];
            }
            
            // Сортировка по sort
            usort($data['sections'], function($a, $b) {
                return ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0);
            });
            
            return $data['sections'];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Получить все данные сервера
     */
    private function getAllData(): array
    {
        try {
            $filePath = $this->getDataFilePath();
            
            if (!file_exists($filePath)) {
                return ['sections' => []];
            }
            
            $content = file_get_contents($filePath);
            return json_decode($content, true) ?: ['sections' => []];
        } catch (\Exception $e) {
            return ['sections' => []];
        }
    }

    /**
     * Сохранить все данные сервера
     */
    private function saveAllData(array $data): bool
    {
        try {
            $filePath = $this->getDataFilePath();
            $this->ensureDataDir();
            
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            return file_put_contents($filePath, $json) !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Сохранить настройки плагина или контент раздела
     */
    public function save(): void
    {
        validation::user_protection("admin");
        
        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $langData = $_POST['lang'] ?? [];
        $langNames = $_POST['lang_name'] ?? [];
        
        try {
            $data = $this->getAllData();
            $found = false;
            
            foreach ($data['sections'] as & $section) {
                if ($section['id'] == $id) {
                    $section['name'] = $name;
                    $section['lang_name'] = $langNames;
                    $section['lang_description'] = $langData;
                    $section['updated_at'] = date('Y-m-d H:i:s');
                    $found = true;
                    break;
                }
            }
            
            if ($found) {
                if ($this->saveAllData($data)) {
                    board::success(lang::get_phrase('Settings saved successfully'));
                } else {
                    board::error(lang::get_phrase('Error saving data'));
                }
            } else {
                board::success(lang::get_phrase('Settings saved successfully'));
            }
        } catch (\Exception $e) {
            board::error(lang::get_phrase('An error occurred while saving settings'));
        }
    }

    public function addSectionPage(): void
    {
        validation::user_protection("admin");
        $languages = \Ofey\Logan22\controller\config\config::load()->lang()->getAllowLang();
        $categories = $this->getCategories();
        $lang = \Ofey\Logan22\controller\config\config::load()->lang()->lang_user_default();
        
        // Форматируем категории
        foreach ($categories as &$category) {
            if (isset($category['lang_name'][$lang]) && !empty($category['lang_name'][$lang])) {
                $category['name'] = $category['lang_name'][$lang];
            } elseif (isset($category['lang_name']['en']) && !empty($category['lang_name']['en'])) {
                $category['name'] = $category['lang_name']['en'];
            }
        }
        
        tpl::addVar([
            'languages' => $languages,
            'categories' => $categories,
        ]);
        tpl::displayPlugin("server_description/tpl/add_section.html");
    }

    /**
     * Добавить новый раздел
     */
    public function addSection(): void
    {
        validation::user_protection("admin");
        
        $names = $_POST['names'] ?? [];
        $categoryId = $_POST['categoryId'] ?? 0;
        $defaultName = $names['en'] ?? $names['ru'] ?? reset($names) ?? '';
        
        if (empty($defaultName)) {
            board::error(lang::get_phrase('Section name is required'));
            return;
        }
        
        if (empty($categoryId)) {
            board::error(lang::get_phrase('Category is required'));
            return;
        }
        
        // Проверяем что категория существует
        $categoryExists = false;
        $categories = $this->getCategories();
        foreach ($categories as $cat) {
            if ($cat['id'] == $categoryId) {
                $categoryExists = true;
                break;
            }
        }
        
        if (!$categoryExists) {
            board::error(lang::get_phrase('Category not found'));
            return;
        }
        
        try {
            $data = $this->getAllData();
            
            // Получаем максимальный ID
            $maxId = 0;
            foreach ($data['sections'] as $section) {
                if (isset($section['id']) && $section['id'] > $maxId) {
                    $maxId = $section['id'];
                }
            }
            
            // Получаем максимальный sort для этой категории
            $maxSort = 0;
            foreach ($data['sections'] as $section) {
                if (($section['category_id'] ?? 0) == $categoryId && isset($section['sort'])) {
                    $maxSort = max($maxSort, $section['sort']);
                }
            }
            
            $newSection = [
                'id' => $maxId + 1,
                'category_id' => (int)$categoryId,
                'name' => $defaultName,
                'lang_name' => $names,
                'description' => '',
                'sort' => $maxSort + 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'subsections' => [],
                'lang_description' => []
            ];
            
            $data['sections'][] = $newSection;
            
            if ($this->saveAllData($data)) {
                @header('Content-Type: application/json');
                echo json_encode(['redirect' => "/admin/plugin/server_description/edit/" . $newSection['id']]);
                exit;
            } else {
                board::error(lang::get_phrase('Error adding section'));
            }
        } catch (\Exception $e) {
            board::error(lang::get_phrase('Error adding section') . ': ' . $e->getMessage());
        }
    }

    /**
     * Редактировать раздел
     */
    public function editSection(): void
    {
        validation::user_protection("admin");
        
        $sectionId = $_POST['sectionId'] ?? 0;
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $langNames = $_POST['lang_name'] ?? [];
        
        if (empty($name)) {
            board::error(lang::get_phrase('Section name is required'));
        }
        
        try {
            $data = $this->getAllData();
            
            $found = false;
            foreach ($data['sections'] as & $section) {
                if ($section['id'] == $sectionId) {
                    $section['name'] = $name;
                    $section['lang_name'] = $langNames;
                    $section['description'] = $description;
                    $section['updated_at'] = date('Y-m-d H:i:s');
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                board::error(lang::get_phrase('Section not found'));
            }
            
            if ($this->saveAllData($data)) {
                board::success(lang::get_phrase('Section updated successfully'));
            } else {
                board::error(lang::get_phrase('Error updating section'));
            }
        } catch (\Exception $e) {
            board::error(lang::get_phrase('Error updating section') . ': ' . $e->getMessage());
        }
    }

    /**
     * Удалить раздел
     */
    public function deleteSection(): void
    {
        validation::user_protection("admin");
        
        $sectionId = $_POST['sectionId'] ?? 0;
        
        try {
            $data = $this->getAllData();
            
            $sectionToDelete = null;
            $found = false;
            
            foreach ($data['sections'] as $key => $section) {
                if ($section['id'] == $sectionId) {
                    $sectionToDelete = $section;
                    unset($data['sections'][$key]);
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                board::error(lang::get_phrase('Section not found'));
                return;
            }
            
            // Удаляем все изображения связанные с разделом
            if ($sectionToDelete) {
                // Извлекаем и удаляем изображения из контента для всех языков
                if (!empty($sectionToDelete['lang_description']) && is_array($sectionToDelete['lang_description'])) {
                    foreach ($sectionToDelete['lang_description'] as $html) {
                        if (is_string($html)) {
                            $imagesToDelete = $this->extractImagesToDelete($html);
                            $this->deleteImageFiles($imagesToDelete);
                        }
                    }
                }
            }
            
            // Переиндексируем массив
            $data['sections'] = array_values($data['sections']);
            
            if ($this->saveAllData($data)) {
                board::success(lang::get_phrase('Section deleted successfully'));
            } else {
                board::error(lang::get_phrase('Error deleting section'));
            }
        } catch (\Exception $e) {
            board::error(lang::get_phrase('Error deleting section') . ': ' . $e->getMessage());
        }
    }

    /**
     * Добавить новую категорию
     */
    public function addCategory(): void
    {
        validation::user_protection("admin");
        
        $names = $_POST['names'] ?? [];
        $defaultName = $names['en'] ?? $names['ru'] ?? reset($names) ?? '';
        
        if (empty($defaultName)) {
            board::error(lang::get_phrase('Category name is required'));
            return;
        }
        
        try {
            $data = $this->getAllData();
            
            if (!isset($data['categories'])) {
                $data['categories'] = [];
            }
            
            $maxId = 0;
            foreach ($data['categories'] as $category) {
                if (isset($category['id']) && $category['id'] > $maxId) {
                    $maxId = $category['id'];
                }
            }
            
            $maxSort = 0;
            foreach ($data['categories'] as $category) {
                if (isset($category['sort']) && $category['sort'] > $maxSort) {
                    $maxSort = $category['sort'];
                }
            }
            
            $newCategory = [
                'id' => $maxId + 1,
                'name' => $defaultName,
                'lang_name' => $names,
                'sort' => $maxSort + 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            
            $data['categories'][] = $newCategory;
            
            if ($this->saveAllData($data)) {
                board::success(lang::get_phrase('Category added successfully'));
            } else {
                board::error(lang::get_phrase('Error adding category'));
            }
        } catch (\Exception $e) {
            board::error(lang::get_phrase('Error adding category') . ': ' . $e->getMessage());
        }
    }

    /**
     * Редактировать категорию
     */
    public function editCategory(): void
    {
        validation::user_protection("admin");
        
        $categoryId = $_POST['categoryId'] ?? 0;
        $names = $_POST['names'] ?? [];
        $defaultName = $names['en'] ?? $names['ru'] ?? reset($names) ?? '';
        
        if (empty($defaultName)) {
            board::error(lang::get_phrase('Category name is required'));
            return;
        }
        
        try {
            $data = $this->getAllData();
            
            if (!isset($data['categories'])) {
                $data['categories'] = [];
            }
            
            $found = false;
            foreach ($data['categories'] as & $category) {
                if ($category['id'] == $categoryId) {
                    $category['name'] = $defaultName;
                    $category['lang_name'] = $names;
                    $category['updated_at'] = date('Y-m-d H:i:s');
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                board::error(lang::get_phrase('Category not found'));
                return;
            }
            
            if ($this->saveAllData($data)) {
                board::success(lang::get_phrase('Category updated successfully'));
            } else {
                board::error(lang::get_phrase('Error updating category'));
            }
        } catch (\Exception $e) {
            board::error(lang::get_phrase('Error updating category') . ': ' . $e->getMessage());
        }
    }

    /**
     * Удалить категорию
     */
    public function deleteCategory(): void
    {
        validation::user_protection("admin");
        
        $categoryId = $_POST['categoryId'] ?? 0;
        
        try {
            $data = $this->getAllData();
            
            if (!isset($data['categories'])) {
                board::error(lang::get_phrase('Category not found'));
                return;
            }
            
            // Удаляем категорию
            $found = false;
            foreach ($data['categories'] as $key => $category) {
                if ($category['id'] == $categoryId) {
                    unset($data['categories'][$key]);
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                board::error(lang::get_phrase('Category not found'));
                return;
            }
            
            // Переиндексируем массив категорий
            $data['categories'] = array_values($data['categories']);
            
            // Удаляем разделы этой категории
            if (isset($data['sections'])) {
                $data['sections'] = array_filter($data['sections'], function($section) use ($categoryId) {
                    return ($section['category_id'] ?? 0) != $categoryId;
                });
                $data['sections'] = array_values($data['sections']);
            }
            
            if ($this->saveAllData($data)) {
                board::success(lang::get_phrase('Category deleted successfully'));
            } else {
                board::error(lang::get_phrase('Error deleting category'));
            }
        } catch (\Exception $e) {
            board::error(lang::get_phrase('Error deleting category') . ': ' . $e->getMessage());
        }
    }

    /**
     * Переместить категорию (сортировка)
     */
    public function moveCategory(): void
    {
        validation::user_protection("admin");
        
        $id = $_POST['id'] ?? 0;
        $direction = $_POST['direction'] ?? '';
        
        $data = $this->getAllData();
        
        if (!isset($data['categories'])) {
            return;
        }
        
        $categories = $data['categories'];
        
        $index = -1;
        foreach ($categories as $k => $c) {
            if ($c['id'] == $id) {
                $index = $k;
                break;
            }
        }
        
        if ($index != -1) {
            if ($direction == 'up' && $index > 0) {
                $tmp = $categories[$index];
                $categories[$index] = $categories[$index - 1];
                $categories[$index - 1] = $tmp;
            } elseif ($direction == 'down' && $index < count($categories) - 1) {
                $tmp = $categories[$index];
                $categories[$index] = $categories[$index + 1];
                $categories[$index + 1] = $tmp;
            }
            
            // Обновляем sort
            foreach ($categories as $k => & $c) {
                $c['sort'] = $k + 1;
            }
            
            $data['categories'] = $categories;
            
            if ($this->saveAllData($data)) {
                board::success(lang::get_phrase('Sorted successfully'));
            } else {
                board::error(lang::get_phrase('Error sorting'));
            }
        }
    }

    /**
     * Показать публичную страницу описания сервера по имени
     */
    public function view(string $serverName = ""): void
    {
        $categories = $this->getCategories();
        $sections = $this->getSections();
        $lang = \Ofey\Logan22\controller\config\config::load()->lang()->lang_user_default();

        // Форматируем категории
        foreach ($categories as &$category) {
            if (isset($category['lang_name'][$lang]) && !empty($category['lang_name'][$lang])) {
                $category['name'] = $category['lang_name'][$lang];
            } elseif (isset($category['lang_name']['en']) && !empty($category['lang_name']['en'])) {
                $category['name'] = $category['lang_name']['en'];
            }
            $category['sections'] = [];
        }

        foreach ($sections as &$section) {
            // Список доступных языков для этого раздела для флагов в навигации
            $section['available_languages'] = isset($section['lang_description']) ? array_keys(array_filter($section['lang_description'], function($v) { return !empty($v); })) : [];

            // Название на выбранном языке
            if (isset($section['lang_name'][$lang]) && !empty($section['lang_name'][$lang])) {
                $section['name'] = $section['lang_name'][$lang];
            } elseif (isset($section['lang_name']['en']) && !empty($section['lang_name']['en'])) {
                $section['name'] = $section['lang_name']['en'];
            }

            // Основной контент на выбранном языке
            $content = "";
            if (isset($section['lang_description'][$lang]) && !empty($section['lang_description'][$lang])) {
                $content = $section['lang_description'][$lang];
            } elseif (isset($section['lang_description']['en']) && !empty($section['lang_description']['en'])) {
                $content = $section['lang_description']['en'];
            }

            $section['description'] = $this->processBBCode($content);
            
            // Добавляем раздел в его категорию
            $categoryId = $section['category_id'] ?? 0;
            foreach ($categories as &$cat) {
                if ($cat['id'] == $categoryId) {
                    $cat['sections'][] = $section;
                    break;
                }
            }
        }

        tpl::addVar([
            'serverName' => $serverName,
            'categories' => $categories,
            'sections' => $sections,
        ]);

        tpl::displayPlugin("server_description/tpl/index.html");
    }

    /**
     * Получить изображение грейда предмета
     */
    private function getGradeImg(string $gradeType): string
    {
        $dirGrade = "/uploads/images/grade";
        
        // Преобразуем к строке и очищаем
        $type = trim((string)($gradeType ?? ''));
        if (empty($type)) {
            return '';
        }
        
        // Приводим к нижнему регистру
        $type = strtolower($type);
        
        // Нормализация - убираем все кроме букв и цифр
        $type = preg_replace('/[^a-z0-9]/', '', $type);
        
        if (empty($type)) {
            return '';
        }
        
        // Таблица соответствия кристаллов
        $gradeMap = [
            'd' => ['path' => 'd.png', 'size' => '20px'],
            '1' => ['path' => 'd.png', 'size' => '20px'],
            'c' => ['path' => 'c.png', 'size' => '20px'],
            '2' => ['path' => 'c.png', 'size' => '20px'],
            'b' => ['path' => 'b.png', 'size' => '20px'],
            '3' => ['path' => 'b.png', 'size' => '20px'],
            'a' => ['path' => 'a.png', 'size' => '20px'],
            '4' => ['path' => 'a.png', 'size' => '20px'],
            's' => ['path' => 's.png', 'size' => '20px'],
            '5' => ['path' => 's.png', 'size' => '20px'],
            's80' => ['path' => 's80.png', 'size' => '40px'],
            '6' => ['path' => 's80.png', 'size' => '40px'],
            's84' => ['path' => 's84.png', 'size' => '40px'],
            '7' => ['path' => 's84.png', 'size' => '40px'],
            'r' => ['path' => 'r.png', 'size' => '20px'],
            '8' => ['path' => 'r.png', 'size' => '20px'],
            'r95' => ['path' => 'r95.png', 'size' => '40px'],
            '9' => ['path' => 'r95.png', 'size' => '40px'],
            'r99' => ['path' => 'r99.png', 'size' => '40px'],
            '10' => ['path' => 'r99.png', 'size' => '40px'],
            'r110' => ['path' => 'r110.png', 'size' => '40px'],
            '11' => ['path' => 'r110.png', 'size' => '40px'],
        ];
        
        if (isset($gradeMap[$type])) {
            $grade = $gradeMap[$type];
            $size = $grade['size'];
            return "<img src='{$dirGrade}/" . $grade['path'] . "' style='width:" . $size . ";height:" . $size . ";' alt='Grade'>";
        }
        
        return '';
    }

    /**
     * Форматировать tooltip для предмета
     */
    private function formatItemTooltip($item): string
    {
        $name = htmlspecialchars($item->getItemName() ?? 'Unknown', ENT_QUOTES, 'UTF-8');
        $addName = $item->getAddName() ? ' +' . htmlspecialchars($item->getAddName(), ENT_QUOTES, 'UTF-8') : '';
        $description = htmlspecialchars($item->getDescription() ?? '', ENT_QUOTES, 'UTF-8');
        $type = htmlspecialchars($item->getType() ?? '', ENT_QUOTES, 'UTF-8');
        $price = $item->getPrice() ?? 0;
        $bodyPart = htmlspecialchars($item->getBodyPart() ?? '', ENT_QUOTES, 'UTF-8');
        
        $html = '<div class="item-tooltip">';
        $html .= '<div style="font-weight: bold; margin-bottom: 8px;">' . $name . $addName . '</div>';
        
        if ($description) {
            $html .= '<div style="font-size: 12px; margin-bottom: 8px; color: #999;">' . $description . '</div>';
        }
        
        if ($type) {
            $html .= '<div style="font-size: 12px;"><strong>Type:</strong> ' . $type . '</div>';
        }
        
        if ($bodyPart && $bodyPart !== 'none') {
            $html .= '<div style="font-size: 12px;"><strong>Slot:</strong> ' . $bodyPart . '</div>';
        }
        
        if ($price > 0) {
            $html .= '<div style="font-size: 12px;"><strong>Price:</strong> ' . number_format($price) . 'pp</div>';
        }
        
        // Характеристики предмета
        $stats = $item->getStats();
        if (!empty($stats) && is_array($stats)) {
            $html .= '<div style="margin-top: 8px; border-top: 1px solid #444; padding-top: 8px;">';
            foreach ($stats as $stat => $value) {
                if ($value != 0) {
                    $html .= '<div style="font-size: 12px;"><strong>' . htmlspecialchars($stat, ENT_QUOTES, 'UTF-8') . ':</strong> ' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</div>';
                }
            }
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Форматировать HTML для предмета
     */
    /**
     * Форматировать HTML для предмета
     */
    private function formatItemHtml(int $itemId): string
    {
        try {
            $item = client_icon::get_item_info($itemId, false, false);
            if (!$item) {
                return '<span class="text-muted">[Item ' . $itemId . ' not found]</span>';
            }
            
            $icon = $item->getIcon();
            $name = htmlspecialchars($item->getItemName(), ENT_QUOTES, 'UTF-8');
            $addName = $item->getAddName() ? ' +' . htmlspecialchars($item->getAddName(), ENT_QUOTES, 'UTF-8') : '';
            $grade = (string)($item->getCrystalType() ?? '');
            $gradeImg = $this->getGradeImg($grade);
            $tooltip = $this->formatItemTooltip($item);
            $tooltipEscaped = htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8');
            
            $uniqueId = 'item-ref-' . $itemId . '-' . substr(md5(uniqid()), 0, 6);
            
            // Если grade есть, показываем изображение; иначе пусто
            $gradeContent = '';
            if (!empty($gradeImg)) {
                $gradeContent = $gradeImg;
            } elseif (!empty($grade)) {
                // Fallback: показываем текст если изображение не найдено но grade есть
                $gradeContent = '<span style="font-weight:bold;color:#666;font-size:11px;padding:2px 4px;background:#eee;border-radius:3px;">' . strtoupper(htmlspecialchars($grade, ENT_QUOTES, 'UTF-8')) . '</span>';
            }
            
            return '<span class="item-reference" id="' . $uniqueId . '" data-tippy-content="' . $tooltipEscaped . '" 
                    style="display: inline-flex; align-items: center; gap: 6px; padding: 2px 6px; border-radius: 4px; transition: all 0.2s; cursor: pointer;"
                    onmouseover="this.style.backgroundColor=\'rgba(100,150,255,0.1)\'; this.style.boxShadow=\'0 0 8px rgba(100,150,255,0.3)\';"
                    onmouseout="this.style.backgroundColor=\'transparent\'; this.style.boxShadow=\'none\';">
                    ' . $gradeContent . '
                    <img src="' . $icon . '" alt="' . $name . '" style="width: 32px; height: 32px; border-radius: 3px; vertical-align: middle;">
                    <span style="color: inherit;">' . $name . $addName . '</span>
                </span>';
        } catch (\Exception $e) {
            return '<span class="text-muted">[Item ' . $itemId . ' error]</span>';
        }
    }

    /**
     * Форматировать tooltip для скилла
     */
    private function formatSkillTooltip($skill): string
    {
        // Проверяем разные варианты имени скилла
        $skillName = $skill['name_ru'] ?? $skill['name_en'] ?? $skill['name'] ?? 'Unknown Skill';
        $name = htmlspecialchars($skillName, ENT_QUOTES, 'UTF-8');
        $skillId = $skill['skill_id'] ?? 'N/A';
        
        $html = '<div class="skill-tooltip">';
        $html .= '<div style="font-weight: bold; margin-bottom: 8px;">' . $name . '</div>';
        $html .= '<div style="font-size: 12px;"><strong>ID:</strong> ' . htmlspecialchars($skillId, ENT_QUOTES, 'UTF-8') . '</div>';
        
        // Если есть дополнительные данные про скилл
        if (!empty($skill['level_max'])) {
            $html .= '<div style="font-size: 12px;"><strong>Max Level:</strong> ' . htmlspecialchars($skill['level_max'], ENT_QUOTES, 'UTF-8') . '</div>';
        }
        
        if (!empty($skill['enchantLevel'])) {
            $html .= '<div style="font-size: 12px;"><strong>Enchant Level:</strong> ' . htmlspecialchars($skill['enchantLevel'], ENT_QUOTES, 'UTF-8') . '</div>';
        }
        
        if (!empty($skill['operateType'])) {
            $html .= '<div style="font-size: 12px;"><strong>Type:</strong> ' . htmlspecialchars($skill['operateType'], ENT_QUOTES, 'UTF-8') . '</div>';
        }
        
        if (!empty($skill['mpConsume'])) {
            $html .= '<div style="font-size: 12px; margin-top: 8px;"><strong>MP Cost:</strong> ' . htmlspecialchars($skill['mpConsume'], ENT_QUOTES, 'UTF-8') . '</div>';
        }
        
        if (!empty($skill['castRange'])) {
            $html .= '<div style="font-size: 12px;"><strong>Range:</strong> ' . htmlspecialchars($skill['castRange'], ENT_QUOTES, 'UTF-8') . '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Форматировать HTML для скилла
     */
    private function formatSkillHtml(int $skillId): string
    {
        try {
            $skill = client_icon::get_skill_info($skillId, false);
            if (!$skill) {
                return '<span class="text-muted">[Skill ' . $skillId . ' not found]</span>';
            }
            
            // Проверяем разные варианты имени скилла
            $skillName = $skill['name_ru'] ?? $skill['name_en'] ?? $skill['name'] ?? 'Unknown';
            
            if ($skillName === 'NoSkillName' || $skillName === 'Unknown') {
                return '<span class="text-muted">[Skill ' . $skillId . ' not found]</span>';
            }
            
            $icon = $skill['icon'];
            $name = htmlspecialchars($skillName, ENT_QUOTES, 'UTF-8');
            $tooltip = $this->formatSkillTooltip($skill);
            $tooltipEscaped = htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8');
            
            $uniqueId = 'skill-ref-' . $skillId . '-' . substr(md5(uniqid()), 0, 6);
            
            return '<span class="skill-reference" id="' . $uniqueId . '" data-tippy-content="' . $tooltipEscaped . '" 
                    style="display: inline-flex; align-items: center; gap: 6px; padding: 2px 6px; border-radius: 4px; transition: all 0.2s; cursor: pointer;"
                    onmouseover="this.style.backgroundColor=\'rgba(100,200,100,0.1)\'; this.style.boxShadow=\'0 0 8px rgba(100,200,100,0.3)\';"
                    onmouseout="this.style.backgroundColor=\'transparent\'; this.style.boxShadow=\'none\';">
                    <img src="' . $icon . '" alt="' . $name . '" style="width: 32px; height: 32px; border-radius: 3px; vertical-align: middle;">
                    <span style="color: inherit;">' . $name . '</span>
                </span>';
        } catch (\Exception $e) {
            return '<span class="text-muted">[Skill ' . $skillId . ' error]</span>';
        }
    }

    /**
     * Парсер BB-кодов в HTML
     */
    private function processBBCode(string $text): string
    {
        $patterns = [
            // Item Reference - {ITEM ID=57}
            '/\{ITEM\s+ID=(\d+)\}/is' => function($m) {
                return $this->formatItemHtml((int)$m[1]);
            },
            // Skill Reference - {SKILL ID=1024}
            '/\{SKILL\s+ID=(\d+)\}/is' => function($m) {
                return $this->formatSkillHtml((int)$m[1]);
            },
            // Card Row Block - контейнер для нескольких карточек
            '/\[card-row](.*?)\[\/card-row]/is' => function($m) {
                return '<div class="row g-3">' . $m[1] . '</div>';
            },
            // Card Block with all attributes (title + class + bg)
            '/\[card\s+title=(["\'])(.*?)\1\s+class=(["\'])(.*?)\3\s+bg=(["\'])(.*?)\5](.*?)\[\/card]/is' => function($m) {
                $title = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
                $class = htmlspecialchars($m[4], ENT_QUOTES, 'UTF-8');
                $bg = htmlspecialchars($m[6], ENT_QUOTES, 'UTF-8');
                $content = $m[7];
                $content = $this->preserveLineBreaks($content);
                return '<div class="' . $class . '"><div class="card custom-card ' . $bg . '"><div class="card-header"><div class="card-title">' . $title . '</div></div><div class="card-body"><div class="text-break mb-0">' . $content . '</div></div></div></div>';
            },
            // Card Block with title + class (no bg)
            '/\[card\s+title=(["\'])(.*?)\1\s+class=(["\'])(.*?)\3](.*?)\[\/card]/is' => function($m) {
                $title = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
                $class = htmlspecialchars($m[4], ENT_QUOTES, 'UTF-8');
                $content = $m[5];
                $content = $this->preserveLineBreaks($content);
                return '<div class="' . $class . '"><div class="card custom-card"><div class="card-header"><div class="card-title">' . $title . '</div></div><div class="card-body"><div class="text-break mb-0">' . $content . '</div></div></div></div>';
            },
            // Card Block with title + bg (no class)
            '/\[card\s+title=(["\'])(.*?)\1\s+bg=(["\'])(.*?)\3](.*?)\[\/card]/is' => function($m) {
                $title = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
                $bg = htmlspecialchars($m[4], ENT_QUOTES, 'UTF-8');
                $content = $m[5];
                $content = $this->preserveLineBreaks($content);
                return '<div class="card custom-card ' . $bg . '"><div class="card-header"><div class="card-title">' . $title . '</div></div><div class="card-body"><div class="text-break mb-0">' . $content . '</div></div></div>';
            },
            // Card Block with class + bg (no title)
            '/\[card\s+class=(["\'])(.*?)\1\s+bg=(["\'])(.*?)\3](.*?)\[\/card]/is' => function($m) {
                $class = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
                $bg = htmlspecialchars($m[4], ENT_QUOTES, 'UTF-8');
                $content = $m[5];
                $content = $this->preserveLineBreaks($content);
                return '<div class="' . $class . '"><div class="card custom-card ' . $bg . '"><div class="card-body"><div class="text-break mb-0">' . $content . '</div></div></div></div>';
            },
            // Card Block with title only
            '/\[card\s+title=(["\'])(.*?)\1](.*?)\[\/card]/is' => function($m) {
                $title = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
                $content = $m[3];
                $content = $this->preserveLineBreaks($content);
                return '<div class="card custom-card"><div class="card-header"><div class="card-title">' . $title . '</div></div><div class="card-body"><div class="text-break mb-0">' . $content . '</div></div></div>';
            },
            // Card Block with class only
            '/\[card\s+class=(["\'])(.*?)\1](.*?)\[\/card]/is' => function($m) {
                $class = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
                $content = $m[3];
                $content = $this->preserveLineBreaks($content);
                return '<div class="' . $class . '"><div class="card custom-card"><div class="card-body"><div class="text-break mb-0">' . $content . '</div></div></div></div>';
            },
            // Card Block with bg only
            '/\[card\s+bg=(["\'])(.*?)\1](.*?)\[\/card]/is' => function($m) {
                $bg = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
                $content = $m[3];
                $content = $this->preserveLineBreaks($content);
                return '<div class="card custom-card ' . $bg . '"><div class="card-body"><div class="text-break mb-0">' . $content . '</div></div></div>';
            },
            // Simple Card Block without attributes
            '/\[card](.*?)\[\/card]/is' => function($m) {
                $content = $m[1];
                $content = $this->preserveLineBreaks($content);
                return '<div class="card custom-card"><div class="card-body"><div class="text-break mb-0">' . $content . '</div></div></div>';
            },
            '/\[tabs](.*?)\[\/tabs]/is' => function($m) {
                $uniqueId = 'tabs_' . substr(md5(uniqid()), 0, 6);
                preg_match_all('/\[tab\s+title=["\'](.*?)["\']](.*?)\[\/tab]/is', $m[1], $tabs);
                if (empty($tabs[0])) return "";
                
                $nav = '<div class="wiki-tabs-nav">';
                $content = '<div class="wiki-tabs-content">';
                foreach ($tabs[1] as $i => $title) {
                    $active = ($i === 0) ? 'active' : '';
                    $nav .= '<div class="wiki-tab-link '.$active.'" data-target="'.$uniqueId.'_'.$i.'">'.$title.'</div>';
                    $content .= '<div class="wiki-tab-pane '.$active.'" id="'.$uniqueId.'_'.$i.'">'.$tabs[2][$i].'</div>';
                }
                $nav .= '</div>';
                $content .= '</div>';
                return '<div class="wiki-tabs-container">' . $nav . $content . '</div>';
            },
            '/\[spoiler\s+title=["\'](.*?)["\']](.*?)\[\/spoiler]/is' => '<div class="spoiler-container"><div class="spoiler-title">$1</div><div class="spoiler-content">$2</div></div>',
            '/\[accordion](.*?)\[\/accordion]/is' => '<div class="wiki-accordion">$1</div>',
            '/\[tooltip\s+text=["\'](.*?)["\']](.*?)\[\/tooltip]/is' => '<span class="wiki-tooltip" data-tippy-content="$1">$2</span>',
            '/\[table](.*?)\[\/table]/is' => '<div class="table-responsive"><table class="table table-bordered wiki-table">$1</table></div>',
            '/\[tr](.*?)\[\/tr]/is' => '<tr>$1</tr>',
            '/\[th](.*?)\[\/th]/is' => '<th>$1</th>',
            '/\[td](.*?)\[\/td]/is' => '<td>$1</td>',
            '/\[b](.*?)\[\/b]/is' => '<strong>$1</strong>',
            '/\[i](.*?)\[\/i]/is' => '<em>$1</em>',
            '/\[u](.*?)\[\/u]/is' => '<u>$1</u>',
            '/\[center](.*?)\[\/center]/is' => '<div class="text-center">$1</div>',
            // Стандартные HTML теги, которые может прислать Quill, сохраняем атрибуты
            '/<strong(.*?)>(.*?)<\/strong>/is' => '<strong$1>$2</strong>',
            '/<em(.*?)>(.*?)<\/em>/is' => '<em$1>$2</em>',
            '/<u(.*?)>(.*?)<\/u>/is' => '<u$1>$2</u>',
            '/<p(.*?)>(.*?)<\/p>/is' => '<p$1>$2</p>',
            '/<span(.*?)>(.*?)<\/span>/is' => '<span$1>$2</span>',
            '/<br\s*\/?>/i' => '<br>',
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (is_callable($replacement)) {
                $text = preg_replace_callback($pattern, $replacement, $text);
            } else {
                $text = preg_replace($pattern, $replacement, $text);
            }
        }

        return $text;
    }

    /**
     * Загрузить изображение для контента
     */
    public function uploadImage(): void
    {
        validation::user_protection("admin");
        
        @header('Content-Type: application/json');
        
        // Проверяем наличие файла
        $file = $_FILES['image'] ?? $_FILES['filepond'] ?? null;
        
        if (!$file || !isset($file['tmp_name']) || empty($file['tmp_name'])) {
            echo json_encode([
                'success' => false,
                'message' => lang::get_phrase('No image file provided')
            ]);
            exit;
        }
        
        try {
            // Проверяем размер файла (максимум 5MB)
            $maxSize = 5 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                throw new \Exception(lang::get_phrase('File size exceeds limit'));
            }
            
            // Проверяем тип файла через getimagesize
            $imageInfo = @getimagesize($file['tmp_name']);
            if (!$imageInfo) {
                throw new \Exception(lang::get_phrase('Invalid image file'));
            }
            
            // Разрешённые типы
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($imageInfo['mime'] ?? '', $allowedMimes)) {
                throw new \Exception(lang::get_phrase('Image type not allowed'));
            }
            
            // Создаём директорию для изображений
            $serverId = user::self()->getServerId() ?? 0;
            $uploadDir = "uploads/images/description/{$serverId}";
            
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            
            // Генерируем уникальное имя файла
            $randomId = bin2hex(random_bytes(16));
            $filename = $randomId . '.webp';
            $filepath = $uploadDir . '/' . $filename;
            
            // Используем Intervention Image для обработки и конвертации в WebP
            $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
            $image = $manager->read($file['tmp_name']);
            
            // Сохраняем в WebP формате с качеством 90
            $image->toWebp(90)->save($filepath);
            
            // Получаем размеры изображения
            $width = $image->width();
            $height = $image->height();
            
            // Возвращаем ответ
            echo json_encode([
                'success' => true,
                'file' => [
                    'url' => '/' . $filepath,
                    'name' => $file['name'] ?? 'image.webp',
                    'width' => $width,
                    'height' => $height
                ]
            ]);
            exit;
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => lang::get_phrase('Upload error') . ': ' . $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * Удалить изображение из контента при удалении раздела
     * Парсит HTML и ищет img теги с путями /uploads/images/description/
     */
    private function extractImagesToDelete(string $html): array
    {
        $images = [];
        $pattern = '/<img[^>]+src=["\']([^"\']*\/uploads\/images\/description\/[^"\']+)["\'][^>]*>/i';
        
        if (preg_match_all($pattern, $html, $matches)) {
            foreach ($matches[1] as $src) {
                // Убираем слеш в начале, если есть
                $src = ltrim($src, '/');
                if (is_file($src)) {
                    $images[] = $src;
                }
            }
        }
        
        return $images;
    }

    /**
     * Удалить файлы изображений
     */
    private function deleteImageFiles(array $imagePaths): void
    {
        foreach ($imagePaths as $path) {
            try {
                if (is_file($path)) {
                    @unlink($path);
                }
            } catch (\Exception $e) {
                // Игнорируем ошибки удаления
            }
        }
    }

    /**
     * Страница редактирования контента раздела
     */
    public function edit($id): void
    {
        validation::user_protection("admin");
        
        $data = $this->getAllData();
        $section = null;
        
        foreach ($data['sections'] as $s) {
            if ($s['id'] == $id) {
                $section = $s;
                break;
            }
        }
        
        if (!$section) {
            redirect::location("/admin/plugin/server_description");
        }

        $languages = \Ofey\Logan22\controller\config\config::load()->lang()->getAllowLang();
        
        tpl::addVar([
            'section' => $section,
            'languages' => $languages,
        ]);
        
        tpl::displayPlugin("server_description/tpl/content_editor.html");
    }

    /**
     * Перемещение раздела (сортировка)
     */
    public function move(): void
    {
        validation::user_protection("admin");
        
        $id = $_POST['id'] ?? 0;
        $direction = $_POST['direction'] ?? '';
        
        $data = $this->getAllData();
        $sections = $data['sections'];
        
        $index = -1;
        foreach ($sections as $k => $s) {
            if ($s['id'] == $id) {
                $index = $k;
                break;
            }
        }
        
        if ($index != -1) {
            if ($direction == 'up' && $index > 0) {
                $tmp = $sections[$index];
                $sections[$index] = $sections[$index - 1];
                $sections[$index - 1] = $tmp;
            } elseif ($direction == 'down' && $index < count($sections) - 1) {
                $tmp = $sections[$index];
                $sections[$index] = $sections[$index + 1];
                $sections[$index + 1] = $tmp;
            }
            
            // Обновляем sort
            foreach ($sections as $k => & $s) {
                $s['sort'] = $k + 1;
            }
            
            $data['sections'] = $sections;
            
            if ($this->saveAllData($data)) {
                board::success(lang::get_phrase('Sorted successfully'));
            } else {
                board::error(lang::get_phrase('Error sorting'));
            }
        }
    }
}
