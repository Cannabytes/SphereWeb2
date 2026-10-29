<?php
/** UPDATE **/

namespace Ofey\Logan22\model\config;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\captcha\google;

class captcha
{

    private bool $googleCaptcha = false;

    private string $googleClientKey = "";

    private string $googleServerKey = "";

    private bool $cloudflareCaptcha = false;

    private string $cloudflareSiteKey = "";

    private string $cloudflareSecretKey = "";

    public function __construct($setting)
    {
        if ($setting) {
            // Старый параметр defaultCaptcha (встроенная капча Sphere) больше не поддерживается и игнорируется:
            // если была выбрана она, капча считается отключенной.
            $this->googleCaptcha = isset($setting['googleCaptcha']) ? filter_var($setting['googleCaptcha'], FILTER_VALIDATE_BOOLEAN) : false;
            $this->googleClientKey = $setting['googleClientKey'] ?? "";
            $this->googleServerKey = $setting['googleServerKey'] ?? "";
            $this->cloudflareCaptcha = isset($setting['cloudflareCaptcha']) ? filter_var($setting['cloudflareCaptcha'], FILTER_VALIDATE_BOOLEAN) : false;
            $this->cloudflareSiteKey = $setting['cloudflareSiteKey'] ?? "";
            $this->cloudflareSecretKey = $setting['cloudflareSecretKey'] ?? "";
        }
    }

    public function isEnabled(): bool
    {
        return $this->getCaptcha() !== "disabled";
    }

    /**
     * Встроенная капча Sphere удалена. Метод оставлен для совместимости со сторонними шаблонами.
     */
    public function isDefaultCaptcha(): bool
    {
        return false;
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
        return $this->cloudflareCaptcha && $this->cloudflareSiteKey !== "" && $this->cloudflareSecretKey !== "";
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
        $captcha = $this->getCaptcha();
        if ($captcha == "google") {
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
        } else if ($captcha == "cloudflare") {
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
     * Возвращает название капчи, которая будет использоваться: google, cloudflare или disabled.
     * Капча без заполненных ключей считается отключенной.
     *
     * @return string
     */
    public function getCaptcha(): string
    {
        if ($this->isGoogleCaptcha()) {
            return "google";
        }

        if ($this->isCloudflareCaptcha()) {
            return "cloudflare";
        }

        return "disabled";
    }

    public function isGoogleCaptcha(): bool
    {
        return $this->googleCaptcha && $this->googleClientKey !== "" && $this->googleServerKey !== "";
    }


}
