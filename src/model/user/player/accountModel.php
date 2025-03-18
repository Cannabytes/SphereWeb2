<?php

namespace Ofey\Logan22\model\user\player;

class accountModel
{

    private string $accountName;

    private string $password;

    private mixed $password_hide;

    private int $charactersCount;

    /**
     * @var \Ofey\Logan22\model\user\player\characterModel[]
     */
    private array $character = [];

    private mixed $charactersArray = [];

    /**
     * @return mixed
     */
    public function getAccount()
    {
        return $this->accountName;
    }

    /**
     * @param   string  $accountName
     *
     * @return accountModel
     */
    public function setAccount($accountName)
    {
        $this->accountName = $accountName;

        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param   string  $password
     *
     * @return accountModel
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPasswordHide(): bool
    {
        return $this->password_hide;
    }

    /**
     * @param   mixed  $password_hide
     *
     * @return accountModel
     */
    public function setPasswordHide($password_hide)
    {
        $this->password_hide = $password_hide;

        return $this;
    }

    public function getCharactersArray(): array
    {
        return $this->charactersArray;
    }

    public function setCharacters(?array $characters = null): void
    {
        if ($characters == null) {
            $this->character       = [];
            $this->charactersCount = 0;
            return;
        }
        foreach ($characters as $character) {
            if ( ! isset($character)) {
                continue;
            }
            $this->charactersArray[] = $character;
            $this->character[]       = new characterModel($character);
            $this->charactersCount   = count($this->character);
        }
    }

    /**
     * @return \Ofey\Logan22\model\user\player\characterModel[]
     */
    public function getCharacters(): array
    {
        return $this->character;
    }

    /**
     * @return int
     */
    public function getCharactersCount(): int
    {
        return $this->charactersCount;
    }

}