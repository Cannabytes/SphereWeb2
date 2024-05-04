<?php

namespace Ofey\Logan22\model\user;

use DateTime;
use Exception;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\config\config;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\item\item;
use Ofey\Logan22\model\item\warehouse;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\user\player\character;
use Ofey\Logan22\model\user\player\characters;
use Ofey\Logan22\model\user\player\player;
use PDOException;
use PDOStatement;

class userModel
{

    private string $email = '', $password = '';

    private ?string $name = null, $signature = null, $ip = '0.0.0.0', $accessLevel = 'guest', $avatar = 'none.jpeg', $timezone, $country = null, $city = null;

    private ?DateTime $dateCreate, $dateUpdate;

    private ?array $warehouse = null;

    private ?array $players = null;

    private int $id;

    private float $donate = 0;

    private ?int $serverId = null;

    private bool $isAuth = false;

    private ?int $countWarehouseItems = null;

    public function setUser($user)
    {
        if ($user['server_id'] == null) {
            $server_id = server::getLastServer()->getId();
            if ($server_id != null) {
                $this->changeServerId(server::getLastServer()->getId(), $user['id']);
                $user['server_id'] = $server_id;
            }
        }
        $this->setIsAuth(true);
        $this->id          = $user['id'];
        $this->email       = $user['email'];
        $this->password    = $user['password'];
        $this->signature   = $user['signature'];
        $this->name        = $user['name'] ?? 'User does not exist';
        $this->ip          = $this->getIp();
        $this->dateCreate  = new DateTime($user['date_create']);
        $this->dateUpdate  = new DateTime($user['date_update']);
        $this->accessLevel = $user['access_level'];
        $this->donate      = $user['donate_point'];
        $this->avatar      = $user['avatar'];
        $this->timezone    = $user['timezone'];
        $this->country     = $user['country'];
        $this->city        = $user['city'];
        $this->serverId    = $user['server_id'];
        $this->warehouse   = $this->warehouse() ?? null;
        $this->players     = $this->getPlayers();

        return $this;
    }

    public function __construct(?int $userId)
    {
        if ($userId == null) {
            return $this;
        }
        $user = sql::getRow(
          "SELECT `id`, `email`, `password`, `name`, `signature`, `ip`, `date_create`, `date_update`, `access_level`, `donate_point`, `avatar`, `avatar_background`, `timezone`, `country`, `city`, `server_id` FROM `users` WHERE id = ? LIMIT 1",
          [$userId]
        );
        if ($user) {
            if ($user['server_id'] == null) {
                $server_id = server::getLastServer()->getId();
                if ($server_id != null) {
                    $this->changeServerId(server::getLastServer()->getId(), $user['id']);
                    $user['server_id'] = $server_id;
                }
            }
            $this->setIsAuth(true);
            $this->id          = $user['id'];
            $this->email       = $user['email'];
            $this->password    = $user['password'];
            $this->signature   = $user['signature'];
            $this->name        = $user['name'] ?? 'User does not exist';
            $this->ip          = $this->getIp();
            $this->dateCreate  = new DateTime($user['date_create']);
            $this->dateUpdate  = new DateTime($user['date_update']);
            $this->accessLevel = $user['access_level'];
            $this->donate      = $user['donate_point'];
            $this->avatar      = $user['avatar'];
            $this->timezone    = $user['timezone'];
            $this->country     = $user['country'];
            $this->city        = $user['city'];
            $this->serverId    = $user['server_id'];
            $this->warehouse   = $this->warehouse() ?? null;
            $this->players     = $this->getPlayers();

            return $this;
        }

        return null;
    }

    public function getId(): int
    {
        return $this->id ?? 0;
    }

    //Сохранение информации об игроке

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    private function changeServerId(int $serverId, int $user_id): void
    {
        sql::sql("UPDATE `users` SET `server_id` = ? WHERE `id` = ?", [
          $serverId,
          $user_id,
        ]);
    }

    /**
     * @return string|null
     */
    public function getIp(): ?string
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    private function warehouse(): array
    {
        $items = sql::getRows(
          "SELECT id, item_id, count, enchant, phrase FROM `warehouse` WHERE server_id = ? AND user_id = ? AND issued = 0",
          [
            $this->serverId(),
            $this->getId(),
          ]
        );

        $warehouseArray = [];
        foreach ($items as $item) {
            $warehouse = new warehouse();
            $warehouse->setId($item['id'])->setItemId($item['item_id'])->setCount($item['count'])->setEnchant($item['enchant'])->setPhrase(
              $item['phrase']
            )->setItem(item::getItem($item['item_id']));
            $warehouseArray[] = $warehouse;
        }

        return $warehouseArray;
    }

    //Кол-во предметов на складе

    private function serverId()
    {
        return $this->serverId;
    }

    /**
     * Получение информации о всех игровых персонажей у владельца аккаунта
     * Каждые новые N минут, выполняем повторный запрос на обновление информации
     *
     * @param   bool  $forcedUpdate  Принудительное обновление данных персонажей
     *
     * @return array|null
     * @throws Exception
     */
    public function getPlayers(bool $forcedUpdate = false): ?array
    {
        if ( ! $this->isAuth()) {
            return null;
        }
        if ($this->players === null) {
            $players = sql::getRow(
              "SELECT `id`, `user_id`, `server_id`, `players`, `date` FROM `user_players` WHERE server_id = ? AND user_id = ?;",
              [
                $this->getServerId(),
                $this->getId(),
              ]
            );
            if ($players === false) {
                $this->savePlayers();
            } else {
                //Проверяем, прошло ли больше 5 мин или если ничего не найдено
                if (strtotime($players['date']) < time() - 5 * 60 and ! $forcedUpdate) {
                    $server = server::getServer($this->getServerId());
                    if ( ! $server) {
                        $this->players = null;

                        return null;
                    }
                    $this->players = character::get_account_players($this->getEmail(), $server->getId());
                    $this->savePlayers($this->players);
                } else {
                    $this->players = [];
                    $players       = json_decode($players['players'], true);
                    foreach ($players as $playerData) {
                        $player = new player();
                        $player->setId($playerData['id']);
                        $player->setEmail($playerData['email']);
                        $player->setAccount($playerData['account']);
                        $player->setPassword($playerData['password']);
                        $player->setPasswordHide($playerData['password_hide']);
                        $player->setServerId($playerData['server_id']);
                        foreach ($playerData['characters'] as $charData) {
                            $character = new characters();
                            $character->setPlayerId($charData['player_id']);
                            $character->setAccountName($charData['account_name']);
                            $character->setPlayerName($charData['player_name']);
                            $character->setLevel($charData['level']);
                            $character->setClassId($charData['class_id']);
                            $character->setOnline($charData['online']);
                            $character->setPvp($charData['pvp']);
                            $character->setPk($charData['pk']);
                            $character->setSex($charData['sex']);
                            $character->setClanId($charData['clanid']);
                            $character->setClanName($charData['clan_name']);
                            $character->setTitle($charData['title']);
                            $character->setTimeInGame($charData['time_in_game']);
                            $character->setIsBase($charData['isBase']);
                            $character->setCreateTime($charData['createtime']);
                            $character->setClanCrest($charData['clan_crest']);
                            $character->setAllianceCrest($charData['alliance_crest']);
                            $player->setCharacters($character);  // Добавление каждого персонажа индивидуально
                        }
                        $this->players[] = $player;
                    }
                    $this->savePlayers($this->players);
                }
            }
        }

        return $this->players;
    }

    public function isAuth(): bool
    {
        return $this->isAuth;
    }

    public function setIsAuth(bool $isAuth): void
    {
        $this->isAuth = $isAuth;
    }

    public function getServerId(): ?int
    {
        if ($this->serverId == null) {
            return server::getLastServer()->getId();
        }

        return $this->serverId ?? null;
    }

    public function setServerId(int $serverId): void
    {
        if (user::getUserId()->isAuth()) {
            $this->changeServerId($serverId, user::getUserId()->getId());
        }
        $this->serverId = $serverId;
    }

    private function savePlayers($players = []): void
    {
        if (empty($players)) {
            $sql = 'INSERT INTO `user_players` (`user_id`, `server_id`, `players`, `date`) VALUES (?, ?, ?, ?);';
            sql::run($sql, [
              $this->getId(),
              $this->getServerId(),
              json_encode($this->objectToArray($players), JSON_UNESCAPED_UNICODE),
              time::mysql(),
            ]);
        } else {
            $sql = 'UPDATE `user_players` SET `players` = ?, `date` = ? WHERE `user_id` = ? AND `server_id` = ?';
            sql::run($sql, [
              json_encode($this->objectToArray($players), JSON_UNESCAPED_UNICODE),
              time::mysql(),
              $this->getId(),
              $this->getServerId(),
            ]);
        }
    }

    private function objectToArray($obj)
    {
        if (is_object($obj)) {
            $obj = (array)$obj;
        }
        if (is_array($obj)) {
            $new = [];
            foreach ($obj as $key => $val) {
                $key       = preg_replace('/\0[^\0]*\0/', '', $key);  // Удаляем приватный/защищённый префикс
                $new[$key] = $this->objectToArray($val);
            }
        } else {
            $new = $obj;
        }

        return $new;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param   string  $email
     *
     * @return userModel
     */
    public function setEmail(string $email): userModel
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @param   string  $password
     *
     * @return userModel
     */
    public function setPassword(string $password): userModel
    {
        $this->password = $password;

        return $this;
    }

    public function __toString(): string
    {
        return "Нет выбранного у метода: Список доступных методов в <src/model/user/userModel.php>";
    }

    public function countWarehouseItems(): int
    {
        if ( ! $this->isAuth) {
            return 0;
        }
        if ($this->countWarehouseItems === null) {
            return count($this->getWarehouse());
        }

        return $this->countWarehouseItems;
    }

    /**
     * @return mixed
     */
    public function getWarehouse($reload = false): mixed
    {
        if ($reload) {
            return $this->warehouse();
        }

        return $this->warehouse;
    }

    /**
     * @param   mixed  $warehouse
     */
    public function setWarehouse($warehouse): void
    {
        $this->warehouse = $warehouse;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param   string|null  $name
     *
     * @return userModel
     */
    public function setName(?string $name): userModel
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSignature(): ?string
    {
        return $this->signature;
    }

    /**
     * @param   string|null  $signature
     *
     * @return userModel
     */
    public function setSignature(?string $signature): userModel
    {
        $this->signature = $signature;

        return $this;
    }

    /**
     * @param   string|null  $accessLevel
     *
     * @return userModel
     */
    public function setAccessLevel(?string $accessLevel): userModel
    {
        $this->accessLevel = $accessLevel;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAvatar(): ?string
    {
        return fileSys::localdir("/uploads/avatar/{$this->avatar}");
    }

    /**
     * @param   string|null  $avatar
     *
     * @return userModel
     */
    public function setAvatar(?string $avatar): userModel
    {
        sql::run('UPDATE `users` SET `avatar` = ? WHERE `id` = ?', [
          $avatar,
          $this->getId(),
        ]);
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    /**
     * @param   string|null  $timezone
     *
     * @return userModel
     */
    public function setTimezone(?string $timezone): userModel
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @param   string|null  $country
     *
     * @return userModel
     */
    public function setCountry(?string $country): userModel
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @param   string|null  $city
     *
     * @return userModel
     */
    public function setCity(?string $city): userModel
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getDateCreate(): string
    {
        return $this->dateCreate->format('Y-m-d H:i:s');
    }

    /**
     * @param   DateTime|null  $dateCreate
     *
     * @return userModel
     */
    public function setDateCreate(?DateTime $dateCreate): userModel
    {
        $this->dateCreate = $dateCreate;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getDateUpdate(): ?DateTime
    {
        return $this->dateUpdate;
    }

    /**
     * @param   DateTime|null  $dateUpdate
     *
     * @return userModel
     */
    public function setDateUpdate(?DateTime $dateUpdate): userModel
    {
        $this->dateUpdate = $dateUpdate;

        return $this;
    }

    /**
     * Проверяет, достаточно ли денег у пользователя для совершения покупки.
     *
     * @param   float|int  $purchaseAmount  Стоимость покупки.
     *
     * @return bool Возвращает true, если средств достаточно, иначе false.
     */
    public function canAffordPurchase(float|int $purchaseAmount): bool
    {
        $userBalance = self::getDonate();

        if ($userBalance <= 0) {
            board::error("Вы не можете совершать покупки если у Вас на счету 0");
        }

        // Проверяем, что оба аргумента являются числами и не являются NaN или INF
        if ( ! is_numeric($userBalance) || is_nan($userBalance) || is_infinite($userBalance)) {
            board::error('Некорректное значение баланса пользователя.');
        }

        if ( ! is_numeric($purchaseAmount) || is_nan($purchaseAmount) || is_infinite($purchaseAmount)) {
            board::error('Некорректная стоимость покупки.');
        }

        // Проверяем, что баланс пользователя и стоимость покупки неотрицательны
        if ($purchaseAmount < 0) {
            board::error('Стоимость покупки должны быть неотрицательными числами.');
        }

        // Проверяем, что сумма денег на счету не меньше стоимости покупки
        return $userBalance >= $purchaseAmount;
    }

    /**
     * @return int
     */
    public function getDonate(): int
    {
        return $this->donate;
    }

    /**
     * @param   int  $donate
     *
     * @return userModel
     */
    public function setDonate(int $donate): userModel
    {
        $this->donate = $donate;

        return $this;
    }

    /**
     * Добавляет средства на счет пользователя.
     *
     * @param   float  $amount  Сумма для добавления.
     *
     * @return bool Возвращает true, если операция успешна.
     */
    public function donateAdd(float $amount): bool
    {
        if ($amount < 0) {
            board::error("Сумма должна быть положительным числом.");
        }

        $this->donate += $amount;

        return $this->donateUpdate();
    }

    /**
     * Обновляет баланс донатов пользователя в базе данных.
     *
     * @return bool Возвращает true, если обновление прошло успешно.
     */
    private function donateUpdate(): bool
    {
        try {
            sql::run("UPDATE `users` SET `donate_point` = ? WHERE `id` = ?", [$this->donate, $this->getId()]);

            return true;
        } catch (Exception $e) {
            board::error("Ошибка при обновлении средств: " . $e->getMessage());

            return false;
        }
    }

    /**
     * Уменьшает средства на счету пользователя.
     *
     * @param   float  $amount  Сумма для уменьшения.
     *
     * @return bool Возвращает true, если операция успешна и средств достаточно.
     */
    public function donateDeduct(float $amount): bool
    {
        if ($amount < 0) {
            board::error("Сумма должна быть положительным числом.");
        }

        if ($this->donate < $amount) {
            return false;  // Недостаточно средств
        }

        $this->donate -= $amount;

        return $this->donateUpdate();
    }

    public function getCountPlayers(): int
    {
        if (empty($this->getPlayers())) {
            return 0;
        }
        $allPlayerCount = 0;
        foreach ($this->getPlayers() as $player) {
            $allPlayerCount += count($player->getCharacters());
        }

        return $allPlayerCount;
    }

    public function addLog($type, $phrase, $variables = []): void
    {
        $variables = json_encode($variables);
        $_REQUEST  = json_encode($_REQUEST);
        $method    = $_SERVER['REQUEST_METHOD'];
        $action    = $_SERVER['REQUEST_URI'];
        $trace     = debug_backtrace()[0];
        $file      = $trace['file'];
        $line      = $trace['line'];
        sql::run(
          "INSERT INTO `logs_all` (`user_id`, `time`, `type`, `phrase`, `variables`, `server_id`, `request`, `method`, `action`, `file`, `line`) VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
          [$this->getId(), time::mysql(), $type, $phrase, $variables, $this->getServerId(), $_REQUEST, $method, $action, $file, $line]
        );
    }

    public function addToInventory(int $server_id, int $item_id, int $count, int $enchant, int $phrase): array
    {
        $stmt = sql::run(
          "INSERT INTO `warehouse` (`user_id`, `server_id`, `item_id`, `count`, `enchant`, `phrase`) VALUES (?, ?, ?, ?, ?, ?)",
          [
            $this->getId(),
            $server_id,
            $item_id,
            $count,
            $enchant,
            $phrase,
          ]
        );
        if ($stmt instanceof PDOStatement) {
            return ['success' => true, 'errorInfo' => null];
        } elseif ($stmt instanceof PDOException) {
            // Здесь вы можете логировать ошибку или обрабатывать её иначе
            return ['success' => false, 'errorInfo' => ['code' => $stmt->getCode(), 'message' => $stmt->getMessage()]];
        } else {
            // Общий случай ошибки, если результат не является ни PDOStatement, ни PDOException
            return ['success' => false, 'errorInfo' => sql::errorInfo()];
        }
    }

    /**
     * Является ли пользователь администратором
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return (bool)$this->getAccessLevel("admin");
    }

    /**
     * @return string|null
     */
    public function getAccessLevel($accessLevel = null): string|bool|null
    {
        if ($accessLevel != null) {
            return $accessLevel == $this->accessLevel;
        }

        return $this->accessLevel;
    }

    public function getLang(): string {
        return \Ofey\Logan22\controller\config\config::load()->lang()->lang_user_default();
    }

}