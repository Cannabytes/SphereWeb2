<?php

namespace Ofey\Logan22\model\user\player;

class characters
{
    private string $account_name = '';
    private int $player_id = 0;
    private string $player_name = '';
    private int $pvp = 0;
    private int $pk = 0;
    private string $title = '';
    private int $sex = 0;
    private ?int $clanid = 0;
    private int $level = 0;
    private int $class_id = 0;
    private int $online = 0;
    private int $karma = 0;
    private int $time_in_game = 0;
    private int $isBase = 0;
    private int $createtime = 0;
    private ?string $clan_name = null;
    private ?string $clan_crest = null;
    private ?string $alliance_crest = null;

    public function getPlayerName(): string
    {
        return $this->player_name;
    }

    public function setPlayerName(string $player_name): void
    {
        $this->player_name = $player_name;
    }

    public function getPvp(): int
    {
        return $this->pvp;
    }

    public function setPvp(int $pvp): void
    {
        $this->pvp = $pvp;
    }

    public function getPk(): int
    {
        return $this->pk;
    }

    public function setPk(int $pk): void
    {
        $this->pk = $pk;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getSex(): int
    {
        return $this->sex;
    }

    public function setSex(int $sex): void
    {
        $this->sex = $sex;
    }

    public function getClanId(): ?int
    {
        return $this->clanid;
    }

    public function setClanId(?int $clanid): void
    {
        $this->clanid = $clanid;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    public function getClassId(): int
    {
        return $this->class_id;
    }

    public function setClassId(int $class_id): void
    {
        $this->class_id = $class_id;
    }

    public function getOnline(): int
    {
        return $this->online;
    }

    public function setOnline(int $online): void
    {
        $this->online = $online;
    }

    public function getTimeInGame(): int
    {
        return $this->time_in_game;
    }

    public function setTimeInGame(int $time_in_game): void
    {
        $this->time_in_game = $time_in_game;
    }

    public function getAccountName(): string
    {
        return $this->account_name;
    }

    public function setAccountName(string $account_name): void
    {
        $this->account_name = $account_name;
    }

    public function getPlayerId(): int
    {
        return $this->player_id;
    }

    public function setPlayerId(int $player_id): void
    {
        $this->player_id = $player_id;
    }

    /**
     * @return int
     */
    public function getKarma(): int
    {
        return $this->karma;
    }

    /**
     * @param int $karma
     * @return characters
     */
    public function setKarma(int $karma): characters
    {
        $this->karma = $karma;
        return $this;
    }

    /**
     * @return int
     */
    public function getIsBase(): int
    {
        return $this->isBase;
    }

    /**
     * @param int $isBase
     * @return characters
     */
    public function setIsBase(int $isBase): characters
    {
        $this->isBase = $isBase;
        return $this;
    }

    /**
     * @return int
     */
    public function getCreatetime(): int
    {
        return $this->createtime;
    }

    /**
     * @param int $createtime
     * @return characters
     */
    public function setCreatetime(int $createtime): characters
    {
        $this->createtime = $createtime;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getClanName(): ?string
    {
        return $this->clan_name;
    }

    /**
     * @param string|null $clan_name
     * @return characters
     */
    public function setClanName(?string $clan_name): characters
    {
        $this->clan_name = $clan_name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getClanCrest(): ?string
    {
        return $this->clan_crest;
    }

    /**
     * @param string|null $clan_crest
     * @return characters
     */
    public function setClanCrest(?string $clan_crest): characters
    {
        $this->clan_crest = $clan_crest;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAllianceCrest(): ?string
    {
        return $this->alliance_crest;
    }

    /**
     * @param string|null $alliance_crest
     * @return characters
     */
    public function setAllianceCrest(?string $alliance_crest): characters
    {
        $this->alliance_crest = $alliance_crest;
        return $this;
    }



}