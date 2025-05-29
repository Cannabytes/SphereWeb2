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
     * Получает статистику сервера с кэшированием
     *
     * @param int|null $server_id ID сервера
     * @return array|false Статистика сервера или false в случае ошибки
     * @throws Exception
     */
    private static function getStatistic(int $server_id = null): false|array
    {
        // Проверка наличия серверов
        if (server::get_count_servers() === 0) {
            return false;
        }

        // Проверка глобального флага статистики
        if (self::$statistic === false) {
            return false;
        }

        // Определение ID сервера
        $server_id = self::resolveServerId($server_id);
        if (!$server_id) {
            return false;
        }

        // Возврат кэшированных данных из памяти
        if (self::isStatisticCachedInMemory($server_id)) {
            return self::$statistic[$server_id];
        }

        // Получение данных из эмуляции
        if (self::isEmulationEnabled()) {
            return self::getEmulationStatistic($server_id);
        }

        // Получение данных из файлового кэша
        $cachedData = self::getCachedStatistic($server_id);
        if ($cachedData !== null) {
            return $cachedData;
        }

        // Получение свежих данных с сервера
        return self::fetchFreshStatistic($server_id);
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

        return (int)$server_id;
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

            $cacheData = $server->getCache('statistic');
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

            $statistics = self::requestStatistics();
            if (!$statistics) {
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

            $server_id = (int)$server_id;

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

    public static function timeHasPassed($seconds, $reduce = false): string
    {
        $days = floor($seconds / 86400);
        $seconds %= 86400;
        $hours = floor($seconds / 3600);
        $seconds %= 3600;
        $minutes = floor($seconds / 60);
        $seconds %= 60;

        $result = '';
        if ($days > 0) {
            $d = \Ofey\Logan22\component\lang\lang::get_phrase('d');
            $daysStr = \Ofey\Logan22\component\lang\lang::get_phrase('days');
            $result .= $days . ($reduce ? " {$d}. " : " {$daysStr}, ");
        }
        if ($hours > 0) {
            $h = \Ofey\Logan22\component\lang\lang::get_phrase('h');
            $hoursStr = \Ofey\Logan22\component\lang\lang::get_phrase('hours');
            $result .= $hours . ($reduce ? " {$h}. " : " {$hoursStr}, ");
        }
        if ($minutes > 0) {
            $m = \Ofey\Logan22\component\lang\lang::get_phrase('m');
            $minutesStr = \Ofey\Logan22\component\lang\lang::get_phrase('minutes');
            $result .= $minutes . ($reduce ? " {$m}. " : " {$minutesStr}, ");
        }

        $s = \Ofey\Logan22\component\lang\lang::get_phrase('s');
        $secondsStr = \Ofey\Logan22\component\lang\lang::get_phrase('seconds');
        $result .= $seconds . ($reduce ? " {$s}. " : " {$secondsStr}");

        return $result;
    }


}