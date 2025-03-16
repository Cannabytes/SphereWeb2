<?php
/** UPDATE **/

namespace Ofey\Logan22\model\config;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\captcha\google;

class captcha
{

    private bool $enable = true;

    private bool $defaultCaptcha = true;

    private bool $googleCaptcha = false;

    private string $googleClientKey = "";

    private string $googleServerKey = "";

    private bool $cloudflareCaptcha = false;

    private string $cloudflareSiteKey = "";

    private string $cloudflareSecretKey = "";

    public function __construct($setting)
    {
        if ($setting) {
            $this->enable          = filter_var($setting['enable'], FILTER_VALIDATE_BOOLEAN);
            $this->defaultCaptcha  = filter_var($setting['defaultCaptcha'], FILTER_VALIDATE_BOOLEAN);
            $this->googleCaptcha   = filter_var($setting['googleCaptcha'], FILTER_VALIDATE_BOOLEAN);
            $this->googleClientKey = $setting['googleClientKey'] ?? "";
            $this->googleServerKey = $setting['googleServerKey'] ?? "";
            $this->cloudflareCaptcha = filter_var($setting['cloudflareCaptcha'], FILTER_VALIDATE_BOOLEAN);
            $this->cloudflareSiteKey = $setting['cloudflareSiteKey'] ?? "";
            $this->cloudflareSecretKey = $setting['cloudflareSecretKey'] ?? "";
        }
    }

    public function isEnabled(): bool
    {
        if ($this->defaultCaptcha) {
            return true;
        }
        if ($this->googleCaptcha) {
            return true;
        }
        if ($this->cloudflareCaptcha) {
            return true;
        }
        return $this->enable;
    }

    public function isDefaultCaptcha(): bool
    {
        return $this->defaultCaptcha;
    }

    public function getGoogleClientKey(): string
    {
        return $this->googleClientKey;
    }

    public function getGoogleServerKey(): string
    {
        return $this->googleServerKey;
    }

    public function isCloudflareCaptcha(): bool
    {
        return $this->cloudflareCaptcha;
    }

    public function getCloudflareSiteKey(): string
    {
        return $this->cloudflareSiteKey;
    }

    public function getCloudflareSecretKey(): string
    {
        return $this->cloudflareSecretKey;
    }

    public function validator(): void
    {
        if (!$this->isEnabled()) {
            return;
        }
        if ($this->getCaptcha() == "google") {
            // Существующая логика для Google reCAPTCHA
            if(!isset($_POST['g-recaptcha-response'])){
                board::notice(false, "Google recaptcha не вернула ответ");
            }

            //для V2 Google Captcha
            $g_captcha = google::check($_POST['g-recaptcha-response'] ?? null);
            if (isset($g_captcha['success'])) {
                if (!$g_captcha['success']) {
                    board::notice(false, "Google recaptcha не вернула ответ");
                }
            }
        } else if ($this->getCaptcha() == "cloudflare") {
            $token = $_POST['cf-turnstile-response'] ?? '';
            if (empty($token)) {
                board::notice(false, "Пожалуйста, пройдите проверку капчи");
            }
            $result = $this->verifyCloudflareTurnstile($token);
            if ($result['success'] === true) {
                return;
            }
            // Проверка результата
            if (!isset($result['success']) || $result['success'] !== true) {
                // Улучшенная обработка ошибок
                $errorMessage = "Проверка капчи Cloudflare не пройдена";

                if (isset($result['error-codes']) && is_array($result['error-codes'])) {
                    // Специальная обработка для timeout-or-duplicate
                    if (in_array('timeout-or-duplicate', $result['error-codes'])) {
                        $errorMessage = "Токен проверки устарел или уже использован. Обновите страницу и попробуйте снова.";
                    } else {
                        $errorMessage = "Проверка капчи не пройдена";
                    }
                }
                error_log("Ошибка проверки Cloudflare Turnstile: " . json_encode($result));
                board::notice(false, $errorMessage);
            }
        } else {
            $token = $_POST['cf-turnstile-response'] ?? '';
            if (empty($token)) {
                board::notice(false, "Пожалуйста, пройдите проверку капчи");
            }
            $result = $this->verifySphereCloudflareTurnstile($token);
            if ($result['success'] === true) {
                return;
            }
            // Проверка результата
            if (!isset($result['success']) || $result['success'] !== true) {
                // Улучшенная обработка ошибок
                $errorMessage = "Проверка капчи не пройдена";

                if (isset($result['error-codes']) && is_array($result['error-codes'])) {
                    // Специальная обработка для timeout-or-duplicate
                    if (in_array('timeout-or-duplicate', $result['error-codes'])) {
                        $errorMessage = "Токен проверки устарел или уже использован. Обновите страницу и попробуйте снова.";
                    } else {
                        $errorMessage .= ": " . implode(', ', $result['error-codes']);
                    }
                }
                error_log("Ошибка проверки Cloudflare Turnstile: " . json_encode($result));
                board::notice(false, $errorMessage);
            }
        }
    }

    /**
     * Проверяет токен Cloudflare Turnstile
     *
     * @param string $token Токен от Cloudflare Turnstile
     * @param string|null $remoteIp IP-адрес пользователя (опционально)
     * @return array Результат проверки
     */
    private function verifyCloudflareTurnstile(string $token, ?string $remoteIp = null): array
    {
        // Конфигурация Cloudflare Turnstile
        $secretKey = $this->getCloudflareSecretKey();
        $verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        $timeout = 5; // Таймаут в секундах

        // Подготовка данных для запроса
        $data = [
            'secret' => $secretKey,
            'response' => $token
        ];

        // Добавляем IP пользователя, если он предоставлен
        if ($remoteIp === null && isset($_SERVER['REMOTE_ADDR'])) {
            $remoteIp = $_SERVER['REMOTE_ADDR'];
        }

        if ($remoteIp !== null) {
            $data['remoteip'] = $remoteIp;
        }

        // Используем cURL для более надежного запроса
        $ch = curl_init($verifyUrl);

        // Настройка cURL
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true, // Обязательно проверяем SSL сертификат
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
        ]);

        // Выполняем запрос с обработкой ошибок
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Обрабатываем ошибки cURL
        if ($response === false) {
            $errorMessage = 'Ошибка cURL при проверке Cloudflare Turnstile: ' . $curlError;
            error_log($errorMessage);
            return [
                'success' => false,
                'error-codes' => ['connection-error'],
                'error-message' => $errorMessage
            ];
        }

        // Проверяем HTTP-статус
        if ($httpCode !== 200) {
            $errorMessage = 'Cloudflare Turnstile API вернул код состояния HTTP: ' . $httpCode;
            error_log($errorMessage);
            return [
                'success' => false,
                'error-codes' => ['http-error'],
                'http-code' => $httpCode,
                'error-message' => $errorMessage
            ];
        }

        // Декодируем JSON-ответ
        $result = json_decode($response, true);

        // Проверяем корректность JSON
        if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
            $errorMessage = 'Некорректный JSON-ответ от Cloudflare Turnstile API: ' . json_last_error_msg();
            error_log($errorMessage);
            return [
                'success' => false,
                'error-codes' => ['invalid-json-response'],
                'error-message' => $errorMessage
            ];
        }

        // Добавляем метку времени для отладки
        $result['verified_at'] = time();

        // Логируем информацию о проверке (только если проверка не пройдена)
        if (!($result['success'] ?? false)) {
            error_log('Cloudflare Turnstile проверка не пройдена: ' . json_encode($result));
        }

        return $result;
    }

    /**
     * Проверка Cloudflare Turnstile через сервер-посредник
     *
     * @param string $token Токен от Cloudflare Turnstile
     * @return array Результат проверки
     */
    private function verifySphereCloudflareTurnstile(string $token): array
    {
        // Добавляем уникальный идентификатор к запросу для предотвращения кэширования
        $uniqueId = uniqid('verify_', true);

        // URL сервера для проверки токена
        $getterUrl = 'https://sphereweb.net/cloudflare/getter.php';

        // Подготовка данных для отправки
        $data = [
            'token' => $token,
            'user_ip' => $_SERVER['REMOTE_ADDR'],
            'unique_id' => $uniqueId // Добавляем уникальный идентификатор
        ];

        // Инициализация cURL
        $ch = curl_init($getterUrl);

        // Настройка параметров запроса
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Origin: ' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'],
            'Cache-Control: no-cache, no-store, must-revalidate',
            'Pragma: no-cache'
        ]);

        // Выполнение запроса
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        // Закрытие соединения
        curl_close($ch);

        // Обработка ошибок соединения
        if ($response === false) {
            // Запись в журнал ошибок
            error_log("Ошибка соединения с сервером проверки капчи: " . $curlError);

            return [
                'success' => false,
                'error' => 'Ошибка соединения с сервером проверки капчи',
                'error-codes' => ['connection_failed']
            ];
        }

        // Если код ответа не 200, значит есть проблема
        if ($httpCode !== 200) {
            error_log("Сервер проверки капчи вернул код ошибки: " . $httpCode);

            return [
                'success' => false,
                'error' => 'Сервер проверки капчи вернул код ошибки: ' . $httpCode,
                'error-codes' => ['invalid_response']
            ];
        }

        // Декодирование JSON-ответа
        $result = json_decode($response, true);

        // Проверка корректности JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Ошибка декодирования JSON-ответа: " . json_last_error_msg());

            return [
                'success' => false,
                'error' => 'Некорректный ответ сервера проверки капчи',
                'error-codes' => ['invalid_json']
            ];
        }

        // Журналирование результата проверки для отладки
        error_log("Результат проверки токена: " . json_encode($result));

        return $result;
    }


    /**
     * Возвращает название капчи, которая будет использоваться.
     *
     * @return string
     */
    public function getCaptcha(): string
    {
        if (!$this->isEnabled()) {
            return "disabled";
        }
        if ($this->defaultCaptcha) {
            return "default";
        }
        if ($this->googleCaptcha) {
            if (empty($this->googleClientKey) or empty($this->googleServerKey)) {
                return "default";
            }

            return "google";
        }

        if ($this->cloudflareCaptcha) {
            if (empty($this->cloudflareSiteKey) or empty($this->cloudflareSecretKey)) {
                return "default";
            }

            return "cloudflare";
        }

        return "default";
    }

    public function isGoogleCaptcha(): bool
    {
        return $this->googleCaptcha;
    }


}