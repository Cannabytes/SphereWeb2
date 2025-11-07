<?php

namespace Ofey\Logan22\component\plugins\sphere_forum;

use Exception;
use Intervention\Image\ImageManager;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\plugins\sphere_forum\struct\ForumClan;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\request\XssSecurity;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class ForumClans
{
    private $uploadPath = 'uploads/clans/';
    private $clans = [];
    private $clanName = [];

    /**
     * Проверяет, включена ли функция кланов
     */
    private function checkClansEnabled(): void
    {
        if (!forum::areClanEnabled()) {
            board::error("Функция кланов отключена администратором");
            redirect::location("/forum");
        }
    }

    public function create(): void
    {
        $this->checkClansEnabled();
        
        try {
            if(user::self()->isGuest()){
                redirect::location("/forum");
            }
            $logoFile = isset($_FILES['clanLogo']) ? $_FILES['clanLogo'] : null;
            $bgFile = isset($_FILES['clanBackground']) ? $_FILES['clanBackground'] : null;

            $clanName = trim($_POST['clanName']);
            $clanDescription = trim($_POST['clanDescription']);
            
            $clanName = XssSecurity::clean($clanName);
            $clanDescription = XssSecurity::clean($clanDescription);
            
            $data = [
                'clanName' => $clanName,
                'clanDescription' => $clanDescription,
                'nameColor' => $_POST['nameColor'],
                'acceptance' => $_POST['acceptance'],
                'clanLogo' => $_FILES['clanLogo'] ?? null,
                'clanBackground' => $_FILES['clanBackground'] ?? null
            ];

            $this->validateClanData($data);

            $logoPath = $logoFile ? $this->handleImage($logoFile, 'logo_') : null;
            $backgroundPath = $bgFile ? $this->handleImage($bgFile, 'bg_') : null;

            sql::run(
                "INSERT INTO forum_clans (owner_id, name, `desc`, logo, background_logo, text_color, acceptance) 
             VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    user::self()->getId(),
                    $data['clanName'],
                    $data['clanDescription'],
                    $logoPath,
                    $backgroundPath,
                    $data['nameColor'],
                    (int)$data['acceptance']
                ]
            );

            $clanId = sql::lastInsertId();

            sql::run(
                "INSERT INTO forum_clan_members (clan_id, user_id, role) VALUES (?, ?, ?)",
                [$clanId, user::self()->getId(), 'leader']
            );
            user::self()->addVar('clanId', $clanId);

            board::redirect("/forum/clan/" . $clanName);
            board::success("Клан успешно создан");

        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }

    private function validateClanData($data)
    {
        if (!isset($data['clanName']) || mb_strlen($data['clanName']) < 3 || mb_strlen($data['clanName']) > 16) {
            board::error('Название клана должно содержать от 3 до 16 символов');
        }

        if (!isset($data['clanDescription']) || strlen($data['clanDescription']) > 255) {
            board::error('Описание клана не должно превышать 255 символов');
        }

        if (!isset($data['acceptance']) || !in_array((int)$data['acceptance'], [1, 2])) {
            board::error('Некорректный тип принятия в клан');
        }

        $this->validateColor($data['nameColor']);
    }

    private function validateColor($color)
    {
        $validColors = [
            'clan-golden', 'clan-emerald', 'clan-neon-blue', 'clan-fiery', 'clan-purple',
            'clan-crimson', 'clan-lime', 'clan-marine', 'clan-pink', 'clan-icy',
            'clan-orange', 'clan-salad', 'clan-turquoise', 'clan-coral', 'clan-amethyst',
            'clan-amber', 'clan-sapphire', 'clan-ruby', 'clan-ultramarine', 'clan-pearl',
            'clan-cosmic', 'clan-sunset', 'clan-aurora', 'clan-electric', 'clan-purple-haze',
            'clan-bronze', 'clan-moonlight', 'clan-acid', 'clan-aquamarine', 'clan-magic'
        ];

        if (!in_array($color, $validColors)) {
            board::error('Некорректный цвет текста');
        }
    }

    private function handleImage($file, $prefix)
    {
        if (!$file['error']) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 2 * 1024 * 1024;

            if (!in_array($file['type'], $allowedTypes)) {
                board::error('Неподдерживаемый формат изображения');
            }

            if ($file['size'] > $maxSize) {
                board::error('Размер файла превышает 2MB');
            }

            $filename = $prefix . uniqid() . '.webp';

            $uploadDir = fileSys::get_dir('/uploads/clans/');
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Обработка файла
            $tmpName = is_array($file['tmp_name']) ? $file['tmp_name'][0] : $file['tmp_name'];
            $error = is_array($file['error']) ? $file['error'][0] : $file['error'];

            if ($error !== UPLOAD_ERR_OK) {
                throw new Exception("Ошибка при загрузке файла");
            }

            $manager = ImageManager::gd();

            $image = $manager->read($tmpName);

            $originalWidth = $image->width();
            $originalHeight = $image->height();

            if (!$image->save($uploadDir . $filename)) {
                throw new Exception("Ошибка при сохранении изображения");
            }

            $thumbImage = $image;
            if ($originalHeight > 300) {
                $thumbImage = $image->scale(height: 300);
            }
            if ($originalWidth > 300) {
                $thumbImage = $image->scale(width: 300);
            }

            $thumb = pathinfo($filename, PATHINFO_FILENAME) . '_thumb.png';
            if (!$thumbImage->save($uploadDir . $thumb)) {
                throw new Exception("Ошибка при сохранении миниатюры");
            }

            return $filename;
        }
        return null;
    }

    function getClanInfoById(int $clanId): false|ForumClan
    {
        if (isset($this->clans[$clanId])) {
            return $this->clans[$clanId];
        }
        $row = sql::getRow("SELECT * FROM forum_clans WHERE id = ? LIMIT 1", [$clanId]);
        if ($row) {
            $clan = new ForumClan($row);
            $this->clans[$clanId] = $clan; // Сохраняем клан по ID
            $this->clanName[$clan->getName()] = &$this->clans[$clanId];
            return $clan;
        }
        return false;
    }

    function getClanInfoByName(string $name): false|ForumClan
    {
        $name = trim($name);
        if (isset($this->clanName[$name])) {
            return $this->clanName[$name];
        }
        $row = sql::getRow("SELECT * FROM forum_clans WHERE name = ?", [$name]);
        if ($row) {
            $clan = new ForumClan($row);
            $this->clans[$clan->getId()] = $clan;
            $this->clanName[$clan->getName()] = &$this->clans[$clan->getId()]; 
            return $clan;
        }
        return false;
    }


    function getClanList($count = 5): array
    {
        $rows = sql::getRows("SELECT * FROM `forum_clans` WHERE `id` >= (SELECT FLOOR( MAX(`id`) * RAND() ) FROM `forum_clans` ) LIMIT ?;", [$count]);
        $clans = [];
        foreach ($rows as $row) {
            $clans[] = new ForumClan($row);
        }
        return $clans;
    }

    function editClan($clanName): void
    {
        $clanName = trim($clanName);
        $clan = $this->getClanInfoByName($clanName);
        if(!$clan){
            redirect::location("/forum");
        }
        tpl::addVar('clan', $clan);
        tpl::displayPlugin("/sphere_forum/tpl/clan/edit.html");
    }

    public function view($clanName)
    {
        $this->checkClansEnabled();
        $clanName = trim($clanName);
        $clan = $this->getClanInfoByName($clanName);
        if(!$clan){
            redirect::location("/forum");
        }
        $isMember = false;
        if ($clan) {
            $isMember = $this->isMember($clan->getId());
        }

        // Получаем сообщения клана
        $clanPosts = $clan->getClanPosts();
        tpl::addVar('isMember', $isMember);
        tpl::addVar('clan', $clan);
        tpl::addVar('clanPosts', $clanPosts);
        tpl::displayPlugin("/sphere_forum/tpl/clan/view.html");
    }

    public function createClanIndex(): void
    {
        $this->checkClansEnabled();
        if(user::self()->isGuest()){
            redirect::location("/forum");
        }
        $clanId = user::self()->getVar("clanId")['val'] ?? false;
        if($clanId){
           $clan = $this->getClanInfoById($clanId);
           if($clan){
               redirect::location("/forum/clan/" . $clan->getName());
           }
        }
        tpl::displayPlugin("/sphere_forum/tpl/clan/create.html");
    }

    function getUserOwnerClan(): false|ForumClan
    {
       $row = sql::getRow("SELECT * FROM forum_clans WHERE owner_id = ? LIMIT 1", [user::self()->getId()]);
       if ($row) {
           return new ForumClan($row);
       }
       return false;
    }

    public function updateClan()
    {
        $this->checkClansEnabled();
        try {
            $data = $_POST;

            $userOwnerClan = $this->getUserOwnerClan();
            //Если пользователь не овнер клана, тогда прощаемся с ним
            if(!$userOwnerClan->getOwnerId()) {
                board::error("У Вас нет клана");
            }
            $this->validateClanData($data);

            // Обработка изображений
            $logoPath = isset($_FILES['clanLogo']) ? $this->handleImage($_FILES['clanLogo'], 'logo_') : null;
            $backgroundPath = isset($_FILES['clanBackground']) ? $this->handleImage($_FILES['clanBackground'], 'bg_') : null;

            $clanName = trim($data['clanName']);
            $desc = trim($data['clanDescription']);
            $updateData = [
                'name' => $clanName,
                'desc' => $desc,
                'text_color' => $data['nameColor'],
                'acceptance' => (int)$data['acceptance']
            ];

            if ($logoPath) $updateData['logo'] = $logoPath;
            if ($backgroundPath) $updateData['background_logo'] = $backgroundPath;

            $sets = [];
            $params = [];
            foreach ($updateData as $key => $value) {
                $sets[] = "`$key` = ?";
                $params[] = $value;
            }
            $params[] = $userOwnerClan->getId();

            sql::run(
                "UPDATE forum_clans SET " . implode(', ', $sets) . " WHERE id = ?",
                $params
            );
            board::redirect("/forum/clan/" . $clanName);
            board::success("Сохранено");
        } catch (Exception $e) {
            board::error($e->getMessage());
            return false;
        }
    }

    public function joinClan($clanId)
    {
        $this->checkClansEnabled();
        $clan = $this->getClanInfoById($clanId);
        if (!$clan) {
            board::error("Клан не найден");
        }

        // Проверяем, не состоит ли уже пользователь в клане
        if ($this->isMember($clanId)) {
            board::error("Вы уже состоите в этом клане");
        }

        try {
            if ($clan->getAcceptance() == 1) { // Автоматическое принятие
                sql::run(
                    "INSERT INTO forum_clan_members (clan_id, user_id) VALUES (?, ?)",
                    [$clanId, user::self()->getId()]
                );
                user::self()->addVar('clanId', $clanId);
            } else { // Отправка заявки
                sql::run(
                    "INSERT INTO forum_clan_requests (clan_id, user_id) VALUES (?, ?)",
                    [$clanId, user::self()->getId()]
                );
            }
            return true;
        } catch (Exception $e) {
            board::error("Ошибка при вступлении в клан");
            return false;
        }
    }



    public function handleRequest($requestId, $accept): bool
    {
        $this->checkClansEnabled();
        $request = sql::getRow(
            "SELECT * FROM forum_clan_requests WHERE id = ?",
            [$requestId]
        );

        if (!$request || !$this->isOwner($request['clan_id'])) {
            board::error("Доступ запрещен");
        }

        try {
            if ($accept) {
                sql::transaction(function() use ($request) {
                    // Добавляем пользователя в клан
                    sql::run(
                        "INSERT INTO forum_clan_members (clan_id, user_id) VALUES (?, ?)",
                        [$request['clan_id'], $request['user_id']]
                    );

                    // Обновляем статус заявки
                    sql::run(
                        "UPDATE forum_clan_requests SET status = 'accepted' WHERE id = ?",
                        [$request['id']]
                    );

                    // Добавляем переменную пользователю
                    $user = user::getUserId($request['user_id']);
                    $user->addVar('clanId', $request['clan_id']);
                });

                board::success("Заявка успешно принята");
            } else {
                sql::run(
                    "UPDATE forum_clan_requests SET status = 'rejected' WHERE id = ?",
                    [$request['id']]
                );
                board::success("Заявка отклонена");
            }

            return true;
        } catch (Exception $e) {
            board::error("Ошибка при обработке заявки: " . $e->getMessage());
            return false;
        }
    }

    private function isOwner($clanId): bool
    {
        $clan = $this->getClanInfoById($clanId);
        return $clan && $clan->getOwnerId() === user::self()->getId();
    }

    private function isMember($clanId): bool
    {
        return (bool)sql::getValue(
            "SELECT 1 FROM forum_clan_members WHERE clan_id = ? AND user_id = ?",
            [$clanId, user::self()->getId()]
        );
    }

    public function leaveClan($clanId) {
        $this->checkClansEnabled();
        if (!$this->isMember($clanId)) {
            board::error("Вы не состоите в этом клане");
        }
        sql::transaction(function() use ($clanId) {
            sql::run(
                "DELETE FROM forum_clan_members WHERE clan_id = ? AND user_id = ?",
                [$clanId, user::self()->getId()]
            );
            sql::run(
                "DELETE FROM forum_clan_requests WHERE clan_id = ? AND user_id = ?",
                [$clanId, user::self()->getId()]
            );
            user::self()->addVar('clanId', null);
        });

        return true;
    }

    public function createClanPost() {
        $this->checkClansEnabled();
        try {
            if (!user::self()->isAuth()) {
                throw new \Exception('Необходима авторизация');
            }

            $clanId = $_POST['clan_id'] ?? null;
            $message = $_POST['message'] ?? '';
            
            // XSS защита: очищаем сообщение
            $message = XssSecurity::clean($message);

            if (!$clanId) {
                throw new \Exception('Не указан ID клана');
            }

            $clan = $this->getClanInfoById((int)$clanId);
            if (!$clan) {
                throw new \Exception('Клан не найден');
            }

            if (!$this->isMember($clanId) && $clan->getOwnerId() !== user::self()->getId()) {
                throw new \Exception('Нет прав для публикации сообщений');
            }

            // Обработка изображений
            $images = [];
            if (isset($_FILES['images'])) {
                $images = $this->handleImages($_FILES['images']);
                if (count($images) > 10) {
                    throw new \Exception('Превышено максимальное количество изображений');
                }
            }

            // Создаем пост
            $post = $clan->createPost(user::self()->getId(), $message, $images);

            echo json_encode([
                'success' => true,
                'message' => 'Сообщение опубликовано',
                'post' => $post
            ]);

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function updateClanPost() {
        $this->checkClansEnabled();
        try {
            if (!user::self()->isAuth()) {
                throw new \Exception('Необходима авторизация');
            }

            $postId = $_POST['post_id'] ?? null;
            $message = $_POST['message'] ?? '';
            $clanId = $_POST['clan_id'] ?? null;
            
            // XSS защита: очищаем сообщение
            $message = XssSecurity::clean($message);

            if (!$postId || !$message || !$clanId) {
                throw new \Exception('Не все обязательные поля заполнены');
            }

            $clan = $this->getClanInfoById((int)$clanId);
            if (!$clan) {
                throw new \Exception('Клан не найден');
            }

            $result = $clan->updatePost($postId, $message);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Сообщение обновлено'
                ]);
            } else {
                throw new \Exception('Не удалось обновить сообщение');
            }

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function deleteClanPost() {
        $this->checkClansEnabled();
        try {
            if (!user::self()->isAuth()) {
                throw new \Exception('Необходима авторизация');
            }

            $postId = $_POST['post_id'] ?? null;
            $clanId = $_POST['clan_id'] ?? null;

            $clan = $this->getClanInfoById($clanId);
            if (!$clan) {
                throw new \Exception('Клан не найден');
            }

            $clan->deletePost($postId);
            board::reload();
            board::success("Удалено");

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    private function handleImages(array $files): array {
        $images = [];
        $manager = ImageManager::gd();
        $uploadDir = fileSys::get_dir('/uploads/clans/');

        foreach ($files['name'] as $index => $name) {
            if ($files['error'][$index] !== UPLOAD_ERR_OK) {
                continue;
            }

            // Проверка типа файла
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($files['type'][$index], $allowedTypes)) {
                continue;
            }

            // Проверка размера
            if ($files['size'][$index] > 5 * 1024 * 1024) { // 5MB
                continue;
            }

            try {
                // Создаем уникальное имя файла
                $filename = 'post_' . uniqid() . '.webp';

                // Читаем изображение
                $image = $manager->read($files['tmp_name'][$index]);

                // Изменяем размер если нужно
                if ($image->width() > 2048 || $image->height() > 2048) {
                    $image->scale(width: 2048, height: 2048);
                }

                // Сохраняем как webp
                if ($image->save($uploadDir . $filename)) {
                    $images[] = $filename;
                }
            } catch (\Exception $e) {
                error_log("Error processing image: " . $e->getMessage());
                continue;
            }
        }

        return $images;
    }

    public function deleteClan() {
        $this->checkClansEnabled();
        try {
            $clanId = $_POST['clan_id'] ?? null;
            if (!$clanId) {
                throw new Exception('Не указан ID клана');
            }

            $clan = $this->getClanInfoById((int)$clanId);
            if (!$clan) {
                throw new Exception('Клан не найден');
            }

            // Проверяем права (владелец или администратор)
            if ($clan->getOwnerId() !== user::self()->getId() && !user::self()->isAdmin()) {
                throw new Exception('Недостаточно прав для удаления клана');
            }

            sql::transaction(function() use ($clanId) {
                // Удаляем все связанные данные
                sql::run("DELETE FROM forum_clan_chat WHERE clan_id = ?", [$clanId]);
                sql::run("DELETE FROM forum_clan_requests WHERE clan_id = ?", [$clanId]);
                sql::run("DELETE FROM forum_clan_members WHERE clan_id = ?", [$clanId]);
                sql::run("DELETE FROM forum_clan_post_images WHERE post_id IN (SELECT id FROM forum_clan_posts WHERE clan_id = ?)", [$clanId]);
                sql::run("DELETE FROM forum_clan_posts WHERE clan_id = ?", [$clanId]);
                sql::run("DELETE FROM forum_clans WHERE id = ?", [$clanId]);

                // Удаляем переменную clanId у всех пользователей клана
                sql::run("DELETE FROM forum_clans WHERE `val` = 'clanId' AND user_id = ?", [user::self()->getId()]);
            });

            board::redirect("/forum/clans");
            board::success("Клан успешно удален");
        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }

    public function showAllClans() {
        $this->checkClansEnabled();
        $clans = sql::getRows(
            "SELECT c.*, 
            u.name as owner_name,
            COUNT(DISTINCT m.user_id) as members_count
        FROM forum_clans c
        LEFT JOIN users u ON c.owner_id = u.id
        LEFT JOIN forum_clan_members m ON c.id = m.clan_id
        GROUP BY c.id
        ORDER BY members_count DESC"
        );

        foreach ($clans as &$clan) {
            $clan = new ForumClan($clan);
        }

        tpl::addVar('clans', $clans);
        tpl::displayPlugin("/sphere_forum/tpl/clan/list.html");
    }

    public function adminClansList() {
        $this->checkClansEnabled();
        if (!user::self()->isAdmin()) {
            redirect::location("/forum");
            return;
        }

        $clans = sql::getRows(
            "SELECT c.*, 
            u.name as owner_name,
            COUNT(DISTINCT m.user_id) as members_count
        FROM forum_clans c
        LEFT JOIN users u ON c.owner_id = u.id
        LEFT JOIN forum_clan_members m ON c.id = m.clan_id
        GROUP BY c.id
        ORDER BY c.created_at DESC"
        );

        tpl::addVar('clans', $clans);
        tpl::displayPlugin("/sphere_forum/tpl/admin/clans.html");
    }

    public function adminEditClan($clanId) {
        $this->checkClansEnabled();
        if (!user::self()->isAdmin()) {
            redirect::location("/forum");
            return;
        }

        $clan = $this->getClanInfoById($clanId);
        if (!$clan) {
            redirect::location("/admin/forum/clans");
            return;
        }

        tpl::addVar('clan', $clan);
        tpl::displayPlugin("/sphere_forum/tpl/admin/edit_clan.html");
    }

}

