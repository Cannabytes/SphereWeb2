<?php

namespace Ofey\Logan22\component\plugins\library;

use SQLite3;
use RuntimeException;

/**
 * Lightweight repository for reading armors data from local plugin SQLite DB (highfive.db).
 * Mirrors WeaponRepository logic but targets the `armors` table.
 */
class ArmorRepository
{
    private string $dbPath;
    private ?SQLite3 $db = null;

    public function __construct(?string $dbPath = null)
    {
        $this->dbPath = $dbPath ?? __DIR__ . '/db/highfive.db';
        if (!is_file($this->dbPath)) {
            throw new RuntimeException('SQLite database not found: ' . $this->dbPath);
        }
        $this->open();
    }

    private function open(): void
    {
        $this->db = new SQLite3($this->dbPath, SQLITE3_OPEN_READONLY);
        $this->db->exec('PRAGMA encoding = "UTF-8"');
    }

    public function __destruct()
    {
        if ($this->db instanceof SQLite3) {
            $this->db->close();
        }
    }

    /**
     * Parse JSON from 'for' field and extract defensive values and a normalized stats list.
     * Returns pDef/mDef and enchant variants for backward compatibility plus a full 'stats' array
     * with entries: code,label,type,value,formatted
     *
     * @param string|null $forJson
     * @return array{pDef: int|null, mDef: int|null, pDef_enchant: int|null, mDef_enchant: int|null, stats: array}
     */
    public function parseForField(?string $forJson): array
    {
        // Default empty structure
        $result = [
            'pDef' => null,
            'mDef' => null,
            'pDef_enchant' => null,
            'mDef_enchant' => null,
            'stats' => []
        ];

        if (empty($forJson)) {
            return $result;
        }

        $data = json_decode($forJson, true);
        if (!is_array($data)) {
            return $result;
        }

        // A small dictionary mapping stat codes to human-friendly labels.
        // Extend this list as needed (user-provided link can be used to expand mappings).
        $labels = StatLabels::all();

        $pDef = null;
        $mDef = null;
        $pDefEnchant = null;
        $mDefEnchant = null;
        $stats = [];

        $formatStat = function ($code, $type, $val) {
            $t = strtolower((string)$type);
            // Keep original string for non-numeric values
            if (is_numeric($val)) {
                // Normalize numeric string to float/int
                if ((string)(int)$val === (string)$val) {
                    $num = (int)$val;
                } else {
                    $num = (float)$val;
                }
            } else {
                $num = $val;
            }

            // Multiplicative types -> show as xN
            if (in_array($t, ['mul', 'multiply', 'mult'], true)) {
                return 'x' . $val;
            }

            // Percent types -> show with percent sign. Accept several possible markers.
            if (in_array($t, ['pct', 'percent', 'percent_add', 'percent_sub', '%'], true) || preg_match('/(rate|chance|percent)/i', $code)) {
                if (!is_numeric($num)) return (string)$val;
                // If value is fractional (0.xx) treat as portion and convert to percent
                if (abs($num) < 1 && $num !== 0) {
                    $num = $num * 100;
                }
                $sign = ($num > 0) ? '+' : '';
                // Format without trailing zeros when integer
                $formatted = (floor($num) == $num) ? (int)$num : rtrim(rtrim(number_format($num, 2, '.', ''), '0'), '.');
                return $sign . $formatted . '%';
            }

            // Enchant values often shown as +N
            if ($t === 'enchant') {
                return '+' . $val;
            }

            // Default: absolute addition / subtraction
            if (is_numeric($num)) {
                return (string)$num;
            }
            return (string)$val;
        };

        foreach ($data as $item) {
            if (!is_array($item) || !isset($item['stat'], $item['type'], $item['val'])) {
                continue;
            }
            $stat = $item['stat'];
            $type = $item['type'];
            $val = $item['val'];

            // Capture known defenses for backward compatibility
            if ($stat === 'pDef') {
                if (strtolower($type) === 'add') {
                    $pDef = is_numeric($val) ? (int)$val : $val;
                } elseif (strtolower($type) === 'enchant') {
                    $pDefEnchant = is_numeric($val) ? (int)$val : $val;
                }
            }
            if ($stat === 'mDef') {
                if (strtolower($type) === 'add') {
                    $mDef = is_numeric($val) ? (int)$val : $val;
                } elseif (strtolower($type) === 'enchant') {
                    $mDefEnchant = is_numeric($val) ? (int)$val : $val;
                }
            }

            // If this is an enchant entry with zero value, skip adding it to the stats list
            if (strtolower($type) === 'enchant' && is_numeric($val) && (int)$val === 0) {
                continue;
            }

            $label = $labels[$stat] ?? $stat;
            $formatted = $formatStat($stat, $type, $val);

            // Extra text used by some templates (e.g. enchant suffix handled separately)
            $extra = '';
            if (strtolower($type) === 'enchant' && is_numeric($val) && (int)$val > 0) {
                $extra = '+' . (int)$val . ' за заточку';
            }

            $stats[] = [
                'code' => $stat,
                'label' => $label,
                'type' => $type,
                'value' => $val,
                'formatted' => $formatted,
                'extra' => $extra,
            ];
        }

        $result['pDef'] = $pDef;
        $result['mDef'] = $mDef;
        $result['pDef_enchant'] = $pDefEnchant;
        $result['mDef_enchant'] = $mDefEnchant;
        $result['stats'] = $stats;

        return $result;
    }

    /**
     * Return distinct armor body parts (slots) present in armors table.
     * @return array<string>
     */
    public function getArmorBodyparts(): array
    {
        // Exclude jewelry bodyparts (these belong to the jewelry page)
        $sql = "SELECT DISTINCT bodypart FROM armors WHERE bodypart IS NOT NULL AND TRIM(bodypart) != '' AND UPPER(bodypart) NOT IN ('NECK','RBRACELET','REAR;LEAR','RFINGER;LFINGER') ORDER BY bodypart";
        $res = $this->db->query($sql);
        $parts = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $parts[] = strtoupper($row['bodypart']);
        }
        return $parts;
    }

    /**
     * Fetch armor pieces for a given bodypart grouped by crystal_type.
     * Empty or null crystal types are normalized to 'NG'.
     * @param string $bodypart
     * @return array<string,array<int,array<string,mixed>>>
     */
    public function getArmorsGroupedByCrystal(string $bodypart): array
    {
        $bpUpper = strtoupper(trim($bodypart));

        // Jewelry bodyparts that should appear on the jewelry page instead of the regular armors page
        $jewelryParts = ['NECK', 'RBRACELET', 'REAR;LEAR', 'RFINGER;LFINGER'];

        // If user requested the special 'jewelry' page, fetch only jewelry bodyparts
        if ($bpUpper === 'JEWELRY') {
            $in = "'" . implode("','", $jewelryParts) . "'";
            $sql = "SELECT * FROM armors WHERE UPPER(bodypart) IN (" . $in . ") ORDER BY crystal_type, name";
            $res = $this->db->query($sql);
        } else {
            // Default: request a specific bodypart.
            $stmt = $this->db->prepare('SELECT * FROM armors WHERE UPPER(bodypart) = :bp ORDER BY crystal_type, name');
            $stmt->bindValue(':bp', $bpUpper, SQLITE3_TEXT);
            $res = $stmt->execute();
        }
        $grouped = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            // Парсим поле 'for' для извлечения значений защиты
            $defenseData = $this->parseForField($row['for']);
            // Напрямую присваиваем значения защиты (без ведущего underscore)
            $row['pDef'] = $defenseData['pDef'];
            $row['mDef'] = $defenseData['mDef'];
            $row['pDef_enchant'] = $defenseData['pDef_enchant'];
            $row['mDef_enchant'] = $defenseData['mDef_enchant'];


            $crystal = $row['crystal_type'] ?? '';
            $crystal = trim((string) $crystal) === '' ? 'NG' : strtoupper($crystal);
            $grouped[$crystal][] = $row;
        }
        ksort($grouped);
        return $grouped;
    }

    /**
     * Fetch skill row by id from skills table. Returns null if not found.
     * Reused by armor logic to enrich item_skill JSON.
     */
    public function getSkillById(int $skillId): ?array
    {
        static $cache = [];
        if (isset($cache[$skillId])) return $cache[$skillId];
        $stmt = $this->db->prepare('SELECT id, name, icon, levels, cooltime, reusedelay as reuse_delay, reusedelay, castrange, isdebuff, "for" FROM skills WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $skillId, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $row = $res->fetchArray(SQLITE3_ASSOC);
        if ($row === false) {
            return $cache[$skillId] = null;
        }
        if (isset($row['for']) && $row['for'] !== '' && strtolower(trim($row['for'])) !== 'null') {
            [$effects, $effectText] = $this->parseSkillEffects($row['for']);
            $row['effects'] = $effects;
            $row['effect_text'] = $effectText;
        } else {
            $row['effects'] = [];
            $row['effect_text'] = null;
        }
        return $cache[$skillId] = $row;
    }

    /**
     * Robustly parse item_skill field to normalized list of [[skill_id,skill_level], ...] style associative arrays.
     * Mirrors WeaponRepository::parseItemSkillRaw to keep logic consistent between weapons and armors/jewelry.
     *
     * @param mixed $raw
     * @return array<int,array{skill_id:int,skill_level:int}>
     */
    public function parseItemSkillRaw($raw): array
    {
        if ($raw === null) return [];
        if (is_array($raw)) {
            $decoded = $raw;
        } else {
            $str = trim((string)$raw);
            if ($str === '' || strtolower($str) === 'null') return [];
            $decoded = json_decode($str, true);
            if (!is_array($decoded) && preg_match('/^[\[{].*[\]}]$/s', $str) && strpos($str, "'") !== false) {
                $alt = str_replace("'", '"', $str);
                $decoded = json_decode($alt, true);
            }
            if (!is_array($decoded)) {
                $pairs = [];
                if (preg_match_all('/(\d+)\s*[-:,; ]\s*(\d+)/', $str, $m, PREG_SET_ORDER)) {
                    foreach ($m as $mm) $pairs[] = [(int)$mm[1], (int)$mm[2]];
                } elseif (preg_match_all('/\b(\d{2,})\b/', $str, $m2)) {
                    foreach ($m2[1] as $idOnly) $pairs[] = [(int)$idOnly, 1];
                }
                if ($pairs) {
                    $tmp = [];
                    foreach ($pairs as [$sid, $lvl]) if ($sid > 0) $tmp[] = ['skill_id' => $sid, 'skill_level' => max(1, $lvl)];
                    $decoded = $tmp ?: $decoded;
                }
            }
            if (!is_array($decoded)) return [];
        }
        // Single object case
        $isAssocSingle = isset($decoded['skill_id']) || isset($decoded['id']);
        if ($isAssocSingle && (isset($decoded['skill_level']) || isset($decoded['level']))) {
            $decoded = [$decoded];
        }
        $result = [];
        $seen = [];
        foreach ($decoded as $entry) {
            if (!is_array($entry)) continue;
            $sid = $entry['skill_id'] ?? $entry['id'] ?? null;
            $lvl = $entry['skill_level'] ?? $entry['level'] ?? 1;
            $sid = (int)$sid;
            $lvl = (int)$lvl;
            if ($sid <= 0) continue;
            if ($lvl <= 0) $lvl = 1;
            $key = $sid . ':' . $lvl;
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $result[] = ['skill_id' => $sid, 'skill_level' => $lvl];
        }
        return $result;
    }

    /** Parse skills."for" JSON into list of effect entries and combined effect text */
    private function parseSkillEffects(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '' || strtolower($raw) === 'null') return [[], null];
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) return [[], null];
        $statObjects = [];
        $walk = function ($node) use (&$walk, &$statObjects) {
            if (is_array($node)) {
                $isStat = isset($node['stat']) && (isset($node['val']) || isset($node['value'])) && isset($node['tag']);
                if ($isStat) {
                    $statObjects[] = $node;
                } else {
                    foreach ($node as $v) $walk($v);
                }
            }
        };
        $walk($decoded);
        if (!$statObjects) return [[], null];
        $labels = StatLabels::all();
        $effects = [];
        foreach ($statObjects as $obj) {
            $stat = $obj['stat'] ?? null;
            $tag = strtolower((string)($obj['tag'] ?? ''));
            $valRaw = $obj['val'] ?? ($obj['value'] ?? null);
            if ($stat === null || $valRaw === null) continue;
            $label = $labels[$stat] ?? $stat;
            if (is_array($valRaw)) {
                // Defer level selection to caller (store raw_values array)
                $effects[] = [
                    'stat' => $stat,
                    'label' => $label,
                    'tag' => $tag,
                    'raw_values' => $valRaw,
                ];
                continue;
            }
            if (is_numeric($valRaw)) {
                $num = (float)$valRaw;
                if (in_array($tag, ['mul', 'multiply', 'mult'], true)) {
                    $formatted = '+' . $this->trimNumber($num * 100.0) . '%';
                } elseif (in_array($tag, ['pct', 'percent', 'percent_add', 'percent_sub', '%'], true) || preg_match('/(rate|chance|percent)/i', $stat)) {
                    if (abs($num) < 1 && $num != 0) $num *= 100;
                    $sign = $num >= 0 ? '+' : '';
                    $formatted = $sign . $this->trimNumber($num) . '%';
                } elseif ($tag === 'add' || $tag === 'enchant') {
                    $sign = $num >= 0 ? '+' : '';
                    $formatted = $sign . $this->trimNumber($num);
                } else {
                    $sign = $num >= 0 ? '+' : '';
                    $formatted = $sign . $this->trimNumber($num);
                }
            } else {
                $formatted = (string)$valRaw;
            }
            $effects[] = [
                'stat' => $stat,
                'label' => $label,
                'tag' => $tag,
                'raw' => $valRaw,
                'formatted' => $formatted,
                'text' => $formatted . ' ' . $label,
            ];
        }
        $effectTextParts = [];
        foreach ($effects as $e) if (isset($e['text'])) $effectTextParts[] = $e['text'];
        $effectText = $effectTextParts ? implode(', ', $effectTextParts) : null;
        return [$effects, $effectText];
    }

    private function trimNumber(float $num): string
    {
        $formatted = number_format($num, 2, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');
        return $formatted === '' ? '0' : $formatted;
    }
}
