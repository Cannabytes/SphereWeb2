<?php
/////

namespace Ofey\Logan22\model\user;

use DateTime;
use DateTimeZone;
use Exception;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\session\session;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\admin\userlog;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\donate\payMessage;
use Ofey\Logan22\model\item\item;
use Ofey\Logan22\model\item\warehouse;
use Ofey\Logan22\model\log\logTypes;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\user\player\accountModel;
use Ofey\Logan22\model\user\player\characterModel;
use Ofey\Logan22\model\user\player\player_account;
use PDOException;
use PDOStatement;

class userModel
{
    private const int ONLINE_THRESHOLD_MINUTES = 3;

    private string $email = '', $password = '';

    private ?string $name = null, $signature = null, $ip = '0.0.0.0', $accessLevel = 'guest', $avatar = 'none.jpeg', $timezone, $country = null, $city = null;

    private ?DateTime $dateCreate, $dateUpdate;

    private ?array $warehouse = null;

    /**
     * @var array|null accountModel[]
     */
    private null|false|array $accounts = null;

    private int $id = 0;

    private float|int $donate = 0;

    private ?int $serverId = null;

    private bool $isAuth = false;

    private ?int $countWarehouseItems = null;

    private array $characters = [];

    /**
     * @var $account accountModel[]
     */
    private array $account = [];

    private ?string $lang = null;

    private ?DateTime $lastActivity = null;

    private bool $isFoundUser = false;

    public function __construct(?int $userId = null)
    {
        if ($userId == null) {
            return $this;
        }
        $user = sql::getRow(
            "SELECT `id`, `email`, `password`, `name`, `signature`, `ip`, `date_create`, `date_update`, `access_level`, `donate_point`, `avatar`, `avatar_background`, `timezone`, `country`, `city`, `server_id`, `lang`, `last_activity` FROM `users` WHERE id = ? LIMIT 1",
            [$userId]
        );
        if ($user) {
            if ($user['server_id'] === null) {
                $server_id = server::getDefaultServer();
                if ($server_id != null) {
                    $this->changeServerId($server_id, $user['id']);
                    $user['server_id'] = $server_id;
                }
            } else {
                $allServersId = server::getServerIds();
                if ($allServersId != null) {
                    $found = false;
                    foreach ($allServersId as $id) {
                        if ($user['server_id'] == $id) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $server_id = server::getDefaultServer();
                        $this->changeServerId($server_id, $user['id']);
                        $user['server_id'] = $server_id;
                    }
                }
            }
            if (isset($_SESSION['id']) &&
                $_SESSION['id'] == $user['id'] &&
                isset($_SESSION['oauth2']) &&
                !$_SESSION['oauth2'] &&
                !password_verify($_SESSION['password'], $user['password'])) {

                session::clear();
                redirect::location("/main");
            }

            $this->setIsAuth(true);
            $this->id = $user['id'];
            $this->email = $user['email'];
            $this->password = $user['password'];
            $this->signature = $user['signature'];
            $this->name = $user['name'] ?? 'User does not exist';
            $this->ip = $this->getIp();
            $this->dateCreate = new DateTime($user['date_create']);
            $this->dateUpdate = new DateTime($user['date_update']);
            $this->accessLevel = $user['access_level'];
            $this->donate = $user['donate_point'];
            $this->avatar = $user['avatar'];
            $this->timezone = $user['timezone'];
            $this->country = $user['country'];
            $this->city = $user['city'];
            $this->serverId = $user['server_id'];
            $this->lang = $user['lang'];
            $this->isFoundUser = true;

            if (isset($_SESSION['id']) && $userId == $_SESSION['id']) {
                \Ofey\Logan22\component\sphere\server::setUser($this);
                if(isset($_SESSION['lang'])){
                    if($this->lang != $_SESSION['lang']){
                        $_SESSION['lang'] = $this->lang;
                    }
                }else{
                    $_SESSION['lang'] = $this->lang;
                }
            }

            $this->initLastActivity($user['last_activity']);

            if (isset($_SESSION['id'])) {
                // Обновляем время, когда селф-пользователь был в онлайне (т.е. сейчас)
                if ($_SESSION['id'] == $this->getId()) {
                    if ($this->lastActivity == null) {
                        $this->updateLastActivity();
                    }
                    $now = new DateTime();
                    $diff = $now->getTimestamp() - $this->lastActivity->getTimestamp();
                    if ($diff > (self::ONLINE_THRESHOLD_MINUTES * 60)) {
                        $this->updateLastActivity();
                    }
                }
            }
            $this->warehouse = $this->warehouse() ?? null;
            $this->accounts = null;

            return $this;
        }

        return null;
    }

    // Найден ли такой пользователь
    public function isFoundUser(): bool
    {
        return $this->isFoundUser;
    }

    /**
     * Обновляет время последней активности пользователя
     */
    public function updateLastActivity(): void {
        if (!$this->isAuth()) {
            return;
        }

        // Устанавливаем часовой пояс UTC для записи в БД
        sql::run("SET time_zone = '+00:00'");
        sql::run("UPDATE `users` SET `last_activity` = UTC_TIMESTAMP() WHERE `id` = ?", [$this->getId()]);

        // Возвращаем системный часовой пояс
        sql::run("SET time_zone = @@global.time_zone");

        // Обновляем локальное свойство
        $utcNow = new DateTime('now', new DateTimeZone('UTC'));
        $this->lastActivity = $this->convertUtcToUserTime($utcNow);
    }

    /**
     * Инициализация времени последней активности из БД
     */
    private function initLastActivity(?string $lastActivityStr): void {
        if ($lastActivityStr === null) {
            $this->lastActivity = null;
            return;
        }

        // Создаем DateTime объект в UTC из значения в БД
        $utcTime = new DateTime($lastActivityStr, new DateTimeZone('UTC'));
        // Конвертируем в часовой пояс пользователя
        $this->lastActivity = $this->convertUtcToUserTime($utcTime);
    }

    private static $validTimezones = null;

    private function convertUtcToUserTime(DateTime $utcTime): DateTime {
        try {
            $timezone = $this->getTimezone();

            // Ленивая инициализация списка часовых поясов
            if (self::$validTimezones === null) {
                self::$validTimezones = DateTimeZone::listIdentifiers();
            }
            // Проверяем валидность часового пояса
            if (empty($timezone) || !in_array($timezone, self::$validTimezones)) {
                $timezone = 'UTC';
                $this->setTimezone($timezone);
            }
            $userTimezone = new DateTimeZone($timezone);
            $userTime = clone $utcTime;
            $userTime->setTimezone($userTimezone);
            return $userTime;
        } catch (Exception $e) {
            $userTimezone = new DateTimeZone('UTC');
            $userTime = clone $utcTime;
            $userTime->setTimezone($userTimezone);
            return $userTime;
        }
    }

    /**
     * Проверяет, онлайн ли пользователь
     */
    public function isOnline(): bool {
        if ($this->lastActivity === null) {
            return false;
        }

        $userNow = new DateTime('now', new DateTimeZone($this->getTimezone()));
        $diff = $userNow->getTimestamp() - $this->lastActivity->getTimestamp();
        return $diff < (self::ONLINE_THRESHOLD_MINUTES * 60);
    }

    private function convertToServerTime(DateTime $userTime): DateTime {
        $serverTimezone = new \DateTimeZone(date_default_timezone_get());

        // Создаем копию времени
        $serverTime = clone $userTime;

        // Конвертируем в серверный часовой пояс
        $serverTime->setTimezone($serverTimezone);

        return $serverTime;
    }

    public function getLastActive(): ?string
    {
        return $this->lastActivity?->format('d.m.Y H:i');
    }

    /**
     * Форматирует время последней активности для отображения
     */
    public function getLastActivityFormatted(): string {

        if ($this->lastActivity === null) {
            return "Никогда не был на сайте";
        }

        if ($this->isOnline()) {
            return "Онлайн";
        }

        $userNow = new DateTime('now', new DateTimeZone($this->getTimezone()));
        $diff = $userNow->getTimestamp() - $this->lastActivity->getTimestamp();

        if ($diff < 3600) {
            $minutes = floor($diff / 60);
            return "{$minutes} мин. назад";
        }

        if ($diff < 86400) {
            $hours = floor($diff / 3600);
            return "{$hours} ч. назад";
        }

        return $this->lastActivity->format('d.m.Y H:i');
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

    public function setUser($user, bool $loadWarehouse = false)
    {
        if ($user['server_id'] === null) {
            $lastServer = server::getLastServer();
            if ($lastServer !== null) {
                $server_id = $lastServer->getId();
                if ($server_id !== null) {
                    $this->changeServerId($server_id, $user['id']);
                    $user['server_id'] = $server_id;
                }
            }
        }

        $this->setIsAuth(true);
        $this->id = $user['id'];
        $this->email = $user['email'];
        $this->password = $user['password'];
        $this->signature = $user['signature'];
        $this->name = $user['name'] ?? 'User does not exist';
        $this->ip = $this->getIp();
        $this->dateCreate = new DateTime($user['date_create']);
        $this->dateUpdate = new DateTime($user['date_update']);
        $this->accessLevel = $user['access_level'];
        $this->donate = $user['donate_point'];
        $this->avatar = $user['avatar'];
        $this->timezone = $user['timezone'];
        $this->country = $user['country'];
        $this->city = $user['city'];
        $this->serverId = $user['server_id'];
        $this->lang = $user['lang'];
        if($loadWarehouse) {
            $this->warehouse = $this->warehouse() ?? null;
        }
        $this->initLastActivity($user['last_activity']);

        $this->accounts = false;
        return $this;
    }

    public function getId(): int
    {
        return $this->id ?? 0;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return warehouse[]|array
     */
    private function warehouse(): array
    {
        $items = sql::getRows(
            "SELECT id, item_id, count, enchant, phrase FROM `warehouse` WHERE server_id = ? AND user_id = ? AND issued = 0", [
                $this->serverId(),
                $this->getId(),
            ]
        );
        $warehouseArray = [];
        foreach ($items as $item) {
            $warehouse = new warehouse();
            $warehouse->setId($item['id'])->setItemId($item['item_id'])->setCount($item['count'])->setEnchant($item['enchant'])->setPhrase(
                $item['phrase']
            )->setItem(item::getItem($item['item_id']))->setServerId($this->serverId());
            $warehouseArray[] = $warehouse;
        }

        return $warehouseArray;
    }

    private function serverId()
    {
        return $this->serverId;
    }

    //Кол-во предметов на складе

    public function getInstance(): userModel
    {
        return $this;
    }

    public function AddHistoryDonate(float|int $amount, ?string $message = null, string $pay_system = "sphereBonus", null|int $id_admin_pay = null, $input = null): void
    {
        if ($message == null) {
            $message = payMessage::getRandomPhrase();
        }
        $isSphereSystem = in_array($pay_system, ["sphereBonus", "cumulativeBonus", "oneTimeBonus"]) ? 1 : 0;

        if ($isSphereSystem == 0) {
            if ($input == null) {
                $input = json_encode($_REQUEST, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            \Ofey\Logan22\component\sphere\server::send(type::DONATE_STATISTIC, [
                'paySystem' => $pay_system,
                'request' => $input,
            ]);

            self::addLog(logtypes::LOG_DONATE_SUCCESS, "LOG_DONATE_SUCCESS", [$amount, $pay_system]);

        }else{
            // Если это бонус пожертвований
            self::addLog(logtypes::LOG_DONATE_BONUS_SUCCESS, "LOG_DONATE_BONUS_SUCCESS", [$amount]);
        }

        sql::run(
            "INSERT INTO `donate_history_pay` (`user_id`, `point`, `message`, `pay_system`, `id_admin_pay`, `date`, `sphere`) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $this->getId(),
                ceil($amount),
                $message,
                $pay_system,
                $id_admin_pay,
                time::mysql(),
                $isSphereSystem, //Означает что это зачисление от sphere
            ]
        );
    }

    /** Возвращает историю пожертвваний */
    public function getHistoryDonate($getPoint = false)
    {
        if ($getPoint) {
            $point = sql::getRow(
                "SELECT SUM(donate_history_pay.point) AS `point` FROM donate_history_pay WHERE sphere = 0 AND user_id = ?;",
                [$this->getId()]
            );
            if ($point) {
                return $point['point'];
            }

            return 0;
        }

        return sql::getRows("SELECT * FROM `donate_history_pay` WHERE user_id = ?", [$this->getId()]);
    }

    public function isPlayer($playerName): characterModel|false
    {
        if ($this->accounts === null) {
            return false;
        }
        foreach ($this->accounts as $players) {
            foreach ($players->getCharacters() as $player) {
                if ($player->getPlayerName() == $playerName) {
                    return $player;
                }
            }
        }

        return false;
    }

    public function getPlayerCount(): int
    {
        return $this->accounts ? count($this->accounts) : 0;
    }

    public function __toString(): string
    {
        return "Нет выбранного у метода: Список доступных методов в <src/model/user/userModel.php>";
    }

    public function countWarehouseItems(): int
    {
        if (!$this->isAuth) {
            return 0;
        }
        if ($this->countWarehouseItems === null) {
            return count($this->getWarehouse());
        }

        return $this->countWarehouseItems;
    }

    /**
     * @return warehouse[]
     */
    public function getWarehouse($reload = false): mixed
    {
        if ($reload) {
            return $this->warehouse();
        }

        return $this->warehouse;
    }

    /**
     * @param mixed $warehouse
     */
    public function setWarehouse($warehouse): void
    {
        $this->warehouse = $warehouse;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     *
     * @return userModel
     */
    public function setName(?string $name): userModel
    {
        sql::run("UPDATE `users` SET `name` = ? WHERE `id` = ?", [$name, $this->getId()]);
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

    //Запись в историю пожертвований

    /**
     * @param string|null $signature
     *
     * @return userModel
     */
    public function setSignature(?string $signature): userModel
    {
        $this->signature = $signature;

        return $this;
    }

    /**
     * @param string|null $accessLevel
     *
     * @return userModel
     */
    public function setAccessLevel(?string $accessLevel): userModel
    {
        $this->accessLevel = $accessLevel;

        return $this;
    }

    /**
     * @param string|null $avatar
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
     * @param string|null $timezone
     *
     * @return userModel
     */
    public function setTimezone(?string $timezone): userModel
    {
        sql::run("UPDATE `users` SET `timezone` = ? WHERE `id` = ?", [$timezone, $this->getId()]);
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * @param string|null $country
     *
     * @return userModel
     */
    public function setCountry(?string $country): userModel
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @param string|null $city
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
     * @param DateTime|null $dateCreate
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
     * @param DateTime|null $dateUpdate
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
     * @param float|int $purchaseAmount Стоимость покупки.
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
        if (!is_numeric($userBalance) || is_nan($userBalance) || is_infinite($userBalance)) {
            board::error('Некорректное значение баланса пользователя.');
        }

        if (!is_numeric($purchaseAmount) || is_nan($purchaseAmount) || is_infinite($purchaseAmount)) {
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
    public function getDonate(): int|float
    {
        return floor($this->donate * 10) / 10;
    }

    public function getBalance(): int|float
    {
        return $this->getDonate();
    }

    /**
     * @param int $donate
     *
     * @return userModel
     */
    public function setDonate(int $donate): userModel
    {
        $this->donate = $donate;

        return $this;
    }

    /**
     * @param int $donate
     *
     * @return userModel
     */
    public function setBalance(int $donate): userModel
    {
        return $this->setDonate($donate);
    }

    /**
     * Добавляет средства на счет пользователя.
     *
     * @param float|int $amount Сумма для добавления.
     *
     * @return \Ofey\Logan22\model\user\userModel Возвращает true, если операция успешна.
     */
    public function donateAdd(float|int $amount)
    {
        if ($amount < 0) {
            board::error("Сумма должна быть положительным числом.");
        }
        $this->donate += round($amount, 1);
        $this->donateUpdate();

        return $this;
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
     * @param float $amount Сумма для уменьшения.
     *
     * @return bool Возвращает true, если операция успешна и средств достаточно.
     */
    public function donateDeduct(float $amount): bool
    {
        if ($amount < 0) {
            board::error(lang::get_phrase('The amount must be a positive number'));
        }

        if ($this->donate < $amount) {
            return false;  // Недостаточно средств
        }

        $this->donate -= $amount;

        return $this->donateUpdate();
    }

    public function getCountPlayers(): int
    {
        $allPlayerCount = 0;
        foreach ($this->getAccounts() as $accounts) {
            $allPlayerCount += count($accounts->getCharacters());
        }
        return $allPlayerCount;
    }

    /**
     * @param null $account
     *
     * @return array|null|AccountModel[]
     */
    public function getAccounts($account = null): array|null|false|AccountModel
    {
        if ($this->accounts === []) {
            return [];
        }
        $this->accounts = $this->getLoadAccounts();
        if ($this->accounts === null or $this->accounts == []) {
            return [];
        }
        foreach ($this->accounts as $accountObj) {
            if ($accountObj->getAccount() == $account) {
                return $accountObj;
            }
        }

        return $this->accounts;
    }

    /**
     * Чтение и запись (обновление) информации о персонажах в таблицу player_accounts
     *
     * @return array
     */
    public function getLoadAccounts($need_reload = false): ?array
    {
        // Проверяем базовые условия
        if (server::get_count_servers() == 0 || $this->getServerId() === null || !$this->isAuth()) {
            return null;
        }

        // Возвращаем кешированные аккаунты, если они есть и не требуется принудительное обновление
        if ($this->accounts !== null && !$need_reload) {
            return $this->accounts;
        }

        // Инициализируем хранилище времени обновления аккаунтов, если оно еще не существует
        if (!isset($_SESSION['account_update_times'][$this->getServerId()])) {
            $_SESSION['account_update_times'][$this->getServerId()] = [];
        }

        $currentTime = time();
        $updateInterval = 7; // Интервал обновления в секундах

        // Загружаем данные из базы данных
        $accounts = sql::getRows(
            "SELECT `id`, `login`, `password`, `password_hide`, `email`, `ip`, `server_id`, `characters`, `date_update_characters` 
         FROM `player_accounts` 
         WHERE email = ? AND server_id = ?;",
            [$this->getEmail(), $this->getServerId()]
        );

        // Если нужно принудительное обновление или нет данных в базе
        $needFullUpdate = $need_reload || empty($accounts);

        // Если не требуется полное обновление, проверим каждый аккаунт индивидуально
        if (!$needFullUpdate) {
            $accountsToUpdate = [];
            $this->accounts = [];

            foreach ($accounts as $player) {
                $login = $player['login'];
                $lastUpdateTime = $_SESSION['account_update_times'][$this->getServerId()][$login] ?? 0;

                // Проверяем, нужно ли обновлять этот конкретный аккаунт
                $needUpdate = ($currentTime - $lastUpdateTime) >= $updateInterval;

                if ($needUpdate) {
                    // Добавляем аккаунт в список на обновление
                    $accountsToUpdate[] = $login;
                }

                // Создаем объект аккаунта из имеющихся данных
                $account = new accountModel();
                $account->setAccount($player['login']);
                $account->setPassword($player['password']);
                $account->setPasswordHide($player['password_hide']);

                if (empty($player['characters']) || $player['characters'] === '[]') {
                    $account->setCharacters();
                } else {
                    $characters = json_decode($player['characters'], true);
                    $account->setCharacters($characters);
                }

                $this->accounts[] = $account;
            }

            // Если нет аккаунтов для обновления, возвращаем данные из БД
            if (empty($accountsToUpdate)) {
                return $this->accounts;
            }

            // Если есть аккаунты для обновления, делаем запрос только для них
            $needFullUpdate = true; // Временно, пока не реализован частичный запрос
        }

        // Если требуется полное обновление или есть аккаунты для частичного обновления
        if ($needFullUpdate) {
            // Получаем данные через API
            $sphere = \Ofey\Logan22\component\sphere\server::send(type::ACCOUNT_PLAYERS, [
                'forced' => $need_reload,
                'email' => $this->getEmail(),
                // Можно добавить список аккаунтов для частичного обновления
                // 'accounts' => $accountsToUpdate, // Потребуется доработка API
            ])->show(false)->getResponse();

            if (isset($sphere['error']) || !$sphere) {
                $this->accounts = [];
                return [];
            }

            $this->accounts = [];
            foreach ($sphere as $player) {
                if (empty($player['login'])) {
                    continue;
                }

                $account = new accountModel();
                $account->setAccount($player['login']);
                $account->setPassword($player['password']);
                $account->setPasswordHide($player['is_password_hide'] ?? false);
                $account->setCharacters($player['characters']);
                $this->accounts[] = $account;

                // Обновляем время для этого конкретного аккаунта
                $_SESSION['account_update_times'][$this->getServerId()][$player['login']] = $currentTime;
            }

            // Сохраняем аккаунты в базу данных
            $this->saveAccounts();
        }

        return $this->accounts;
    }

    public function getServerId(): ?int
    {
        // Если ID сервера уже установлен
        if (!empty($this->serverId)) {
            return $this->serverId;
        }

        // Проверяем значение в сессии
        if (!$this->isAuth && !empty($_SESSION['server_id'])) {
            $this->serverId = (int)$_SESSION['server_id'];
            return $this->serverId;
        }

        // Получаем все серверы
        $servers = server::getServerAll();
        if (empty($servers)) {
            return null;
        }

        // Проверяем сервер по умолчанию
        foreach ($servers as $server) {
            if ($server->isDefault()) {
                $this->serverId = $server->getId();
                return $this->serverId;
            }
        }

        // Если сервер по умолчанию отсутствует, берем последний сервер
        $lastServer = server::getLastServer();
        if ($lastServer) {
            $this->serverId = $lastServer->getId();
        }

        return $this->serverId ?? null;
    }

    public function setServerId(int $serverId): void
    {
        if (user::getUserId()->isAuth()) {
            $this->changeServerId($serverId, user::getUserId()->getId());
        } else {
            $_SESSION['server_id'] = $serverId;
        }
        $this->serverId = $serverId;
    }

    public function isAuth(): bool
    {
        return $this->isAuth;
    }

    public function setIsAuth(bool $isAuth): void
    {
        $this->isAuth = $isAuth;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return userModel
     */
    public function setEmail(string $email): userModel
    {
        $this->email = $email;

        return $this;
    }

    public function setAccount(string $accountName, string $password = "", bool $password_hide = false, array $characters = [])
    {
        $account = new accountModel();
        $account->setAccount($accountName);
        $account->setPassword($password);
        $account->setPasswordHide($password_hide);
        $account->setCharacters($characters);
        $this->account[] = $account;

        return $this->account;
    }

    /**
     * @param string $password
     *
     * @return userModel
     */
    public function setPassword(string $password): userModel
    {
        $password = password_hash($password, PASSWORD_BCRYPT);
        sql::run("UPDATE `users` SET `password` = ? WHERE `id` = ?", [$password, $this->getId()]);
        $this->password = $password;

        return $this;
    }

    function saveAccounts()
    {
        // Удаляем старые записи для текущего сервера и email
        sql::run('DELETE FROM `player_accounts` WHERE `email` = ? AND `server_id` = ?;', [
            $this->getEmail(),
            $this->getServerId(),
        ]);

        $currentTime = time::mysql();
        $sql = 'INSERT INTO `player_accounts` (`login`, `password`, `email`, `server_id`, `characters`, `date_update_characters`, `password_hide`) VALUES (?, ?, ?, ?, ?, ?, ?);';

        foreach ($this->accounts as $account) {
            sql::run($sql, [
                $account->getAccount(),
                $account->getPassword(),
                $this->getEmail(),
                (int)$this->getServerId(),
                json_encode($account->getCharactersArray()),
                $currentTime,
                (int)$account->isPasswordHide(),
            ]);

            // Обновляем время последнего обновления для этого аккаунта в сессии
            if (!isset($_SESSION['account_update_times'])) {
                $_SESSION['account_update_times'] = [];
            }
            if (!isset($_SESSION['account_update_times'][$this->getServerId()])) {
                $_SESSION['account_update_times'][$this->getServerId()] = [];
            }
            $_SESSION['account_update_times'][$this->getServerId()][$account->getAccount()] = time();
        }
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function addLog(logTypes $type, $phrase, $variables = []): void
    {
        $variables = json_encode($variables);
        $request = $_REQUEST;

        if (isset($_REQUEST['g-recaptcha-response'])) {
            $request['g-recaptcha-response'] = '_REMOVED_';
        }
        if (isset($_REQUEST['PHPSESSID'])) {
            $request['PHPSESSID'] = '_REMOVED_';
        }
        if (isset($_REQUEST['password'])) {
            $request['password'] = '_REMOVED_';
        }

        $request = json_encode($_REQUEST);
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_SERVER['REQUEST_URI'];
        $trace = debug_backtrace()[0];
        $file = $trace['file'];
        $line = $trace['line'];
        sql::run(
            "INSERT INTO `logs_all` (`user_id`, `time`, `type`, `phrase`, `variables`, `server_id`, `request`, `method`, `action`, `file`, `line`) VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$this->getId(), time::mysql(), $type->value, $phrase, $variables, $this->getServerId(), $request, $method, $action, $file, $line]
        );
    }

    public function getLogs(logTypes $type): array
    {

        $query = "SELECT `id`, `user_id`, `time`, `type`, `phrase`, `variables`, `server_id`, `request`, `method`, `action`, `file`, `line` 
              FROM `logs_all`
              WHERE `user_id` = ? AND type = ?
              ORDER BY `time` DESC";

        $results = sql::run($query, [
            $this->getId(),
            $type->value,
        ])->fetchAll();

        // Преобразуем данные обратно, если нужно
        foreach ($results as &$result) {
            $result['variables'] = json_decode($result['variables'], true);
            $result['request'] = json_decode($result['request'], true);
        }

        return $results;
    }


    /**
     * Добавить предмето пользователю в склад
     *
     * @param int|string $server_id
     * @param int|string $item_id
     * @param int|string $count
     * @param int|string $enchant
     * @param string|int $phrase
     *
     * @return array
     * @throws \Exception
     */
    public function addToWarehouse(
        int|string $server_id = 0,
        int|string $item_id = 0,
        int|string $count = 0,
        int|string $enchant = 0,
        string|int $phrase = 'none'
    ): array
    {
        if ($server_id == 0) {
            $server_id = $this->serverId();
        }
        $stmt = sql::run(
            "INSERT INTO `warehouse` (`user_id`, `server_id`, `item_id`, `count`, `enchant`, `phrase`) VALUES (?, ?, ?, ?, ?, ?)", [
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
     * Проверка наличия предметов в складе
     * Мы сопоставляем список предметов на отправку игроку с тем списком предметов что есть
     * Если пользователь отправил ID объекта предмета, которого нет у пользователя, возвращаем false
     *
     * @param $objectItems
     *
     * @return bool
     */
    public function checkItemsWarehouse($objectItems): bool
    {
        $warehouseItems = array_map(fn($item) => $item->getId(), user::self()->getWarehouse());
        foreach ($objectItems as $itemId) {
            if (!in_array($itemId, $warehouseItems)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Перевод из склада в игровой инвентарь
     *
     * @param $playerName
     * @param $objectItems
     *
     * @return bool
     * @throws \Exception
     */
    public function warehouseToGame($playerName, $objectItems): bool
    {
        $isSend = false;
        sql::transaction(function () use ($playerName, $objectItems, &$isSend) {
            foreach (user::self()->getWarehouse() as $warehouse) {
                if (in_array($warehouse->getId(), $objectItems)) {
                    $itemId = $warehouse->getItemId();
                    $count = $warehouse->getCount();
                    $enchant = $warehouse->getEnchant();
                    $serverId = $warehouse->getServerId();
                    userlog::add("inventory_to_game", 542, [$itemId, $count, $enchant, $playerName]);
                    $this->addToInventoryPlayer($serverId, $itemId, $count, $enchant, $playerName);
                    $this->removeWarehouseObjectId($warehouse->getId());
                    $isSend = true;
                }
            }
        });

        return $isSend;
    }

    /**
     * Отправка в игровой инвентарь
     *
     * @param $serverId
     * @param $itemId
     * @param $count
     * @param $enchant
     * @param $playerName
     *
     * @return void
     */
    public function addToInventoryPlayer($serverId, $itemId, $count, $enchant, $playerName): void
    {
        player_account::addItem($serverId, $itemId, $count, $enchant, $playerName);
    }

    /**
     * Удаление предметов из склада.
     * Удалить можно по ID или массиву ID объектов
     *
     * @param int|array $objectId
     *
     * @return void
     * @throws \Exception
     */
    public function removeWarehouseObjectId(int|array $objectId)
    {
        if (is_array($objectId)) {
            // Формируем строку с плейсхолдерами для каждого элемента массива
            $placeholders = implode(',', array_fill(0, count($objectId), '?'));
            sql::run("DELETE FROM `warehouse` WHERE `id` IN ($placeholders)", $objectId);
        } else {
            sql::run("DELETE FROM `warehouse` WHERE `id` = ?", [$objectId]);
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

    public function isGuest(): bool
    {
        return (bool)$this->getAccessLevel("guest");
    }

    // Добавляем приватное свойство для кэша
    private array $varCache = [];

    // Обновляем метод addVar чтобы он очищал кэш при добавлении новых данных
    public function addVar(string $name, mixed $data, $server = null) {
        if ($server === null) {
            $server = 0;
        }

        sql::run(
            'DELETE FROM `user_variables` WHERE `server_id` = ? AND `user_id` = ? AND `var` = ?',
            [$server, $this->getId(), $name]
        );

        $result = sql::run(
            'INSERT INTO `user_variables` (`server_id`, `user_id`, `var`, `val`) VALUES (?, ?, ?, ?)',
            [$server, $this->getId(), $name, $data]
        );

        // Очищаем кэш при добавлении новых данных
        $this->clearVarCache();

        return $result;
    }

    public function getVar(string $name, $serverId = null) {
        // Проверяем наличие данных в кэше
        if (isset($this->varCache[$name])) {
            return $this->varCache[$name];
        }

        // Если данных нет в кэше, получаем их из БД
        if ($serverId !== null) {
            $result = sql::getRow(
                'SELECT `val` FROM `user_variables` WHERE `server_id` = ? AND `user_id` = ? AND `var` = ?',
                [$serverId, $this->getId(), $name]
            );
        } else {
            $result = sql::getRow(
                'SELECT `val` FROM `user_variables` WHERE `user_id` = ? AND `var` = ?',
                [$this->getId(), $name]
            );
        }

        // Сохраняем результат в кэш
        $this->varCache[$name] = $result;

        return $result;
    }

    // Добавляем метод для очистки кэша
    public function clearVarCache(): void {
        $this->varCache = [];
    }

    public function toArray(): array
    {
        $obj = get_object_vars($this);
        $obj['avatar'] = $this->getAvatar();
        $obj['timezone'] = $this->getTimezone();
        $obj['country'] = $this->getCountry();
        $obj['city'] = $this->getCity();
        $obj['serverId'] = $this->getServerId();
        $obj['lang'] = $this->getLang();

        return $obj;
    }

    /**
     * @return string|null
     */
    public function getAvatar(): ?string
    {
        return ("/uploads/avatar/{$this->avatar}");
    }

    /**
     * @return string|null
     */
    public function getTimezone(): ?string
    {
        return $this->timezone ?? "Europe/Moscow";
    }

    /**
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    //Добавление переменной

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    // Получение переменной
    // Если $serverId null , тогда не обращаем внимание на сервер

    public function getLang(): string
    {
        if ($this->lang == null) {
            $this->lang = config::load()->lang()->lang_user_default();
            return $this->lang;
        }
        if (config::load()->lang()->name($this->lang)) {
            return $this->lang;
        }

        return config::load()->lang()->getDefault();
    }

    public function setLang($lang = 'en')
    {
        if (in_array($lang, config::load()->lang()->getAllowLanguages())) {
            if (config::load()->lang()->name($lang)) {
                session::add("lang", $lang);
            }
        } else {
            $lang = config::load()->lang()->getDefault();
            session::add("lang", $lang);
        }
        if ($this->isAuth) {
            sql::run("UPDATE `users` SET `lang` = ? WHERE `id` = ?", [$lang, $this->getId()]);
        }
        redirect::location($_SERVER['HTTP_REFERER'] ?? "/main");
    }

}