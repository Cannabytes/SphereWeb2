<?php

namespace Ofey\Logan22\model\github;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\config\github;
use Ofey\Logan22\model\db\sql;

class update
{

    static function update()
    {
        $github = new github();
        $github->update();
    }

    static function checkNewCommit()
    {
        $sphere = server::send(type::GET_COMMIT_LAST, [
          'last_commit' => self::getLastCommit(),
        ])->getResponse();
        if ( ! $sphere['status']) {
            $last_commit_now = $sphere['last_commit_now'];
            foreach ($sphere['data'] as $data) {
                $file   = $data['file'];
                $status = $data['status'];
                $link   = $data['link'];
                if ($status == 'added' || $status == 'modified') {
                    file_put_contents(fileSys::get_dir($file), file_get_contents($link));
                } elseif ($status == 'removed') {
                    unlink(fileSys::get_dir($file));
                }
            }
            self::addLastCommit($last_commit_now);
            board::success("ПО обновлено");
        }
        board::success("Обновление не требуется");
    }

    static function getLastCommit(): string|null
    {
        $github = sql::getRow("SELECT * FROM `github_updates` ORDER BY `id` DESC LIMIT 1");

        return $github['sha'];
    }

    static function addLastCommit($last_commit_now): void
    {
        sql::run("INSERT INTO `github_updates` (`sha`, `author`, `url`, `message`, `date`, `date_update`) VALUES (?, ?, ?, ?, ?, ?)", [
          $last_commit_now,
          "Cannabytes",
          "https://github.com/Cannabytes/SphereWeb2/commit/" . $last_commit_now,
          "Autoupdated",
          time::mysql(),
          time::mysql(),
        ]);
        if (sql::isError()) {
            board::error("Ошибка записи коммита");
        }
    }

}