<?php

namespace Ofey\Logan22\component\plugins\sphere_forum;

use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\template\tpl;
use ReflectionClass;

class sphere_forum
{
    private ?string $nameClass = null;

    private function getNameClass(): string
    {
        if ($this->nameClass == null) {
            $this->nameClass = (new ReflectionClass($this))->getShortName();
        }

        return $this->nameClass;
    }

    public function __construct()
    {
        tpl::addVar([
            'setting' => plugin::getSetting($this->getNameClass()),
            'pluginName' => $this->getNameClass(),
            'pluginActive' =>(bool)plugin::getPluginActive($this->getNameClass()) ?? false,
        ]);
    }

    public function index(): void
    {
        validation::user_protection("admin");
        tpl::displayPlugin("sphere_forum/tpl/admin/index.html");
    }

    /**
     * Отображает страницу настроек форума
     */
    public function settings(): void
    {
        validation::user_protection("admin");
        
        // Получаем текущие настройки форума
        $currentSettings = $this->getForumSettings();
        
        tpl::addVar([
            'forumSettings' => $currentSettings,
        ]);
        
        tpl::displayPlugin("sphere_forum/tpl/admin/settings.html");
    }

    /**
     * Сохраняет настройки форума
     */
    public function saveSettings(): void
    {
        validation::user_protection("admin");
        
        $settings = [
            // Основные настройки
            'posts_per_page' => (int)($_POST['posts_per_page'] ?? 10),
            'topics_per_page' => (int)($_POST['topics_per_page'] ?? 20),
            'enable_bbcode' => isset($_POST['enable_bbcode']),
            'enable_smiles' => isset($_POST['enable_smiles']),
            'enable_polls' => isset($_POST['enable_polls']),
            'enable_attachments' => isset($_POST['enable_attachments']),
            'max_attachment_size' => (int)($_POST['max_attachment_size'] ?? 5),
            'enable_clans' => isset($_POST['enable_clans']),
            
            // Настройки антифлуда для сообщений
            'post_max_per_minute' => (int)($_POST['post_max_per_minute'] ?? 10),
            'post_max_per_hour' => (int)($_POST['post_max_per_hour'] ?? 180),
            'post_min_interval' => (int)($_POST['post_min_interval'] ?? 5),
            'post_cooldown' => (int)($_POST['post_cooldown'] ?? 300),
            
            // Настройки антифлуда для тем
            'thread_max_per_minute' => (int)($_POST['thread_max_per_minute'] ?? 3),
            'thread_max_per_hour' => (int)($_POST['thread_max_per_hour'] ?? 10),
            'thread_min_interval' => (int)($_POST['thread_min_interval'] ?? 60),
            'thread_cooldown' => (int)($_POST['thread_cooldown'] ?? 600),
            
            // Настройки модерации
            'enable_auto_moderation' => isset($_POST['enable_auto_moderation']),
            'enable_post_edit' => isset($_POST['enable_post_edit']),
            'post_edit_time_limit' => (int)($_POST['post_edit_time_limit'] ?? 3600),
            'enable_post_delete' => isset($_POST['enable_post_delete']),
            
            // Настройки прав
            'allow_guest_view' => isset($_POST['allow_guest_view']),
            'require_approval_new_topics' => isset($_POST['require_approval_new_topics']),
            'require_approval_new_posts' => isset($_POST['require_approval_new_posts']),
        ];
        
        $this->saveForumSettings($settings);
        
        \Ofey\Logan22\component\alert\board::success("Настройки форума успешно сохранены");
    }

    /**
     * Получает настройки форума из базы данных
     */
    private function getForumSettings(): array
    {
        $settings = sql::getRow(
            "SELECT setting FROM settings WHERE `key` = '__FORUM_SETTINGS__' LIMIT 1"
        );
        
        if ($settings && !empty($settings['setting'])) {
            $decoded = json_decode($settings['setting'], true);
            return $decoded ?: $this->getDefaultSettings();
        }
        
        return $this->getDefaultSettings();
    }

    /**
     * Сохраняет настройки форума в базу данных
     */
    private function saveForumSettings(array $settings): void
    {
        sql::run(
            "DELETE FROM settings WHERE `key` = '__FORUM_SETTINGS__'"
        );
        
        sql::run(
            "INSERT INTO settings (`key`, setting, dateUpdate) VALUES (?, ?, NOW())",
            ['__FORUM_SETTINGS__', json_encode($settings, JSON_UNESCAPED_UNICODE)]
        );
    }

    /**
     * Возвращает настройки по умолчанию
     */
    private function getDefaultSettings(): array
    {
        return [
            // Основные настройки
            'posts_per_page' => 10,
            'topics_per_page' => 20,
            'enable_bbcode' => true,
            'enable_smiles' => true,
            'enable_polls' => true,
            'enable_attachments' => true,
            'max_attachment_size' => 5,
            'enable_clans' => true,
            
            // Настройки антифлуда для сообщений
            'post_max_per_minute' => 10,
            'post_max_per_hour' => 180,
            'post_min_interval' => 5,
            'post_cooldown' => 300,
            
            // Настройки антифлуда для тем
            'thread_max_per_minute' => 3,
            'thread_max_per_hour' => 10,
            'thread_min_interval' => 60,
            'thread_cooldown' => 600,
            
            // Настройки модерации
            'enable_auto_moderation' => false,
            'enable_post_edit' => true,
            'post_edit_time_limit' => 3600,
            'enable_post_delete' => true,
            
            // Настройки прав
            'allow_guest_view' => true,
            'require_approval_new_topics' => false,
            'require_approval_new_posts' => false,
        ];
    }

}