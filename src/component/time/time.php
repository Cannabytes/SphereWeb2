<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 26.08.2022 / 17:59:44
 */

namespace Ofey\Logan22\component\time;

use DateTime;
use DateTimeZone;
use Exception;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\controller\config\config;

class time {

    private static ?DateTimeZone $serverTimezone = null;

    //Время формата datetime
    public static function mysql(): string {
        $date = new DateTime('now', self::getServerTimezone());
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Сравниваем две даты, и возвращаем разницу в секундах
     * @param $datetime1
     * @param $datetime2
     *
     * @return int
     * @throws \Exception
     */
    public static function diff(?string $datetime1, ?string $datetime2): int
    {
        try {
            // Проверка входных параметров
            if (empty($datetime1) || empty($datetime2)) {
                return 0;
            }

            $date1 = new DateTime($datetime1);
            $date2 = new DateTime($datetime2);

            $interval = $date1->diff($date2);

            return $interval->days * 24 * 60 * 60 +
                $interval->h * 60 * 60 +
                $interval->i * 60 +
                $interval->s;
        } catch (Exception $e) {
            // Логирование ошибки, если необходимо
            error_log("Time diff error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Форматирование даты из 2024-04-28T15:23:24Z в 2024-04-28 15:23:24
     * @param   string  $date
     *
     * @return string
     */
    public static function iso8601ToMysql(string $date): string
    {
        $dateTime = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $date);
        return $dateTime->format('Y-m-d H:i:s');
    }

    private static ?array $timeoutCache = null;

    /**
     * @return int[]|null
     * Время по умолчанию
     */
    public static function cache_timeout($value = null): array|int {
        if(self::$timeoutCache == null) {
            if(file_exists(fileSys::get_dir('src/config/cache.php'))) {
                require_once(fileSys::get_dir('src/config/cache.php'));
                self::$timeoutCache = $cache_timeout ?? self::defaultTimeout();
            } else {
                self::$timeoutCache = self::defaultTimeout();
            }
        }
        return $value === null ? self::$timeoutCache : (self::$timeoutCache[$value] ?? 60 * 10);
    }


    private static function defaultTimeout(): array {
        return [
            'forum'                           => 60,
            'server_online_status'            => 120,
            'statistic_pvp'                   => 60,
            'statistic_pk'                    => 60,
            'statistic_online'                => 60,
            'statistic_clan'                  => 60,
            'statistic_clan_data'             => 60,
            'statistic_clan_skills'           => 60,
            'statistic_clan_players'          => 60,
            'statistic_heroes'                => 60,
            'statistic_player_info'           => 60,
            'statistic_player_info_sub_class' => 60,
            'statistic_player_inventory_info' => 60,
            'statistic_castle'                => 60,
            'statistic_block'                 => 60,
            'statistic_counter'               => 60,
            'referral'                        => 60,
        ];
    }


    public static function secToHum($timeString) {
        $parts = explode(':', $timeString);
        $hours = (int)$parts[0];
        $minutes = (int)$parts[1];
        $seconds = (int)$parts[2];
        $result = '';
        if ($hours > 0) {
            $result .= $hours . ' ч.';
            $result .= ' ';
        }
        if ($minutes > 0) {
            $result .= $minutes . ' м. ';
        }
        if ($seconds > 0) {
            $result .= $seconds . ' сек.';
        }
        return trim($result == '' ? 'только что' : $result . ' назад');
    }

    public static function getServerTimezone(): DateTimeZone
    {
        if (self::$serverTimezone instanceof DateTimeZone) {
            return self::$serverTimezone;
        }

        $timezoneName = null;

        try {
            if (file_exists(fileSys::get_dir('/data/db.php'))) {
                $timezoneName = config::load()->other()->getTimezone();
            }
        } catch (\Throwable $e) {
            $timezoneName = null;
        }

        if (!$timezoneName) {
            $timezoneName = date_default_timezone_get() ?: 'UTC';
        }

        try {
            self::$serverTimezone = new DateTimeZone($timezoneName);
        } catch (Exception $e) {
            self::$serverTimezone = new DateTimeZone('UTC');
        }

        return self::$serverTimezone;
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