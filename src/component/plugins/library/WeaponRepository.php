<?php

namespace Ofey\Logan22\component\plugins\library;

use SQLite3;
use RuntimeException;
use Ofey\Logan22\component\image\client_icon;

/**
 * Lightweight repository for reading weapons data from local plugin SQLite DB (highfive.db).
 * Only read operations are implemented. Connection kept per instance; inexpensive for small queries.
 */
class WeaponRepository
{
    private string $dbPath;
    private ?SQLite3 $db = null;

    public function __construct(?string $dbPath = null)
    {
        // Default path inside plugin db directory
        $this->dbPath = $dbPath ?? __DIR__ . '/db/highfive.db';
        if (!is_file($this->dbPath)) {
            throw new RuntimeException('SQLite database not found: ' . $this->dbPath);
        }
        $this->open();
    }

    private function open(): void
    {
        $this->db = new SQLite3($this->dbPath, SQLITE3_OPEN_READONLY);
        // Enforce text encoding
        $this->db->exec('PRAGMA encoding = "UTF-8"');
    }

    public function __destruct()
    {
        if ($this->db instanceof SQLite3) {
            $this->db->close();
        }
    }

    /**
     * Return distinct weapon types (filtered business logic like original spec: excluding empty + unwanted types).
     * @return array<string>
     */
    public function getWeaponTypes(): array
    {
        $sql = "SELECT DISTINCT weapon_type FROM weapons WHERE weapon_type IS NOT NULL AND weapon_type NOT IN ('FLAG','OWNTHING','FIST') ORDER BY weapon_type";
        $res = $this->db->query($sql);
        $types = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            if (!empty($row['weapon_type'])) {
                $types[] = $row['weapon_type'];
            }
        }
        return $types;
    }

    /**
     * Fetch weapons by type (e.g. SWORD, BOW) grouped by crystal_type.
     * Returns associative array: [ crystal_type(string) => list<weaponRow> ]
     * Empty or null crystal types are normalized to 'NG'.
     * @param string $weaponType
     * @return array<string,array<int,array<string,mixed>>>
     */
    public function getWeaponsGroupedByCrystal(string $weaponType): array
    {
        // Build safe explicit column list only with columns that really exist to avoid prepare() = false
        $desired = ['id', 'name', 'for', 'item_skill', 'weapon_type', 'crystal_type', 'icon', 'price', 'is_magic_weapon', 'pAtk', 'mAtk', 'critRate', 'pAtkSpd'];
        $available = $this->getWeaponTableColumns();
        $use = array_values(array_intersect($desired, $available));
        if (!$use) {
            $selectCols = '*'; // fallback
        } else {
            $selectCols = implode(',', array_map(fn($c) => $c === 'for' ? '"for"' : $c, $use));
        }
        $sql = 'SELECT ' . $selectCols . ' FROM weapons WHERE UPPER(weapon_type) = :type ORDER BY crystal_type, name';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            // Final fallback: broad select * (should not fail unless table missing)
            $stmt = $this->db->prepare('SELECT * FROM weapons WHERE UPPER(weapon_type) = :type ORDER BY crystal_type, name');
            if (!$stmt) {
                return []; // give up gracefully
            }
        }
        $stmt->bindValue(':type', strtoupper($weaponType), SQLITE3_TEXT);
        $res = $stmt->execute();
        $grouped = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $crystal = $row['crystal_type'] ?? '';
            $crystal = trim((string)$crystal) === '' ? 'NG' : strtoupper($crystal);
            $grouped[$crystal][] = $row;
        }
        ksort($grouped); // alphabetical grade order; consumer can reorder if needed
        return $grouped;
    }

    /** Count weapons of a given type */
    public function countWeaponsByType(string $weaponType): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(1) AS cnt FROM weapons WHERE UPPER(weapon_type)=:t');
        if (!$stmt) return 0;
        $stmt->bindValue(':t', strtoupper($weaponType), SQLITE3_TEXT);
        $res = $stmt->execute();
        $row = $res?->fetchArray(SQLITE3_ASSOC);
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Return counts grouped by crystal (normalized, empty->NG)
     * @return array<string,int>
     */
    public function getWeaponCountsByGrade(string $weaponType): array
    {
        $stmt = $this->db->prepare('SELECT COALESCE(NULLIF(TRIM(UPPER(crystal_type)),""),"NG") AS g, COUNT(1) AS c FROM weapons WHERE UPPER(weapon_type)=:t GROUP BY g');
        if (!$stmt) return [];
        $stmt->bindValue(':t', strtoupper($weaponType), SQLITE3_TEXT);
        $res = $stmt->execute();
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            if (!isset($row['g'])) continue;
            $out[$row['g']] = (int)$row['c'];
        }
        return $out;
    }

    /**
     * Paginated weapon list (flat) ordered by custom crystal grade ordering then name.
     * Returns list of rows with an added key _grade (normalized crystal) for convenience.
     * @return array<int,array<string,mixed>>
     */
    public function getWeaponsPage(string $weaponType, int $offset, int $limit): array
    {
        $offset = max(0, $offset);
        $limit = max(1, $limit);
        $desired = ['id', 'name', 'for', 'item_skill', 'weapon_type', 'crystal_type', 'icon', 'price', 'is_magic_weapon', 'pAtk', 'mAtk', 'critRate', 'pAtkSpd'];
        $available = $this->getWeaponTableColumns();
        $use = array_values(array_intersect($desired, $available));
        $selectCols = $use ? implode(',', array_map(fn($c) => $c === 'for' ? '"for"' : $c, $use)) : '*';
        $case = "CASE UPPER(COALESCE(NULLIF(TRIM(crystal_type),''),'NG'))\n            WHEN 'R' THEN 1 WHEN 'S84' THEN 2 WHEN 'S80' THEN 3 WHEN 'S' THEN 4 WHEN 'A' THEN 5 WHEN 'B' THEN 6 WHEN 'C' THEN 7 WHEN 'D' THEN 8 ELSE 9 END";
        $sql = 'SELECT ' . $selectCols . ' FROM weapons WHERE UPPER(weapon_type)=:t ORDER BY ' . $case . ', name LIMIT :lim OFFSET :off';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];
        $stmt->bindValue(':t', strtoupper($weaponType), SQLITE3_TEXT);
        $stmt->bindValue(':lim', $limit, SQLITE3_INTEGER);
        $stmt->bindValue(':off', $offset, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $rows = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $grade = $row['crystal_type'] ?? '';
            $grade = trim((string)$grade) === '' ? 'NG' : strtoupper($grade);
            $row['_grade'] = $grade;
            $rows[] = $row;
        }
        return $rows;
    }

    private function getWeaponTableColumns(): array
    {
        static $cols = null;
        if ($cols !== null) return $cols;
        $cols = [];
        $res = $this->db->query('PRAGMA table_info(weapons)');
        if ($res) {
            while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
                if (!empty($row['name'])) $cols[] = $row['name'];
            }
        }
        return $cols;
    }

    /**
     * Robustly parse item_skill field to normalized list:[{skill_id:int, skill_level:int}]
     * Accepts variations:
     *  - JSON array of objects with skill_id/skill_level
     *  - JSON array of objects with id/level
     *  - Single JSON object {skill_id,skill_level}
     *  - Raw string containing JSON (trim whitespace)
     */
    public function parseItemSkillRaw($raw): array
    {
        if ($raw === null) return [];

        // 1. Fast path: already structured array
        if (is_array($raw)) {
            $decoded = $raw;
        } else {
            $str = trim((string)$raw);
            if ($str === '' || strtolower($str) === 'null') return [];

            // Try plain JSON first
            $decoded = json_decode($str, true);

            // If JSON failed, attempt a very light normalisation: replace single quotes with double quotes
            // ONLY if it looks like JSON but used single quotes.
            if (!is_array($decoded) && preg_match('/^[\[{].*[\]}]$/s', $str) && strpos($str, "'") !== false) {
                $alt = str_replace("'", '"', $str);
                $decoded = json_decode($alt, true);
            }

            // If still not an array – attempt to parse custom compact formats used in legacy data dumps:
            // Examples we try to support:
            //  "3552-1;3654-1;3047-3"  |  "3552:1,3654:1,3047:3"  |  "3552 1 3654 1 3047 3"  |  "3552;3654;3047" (default level 1)
            if (!is_array($decoded)) {
                $pairs = [];
                // Pattern for id-level pairs separated by non-digit delimiters
                if (preg_match_all('/(\d+)\s*[-:,; ]\s*(\d+)/', $str, $m, PREG_SET_ORDER)) {
                    foreach ($m as $mm) {
                        $pairs[] = [(int)$mm[1], (int)$mm[2]];
                    }
                } elseif (preg_match_all('/\b(\d{2,})\b/', $str, $m2)) { // fallback: just a list of ids (level=1)
                    foreach ($m2[1] as $idOnly) {
                        $pairs[] = [(int)$idOnly, 1];
                    }
                }
                if ($pairs) {
                    $decoded = [];
                    foreach ($pairs as [$sid, $lvl]) {
                        if ($sid <= 0) continue;
                        $decoded[] = ['skill_id' => $sid, 'skill_level' => max(1, $lvl)];
                    }
                }
            }

            if (!is_array($decoded)) return []; // give up – unrecognised format
        }

        // 2. Normalise: single associative object -> wrap
        $isAssocSingle = isset($decoded['skill_id']) || isset($decoded['id']);
        if ($isAssocSingle && (isset($decoded['skill_level']) || isset($decoded['level']))) {
            $decoded = [$decoded];
        }

        // 3. Canonical result list
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
            // Deduplicate (some sources repeat same skill multiple times)
            $key = $sid . ':' . $lvl;
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $result[] = ['skill_id' => $sid, 'skill_level' => $lvl];
        }
        return $result;
    }

    /**
     * Build unified web path for a skill icon name using client_icon::getIcon
     */
    public static function resolveSkillIcon(?string $icon): string
    {
        if ($icon === null || $icon === '') {
            return client_icon::getIcon('', 'skills');
        }
        // strip extension if present
        if (pathinfo($icon, PATHINFO_EXTENSION) === 'webp') {
            $icon = pathinfo($icon, PATHINFO_FILENAME);
        }
        return client_icon::getIcon($icon, 'skills');
    }

    /**
     * Fetch skill row by id from skills table. Returns null if not found.
     * @param int $skillId
     * @return array|null
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
                $effects[] = ['stat' => $stat, 'label' => $label, 'tag' => $tag, 'raw_values' => $valRaw];
            } else {
                $formatted = $this->formatEffectValue($tag, $stat, $valRaw);
                $effects[] = ['stat' => $stat, 'label' => $label, 'tag' => $tag, 'raw' => $valRaw, 'formatted' => $formatted, 'text' => $formatted . ' ' . $label];
            }
        }
        $effectTextParts = [];
        foreach ($effects as $e) if (isset($e['text'])) $effectTextParts[] = $e['text'];
        $effectText = $effectTextParts ? implode(', ', $effectTextParts) : null;
        return [$effects, $effectText];
    }

    private function formatEffectValue(string $tag, string $stat, $val): string
    {
        if (!is_numeric($val)) return (string)$val;
        $num = (float)$val;
        if (in_array($tag, ['mul', 'multiply', 'mult'], true)) {
            $abs = $num * 100.0;
            return '+' . $this->trimNumber($abs) . '%';
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
}
