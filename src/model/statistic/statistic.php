<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 31.08.2022 / 17:09:15
 */

namespace Ofey\Logan22\model\statistic;

use Exception;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\user\user;

class statistic
{

    private static null|false|array $statistic = null;

    public static function get_pvp($server_id = 0)
    {
        if ($server_id == 0 or $server_id == null) {
            $server_id = user::self()->getServerId();
        }
        self::getStatistic($server_id);
        return self::$statistic[$server_id]['pvp'] ?? null;
    }

    /**
     * Получает статистику сервера с кэшированием и сохраняет её в self::$statistic
     *
     * @param int|null $server_id ID сервера
     * @return void
     * @throws Exception
     */
    private static function getStatistic(?int $server_id = null): void
    {
        // Initialize statistic array if not set
        if (self::$statistic === null) {
            self::$statistic = [];
        }

        // Check if servers exist
        if (server::get_count_servers() === 0) {
            self::$statistic = false;
            return;
        }

        // Check global statistic flag
        if (self::$statistic === false) {
            return;
        }

        // Resolve server ID
        $server_id = self::resolveServerId($server_id);
        if (!$server_id) {
            return;
        }

        // Return if already cached in memory
        if (self::isStatisticCachedInMemory($server_id)) {
            return;
        }

        // Get data from emulation if enabled
        if (self::isEmulationEnabled()) {
            $result = self::getEmulationStatistic($server_id);
            if ($result !== false) {
                self::$statistic[$server_id] = $result;
            }
            return;
        }
        
        // Get data from file cache
        $cachedData = self::getCachedStatistic($server_id);
        if ($cachedData !== null) {
            self::$statistic[$server_id] = $cachedData;
            return;
        }

        // Get fresh data from server
        $freshData = self::fetchFreshStatistic($server_id);
        if ($freshData !== false) {
            self::$statistic[$server_id] = $freshData;
        }
    }

    /**
     * Определяет ID сервера
     *
     * @param int|null $server_id
     * @return int|null
     */
    private static function resolveServerId(?int $server_id): ?int
    {
        if ($server_id === null || $server_id === 0) {
            $user = user::self();
            return $user?->getServerId();
        }

        return (int) $server_id;
    }

    /**
     * Проверяет, кэширована ли статистика в памяти
     *
     * @param int $server_id
     * @return bool
     */
    private static function isStatisticCachedInMemory(int $server_id): bool
    {
        return is_array(self::$statistic) && isset(self::$statistic[$server_id]);
    }

    /**
     * Проверяет, включена ли эмуляция
     *
     * @return bool
     */
    private static function isEmulationEnabled(): bool
    {
        try {
            return \Ofey\Logan22\controller\config\config::load()
                ->enabled()
                ->isEnableEmulation();
        } catch (Exception $e) {
            error_log("Emulation check error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Получает статистику из эмуляции
     *
     * @param int $server_id
     * @return array|false
     */
    private static function getEmulationStatistic(int $server_id)
    {
        try {
            $data = include "src/component/emulation/data/data.php";

            if (!isset($data[$server_id]['statistic'])) {
                return false;
            }

            self::$statistic[$server_id] = $data[$server_id]['statistic'];
            return self::$statistic[$server_id];

        } catch (Exception $e) {
            error_log("Emulation data error for server {$server_id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Получает кэшированную статистику
     *
     * @param int $server_id
     * @return array|null
     */
    private static function getCachedStatistic(int $server_id): ?array
    {
        try {
            $server = server::getServer($server_id);
            if (!$server) {
                return null;
            }

            $cacheData = $server->getCache(type: 'statistic', onlyData: false);
            if (!$cacheData || empty($cacheData['data'])) {
                return null;
            }
            // Проверка актуальности кэша
            if (self::isCacheExpired($cacheData['date'])) {
                return null;
            }

            self::$statistic[$server_id] = $cacheData['data'];
            return self::$statistic[$server_id];

        } catch (Exception $e) {
            error_log("Cache read error for server {$server_id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Проверяет, истек ли срок действия кэша
     *
     * @param string $cacheDate
     * @return bool
     */
    private static function isCacheExpired(string $cacheDate): bool
    {
        try {
            $timeout = config::load()->other()->getTimeoutSaveStatistic();
            return time::diff($cacheDate, time::mysql()) >= $timeout;
        } catch (Exception $e) {
            error_log("Cache expiry check error: " . $e->getMessage());
            return true; // Считаем кэш истекшим при ошибке
        }
    }

    /**
     * Получает свежую статистику с сервера
     *
     * @param int $server_id
     * @return array|false
     */
    private static function fetchFreshStatistic(int $server_id)
    {
        try {
            \Ofey\Logan22\component\sphere\server::setUser(user::self());

            $cache = null;
            $server = server::getServer($server_id);

            // Попытка читать существующий кэш для данного сервера
            if ($server) {
                try {
                    $cache = $server->getCache(type: 'statistic', onlyData: false);
                    if ($cache && !empty($cache['data'])) {
                        if (!self::isCacheExpired($cache['date'])) {
                            self::$statistic[$server_id] = $cache['data'];
                            return self::$statistic[$server_id];
                        }
                    }
                } catch (Exception $e) {
                    error_log("Error reading statistic cache for server {$server_id}: " . $e->getMessage());
                }
            }

            $statistics = self::requestStatistics();
            if (!$statistics) {
                // Если запрос не удался — вернём устаревший кэш если он есть
                if ($cache && !empty($cache['data'])) {
                    self::$statistic[$server_id] = $cache['data'];
                    return self::$statistic[$server_id];
                }
                self::$statistic = false;
                return false;
            }

            self::processStatisticsData($statistics);

            // Гарантированно сохранить кэш для этого сервера (processStatisticsData
            // уже вызывает сохранение, но дублируем для надёжности)
            if ($server && isset(self::$statistic[$server_id])) {
                try {
                    $server->setCache('statistic', self::$statistic[$server_id]);
                } catch (Exception $e) {
                    error_log("Error saving statistic cache for server {$server_id}: " . $e->getMessage());
                }
            }

            return self::$statistic[$server_id] ?? false;

        } catch (Exception $e) {
            error_log("Fresh statistic fetch error for server {$server_id}: " . $e->getMessage());
            self::$statistic = false;
            return false;
        }
    }

    /**
     * Выполняет запрос статистики с повторной попыткой при первой загрузке
     *
     * @return array|null
     * @throws Exception
     */
    private static function requestStatistics(): ?array
    {
        $response = \Ofey\Logan22\component\sphere\server::send(type::STATISTIC_ALL)->getResponse();

        if (!isset($response['statistics'])) {
            return null;
        }

        // Если это первая загрузка, ждем и повторяем запрос
        if (isset($response['isFirstLoad']) && empty($response['statistics'])) {
            usleep(250000); // 250ms
            $response = \Ofey\Logan22\component\sphere\server::send(type::STATISTIC_ALL)->getResponse();

            if (!isset($response['statistics'])) {
                return null;
            }
        }

        return $response['statistics'];
    }

    /**
     * Обрабатывает и кэширует данные статистики
     *
     * @param array $statistics
     * @return void
     */
    private static function processStatisticsData(array $statistics): void
    {
        if (empty($statistics)) {
            return;
        }

        foreach ($statistics as $server_id => $serverData) {
            if (!is_array($serverData)) {
                continue;
            }

            $server_id = (int) $server_id;

            // Инициализация массива для сервера если не существует
            if (!isset(self::$statistic[$server_id])) {
                self::$statistic[$server_id] = [];
            }

            // Обработка статистики по типам
            foreach ($serverData as $type => $statistic) {
                if ($statistic !== null) {
                    self::$statistic[$server_id][$type] = $statistic;
                }
            }

            // Сохранение в кэш
            self::saveStatisticToCache($server_id);
        }
    }

    /**
     * Сохраняет статистику в кэш
     *
     * @param int $server_id
     * @return void
     */
    private static function saveStatisticToCache(int $server_id): void
    {
        try {
            $server = server::getServer($server_id);
            if ($server && isset(self::$statistic[$server_id])) {
                $server->setCache("statistic", self::$statistic[$server_id]);
            }
        } catch (Exception $e) {
            error_log("Cache save error for server {$server_id}: " . $e->getMessage());
        }
    }

    /**
     * Очищает кэш статистики для указанного сервера
     *
     * @param int $server_id
     * @return bool
     */
    public static function clearStatisticCache(int $server_id): bool
    {
        try {
            // Очистка кэша в памяти
            if (isset(self::$statistic[$server_id])) {
                unset(self::$statistic[$server_id]);
            }

            // Очистка файлового кэша
            $server = server::getServer($server_id);
            if ($server) {
                return $server->deleteCache('statistic');
            }

            return true;
        } catch (Exception $e) {
            error_log("Clear statistic cache error for server {$server_id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Принудительное обновление статистики
     *
     * @param int|null $server_id
     * @return array|false
     */
    public static function refreshStatistic($server_id = null)
    {
        $server_id = self::resolveServerId($server_id);
        if (!$server_id) {
            return false;
        }

        // Очищаем кэш
        self::clearStatisticCache($server_id);

        // Получаем свежие данные
        return self::fetchFreshStatistic($server_id);
    }
    public static function get_pk($server_id = 0)
    {
        if ($server_id == 0 or $server_id == null) {
            $server_id = user::self()->getServerId();
        }
        self::getStatistic($server_id);
        return self::$statistic[$server_id]['pk'] ?? [];
    }

    public static function get_players_online_time($server_id = 0)
    {
        if ($server_id == 0 or $server_id == null) {
            $server_id = user::self()->getServerId();
        }
        self::getStatistic($server_id);
        return self::$statistic[$server_id]['online'] ?? [];
    }

    public static function get_exp($server_id = 0)
    {
        if ($server_id == 0 or $server_id == null) {
            $server_id = user::self()->getServerId();
        }
        self::getStatistic($server_id);
        return self::$statistic[$server_id]['exp'] ?? [];
    }

    public static function get_clan($server_id = 0)
    {
        if ($server_id == 0 or $server_id == null) {
            $server_id = user::self()->getServerId();
        }
        self::getStatistic($server_id);
        return self::$statistic[$server_id]['clan'] ?? [];
    }

    public static function get_castle($server_id = 0)
    {
        if ($server_id == 0 or $server_id == null) {
            $server_id = user::self()->getServerId();
        }
        self::getStatistic($server_id);
        return self::$statistic[$server_id]['castle'] ?? [];
    }



}