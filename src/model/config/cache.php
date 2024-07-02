<?php

namespace Ofey\Logan22\model\config;

use Ofey\Logan22\model\db\sql;

class cache
{

    private int $forum = 60;

    private int $status = 60;

    private int $pvp = 60;

    private int $pk = 60;

    private int $clan = 60;

    private int $castle = 60;

    private int $referral = 60;

    public function __construct()
    {
        $configData     = sql::getRow(
          "SELECT * FROM `settings` WHERE `key` = '__config_cache__'"
        );
        if($configData) {
            $setting        = json_decode($configData['setting'], true);
            $this->forum    = (int)$setting['forumCache'];
            $this->status   = (int)$setting['statusCache'];
            $this->pvp      = (int)$setting['pvpCache'];
            $this->pk       = (int)$setting['pkCache'];
            $this->clan     = (int)$setting['clanCache'];
            $this->castle   = (int)$setting['castleCache'];
            $this->referral = (int)$setting['referralCache'];
        }
    }

    public function getForum(): int
    {
        return $this->forum;
    }
    public function getStatus(): int{
        return $this->status;
    }
    public function getPvp(): int{
        return $this->pvp;
    }
    public function getPk(): int{
        return $this->pk;
    }
    public function getClan(): int{
        return $this->clan;
    }
    public function getCastle(): int{
        return $this->castle;
    }
    public function getReferral(): int{
        return $this->referral;
    }


}