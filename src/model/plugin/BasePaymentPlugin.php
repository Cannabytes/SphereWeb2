<?php

namespace Ofey\Logan22\model\plugin;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\redirect;
use ReflectionClass;

/**
 * Базовый абстрактный класс для платежных плагинов
 * Содержит общие методы, которые используются во всех платежных плагинах
 */
abstract class BasePaymentPlugin
{
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
        $scheme = $_SERVER['REQUEST_SCHEME'] ?? 'https';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        return $scheme . '://' . $host;
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
     * Абстрактный метод для проверки конфигурации плагина
     * Должен быть реализован в каждом конкретном плагине
     */
    abstract protected function isConfigured(): bool;
}
