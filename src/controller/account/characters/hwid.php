<?php

namespace Ofey\Logan22\controller\account\characters;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\model\user\user;

class hwid
{

    public static function reset(): void
    {
        $account_user = $_POST['account'] ?? board::error("No account");
        $data = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->isResetHWID();
        if ($data) {
            foreach (user::self()->getAccounts() as $account) {
                if ($account->getAccount() == $account_user) {
                    $server = server::send(type::RESET_HWID, [
                        "account" => $account->getAccount(),
                    ])->show(true)->getResponse();
                    if (isset($server['success'])) {
                        board::success($server['success']);
                    }
                    if (isset($server['error'])) {
                        board::error($server['error']);
                    }
                }
            }
            board::error("Account not found");
        }else{
            board::error("Server not allow reset HWID");
        }
    }

}