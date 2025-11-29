<?php
/**
 * Two-Factor Authentication Controller
 * Handles 2FA setup, enable, disable, and verification
 */

namespace Ofey\Logan22\controller\user\auth;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\session\session;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\auth\twofa;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class twofaController
{
    /**
     * API: Генерация секрета и QR кода для настройки 2FA
     */
    public static function setup(): void
    {
        if (!user::self()->isAuth()) {
            board::error(lang::get_phrase('not_authorized'));
        }

        // 2FA только для обычной авторизации
        if (user::self()->getPassword() === "GOOGLE" || user::self()->getPassword() === "TELEGRAM") {
            board::error(lang::get_phrase('2fa_not_available_oauth'));
        }

        // Генерируем новый секрет
        $secret = twofa::generateSecret();
        $email = user::self()->getEmail();
        
        // Получаем название сайта из конфига или используем дефолт
        $siteName = $_SERVER['HTTP_HOST'] ?? 'SphereWeb';
        
        // Генерируем URI и URL для QR кода
        $otpauthUri = twofa::getOtpauthUri($secret, $email, $siteName);
        $qrCodeUrl = twofa::getQRCodeUrl($otpauthUri);

        // Сохраняем секрет в сессию для последующей активации
        session::add('twofa_temp_secret', $secret);

        board::response('json', [
            'ok' => true,
            'secret' => $secret,
            'qrCode' => $qrCodeUrl,
            'otpauthUri' => $otpauthUri
        ]);
    }

    /**
     * API: Включение 2FA
     */
    public static function enable(): void
    {
        if (!user::self()->isAuth()) {
            board::error(lang::get_phrase('not_authorized'));
        }

        // 2FA только для обычной авторизации
        if (user::self()->getPassword() === "GOOGLE" || user::self()->getPassword() === "TELEGRAM") {
            board::error(lang::get_phrase('2fa_not_available_oauth'));
        }

        $secret = $_POST['secret'] ?? session::get('twofa_temp_secret');
        $code = $_POST['code'] ?? '';

        if (empty($secret) || empty($code)) {
            board::notice(false, lang::get_phrase('2fa_missing_data'));
        }

        // Проверяем код и активируем 2FA
        if (twofa::enable(user::self()->getId(), $secret, $code)) {
            // Очищаем временный секрет из сессии
            session::remove('twofa_temp_secret');
            
            board::notice(true, lang::get_phrase('2fa_enabled_success'));
        } else {
            board::notice(false, lang::get_phrase('2fa_invalid_code'));
        }
    }

    /**
     * API: Отключение 2FA
     */
    public static function disable(): void
    {
        if (!user::self()->isAuth()) {
            board::error(lang::get_phrase('not_authorized'));
        }

        $code = $_POST['code'] ?? '';

        if (empty($code)) {
            board::notice(false, lang::get_phrase('2fa_missing_data'));
        }

        // Проверяем код и отключаем 2FA
        if (twofa::disable(user::self()->getId(), $code)) {
            board::notice(true, lang::get_phrase('2fa_disabled_success'));
        } else {
            board::notice(false, lang::get_phrase('2fa_invalid_code'));
        }
    }

    /**
     * Отображение страницы верификации 2FA
     */
    public static function showVerifyPage(): void
    {
        $token = session::get('twofa_pending_token');
        
        if (empty($token)) {
            redirect::location('/login');
        }

        tpl::addVar('twofa_token', $token);
        tpl::addVar('error', session::get('twofa_error'));
        session::remove('twofa_error');
        
        tpl::display('2fa-verify.html');
    }

    /**
     * Верификация 2FA кода после авторизации
     */
    public static function verify(): void
    {
        $token = $_POST['token'] ?? '';
        $code = $_POST['code'] ?? '';

        // Получаем данные ожидающей авторизации из сессии
        $pendingToken = session::get('twofa_pending_token');
        $pendingUserId = session::get('twofa_pending_user_id');
        $pendingEmail = session::get('twofa_pending_email');
        $pendingPassword = session::get('twofa_pending_password');

        // Проверяем токен
        if (empty($token) || $token !== $pendingToken || empty($pendingUserId)) {
            session::remove('twofa_pending_token');
            session::remove('twofa_pending_user_id');
            session::remove('twofa_pending_email');
            session::remove('twofa_pending_password');
            redirect::location('/login');
        }

        // Проверяем код
        if (empty($code) || strlen($code) !== 6) {
            session::add('twofa_error', true);
            redirect::location('/auth/2fa');
        }

        // Верифицируем код
        if (twofa::verifyUserCode($pendingUserId, $code)) {
            // Успешная верификация - завершаем авторизацию
            \Ofey\Logan22\model\user\auth\auth::addAuthLog($pendingUserId);
            
            session::add('id', $pendingUserId);
            session::add('email', $pendingEmail);
            session::add('password', $pendingPassword);

            // Очищаем данные ожидающей авторизации
            session::remove('twofa_pending_token');
            session::remove('twofa_pending_user_id');
            session::remove('twofa_pending_email');
            session::remove('twofa_pending_password');

            redirect::location('/main');
        } else {
            // Неверный код
            session::add('twofa_error', true);
            redirect::location('/auth/2fa');
        }
    }

    /**
     * Создание ожидающей 2FA авторизации
     * Вызывается из auth.php когда пользователь с 2FA успешно ввел пароль
     * 
     * @param int $userId
     * @param string $email
     * @param string $password
     */
    public static function createPending2FA(int $userId, string $email, string $password): void
    {
        // Генерируем уникальный токен
        $token = bin2hex(random_bytes(32));
        
        // Сохраняем данные в сессию
        session::add('twofa_pending_token', $token);
        session::add('twofa_pending_user_id', $userId);
        session::add('twofa_pending_email', $email);
        session::add('twofa_pending_password', $password);

        // Редирект на страницу ввода 2FA кода
        board::response("notice", [
            "message" => lang::get_phrase('2fa_required'),
            "ok" => true,
            "redirect" => "/auth/2fa"
        ]);
    }

    /**
     * Отмена ожидающей 2FA авторизации
     */
    public static function cancelPending(): void
    {
        session::remove('twofa_pending_token');
        session::remove('twofa_pending_user_id');
        session::remove('twofa_pending_email');
        session::remove('twofa_pending_password');
        
        redirect::location('/login');
    }
}
