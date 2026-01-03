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
    private static null|false|array $statistic_old = null;

    public static function get_pvp($server_id = 0)
    {
        if ($server_id == 0 or $server_id == null) {
            $server_id = user::self()->getServerId();
        }
        self::getStatistic($server_id);
        if (!is_array(self::$statistic) || !isset(self::$statistic[$server_id])) {
            return [];
        }
        return self::compareRanks(self::$statistic[$server_id]['pvp'] ?? [], self::$statistic_old[$server_id]['pvp'] ?? [], 'player_name');
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
        if (self::$statistic_old === null) {
            self::$statistic_old = [];
        }

        // Check if servers exist
        if (server::get_count_servers() === 0) {
            self::$statistic = false;
            self::$statistic_old = false;
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
            // Если основная статистика в памяти, но старая нет - пробуем загрузить старую
            if (!isset(self::$statistic_old[$server_id])) {
                $oldCachedData = self::getCachedStatistic($server_id, 'statistic_old');
                if ($oldCachedData !== null) {
                    self::$statistic_old[$server_id] = $oldCachedData;
                }
            }
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
            
            // Также загружаем старую статистику
            $oldCachedData = self::getCachedStatistic($server_id, 'statistic_old');
            if ($oldCachedData !== null) {
                self::$statistic_old[$server_id] = $oldCachedData;
            } else {
                // Если старой статистики нет, создадим её из текущей, чтобы файл появился
                $server = server::getServer($server_id);
                if ($server) {
                    $server->setCache('statistic_old', $cachedData);
                    self::$statistic_old[$server_id] = $cachedData;
                }
            }
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
     * @param string $type
     * @return array|null
     */
    private static function getCachedStatistic(int $server_id, string $type = 'statistic'): ?array
    {
        try {
            $server = server::getServer($server_id);
            if (!$server) {
                return null;
            }

            $cacheData = $server->getCache(type: $type, onlyData: false);
            if (!$cacheData || empty($cacheData['data'])) {
                return null;
            }
            // Проверка актуальности кэша только для основной статистики
            if ($type === 'statistic' && self::isCacheExpired($cacheData['date'])) {
                return null;
            }

            return $cacheData['data'];

        } catch (Exception $e) {
            error_log("Cache read error for server {$server_id} type {$type}: " . $e->getMessage());
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
                            
                            // Загружаем старую статистику для сравнения
                            $oldCache = $server->getCache(type: 'statistic_old', onlyData: true);
                            if ($oldCache) {
                                self::$statistic_old[$server_id] = $oldCache;
                            } else {
                                // Если старой статистики нет, создадим её из текущей
                                $server->setCache('statistic_old', $cache['data']);
                                self::$statistic_old[$server_id] = $cache['data'];
                            }
                            
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
            $server = server::getServer($server_id);
            if (!$server) {
                continue;
            }

            // Получаем текущий кэш из файла для сравнения
            $currentCache = $server->getCache('statistic', onlyData: true);

            // Если текущий кэш есть и он отличается от новых данных
            if ($currentCache !== null && $currentCache != $serverData) {
                // Данные изменились! Сохраняем старые данные в statistic_old
                $server->setCache('statistic_old', $currentCache);
                self::$statistic_old[$server_id] = $currentCache;
            } elseif ($currentCache !== null && !isset(self::$statistic_old[$server_id])) {
                // Если данные не изменились, но в памяти нет старой статистики, попробуем загрузить её из файла
                $oldCache = $server->getCache('statistic_old', onlyData: true);
                if ($oldCache) {
                    self::$statistic_old[$server_id] = $oldCache;
                } else {
                    // Если файла старой статистики вообще нет, создадим его из текущего кэша
                    $server->setCache('statistic_old', $currentCache);
                    self::$statistic_old[$server_id] = $currentCache;
                }
            }

            // Обновляем данные в памяти
            self::$statistic[$server_id] = $serverData;

            // Сохраняем новые данные в основной кэш (statistic.php)
            try {
                $server->setCache("statistic", $serverData);
            } catch (Exception $e) {
                error_log("Cache save error for server {$server_id}: " . $e->getMessage());
            }
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
            if (isset(self::$statistic_old[$server_id])) {
                unset(self::$statistic_old[$server_id]);
            }

            // Очистка файлового кэша
            $server = server::getServer($server_id);
            if ($server) {
                $server->deleteCache('statistic_old');
                return $server->deleteCache('statistic');
            }

            return true;
        } catch (Exception $e) {
            error_log("Clear statistic cache error for server {$server_id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Сравнивает текущую статистику с предыдущей и вычисляет изменение ранга
     *
     * @param array $current Текущая статистика
     * @param array $old Предыдущая статистика
     * @param string $identifierKey Ключ для идентификации объекта (например, 'player_name' или 'clan_name')
     * @return array
     */
    private static function compareRanks(array $current, array $old, string $identifierKey): array
    {
        if (empty($old)) {
            return $current;
        }

        $oldPositions = [];
        foreach ($old as $index => $item) {
            if (isset($item[$identifierKey])) {
                $oldPositions[$item[$identifierKey]] = $index;
            }
        }

        foreach ($current as $index => &$item) {
            $name = $item[$identifierKey] ?? null;
            if ($name && isset($oldPositions[$name])) {
                $oldIndex = $oldPositions[$name];
                if ($index < $oldIndex) {
                    $item['rank_change'] = 'up';
                } elseif ($index > $oldIndex) {
                    $item['rank_change'] = 'down';
                } else {
                    $item['rank_change'] = 'none';
                }
            } else {
                $item['rank_change'] = 'new';
            }
        }

        return $current;
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

        // Очищаем основной кэш в памяти
        if (isset(self::$statistic[$server_id])) {
            unset(self::$statistic[$server_id]);
        }

        // Получаем свежие данные
        return self::fetchFreshStatistic($server_id);
    }
    public static function get_pk($server_id = 0)
    {
        if ($server_id == 0 or $server_id == null) {
            $server_id = user::self()->getServerId();
        }
        self::getStatistic($server_id);
        if (!is_array(self::$statistic) || !isset(self::$statistic[$server_id])) {
            return [];
        }
        return self::compareRanks(self::$statistic[$server_id]['pk'] ?? [], self::$statistic_old[$server_id]['pk'] ?? [], 'player_name');
    }

    public static function get_players_online_time($server_id = 0)
    {
        if ($server_id == 0 or $server_id == null) {
            $server_id = user::self()->getServerId();
        }
        self::getStatistic($server_id);
        if (!is_array(self::$statistic) || !isset(self::$statistic[$server_id])) {
            return [];
        }
        return self::compareRanks(self::$statistic[$server_id]['online'] ?? [], self::$statistic_old[$server_id]['online'] ?? [], 'player_name');
    }

    public static function get_exp($server_id = 0)
    {
        if ($server_id == 0 or $server_id == null) {
            $server_id = user::self()->getServerId();
        }
        self::getStatistic($server_id);
        if (!is_array(self::$statistic) || !isset(self::$statistic[$server_id])) {
            return [];
        }
        return self::compareRanks(self::$statistic[$server_id]['exp'] ?? [], self::$statistic_old[$server_id]['exp'] ?? [], 'player_name');
    }

    public static function get_clan($server_id = 0)
    {
        if ($server_id == 0 or $server_id == null) {
            $server_id = user::self()->getServerId();
        }
        self::getStatistic($server_id);
        if (!is_array(self::$statistic) || !isset(self::$statistic[$server_id])) {
            return [];
        }
        return self::compareRanks(self::$statistic[$server_id]['clan'] ?? [], self::$statistic_old[$server_id]['clan'] ?? [], 'clan_name');
    }

    public static function get_castle($server_id = 0)
    {
        if ($server_id == 0 or $server_id == null) {
            $server_id = user::self()->getServerId();
        }
        self::getStatistic($server_id);
        if (!is_array(self::$statistic) || !isset(self::$statistic[$server_id])) {
            return [];
        }
        return self::$statistic[$server_id]['castle'] ?? [];
    }



}