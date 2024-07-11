<?php

namespace Ofey\Logan22\model\user;

use DateTime;
use Exception;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\session\session;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\admin\userlog;
use Ofey\Logan22\model\db\sql;
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

    private string $email = '', $password = '';

    private ?string $name = null, $signature = null, $ip = '0.0.0.0', $accessLevel = 'guest', $avatar = 'none.jpeg', $timezone, $country = null, $city = null;

    private ?DateTime $dateCreate, $dateUpdate;

    private ?array $warehouse = null;

    /**
     * @var array|null accountModel[]
     */
    private null|false|array $accounts = null;

    private int $id;

    private float|int $donate = 0;

    private ?int $serverId = null;

    private bool $isAuth = false;

    private ?int $countWarehouseItems = null;

    private array $characters = [];

    /**
     * @var $account accountModel[]
     */
    private array $account = [];

    private ?string $lang = 'en';

    public function getInstance(): userModel
    {
        return $this;
    }

    public function __construct(?int $userId = null)
    {
        if ($userId == null) {
            return $this;
        }
        $user = sql::getRow(
          "SELECT `id`, `email`, `password`, `name`, `signature`, `ip`, `date_create`, `date_update`, `access_level`, `donate_point`, `avatar`, `avatar_background`, `timezone`, `country`, `city`, `server_id`, `lang` FROM `users` WHERE id = ? LIMIT 1",
          [$userId]
        );
        if ($user) {

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
            //Проверим, актуален ли сервер, который у пользователя
            $lastServer = server::getServer($user['server_id']);
            if ($lastServer !== null) {
                $server_id = $lastServer->getId();
                if ($server_id !== null) {
                    $this->changeServerId($server_id, $user['id']);
                    $user['server_id'] = $server_id;
                }
            }

            //TODO: Сделать разлогин, если пароль изменился
            //Нужно проверять  ещё isset, так как идет запрос из платежек сюда.
//            if($_SESSION['id'] == $user['id']){
//                if ($user['password'] != password_hash($user['password'], PASSWORD_BCRYPT)) {
//                    $user['password'] = null;
//                    session::clear();
//                    return null;
//                }
//            }


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
            $this->lang        = $user['lang'];

            \Ofey\Logan22\component\sphere\server::setUser($this);
            $this->warehouse = $this->warehouse() ?? null;
            $this->accounts  = false;

            //            $this->players   = $this->getCharacters();

            return $this;
        }

        return null;
    }

    public function getId(): int
    {
        return $this->id ?? 0;
    }

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

    public function setUser($user)
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
        $this->lang        = $user['lang'];

        $this->warehouse = $this->warehouse() ?? null;
        $this->accounts  = false;
        //        $this->accounts  = $this->getLoadAccounts();

        //        $this->players     = $this->getCharacters();

        return $this;
    }

    /**
     * @return warehouse[]|array
     */
    private function warehouse(): array
    {
        $items          = sql::getRows(
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

    //Кол-во предметов на складе

    private function serverId()
    {
        return $this->serverId;
    }

    /**
     * @param   null  $account
     *
     * @return array|null|AccountModel[]
     */
    public function getAccounts($account = null): array|null|false|AccountModel
    {
        if ($this->accounts === null) {
            return null;
        }
        $this->accounts = $this->getLoadAccounts();
        foreach ($this->accounts as $accountObj) {
            if ($accountObj->getAccount() == $account) {
                return $accountObj;
            }
        }
        if ($this->accounts === null or $this->accounts == []) {
            return [];
        }

        return $this->accounts;
    }

    /**
     * Чтение и запись (обновление) информации о персонажах в таблицу player_accounts
     *
     * @return array
     */
    public function getLoadAccounts($reload = false): ?array
    {
        if ($this->getServerId() == null) {
            return null;
        }
        if ( ! $this->isAuth()) {
            return null;
        }
        if ($this->accounts != null) {
            return $this->accounts;
        }

        $accounts = sql::getRows(
          "SELECT `id`, `login`, `password`, `password_hide`, `email`, `ip`, `server_id`, `characters`, `date_update_characters` FROM `player_accounts` WHERE email = ? AND server_id = ?;",
          [
            $this->getEmail(),
            $this->getServerId(),
          ]
        );

        $currentTime = time();
        $needUpdate  = true;
        foreach ($accounts as $account) {
            if ($account['date_update_characters'] == null) {
                $needUpdate = true;
                break;
            }
            $accountUpdateTime = strtotime($account['date_update_characters']);
            if (($currentTime - $accountUpdateTime) < 60) {
                $needUpdate = false;
                break;
            }
        }

        //Если обновление не требуется, выводим данные ранее сохраненные
        if ( ! $needUpdate) {
            $this->accounts = [];
            foreach ($accounts as $player) {
                $account = new accountModel();
                $account->setAccount($player['login']);
                $account->setPassword($player['password']);
                $account->setPasswordHide($player['password_hide']);

                if ($player['characters'] == null or $player['characters'] == '' or $player['characters'] == '[]') {
                    $account->setCharacters();
                } else {
                    $characters = json_decode($player['characters'], true);
                    $account->setCharacters($characters);
                }

                $this->accounts[] = $account;
            }

            return $this->accounts;
        }

        $sphere = \Ofey\Logan22\component\sphere\server::send(type::ACCOUNT_PLAYERS, [
          'email' => $this->getEmail(),
        ])->getResponse();

        if (isset($sphere['error']) or ! $sphere) {
            return [];
        }
        foreach ($sphere as $player) {
            $account = new accountModel();
            $account->setAccount($player['login']);
            $account->setPassword($player['password']);
            $account->setPasswordHide($player['is_password_hide'] ?? false);
            $account->setCharacters($player['characters']);
            $this->account[] = $account;
        }

        $this->saveAccounts();

        //        var_dump($this->account);exit();
        return $this->account;
    }

    public function getServerId(): ?int
    {
        if ($this->serverId === null) {
            if (isset($_SESSION['server_id']) && ! $this->isAuth) {
                return $_SESSION['server_id'];
            }
            $lastServer = server::getLastServer();

            return $lastServer ? $lastServer->getId() : null;
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
     * @param   string  $email
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
     * @param   string  $password
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

        sql::run('DELETE FROM `player_accounts` WHERE `email` = ? AND `server_id` = ?;', [
          $this->getEmail(),
          $this->getServerId(),
        ]);

        $sql = 'INSERT INTO `player_accounts` (`login`, `password`, `email`, `server_id`, `characters`, `date_update_characters`, `password_hide`) VALUES (?, ?, ?, ?, ?, ?, ?);';
        foreach ($this->account as $account) {
            //Удаление старых записей
            sql::run($sql, [
              $account->getAccount(),
              $account->getPassword(),
              $this->getEmail(),
              (int)$this->getServerId(),
              json_encode($account->getCharactersArray()),
              time::mysql(),
              (int)$account->isPasswordHide(),
            ]);
        }
    }

    //Запись в историю пожертвований

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function AddHistoryDonate(
      float|int $amount,
      $message = "За Пожертвование",
      $pay_system = "sphereBonus",
      $id_admin_pay = null
    ): void {
        $isSphereSystem = in_array($pay_system, ["sphereBonus", "cumulativeBonus", "oneTimeBonus"]) ? 1 : 0;

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

    public function isPlayer($playerName): characterModel|false
    {
        if ($this->accounts === null) {
            return false;
        }
        foreach ($this->accounts as $players) {
            foreach ($players->getCharacters() as $player) {
                if ($player->getPlayerName() === $playerName) {
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
        return $this->timezone ?? "Europe/Moscow";
    }

    /**
     * @param   string|null  $timezone
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
    public function getDonate(): int|float
    {
        return floor($this->donate * 10) / 10;
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
     * @param   float|int  $amount  Сумма для добавления.
     *
     * @return \Ofey\Logan22\model\user\userModel Возвращает true, если операция успешна.
     */
    public function donateAdd(float|int $amount)
    {
        if ($amount < 0) {
            board::error("Сумма должна быть положительным числом.");
        }

        $this->donate += ceil($amount);
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
        //        if (empty($this->getPlayers())) {
        //            return 0;
        //        }
        //        $allPlayerCount = 0;
        //        foreach ($this->getPlayers() as $player) {
        //            $allPlayerCount += count($player->getCharacters());
        //        }
        $allPlayerCount = 24;

        return $allPlayerCount;
    }

    public function addLog(logTypes $type, $phrase , $variables = []): void
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

        $request  = json_encode($_REQUEST);
        $method    = $_SERVER['REQUEST_METHOD'];
        $action    = $_SERVER['REQUEST_URI'];
        $trace     = debug_backtrace()[0];
        $file      = $trace['file'];
        $line      = $trace['line'];
        sql::run(
          "INSERT INTO `logs_all` (`user_id`, `time`, `type`, `phrase`, `variables`, `server_id`, `request`, `method`, `action`, `file`, `line`) VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
          [$this->getId(), time::mysql(), $type->value, $phrase, $variables, $this->getServerId(), $request, $method, $action, $file, $line]
        );
    }

    /**
     * Добавить предмето пользователю в склад
     *
     * @param   int|string  $server_id
     * @param   int|string  $item_id
     * @param   int|string  $count
     * @param   int|string  $enchant
     * @param   string|int  $phrase
     *
     * @return array
     * @throws \Exception
     */
    public function addToWarehouse(
      int|string $server_id,
      int|string $item_id,
      int|string $count,
      int|string $enchant,
      string|int $phrase
    ): array {
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
            if ( ! in_array($itemId, $warehouseItems)) {
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
                    $itemId           = $warehouse->getItemId();
                    $count            = $warehouse->getCount();
                    $enchant          = $warehouse->getEnchant();
                    $serverId         = $warehouse->getServerId();
                    $arrObjectItems[] = [
                      'itemId'  => $itemId,
                      'count'   => $count,
                      'enchant' => $enchant,
                    ];
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
     * @param   int|array  $objectId
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

    //Добавление переменной
    public function addVar(string $name, mixed $data) {

        sql::run('DELETE FROM `user_variables` WHERE `server_id` = ? AND `user_id` = ? AND `var` = ?', [
          $this->getServerId(),
          $this->getId(),
          $name,
        ]);

        return sql::run('INSERT INTO `user_variables` (`server_id`, `user_id`, `var`, `val`) VALUES (?, ?, ?, ?)', [
          $this->getServerId(),
          $this->getId(),
          $name,
          $data,
        ]);
    }

    // Получение переменной
    // Если $serverId null , тогда не обращаем внимание на сервер
    public function getVar(string $name, $serverId = null) {
        if ($serverId !== null) {
            return sql::getRow('SELECT `val` FROM `user_variables` WHERE `server_id` = ? AND `user_id` = ? AND `var` = ?', [
              $serverId,
              $this->getId(),
              $name,
            ]);
        }
        return sql::getRow('SELECT `val` FROM `user_variables` WHERE `user_id` = ? AND `var` = ?', [
          $this->getId(),
          $name,
        ]);
    }

    /**
     * @return void
     * @throws \Exception
     */
    private function updateCharacters($login, $charactersAll): void
    {
        foreach ($charactersAll as $characters) {
            if (empty($characters)) {
                $json = "[]";
            } else {
                $json = json_encode($characters);
            }
            sql::run("UPDATE `player_accounts` SET `characters` = ? , `date_update_characters` = ? WHERE `login` = ? AND `server_id` = ?", [
              $json,
              time::mysql(),
              $login,
              $this->getServerId(),
            ]);
        }
    }

    private function savePlayers($players = []): void
    {
        // Удаление старой записи
        sql::run('DELETE FROM `user_players` WHERE `user_id` = ? AND `server_id` = ?;', [
          $this->getId(),
          $this->getServerId(),
        ]);

        $sql = 'INSERT INTO `user_players` (`user_id`, `server_id`, `players`, `date`) VALUES (?, ?, ?, ?);';
        sql::run($sql, [
          $this->getId(),
          $this->getServerId(),
          json_encode($this->objectToArray($players), JSON_UNESCAPED_UNICODE),
          time::mysql(),
        ]);
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

}