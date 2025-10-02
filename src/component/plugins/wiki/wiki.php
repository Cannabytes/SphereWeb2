<?php

namespace Ofey\Logan22\component\plugins\wiki;

use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\image\client_icon;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\template\tpl;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\model\server\server;

class wiki
{
    /**
     * Global TTL in seconds for all wiki page content caches.
     * Adjust this to change cache lifetime for every page at once.
     */
    public static $pageCacheTtl = 3600*24; //  minutes 

    /**
     * Global TTL in seconds for wiki API/JSON caches (e.g., weaponsData).
     */
    public static $apiCacheTtl = 3600*24; 

    /**
     * Toggle whole-page HTML caching for wiki pages. Disabled by default now to avoid language mix issues.
     * Data-level caching (SQLite query results) is used instead via DataCache.
     */
    public static bool $enablePageCache = false;

     /**
     * Admin: Download .db file from Go server and save to local plugin db folder
     */
    public function adminDownloadDb(): void
    {
        \Ofey\Logan22\model\admin\validation::user_protection('admin');
        $file = $_REQUEST['file'] ?? '';
        if (!is_string($file) || $file === '' || !preg_match('/^[\w\-.]+\.db$/', $file)) {
            $this->jsonResponse(["ok" => false, "error" => "Некорректное имя файла."]);
            return;
        }
        // Запрос к Go-серверу на скачивание файла через GET
        $path = '/api/wiki/download/' . urlencode($file);
        $response = \Ofey\Logan22\component\sphere\server::sendCustomDownload($path);
        if (!is_array($response) || empty($response['content'])) {
            $this->jsonResponse(["ok" => false, "error" => "Файл не получен с сервера."]);
            return;
        }
        if(isset($response['http_code']) && $response['http_code'] === 404) {
            $this->jsonResponse(["ok" => false, "error" => "Файл не найден на сервере."]);
            return;
        }
        $dbDir = __DIR__ . '/db';
        if (!is_dir($dbDir)) {
            @mkdir($dbDir, 0755, true);
        }
        $savePath = $dbDir . DIRECTORY_SEPARATOR . $file;
        try {
            file_put_contents($savePath, $response['content']);
        } catch (\Throwable $e) {
            $this->jsonResponse(["ok" => false, "error" => "Ошибка сохранения файла: " . $e->getMessage()]);
            return;
        }
        $this->jsonResponse(["ok" => true]);
    }

    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    public function __construct()
    {
        // Only enforce when plugin is enabled
        if (!(bool)plugin::getPluginActive('wiki')) {
            return;
        }
        $serverId = 0;
        try { $serverId = (int)\Ofey\Logan22\model\user\user::self()->getServerId(); } catch (\Throwable $e) { $serverId = 0; }
        if ($serverId <= 0) {
            // If this looks like an AJAX/JSON request, return JSON error to avoid HTML redirects in XHR
            $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
            $xrw = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
            $isJson = (stripos($accept, 'application/json') !== false) || ($xrw === 'xmlhttprequest') || (isset($_GET['format']) && $_GET['format'] === 'json');
            if ($isJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'no_server_selected']);
                exit;
            }
            redirect::location('/main');
        }
    }
    /** Build a stable page-cache key array for wiki pages, including route, lang, server id, and selected DB file. */
    private static function pageCacheKey(string $route, array $extra = []): array
    {
        try { $lang = \Ofey\Logan22\controller\config\config::load()->lang()->lang_user_default(); } catch (\Throwable $e) { $lang = 'en'; }
        $serverId = 0; try { $serverId = (int)\Ofey\Logan22\model\user\user::self()->getServerId(); } catch (\Throwable $e) {}
        $dbFile = basename(self::dbPath());
        $base = ['wiki', $route, 'lang:'.$lang, 'srv:'.$serverId, 'db:'.$dbFile];
        foreach ($extra as $k => $v) { $base[] = is_scalar($v) ? $k.':'.$v : $k.':'.md5(json_encode($v)); }
        $pretty = self::prettyCachePath($route, $extra);
        if ($pretty !== null && $pretty !== '') {
            $base[] = 'path:' . $pretty;
        }
        return $base;
    }

    /** Build human-readable cache file path for wiki pages to organize caches nicely. */
    private static function prettyCachePath(string $route, array $extra): ?string
    {
        switch ($route) {
            case 'index':
                return 'index/npc-min-' . self::slugifyCacheValue($extra['npcMin'] ?? null, 'default') . '.html';
            case 'weapons':
                return 'items/weapons/' . self::slugifyCacheValue($extra['type'] ?? 'all', 'all') . '.html';
            case 'armors':
                return 'items/armors/bodypart-' . self::slugifyCacheValue($extra['bp'] ?? 'all', 'all') . '.html';
            case 'armors_other':
                return 'items/armors/other/bodypart-' . self::slugifyCacheValue($extra['bp'] ?? 'all', 'all') . '.html';
            case 'jewelry':
                return 'items/jewelry/filter-' . self::slugifyCacheValue($extra['filter'] ?? 'all', 'all') . '.html';
            case 'recipes':
                return 'items/recipes/index.html';
            case 'recipe_product':
                return 'items/recipes/product-' . self::slugifyCacheValue($extra['product'] ?? 'unknown', 'unknown') . '.html';
            case 'etcitems':
                $type = self::slugifyCacheValue($extra['type'] ?? 'all', 'all');
                $page = (int)($extra['page'] ?? 1);
                return 'items/etc/' . $type . '/page-' . max(1, $page) . '.html';
            case 'items_drop':
                $page = (int)($extra['page'] ?? 1);
                $queryPart = isset($extra['q']) && $extra['q'] !== ''
                    ? 'search-' . self::slugifyCacheValue($extra['q'], 'term')
                    : 'all';
                return 'items/drop/' . $queryPart . '/page-' . max(1, $page) . '.html';
            case 'items_spoil':
                $page = (int)($extra['page'] ?? 1);
                $queryPart = isset($extra['q']) && $extra['q'] !== ''
                    ? 'search-' . self::slugifyCacheValue($extra['q'], 'term')
                    : 'all';
                return 'items/spoil/' . $queryPart . '/page-' . max(1, $page) . '.html';
            case 'npcs':
                return 'npc/index.html';
            case 'npcs_monsters':
                $range = self::slugifyCacheValue($extra['range'] ?? 'all', 'all');
                $page = (int)($extra['page'] ?? 1);
                $flag = (int)($extra['has81'] ?? 0);
                return 'npc/monsters/' . $range . '/page-' . max(1, $page) . '-flag-' . $flag . '.html';
            case 'npcs_raidboses':
                $range = self::slugifyCacheValue($extra['range'] ?? 'all', 'all');
                $page = (int)($extra['page'] ?? 1);
                $flag = (int)($extra['has81'] ?? 0);
                return 'npc/raidbosses/' . $range . '/page-' . max(1, $page) . '-flag-' . $flag . '.html';
            case 'npcs_epicbosses_all':
                return 'npc/epicbosses/all.html';
            case 'npcs_other':
                return 'npc/other/index.html';
            case 'npcs_type':
                return 'npc/type/' . self::slugifyCacheValue($extra['type'] ?? 'all', 'all') . '.html';
            case 'npc_view':
                $idSlug = self::slugifyCacheValue($extra['id'] ?? 'unknown', 'unknown');
                $scopeSlug = isset($extra['scope']) ? self::slugifyCacheValue($extra['scope'], 'guest') : 'guest';
                if ($scopeSlug !== 'guest') {
                    return 'npc/' . $idSlug . '/' . $scopeSlug . '.html';
                }
                return 'npc/' . $idSlug . '.html';
            case 'armorsets':
                return 'items/armor-sets/index.html';
            case 'item_sources':
                $item = self::slugifyCacheValue($extra['item'] ?? 'unknown', 'unknown');
                $filter = self::slugifyCacheValue($extra['filter'] ?? 'all', 'all');
                return 'items/sources/' . $item . '/' . $filter . '.html';
            default:
                $base = 'misc/' . self::slugifyCacheValue($route, 'route');
                if (empty($extra)) {
                    return $base . '/index.html';
                }
                $chunks = [];
                foreach ($extra as $k => $v) {
                    $chunks[] = self::slugifyCacheValue($k, 'key') . '-' . self::slugifyCacheValue($v, 'value');
                    if (count($chunks) >= 4) {
                        break;
                    }
                }
                if (!$chunks) {
                    $chunks[] = 'index';
                }
                return $base . '/' . implode('_', $chunks) . '.html';
        }
    }

    /** Normalize values for cache path usage. */
    private static function slugifyCacheValue($value, string $fallback = 'all'): string
    {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif ($value === null) {
            $value = '';
        } elseif (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $value = trim((string)$value);
        if ($value === '') {
            return $fallback;
        }
        $lower = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        $slug = preg_replace('/[^\p{L}0-9]+/u', '-', $lower);
        if ($slug === null) {
            $slug = '';
        }
        $slug = trim($slug, '-');
        if ($slug === '') {
            return $fallback;
        }
        return $slug;
    }

    /** API/JSON lightweight cache: file-based with TTL, server/lang/db aware via pageCacheKey parts. */
    private static function apiCachePath(array $keyParts, string $ext = 'json'): string
    {
        $hash = sha1(implode('|', $keyParts));
        $dir = fileSys::get_dir('uploads/cache/plugins/wiki/api');
        if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
        return rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $hash . '.' . $ext;
    }

    private static function apiCacheTryServe(array $keyParts, int $ttl): bool
    {
        // Allow bypass
        if (!empty($_GET['no_cache'])) return false;
        $path = self::apiCachePath($keyParts, 'json');
        if (is_file($path)) {
            $age = time() - (int)@filemtime($path);
            if ($age >= 0 && $age <= $ttl) {
                header('Content-Type: application/json; charset=utf-8');
                header('X-API-Cache: HIT');
                $out = @file_get_contents($path);
                if ($out !== false) { echo $out; return true; }
            }
        }
        return false;
    }

    private static function apiCacheSave(array $keyParts, string $payload): void
    {
        $path = self::apiCachePath($keyParts, 'json');
        @file_put_contents($path, $payload, LOCK_EX);
        header('X-API-Cache: MISS-SAVED');
    }

    private static array $gradeInfoCache = [];
    private static array $bodypartLabelsCache = [];
    private static array $bodypartShortCache = [];

    private static function translateOrDefault(string $key, string $default): string
    {
        $value = lang::get_phrase($key);
        if ($value === "[Not phrase «{$key}»]") {
            return $default;
        }
        return $value;
    }

    private static function gradeDefinitions(): array
    {
        return [
            'NG' => [
                'title_key' => 'grade_ng_title',
                'desc_key' => 'grade_ng_desc',
                'default_title' => 'No grade',
                'default_desc' => 'Wearable from the very beginning of the game.',
                'level' => '1+',
            ],
            'D' => [
                'title_key' => 'grade_d_title',
                'desc_key' => 'grade_d_desc',
                'default_title' => 'D Grade',
                'default_desc' => 'Available from level 20.',
                'level' => '20+',
            ],
            'C' => [
                'title_key' => 'grade_c_title',
                'desc_key' => 'grade_c_desc',
                'default_title' => 'C Grade',
                'default_desc' => 'Available from level 40.',
                'level' => '40+',
            ],
            'B' => [
                'title_key' => 'grade_b_title',
                'desc_key' => 'grade_b_desc',
                'default_title' => 'B Grade',
                'default_desc' => 'Available from level 52.',
                'level' => '52+',
            ],
            'A' => [
                'title_key' => 'grade_a_title',
                'desc_key' => 'grade_a_desc',
                'default_title' => 'A Grade',
                'default_desc' => 'Available from level 61.',
                'level' => '61+',
            ],
            'S' => [
                'title_key' => 'grade_s_title',
                'desc_key' => 'grade_s_desc',
                'default_title' => 'S Grade',
                'default_desc' => 'Available from level 76.',
                'level' => '76+',
            ],
            'S80' => [
                'title_key' => 'grade_s80_title',
                'desc_key' => 'grade_s80_desc',
                'default_title' => 'S80 Grade',
                'default_desc' => 'Available from level 80.',
                'level' => '80+',
            ],
            'S84' => [
                'title_key' => 'grade_s84_title',
                'desc_key' => 'grade_s84_desc',
                'default_title' => 'S84 Grade',
                'default_desc' => 'Available from level 84.',
                'level' => '84+',
            ],
            'R' => [
                'title_key' => 'grade_r_title',
                'desc_key' => 'grade_r_desc',
                'default_title' => 'R Grade',
                'default_desc' => 'Occasionally encountered (R Grade).',
                'level' => '??',
            ],
            'R95' => [
                'title_key' => 'grade_r95_title',
                'desc_key' => 'grade_r95_desc',
                'default_title' => 'R95 Grade',
                'default_desc' => 'Available from level 95.',
                'level' => '95+',
            ],
            'R99' => [
                'title_key' => 'grade_r99_title',
                'desc_key' => 'grade_r99_desc',
                'default_title' => 'R99 Grade',
                'default_desc' => 'Available from level 99.',
                'level' => '99+',
            ],
        ];
    }

    private static function defaultGradeOrder(): array
    {
        return ['R99', 'R95', 'R', 'S84', 'S80', 'S', 'A', 'B', 'C', 'D', 'NG'];
    }

    private static function orderByGrade(array $grouped, ?array $order = null): array
    {
        $order = $order ?: self::defaultGradeOrder();
        $ordered = [];
        foreach ($order as $grade) {
            if (isset($grouped[$grade])) {
                $ordered[$grade] = $grouped[$grade];
            }
        }
        foreach ($grouped as $grade => $items) {
            if (!isset($ordered[$grade])) {
                $ordered[$grade] = $items;
            }
        }
        return $ordered;
    }

    private static function getGradeInfo(): array
    {
        if (!self::$gradeInfoCache) {
            $info = [];
            foreach (self::gradeDefinitions() as $code => $def) {
                $info[$code] = [
                    'title' => self::translateOrDefault($def['title_key'], $def['default_title']),
                    'level' => $def['level'],
                    'desc' => self::translateOrDefault($def['desc_key'], $def['default_desc']),
                ];
            }
            self::$gradeInfoCache = $info;
        }
        return self::$gradeInfoCache;
    }

    private static function bodypartDefinitions(): array
    {
        return [
            'CHEST' => [
                'label_key' => 'bodypart_label_chest',
                'label_default' => 'Chest',
                'short_key' => 'bodypart_short_chest',
                'short_default' => 'Chest',
            ],
            'LEGS' => [
                'label_key' => 'bodypart_label_legs',
                'label_default' => 'Legs',
                'short_key' => 'bodypart_short_legs',
                'short_default' => 'Legs',
            ],
            'FULLARMOR' => [
                'label_key' => 'bodypart_label_fullarmor',
                'label_default' => 'Armor set',
                'short_key' => 'bodypart_short_fullarmor',
                'short_default' => 'Set',
            ],
            'FULL_ARMOR' => [
                'label_key' => 'bodypart_label_fullarmor',
                'label_default' => 'Armor set',
                'short_key' => 'bodypart_short_fullarmor',
                'short_default' => 'Set',
            ],
            'HEAD' => [
                'label_key' => 'bodypart_label_head',
                'label_default' => 'Helmet',
                'short_key' => 'bodypart_short_head',
                'short_default' => 'Helmet',
            ],
            'GLOVES' => [
                'label_key' => 'bodypart_label_gloves',
                'label_default' => 'Gloves',
                'short_key' => 'bodypart_short_gloves',
                'short_default' => 'Gloves',
            ],
            'FEET' => [
                'label_key' => 'bodypart_label_feet',
                'label_default' => 'Boots',
                'short_key' => 'bodypart_short_feet',
                'short_default' => 'Boots',
            ],
            'SHIELD' => [
                'label_key' => 'bodypart_label_shield',
                'label_default' => 'Shield',
                'short_key' => 'bodypart_short_shield',
                'short_default' => 'Shield',
            ],
            'BACK' => [
                'label_key' => 'bodypart_label_back',
                'label_default' => 'Cloak',
                'short_key' => 'bodypart_short_back',
                'short_default' => 'Cloak',
            ],
            'UNDERWEAR' => [
                'label_key' => 'bodypart_label_underwear',
                'label_default' => 'Underwear',
                'short_key' => 'bodypart_short_underwear',
                'short_default' => 'Underw.',
            ],
            'FORMAL_WEAR' => [
                'label_key' => 'bodypart_label_formal_wear',
                'label_default' => 'Formal wear',
                'short_key' => 'bodypart_short_formal_wear',
                'short_default' => 'Formal',
            ],
            'HAIR' => [
                'label_key' => 'bodypart_label_hair',
                'label_default' => 'Hair accessory',
                'short_key' => 'bodypart_short_hair',
                'short_default' => 'Hair',
            ],
            'HAIR2' => [
                'label_key' => 'bodypart_label_hair2',
                'label_default' => 'Hair accessory 2',
                'short_key' => 'bodypart_short_hair2',
                'short_default' => 'Hair 2',
            ],
            'HAIRALL' => [
                'label_key' => 'bodypart_label_hairall',
                'label_default' => 'Hair accessory (all)',
                'short_key' => 'bodypart_short_hairall',
                'short_default' => 'Hair*',
            ],
            'HAIR_ALL' => [
                'label_key' => 'bodypart_label_hairall',
                'label_default' => 'Hair accessory (all)',
                'short_key' => 'bodypart_short_hairall',
                'short_default' => 'Hair*',
            ],
            'FACE' => [
                'label_key' => 'bodypart_label_face',
                'label_default' => 'Mask',
                'short_key' => 'bodypart_short_face',
                'short_default' => 'Mask',
            ],
            'BELT' => [
                'label_key' => 'bodypart_label_belt',
                'label_default' => 'Belt',
                'short_key' => 'bodypart_short_belt',
                'short_default' => 'Belt',
            ],
            'TALISMAN' => [
                'label_key' => 'bodypart_label_talisman',
                'label_default' => 'Talisman',
                'short_key' => 'bodypart_short_talisman',
                'short_default' => 'Talisman',
            ],
            'BROOCH' => [
                'label_key' => 'bodypart_label_brooch',
                'label_default' => 'Brooch',
                'short_key' => 'bodypart_short_brooch',
                'short_default' => 'Brooch',
            ],
            'BROOCH_JEWEL' => [
                'label_key' => 'bodypart_label_brooch_jewel',
                'label_default' => 'Brooch jewel',
                'short_key' => 'bodypart_short_brooch_jewel',
                'short_default' => 'Jewel',
            ],
            'NECK' => [
                'label_key' => 'bodypart_label_neck',
                'label_default' => 'Necklace',
                'short_key' => 'bodypart_short_neck',
                'short_default' => 'Necklace',
            ],
            'NECKLACE' => [
                'label_key' => 'bodypart_label_neck',
                'label_default' => 'Necklace',
                'short_key' => 'bodypart_short_neck',
                'short_default' => 'Necklace',
            ],
            'RBRACELET' => [
                'label_key' => 'bodypart_label_rbracelet',
                'label_default' => 'Right bracelet',
                'short_key' => 'bodypart_short_rbracelet',
                'short_default' => 'R. bracelet',
            ],
            'RIGHT_BRACELET' => [
                'label_key' => 'bodypart_label_rbracelet',
                'label_default' => 'Right bracelet',
                'short_key' => 'bodypart_short_rbracelet',
                'short_default' => 'R. bracelet',
            ],
            'LBRACELET' => [
                'label_key' => 'bodypart_label_lbracelet',
                'label_default' => 'Left bracelet',
                'short_key' => 'bodypart_short_lbracelet',
                'short_default' => 'L. bracelet',
            ],
            'LEFT_BRACELET' => [
                'label_key' => 'bodypart_label_lbracelet',
                'label_default' => 'Left bracelet',
                'short_key' => 'bodypart_short_lbracelet',
                'short_default' => 'L. bracelet',
            ],
            'REAR;LEAR' => [
                'label_key' => 'bodypart_label_earrings',
                'label_default' => 'Earrings',
                'short_key' => 'bodypart_short_earrings',
                'short_default' => 'Earrings',
            ],
            'RIGHT_EAR;LEFT_EAR' => [
                'label_key' => 'bodypart_label_earrings',
                'label_default' => 'Earrings',
                'short_key' => 'bodypart_short_earrings',
                'short_default' => 'Earrings',
            ],
            'RFINGER;LFINGER' => [
                'label_key' => 'bodypart_label_rings',
                'label_default' => 'Rings',
                'short_key' => 'bodypart_short_rings',
                'short_default' => 'Rings',
            ],
            'RIGHT_FINGER;LEFT_FINGER' => [
                'label_key' => 'bodypart_label_rings',
                'label_default' => 'Rings',
                'short_key' => 'bodypart_short_rings',
                'short_default' => 'Rings',
            ],
            'WOLF' => [
                'label_key' => 'bodypart_label_wolf',
                'label_default' => 'Wolf',
                'short_key' => 'bodypart_short_wolf',
                'short_default' => 'Wolf',
            ],
            'GREAT_WOLF' => [
                'label_key' => 'bodypart_label_great_wolf',
                'label_default' => 'Great wolf',
                'short_key' => 'bodypart_short_great_wolf',
                'short_default' => 'Great wolf',
            ],
            'STRIDER' => [
                'label_key' => 'bodypart_label_strider',
                'label_default' => 'Strider',
                'short_key' => 'bodypart_short_strider',
                'short_default' => 'Strider',
            ],
            'HATCHLING' => [
                'label_key' => 'bodypart_label_hatchling',
                'label_default' => 'Hatchling',
                'short_key' => 'bodypart_short_hatchling',
                'short_default' => 'Hatchling',
            ],
            'BABY_PET' => [
                'label_key' => 'bodypart_label_baby_pet',
                'label_default' => 'Baby pet',
                'short_key' => 'bodypart_short_baby_pet',
                'short_default' => 'Baby',
            ],
            'AGATHION_CHARM' => [
                'label_key' => 'bodypart_label_agathion_charm',
                'label_default' => 'Agathion charm',
                'short_key' => 'bodypart_short_agathion_charm',
                'short_default' => 'Agathion',
            ],
        ];
    }

    private static function getBodypartLabels(): array
    {
        if (!self::$bodypartLabelsCache) {
            foreach (self::bodypartDefinitions() as $code => $def) {
                self::$bodypartLabelsCache[$code] = self::translateOrDefault($def['label_key'], $def['label_default']);
            }
        }
        return self::$bodypartLabelsCache;
    }

    private static function getBodypartShortLabels(): array
    {
        if (!self::$bodypartShortCache) {
            foreach (self::bodypartDefinitions() as $code => $def) {
                self::$bodypartShortCache[$code] = self::translateOrDefault($def['short_key'], $def['short_default']);
            }
        }
        return self::$bodypartShortCache;
    }

    private static function filterMapByKeys(array $map, array $keys): array
    {
        if (!$keys) {
            return [];
        }
        $normalized = [];
        foreach ($keys as $key) {
            if (isset($map[$key])) {
                $normalized[$key] = $map[$key];
            }
        }
        return $normalized;
    }
    private $weaponTypes = [
        "ANCIENTSWORD" => "Древний меч",
        "BLUNT"       => "Дробящее оружие (булавы, молоты)",
        "BOW"         => "Лук",
        "CROSSBOW"    => "Арбалет",
        "DAGGER"      => "Кинжал",
        "DUAL"        => "Скрещенные клинки",
        "DUALDAGGER"  => "Два кинжала",
        "DUALFIST"    => "Кастеты",
        "ETC"         => "Прочее, редкие или уникальные предметы",
        "FISHINGROD"  => "Удочка",
        "POLE"        => "Длинное оружие (посохи, копья)",
        "RAPIER"      => "Рапира",
        "SWORD"       => "Меч"
    ];

    // Guard helpers to ensure plugin is active
    private function isActive(): bool
    {
        return (bool)plugin::getPluginActive("wiki");
    }

    /** Return current absolute DB path for wiki (selected in settings). */
    public static function dbPath(): string
    {
        return WikiDb::getSelectedPath();
    }

    private function ensureActiveHtml(): void
    {
        if (!$this->isActive()) {
            redirect::location("/main");
        }
    }

    private function ensureActiveJson(): bool
    {
        if (!$this->isActive()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'plugin_disabled']);
            return false;
        }
        return true;
    }

    /** Resolve absolute cache base dir for wiki caches (HTML + API). */
    public static function cacheBaseDir(): string
    {
        return fileSys::get_dir('uploads/cache/plugins/wiki');
    }

    /** Count cached files under the wiki cache directory. */
    public static function countCacheFiles(): int
    {
        $base = self::cacheBaseDir();
        if (!is_dir($base)) return 0;
        $count = 0;
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $file) {
            if ($file->isFile()) { $count++; }
        }
        return $count;
    }

    /** Optional admin helper to clear all wiki cached files and remove the base directory. */
    public static function clearPageCache(): void
    {
        $base = self::cacheBaseDir();
        if (is_dir($base)) {
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $file) {
                if ($file->isDir()) @rmdir($file->getRealPath()); else @unlink($file->getRealPath());
            }
            // Remove the base dir itself
            @rmdir($base);
        }
    }

    /** Admin endpoint: clear wiki cache and redirect back. */
    public function adminClearCache(): void
    {
        validation::user_protection('admin');
        self::clearPageCache();
        
        // Optional success message for AJAX or UI feedback
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'ok']);
            return;
        }
    \Ofey\Logan22\component\alert\board::success(\Ofey\Logan22\component\lang\lang::get_phrase('wiki_cache_cleared'));
        redirect::location('/admin/plugin/wiki');
    }

    /** Admin settings page */
    public function setting(): void
    {
        validation::user_protection("admin");
        $setting = plugin::getSetting("wiki");
        $cacheCount = self::countCacheFiles();
        // Detect if SQLite3 extension is available (required for this plugin)
        $sqliteEnabled = extension_loaded('sqlite3') && class_exists('\SQLite3');
        // Discover available DB files inside plugin db folder
        $dbDir = __DIR__ . '/db';
        $files = [];
        $descriptions = [];
        if (is_dir($dbDir)) {
            foreach (glob($dbDir . '/*.db') ?: [] as $path) {
                $file = basename($path);
                $files[] = $file;
                // Try to read optional description from table "desc" (column text)
                try {
                    $db = new \SQLite3($path, \SQLITE3_OPEN_READONLY);
                    $stmt = @$db->prepare('SELECT text FROM "desc" LIMIT 1');
                    $desc = null;
                    if ($stmt) {
                        $res = @$stmt->execute();
                        if ($res) {
                            $row = $res->fetchArray(\SQLITE3_ASSOC);
                            if ($row && isset($row['text'])) {
                                $desc = trim((string)$row['text']);
                            }
                            @$res->finalize();
                        }
                        @$stmt->close();
                    }
                    @$db->close();
                    if ($desc !== null && $desc !== '') {
                        $descriptions[$file] = $desc;
                    }
                } catch (\Throwable $e) {
                    // ignore, leave without description
                }
            }
            sort($files, SORT_NATURAL | SORT_FLAG_CASE);
        }

        $currentDb = isset($setting['dbFile']) && is_string($setting['dbFile']) && $setting['dbFile'] !== ''
            ? $setting['dbFile']
            : 'highfive.db';

        if (server::get_count_servers() === 0) {
            tpl::addVar('noServers', true);
            tpl::addVar('dbListUnified', []);
            tpl::addVar('dbFiles', $files);
            tpl::addVar('currentDb', $currentDb);
            tpl::addVar('cacheCount', $cacheCount);
            tpl::addVar('setting', $setting);
            tpl::addVar('dbDescriptions', $descriptions);
            tpl::addVar('sqliteEnabled', $sqliteEnabled);
            tpl::displayPlugin("wiki/tpl/setting.html");
            return;
        }
        
        // Получение списка файлов баз данных NPC с Go-сервера
        $dbListWiki = \Ofey\Logan22\component\sphere\server::send(type::WIKI_DB_LIST, [])->show()->getResponse();
       
        $DbFiles = [];
        if (is_array($dbListWiki) && isset($dbListWiki['files']) && is_array($dbListWiki['files'])) {
            $DbFiles = $dbListWiki['files'];
        }

        // Собираем объединённый список для шаблона
        $dbs = [];
        foreach ($DbFiles as $file) {
            // Если $file — массив с ключом 'name', берём имя из него
            if (is_array($file) && isset($file['name'])) {
                $fname = $file['name'];
            } else {
                $fname = $file;
            }
            $isDownloaded = in_array($fname, $files, true);
            $dbs[] = [
                'name' => $fname,
                'isDownloaded' => $isDownloaded,
                'onServer' => true,
                'description' => $descriptions[$fname] ?? '',
                'selected' => ($currentDb === $fname),
            ];
        }
        // Ensure local-only DB files (present in $files but not returned by server) are also included
        $existing = [];
        foreach ($dbs as $d) { $existing[$d['name']] = true; }
        foreach ($files as $localFile) {
            if (!isset($existing[$localFile])) {
                $dbs[] = [
                    'name' => $localFile,
                    'isDownloaded' => true,
                    'onServer' => false,
                    'description' => $descriptions[$localFile] ?? '',
                    'selected' => ($currentDb === $localFile),
                ];
            }
        }
        // Natural sort by name to keep stable ordering in template
        usort($dbs, function($a, $b){ return strnatcasecmp($a['name'], $b['name']); });

        tpl::addVar('dbListUnified', $dbs);
        // Also expose raw local filenames list to template (used to detect purely-local files)
        tpl::addVar('dbFiles', $files);
        tpl::addVar('currentDb', $currentDb);
        tpl::addVar('cacheCount', $cacheCount);
        tpl::addVar('setting', $setting);
        tpl::addVar('dbDescriptions', $descriptions);
        // Count pending moderation images
        $moderationDir = \Ofey\Logan22\component\fileSys\fileSys::get_dir('uploads/images/npc/moderation');
        $logFile = $moderationDir . DIRECTORY_SEPARATOR . 'upload_log.json';
        $pendingImagesCount = 0;
        if (file_exists($logFile)) {
            try {
                $entries = json_decode(file_get_contents($logFile), true) ?: [];
                foreach ($entries as $entry) {
                    $filename = $entry['filename'] ?? null;
                    if (!$filename) continue;
                    $p = $moderationDir . DIRECTORY_SEPARATOR . $filename;
                    if (is_file($p)) $pendingImagesCount++;
                }
            } catch (\Throwable $e) {}
        }
    tpl::addVar('pendingImagesCount', $pendingImagesCount);
    tpl::addVar("pluginName", "wiki");
    // Only report plugin as active to template if sqlite is enabled
    $pluginActive = $sqliteEnabled && (bool)plugin::getPluginActive("wiki");
    tpl::addVar("pluginActive", $pluginActive);
    tpl::addVar('sqliteEnabled', $sqliteEnabled);
        // Count pending moderation images
        $moderationDir = \Ofey\Logan22\component\fileSys\fileSys::get_dir('uploads/images/npc/moderation');
        $logFile = $moderationDir . DIRECTORY_SEPARATOR . 'upload_log.json';
        $pendingImagesCount = 0;
        if (file_exists($logFile)) {
            try {
                $entries = json_decode(file_get_contents($logFile), true) ?: [];
                foreach ($entries as $entry) {
                    $filename = $entry['filename'] ?? null;
                    if (!$filename) continue;
                    $p = $moderationDir . DIRECTORY_SEPARATOR . $filename;
                    if (is_file($p)) $pendingImagesCount++;
                }
            } catch (\Throwable $e) {}
        }
        tpl::addVar('pendingImagesCount', $pendingImagesCount);
        tpl::addVar("pluginName", "wiki");
        // repeat vars for consistency (second block kept originally) but ensure sqlite gating
        tpl::addVar("pluginActive", $pluginActive);
        tpl::addVar('sqliteEnabled', $sqliteEnabled);
        tpl::displayPlugin("wiki/tpl/setting.html");
    }

    public function show(): void
    {
    $this->ensureActiveHtml();

        // Read configurable NPC search min length up-front and expose to template.
        // IMPORTANT: include it in the page cache key so that changing the setting invalidates cached HTML.
        try { $setting = plugin::getSetting('wiki'); } catch (\Throwable $e) { $setting = []; }
        $minLen = isset($setting['npcSearchMinLen']) ? (int)$setting['npcSearchMinLen'] : 4;
        if ($minLen < 1) $minLen = 1;
        tpl::addVar('wiki_min_search_len', $minLen);

        // Try resolve cached content (content-only cache). On hit, render with wrapper via tpl and exit
    if (self::$enablePageCache && \Ofey\Logan22\template\tpl::pageCacheTryServe(self::pageCacheKey('index', ['npcMin' => $minLen]), self::$pageCacheTtl, 'wiki', false, true)) {
            tpl::displayPlugin("/wiki/tpl/index.html");
            return;
        }

    $classRepo = new ClassRepository();
        $classesByRace = $classRepo->getClassesByRace();

    tpl::addVar('classesByRace', $classesByRace);
    // Enable content-only cache capture on render
    if (self::$enablePageCache) { \Ofey\Logan22\template\tpl::pageCacheBegin(self::pageCacheKey('index', ['npcMin' => $minLen]), self::$pageCacheTtl, 'wiki', false, true); }
        tpl::displayPlugin("/wiki/tpl/index.html");
    }

    /**
     * AJAX: return spawn points for NPC as JSON
     * URL: /wiki/npc/spawns/{id}
     */
    public function npcSpawnsAjax(int $id): void
    {
    if (!$this->ensureActiveJson()) return;
        header('Content-Type: application/json; charset=utf-8');
        if ($id <= 0) {
            echo json_encode(['error' => 'bad_id']);
            return;
        }
        $points = self::getNpcSpawnPoints($id);
        // include map calibration offsets to allow client to compute positions consistently
        $payload = [
            'npc_id' => $id,
            'points' => $points,
            'map_calibration' => ['x' => self::MAP_OFFSET_X, 'y' => self::MAP_OFFSET_Y],
        ];
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Lightweight item info endpoint for tooltips.
     * URL: /wiki/item/{id}
     * Method: GET (returns JSON)
     * Provides: basic properties needed for hover popup.
     */
    public function itemInfo(int $id = 0): void
    {
        if (!$this->ensureActiveJson()) return;
        header('Content-Type: application/json; charset=utf-8');
        if ($id <= 0) {
            $postId = null;
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Try form-encoded first
                if (isset($_POST['id'])) {
                    $postId = (int)$_POST['id'];
                } else {
                    // Try raw JSON
                    $raw = file_get_contents('php://input');
                    if ($raw) {
                        $j = json_decode($raw, true);
                        if (is_array($j) && isset($j['id'])) {
                            $postId = (int)$j['id'];
                        }
                    }
                }
            }
            if ($postId !== null && $postId > 0) {
                $id = $postId;
            }
        }
        $id = (int)$id;
        if ($id <= 0) { echo json_encode(['ok'=>false,'error'=>'bad_id','hint'=>'use POST id=NUM']); return; }
        try {
            $db = WikiDb::get();
            // Реальные таблицы не содержат единый набор колонок. Выполним последовательные запросы
            // — сначала weapons, затем armors, затем etcitems — и сопоставим поля в единый формат.
            $row = null;

            // 1) weapons - build safe SELECT only using columns that actually exist in the table
            $buildSelectFor = function(string $table, array $mapping) use ($db): string {
                $cols = [];
                $existent = [];
                $q = @$db->query("PRAGMA table_info('" . $table . "')");
                if ($q) {
                    while ($r = $q->fetchArray(SQLITE3_ASSOC)) {
                        $existent[$r['name']] = true;
                    }
                }
                // mapping: key => SQL fragment if exists, valueFallback used when not
                foreach ($mapping as $alias => $frag) {
                    if (is_array($frag)) {
                        // frag = [columnName, fallbackSql]
                        [$colName, $fallback] = $frag;
                        if (isset($existent[$colName])) $cols[] = $colName . ' AS ' . $alias;
                        else $cols[] = $fallback . ' AS ' . $alias;
                    } else {
                        // frag is literal SQL fragment (always included)
                        $cols[] = $frag . ' AS ' . $alias;
                    }
                }
                return 'SELECT ' . implode(', ', $cols) . ' FROM ' . $table . ' WHERE id = :id LIMIT 1';
            };

            $mappingW = [
                'id' => 'id',
                'name' => 'name',
                'icon' => 'icon',
                'crystal_type' => 'crystal_type',
                'price' => 'price',
                'source' => "'weapon'",
                'for_json' => ['for', "NULL"],
                'item_type' => ['weapon_type', "NULL"],
                'is_drop' => ['is_drop', '0'],
                'is_sweep' => ['is_sweep', '0'],
                'is_craft' => ['is_craft', '0'],
                'item_skill' => ['item_skill', 'NULL'],
                'is_stackable' => ['is_stackable', '0'],
            ];
            $sqlW = $buildSelectFor('weapons', $mappingW);
            $stmt = $db->prepare($sqlW);
            if (!$stmt) {
                $errCode = method_exists($db, 'lastErrorCode') ? $db->lastErrorCode() : null;
                $errMsg = method_exists($db, 'lastErrorMsg') ? $db->lastErrorMsg() : null;
                echo json_encode(['ok' => false, 'error' => 'db_prepare_failed', 'table' => 'weapons', 'db_error_code' => $errCode, 'db_error_msg' => $errMsg]);
                return;
            }
            $stmt->bindValue(':id', $id, \SQLITE3_INTEGER);
            $res = $stmt->execute();
            if ($res === false) {
                $errCode = method_exists($db, 'lastErrorCode') ? $db->lastErrorCode() : null;
                $errMsg = method_exists($db, 'lastErrorMsg') ? $db->lastErrorMsg() : null;
                echo json_encode(['ok' => false, 'error' => 'db_execute_failed', 'table' => 'weapons', 'db_error_code' => $errCode, 'db_error_msg' => $errMsg]);
                return;
            }
            $row = $res->fetchArray(\SQLITE3_ASSOC) ?: null;

            // 2) armors
            if (!$row) {
                $sqlA = 'SELECT id,name,icon,crystal_type,price,\'armor\' AS source, "for" AS for_json, armor_type AS item_type,'
                      . ' COALESCE(is_dropable,0) AS is_dropable, COALESCE(is_sellable,0) AS is_sellable, COALESCE(is_tradable,0) AS is_tradable,'
                      . ' COALESCE(is_drop,0) AS is_drop, COALESCE(is_sweep,0) AS is_sweep, COALESCE(is_craft,0) AS is_craft,'
                      . ' COALESCE(item_skill, NULL) AS item_skill, 0 AS is_stackable'
                      . ' FROM armors WHERE id = :id LIMIT 1';
                $mappingA = [
                    'id' => 'id',
                    'name' => 'name',
                    'icon' => 'icon',
                    'crystal_type' => 'crystal_type',
                    'price' => 'price',
                    'source' => "'armor'",
                    'for_json' => ['for', "NULL"],
                    'item_type' => ['armor_type', "NULL"],
                    // dropable/sellable/tradable removed from schema; derive flags from other fields if needed
                    'is_drop' => ['is_drop', '0'],
                    'is_sweep' => ['is_sweep', '0'],
                    'is_craft' => ['is_craft', '0'],
                    'item_skill' => ['item_skill', 'NULL'],
                    'is_stackable' => ['is_stackable', '0'],
                ];
                $sqlA = $buildSelectFor('armors', $mappingA);
                $stmt = $db->prepare($sqlA);
                if (!$stmt) {
                    $errCode = method_exists($db, 'lastErrorCode') ? $db->lastErrorCode() : null;
                    $errMsg = method_exists($db, 'lastErrorMsg') ? $db->lastErrorMsg() : null;
                    echo json_encode(['ok' => false, 'error' => 'db_prepare_failed', 'table' => 'armors', 'db_error_code' => $errCode, 'db_error_msg' => $errMsg]);
                    return;
                }
                $stmt->bindValue(':id', $id, \SQLITE3_INTEGER);
                $res = $stmt->execute();
                if ($res === false) {
                    $errCode = method_exists($db, 'lastErrorCode') ? $db->lastErrorCode() : null;
                    $errMsg = method_exists($db, 'lastErrorMsg') ? $db->lastErrorMsg() : null;
                    echo json_encode(['ok' => false, 'error' => 'db_execute_failed', 'table' => 'armors', 'db_error_code' => $errCode, 'db_error_msg' => $errMsg]);
                    return;
                }
                $row = $res->fetchArray(\SQLITE3_ASSOC) ?: null;
            }

            // 3) etcitems
            if (!$row) {
                $sqlE = 'SELECT id,name,icon,crystal_type,price,\'etcitem\' AS source, "for" AS for_json, etcitem_type AS item_type,'
                      . ' COALESCE(is_dropable,0) AS is_dropable, COALESCE(is_sellable,0) AS is_sellable, COALESCE(is_tradable,0) AS is_tradable,'
                      . ' COALESCE(is_drop,0) AS is_drop, COALESCE(is_sweep,0) AS is_sweep, COALESCE(is_craft,0) AS is_craft,'
                      . ' COALESCE(item_skill, NULL) AS item_skill, COALESCE(is_stackable,0) AS is_stackable'
                      . ' FROM etcitems WHERE id = :id LIMIT 1';
                $mappingE = [
                    'id' => 'id',
                    'name' => 'name',
                    'icon' => 'icon',
                    'crystal_type' => 'crystal_type',
                    'price' => 'price',
                    'source' => "'etcitem'",
                    'for_json' => ['for', "NULL"],
                    'item_type' => ['etcitem_type', "NULL"],
                    // dropable/sellable/tradable removed from schema; derive flags from other fields if needed
                    'is_drop' => ['is_drop', '0'],
                    'is_sweep' => ['is_sweep', '0'],
                    'is_craft' => ['is_craft', '0'],
                    'item_skill' => ['item_skill', 'NULL'],
                    'is_stackable' => ['is_stackable', '0'],
                ];
                $sqlE = $buildSelectFor('etcitems', $mappingE);
                $stmt = $db->prepare($sqlE);
                if (!$stmt) {
                    $errCode = method_exists($db, 'lastErrorCode') ? $db->lastErrorCode() : null;
                    $errMsg = method_exists($db, 'lastErrorMsg') ? $db->lastErrorMsg() : null;
                    echo json_encode(['ok' => false, 'error' => 'db_prepare_failed', 'table' => 'etcitems', 'db_error_code' => $errCode, 'db_error_msg' => $errMsg]);
                    return;
                }
                $stmt->bindValue(':id', $id, \SQLITE3_INTEGER);
                $res = $stmt->execute();
                if ($res === false) {
                    $errCode = method_exists($db, 'lastErrorCode') ? $db->lastErrorCode() : null;
                    $errMsg = method_exists($db, 'lastErrorMsg') ? $db->lastErrorMsg() : null;
                    echo json_encode(['ok' => false, 'error' => 'db_execute_failed', 'table' => 'etcitems', 'db_error_code' => $errCode, 'db_error_msg' => $errMsg]);
                    return;
                }
                $row = $res->fetchArray(\SQLITE3_ASSOC) ?: null;
            }

            if (!$row) { echo json_encode(['ok'=>false,'error'=>'not_found','id'=>$id]); return; }
            // Normalize booleans and build icon path via unified resolver
            $iconRaw = $row['icon'] ?? '';
            $iconPath = client_icon::getIcon($iconRaw, 'icon');
            $flags = [
                'tradable' => (bool)($row['is_tradable'] ?? false),
                'sellable' => (bool)($row['is_sellable'] ?? false),
                'dropable' => (bool)($row['is_dropable'] ?? false),
                'stackable' => (bool)($row['is_stackable'] ?? false),
                'craft' => (bool)($row['is_craft'] ?? false),
                'sweep' => (bool)($row['is_sweep'] ?? false),
            ];
            // Парсим JSON из поля for_json для извлечения стат
            $stats = [];
            if(!empty($row['for_json'])){
                $decoded = json_decode($row['for_json'], true);
                if(is_array($decoded)){
                    $labels = StatLabels::all();
                    $acc = [];
                    foreach($decoded as $entry){
                        if(!is_array($entry) || !isset($entry['stat'],$entry['type'],$entry['val'])) continue;
                        $stat = (string)$entry['stat'];
                        $type = strtolower((string)$entry['type']);
                        $val = $entry['val'];
                        if(in_array($type,['add','set'])){
                            $acc[$stat] = $val; // base value
                        }
                    }
                    // Приоритет популярных статов
                    $order = ['pAtk','mAtk','critRate','pAtkSpd','pDef','mDef'];
                    foreach($order as $s){ if(isset($acc[$s])){ $stats[] = ['code'=>$s,'label'=>$labels[$s] ?? $s,'value'=>$acc[$s]]; unset($acc[$s]); } }
                    // Остальные (ограничим 10)
                    $left = array_slice($acc,0,10,true);
                    foreach($left as $s=>$v){ $stats[] = ['code'=>$s,'label'=>$labels[$s] ?? $s,'value'=>$v]; }
                }
            }
            $payload = [
                'ok' => true,
                'id' => (int)$row['id'],
                'name' => $row['name'] ?? ('Item '.$id),
                'description' => '',
                'icon' => $iconPath,
                'source' => $row['source'] ?? null,
                'crystal_type' => $row['crystal_type'] ?? null,
                'item_type' => $row['item_type'] ?? null,
                'price' => is_numeric($row['price'] ?? null) ? (int)$row['price'] : ($row['price'] ?? null),
                'stats' => $stats, // list of {code,label,value}
                'flags' => $flags,
            ];
            echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            echo json_encode(['ok'=>false,'error'=>'exception']);
        }
    }

    /**
     * Weapons list page. If $type omitted, default to SWORD.
     * @param string|null $type
     */
    public function weapons(?string $type = null): void
    {
    $this->ensureActiveHtml();
    // Content cache for weapons list per type
        $___typePre = $type ? strtoupper($type) : 'SWORD';
    if (self::$enablePageCache && \Ofey\Logan22\template\tpl::pageCacheTryServe(self::pageCacheKey('weapons', ['type' => $___typePre]), self::$pageCacheTtl, 'wiki', false, true)) {
            tpl::displayPlugin('/wiki/tpl/weapons.html');
            return;
        }
        $time_total_start = microtime(true);
        $time_db_open_start = microtime(true);
        $repo = new WeaponRepository();
        $time_db_open_end = microtime(true);
        $time_read_start = microtime(true);
        $types = $repo->getWeaponTypes();
        $type = $type ? strtoupper($type) : 'SWORD';
        if (!in_array($type, $types)) $type = $types[0] ?? 'SWORD';
        // Загружаем только первые N предметов (остальное через AJAX)
        $initialPerPage = 30;
        $rows = $repo->getWeaponsPage($type, 0, $initialPerPage);
        $totalCount = $repo->countWeaponsByType($type);
        $initialCount = count($rows);
        // Группируем подмножество по грейду
        $grouped = [];
        foreach ($rows as $r) {
            $g = $r['_grade'] ?? 'NG';
            $grouped[$g][] = $r;
        }
        $typeLabels = $this->weaponTypes;
        foreach ($types as $t) if (!isset($typeLabels[$t])) $typeLabels[$t] = ucfirst(strtolower($t));
        $gradeInfo = self::getGradeInfo();
        $orderedGrouped = self::orderByGrade($grouped);
        tpl::addVar('weaponTypes', $types);
        tpl::addVar('weaponTypeLabels', $typeLabels);
        tpl::addVar('currentWeaponType', $type);
        tpl::addVar('weaponsByCrystal', $orderedGrouped);
        tpl::addVar('weaponGradeInfo', $gradeInfo);
        tpl::addVar('weapon_per_page', $initialPerPage);
        tpl::addVar('weapon_total_count', $totalCount);
        tpl::addVar('weapon_initial_count', $initialCount);
        $stateLabels = StatLabels::all();
        foreach ($orderedGrouped as $grade => $items) {
            foreach ($items as $idx => $row) {
                $stats = [];
                $mainCodes = ['pAtk', 'mAtk', 'critRate', 'pAtkSpd'];
                $mainBase = [];
                $mainEnchant = [];
                // Pre-resolve weapon icon full path (unified helper) so шаблон не строит несуществующие пути
                if (!isset($orderedGrouped[$grade][$idx]['icon_path'])) {
                    $orderedGrouped[$grade][$idx]['icon_path'] = client_icon::getIcon($row['icon'] ?? '', 'icon');
                }
                if (!empty($row['for'])) {
                    $decoded = json_decode($row['for'], true);
                    if (is_array($decoded)) {
                        $baseValues = [];
                        $enchantValues = [];
                        foreach ($decoded as $entry) {
                            if (!isset($entry['stat'], $entry['type'], $entry['val'])) continue;
                            $stat = $entry['stat'];
                            $tag = $entry['type'];
                            $val = $entry['val'];
                            if ($tag === 'add' || $tag === 'set') $baseValues[$stat] = $val;
                            elseif ($tag === 'enchant' && (int)$val > 0) $enchantValues[$stat] = $val;
                        }
                        foreach ($baseValues as $code => $val) {
                            $label = $stateLabels[$code] ?? $code;
                            $suffix = '';
                            if (isset($enchantValues[$code])) $suffix = ' (+' . $enchantValues[$code] . ' за заточку)';
                            $stats[] = ['code' => $code, 'label' => $label, 'value' => $val, 'extra' => $suffix];
                        }
                        foreach ($mainCodes as $code) {
                            if (isset($baseValues[$code])) $mainBase[$code] = $baseValues[$code];
                            if (isset($enchantValues[$code])) $mainEnchant[$code] = $enchantValues[$code];
                        }
                    }
                }
                $orderedGrouped[$grade][$idx]['stats'] = $stats;
                foreach ($mainCodes as $code) {
                    $orderedGrouped[$grade][$idx][$code] = $mainBase[$code] ?? null;
                    if (isset($mainEnchant[$code])) $orderedGrouped[$grade][$idx][$code . '_enchant'] = $mainEnchant[$code];
                }
                $price = $row['price'] ?? '';
                $orderedGrouped[$grade][$idx]['price_formatted'] = is_numeric($price) ? number_format((float)$price, 0, '.', ' ') : $price;
                $skillList = [];
                if (array_key_exists('item_skill', $row)) {
                    $parsed = $repo->parseItemSkillRaw($row['item_skill']);
                    foreach ($parsed as $s) {
                        $sid = (int)($s['skill_id'] ?? 0);
                        $lvl = max(1, (int)($s['skill_level'] ?? 1));
                        if ($sid <= 0) continue;
                        $meta = $repo->getSkillById($sid);
                        $entry = [
                            'id' => $sid,
                            'level' => $lvl,
                            'name' => $meta['name'] ?? ('Skill ' . $sid),
                            'icon' => $meta['icon'] ?? null,
                            // Pre-resolved full web path using unified client_icon::getIcon
                            'icon_path' => WeaponRepository::resolveSkillIcon($meta['icon'] ?? null),
                        ];
                        if ($meta && !empty($meta['effects'])) {
                            $lvlEffects = [];
                            foreach ($meta['effects'] as $eff) {
                                if (isset($eff['raw_values']) && is_array($eff['raw_values']) && $eff['raw_values']) {
                                    $vals = $eff['raw_values'];
                                    $idxLevel = $lvl - 1;
                                    $chosen = $vals[$idxLevel] ?? end($vals);
                                    $formatted = $this->formatSkillEffectValue($eff['tag'] ?? 'add', $eff['stat'] ?? '', $chosen);
                                    $lvlEffects[] = ['stat' => $eff['stat'] ?? null, 'label' => $eff['label'] ?? ($eff['stat'] ?? ''), 'formatted' => $formatted, 'text' => $formatted . ' ' . ($eff['label'] ?? ($eff['stat'] ?? ''))];
                                } elseif (isset($eff['formatted'])) {
                                    $lvlEffects[] = $eff;
                                }
                            }
                            if ($lvlEffects) {
                                $entry['effects'] = $lvlEffects;
                                $entry['effect_text'] = implode(', ', array_map(fn($e) => $e['text'] ?? ($e['formatted'] ?? ''), $lvlEffects));
                                $entry['formatted'] = array_map(fn($e) => $e['formatted'] ?? ($e['text'] ?? ''), $lvlEffects);
                            }
                        }
                        $skillList[] = $entry;
                    }
                }
                $orderedGrouped[$grade][$idx]['skills'] = $skillList;
            }
        }
        tpl::addVar('weaponsByCrystal', $orderedGrouped);
        tpl::addVar('weaponStatLabels', $stateLabels);
        $time_read_end = microtime(true);
        $time_total_end = microtime(true);
        $timing = [
            'db_open_s' => number_format(($time_db_open_end - $time_db_open_start), 6, '.', ''),
            'read_s' => number_format(($time_read_end - $time_read_start), 6, '.', ''),
            'total_s' => number_format(($time_total_end - $time_total_start), 6, '.', ''),
        ];
        $timing['db_open_ms'] = number_format(($time_db_open_end - $time_db_open_start) * 1000, 3, '.', '');
        $timing['read_ms'] = number_format(($time_read_end - $time_read_start) * 1000, 3, '.', '');
        $timing['total_ms'] = number_format(($time_total_end - $time_total_start) * 1000, 3, '.', '');
    tpl::addVar('db_timing', $timing);
    // Save content-only cache after render
    if (self::$enablePageCache) { \Ofey\Logan22\template\tpl::pageCacheBegin(self::pageCacheKey('weapons', ['type' => $type]), self::$pageCacheTtl, 'wiki', false, true); }
    tpl::displayPlugin('/wiki/tpl/weapons.html');
    }

    /**
     * AJAX endpoint: return next page of weapons (50 per page) flattened but grouped in response by grade.
     * URL: /wiki/items/weapons/data/{type}?page=N (1-based)
     * Response JSON: { page, per_page, total, total_pages, items_by_grade: {GRADE:[rows...]}}
     */
    public function weaponsData(string $type, ?int $page = null): void
    {
    if (!$this->ensureActiveJson()) return;
        header('Content-Type: application/json; charset=utf-8');
        // Try API cache first (type+page)
        $typeKeyRaw = strtoupper($type);
        $pageRaw = $page ?? (isset($_GET['page']) ? (int)$_GET['page'] : null);
        if ($pageRaw !== null && $pageRaw < 1) $pageRaw = 1;
        $cacheKey = self::pageCacheKey('weapons_data', ['type' => $typeKeyRaw, 'page' => (int)($pageRaw ?? 1)]);
        // IMPORTANT: для страниц > 1 сначала нужно узнать общее количество, иначе можем отдать закэшированную
        // "последнюю" страницу с дублирующимися предметами (когда total_pages = 1, а запрошена page=2).
        // Поэтому кеш пытаемся отдать ТОЛЬКО для первой страницы. Для остальных сначала пересчитываем.
        $tryServeCache = ($pageRaw === null || (int)$pageRaw <= 1);
        if ($tryServeCache && self::apiCacheTryServe($cacheKey, self::$apiCacheTtl)) {
            return;
        }
        try {
            $repo = new WeaponRepository();
        } catch (\Throwable $e) {
            echo json_encode(['error' => 'db_unavailable']);
            return;
        }
        $types = $repo->getWeaponTypes();
        $type = strtoupper($type);
        if (!in_array($type, $types, true)) {
            echo json_encode(['error' => 'bad_type']);
            return;
        }
    $perPage = 30; // должен совпадать с initialPerPage на странице
    $page = $pageRaw ?? (isset($_GET['page']) ? (int)$_GET['page'] : 1);
        if ($page < 1) $page = 1;
        $total = $repo->countWeaponsByType($type);
        $totalPages = $perPage > 0 ? (int)ceil($total / $perPage) : 1;
        if ($totalPages < 1) $totalPages = 1;
        $requestedPage = $page; // сохраняем запрошенный номер для ответа клиенту
        $rows = [];
        if ($requestedPage <= $totalPages) {
            $offset = ($requestedPage - 1) * $perPage;
            $rows = $repo->getWeaponsPage($type, $offset, $perPage);
        } else {
            // Запрошена страница за пределами диапазона — возвращаем пустой набор без дублирования предыдущих.
            // Это предотвратит повторное добавление уже существующих предметов на клиенте.
        }

        // Post-process rows similar to full page logic (stats, enchant, skills, price formatting)
        $stateLabels = StatLabels::all();
        foreach ($rows as &$row) {
            $mainCodes = ['pAtk', 'mAtk', 'critRate', 'pAtkSpd'];
            $mainBase = [];
            $mainEnchant = [];
            $statsOut = [];
            // Pre-resolve weapon icon
            $row['icon_path'] = client_icon::getIcon($row['icon'] ?? '', 'icon');
            if (!empty($row['for'])) {
                $decoded = json_decode($row['for'], true);
                if (is_array($decoded)) {
                    $baseValues = [];
                    $enchantValues = [];
                    foreach ($decoded as $entry) {
                        if (!isset($entry['stat'], $entry['type'], $entry['val'])) continue;
                        $stat = $entry['stat'];
                        $tag = $entry['type'];
                        $val = $entry['val'];
                        if ($tag === 'add' || $tag === 'set') $baseValues[$stat] = $val;
                        elseif ($tag === 'enchant' && (int)$val > 0) $enchantValues[$stat] = $val;
                    }
                    foreach ($baseValues as $code => $val) {
                        $label = $stateLabels[$code] ?? $code;
                        $suffix = isset($enchantValues[$code]) ? ' (+' . $enchantValues[$code] . ' за заточку)' : '';
                        $statsOut[] = ['code' => $code, 'label' => $label, 'value' => $val, 'extra' => $suffix];
                    }
                    foreach ($mainCodes as $code) {
                        if (isset($baseValues[$code])) $mainBase[$code] = $baseValues[$code];
                        if (isset($enchantValues[$code])) $mainEnchant[$code] = $enchantValues[$code];
                    }
                }
            }
            foreach ($mainCodes as $code) {
                if (isset($mainBase[$code])) $row[$code] = $mainBase[$code];
                if (isset($mainEnchant[$code])) $row[$code . '_enchant'] = $mainEnchant[$code];
            }
            $price = $row['price'] ?? '';
            $row['price_formatted'] = is_numeric($price) ? number_format((float)$price, 0, '.', ' ') : $price;
            $skillList = [];
            if (array_key_exists('item_skill', $row)) {
                $parsed = $repo->parseItemSkillRaw($row['item_skill']);
                foreach ($parsed as $s) {
                    $sid = (int)($s['skill_id'] ?? 0);
                    $lvl = max(1, (int)($s['skill_level'] ?? 1));
                    if ($sid <= 0) continue;
                    $meta = $repo->getSkillById($sid);
                    $entry = [
                        'id' => $sid,
                        'level' => $lvl,
                        'name' => $meta['name'] ?? ('Skill ' . $sid),
                        'icon' => $meta['icon'] ?? null,
                        'icon_path' => WeaponRepository::resolveSkillIcon($meta['icon'] ?? null),
                    ];
                    if ($meta && !empty($meta['effects'])) {
                        $lvlEffects = [];
                        foreach ($meta['effects'] as $eff) {
                            if (isset($eff['raw_values']) && is_array($eff['raw_values']) && $eff['raw_values']) {
                                $vals = $eff['raw_values'];
                                $idxLevel = $lvl - 1;
                                $chosen = $vals[$idxLevel] ?? end($vals);
                                $formatted = $this->formatSkillEffectValue($eff['tag'] ?? 'add', $eff['stat'] ?? '', $chosen);
                                $lvlEffects[] = ['stat' => $eff['stat'] ?? null, 'label' => $eff['label'] ?? ($eff['stat'] ?? ''), 'formatted' => $formatted, 'text' => $formatted . ' ' . ($eff['label'] ?? ($eff['stat'] ?? ''))];
                            } elseif (isset($eff['formatted'])) {
                                $lvlEffects[] = $eff;
                            }
                        }
                        if ($lvlEffects) {
                            $entry['effects'] = $lvlEffects;
                            $entry['effect_text'] = implode(', ', array_map(fn($e) => $e['text'] ?? ($e['formatted'] ?? ''), $lvlEffects));
                        }
                    }
                    $skillList[] = $entry;
                }
            }
            $row['skills'] = $skillList;
            $row['stats'] = $statsOut;
        }
        unset($row);

        // Group by grade for response
        $byGrade = [];
        foreach ($rows as $r) {
            $g = $r['_grade'] ?? 'NG';
            $byGrade[$g][] = $r;
        }
        $hasMore = $requestedPage < $totalPages;
        $payload = json_encode([
            'page' => $requestedPage,          // всегда возвращаем запрошенный номер
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_more' => $hasMore,
            'items_by_grade' => $byGrade,
        ], JSON_UNESCAPED_UNICODE);
    self::apiCacheSave($cacheKey, $payload);
    echo $payload;
    }

    /** Armor list page */
    public function armors(?string $bodypart = null): void
    {
        $this->ensureActiveHtml();
        $time_total_start = microtime(true);
        $time_db_open_start = microtime(true);
        $repo = new ArmorRepository();
        $time_db_open_end = microtime(true);
        $time_read_start = microtime(true);

        // Полный список слотов (не бижутерия)
        $all = $repo->getArmorBodyparts();
        $jewelryParts = $repo->getJewelryBodyparts();
        // Хелперы канонизации и разрешения варианта из БД
        $canonical = function (string $p): string { $u = strtoupper($p); if ($u === 'FULL_ARMOR') return 'FULLARMOR'; if ($u === 'HAIR_ALL') return 'HAIRALL'; return $u; };
        $resolveDbPart = function (string $p) use ($all, $canonical): string {
            $c = $canonical($p);
            $cands = [$c];
            if ($c === 'FULLARMOR') $cands[] = 'FULL_ARMOR';
            if ($c === 'HAIRALL') $cands[] = 'HAIR_ALL';
            foreach ($cands as $cand) if (in_array($cand, $all, true)) return $cand;
            return $c;
        };
        // Базовые части для этой страницы
        $coreSet = ['CHEST', 'LEGS', 'FULLARMOR', 'HEAD', 'GLOVES', 'FEET', 'SHIELD'];
        $coreParts = [];
        foreach ($all as $p) { if (in_array($canonical($p), $coreSet, true)) $coreParts[] = $p; }

        $origParam = $bodypart;
        $bodypart = $bodypart ? strtoupper($bodypart) : ($coreParts[0] ?? 'CHEST');
        $dbBodypart = $resolveDbPart($bodypart);
        // Если бижутерия — перенаправляем на соответствующую страницу
        if (in_array($dbBodypart, $jewelryParts, true)) {
            redirect::location('/wiki/items/jewelry/' . strtolower($dbBodypart));
            return;
        }
        // Если не входит в базовые — "остальная броня"
        if (!in_array($dbBodypart, $coreParts, true)) {
            $target = $origParam ? strtolower($origParam) : '';
            // Не добавляем "other" повторно и не добавляем пустой сегмент
            if ($target === '' || $target === 'other') {
                redirect::location('/wiki/items/armors/other');
            } else {
                redirect::location('/wiki/items/armors/other/' . $target);
            }
            return;
        }

        // Кэш страницы по части
    if (self::$enablePageCache && tpl::pageCacheTryServe(self::pageCacheKey('armors', ['bp' => $dbBodypart]), self::$pageCacheTtl, 'wiki', false, true)) {
            tpl::displayPlugin('/wiki/tpl/armors.html');
            return;
        }

        $grouped = $repo->getArmorsGroupedByCrystal($dbBodypart);
        $gradeInfo = self::getGradeInfo();
        $orderedGrouped = self::orderByGrade($grouped);
        $allLabels = self::getBodypartLabels();
        $allShort = self::getBodypartShortLabels();
        $labelKeys = array_unique(array_merge($coreParts, [$dbBodypart]));
        $bodypartLabels = self::filterMapByKeys($allLabels, $labelKeys);
        $bodypartShort = self::filterMapByKeys($allShort, $labelKeys);
        $stateLabels = StatLabels::all();
        $mainCodes = ['pDef', 'mDef'];
        foreach ($orderedGrouped as $grade => $items) {
            foreach ($items as $idx => $row) {
                $mainBase = [];
                $mainEnchant = [];
                $normalizedStats = [];
                if (!empty($row['for'])) {
                    $parsed = $repo->parseForField($row['for']);
                    if (is_array($parsed)) {
                        $mainBase['pDef'] = $parsed['pDef'] ?? null;
                        $mainBase['mDef'] = $parsed['mDef'] ?? null;
                        if (isset($parsed['pDef_enchant'])) $mainEnchant['pDef'] = $parsed['pDef_enchant'];
                        if (isset($parsed['mDef_enchant'])) $mainEnchant['mDef'] = $parsed['mDef_enchant'];
                        $normalizedStats = $parsed['stats'] ?? [];
                    }
                }
                foreach ($mainCodes as $code) {
                    $orderedGrouped[$grade][$idx][$code] = $mainBase[$code] ?? null;
                    if (isset($mainEnchant[$code])) $orderedGrouped[$grade][$idx][$code . '_enchant'] = $mainEnchant[$code];
                }
                $price = $row['price'] ?? '';
                $orderedGrouped[$grade][$idx]['price_formatted'] = is_numeric($price) ? number_format((float)$price, 0, '.', ' ') : $price;
                // Build skill list using robust parser (same as weapons)
                $skillList = [];
                if (array_key_exists('item_skill', $row)) {
                    $parsedSkills = $repo->parseItemSkillRaw($row['item_skill']);
                    foreach ($parsedSkills as $skill) {
                        $sid = (int)($skill['skill_id'] ?? 0);
                        $slevel = max(1, (int)($skill['skill_level'] ?? 1));
                        if ($sid <= 0) continue;
                        $meta = $repo->getSkillById($sid);
                        $entry = [
                            'id' => $sid,
                            'level' => $slevel,
                            'name' => $meta['name'] ?? ('Skill ' . $sid),
                            'icon' => $meta['icon'] ?? null,
                        ];
                        if ($meta && !empty($meta['effects'])) {
                            $lvlEffects = [];
                            foreach ($meta['effects'] as $eff) {
                                if (isset($eff['raw_values']) && is_array($eff['raw_values']) && $eff['raw_values']) {
                                    $vals = $eff['raw_values'];
                                    $idxLevel = $slevel - 1;
                                    $chosen = $vals[$idxLevel] ?? end($vals);
                                    $formatted = $this->formatSkillEffectValue($eff['tag'] ?? 'add', $eff['stat'] ?? '', $chosen);
                                    $lvlEffects[] = [
                                        'stat' => $eff['stat'] ?? null,
                                        'label' => $eff['label'] ?? ($eff['stat'] ?? ''),
                                        'formatted' => $formatted,
                                        'text' => $formatted . ' ' . ($eff['label'] ?? ($eff['stat'] ?? '')),
                                    ];
                                } elseif (isset($eff['formatted'])) {
                                    $lvlEffects[] = $eff;
                                }
                            }
                            if ($lvlEffects) {
                                $entry['effects'] = $lvlEffects;
                                $entry['effect_text'] = implode(', ', array_map(fn($e) => $e['text'] ?? ($e['formatted'] ?? ''), $lvlEffects));
                                $entry['formatted'] = array_map(fn($e) => $e['formatted'] ?? ($e['text'] ?? ''), $lvlEffects);
                            }
                        }
                        $skillList[] = $entry;
                    }
                }
                $orderedGrouped[$grade][$idx]['skills'] = $skillList;
                $orderedGrouped[$grade][$idx]['stats'] = $normalizedStats;
            }
        }
    tpl::addVar('armorBodyparts', $coreParts);
        tpl::addVar('armorBodypartLabels', $bodypartLabels);
        tpl::addVar('armorBodypartShort', $bodypartShort);
    tpl::addVar('currentArmorPart', $dbBodypart);
        tpl::addVar('itemType', 'armors');
        tpl::addVar('armorsByCrystal', $orderedGrouped);
        tpl::addVar('armorGradeInfo', $gradeInfo);
        tpl::addVar('armorStatLabels', $stateLabels);
        $time_read_end = microtime(true);
        $time_total_end = microtime(true);
        $timing = ['db_open_s' => number_format(($time_db_open_end - $time_db_open_start), 6, '.', ''), 'read_s' => number_format(($time_read_end - $time_read_start), 6, '.', ''), 'total_s' => number_format(($time_total_end - $time_total_start), 6, '.', '')];
        $timing['db_open_ms'] = number_format(($time_db_open_end - $time_db_open_start) * 1000, 3, '.', '');
        $timing['read_ms'] = number_format(($time_read_end - $time_read_start) * 1000, 3, '.', '');
        $timing['total_ms'] = number_format(($time_total_end - $time_total_start) * 1000, 3, '.', '');
    tpl::addVar('db_timing', $timing);
    // Save content cache
    if (self::$enablePageCache) { tpl::pageCacheBegin(self::pageCacheKey('armors', ['bp' => $dbBodypart]), self::$pageCacheTtl, 'wiki', false, true); }
        tpl::displayPlugin('/wiki/tpl/armors.html');
    }

    /** Остальная броня (все части, кроме базовых и бижутерии) */
    public function armorsOther(?string $bodypart = null): void
    {
        $this->ensureActiveHtml();
        $time_total_start = microtime(true);
        $time_db_open_start = microtime(true);
        $repo = new ArmorRepository();
        $time_db_open_end = microtime(true);
        $time_read_start = microtime(true);

        $all = $repo->getArmorBodyparts();
        $jewelryParts = $repo->getJewelryBodyparts();
        $canonical = function(string $p): string { $u = strtoupper($p); if ($u === 'FULL_ARMOR') return 'FULLARMOR'; if ($u === 'HAIR_ALL') return 'HAIRALL'; return $u; };
        $resolveDbPart = function(string $p) use ($all, $canonical): string {
            $c = $canonical($p);
            $cands = [$c];
            if ($c === 'FULLARMOR') $cands[] = 'FULL_ARMOR';
            if ($c === 'HAIRALL') $cands[] = 'HAIR_ALL';
            foreach ($cands as $cand) if (in_array($cand, $all, true)) return $cand;
            return $c;
        };
        $coreSet = ['CHEST','LEGS','FULLARMOR','HEAD','GLOVES','FEET','SHIELD'];
        // Исключаем базовые и бижутерию
        $parts = [];
        foreach ($all as $p) { if (!in_array($canonical($p), $coreSet, true) && !in_array($p, $jewelryParts, true)) $parts[] = $p; }
        // Поддержка алиасов из меню + выбор части
    // Пусто или явное "OTHER" -> просто берём первую доступную часть
    $bodypart = $bodypart ? strtoupper($bodypart) : ($parts[0] ?? 'BACK');
    if ($bodypart === 'OTHER') { $bodypart = $parts[0] ?? 'BACK'; }
        $dbBodypart = $resolveDbPart($bodypart);
        // Если передали бижутерию — перенаправим на jewelry
        if (in_array($dbBodypart, $jewelryParts, true)) {
            redirect::location('/wiki/items/jewelry/' . strtolower($dbBodypart));
            return;
        }
        if (!in_array($dbBodypart, $parts, true)) {
            $dbBodypart = $parts[0] ?? ($all[0] ?? 'BACK');
        }

        // Кэш по части
    if (self::$enablePageCache && tpl::pageCacheTryServe(self::pageCacheKey('armors_other', ['bp' => $dbBodypart]), self::$pageCacheTtl, 'wiki', false, true)) {
            tpl::displayPlugin('/wiki/tpl/armors.html');
            return;
        }

    $grouped = $repo->getArmorsGroupedByCrystal($dbBodypart);
        $gradeInfo = self::getGradeInfo();
        $orderedGrouped = self::orderByGrade($grouped);

        $allLabels = self::getBodypartLabels();
        $allShort = self::getBodypartShortLabels();
        $labelKeys = array_unique(array_merge($parts, [$dbBodypart]));
        $bodypartLabels = self::filterMapByKeys($allLabels, $labelKeys);
        $bodypartShort = self::filterMapByKeys($allShort, $labelKeys);

        $stateLabels = StatLabels::all();
        $mainCodes = ['pDef', 'mDef'];
        foreach ($orderedGrouped as $grade => $items) {
            foreach ($items as $idx => $row) {
                $mainBase = [];
                $mainEnchant = [];
                $normalizedStats = [];
                if (!empty($row['for'])) {
                    $parsed = $repo->parseForField($row['for']);
                    if (is_array($parsed)) {
                        $mainBase['pDef'] = $parsed['pDef'] ?? null;
                        $mainBase['mDef'] = $parsed['mDef'] ?? null;
                        if (isset($parsed['pDef_enchant'])) $mainEnchant['pDef'] = $parsed['pDef_enchant'];
                        if (isset($parsed['mDef_enchant'])) $mainEnchant['mDef'] = $parsed['mDef_enchant'];
                        $normalizedStats = $parsed['stats'] ?? [];
                    }
                }
                foreach ($mainCodes as $code) {
                    $orderedGrouped[$grade][$idx][$code] = $mainBase[$code] ?? null;
                    if (isset($mainEnchant[$code])) $orderedGrouped[$grade][$idx][$code . '_enchant'] = $mainEnchant[$code];
                }
                $price = $row['price'] ?? '';
                $orderedGrouped[$grade][$idx]['price_formatted'] = is_numeric($price) ? number_format((float)$price, 0, '.', ' ') : $price;
                $skillList = [];
                if (array_key_exists('item_skill', $row)) {
                    $parsedSkills = $repo->parseItemSkillRaw($row['item_skill']);
                    foreach ($parsedSkills as $skill) {
                        $sid = (int)($skill['skill_id'] ?? 0);
                        $slevel = max(1, (int)($skill['skill_level'] ?? 1));
                        if ($sid <= 0) continue;
                        $meta = $repo->getSkillById($sid);
                        $entry = [ 'id' => $sid, 'level' => $slevel, 'name' => $meta['name'] ?? ('Skill ' . $sid), 'icon' => $meta['icon'] ?? null ];
                        if ($meta && !empty($meta['effects'])) {
                            $lvlEffects = [];
                            foreach ($meta['effects'] as $eff) {
                                if (isset($eff['raw_values']) && is_array($eff['raw_values']) && $eff['raw_values']) {
                                    $vals = $eff['raw_values'];
                                    $idxLevel = $slevel - 1;
                                    $chosen = $vals[$idxLevel] ?? end($vals);
                                    $formatted = $this->formatSkillEffectValue($eff['tag'] ?? 'add', $eff['stat'] ?? '', $chosen);
                                    $lvlEffects[] = ['stat' => $eff['stat'] ?? null, 'label' => $eff['label'] ?? ($eff['stat'] ?? ''), 'formatted' => $formatted, 'text' => $formatted . ' ' . ($eff['label'] ?? ($eff['stat'] ?? ''))];
                                } elseif (isset($eff['formatted'])) {
                                    $lvlEffects[] = $eff;
                                }
                            }
                            if ($lvlEffects) {
                                $entry['effects'] = $lvlEffects;
                                $entry['effect_text'] = implode(', ', array_map(fn($e) => $e['text'] ?? ($e['formatted'] ?? ''), $lvlEffects));
                            }
                        }
                        $skillList[] = $entry;
                    }
                }
                $orderedGrouped[$grade][$idx]['skills'] = $skillList;
                $orderedGrouped[$grade][$idx]['stats'] = $normalizedStats;
            }
        }

        tpl::addVar('armorBodyparts', $parts);
        tpl::addVar('armorBodypartLabels', $bodypartLabels);
        tpl::addVar('armorBodypartShort', $bodypartShort);
    tpl::addVar('currentArmorPart', $dbBodypart);
        tpl::addVar('itemType', 'armors');
        tpl::addVar('armorsByCrystal', $orderedGrouped);
        tpl::addVar('armorGradeInfo', $gradeInfo);
        tpl::addVar('armorStatLabels', $stateLabels);
        $time_read_end = microtime(true);
        $time_total_end = microtime(true);
        $timing = [
            'db_open_s' => number_format(($time_db_open_end - $time_db_open_start), 6, '.', ''),
            'read_s' => number_format(($time_read_end - $time_read_start), 6, '.', ''),
            'total_s' => number_format(($time_total_end - $time_total_start), 6, '.', ''),
        ];
        $timing['db_open_ms'] = number_format(($time_db_open_end - $time_db_open_start) * 1000, 3, '.', '');
        $timing['read_ms'] = number_format(($time_read_end - $time_read_start) * 1000, 3, '.', '');
        $timing['total_ms'] = number_format(($time_total_end - $time_total_start) * 1000, 3, '.', '');
        tpl::addVar('db_timing', $timing);
    if (self::$enablePageCache) { tpl::pageCacheBegin(self::pageCacheKey('armors_other', ['bp' => $dbBodypart]), self::$pageCacheTtl, 'wiki', false, true); }
        tpl::displayPlugin('/wiki/tpl/armors.html');
    }

    /** Jewelry list page */
    public function jewelry(?string $filter = null): void
    {
        $this->ensureActiveHtml();
        $time_total_start = microtime(true);
        $time_db_open_start = microtime(true);
        $repo = new ArmorRepository();
        $time_db_open_end = microtime(true);
        $time_read_start = microtime(true);
        $parts = $repo->getArmorBodyparts();
        $availableJewelry = $repo->getJewelryBodyparts();
        $filter = $filter ? strtoupper($filter) : null;
    if (self::$enablePageCache && tpl::pageCacheTryServe(self::pageCacheKey('jewelry', ['filter' => $filter]), self::$pageCacheTtl, 'wiki', false, true)) {
            tpl::displayPlugin('/wiki/tpl/armors.html');
            return;
        }
        if ($filter && in_array($filter, $availableJewelry)) {
            $grouped = $repo->getArmorsGroupedByCrystal($filter);
        } else {
            $filter = $availableJewelry[0] ?? 'NECK';
            $grouped = $repo->getArmorsGroupedByCrystal($filter);
        } 

        $gradeInfo = self::getGradeInfo();
        $orderedGrouped = self::orderByGrade($grouped);
        $allLabels = self::getBodypartLabels();
        $allShort = self::getBodypartShortLabels();
        $labelKeys = array_unique(array_merge($availableJewelry, [$filter]));
        $bodypartLabels = self::filterMapByKeys($allLabels, $labelKeys);
        $bodypartShort = self::filterMapByKeys($allShort, $labelKeys);
        $stateLabels = StatLabels::all();
        $mainCodes = ['pDef', 'mDef'];
        foreach ($orderedGrouped as $grade => $items) {
            foreach ($items as $idx => $row) {
                $mainBase = [];
                $mainEnchant = [];
                $normalizedStats = [];
                if (!empty($row['for'])) {
                    $parsed = $repo->parseForField($row['for']);
                    if (is_array($parsed)) {
                        $mainBase['pDef'] = $parsed['pDef'] ?? null;
                        $mainBase['mDef'] = $parsed['mDef'] ?? null;
                        if (isset($parsed['pDef_enchant'])) $mainEnchant['pDef'] = $parsed['pDef_enchant'];
                        if (isset($parsed['mDef_enchant'])) $mainEnchant['mDef'] = $parsed['mDef_enchant'];
                        $normalizedStats = $parsed['stats'] ?? [];
                    }
                }
                foreach ($mainCodes as $code) {
                    $orderedGrouped[$grade][$idx][$code] = $mainBase[$code] ?? null;
                    if (isset($mainEnchant[$code])) $orderedGrouped[$grade][$idx][$code . '_enchant'] = $mainEnchant[$code];
                }
                $price = $row['price'] ?? '';
                $orderedGrouped[$grade][$idx]['price_formatted'] = is_numeric($price) ? number_format((float)$price, 0, '.', ' ') : $price;
                // Unified skill parsing for jewelry
                $skillList = [];
                if (array_key_exists('item_skill', $row)) {
                    $parsedSkills = $repo->parseItemSkillRaw($row['item_skill']);
                    foreach ($parsedSkills as $skill) {
                        $sid = (int)($skill['skill_id'] ?? 0);
                        $slevel = max(1, (int)($skill['skill_level'] ?? 1));
                        if ($sid <= 0) continue;
                        $meta = $repo->getSkillById($sid);
                        $entry = [
                            'id' => $sid,
                            'level' => $slevel,
                            'name' => $meta['name'] ?? ('Skill ' . $sid),
                            'icon' => $meta['icon'] ?? null,
                            'cooltime' => $meta['cooltime'] ?? $meta['reuse_delay'] ?? null,
                            'cast_range' => $meta['castrange'] ?? null,
                            'isdebuff' => $meta['isdebuff'] ?? null,
                        ];
                        if ($meta && !empty($meta['effects'])) {
                            $lvlEffects = [];
                            foreach ($meta['effects'] as $eff) {
                                if (isset($eff['raw_values']) && is_array($eff['raw_values']) && $eff['raw_values']) {
                                    $vals = $eff['raw_values'];
                                    $idxLevel = $slevel - 1;
                                    $chosen = $vals[$idxLevel] ?? end($vals);
                                    $formatted = $this->formatSkillEffectValue($eff['tag'] ?? 'add', $eff['stat'] ?? '', $chosen);
                                    $lvlEffects[] = [
                                        'stat' => $eff['stat'] ?? null,
                                        'label' => $eff['label'] ?? ($eff['stat'] ?? ''),
                                        'formatted' => $formatted,
                                        'text' => $formatted . ' ' . ($eff['label'] ?? ($eff['stat'] ?? '')),
                                    ];
                                } elseif (isset($eff['formatted'])) {
                                    $lvlEffects[] = $eff;
                                }
                            }
                            if ($lvlEffects) {
                                $entry['effects'] = $lvlEffects;
                                $entry['effect_text'] = implode(', ', array_map(fn($e) => $e['text'] ?? ($e['formatted'] ?? ''), $lvlEffects));
                            }
                        }
                        $skillList[] = $entry;
                    }
                }
                $orderedGrouped[$grade][$idx]['skills'] = $skillList;
                $orderedGrouped[$grade][$idx]['stats'] = $normalizedStats;
            }
        }
        tpl::addVar('armorBodyparts', $availableJewelry);
        tpl::addVar('armorBodypartLabels', $bodypartLabels);
    tpl::addVar('armorBodypartShort', $bodypartShort);
        tpl::addVar('currentArmorPart', $filter);
        tpl::addVar('itemType', 'jewelry');
        tpl::addVar('armorsByCrystal', $orderedGrouped);
        tpl::addVar('armorGradeInfo', $gradeInfo);
        tpl::addVar('armorStatLabels', $stateLabels);
        $time_read_end = microtime(true);
        $time_total_end = microtime(true);
        $timing = ['db_open_s' => number_format(($time_db_open_end - $time_db_open_start), 6, '.', ''), 'read_s' => number_format(($time_read_end - $time_read_start), 6, '.', ''), 'total_s' => number_format(($time_total_end - $time_total_start), 6, '.', '')];
        $timing['db_open_ms'] = number_format(($time_db_open_end - $time_db_open_start) * 1000, 3, '.', '');
        $timing['read_ms'] = number_format(($time_read_end - $time_read_start) * 1000, 3, '.', '');
        $timing['total_ms'] = number_format(($time_total_end - $time_total_start) * 1000, 3, '.', '');
    tpl::addVar('db_timing', $timing);
    // Save content cache
    if (self::$enablePageCache) { tpl::pageCacheBegin(self::pageCacheKey('jewelry', ['filter' => $filter]), self::$pageCacheTtl, 'wiki', false, true); }
        tpl::displayPlugin('/wiki/tpl/armors.html');
    }

    /** Recipes list */
    public function recipes(): void
    {
    $this->ensureActiveHtml();
        // Content-only cache for recipes list
    if (self::$enablePageCache && tpl::pageCacheTryServe(self::pageCacheKey('recipes'), self::$pageCacheTtl, 'wiki', false, true)) {
            tpl::displayPlugin('/wiki/tpl/recipes.html');
            return;
        }
        $time_total_start = microtime(true);
        $time_db_open_start = microtime(true);
        $repo = new RecipeRepository();
        $time_db_open_end = microtime(true);
        $time_read_start = microtime(true);
        $recipes = $repo->getAllRecipes();
        $time_read_end = microtime(true);
        $time_total_end = microtime(true);
        tpl::addVar('recipes', $recipes);
        $timing = ['db_open_s' => number_format(($time_db_open_end - $time_db_open_start), 6, '.', ''), 'read_s' => number_format(($time_read_end - $time_read_start), 6, '.', ''), 'total_s' => number_format(($time_total_end - $time_total_start), 6, '.', '')];
        $timing['db_open_ms'] = number_format(($time_db_open_end - $time_db_open_start) * 1000, 3, '.', '');
        $timing['read_ms'] = number_format(($time_read_end - $time_read_start) * 1000, 3, '.', '');
        $timing['total_ms'] = number_format(($time_total_end - $time_total_start) * 1000, 3, '.', '');
        tpl::addVar('db_timing', $timing);
        // Save content cache
    if (self::$enablePageCache) { tpl::pageCacheBegin(self::pageCacheKey('recipes'), self::$pageCacheTtl, 'wiki', false, true); }
        tpl::displayPlugin('/wiki/tpl/recipes.html');
    }

    /** Show recipes producing a specific item by product id */
    public function recipeByProduction(int $productId): void
    {
        $this->ensureActiveHtml();
        $productId = max(0, (int)$productId);
        // Cache per productId
    if (self::$enablePageCache && $productId > 0 && tpl::pageCacheTryServe(self::pageCacheKey('recipe_product', ['product' => $productId]), self::$pageCacheTtl, 'wiki', false, true)) {
            tpl::displayPlugin('/wiki/tpl/recipe_product.html');
            return;
        }
        $repo = new RecipeRepository();
        $list = $repo->getRecipesByProductionId($productId);
        // Try resolve product meta for header
        $meta = null;
    if ($productId > 0) {
            // Use minimal manual lookup similar to bulk load
            try {
                $db = WikiDb::get();
                $id = (int)$productId;
                $sql = "SELECT id,name,icon,crystal_type,'weapon' AS source FROM weapons WHERE id=$id
                        UNION ALL SELECT id,name,icon,crystal_type,'armor' AS source FROM armors WHERE id=$id
                        UNION ALL SELECT id,name,icon,NULL AS crystal_type,'etcitem' AS source FROM etcitems WHERE id=$id LIMIT 1";
                $res = $db->query($sql);
                $row = $res?->fetchArray(\SQLITE3_ASSOC);
                if ($row) {
                    $meta = [
                        'id' => (int)$row['id'],
                        'name' => $row['name'] ?? ('Item ' . $productId),
                        'icon' => $row['icon'] ?? null,
                        'icon_path' => client_icon::getIcon($row['icon'] ?? '', 'icon'),
                        'source' => $row['source'] ?? null,
                        'crystal_type' => $row['crystal_type'] ?? null,
                    ];
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }
        tpl::addVar('product_id', $productId);
        tpl::addVar('product_meta', $meta);
        tpl::addVar('recipes', $list);
        if ($productId > 0) {
            if (self::$enablePageCache) { tpl::pageCacheBegin(self::pageCacheKey('recipe_product', ['product' => $productId]), self::$pageCacheTtl, 'wiki', false, true); }
        }
        tpl::displayPlugin('/wiki/tpl/recipe_product.html');
    }

    /** Etc items list
     * @param string|null $type From pretty URL /type/{TYPE}
     * @param int|string|null $page From pretty URL /page/{PAGE}
     */
    public function etcitems(?string $type = null, int|string|null $page = null): void
    {
    $this->ensureActiveHtml();
        $time_total_start = microtime(true);
        $time_db_open_start = microtime(true);
        $repo = new EtcItemRepository();
        $time_db_open_end = microtime(true);

        // Types with counts (quest separated in repository). Move 'quest' to end for UX.
        $typesWithCounts = $repo->getTypesWithCounts();
        $availableTypes = array_keys($typesWithCounts);
        // Keep natural sort except ensure 'quest' last
        sort($availableTypes, SORT_NATURAL | SORT_FLAG_CASE);
        if (in_array('quest', $availableTypes, true)) {
            $availableTypes = array_values(array_filter($availableTypes, fn($t) => $t !== 'quest'));
            $availableTypes[] = 'quest';
        }

        // Selected type: if not provided -> default to 'other' (if exists) else first.
        // Priority: explicit param ($type) > query string ?type=
        $requestedType = $type ?? ($_GET['type'] ?? null);
        $defaultType = in_array('other', $availableTypes, true) ? 'other' : ($availableTypes[0] ?? '');
        if ($requestedType === null || $requestedType === '') {
            $currentType = $defaultType; // direct access behaves like type=other
        } else {
            // case-insensitive resolution of type
            $found = null;
            foreach ($availableTypes as $t) {
                if (strcasecmp($t, $requestedType) === 0) {
                    $found = $t;
                    break;
                }
            }
            $currentType = $found ?? $defaultType;
        }
        tpl::addVar('etc_default_type', $defaultType);

        // Pagination
        $perPage = 100; // fixed as requested
        // Priority: explicit param ($page) > query string ?page=
        $page = $page !== null ? (int)$page : (isset($_GET['page']) ? (int)$_GET['page'] : 1);
        if ($page < 1) $page = 1;
        if ($page < 1) $page = 1;

        // Try serve cached content for etcitems by type+page
    if (self::$enablePageCache && tpl::pageCacheTryServe(self::pageCacheKey('etcitems', ['type' => $currentType, 'page' => $page]), self::$pageCacheTtl, 'wiki', false, true)) {
            tpl::displayPlugin('/wiki/tpl/etcitems.html');
            return;
        }

        $time_read_start = microtime(true);
        $pageData = $repo->getItemsByType($currentType, $page, $perPage);
        $time_read_end = microtime(true);
        $total = $pageData['total'] ?? 0;
        $pages = $perPage > 0 ? (int)ceil($total / $perPage) : 1;
        if ($pages < 1) $pages = 1;
        if ($page > $pages) {
            // refetch last page if page out of range
            $page = $pages;
            $pageData = $repo->getItemsByType($currentType, $page, $perPage);
        }

        // Vars to template
        // Rebuild types array in the chosen order
        $sortedTypes = [];
        foreach ($availableTypes as $t) {
            if (isset($typesWithCounts[$t])) $sortedTypes[$t] = $typesWithCounts[$t];
        }
        tpl::addVar('etc_types', $sortedTypes);
        tpl::addVar('etc_has_quest', array_key_exists('quest', $sortedTypes));
        tpl::addVar('etc_current_type', $currentType);
        tpl::addVar('etc_items', $pageData['items'] ?? []);
        tpl::addVar('etc_quest_included', $pageData['quest_included'] ?? false);
        tpl::addVar('etc_page', $page);
        tpl::addVar('etc_pages', $pages);
        tpl::addVar('etc_total', $total);
        tpl::addVar('etc_per_page', $perPage);

        $time_total_end = microtime(true);
        $timing = [
            'db_open_s' => number_format(($time_db_open_end - $time_db_open_start), 6, '.', ''),
            'read_s' => number_format(($time_read_end - $time_read_start), 6, '.', ''),
            'total_s' => number_format(($time_total_end - $time_total_start), 6, '.', ''),
        ];
        $timing['db_open_ms'] = number_format(($time_db_open_end - $time_db_open_start) * 1000, 3, '.', '');
        $timing['read_ms'] = number_format(($time_read_end - $time_read_start) * 1000, 3, '.', '');
        $timing['total_ms'] = number_format(($time_total_end - $time_total_start) * 1000, 3, '.', '');
        tpl::addVar('db_timing', $timing);
    // Save content cache for etcitems page
    if (self::$enablePageCache) { tpl::pageCacheBegin(self::pageCacheKey('etcitems', ['type' => $currentType, 'page' => $page]), self::$pageCacheTtl, 'wiki', false, true); }
        tpl::displayPlugin('/wiki/tpl/etcitems.html');
    }

    /**
     * List of items that can DROP (group_id != 0) with search and pagination.
     * Page routes: /wiki/items/drop and /wiki/items/drop/page/{N}
     * Query: ?q=substring (by item name or id)
     */
    public function itemsDrop(?int $page = null, ?string $qParam = null): void
    {
        $this->ensureActiveHtml();
        // Support both pretty URL /search/{q} and legacy ?q=
        $qRaw = $qParam ?? (isset($_GET['q']) ? (string)$_GET['q'] : '');
        $q = trim($qRaw);
        $pageNum = $page ?? (isset($_GET['page']) ? (int)$_GET['page'] : 1);
        if ($pageNum < 1) $pageNum = 1;
        $perPage = 100;

        // Try page cache (content-only) per page+query
        $cacheKey = self::pageCacheKey('items_drop', ['page' => $pageNum, 'q' => $q]);
    if (self::$enablePageCache && \Ofey\Logan22\template\tpl::pageCacheTryServe($cacheKey, self::$pageCacheTtl, 'wiki', false, true)) {
            tpl::displayPlugin('/wiki/tpl/drop.html');
            return;
        }

        $res = $this->queryDistinctItemsFromDrops('drop', $q, $pageNum, $perPage);
        tpl::addVar('list_items', $res['items']);
        tpl::addVar('list_total', $res['total']);
        tpl::addVar('list_page', $pageNum);
        tpl::addVar('list_pages', $res['pages']);
        tpl::addVar('list_per_page', $perPage);
        tpl::addVar('list_q', $q);
        tpl::addVar('list_kind', 'drop');
    tpl::addVar('list_base', '/wiki/items/drop');
    tpl::addVar('search_base', '/wiki/items/drop/search');
        tpl::addVar('list_title', 'Дроп');

    if (self::$enablePageCache) { \Ofey\Logan22\template\tpl::pageCacheBegin($cacheKey, self::$pageCacheTtl, 'wiki', false, true); }
        tpl::displayPlugin('/wiki/tpl/drop.html');
    }

    /**
     * List of items that can SPOIL (group_id = 0) with search and pagination.
     * Page routes: /wiki/items/spoil and /wiki/items/spoil/page/{N}
     * Query: ?q=substring (by item name or id)
     */
    public function itemsSpoil(?int $page = null, ?string $qParam = null): void
    {
        $this->ensureActiveHtml();
        $qRaw = $qParam ?? (isset($_GET['q']) ? (string)$_GET['q'] : '');
        $q = trim($qRaw);
        $pageNum = $page ?? (isset($_GET['page']) ? (int)$_GET['page'] : 1);
        if ($pageNum < 1) $pageNum = 1;
        $perPage = 100;

        $cacheKey = self::pageCacheKey('items_spoil', ['page' => $pageNum, 'q' => $q]);
    if (self::$enablePageCache && \Ofey\Logan22\template\tpl::pageCacheTryServe($cacheKey, self::$pageCacheTtl, 'wiki', false, true)) {
            tpl::displayPlugin('/wiki/tpl/spoil.html');
            return;
        }

        $res = $this->queryDistinctItemsFromDrops('spoil', $q, $pageNum, $perPage);
        tpl::addVar('list_items', $res['items']);
        tpl::addVar('list_total', $res['total']);
        tpl::addVar('list_page', $pageNum);
        tpl::addVar('list_pages', $res['pages']);
        tpl::addVar('list_per_page', $perPage);
        tpl::addVar('list_q', $q);
        tpl::addVar('list_kind', 'spoil');
    tpl::addVar('list_base', '/wiki/items/spoil');
    tpl::addVar('search_base', '/wiki/items/spoil/search');
        tpl::addVar('list_title', 'Спойл');

    if (self::$enablePageCache) { \Ofey\Logan22\template\tpl::pageCacheBegin($cacheKey, self::$pageCacheTtl, 'wiki', false, true); }
        tpl::displayPlugin('/wiki/tpl/spoil.html');
    }

    /**
     * Helper to fetch distinct items from drops table with filters for drop/spoil and optional name/id search.
     * kind: 'drop' => group_id != 0 (exclude spoil), 'spoil' => group_id = 0
     * Returns array: ['items'=>[{'id','name','icon_path','link_sources'}], 'total'=>N, 'pages'=>M]
     */
    private function queryDistinctItemsFromDrops(string $kind, string $q, int $page, int $perPage): array
    {
        $out = ['items' => [], 'total' => 0, 'pages' => 1];
        try { $db = WikiDb::get(); } catch (\Throwable $e) { return $out; }

        $isSpoil = ($kind === 'spoil');
        $cond = $isSpoil ? 'd.group_id = 0' : '(d.group_id IS NULL OR d.group_id != 0)';

        // Unified items union for joining names/icons/grades
        $itemsCte = "WITH items AS (\n"
            . "  SELECT id, name, icon, crystal_type FROM weapons\n"
            . "  UNION ALL SELECT id, name, icon, crystal_type FROM armors\n"
            . "  UNION ALL SELECT id, name, icon, crystal_type FROM etcitems\n"
            . ")\n";

    $len = function(string $s){ return function_exists('mb_strlen') ? mb_strlen($s) : strlen($s); };
    $useName = ($q !== '' && $len($q) >= 3);
    $useId = ($q !== '' && ctype_digit($q));
    $hasSearch = ($useName || $useId);
    $qLike = '%' . $q . '%';
    $qId = $useId ? (int)$q : null;

        // Count total
        $whereParts = [];
        if ($useName) { $whereParts[] = 'i.name LIKE :q'; }
        if ($useId)   { $whereParts[] = 'dd.id = :qid'; }
        $whereSql = $whereParts ? ('  WHERE ' . implode(' OR ', $whereParts) . "\n") : '';
        $sqlCount = $itemsCte .
            "SELECT COUNT(1) AS cnt FROM (\n".
            "  SELECT dd.id FROM (SELECT d.item_id AS id FROM drops d WHERE $cond GROUP BY d.item_id) dd\n".
            "  LEFT JOIN items i ON i.id = dd.id\n".
            $whereSql .
            ") x";
        $stmtC = $db->prepare($sqlCount);
        if ($stmtC) {
            if ($useName) { $stmtC->bindValue(':q', $qLike, \SQLITE3_TEXT); }
            if ($useId)   { $stmtC->bindValue(':qid', (int)$qId, \SQLITE3_INTEGER); }
            $resC = $stmtC->execute();
            if ($resC) {
                $row = $resC->fetchArray(\SQLITE3_ASSOC);
                $total = (int)($row['cnt'] ?? 0);
                $out['total'] = $total;
                $pages = ($perPage > 0) ? max(1, (int)ceil($total / $perPage)) : 1;
                $out['pages'] = $pages;
                if ($page > $pages) $page = $pages;
            }
        }

        $offset = max(0, ($page - 1) * $perPage);
        // List page
        $sqlList = $itemsCte .
            "SELECT dd.id, COALESCE(i.name, '') AS name, i.icon, i.crystal_type\n".
            "FROM (SELECT d.item_id AS id FROM drops d WHERE $cond GROUP BY d.item_id) dd\n".
            "LEFT JOIN items i ON i.id = dd.id\n".
            $whereSql .
            "ORDER BY CASE WHEN i.name IS NULL OR i.name='' THEN 1 ELSE 0 END, i.name COLLATE NOCASE, dd.id\n".
            "LIMIT :lim OFFSET :off";
        $stmtL = $db->prepare($sqlList);
        if ($stmtL) {
            if ($useName) { $stmtL->bindValue(':q', $qLike, \SQLITE3_TEXT); }
            if ($useId)   { $stmtL->bindValue(':qid', (int)$qId, \SQLITE3_INTEGER); }
            $stmtL->bindValue(':lim', $perPage, \SQLITE3_INTEGER);
            $stmtL->bindValue(':off', $offset, \SQLITE3_INTEGER);
            $resL = $stmtL->execute();
            $rows = [];
            if ($resL) {
                while ($r = $resL->fetchArray(\SQLITE3_ASSOC)) {
                    $rows[] = $r;
                }
            }
            // Post-process rows: resolve icon path and build link
            foreach ($rows as $r) {
                $iid = (int)($r['id'] ?? 0);
                if ($iid <= 0) continue;
                $name = isset($r['name']) && $r['name'] !== '' ? (string)$r['name'] : ('Item ' . $iid);
                $iconRaw = (string)($r['icon'] ?? '');
                $out['items'][] = [
                    'id' => $iid,
                    'name' => $name,
                    'icon_path' => client_icon::getIcon($iconRaw, 'icon'),
                    'crystal_type' => $r['crystal_type'] ?? null,
                    'link_sources' => '/wiki/items/sources/' . $iid . '/' . ($isSpoil ? 'spoil' : 'drop'),
                ];
            }
        }
        return $out;
    }

    /** NPC index */
    public function npcs(): void
    {
    $this->ensureActiveHtml();
    if (tpl::pageCacheTryServe(self::pageCacheKey('npcs'), self::$pageCacheTtl, 'wiki', false, true)) {
            tpl::displayPlugin('/wiki/tpl/npcs.html');
            return;
        }
        tpl::addVar('npc_data_url', '/wiki/npcs/data');
    tpl::pageCacheBegin(self::pageCacheKey('npcs'), self::$pageCacheTtl, 'wiki', false, true);
        tpl::displayPlugin('/wiki/tpl/npcs.html');
    }

    public function npcsMonsters(?string $range = null): void
    {
    $this->ensureActiveHtml();
        // Диапазоны уровней: 1-10,11-20,...,71-80,81+
        $allowedRanges = ['1-10', '11-20', '21-30', '31-40', '41-50', '51-60', '61-70', '71-80', '81+'];
        if ($range === null || $range === '' || !in_array($range, $allowedRanges, true)) {
            $range = '1-10';
        }

    $repo = new NpcRepository();
    // Use case-insensitive LIKE to match various Monster type strings (e.g., 'Monster', 'L2Monster')
    $types = ['%MONSTER%'];
        // Check if there are any monsters with level >= 81 to decide whether to show the 81+ shortcut
        try {
            // Align with list visibility rules (no empty names, exclude specific titles like 'Raid Fighter')
            $has81plus = $repo->countVisibleWithTypeAndLevel($types, false, 81, null) > 0;
        } catch (\Throwable $e) { $has81plus = false; }
        $min = null;
        $max = null;
        if (preg_match('/^(\d+)-(\d+)$/', $range, $m)) {
            $min = (int)$m[1];
            $max = (int)$m[2];
        } elseif (preg_match('/^(\d+)\+$/', $range, $m2)) {
            $min = (int)$m2[1];
            $max = null; 
        }

        $perPage = 50; 
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        // Try cached listing by range+page
    if (tpl::pageCacheTryServe(self::pageCacheKey('npcs_monsters', ['range' => $range, 'page' => $page, 'has81' => (int)$has81plus]), self::$pageCacheTtl, 'wiki', false, true)) {
            tpl::displayPlugin('/wiki/tpl/npcs_monsters.html');
            return;
        }
        $offset = ($page - 1) * $perPage;
        $total = $repo->countFilteredWithTypeAndLevel($types, false, null, $min, $max);
        $pages = (int)ceil($total / $perPage);
        if ($page > $pages && $pages > 0) {
            $page = $pages;
            $offset = ($page - 1) * $perPage;
        }
        $list = $repo->getPageWithTypeAndLevel($offset, $perPage, null, 'level', 'ASC', $types, false, $min, $max);
        // Remove NPCs that should not be displayed:
        //  - name is empty
        //  - title == "Raid Fighter"
        $list = array_values(array_filter($list, function ($npc) {
            if (!is_array($npc)) return false;
            $name = trim((string)($npc['name'] ?? ''));
            $title = $npc['title'] ?? '';
            if ($name === '') return false;
            if ($title === 'Raid Fighter') return false;
            return true;
        }));

        // For each NPC, try to locate locally cached images; if missing, request from Sphere API and download.
        foreach ($list as &$npc) {
            $npcImages = [];
            $npcId = (int)($npc['id'] ?? 0);
            if ($npcId <= 0) continue;
            $rangeStart = floor($npcId / 1000) * 1000;
            $rangeEnd = $rangeStart + 999;
            $rangeDir = "uploads/images/npc/{$rangeStart}_{$rangeEnd}/{$npcId}";
            $fullRangeDir = fileSys::get_dir($rangeDir);

            if (is_dir($fullRangeDir)) {
                $files = scandir($fullRangeDir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..' && is_file($fullRangeDir . '/' . $file)) {
                        $npcImages[] = "/{$rangeDir}/{$file}";
                    }
                }
            }

            if (empty($npcImages)) {
                $npcImgRequest = \Ofey\Logan22\component\sphere\server::send(type::GET_NPC_IMG, [
                    'npcid' => (string)$npcId,
                ])->show()->getResponse();
                if (!empty($npcImgRequest) && is_array($npcImgRequest)) {
                    $imagesList = $npcImgRequest;
                    if (isset($npcImgRequest['images']) && is_array($npcImgRequest['images'])) {
                        $imagesList = $npcImgRequest['images'];
                    }
                    foreach ($imagesList as $img) {
                        $imgStr = null;
                        if (is_string($img)) {
                            $imgStr = $img;
                        } elseif (is_array($img) && isset($img['img']) && is_string($img['img'])) {
                            $imgStr = $img['img'];
                        } else {
                            continue;
                        }

                        $encoded = str_replace('\\', '/', ltrim($imgStr, '\\/'));
                        $path = '/api/npc/image/' . urlencode($encoded);
                        $res = \Ofey\Logan22\component\sphere\server::sendCustomDownload($path);

                        if (empty($res) || empty($res['content']) || ($res['http_code'] ?? 0) !== 200) {
                            continue;
                        }

                        $savePath = fileSys::get_dir("uploads/images/") . $encoded;
                        $saveDir = dirname($savePath);
                        if (!is_dir($saveDir)) {
                            @mkdir($saveDir, 0755, true);
                        }

                        try {
                            @file_put_contents($savePath, $res['content']);
                        } catch (\Throwable $e) {
                            // ignore write errors
                        }
                    }

                    // re-scan after attempted download
                    if (is_dir($fullRangeDir)) {
                        $files = scandir($fullRangeDir);
                        foreach ($files as $file) {
                            if ($file !== '.' && $file !== '..' && is_file($fullRangeDir . '/' . $file)) {
                                $npcImages[] = "/{$rangeDir}/{$file}";
                            }
                        }
                    }
                }
            }

            if (!empty($npcImages)) {
                $npc['image'] = $npcImages[0];
                $npc['images'] = $npcImages;
            }
        }
        unset($npc);

    tpl::addVar('npc_list', $list);
        tpl::addVar('selected_range', $range);
        tpl::addVar('page', $page);
        tpl::addVar('pages', $pages);
        tpl::addVar('per_page', $perPage);
        tpl::addVar('total', $total);
    tpl::addVar('has81plus', $has81plus);
    // Save cache
    tpl::pageCacheBegin(self::pageCacheKey('npcs_monsters', ['range' => $range, 'page' => $page, 'has81' => (int)$has81plus]), self::$pageCacheTtl, 'wiki', false, true);
    tpl::displayPlugin('/wiki/tpl/npcs_monsters.html');
    }

    public function npcsRaidboses(?string $range = null): void
    {
    $this->ensureActiveHtml();
        // Диапазоны уровней: 20-30,31-40,41-50,51-60,61-70,71-80,81+
        $allowedRanges = ['20-30', '31-40', '41-50', '51-60', '61-70', '71-80', '81+'];
        if ($range === null || $range === '' || !in_array($range, $allowedRanges, true)) {
            $range = '20-30';
        }

        $repo = new NpcRepository();
        $types = ['RaidBoss'];
        $ignoredIds = [29001, 29002, 29003, 29004, 29005, 29006, 29022];
        try {
            $has81plus = $repo->countFilteredWithTypeAndLevel($types, false, null, 81, null, ignoreIds: $ignoredIds) > 0;
        } catch (\Throwable $e) { $has81plus = false; }
        $min = null;
        $max = null;
        if (preg_match('/^(\d+)-(\d+)$/', $range, $m)) {
            $min = (int)$m[1];
            $max = (int)$m[2];
        } elseif (preg_match('/^(\d+)\+$/', $range, $m2)) {
            $min = (int)$m2[1];
            $max = null;
        }

        $perPage = 50; // pagination size like monsters
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $offset = ($page - 1) * $perPage;
        // Try cached content for bosses listing by range+page
    if (tpl::pageCacheTryServe(self::pageCacheKey('npcs_raidboses', ['range' => $range, 'page' => $page, 'has81' => (int)$has81plus]), self::$pageCacheTtl, 'wiki', false, true)) {
            tpl::displayPlugin('/wiki/tpl/npcs_raidboses.html');
            return;
        }

        $total = $repo->countFilteredWithTypeAndLevel($types, false, null, $min, $max, ignoreIds: $ignoredIds);
        $pages = (int)ceil($total / $perPage);
        if ($page > $pages && $pages > 0) {
            $page = $pages;
            $offset = ($page - 1) * $perPage;
        }

        $list = $repo->getPageWithTypeAndLevel($offset, $perPage, null, 'level', 'ASC', $types, false, $min, $max, ignoreIds: $ignoredIds);

        // For raidbosses, try to attach local/downloaded images same as monsters page
        foreach ($list as &$npc) {
            $npcImages = [];
            $npcId = (int)($npc['id'] ?? 0);
            if ($npcId <= 0) continue;
            $rangeStart = floor($npcId / 1000) * 1000;
            $rangeEnd = $rangeStart + 999;
            $rangeDir = "uploads/images/npc/{$rangeStart}_{$rangeEnd}/{$npcId}";
            $fullRangeDir = fileSys::get_dir($rangeDir);

            if (is_dir($fullRangeDir)) {
                $files = scandir($fullRangeDir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..' && is_file($fullRangeDir . '/' . $file)) {
                        $npcImages[] = "/{$rangeDir}/{$file}";
                    }
                }
            }

            if (empty($npcImages)) {
                $npcImgRequest = \Ofey\Logan22\component\sphere\server::send(type::GET_NPC_IMG, [
                    'npcid' => (string)$npcId,
                ])->show()->getResponse();
                if (!empty($npcImgRequest) && is_array($npcImgRequest)) {
                    $imagesList = $npcImgRequest;
                    if (isset($npcImgRequest['images']) && is_array($npcImgRequest['images'])) {
                        $imagesList = $npcImgRequest['images'];
                    }
                    foreach ($imagesList as $img) {
                        $imgStr = null;
                        if (is_string($img)) {
                            $imgStr = $img;
                        } elseif (is_array($img) && isset($img['img']) && is_string($img['img'])) {
                            $imgStr = $img['img'];
                        } else {
                            continue;
                        }

                        $encoded = str_replace('\\', '/', ltrim($imgStr, '\\//'));
                        $path = '/api/npc/image/' . urlencode($encoded);
                        $res = \Ofey\Logan22\component\sphere\server::sendCustomDownload($path);

                        if (empty($res) || empty($res['content']) || ($res['http_code'] ?? 0) !== 200) {
                            continue;
                        }

                        $savePath = fileSys::get_dir("uploads/images/") . $encoded;
                        $saveDir = dirname($savePath);
                        if (!is_dir($saveDir)) {
                            @mkdir($saveDir, 0755, true);
                        }

                        try {
                            @file_put_contents($savePath, $res['content']);
                        } catch (\Throwable $e) {
                            // ignore write errors
                        }
                    }

                    // re-scan after attempted download
                    if (is_dir($fullRangeDir)) {
                        $files = scandir($fullRangeDir);
                        foreach ($files as $file) {
                            if ($file !== '.' && $file !== '..' && is_file($fullRangeDir . '/' . $file)) {
                                $npcImages[] = "/{$rangeDir}/{$file}";
                            }
                        }
                    }
                }
            }

            if (!empty($npcImages)) {
                $npc['image'] = $npcImages[0];
                $npc['images'] = $npcImages;
            }
        }
        unset($npc);

    tpl::addVar('npc_list', $list);
        tpl::addVar('selected_range', $range);
        tpl::addVar('page', $page);
        tpl::addVar('pages', $pages);
        tpl::addVar('per_page', $perPage);
        tpl::addVar('total', $total);
    tpl::addVar('has81plus', $has81plus);
    // Save cache
    tpl::pageCacheBegin(self::pageCacheKey('npcs_raidboses', ['range' => $range, 'page' => $page, 'has81' => (int)$has81plus]), self::$pageCacheTtl, 'wiki', false, true);
    tpl::displayPlugin('/wiki/tpl/npcs_raidboses.html');
    }
    
    /** Epic raid bosses (types: boss, GrandBoss). Ignore is_spawn flag. Do not sort by level; show all. */
    public function npcsEpicbosses(?string $range = null): void
    {
        $this->ensureActiveHtml();
        $repo = new NpcRepository();
        // Epic bosses stored as types: boss, GrandBoss (case-insensitive). Keep array as requested.
        $types = ['boss', 'GrandBoss', 'QueenAnt', 'Orfen', 'Zaken', 'Antharas', 'Valakas', 'Frintezza'];
        // Cache the entire page (no range/pagination)
        if (tpl::pageCacheTryServe(self::pageCacheKey('npcs_epicbosses_all'), self::$pageCacheTtl, 'wiki', false, true)) {
            tpl::displayPlugin('/wiki/tpl/npcs_epicbosses.html');
            return;
        }

        $includeIds = [29022]; 
        $list = $repo->getAllWithType($types, false, null, 'name', 'ASC', null, null, includeIds: $includeIds);

        // Attach images like monsters/raid bosses pages
        foreach ($list as &$npc) {
            $npcImages = [];
            $npcId = (int)($npc['id'] ?? 0);
            if ($npcId <= 0) continue;
            $rangeStart = floor($npcId / 1000) * 1000;
            $rangeEnd = $rangeStart + 999;
            $rangeDir = "uploads/images/npc/{$rangeStart}_{$rangeEnd}/{$npcId}";
            $fullRangeDir = fileSys::get_dir($rangeDir);

            if (is_dir($fullRangeDir)) {
                $files = scandir($fullRangeDir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..' && is_file($fullRangeDir . '/' . $file)) {
                        $npcImages[] = "/{$rangeDir}/{$file}";
                    }
                }
            }

            if (empty($npcImages)) {
                $npcImgRequest = \Ofey\Logan22\component\sphere\server::send(type::GET_NPC_IMG, [
                    'npcid' => (string)$npcId,
                ])->show()->getResponse();
                if (!empty($npcImgRequest) && is_array($npcImgRequest)) {
                    $imagesList = $npcImgRequest;
                    if (isset($npcImgRequest['images']) && is_array($npcImgRequest['images'])) {
                        $imagesList = $npcImgRequest['images'];
                    }
                    foreach ($imagesList as $img) {
                        $imgStr = null;
                        if (is_string($img)) {
                            $imgStr = $img;
                        } elseif (is_array($img) && isset($img['img']) && is_string($img['img'])) {
                            $imgStr = $img['img'];
                        } else {
                            continue;
                        }

                        $encoded = str_replace('\\', '/', ltrim($imgStr, '\\/'));
                        $path = '/api/npc/image/' . urlencode($encoded);
                        $res = \Ofey\Logan22\component\sphere\server::sendCustomDownload($path);

                        if (empty($res) || empty($res['content']) || ($res['http_code'] ?? 0) !== 200) {
                            continue;
                        }

                        $savePath = fileSys::get_dir('uploads/images/') . $encoded;
                        $saveDir = dirname($savePath);
                        if (!is_dir($saveDir)) { @mkdir($saveDir, 0755, true); }
                        try { @file_put_contents($savePath, $res['content']); } catch (\Throwable $e) {}
                    }

                    // re-scan after attempted download
                    if (is_dir($fullRangeDir)) {
                        $files = scandir($fullRangeDir);
                        foreach ($files as $file) {
                            if ($file !== '.' && $file !== '..' && is_file($fullRangeDir . '/' . $file)) {
                                $npcImages[] = "/{$rangeDir}/{$file}";
                            }
                        }
                    }
                }
            }

            if (!empty($npcImages)) {
                $npc['image'] = $npcImages[0];
                $npc['images'] = $npcImages;
            }
        }
        unset($npc);

        tpl::addVar('npc_list', $list);
    // Save cache
    tpl::pageCacheBegin(self::pageCacheKey('npcs_epicbosses_all'), self::$pageCacheTtl, 'wiki', false, true);
        tpl::displayPlugin('/wiki/tpl/npcs_epicbosses.html');
    }

    public function npcsOther(): void
    {
    $this->ensureActiveHtml();
    if (tpl::pageCacheTryServe(self::pageCacheKey('npcs_other'), self::$pageCacheTtl, 'wiki', false, true)) {
            tpl::displayPlugin('/wiki/tpl/npcs_other.html');
            return;
        }
        $repo = new NpcRepository();
        // Strict matching for types; exclude monsters and bosses without using LIKE patterns
        $types = ['Monster', 'RaidBoss', 'GrandBoss'];
        $list = $repo->getAllWithType($types, true, null, 'level', 'ASC');
        tpl::addVar('npc_list', $list);
    tpl::pageCacheBegin(self::pageCacheKey('npcs_other'), self::$pageCacheTtl, 'wiki', false, true);
        tpl::displayPlugin('/wiki/tpl/npcs_other.html');
    }
    /** Generic NPC type page */
    public function npcsType(string $type): void
    {
    $this->ensureActiveHtml();
        // Allow only predefined list of types to avoid arbitrary queries / injection.
        $allowed = [
            'Folk',
            'Monster',
            'Pet',
            'Warehouse',
            'Teleporter',
            'BabyPet',
            'ControlTower',
            'FlameTower',
            'EffectPoint',
            'Chest',
            'Decoy',
            'Merchant',
            'Servitor',
            'TamedBeast',
            'FestivalMonster',
            'UCTower',
            'Block',
            'TerrainObject',
            'QuestGuard',
            'FeedableBeast',
            'RiftInvader',
            'RaidBoss',
            'GrandBoss',
            'Trainer',
            'VillageMasterFighter',
            'VillageMasterPriest',
            'Guard',
            'VillageMasterMystic',
            'VillageMasterDElf',
            'VillageMasterDwarf',
            'VillageMasterOrc',
            'CastleDoorman',
            'PetManager',
            'Auctioneer',
            'ClanHallDoorman',
            'RaceManager',
            'BroadcastingTower',
            'DawnPriest',
            'DuskPriest',
            'SignsPriest',
            'DungeonGatekeeper',
            'FestivalGuide',
            'Fisherman',
            'Doorman',
            'OlympiadManager',
            'Adventurer',
            'ClassMaster',
            'FriendlyMob',
            'FlyTerrainObject',
            'VillageMasterKamael',
            'UCManager',
            'UCHelper',
            'KrateisCubeManager',
            'KrateisMatchManager',
            'Defender',
            'Artefact',
            'ClanHallManager',
            'FortManager',
            'FortLogistics',
            'FortDoorman',
            'FortCommander',
            'TerritoryWard'
        ];
        if (!in_array($type, $allowed, true)) {
            // Simple 404 handling inside plugin context
            header('HTTP/1.1 404 Not Found');
            tpl::addVar('npc_type', htmlspecialchars($type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
            tpl::addVar('npc_list', []);
            tpl::displayPlugin('/wiki/tpl/npcs_type.html');
            return;
        }

        // Если тип monster, перенаправляем на спец. страницу
        if ($type === 'Monster') {
            header('Location: /wiki/npcs/monsters', 302);
            exit;
        }

        // Cache by type for valid types
    if (tpl::pageCacheTryServe(self::pageCacheKey('npcs_type', ['type' => $type]), self::$pageCacheTtl, 'wiki', false, true)) {
            tpl::displayPlugin('/wiki/tpl/npcs_type.html');
            return;
        }
        $repo = new NpcRepository();
        $list = $repo->getAllWithType([$type], false, null, 'level', 'ASC');
        tpl::addVar('npc_type', $type);
        tpl::addVar('npc_list', $list);
    tpl::pageCacheBegin(self::pageCacheKey('npcs_type', ['type' => $type]), self::$pageCacheTtl, 'wiki', false, true);
        tpl::displayPlugin('/wiki/tpl/npcs_type.html');
    }

    public function npcsData(?string $filter = null): void
    {
    if (!$this->ensureActiveJson()) return;
        $repo = new NpcRepository();
        $draw = $_GET['draw'] ?? 0;
        $start = $_GET['start'] ?? 0;
        $length = $_GET['length'] ?? 25;
        $search = $_GET['search']['value'] ?? null;
        $orderColIdx = $_GET['order'][0]['column'] ?? 2;
        $orderDir = $_GET['order'][0]['dir'] ?? 'asc';
    // NPC columns limited to reduced schema
    $columns = ['id', 'name', 'level', 'type', 'hp', 'mp', 'attack_physical', 'attack_magical', 'defence_physical', 'defence_magical', 'attack_attack_speed', 'attack_magic_speed', 'attack_range', 'attack_critical', 'skills'];
        $orderCol = $columns[$orderColIdx] ?? 'level';
        $types = null;
        $exclude = false;
        if ($filter === 'monsters') $types = ['Monster'];
    elseif ($filter === 'raidboses') $types = ['%BOSS%'];
        elseif ($filter === 'other') {
            $types = ['Monster', 'RaidBoss', 'GrandBoss'];
            $exclude = true;
        }
        $total = $repo->countAll();
        if ($types === null) {
            $filtered = $repo->countFiltered($search);
            $rows = $repo->getPage($start, $length, $search, $orderCol, $orderDir);
        } else {
            $filtered = $repo->countFilteredWithType($search, $types, $exclude);
            $rows = $repo->getPageWithType($start, $length, $search, $orderCol, $orderDir, $types, $exclude);
        }
        $data = [];
        foreach ($rows as $r) {
            $skillsHtml = '';
            if (!empty($r['skills'])) {
                $parts = [];
                foreach ($r['skills'] as $s) {
                    $icon = isset($s['icon']) ? '<img src="/uploads/icons/' . htmlspecialchars($s['icon']) . '.png" style="width:16px;height:16px;object-fit:contain;"> ' : '';
                    $title = htmlspecialchars(($s['name'] ?? 'Skill') . ' Lv ' . ($s['level'] ?? '?'));
                    $parts[] = '<span class="badge bg-light text-dark border" title="' . $title . '">' . $icon . htmlspecialchars((string)($s['level'] ?? '')) . '</span>';
                }
                $skillsHtml = '<div class="d-flex flex-wrap gap-1">' . implode(' ', $parts) . '</div>';
            } else {
                $skillsHtml = '<span class="text-muted">—</span>';
            }
            $npcId = (int)($r['id'] ?? 0);
            $link = $npcId > 0 ? '/wiki/npc/id/' . $npcId : '#';
            $nameHtml = '<div class="fw-semibold"><a href="' . $link . '" class="text-decoration-none">' . htmlspecialchars($r['name'] ?? '') . '</a></div>';
            if (!empty($r['title'])) $nameHtml .= '<div class="small text-muted">' . htmlspecialchars($r['title']) . '</div>';
            $data[] = [
                (string)($r['id'] ?? ''),
                $nameHtml,
                htmlspecialchars($r['level'] ?? ''),
                htmlspecialchars($r['type'] ?? ''),
                htmlspecialchars($r['hp'] ?? ''),
                htmlspecialchars($r['mp'] ?? ''),
                htmlspecialchars($r['attack_physical'] ?? ''),
                htmlspecialchars($r['attack_magical'] ?? ''),
                htmlspecialchars($r['defence_physical'] ?? ''),
                htmlspecialchars($r['defence_magical'] ?? ''),
                htmlspecialchars($r['attack_attack_speed'] ?? ''),
                htmlspecialchars($r['attack_magic_speed'] ?? ''),
                htmlspecialchars($r['attack_range'] ?? ''),
                htmlspecialchars($r['attack_critical'] ?? ''),
                $skillsHtml
            ];
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['draw' => (int)$draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Simple search endpoint returning list of items (weapons, armors, etcitems) matching name.
     * URL: /wiki/items/search?q=... (GET)
     * Honors the same npcSearchMinLen setting for simplicity.
     * Returns JSON: { items: [ { id, name, image, type } ] }
     */
    public function itemsSearch(): void
    {
        if (!$this->ensureActiveJson()) return;
        header('Content-Type: application/json; charset=utf-8');
        $q = trim((string)($_GET['q'] ?? ''));
        // Reuse minimal length from settings (npcSearchMinLen)
        try { $setting = plugin::getSetting('wiki'); } catch (\Throwable $e) { $setting = []; }
        $minLen = isset($setting['npcSearchMinLen']) ? (int)$setting['npcSearchMinLen'] : 4;
        if ($minLen < 1) $minLen = 1;
        if ($q === '' || mb_strlen($q) < $minLen) {
            echo json_encode(['error' => 'too_short', 'hint' => 'min_' . $minLen . '_chars']);
            return;
        }
        try {
            $db = WikiDb::get();
        } catch (\Throwable $e) {
            echo json_encode(['error' => 'db_unavailable']);
            return;
        }

        $max = 200;
        $items = [];
        try {
            // Query each table separately to preserve type and icon
            $like = '%' . $q . '%';

            // Helper to render grade image HTML (mirrors Twig grade_img)
            $gradeImg = function($crystal) {
                $dir = "/uploads/images/grade";
                $t = $crystal === null ? '' : strtolower((string)$crystal);
                return match ($t) {
                    'd', '1'   => "<img src='{$dir}/d.png' style='width:20px'>",
                    'c', '2'   => "<img src='{$dir}/c.png' style='width:20px'>",
                    'b', '3'   => "<img src='{$dir}/b.png' style='width:20px'>",
                    'a', '4'   => "<img src='{$dir}/a.png' style='width:20px'>",
                    's', '5'   => "<img src='{$dir}/s.png' style='width:20px'>",
                    's80', '6' => "<img src='{$dir}/s80.png' style='width:40px'>",
                    's84', '7' => "<img src='{$dir}/s84.png' style='width:40px'>",
                    'r', '8'   => "<img src='{$dir}/r.png' style='width:20px'>",
                    'r95', '9' => "<img src='{$dir}/r95.png' style='width:40px'>",
                    'r99','10' => "<img src='{$dir}/r99.png' style='width:40px'>",
                    'r110','11'=> "<img src='{$dir}/r110.png' style='width:40px'>",
                    default     => ''
                };
            };

            // weapons
            $sqlW = 'SELECT id, name, icon, crystal_type FROM weapons WHERE name LIKE :s ORDER BY name LIMIT :max';
            if ($st = $db->prepare($sqlW)) {
                $st->bindValue(':s', $like, \SQLITE3_TEXT);
                $st->bindValue(':max', $max, \SQLITE3_INTEGER);
                $rs = $st->execute();
                while ($row = $rs->fetchArray(\SQLITE3_ASSOC)) {
                    $cr = $row['crystal_type'] ?? null;
                    $items[] = [
                        'id' => (int)$row['id'],
                        'name' => (string)$row['name'],
                        'type' => 'weapon',
                        'image' => \Ofey\Logan22\component\image\client_icon::getIcon((string)($row['icon'] ?? ''), 'icon'),
                        'crystal_type' => $cr,
                        'grade_html' => $gradeImg($cr),
                    ];
                }
            }
            // armors (includes jewelry rows by bodypart)
            $sqlA = 'SELECT id, name, icon, crystal_type FROM armors WHERE name LIKE :s ORDER BY name LIMIT :max';
            if ($st = $db->prepare($sqlA)) {
                $st->bindValue(':s', $like, \SQLITE3_TEXT);
                $st->bindValue(':max', $max, \SQLITE3_INTEGER);
                $rs = $st->execute();
                while ($row = $rs->fetchArray(\SQLITE3_ASSOC)) {
                    $cr = $row['crystal_type'] ?? null;
                    $items[] = [
                        'id' => (int)$row['id'],
                        'name' => (string)$row['name'],
                        'type' => 'armor',
                        'image' => \Ofey\Logan22\component\image\client_icon::getIcon((string)($row['icon'] ?? ''), 'icon'),
                        'crystal_type' => $cr,
                        'grade_html' => $gradeImg($cr),
                    ];
                }
            }
            // etcitems
            $sqlE = 'SELECT id, name, icon FROM etcitems WHERE name LIKE :s ORDER BY name LIMIT :max';
            if ($st = $db->prepare($sqlE)) {
                $st->bindValue(':s', $like, \SQLITE3_TEXT);
                $st->bindValue(':max', $max, \SQLITE3_INTEGER);
                $rs = $st->execute();
                while ($row = $rs->fetchArray(\SQLITE3_ASSOC)) {
                    $items[] = [
                        'id' => (int)$row['id'],
                        'name' => (string)$row['name'],
                        'type' => 'etcitem',
                        'image' => \Ofey\Logan22\component\image\client_icon::getIcon((string)($row['icon'] ?? ''), 'icon'),
                        'crystal_type' => null,
                        'grade_html' => '',
                    ];
                }
            }
        } catch (\Throwable $e) {
            echo json_encode(['error' => 'query_failed']);
            return;
        }

        // Limit overall number and sort by name asc (case-insensitive)
        usort($items, function($a, $b){ return strcasecmp($a['name'], $b['name']); });
        if (count($items) > $max) $items = array_slice($items, 0, $max);

        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Simple search endpoint returning list of NPCs matching name/title.
     * URL: /wiki/npcs/search?q=... (GET)
     * Query must be at least 3 characters. Returns JSON array of {id,name,level}.
     */
    public function npcsSearch(): void
    {
        if (!$this->ensureActiveJson()) return;
        header('Content-Type: application/json; charset=utf-8');
        $q = trim((string)($_GET['q'] ?? ''));
        // Minimum length from settings (default 4)
        try { $setting = plugin::getSetting('wiki'); } catch (\Throwable $e) { $setting = []; }
        $minLen = isset($setting['npcSearchMinLen']) ? (int)$setting['npcSearchMinLen'] : 4;
        if ($minLen < 1) $minLen = 1;
        if ($q === '' || mb_strlen($q) < $minLen) {
            echo json_encode(['error' => 'too_short', 'hint' => 'min_' . $minLen . '_chars']);
            return;
        }
        try {
            $db = WikiDb::get();
        } catch (\Throwable $e) {
            echo json_encode(['error' => 'db_unavailable']);
            return;
        }
        $max = 200;
        try {
            // Search name and title across entire npcs table (no is_spawn restriction)
            // Include `type` so client can display NPC type in results
            $sql = 'SELECT id, name, level, type FROM npcs WHERE name LIKE :s OR title LIKE :s ORDER BY CAST(level AS INTEGER) ASC, name LIMIT :max';
            $stmt = $db->prepare($sql);
            if (!$stmt) { echo json_encode(['error' => 'db_prepare_failed']); return; }
            $like = '%' . $q . '%';
            $stmt->bindValue(':s', $like, \SQLITE3_TEXT);
            $stmt->bindValue(':max', $max, \SQLITE3_INTEGER);
            $res = $stmt->execute();
            $out = [];
            while ($row = $res->fetchArray(\SQLITE3_ASSOC)) {
                $nid = isset($row['id']) ? (int)$row['id'] : 0;
                $img = null;
                if ($nid > 0) {
                    // Use existing getNpcImages() which will attempt to fetch images from the Sphere API
                    try {
                        $imgs = $this->getNpcImages($nid);
                        if (!empty($imgs) && is_array($imgs)) {
                            $img = $imgs[0];
                        }
                    } catch (\Throwable $e) {
                        $img = null;
                    }
                }
                $out[] = [
                    'id' => $nid,
                    'name' => $row['name'] ?? '',
                    'level' => $row['level'] ?? '',
                    'type' => $row['type'] ?? '',
                    'image' => $img,
                ];
            }
            echo json_encode(['query' => $q, 'count' => count($out), 'items' => $out], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            echo json_encode(['error' => 'db_query_failed']);
        }
    }

    /**
     * Detailed NPC page: /wiki/npc/id/{id}
     * Shows full stats, skills (all), and placeholder sections for drop & spoil (future enhancement)
     */
    public function npcView(int $id): void
    {
    $this->ensureActiveHtml();
        $repo = new NpcRepository();
        $npc = $repo->findById($id);

        $uploadContext = $this->prepareUploadTemplateContext();
        foreach ($uploadContext as $ctxKey => $ctxValue) {
            tpl::addVar($ctxKey, $ctxValue);
        }

        if (!$npc) {
            header('HTTP/1.1 404 Not Found');
            tpl::addVar('npcNotFoundId', $id);
            tpl::displayPlugin('/wiki/tpl/npc_view.html');
            return;
        }

        $cacheScope = $uploadContext['wikiCacheScope'] ?? 'guest';
        $cacheKey = self::pageCacheKey('npc_view', ['id' => (int)$id, 'scope' => $cacheScope]);

        if (tpl::pageCacheTryServe($cacheKey, self::$pageCacheTtl, 'wiki', false, true)) {
            tpl::addVar('npc', $npc);
            tpl::displayPlugin('/wiki/tpl/npc_view.html');
            return;
        }

        // Basic derived stats & formatting
        $mainStats = [
            'hp' => 'HP',
            'mp' => 'MP',
            'attack_physical' => 'P.Atk',
            'attack_magical' => 'M.Atk',
            'defence_physical' => 'P.Def',
            'defence_magical' => 'M.Def',
            // Keep additional known columns from reduced schema
            'attack_magic_speed' => 'M.Atk Spd',
            'attack_range' => 'Range',
            'attack_critical' => 'Crit',
            'exp' => 'Exp',
            'sp' => 'SP',
        ];
        $statsPrepared = [];
        foreach ($mainStats as $code => $label) {
            if (isset($npc[$code]) && $npc[$code] !== '') {
                $statsPrepared[] = [
                    'code' => $code,
                    'label' => $label,
                    'value' => is_numeric($npc[$code]) ? number_format((float)$npc[$code], 0, '.', ' ') : $npc[$code],
                ];
            }
        }

    tpl::addVar('npc_stats_prepared', $statsPrepared);
    // Load drop/spoil and spawn data from plugin SQLite DB (highfive.db) using singleton
        try {
            // Spawn points
            $spawnPoints = self::getNpcSpawnPoints((int)$npc['id']);
            if ($spawnPoints) {
                $npc['spawn_points'] = $spawnPoints;
            }

            // Drops and groups
            $db = WikiDb::get();
            $stmt = $db->prepare('SELECT npc_id, local_id, drop_type, group_index, chance FROM drop_groups WHERE npc_id = :nid');
            $stmt->bindValue(':nid', $npc['id'], \SQLITE3_INTEGER);
            $res = $stmt->execute();
                $groups = [];
                while ($r = $res->fetchArray(SQLITE3_ASSOC)) {
                    $gid = (string)($r['local_id'] ?? $r['group_index'] ?? uniqid('g'));
                    $groups[$gid] = [
                        'group_index' => $r['group_index'] ?? $r['local_id'] ?? $gid,
                        'drop_type' => isset($r['drop_type']) ? (int)$r['drop_type'] : null,
                        'chance' => isset($r['chance']) ? $r['chance'] : null,
                        'items' => [],
                    ];
                }

                // load drops (items)
            $stmt2 = $db->prepare('SELECT npc_id, item_id, min, max, chance, group_id, drop_type FROM drops WHERE npc_id = :nid ORDER BY group_id, item_id');
            $stmt2->bindValue(':nid', $npc['id'], \SQLITE3_INTEGER);
            $res2 = $stmt2->execute();
                $spoilCreated = false;
                while ($d = $res2->fetchArray(SQLITE3_ASSOC)) {
                    $groupKey = '';
                    if (isset($d['group_id']) && $d['group_id'] !== null && $d['group_id'] !== '') {
                        $groupKey = (string)$d['group_id'];
                    }
                    // if this group does not exist in drop_groups, create on-the-fly
                    if ($groupKey === '' || !isset($groups[$groupKey])) {
                        // special-case: spoil (drop_type == 0) should be in its own group
                        if (isset($d['drop_type']) && (int)$d['drop_type'] === 0) {
                            $groupKey = 'spoil';
                            if (!isset($groups[$groupKey])) {
                                $groups[$groupKey] = [
                                    'group_index' => 'spoil',
                                    'drop_type' => 0,
                                    'chance' => null,
                                    'items' => [],
                                ];
                            }
                            $spoilCreated = true;
                        } else {
                            // anonymous group for ungrouped drops
                            $groupKey = $groupKey !== '' ? $groupKey : 'default';
                            if (!isset($groups[$groupKey])) {
                                $groups[$groupKey] = [
                                    'group_index' => $groupKey,
                                    'drop_type' => isset($d['drop_type']) ? (int)$d['drop_type'] : null,
                                    'chance' => null,
                                    'items' => [],
                                ];
                            }
                        }
                    }

                    $groups[$groupKey]['items'][] = [
                        'item_id' => isset($d['item_id']) ? (int)$d['item_id'] : null,
                        'min' => isset($d['min']) ? (int)$d['min'] : null,
                        'max' => isset($d['max']) ? (int)$d['max'] : null,
                        'chance' => isset($d['chance']) ? $d['chance'] : null,
                        'drop_type' => isset($d['drop_type']) ? (int)$d['drop_type'] : null,
                    ];
                }
                // assign to npc only if groups found
                if ($groups) {
                    // normalize groups to indexed array preserving keys as group_index where meaningful
                    $npc['drop_groups'] = array_values($groups);
                }
        } catch (\Throwable $e) {
            // fail silently; template will show 'not configured'
        }

        // Build fast lookup for item names/icons from local SQLite (etcitems, weapons, armors)
        $itemLookup = [];
        try {
            $ids = [];
            if (!empty($npc['drop_groups']) && is_array($npc['drop_groups'])) {
                foreach ($npc['drop_groups'] as $g) {
                    if (!isset($g['items']) || !is_array($g['items'])) continue;
                    foreach ($g['items'] as $it) {
                        $iid = (int)($it['item_id'] ?? 0);
                        if ($iid > 0) $ids[$iid] = true;
                    }
                }
            }
            if ($ids) {
                $itemLookup = $this->loadItemsLookupByIds(array_keys($ids));
                // Enrich item lookup with craft detection (recipe existence)
                try {
                    $recipeRepo = new RecipeRepository();
                    foreach ($itemLookup as $iid => &$meta) {
                        $iidInt = (int)$iid;
                        if ($iidInt <= 0) continue;
                        $recipes = $recipeRepo->getRecipesByProductionId($iidInt);
                        if (!empty($recipes)) {
                            $meta['is_craft'] = true;
                            // try to pick a meaningful recipe id when available
                            $first = $recipes[0];
                            if (!empty($first['recipeId'])) {
                                $meta['recipe_id'] = $first['recipeId'];
                            } elseif (!empty($first['_recipe']['id'])) {
                                $meta['recipe_id'] = $first['_recipe']['id'];
                            } else {
                                $meta['recipe_id'] = null;
                            }
                        } else {
                            $meta['is_craft'] = false;
                        }
                    }
                    unset($meta);
                } catch (\Throwable $e) {
                    // ignore recipe lookup failures - non-critical
                }
            }
        } catch (\Throwable $e) {
            $itemLookup = [];
        }

        // Check for local NPC images
        $npcId = $id;
        $rangeStart = floor($npcId / 1000) * 1000;
        $rangeEnd = $rangeStart + 999;
        $rangeDir = "uploads/images/npc/{$rangeStart}_{$rangeEnd}/{$npcId}";
        $fullRangeDir = fileSys::get_dir($rangeDir);
        $npcImages = [];

        if (is_dir($fullRangeDir)) {
            $files = scandir($fullRangeDir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && is_file($fullRangeDir . '/' . $file)) {
                    $npcImages[] = "/{$rangeDir}/{$file}";
                }
            }
        }

        if (!empty($npcImages)) {
            tpl::addVar('npc_images', $npcImages);
        } else {
            // No local images, proceed with download
            $npcImgRequest = \Ofey\Logan22\component\sphere\server::send(type::GET_NPC_IMG, [
                'npcid' => (string)$id,
            ])->show()->getResponse();
            if (!empty($npcImgRequest) && is_array($npcImgRequest)) {
                // Support two response shapes: plain array of strings, or ['images' => [...]]
                $imagesList = $npcImgRequest;
                if (isset($npcImgRequest['images']) && is_array($npcImgRequest['images'])) {
                    $imagesList = $npcImgRequest['images'];
                }
                foreach ($imagesList as $img) {
                    // Normalize to string: item may be string or array with 'img' key
                    $imgStr = null;
                    if (is_string($img)) {
                        $imgStr = $img;
                    } elseif (is_array($img) && isset($img['img']) && is_string($img['img'])) {
                        $imgStr = $img['img'];
                    } else {
                        // unsupported item, skip
                        continue;
                    }

                    // Build GET path parameter, server::NPC_IMG_DOWNLOAD expects wildcard *img -> we will use query style
                    // Use sendCustomDownload which preserves headers and returns raw content
                    $encoded = str_replace('\\', '/', ltrim($imgStr, '\\/'));
                    $path = '/api/npc/image/' . urlencode($encoded);
                    $res = \Ofey\Logan22\component\sphere\server::sendCustomDownload($path);

                    if (empty($res) || empty($res['content']) || ($res['http_code'] ?? 0) !== 200) {
                        // continue to next image if download failed
                        continue;
                    }

                    $savePath = fileSys::get_dir("uploads/images/") . $encoded;
                    $saveDir = dirname($savePath);
                    if (!is_dir($saveDir)) {
                        @mkdir($saveDir, 0755, true);
                    }

                    // Write binary content
                    $written = false;
                    try {
                        $written = file_put_contents($savePath, $res['content']) !== false;
                    } catch (\Throwable $e) {
                        $written = false;
                    }

                    if ($written) {
                        // Successfully downloaded
                    }
                }

                // After download, recheck for images
                if (is_dir($fullRangeDir)) {
                    $files = scandir($fullRangeDir);
                    foreach ($files as $file) {
                        if ($file !== '.' && $file !== '..' && is_file($fullRangeDir . '/' . $file)) {
                            $npcImages[] = "/{$rangeDir}/{$file}";
                        }
                    }
                }
                if (!empty($npcImages)) {
                    tpl::addVar('npc_images', $npcImages);
                }
            }
        }

         

        tpl::addVar('npc', $npc);
    tpl::addVar('item_lookup', $itemLookup);
        // Pass map calibration constants to template
        tpl::addVar('map_calibration', [
            'x' => self::MAP_OFFSET_X,
            'y' => self::MAP_OFFSET_Y,
        ]);
        
    tpl::pageCacheBegin($cacheKey, self::$pageCacheTtl, 'wiki', false, true);
    tpl::displayPlugin('/wiki/tpl/npc_view.html');
    }

    /** Armor sets page */
    public function armorsets(): void
    {
    // Try resolve cached content first (content-only cache)
    if (tpl::pageCacheTryServe(self::pageCacheKey('armorsets'), self::$pageCacheTtl, 'wiki', false, true)) {
        tpl::displayPlugin('/wiki/tpl/armorsets.html');
        return;
    }
                $time_total_start = microtime(true);
        $time_db_open_start = microtime(true);
        $repo = new ArmorSetRepository();
        $time_db_open_end = microtime(true);
        $time_read_start = microtime(true);
        $setsByGrade = $repo->getAllSetsGrouped();
        $time_read_end = microtime(true);
        $time_total_end = microtime(true);
    $ordered = self::orderByGrade($setsByGrade);
    $gradeInfo = self::getGradeInfo();
        tpl::addVar('armorSetsByGrade', $ordered);
        tpl::addVar('armorSetGradeInfo', $gradeInfo);
        $timing = ['db_open_s' => number_format(($time_db_open_end - $time_db_open_start), 6, '.', ''), 'read_s' => number_format(($time_read_end - $time_read_start), 6, '.', ''), 'total_s' => number_format(($time_total_end - $time_total_start), 6, '.', '')];
        $timing['db_open_ms'] = number_format(($time_db_open_end - $time_db_open_start) * 1000, 3, '.', '');
        $timing['read_ms'] = number_format(($time_read_end - $time_read_start) * 1000, 3, '.', '');
        $timing['total_ms'] = number_format(($time_total_end - $time_total_start) * 1000, 3, '.', '');
        tpl::addVar('db_timing', $timing);
    // Enable saving content-only cache after successful render
    tpl::pageCacheBegin(self::pageCacheKey('armorsets'), self::$pageCacheTtl, 'wiki', false, true);
        tpl::displayPlugin('/wiki/tpl/armorsets.html');
    }

    /** Format a single numeric effect value according to tag/stat (shared for level-specific skill formatting). */
    private function formatSkillEffectValue(string $tag, string $stat, $val): string
    {
        if (!is_numeric($val)) return (string)$val;
        $num = (float)$val;
        if (in_array($tag, ['mul', 'multiply', 'mult'], true)) {
            $delta = ($num - 1.0) * 100.0;
            $sign = $delta >= 0 ? '+' : '';
            return $sign . $this->trimNumber($delta) . '%';
        }
        if (in_array($tag, ['pct', 'percent', 'percent_add', 'percent_sub', '%'], true) || preg_match('/(rate|chance|percent)/i', $stat)) {
            if (abs($num) < 1 && $num != 0) $num *= 100;
            $sign = $num >= 0 ? '+' : '';
            return $sign . $this->trimNumber($num) . '%';
        }
        if ($tag === 'add' || $tag === 'enchant') {
            $sign = $num >= 0 ? '+' : '';
            return $sign . $this->trimNumber($num);
        }
        $sign = $num >= 0 ? '+' : '';
        return $sign . $this->trimNumber($num);
    }

    private function trimNumber(float $num): string
    {
        $formatted = number_format($num, 2, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');
        return $formatted === '' ? '0' : $formatted;
    }

    /**
     * Format bytes into human-readable string (KB/MB)
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B','KB','MB','GB','TB'];
        $power = (int)floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);
        $value = $bytes / pow(1024, $power);
        return round($value, 2) . ' ' . $units[$power];
    }

    private function isTrustedUploader($user, array $setting): bool
    {
        if (!$user) {
            return false;
        }

        try {
            $name = trim((string)$user->getName());
        } catch (\Throwable $e) {
            $name = '';
        }

        if ($name === '') {
            return false;
        }

        $trusted = $setting['trustedUsers'] ?? [];
        if (!is_array($trusted) || empty($trusted)) {
            return false;
        }

        $normalize = static function(string $value): string {
            $value = trim($value);
            if (function_exists('mb_strtolower')) {
                return mb_strtolower($value, 'UTF-8');
            }
            return strtolower($value);
        };

        $normalizedName = $normalize($name);
        if ($normalizedName === '') {
            return false;
        }

        foreach ($trusted as $entry) {
            if (!is_string($entry) && !is_numeric($entry)) {
                continue;
            }
            $normalizedEntry = $normalize((string)$entry);
            if ($normalizedEntry !== '' && $normalizedEntry === $normalizedName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build a context array with all user/upload related flags required by NPC view templates.
     */
    private function prepareUploadTemplateContext(): array
    {
        $context = [
            'setting' => [],
            'user' => null,
            'pendingUploadsCount' => 0,
            'wikiTrustedUploader' => false,
            'wikiIsAdmin' => false,
            'wikiUserIsAuth' => false,
            'wikiAllowUserUpload' => false,
            'wikiCanUploadImages' => false,
            'wikiCachePerUser' => false,
            'wikiCacheScope' => 'guest',
            'wikiUserId' => 0,
        ];

        try {
            $setting = plugin::getSetting('wiki');
            if (!is_array($setting)) {
                $setting = [];
            }
            $context['setting'] = $setting;
        } catch (\Throwable $e) {
            $setting = [];
            $context['setting'] = [];
        }

        $user = null;
        try {
            $user = \Ofey\Logan22\model\user\user::self();
        } catch (\Throwable $e) {
            $user = null;
        }
        $context['user'] = $user;

        $userId = 0;
        $isAuth = false;
        $isAdmin = false;

        if ($user) {
            try {
                $userId = (int)$user->getId();
            } catch (\Throwable $e) {
                $userId = 0;
            }

            try {
                $isAuth = method_exists($user, 'isAuth') ? (bool)$user->isAuth() : ($userId > 0);
            } catch (\Throwable $e) {
                $isAuth = ($userId > 0);
            }

            try {
                $access = method_exists($user, 'getAccessLevel') ? strtolower((string)$user->getAccessLevel()) : '';
                $isAdmin = ($access === 'admin');
            } catch (\Throwable $e) {
                $isAdmin = false;
            }
        }

        $context['wikiUserId'] = $userId;
        $context['wikiUserIsAuth'] = $isAuth;
        $context['wikiIsAdmin'] = $isAdmin;

        $allowUserUpload = !empty($setting['allowUserNpcImageUpload']);
        $context['wikiAllowUserUpload'] = $allowUserUpload;

        $trusted = $this->isTrustedUploader($user, $setting);
        $context['wikiTrustedUploader'] = $trusted;

        $canUpload = $isAdmin || ($allowUserUpload && $isAuth);
        $context['wikiCanUploadImages'] = $canUpload;

        $context['wikiCachePerUser'] = $isAuth;
        if ($isAuth) {
            $scopeSuffix = ($userId > 0) ? ('user-' . $userId) : 'auth';
            $context['wikiCacheScope'] = $scopeSuffix;
        } else {
            $context['wikiCacheScope'] = 'guest';
        }

        if ($canUpload) {
            $context['pendingUploadsCount'] = $this->countPendingUploadsForUser($user);
        } else {
            $context['pendingUploadsCount'] = 0;
        }

        return $context;
    }

    /**
     * Count pending NPC image uploads for the given user.
     */
    private function countPendingUploadsForUser($user): int
    {
        if (!$user) {
            return 0;
        }

        try {
            $userId = (int)$user->getId();
        } catch (\Throwable $e) {
            return 0;
        }

        if ($userId <= 0) {
            return 0;
        }

        $moderationDir = fileSys::get_dir('uploads/images/npc/moderation');
        if (!is_dir($moderationDir)) {
            return 0;
        }

        $logFile = $moderationDir . DIRECTORY_SEPARATOR . 'upload_log.json';
        if (!is_file($logFile)) {
            return 0;
        }

        $count = 0;
        try {
            $logData = json_decode(@file_get_contents($logFile), true);
            if (is_array($logData)) {
                foreach ($logData as $entry) {
                    if (!is_array($entry)) {
                        continue;
                    }
                    if ((int)($entry['user_id'] ?? 0) !== $userId) {
                        continue;
                    }
                    $filename = isset($entry['filename']) ? trim((string)$entry['filename']) : '';
                    if ($filename === '') {
                        continue;
                    }
                    $filePath = $moderationDir . DIRECTORY_SEPARATOR . $filename;
                    if (is_file($filePath)) {
                        $count++;
                    }
                }
            }
        } catch (\Throwable $e) {
            return 0;
        }

        return $count;
    }

    /**
     * Convert world coordinates (L2) to image pixel coordinates for the High Five world map.
     * Formula derived from legacy PHP image renderer:
     *   x_px = 285 + (x + 107823) / 200
     *   y_px = 2580 + (y - 255420) / 200
     * Returns [int x_px, int y_px].
     */
    private const MAP_OFFSET_X = -169; // calibration for highfive.webp
    private const MAP_OFFSET_Y = 0;

    private static function mapWorldToImage(float $x, float $y): array
    {
        $ix = (int)round(285 + ($x + 107823.0) / 200.0) + self::MAP_OFFSET_X;
        $iy = (int)round(2580 + ($y - 255420.0) / 200.0) + self::MAP_OFFSET_Y;
        return [$ix, $iy];
    }

    /**
     * Fetch spawn points for NPC from spawns table as list of [x,y,ix,iy].
     * Table structure: spawns(id INTEGER, respawn_delay TEXT, locs TEXT(JSON array)).
     */
    private static function getNpcSpawnPoints(int $npcId): array
    {
        if ($npcId <= 0) return [];
        try {
            $db = WikiDb::get();
        } catch (\Throwable $e) {
            return [];
        }
        try {
            $stmt = $db->prepare('SELECT locs FROM spawns WHERE id = :nid LIMIT 1');
            if (!$stmt instanceof \SQLite3Stmt) return [];
            $stmt->bindValue(':nid', $npcId, \SQLITE3_INTEGER);
            $res = $stmt->execute();
            if (!$res) return [];
            $row = $res->fetchArray(SQLITE3_ASSOC);
            if (!$row || empty($row['locs'])) return [];
            $points = json_decode($row['locs'], true);
            if (!is_array($points) || empty($points)) return [];
            $out = [];
            foreach ($points as $p) {
                if (!is_array($p) || !isset($p['x'], $p['y'])) continue;
                $wx = (float)$p['x'];
                $wy = (float)$p['y'];
                [$ix, $iy] = self::mapWorldToImage($wx, $wy);
                $out[] = ['x' => $wx, 'y' => $wy, 'ix' => $ix, 'iy' => $iy];
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Load items by ids from plugin DB across etcitems, weapons, armors.
     * Returns map: id => ['name' => string|null, 'icon' => string|null, 'icon_path' => string]
     */
    private function loadItemsLookupByIds(array $ids): array
    {
        $out = [];
        if (!$ids) return $out;
        // ensure unique positive ints and stay under SQLite host param limit per chunk
        $uniq = [];
        foreach ($ids as $id) {
            $i = (int)$id;
            if ($i > 0) $uniq[$i] = true;
        }
        if (!$uniq) return $out;
        $idList = array_keys($uniq);
        try { $db = WikiDb::get(); } catch (\Throwable $e) { return $out; }

        // Cache table columns to avoid repeated PRAGMA calls
        static $colsCache = [];
        $hasCol = function(string $table, string $col) use ($db, &$colsCache): bool {
            $table = trim($table);
            $col = strtolower(trim($col));
            if ($table === '') return false;
            if (!isset($colsCache[$table])) {
                $cols = [];
                $res = @$db->query("PRAGMA table_info(\"$table\")");
                if ($res) {
                    while ($row = $res->fetchArray(\SQLITE3_ASSOC)) {
                        if (!empty($row['name'])) $cols[strtolower($row['name'])] = true;
                    }
                }
                $colsCache[$table] = $cols;
            }
            return isset($colsCache[$table][$col]);
        };

        $fetchChunk = function(array $chunk, string $table) use ($db, $hasCol) {
            if (!$chunk) return [];
            $place = implode(',', array_fill(0, count($chunk), '?'));
            $sel_is_drop  = $hasCol($table,'is_drop')  ? 'COALESCE(is_drop,0) AS is_drop'   : '0 AS is_drop';
            $sel_is_sweep = $hasCol($table,'is_sweep') ? 'COALESCE(is_sweep,0) AS is_sweep' : ($hasCol($table,'is_spoil') ? 'COALESCE(is_spoil,0) AS is_sweep' : '0 AS is_sweep');
            $sel_is_craft = $hasCol($table,'is_craft') ? 'COALESCE(is_craft,0) AS is_craft' : ($hasCol($table,'recipe_id') ? '(CASE WHEN COALESCE(recipe_id,0)!=0 THEN 1 ELSE 0 END) AS is_craft' : '0 AS is_craft');
            $sel_crystal = $hasCol($table,'crystal_type') ? 'crystal_type' : 'NULL AS crystal_type';
            $sql = "SELECT id, name, icon, $sel_crystal, $sel_is_drop, $sel_is_sweep, $sel_is_craft FROM {$table} WHERE id IN ($place)";
            $stmt = $db->prepare($sql);
            if (!$stmt) return [];
            foreach ($chunk as $idx => $val) {
                $stmt->bindValue($idx + 1, (int)$val, \SQLITE3_INTEGER);
            }
            $res = $stmt->execute();
            $rows = [];
            if ($res) {
                while ($r = $res->fetchArray(\SQLITE3_ASSOC)) {
                    $rows[] = $r;
                }
            }
            return $rows;
        };

        $chunkSize = 800; // keep under 999 params limit
        for ($o = 0; $o < count($idList); $o += $chunkSize) {
            $chunk = array_slice($idList, $o, $chunkSize);
            // Query weapons, then armors, then etcitems; first found wins
            foreach (['weapons', 'armors', 'etcitems'] as $table) {
                $rows = $fetchChunk($chunk, $table);
                foreach ($rows as $row) {
                    $iid = (int)($row['id'] ?? 0);
                    if ($iid <= 0 || isset($out[$iid])) continue; // keep first found
                    $name = isset($row['name']) ? (string)$row['name'] : null;
                    $icon = isset($row['icon']) ? (string)$row['icon'] : '';
                    // Pre-resolve icon path once (uses unified client_icon resolver)
                    $out[$iid] = [
                        'name' => $name,
                        'icon' => $icon,
                        'icon_path' => client_icon::getIcon($icon, 'icon'),
                        'crystal_type' => $row['crystal_type'] ?? null,
                        'is_drop' => (int)($row['is_drop'] ?? 0),
                        'is_sweep' => (int)($row['is_sweep'] ?? 0),
                        'is_craft' => (int)($row['is_craft'] ?? 0),
                    ];
                }
            }
        }
        return $out;
    }

    /**
     * Page: list NPCs that can drop/spoil a given item
     * URL examples:
     *  - /wiki/items/sources/{itemId}
     *  - /wiki/items/sources/{itemId}/drop|spoil|all
     */
    public function itemSources(int $itemId, ?string $type = null): void
    {
        $this->ensureActiveHtml();
        $itemId = max(0, (int)$itemId);
        if ($itemId <= 0) {
            tpl::addVar('item_id', $itemId);
            tpl::addVar('item_meta', null);
            tpl::addVar('sources', []);
            tpl::addVar('filter_type', 'all');
            tpl::displayPlugin('/wiki/tpl/item_sources.html');
            return;
        }

        $filter = strtolower((string)($type ?? 'all'));
        if (!in_array($filter, ['all', 'drop', 'spoil'], true)) {
            $filter = 'all';
        }

        // Try cached sources per item+filter
    if (tpl::pageCacheTryServe(self::pageCacheKey('item_sources', ['item' => $itemId, 'filter' => $filter]), self::$pageCacheTtl, 'wiki', false, true)) {
            tpl::displayPlugin('/wiki/tpl/item_sources.html');
            return;
        }

        try {
            $db = WikiDb::get();
        } catch (\Throwable $e) {
            tpl::addVar('item_id', $itemId);
            tpl::addVar('item_meta', null);
            tpl::addVar('sources', []);
            tpl::addVar('filter_type', $filter);
            tpl::pageCacheBegin(self::pageCacheKey('item_sources', ['item' => $itemId, 'filter' => $filter]), self::$pageCacheTtl, 'wiki', false, true);
            tpl::displayPlugin('/wiki/tpl/item_sources.html');
            return;
        }

        // Always load ALL sources for counts; filter only for display later
        // Only include drop sources from spawnable NPCs
        // Use GROUP BY d.npc_id and aggregate to prevent duplicate NPC rows when multiple drops exist
        $sqlAll = "SELECT d.npc_id,
                    d.item_id,
                    MIN(d.min) AS min,
                    MAX(d.max) AS max,
                    MAX(d.chance) AS chance,
                    MAX(d.group_id) AS group_id,
                    MAX(d.drop_type) AS drop_type,
                    n.name, n.level, n.type, n.title
                FROM drops d
                LEFT JOIN npcs n ON n.id = d.npc_id
                WHERE d.item_id = :item AND (n.is_spawn = 1 OR n.type = 'GrandBoss')
                GROUP BY d.npc_id
                ORDER BY n.level DESC, n.name ASC, chance DESC";

        $stmtAll = $db->prepare($sqlAll);
        if ($stmtAll) {
            $stmtAll->bindValue(':item', $itemId, \SQLITE3_INTEGER);
        }
        $resAll = $stmtAll ? $stmtAll->execute() : false;
        $all = [];
        if ($resAll) {
            while ($r = $resAll->fetchArray(\SQLITE3_ASSOC)) {
                $npcId = (int)($r['npc_id'] ?? 0);
                if ($npcId <= 0) continue;
                $all[] = [
                    'npc_id'   => $npcId,
                    'name'     => isset($r['name']) ? (string)$r['name'] : null,
                    'level'    => isset($r['level']) ? (int)$r['level'] : null,
                    'type'     => isset($r['type']) ? (string)$r['type'] : null,
                    'title'    => isset($r['title']) ? (string)$r['title'] : null,
                    'min'      => isset($r['min']) ? (int)$r['min'] : null,
                    'max'      => isset($r['max']) ? (int)$r['max'] : null,
                    'chance'   => isset($r['chance']) ? (float)$r['chance'] : null,
                    'group_id' => isset($r['group_id']) ? (int)$r['group_id'] : null,
                    'drop_type'=> isset($r['drop_type']) ? (int)$r['drop_type'] : null,
                    'link'     => '/wiki/npc/id/' . $npcId,
                ];
            }
        }

        // Preload NPC images for all involved NPCs (download if missing)
        $imagesByNpc = [];
        $seen = [];
        foreach ($all as $row) {
            $nid = (int)$row['npc_id'];
            if ($nid <= 0 || isset($seen[$nid])) continue;
            $seen[$nid] = true;
            $imgs = $this->getNpcImages($nid);
            $imagesByNpc[$nid] = $imgs;
        }
        // Attach first image (if any)
        foreach ($all as &$row) {
            $nid = (int)$row['npc_id'];
            $img = $imagesByNpc[$nid][0] ?? null;
            if ($img) $row['image'] = $img;
        }
        unset($row);

        // Load item meta (name/icon) using existing helper
        $itemMeta = $this->loadItemsLookupByIds([$itemId]);
        $meta = $itemMeta[$itemId] ?? null;
        // Compute counts on ALL rows (not filtered)
        $totalAll = count($all);
        $dropCountAll = 0; $spoilCountAll = 0;
        foreach ($all as $s) {
            if ((int)($s['drop_type'] ?? 1) === 0) $spoilCountAll++; else $dropCountAll++;
        }
    // Now filter for display
        $sourcesDisplay = array_values(array_filter($all, function($r) use ($filter) {
            if ($filter === 'all') return true;
            $isSpoil = ((int)($r['drop_type'] ?? 1) === 0);
            return $filter === 'spoil' ? $isSpoil : !$isSpoil;
        }));

        tpl::addVar('item_id', $itemId);
        tpl::addVar('item_meta', $meta);
        tpl::addVar('sources', $sourcesDisplay);
        tpl::addVar('source_total', $totalAll);
        tpl::addVar('source_drop_count', $dropCountAll);
        tpl::addVar('source_spoil_count', $spoilCountAll);
        tpl::addVar('filter_type', $filter);
    // Save cache for item sources page
    tpl::pageCacheBegin(self::pageCacheKey('item_sources', ['item' => $itemId, 'filter' => $filter]), self::$pageCacheTtl, 'wiki', false, true);
        tpl::displayPlugin('/wiki/tpl/item_sources.html');
    }

    /**
     * Try to get cached NPC images; if not present, request from Sphere API to download then re-scan.
     * Returns array of web paths (strings).
     */
    private function getNpcImages(int $npcId): array
    {
        $npcId = (int)$npcId;
        if ($npcId <= 0) return [];
        $rangeStart = (int)(floor($npcId / 1000) * 1000);
        $rangeEnd = $rangeStart + 999;
        $rangeDir = "uploads/images/npc/{$rangeStart}_{$rangeEnd}/{$npcId}";
        $fullRangeDir = fileSys::get_dir($rangeDir);
        $images = [];
        if (is_dir($fullRangeDir)) {
            $files = @scandir($fullRangeDir) ?: [];
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                if (!preg_match('/^\\d+\\.(webp|png|jpg)$/i', $file)) continue;
                $images[] = "/{$rangeDir}/{$file}";
            }
        }
        if ($images) return $images;
        // Try to fetch from server API
        try {
            $npcImgRequest = \Ofey\Logan22\component\sphere\server::send(type::GET_NPC_IMG, [
                'npcid' => (string)$npcId,
            ])->show()->getResponse();
            if (!empty($npcImgRequest) && is_array($npcImgRequest)) {
                // Support both shapes: ["images"=>[...]] or simple array
                $imagesList = $npcImgRequest;
                if (isset($npcImgRequest['images']) && is_array($npcImgRequest['images'])) {
                    $imagesList = $npcImgRequest['images'];
                }
                foreach ($imagesList as $img) {
                    // Normalize to string path
                    $imgStr = null;
                    if (is_string($img)) {
                        $imgStr = $img;
                    } elseif (is_array($img) && isset($img['img']) && is_string($img['img'])) {
                        $imgStr = $img['img'];
                    } else {
                        continue;
                    }
                    $encoded = str_replace('\\\
','/', ltrim($imgStr, '\\/'));
                    $path = '/api/npc/image/' . urlencode($encoded);
                    $res = \Ofey\Logan22\component\sphere\server::sendCustomDownload($path);
                    if (empty($res) || empty($res['content']) || ($res['http_code'] ?? 0) !== 200) {
                        continue;
                    }
                    $savePath = fileSys::get_dir('uploads/images/') . $encoded;
                    $saveDir = dirname($savePath);
                    if (!is_dir($saveDir)) {
                        @mkdir($saveDir, 0755, true);
                    }
                    try { @file_put_contents($savePath, $res['content']); } catch (\Throwable $e) {}
                }
                // Re-scan local folder after attempt
                if (is_dir($fullRangeDir)) {
                    $files = @scandir($fullRangeDir) ?: [];
                    foreach ($files as $file) {
                        if ($file === '.' || $file === '..') continue;
                        if (!preg_match('/^\\d+\\.(webp|png|jpg)$/i', $file)) continue;
                        $images[] = "/{$rangeDir}/{$file}";
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return $images;
    }

    /**
     * Handle NPC image upload
     */
    public function uploadNpcImage()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            // Check if user is logged in
            $user = \Ofey\Logan22\model\user\user::self();
            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'User not logged in']);
                return;
            }

            // Check permissions
            $isAdmin = $user->getAccessLevel() === 'admin';
            $setting = plugin::getSetting('wiki');
            if (!is_array($setting)) {
                $setting = [];
            }
            $allowUserUpload = (bool)($setting['allowUserNpcImageUpload'] ?? false);
            $isTrustedUploader = $this->isTrustedUploader($user, $setting);
            
            // Allow upload only for admins and trusted users (when user upload is enabled)
            if (!$isAdmin && (!$allowUserUpload || !$isTrustedUploader)) {
                echo json_encode(['success' => false, 'message' => 'Permission denied']);
                return;
            }

            // Validate input
            if (!isset($_POST['npc_id']) || !is_numeric($_POST['npc_id'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid NPC ID']);
                return;
            }
            
            $npcId = (int)$_POST['npc_id'];
            
            // If client submitted multiple files via images[], enforce maximum count
            if (isset($_FILES['images'])) {
                $errorsArr = $_FILES['images']['error'];
                $uploadedCount = 0;
                if (is_array($errorsArr)) {
                    foreach ($errorsArr as $err) {
                        if ($err !== UPLOAD_ERR_NO_FILE) {
                            $uploadedCount++;
                        }
                    }
                }
                if ($uploadedCount > 10) {
                    echo json_encode(['success' => false, 'message' => 'Too many files uploaded. Maximum is 10 images per request']);
                    return;
                }
            }

            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'No image uploaded or upload error']);
                return;
            }

            $uploadedFile = $_FILES['image'];
            
            // Check file size (10MB max)
            if ($uploadedFile['size'] > 10 * 1024 * 1024) {
                echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 10MB']);
                return;
            }

            // Validate image type
            $imageInfo = @getimagesize($uploadedFile['tmp_name']);
            if (!$imageInfo) {
                echo json_encode(['success' => false, 'message' => 'Invalid image file']);
                return;
            }

            $mimeType = $imageInfo['mime'];
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            if (!in_array($mimeType, $allowedMimes)) {
                echo json_encode(['success' => false, 'message' => 'Unsupported image format. Use JPG, PNG, GIF, or WebP']);
                return;
            }

            // Create image resource
            $image = null;
            switch ($mimeType) {
                case 'image/jpeg':
                    $image = @imagecreatefromjpeg($uploadedFile['tmp_name']);
                    break;
                case 'image/png':
                    $image = @imagecreatefrompng($uploadedFile['tmp_name']);
                    break;
                case 'image/gif':
                    $image = @imagecreatefromgif($uploadedFile['tmp_name']);
                    break;
                case 'image/webp':
                    $image = @imagecreatefromwebp($uploadedFile['tmp_name']);
                    break;
            }

            if (!$image) {
                echo json_encode(['success' => false, 'message' => 'Failed to process image']);
                return;
            }

            $originalWidth = imagesx($image);
            $originalHeight = imagesy($image);

            // Resize if needed (max 1920x1280)
            $maxWidth = 1920;
            $maxHeight = 1280;
            $needsResize = $originalWidth > $maxWidth || $originalHeight > $maxHeight;

            if ($needsResize) {
                $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
                $newWidth = (int)($originalWidth * $ratio);
                $newHeight = (int)($originalHeight * $ratio);

                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
                
                // Preserve transparency for PNG/GIF
                if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
                    imagealphablending($resizedImage, false);
                    imagesavealpha($resizedImage, true);
                    $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
                    imagefill($resizedImage, 0, 0, $transparent);
                }

                imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
                imagedestroy($image);
                $image = $resizedImage;
            }

            // Create moderation directory
            $moderationDir = fileSys::get_dir('uploads/images/npc/moderation');
            if (!is_dir($moderationDir)) {
                @mkdir($moderationDir, 0755, true);
            }

            // Generate random filename
            $filename = bin2hex(random_bytes(16)) . '.webp';
            $filePath = $moderationDir . DIRECTORY_SEPARATOR . $filename;

            // Save as WebP
            if (!imagewebp($image, $filePath, 90)) {
                imagedestroy($image);
                echo json_encode(['success' => false, 'message' => 'Failed to save image']);
                return;
            }

            // Get final dimensions
            $imgInfo = @getimagesize($filePath);
            $finalWidth = $imgInfo[0] ?? 0;
            $finalHeight = $imgInfo[1] ?? 0;

            // Decide whether this upload should be auto-approved
            $trustedUsers = array_map('strtolower', (array)($setting['trustedUsers'] ?? []));
            $maxPending = isset($setting['maxPendingPerUser']) ? (int)$setting['maxPendingPerUser'] : 30;
            $isTrusted = in_array(strtolower($user->getName()), $trustedUsers, true);

            // Count current pending uploads for this user
            $pendingCount = 0;
            $logFile = $moderationDir . DIRECTORY_SEPARATOR . 'upload_log.json';
            $existingLog = [];
            if (file_exists($logFile)) {
                $existingLog = json_decode(file_get_contents($logFile), true) ?: [];
                foreach ($existingLog as $entry) {
                    if (isset($entry['user_id']) && $entry['user_id'] == $user->getId()) {
                        // ensure file still exists
                        $p = $moderationDir . DIRECTORY_SEPARATOR . ($entry['filename'] ?? '');
                        if (is_file($p)) $pendingCount++;
                    }
                }
            }

            // If user is not admin/trusted and maxPending limit >0 enforced, block if exceeded
            if (!$isAdmin && !$isTrusted && $maxPending > 0 && $pendingCount >= $maxPending) {
                // remove temp file
                @unlink($filePath);
                echo json_encode(['success' => false, 'message' => 'You have reached the maximum number of pending images']);
                return;
            }

            imagedestroy($image);

            // Log the upload into moderation log (even if later auto-approved we keep a record)
            $logData = [
                'npc_id' => $npcId,
                'filename' => $filename,
                'original_name' => $uploadedFile['name'],
                'user_id' => $user->getId(),
                'user_email' => $user->getEmail(),
                'upload_time' => date('Y-m-d H:i:s'),
                'file_size' => filesize($filePath),
                'dimensions' => $finalWidth . 'x' . $finalHeight
            ];
            $existingLog[] = $logData;
            file_put_contents($logFile, json_encode($existingLog, JSON_PRETTY_PRINT));

            // If admin or trusted user — auto-approve immediately: move file to approved location
            if ($isAdmin || $isTrusted) {
                $approvedBase = fileSys::get_dir('uploads/images/npc');
                $rangeStart = intval($npcId / 1000) * 1000;
                $rangeEnd = $rangeStart + 999;
                $rangeDirName = sprintf('%d_%d', $rangeStart, $rangeEnd);
                $npcSpecificDir = rtrim($approvedBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $rangeDirName . DIRECTORY_SEPARATOR . $npcId;
                if (!is_dir($npcSpecificDir)) @mkdir($npcSpecificDir, 0755, true);
                $targetFilename = $npcId . '_' . time() . '.' . pathinfo($filename, PATHINFO_EXTENSION);
                $targetPath = $npcSpecificDir . DIRECTORY_SEPARATOR . $targetFilename;
                if (@rename($filePath, $targetPath)) {
                    $this->removeFromModerationLog($filename); 
                    try { $this->clearNpcCache($npcId); } catch (\Throwable $e) {}
                    // Send notification with absolute image URL
                    try {
                        $imageUrl = $this->buildNpcImageUrl($npcId, $targetFilename);
                        \Ofey\Logan22\component\sphere\server::send(type::NOTICE_NPC_IMG, [
                            'npc_id' => (string)$npcId,
                            'image_url' => $imageUrl,
                        ])->show()->getResponse();
                    } catch (\Throwable $e) { /* ignore send errors */ }
                    echo json_encode(['success' => true, 'message' => 'Image uploaded and auto-approved', 'filename' => $targetFilename]);
                    return;
                }

            }

            echo json_encode([
                'success' => true,
                'message' => 'Image uploaded successfully and awaiting moderation',
                'filename' => $filename
            ]);

        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove entry from moderation log (helper for wiki uploads)
     */
    private function removeFromModerationLog(string $filename): void
    {
        $moderationDir = \Ofey\Logan22\component\fileSys\fileSys::get_dir('uploads/images/npc/moderation');
        $logFile = $moderationDir . DIRECTORY_SEPARATOR . 'upload_log.json';

        if (file_exists($logFile)) {
            $logData = json_decode(file_get_contents($logFile), true) ?: [];
            $logData = array_filter($logData, function($entry) use ($filename) {
                return ($entry['filename'] ?? '') !== $filename;
            });
            file_put_contents($logFile, json_encode(array_values($logData), JSON_PRETTY_PRINT));
        }
    }

    /**
     * Clear NPC cache after image approval - helper used by wiki uploads
     */
    private function clearNpcCache(int $npcId): void
    {
        $clearedFiles = 0;
        try {
            $cacheBaseDir = \Ofey\Logan22\component\fileSys\fileSys::get_dir('uploads/cache/plugins/wiki');
            if (is_dir($cacheBaseDir)) {
                $directories = [$cacheBaseDir];
                $subDirs = glob($cacheBaseDir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];
                foreach ($subDirs as $dir) {
                    $directories[] = $dir;
                }

                $slug = self::slugifyCacheValue($npcId, (string)$npcId);

                foreach ($directories as $dir) {
                    $npcDir = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'npc';
                    if (!is_dir($npcDir)) {
                        continue;
                    }
                    $cacheFile = $npcDir . DIRECTORY_SEPARATOR . $slug . '.html';
                    if (is_file($cacheFile) && @unlink($cacheFile)) {
                        $clearedFiles++;
                    }
                    $scopedDir = $npcDir . DIRECTORY_SEPARATOR . $slug;
                    if (is_dir($scopedDir)) {
                        $scopedFiles = glob($scopedDir . DIRECTORY_SEPARATOR . '*.html') ?: [];
                        foreach ($scopedFiles as $scopedFile) {
                            if (is_file($scopedFile) && @unlink($scopedFile)) {
                                $clearedFiles++;
                            }
                        }
                        @rmdir($scopedDir);
                    }
                }
            }

            // Fallback: scan for any legacy cache files that still reference this NPC
            if (is_dir($cacheBaseDir)) {
                $it = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($cacheBaseDir, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST
                );

                foreach ($it as $file) {
                    if (!$file->isFile()) {
                        continue;
                    }

                    if (strtolower($file->getExtension()) !== 'html') {
                        continue;
                    }

                    $content = @file_get_contents($file->getRealPath());
                    if ($content === false) {
                        continue;
                    }

                    if (
                        strpos($content, '/wiki/npc/id/' . $npcId) !== false ||
                        strpos($content, '"npc_id":' . $npcId) !== false ||
                        strpos($content, 'data-npc-id="' . $npcId . '"') !== false
                    ) {
                        if (@unlink($file->getRealPath())) {
                            $clearedFiles++;
                        }
                    }
                }
            }

            error_log("Wiki: Cleared {$clearedFiles} cache files for NPC {$npcId}");
        } catch (\Throwable $e) {
            error_log('Wiki: Failed to clear NPC cache for ID ' . $npcId . ': ' . $e->getMessage());
        }
    }

    /**
     * Build absolute URL to an approved NPC image file.
     * Result like: https://your.host/uploads/images/npc/1000_1999/1234/1234_1695650000.webp
     */
    private function buildNpcImageUrl(int $npcId, string $filename): string
    {
        $npcId = (int)$npcId;
        $filename = ltrim($filename, '/\\');
        $rangeStart = (int)(floor($npcId / 1000) * 1000);
        $rangeEnd = $rangeStart + 999;
        $relativePath = "/uploads/images/npc/{$rangeStart}_{$rangeEnd}/{$npcId}/{$filename}";
        try {
            $host = \Ofey\Logan22\component\links\http::getHost(false);
        } catch (\Throwable $e) {
            $host = '';
        }
        return ($host ? rtrim($host, '/') : '') . $relativePath;
    }

    /**
     * Admin: approve a single NPC image from moderation
     */
    public function adminApproveNpcImage()
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            \Ofey\Logan22\model\admin\validation::user_protection('admin');

            $filename = $_POST['filename'] ?? '';
            $npcId = isset($_POST['npc_id']) ? (int)$_POST['npc_id'] : 0;
            if ($filename === '' || $npcId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
                return;
            }

            $moderationDir = \Ofey\Logan22\component\fileSys\fileSys::get_dir('uploads/images/npc/moderation');
            $sourcePath = rtrim($moderationDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
            if (!is_file($sourcePath)) {
                echo json_encode(['success' => false, 'message' => 'File not found']);
                return;
            }

            $approvedBase = \Ofey\Logan22\component\fileSys\fileSys::get_dir('uploads/images/npc');
            $rangeStart = intval($npcId / 1000) * 1000;
            $rangeEnd = $rangeStart + 999;
            $rangeDirName = sprintf('%d_%d', $rangeStart, $rangeEnd);
            $npcSpecificDir = rtrim($approvedBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $rangeDirName . DIRECTORY_SEPARATOR . $npcId;
            if (!is_dir($npcSpecificDir)) @mkdir($npcSpecificDir, 0755, true);

            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $targetFilename = $npcId . '_' . time() . '.' . $extension;
            $targetPath = $npcSpecificDir . DIRECTORY_SEPARATOR . $targetFilename;

            if (!@rename($sourcePath, $targetPath)) {
                echo json_encode(['success' => false, 'message' => 'Failed to move image file']);
                return;
            }

            $this->removeFromModerationLog($filename);
            try { $this->clearNpcCache($npcId); } catch (\Throwable $e) {}

            $imageUrl = $this->buildNpcImageUrl($npcId, $targetFilename);
            \Ofey\Logan22\component\sphere\server::send(type::NOTICE_NPC_IMG, [
                'npc_id' => (string)$npcId,
                'image_url' => $imageUrl,
            ])->show()->getResponse();


            echo json_encode(['success' => true, 'message' => 'Image approved', 'filename' => $targetFilename]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    /**
     * Admin: reject (delete) a single NPC image from moderation
     */
    public function adminRejectNpcImage()
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            \Ofey\Logan22\model\admin\validation::user_protection('admin');
            $filename = $_POST['filename'] ?? '';
            if ($filename === '') {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
                return;
            }
            $moderationDir = \Ofey\Logan22\component\fileSys\fileSys::get_dir('uploads/images/npc/moderation');
            $sourcePath = rtrim($moderationDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
            if (is_file($sourcePath)) {
                @unlink($sourcePath);
            }
            $this->removeFromModerationLog($filename);
            echo json_encode(['success' => true, 'message' => 'Image rejected and deleted']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    /**
     * Admin: approve all pending NPC images (move them to approved locations)
     */
    public function adminApproveAllNpcImages()
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            \Ofey\Logan22\model\admin\validation::user_protection('admin');
            $moderationDir = \Ofey\Logan22\component\fileSys\fileSys::get_dir('uploads/images/npc/moderation');
            $logFile = $moderationDir . DIRECTORY_SEPARATOR . 'upload_log.json';
            $entries = [];
            if (file_exists($logFile)) {
                $entries = json_decode(file_get_contents($logFile), true) ?: [];
            }
            $moved = 0;
            foreach ($entries as $entry) {
                $filename = $entry['filename'] ?? null;
                $npcId = isset($entry['npc_id']) ? (int)$entry['npc_id'] : 0;
                if (!$filename || $npcId <= 0) continue;
                $sourcePath = $moderationDir . DIRECTORY_SEPARATOR . $filename;
                if (!is_file($sourcePath)) continue;
                $approvedBase = \Ofey\Logan22\component\fileSys\fileSys::get_dir('uploads/images/npc');
                $rangeStart = intval($npcId / 1000) * 1000;
                $rangeEnd = $rangeStart + 999;
                $rangeDirName = sprintf('%d_%d', $rangeStart, $rangeEnd);
                $npcSpecificDir = rtrim($approvedBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $rangeDirName . DIRECTORY_SEPARATOR . $npcId;
                if (!is_dir($npcSpecificDir)) @mkdir($npcSpecificDir, 0755, true);
                $extension = pathinfo($filename, PATHINFO_EXTENSION);
                $targetFilename = $npcId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                $targetPath = $npcSpecificDir . DIRECTORY_SEPARATOR . $targetFilename;
                if (@rename($sourcePath, $targetPath)) {
                    $moved++;
                    try { $this->clearNpcCache($npcId); } catch (\Throwable $e) {}
                    // Send notification with absolute URL for each approved image
                    try {
                        $imageUrl = $this->buildNpcImageUrl($npcId, $targetFilename);
                        \Ofey\Logan22\component\sphere\server::send(type::NOTICE_NPC_IMG, [
                            'npc_id' => (string)$npcId,
                            'image_url' => $imageUrl,
                        ])->show()->getResponse();
                    } catch (\Throwable $e) { /* ignore send errors */ }
                }
            }
            // clear moderation log
            @file_put_contents($logFile, json_encode([], JSON_PRETTY_PRINT));
            echo json_encode(['success' => true, 'message' => 'Approved ' . $moved . ' images', 'count' => $moved]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    /**
     * Admin: reject all pending NPC images (delete files)
     */
    public function adminRejectAllNpcImages()
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            \Ofey\Logan22\model\admin\validation::user_protection('admin');
            $moderationDir = \Ofey\Logan22\component\fileSys\fileSys::get_dir('uploads/images/npc/moderation');
            $logFile = $moderationDir . DIRECTORY_SEPARATOR . 'upload_log.json';
            $entries = [];
            if (file_exists($logFile)) {
                $entries = json_decode(file_get_contents($logFile), true) ?: [];
            }
            $deleted = 0;
            foreach ($entries as $entry) {
                $filename = $entry['filename'] ?? null;
                if (!$filename) continue;
                $sourcePath = $moderationDir . DIRECTORY_SEPARATOR . $filename;
                if (is_file($sourcePath)) {
                    @unlink($sourcePath);
                    $deleted++;
                }
            }
            @file_put_contents($logFile, json_encode([], JSON_PRETTY_PRINT));
            echo json_encode(['success' => true, 'message' => 'Deleted ' . $deleted . ' images', 'count' => $deleted]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    /**
     * Admin: show moderation UI with pending images list
     */
    public function adminNpcImagesModeration(): void
    {
        validation::user_protection('admin');
        $this->ensureActiveHtml();

        $moderationDir = \Ofey\Logan22\component\fileSys\fileSys::get_dir('uploads/images/npc/moderation');
        $logFile = $moderationDir . DIRECTORY_SEPARATOR . 'upload_log.json';
        $pending = [];
        if (file_exists($logFile)) {
            try {
                $entries = json_decode(file_get_contents($logFile), true) ?: [];
                foreach ($entries as $entry) {
                    $filename = $entry['filename'] ?? null;
                    $npcId = isset($entry['npc_id']) ? (int)$entry['npc_id'] : 0;
                    if (!$filename) continue;
                    $p = $moderationDir . DIRECTORY_SEPARATOR . $filename;
                    if (!is_file($p)) continue;
                    $stat = @stat($p);
                    $size = is_array($stat) && isset($stat['size']) ? (int)$stat['size'] : @filesize($p);
                    $dim = @getimagesize($p);
                    $pending[] = [
                        'filename' => $filename,
                        'npc_id' => $npcId,
                        'original_name' => $entry['original_name'] ?? '',
                        'user_id' => $entry['user_id'] ?? 0,
                        'user_email' => $entry['user_email'] ?? '',
                        'upload_time' => $entry['upload_time'] ?? '',
                        'file_size' => $size,
                        'file_size_formatted' => $this->formatBytes($size),
                        'dimensions' => is_array($dim) && isset($dim[0], $dim[1]) ? ($dim[0] . 'x' . $dim[1]) : '',
                        'file_url' => '/uploads/images/npc/moderation/' . $filename,
                    ];
                }
            } catch (\Throwable $e) {
                // ignore and show empty
            }
        }

        tpl::addVar('pending_images', $pending);
        try { $setting = plugin::getSetting('wiki'); tpl::addVar('setting', $setting); } catch (\Throwable $e) { tpl::addVar('setting', []); }
        try { $user = \Ofey\Logan22\model\user\user::self(); tpl::addVar('user', $user); } catch (\Throwable $e) { tpl::addVar('user', null); }
        tpl::displayPlugin('/wiki/tpl/admin_npc_image_moderation.html');
    }
}
