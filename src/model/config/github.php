<?php

namespace Ofey\Logan22\model\config;

use Ofey\Logan22\component\github\gitdata;
use Ofey\Logan22\model\db\sql;

class github
{

    public ?array $gitdata = null;

    private string $repo_owner = 'Cannabytes';

    private string $repo_name = 'SphereWeb2';

    private string $githubToken = '';

    private int $tokenDays = 90;

    private ?string $lastSHA = null;

    public function __construct()
    {
        $configData        = sql::getRow("SELECT * FROM `settings` WHERE `key` = '__config_github__'");
        $setting           = json_decode($configData['setting'], true);
        $this->githubToken = $setting['githubToken'];
        $this->tokenDays   = $setting['tokenDays'];
    }

    public function getUpdateSphere(): ?array
    {
        if ($this->gitdata != null) {
            return $this->gitdata;
        }
        $repo_owner = $this->repo_owner;
        $repo_name  = $this->repo_name;
        $commit_sha = $this->getLastUpdateSphereCommitSHA();
        if ($commit_sha === null) {
            return [];
        }
        $api_url = "https://api.github.com/repos/$repo_owner/$repo_name/commits?since=$commit_sha";
        $ch      = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'Accept: application/vnd.github.v3+json', // Указываем версию API
          'User-Agent: SphereWeb-Agent', // Указываем имя вашего приложения
          'Authorization: token ' . $this->githubToken,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $commits = json_decode($response, true);
        if(isset($commits['message'])){
            if($commits['message'] == 'Bad credentials'){
                //Когда токен ошибочный либо отсутствует
                return null;
            }
        }
        foreach ($commits as $commit) {
            $this->gitdata[] = new gitdata($commit);
        }
        return $this->gitdata;
    }

    /**
     * Последний SHA локального обновления
     *
     * @return mixed
     */
    public function getLastUpdateSphereCommitSHA(): string|null
    {
        if ($this->lastSHA !== null) {
            return $this->lastSHA;
        }
        $github = sql::getRow("SELECT * FROM `github_updates` ORDER BY `id` DESC LIMIT 1");
        if ($github) {
            $this->lastSHA = $github['sha'];
        }

        return $this->lastSHA;
    }

    public function getToken(): string
    {
        return $this->githubToken;
    }

    public function getTokenDays(): int
    {
        return $this->tokenDays;
    }

}