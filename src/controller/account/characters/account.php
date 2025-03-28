<?php

namespace Ofey\Logan22\controller\account\characters;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\controller\registration\user;
use Ofey\Logan22\model\log\logTypes;

class account
{
    public static function delete(): void
    {
        // Проверка наличия аккаунта в POST запросе
        $account = trim($_POST['account'] ?? '');
        if (empty($account)) {
            board::error(lang::get_phrase("account_not_specified"));
            return;
        }
        // Если кол-во символов в аккаунте больше 20
        if (mb_strlen($account) > 20) {
            board::error(lang::get_phrase("account_too_long"));
        }

        // Получение логов пользователя для проверки лимита удалений
        $logs = \Ofey\Logan22\model\user\user::self()->getLogs(logTypes::LOG_DELETE_ACCOUNT);
        // Проверка лимита удалений (не более 5 в день)
        $todayLogs = array_filter($logs, function($log) {
            $timestamp = is_string($log['time']) ? strtotime($log['time']) : (int)$log['time'];
            return date('Y-m-d', $timestamp) === date('Y-m-d');
        });

        if (count($todayLogs) >= 5) {
            board::error(lang::get_phrase("delete_limit_exceeded"));
            return;
        }

        // Проверка, принадлежит ли аккаунт пользователю
        if (!self::isAccountBelongsToUser($account)) {
            board::error(lang::get_phrase("account_not_belongs_to_user"));
            return;
        }

        try {
            // Отправка запроса на удаление аккаунта
            $data = server::send(type::DELETE_ACCOUNT, [
                'account' => $account
            ])->show()->getResponse();

            if (isset($data['error'])) {
                board::error($data['error']);
                return;
            }

            if (isset($data['success'])) {
                // Логирование успешного удаления
                \Ofey\Logan22\model\user\user::self()->addLog(
                    logTypes::LOG_DELETE_ACCOUNT,
                    "user_deleted_game_account",
                    [$account]
                );

                // Обновление списка аккаунтов пользователя
                \Ofey\Logan22\model\user\user::self()->getLoadAccounts(true);

                board::success(lang::get_phrase("account_deleted_success"));
            }
        } catch (\Exception $e) {
            // Обработка ошибок при выполнении запроса
            board::error(lang::get_phrase("delete_account_error") . ': ' . $e->getMessage());
        }
    }

    /**
     * Проверяет, принадлежит ли аккаунт текущему пользователю
     *
     * @param string $account Имя аккаунта для проверки
     * @return bool Результат проверки
     */
    private static function isAccountBelongsToUser(string $account): bool
    {
        $userAccounts = \Ofey\Logan22\model\user\user::self()->getAccounts();

        foreach ($userAccounts as $userAccount) {
            if ($userAccount->getAccount() === $account) {
                return true;
            }
        }

        return false;
    }

}