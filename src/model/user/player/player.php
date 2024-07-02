<?php

namespace Ofey\Logan22\model\user\player;

use DateTime;

class player
{
    private int $id = 0;
    private string $account = '';
    private string $password = '';
    private string $email = '';
    private string $ip = '';
    private int $server_id = 0;
    private bool $password_hide = true;
    private DateTime $date_create;
    private DateTime $date_update;

    private array $characters = [];

    public function __construct()
    {

    }

    // Возвращаем персонажей
    public function characters()
    {

    }

    public function getAccount()
    {
        return $this->account;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function setEmail(string $email): player
    {
        $this->email = $email;
        return $this;
    }

    public function setIp(string $ip): player
    {
        $this->ip = $ip;
        return $this;
    }

    public function setServerId(int $server_id): player
    {
        $this->server_id = $server_id;
        return $this;
    }

    public function setPasswordHide(bool $password_hide = true): player
    {
        $this->password_hide = $password_hide;
        return $this;
    }

    public function getDateCreate(): DateTime
    {
        return $this->date_create;
    }

    public function setDateCreate(DateTime $date_create): void
    {
        $this->date_create = $date_create;
    }

    public function getDateUpdate(): DateTime
    {
        return $this->date_update;
    }

    public function setDateUpdate(DateTime $date_update): void
    {
        $this->date_update = $date_update;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getServerId(): int
    {
        return $this->server_id;
    }

    public function isPasswordHide(): bool
    {
        return $this->password_hide;
    }

    public function setAccount(string $account): void
    {
        $this->account = $account;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCharacters(): array
    {
        return $this->characters;
    }

    public function getCharactersCount(): int
    {
        return count($this->characters);
    }

    public function setCharacters(characterModel $characters): void
    {
        $this->characters[] = $characters;
    }


}