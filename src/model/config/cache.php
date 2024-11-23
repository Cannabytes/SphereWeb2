<?php
/** UPDATE **/

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

    public function __construct($setting)
    {
        $this->forum    = isset($setting['forumCache']) ? (int)$setting['forumCache'] : $this->forum;
        $this->status   = isset($setting['statusCache']) ? (int)$setting['statusCache'] : $this->status;
        $this->pvp      = isset($setting['pvpCache']) ? (int)$setting['pvpCache'] : $this->pvp;
        $this->pk       = isset($setting['pkCache']) ? (int)$setting['pkCache'] : $this->pk;
        $this->clan     = isset($setting['clanCache']) ? (int)$setting['clanCache'] : $this->clan;
        $this->castle   = isset($setting['castleCache']) ? (int)$setting['castleCache'] : $this->castle;
        $this->referral = isset($setting['referralCache']) ? (int)$setting['referralCache'] : $this->referral;
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