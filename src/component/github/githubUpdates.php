<?php

namespace Ofey\Logan22\component\github;

use Ofey\Logan22\model\db\sql;

class githubUpdates
{

    private ?string $lastSha = null;

    public function __construct()
    {
        return $this;
    }

    public function Sha()
    {
        $github = sql::getRow("SELECT * FROM `github_updates` ORDER BY `id` DESC LIMIT 1");
        if ($github) {
            $this->lastSha = $github['sha'];
        }
        return $this->lastSha;
    }

}