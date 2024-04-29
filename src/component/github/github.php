<?php

namespace Ofey\Logan22\component\github;

use Ofey\Logan22\component\fileSys\fileSys;

class github
{

    public static ?array $gitdata = null;

    private static $token = ''; // замените на имя владельца репозитория

    private static $repo_owner = 'Cannabytes'; // замените на имя репозитория

    private static $repo_name = 'SphereWeb2';

    /**
     * Кол-во элементов в массиве $gitdata
     */
    public static function getCount(): int
    {
        self::getUpdateSphere();
        return count(self::$gitdata ?? []);
    }

    /**
     * Retrieves the latest update from the GitHub repository.
     *
     * This function sends a request to the GitHub API to compare the commits between the specified commit SHA and the latest commit.
     * If there are new commits, it retrieves the commit data and creates a new instance of the gitdata class.
     * If there are no new commits, it displays a message indicating that there are no new commits after the specified commit.
     *
     * @return gitdata[]|null The latest gitdata object if there are new commits, null otherwise.
     */
    public static function getUpdateSphere(): ?array
    {
        if (self::$gitdata != null) {
            return self::$gitdata;
        }

        $repo_owner = self::$repo_owner;
        $repo_name  = self::$repo_name;

        $commit_sha = '40bf3e334224971acc026fb2b0ac141a720e21ec';
        //        $api_url = "https://api.github.com/repos/$repo_owner/$repo_name/compare/$commit_sha...HEAD";
        $api_url = "https://api.github.com/repos/$repo_owner/$repo_name/commits?since=$commit_sha";
        //        var_dump($api_url);exit();
        //        $api_url = "https://api.github.com/repos/$repo_owner/$repo_name/commits";

        // Используем cURL для выполнения запроса к GitHub API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'Accept: application/vnd.github.v3+json', // Указываем версию API
          'User-Agent: SphereWeb-Agent', // Указываем имя вашего приложения
          'Authorization: token ' . self::$token,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        // Преобразуем JSON ответ в массив данных
        $commits = json_decode($response, true);
        //var_dump($commits);exit();
        //        if ( ! empty($data['commits'])) {
        foreach ($commits as $commit) {
            $commit_sha = $commit['sha'];
            //                $commit_response = self::getContents("https://api.github.com/repos/$repo_owner/$repo_name/commits/$commit_sha");
            //                if ($commit_response === null) {
            //                    continue; // Пропускаем этот коммит, если произошла ошибка
            //                }
            //                $commit_data     = json_decode($commit_response, true);
            self::$gitdata[] = new gitdata($commit);
            // Получаем список измененных файлов
            //                $files = $commit_data['files'];
            //                foreach ($files as $file) {
            //                    $file_path = $file['filename'];
            //                    $file_url = "https://raw.githubusercontent.com/$repo_owner/$repo_name/$commit_sha/$file_path";
            //
            //                    $directory = dirname($file_path);
            //
            //                    // Создаем каталоги, если они не существуют
            //                    if (!file_exists($directory)) {
            //                        mkdir($directory, 0755, true);
            //                    }
            //
            //                    // Скачиваем файл
            //                    $file_content = file_get_contents($file_url);
            //                    file_put_contents($file_path, $file_content);
            //
            //                    echo "Скачан файл: $file_path\n";
            //                }
            //                echo "\n";
        }
        //        }
        //var_dump(self::$gitdata);exit();
        return self::$gitdata;
    }

    /**
     * Получение последнего коммита
     *
     * @return \Ofey\Logan22\component\github\gitdata|false
     */
    public static function getLastCommit(): gitdata|false
    {
        $repo_owner = self::$repo_owner;
        $repo_name  = self::$repo_name;
        $api_url    = "https://api.github.com/repos/$repo_owner/$repo_name/commits";
        $ch         = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'Accept: application/vnd.github.v3+json', // Указываем версию API
          'User-Agent: SphereWeb-Agent', // Указываем имя вашего приложения
          'Authorization: token ' . self::$token,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $commit_data = json_decode($response, true);
        if ( ! empty($commit_data[0]['sha'])) {
            return new gitdata($commit_data[0]);
        } else {
            return false;
        }
    }

    public static function getUpdateList(): ?array
    {
        // Путь к вашей .git папке
        $gitDir = '.git';

        // Путь к файлу, содержащему последний SHA коммита
        $commitFile = fileSys::get_dir($gitDir . '/refs/heads/master'); // Измените "master" на имя вашей ветки

        // Получение содержимого файла с SHA коммита
        $commitSHA = file_get_contents($commitFile);

        // Вывод SHA коммита
        echo "Последний SHA коммита: " . trim($commitSHA);
        exit();

        $api_url = "https://api.github.com/repos/$repo_owner/$repo_name/commits";

        // Используем cURL для выполнения запроса к GitHub API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'Accept: application/vnd.github.v3+json', // Указываем версию API
          'User-Agent: SphereWeb-Agent', // Указываем имя вашего приложения
          'Authorization: token ' . $token,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        // Преобразуем JSON ответ в массив данных
        $data = json_decode($response, true);
        foreach ($data as $key => $commit_data) {
            self::$gitdata[] = new gitdata($commit_data);
        }

        return self::$gitdata;
    }

    private static function getContents($url)
    {
        $token = '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';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'Accept: application/vnd.github.v3+json',
          'User-Agent: SphereWeb-Agent',
          'Authorization: token ' . $token,
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

}

