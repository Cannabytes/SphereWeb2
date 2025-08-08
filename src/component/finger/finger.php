<?php

namespace Ofey\Logan22\component\finger;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\session\session;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;

class finger
{

    private static array $keysToInclude = [
        'webgl.commonImageHash',
        'audio.sampleHash',
        'system.platform',
        'locales.timezone',
        'hardware.videocard.renderer',
    ];

    static function createFingerHash(string $fingerStr): string
    {
        $finger = json_decode($fingerStr, true);
        $currentComponents = self::extractStableComponents($finger, self::$keysToInclude);
        ksort($currentComponents);
        $_SESSION['finger'] = $fingerStr;
        return substr(hash('sha256', json_encode($currentComponents)), 0, 16);
    }

    static private function extractStableComponents(array $components, array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            if (str_starts_with($key, 'hash:')) {
                $path = substr($key, 5);
                $dataToHash = self::getValueByDotNotation($components, $path);
                if (is_array($dataToHash)) {
                    $stringToHash = implode('|', array_map('strval', $dataToHash));
                    $result[$key] = md5($stringToHash);
                } else {
                    $result[$key] = null;
                }
            } else {
                $result[$key] = self::getValueByDotNotation($components, $key);
            }
        }
        return $result;
    }

    static private function getValueByDotNotation(array $data, string $path)
    {
        $keys = explode('.', $path);
        foreach ($keys as $key) {
            if (!is_array($data) || !array_key_exists($key, $data)) {
                return null;
            }
            $data = $data[$key];
        }
        return $data;
    }

    static function check(string $finger = "")
    {
        if (empty($finger)) {
            return false;
        }
        $finger = json_decode($finger, true);
        if (!is_array($finger)) {
            return false;
        }
        $currentComponents = self::extractStableComponents($finger, self::$keysToInclude);
        ksort($currentComponents);
        if (!isset($_SESSION['finger'])) {
            $_SESSION['finger'] = json_encode($finger);
            $similarity = 100.0;
        } else {
            $storedFingerJson = $_SESSION['finger'];
            $storedFingerArray = json_decode($storedFingerJson, true);
            if (!is_array($storedFingerArray)) {
                return false;
            }
            $storedComponents = self::extractStableComponents($storedFingerArray, self::$keysToInclude);
            ksort($storedComponents);
            $similarity = self::calcSimilarityPercent($storedComponents, $currentComponents);
        }
        if ($similarity < 80) {
            return false;
        }
        return true;
    }

    static private function calcSimilarityPercent(array $a, array $b): float
    {
        $matches = 0;
        $total = 0;
        foreach ($a as $key => $val) {
            if (!array_key_exists($key, $b)) {
                continue;
            }
            $total++;
            if ($val === $b[$key]) {
                $matches++;
            }
        }
        if ($total === 0)
            return 100.0;
        return ($matches / $total) * 100;
    }

    static public function fingerController()
    {
        if (!user::self()->isAuth()) {
            die("Fail 1");
        }
        if (!isset($_POST['finger'])) {
            die("Fail 2");
        }
        if (!finger::check($_POST['finger'])) {
            session::clear();
            die("clear");
        }
        die("ok");
    }

}