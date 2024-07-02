<?php

namespace Ofey\Logan22\model\config;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\github\gitdata;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\db\sql;

class github
{

    public ?array $gitdata = null;

    private string $repo_owner = 'Cannabytes';

    private string $repo_name = 'SphereWeb2';

    private string $githubToken = '';

    private int $tokenDays = 90;

    private ?string $lastSHA = null;

    private ?int $countCommits = null;

    public function __construct()
    {
        $configData        = sql::getRow("SELECT * FROM `settings` WHERE `key` = '__config_github__'");
        if($configData){
            $setting           = json_decode($configData['setting'], true);
            $this->githubToken = $setting['githubToken'] ?? '';
            $this->tokenDays   = $setting['tokenDays'] ?? 90;
        }else{
            $this->error       = true;
            $this->errorMessage = 'Настройки github не найдены';
            $this->githubToken = '';
            $this->tokenDays   = 90;
        }
    }

    public function getToken(): string
    {
        return $this->githubToken;
    }

    /**
     * Количество актуальных коммитов
     *
     * @return int|null
     */
    public function getCountCommits(): ?int
    {
        if ($this->countCommits !== null) {
            return $this->countCommits;
        }
        $commits            = $this->getActualCommits();
        $this->countCommits = count($commits);

        return $this->countCommits;
    }

    /**
     * @return array
     */
    private function getActualCommits(): array
    {
        $commits = $this->getUpdateSphere();
        if($commits===null){
            return [];
        }
        //Оставляем только актуальные коммиты, до последнего обновления
        $lastSHA        = $this->getLastUpdateSphereCommitSHA();
        $findLastCommit = false;
        foreach ($commits as $key => $commit) {
            if ($findLastCommit) {
                unset($commits[$key]);
            }
            if ($commit->getSHA() == $lastSHA) {
                $findLastCommit = true;
                unset($commits[$key]);
            }
        }

        return $commits ?? [];
    }

    private bool $error = false;
    public function isError(): bool {
        return $this->error;
    }

    private string $errorMessage = '';
    public function getError(): string {
        return $this->errorMessage;
    }

    /**
     * Получение списка коммитов
     *
     * @return gitdata[]|null
     */
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
        if (isset($commits['message'])) {
            if ($commits['message'] == 'Bad credentials') {
                $this->error = true;
                $this->errorMessage = 'Токен GitHub неверный, сгенерируйте себе новый.';
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

    public function getTokenDays(): int
    {
        return $this->tokenDays;
    }

    public function update()
    {
        $repo_owner = $this->repo_owner;
        $repo_name  = $this->repo_name;
        $commits    = $this->getActualCommits();
        if (count($commits) == 0) {
            board::success("Новых коммитов нет");
        }
        $commits = array_reverse($commits);
        foreach ($commits as $commit) {
            $commit_sha      = $commit->getSHA();
            $commit_response = self::getContents("https://api.github.com/repos/$repo_owner/$repo_name/commits/$commit_sha");
            if ($commit_response === null) {
                continue; // Пропускаем этот коммит, если произошла ошибка
            }
            $commit_data = json_decode($commit_response, true);
            $gitdata     = new gitdata();
            $gitdata->getDataCommit(
              $commit_data['sha'],
              $commit_data['html_url'],
              $commit_data['commit']['author']['name'],
              $commit_data['commit']['message'],
              $commit_data['commit']['author']['date']
            );
            // Получаем список измененных файлов
            $files = $commit_data['files'];
            foreach ($files as $file) {
                $file_path = $file['filename'];
                $status    = $file['status'];

                if ($status != "added" && $status != "modified" && $status != "removed") {
                    continue;
                }

                if ($status == "removed") {
                    if (file_exists(fileSys::get_dir($file_path))) {
                        unlink(fileSys::get_dir($file_path));
                    }
                    $this->saveDBCommit($gitdata);
                    continue;
                }

                $file_url  = "https://raw.githubusercontent.com/$repo_owner/$repo_name/$commit_sha/$file_path";
                $directory = dirname($file_path);

                // Создаем каталоги, если они не существуют
                if (!file_exists(fileSys::get_dir( $directory))) {
                    mkdir(fileSys::get_dir($directory), 0755, true);
                }

                // Скачиваем файл
                $file_content = file_get_contents($file_url);
                file_put_contents(fileSys::get_dir("test/" . $file_path), $file_content);

                $this->saveDBCommit($gitdata);
            }
        }

        board::alert([
          'type'    => 'notice',
          'ok'      => 'success',
          'message' => "Успешно обновлено",
          'reload'  => true,
        ]);
    }

    private function getContents($url): bool|string|null
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'Accept: application/vnd.github.v3+json',
          'User-Agent: SphereWeb-Agent',
          'Authorization: token ' . $this->githubToken,
        ]);

        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code >= 200 && $http_code < 300) {
            return $response;
        } else {
            echo "Ошибка HTTP $http_code при запросе $url\n";

            return null;
        }
    }

    public function saveDBCommit(gitdata $gitdata = null): void
    {
        if ($gitdata === null) {
            return;
        }
        sql::run("INSERT INTO `github_updates` (`sha`, `author`, `url`, `message`, `date`, `date_update`) VALUES (?, ?, ?, ?, ?, ?)", [
          $gitdata->getSHA(),
          $gitdata->getAuthor(),
          $gitdata->getUrl(),
          $gitdata->getMessage(),
          $gitdata->getDate(),
          time::mysql(),
        ]);
        if (sql::isError()) {
            $err = sql::getException();
            board::error($err->getMessage());
        }
    }

}