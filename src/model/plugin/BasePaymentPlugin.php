<?php

namespace Ofey\Logan22\model\plugin;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\model\admin\userlog;
use Ofey\Logan22\model\user\user;
use ReflectionClass;

/**
 * Базовый абстрактный класс для платежных плагинов
 * Содержит общие методы, которые используются во всех платежных плагинах
 */
abstract class BasePaymentPlugin
{
    protected const WEBHOOK_ERROR_LOG_THROTTLE_SECONDS = 10;

    protected ?string $nameClass = null;

    /**
     * Получить название класса плагина
     */
    protected function getNameClass(): string
    {
        if ($this->nameClass === null) {
            $this->nameClass = (new ReflectionClass($this))->getShortName();
        }

        return $this->nameClass;
    }

    /**
     * Получить настройку плагина из таблицы settings
     */
    protected function getPluginSetting(string $key, mixed $default = null): mixed
    {
        $settings = plugin::getSetting($this->getNameClass());
        return $settings[$key] ?? $default;
    }

    protected function resolvePluginDescription(string $defaultPhraseKey, ?string $fallback = null): string
    {
        $pluginDescription = trim((string)$this->getPluginSetting('PLUGIN_DESCRIPTION', ''));
        if ($pluginDescription !== '' && $pluginDescription !== $defaultPhraseKey) {
            return $pluginDescription;
        }

        $fallback = $fallback === null ? null : trim($fallback);
        if ($fallback !== null && $fallback !== '') {
            return $fallback;
        }

        return lang::get_phrase($defaultPhraseKey);
    }

    /**
     * Сохранить настройку плагина в таблицу settings
     */
    protected function setPluginSetting(string $key, mixed $value): void
    {
        $pluginSettings = plugin::get($this->getNameClass());
        $pluginSettings->save([
            'setting' => $key,
            'value' => $value,
            'type' => gettype($value),
            'serverId' => 0,
        ]);
    }

    /**
     * Проверить, активен ли плагин
     */
    protected function isPluginActive(): bool
    {
        return (bool)plugin::getPluginActive($this->getNameClass());
    }

    /**
     * Проверить, является ли текущий запрос AJAX запросом
     */
    protected function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Проверка активности плагина с перенаправлением
     * Если плагин выключен, выводит ошибку (для AJAX) или перенаправляет на главную страницу
     */
    protected function checkPluginActive(): void
    {
        if (!$this->isPluginActive()) {
            if ($this->isAjax()) {
                board::error('Плагин выключен');
            }
            redirect::location('/main');
        }
    }

    /**
     * Очистка и валидация списка стран
     */
    protected function sanitizeSupportedCountries(mixed $countries): array
    {
        if (!is_array($countries)) {
            return ['world'];
        }

        $normalized = [];
        foreach ($countries as $country) {
            if (!is_string($country)) {
                continue;
            }
            $code = strtolower(trim($country));
            if ($code === '' || !preg_match('/^[a-z0-9-]+$/', $code)) {
                continue;
            }
            $normalized[] = $code;
        }

        $normalized = array_values(array_unique($normalized));
        return empty($normalized) ? ['world'] : $normalized;
    }

    /**
     * Получить базовый URL сайта
     */
    protected function getBaseUrl(): string
    {
        return \Ofey\Logan22\component\request\url::host();
    }

    /**
     * Очистка и валидация валюты
     */
    protected function sanitizeCurrency(string $currency, string $default = 'USD'): string
    {
        $currency = strtoupper(trim($currency));
        if ($currency === '' || !preg_match('/^[A-Z0-9]{3,10}$/', $currency)) {
            return $default;
        }

        return $currency;
    }

    /**
     * Centralized webhook logger for payment plugins.
     */
    protected function logWebhook(string $phrase, array $context = [], int $userId = 0, ?int $serverId = null): void
    {
        if ($serverId === null && $userId > 0) {
            try {
                $serverId = user::getUserId($userId)->getServerId() ?? 0;
            } catch (\Throwable $e) {
                $serverId = 0;
            }
        }

        if ($this->shouldSkipWebhookLog($phrase)) {
            return;
        }

        userlog::logWebhookRequest(
            $userId,
            (int)($serverId ?? 0),
            $phrase,
            ['plugin' => $this->getNameClass()] + $context
        );
    }

    protected function shouldSkipWebhookLog(string $phrase): bool
    {
        if (!$this->shouldThrottleWebhookPhrase($phrase)) {
            return false;
        }

        $cooldownSeconds = $this->getWebhookErrorThrottleSeconds();
        if ($cooldownSeconds <= 0) {
            return false;
        }

        return $this->isWebhookLogCooldownActive($cooldownSeconds);
    }

    protected function shouldThrottleWebhookPhrase(string $phrase): bool
    {
        return strtoupper($phrase) !== 'PAYMENT_SUCCESS';
    }

    protected function getWebhookErrorThrottleSeconds(): int
    {
        return static::WEBHOOK_ERROR_LOG_THROTTLE_SECONDS;
    }

    private function isWebhookLogCooldownActive(int $cooldownSeconds): bool
    {
        $lockFile = $this->getWebhookLogThrottleFilePath();
        $directory = dirname($lockFile);

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            return false;
        }

        $handle = fopen($lockFile, 'c+');
        if ($handle === false) {
            return false;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return false;
            }

            rewind($handle);
            $lastLoggedAtRaw = trim(stream_get_contents($handle) ?: '');
            $lastLoggedAt = ctype_digit($lastLoggedAtRaw) ? (int)$lastLoggedAtRaw : 0;
            $now = time();

            if ($lastLoggedAt > 0 && ($now - $lastLoggedAt) < $cooldownSeconds) {
                return true;
            }

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, (string)$now);
            fflush($handle);

            return false;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function getWebhookLogThrottleFilePath(): string
    {
        $pluginName = strtolower(preg_replace('/[^a-z0-9_-]+/i', '-', $this->getNameClass()));
        $requestUri = (string)($_SERVER['REQUEST_URI'] ?? 'unknown');
        $hash = sha1($pluginName . '|' . $requestUri);

        return fileSys::get_dir("uploads/cache/webhook_throttle/{$pluginName}/{$hash}.lock");
    }

    /**
     * Абстрактный метод для проверки конфигурации плагина
     * Должен быть реализован в каждом конкретном плагине
     */
    abstract protected function isConfigured(): bool;
}
