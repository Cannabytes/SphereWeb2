<?php

namespace Ofey\Logan22\component\plugins\library;

use SQLite3;
use RuntimeException;

/**
 * Repository for reading armor set data (table: armorsets) from highfive.db
 * and enriching it with item & skill metadata from other tables.
 */
class ArmorSetRepository
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

    /** Simple helper to check table existence */
    private function tableExists(string $table): bool
    {
        if (!$this->db) return false;
        $stmt = $this->db->prepare('SELECT name FROM sqlite_master WHERE type = "table" AND name = :t LIMIT 1');
        $stmt->bindValue(':t', $table, SQLITE3_TEXT);
        $res = $stmt->execute();
        if (!$res) return false;
        $row = $res->fetchArray(SQLITE3_ASSOC);
        return $row !== false;
    }

    public function __destruct()
    {
        if ($this->db instanceof SQLite3) {
            $this->db->close();
        }
    }

    /** Fetch single armor (piece) by id from armors table. */
    public function getArmorById(int $id): ?array
    {
        static $cache = [];
        if (isset($cache[$id])) return $cache[$id];
        if (!$this->db) return null;
        $stmt = $this->db->prepare('SELECT id, name, icon, armor_type, bodypart, crystal_type, item_skill FROM armors WHERE id = :id LIMIT 1');
        if (!$stmt) {
            error_log('[ArmorSetRepository] prepare(getArmorById) failed: ' . $this->db->lastErrorMsg());
            return $cache[$id] = null;
        }
        if (!$stmt->bindValue(':id', $id, SQLITE3_INTEGER)) {
            error_log('[ArmorSetRepository] bindValue(getArmorById) failed: ' . $this->db->lastErrorMsg());
            return $cache[$id] = null;
        }
        $res = $stmt->execute();
        if (!$res) {
            error_log('[ArmorSetRepository] execute(getArmorById) failed: ' . $this->db->lastErrorMsg());
            return $cache[$id] = null;
        }
        $row = $res->fetchArray(SQLITE3_ASSOC);
        if ($row === false) return $cache[$id] = null;
        // Parse item_skill JSON (if present) into enriched skill entries
        $row['item_skills'] = $this->parseSkillList($row['item_skill'] ?? null);
        return $cache[$id] = $row;
    }

    /** Fetch skill metadata by id from skills table (+ parse 'for' effects). */
    public function getSkillById(int $skillId): ?array
    {
        static $cache = [];
        if (isset($cache[$skillId])) return $cache[$skillId];
        if (!$this->db) return null;
        // Note: DB column is 'reusedelay' (without underscore); select it and also alias to reuse_delay for consistency
        $stmt = $this->db->prepare('SELECT id, name, icon, levels, cooltime, reusedelay as reuse_delay, reusedelay, castrange, isdebuff, "for" FROM skills WHERE id = :id LIMIT 1');
        if (!$stmt) {
            error_log('[ArmorSetRepository] prepare(getSkillById) failed: ' . $this->db->lastErrorMsg());
            return $cache[$skillId] = null;
        }
        if (!$stmt->bindValue(':id', $skillId, SQLITE3_INTEGER)) {
            error_log('[ArmorSetRepository] bindValue(getSkillById) failed: ' . $this->db->lastErrorMsg());
            return $cache[$skillId] = null;
        }
        $res = $stmt->execute();
        if (!$res) {
            error_log('[ArmorSetRepository] execute(getSkillById) failed: ' . $this->db->lastErrorMsg());
            return $cache[$skillId] = null;
        }
        $row = $res->fetchArray(SQLITE3_ASSOC);
        if ($row === false) return $cache[$skillId] = null;
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
                // Defer formatting to level-specific phase
                $effects[] = [
                    'stat' => $stat,
                    'label' => $label,
                    'tag' => $tag,
                    'raw_values' => $valRaw,
                ];
            } else {
                $formatted = $this->formatEffectValue($tag, $stat, $valRaw);
                $effects[] = [
                    'stat' => $stat,
                    'label' => $label,
                    'tag' => $tag,
                    'raw' => $valRaw,
                    'formatted' => $formatted,
                    'text' => $formatted . ' ' . $label,
                ];
            }
        }
        // For meta-level (no concrete level) build generic text only for single-value entries
        $effectTextParts = [];
        foreach ($effects as $e) {
            if (isset($e['text'])) $effectTextParts[] = $e['text'];
        }
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

    /** Helper: decode a JSON value that may be empty string or already array. */
    private function decodeJsonArray(?string $raw): array
    {
        if ($raw === null) return [];
        $raw = trim($raw);
        if ($raw === '' || strtolower($raw) === 'null') return [];
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    /** Helper: decode a JSON array or accept already-decoded array of skill descriptors: [{id, level}] */
    private function parseSkillList($raw): array
    {
        // Accept already-decoded arrays (in case DB driver returned an array) or JSON/string
        $skills = is_array($raw) ? $raw : $this->decodeJsonArray($raw);
        $result = [];
        foreach ($skills as $s) {
            if (!is_array($s)) continue;
            // Accept either {id, level} or {skill_id, skill_level}
            $skillId = isset($s['id']) ? (int)$s['id'] : (isset($s['skill_id']) ? (int)$s['skill_id'] : null);
            $level = $s['level'] ?? $s['skill_level'] ?? null;
            if ($skillId === null || $level === null) continue;
            $meta = $this->getSkillById($skillId);
            $entry = [
                'id' => $skillId,
                'level' => $level,
            ];
            if ($meta) {
                $entry['name'] = $meta['name'] ?? null;
                $entry['icon'] = $meta['icon'] ?? null;
                if (!empty($meta['effects'])) {
                    [$lvlEffects, $lvlText] = $this->buildEffectsForLevel($meta['effects'], (int)$level);
                    $entry['effects'] = $lvlEffects;
                    if ($lvlText) $entry['effect_text'] = $lvlText;
                }
            }
            $result[] = $entry;
        }
        return $result;
    }

    /** Helper: decode a single skill object: {id, level} or accept already-decoded array */
    private function parseSingleSkill($raw): ?array
    {
        // If already an array
        if (is_array($raw)) {
            $obj = $raw;
        } else {
            if ($raw === null) return null;
            $raw = trim($raw);
            if ($raw === '' || strtolower($raw) === 'null') return null;
            $obj = json_decode($raw, true);
        }
        if (!is_array($obj)) return null;
        // Accept {id, level} or {skill_id, skill_level}
        $skillId = isset($obj['id']) ? (int)$obj['id'] : (isset($obj['skill_id']) ? (int)$obj['skill_id'] : null);
        $level = $obj['level'] ?? $obj['skill_level'] ?? null;
        if ($skillId === null || $level === null) return null;
        $meta = $this->getSkillById($skillId);
        $entry = [
            'id' => $skillId,
            'level' => $level,
        ];
        if ($meta) {
            $entry['name'] = $meta['name'] ?? null;
            $entry['icon'] = $meta['icon'] ?? null;
            if (!empty($meta['effects'])) {
                [$lvlEffects, $lvlText] = $this->buildEffectsForLevel($meta['effects'], (int)$level);
                $entry['effects'] = $lvlEffects;
                if ($lvlText) $entry['effect_text'] = $lvlText;
            }
        }
        return $entry;
    }

    /** Build level-specific formatted effects from meta effects (which may contain raw_values arrays). */
    private function buildEffectsForLevel(array $metaEffects, int $level): array
    {
        $effects = [];
        foreach ($metaEffects as $e) {
            if (isset($e['raw_values'])) {
                $values = $e['raw_values'];
                if (!is_array($values) || !$values) continue;
                $idx = max(0, $level - 1);
                $chosen = $values[$idx] ?? end($values);
                $formatted = $this->formatEffectValue($e['tag'] ?? 'add', $e['stat'] ?? '', $chosen);
                $effects[] = [
                    'stat' => $e['stat'] ?? null,
                    'label' => $e['label'] ?? ($e['stat'] ?? ''),
                    'tag' => $e['tag'] ?? 'add',
                    'raw' => $chosen,
                    'formatted' => $formatted,
                    'text' => $formatted . ' ' . ($e['label'] ?? ($e['stat'] ?? '')),
                ];
            } else {
                // Already formatted single value effect
                $effects[] = $e;
            }
        }
        $text = implode(', ', array_map(fn($x) => $x['text'] ?? ($x['formatted'] ?? ''), $effects));
        return [$effects, $text ?: null];
    }

    /** Return all armor sets grouped by grade letter (e.g. C, B). */
    public function getAllSetsGrouped(): array
    {
        // Fail-safe: if table doesn't exist just return empty array (avoid fatal error)
        if (!$this->tableExists('armorsets')) {
            // Optional: log once
            error_log('[ArmorSetRepository] Table "armorsets" not found in DB: ' . $this->dbPath);
            return [];
        }
        $sql = 'SELECT * FROM armorsets ORDER BY type, id';
        $res = $this->db->query($sql);
        if ($res === false) {
            error_log('[ArmorSetRepository] Query failed: ' . $this->db->lastErrorMsg());
            return [];
        }
        $grouped = [];
        while ($res && ($row = $res->fetchArray(SQLITE3_ASSOC))) {
            // Derive grade letter from type like "c_grade" -> "C"
            $typeRaw = (string)($row['type'] ?? '');
            $gradeLetter = strtoupper(substr($typeRaw, 0, 1));
            if ($gradeLetter === '') $gradeLetter = 'NG';

            // Chest mandatory
            $chestId = (int)$row['chest'];
            $chestItem = $chestId ? $this->getArmorById($chestId) : null;

            $slots = ['legs', 'head', 'gloves', 'feet', 'shield'];
            $parts = [];
            foreach ($slots as $slot) {
                $raw = trim((string)($row[$slot] ?? ''));
                if ($raw === '') continue; // slot not required
                $ids = json_decode($raw, true);
                if (!is_array($ids)) {
                    // Could be single numeric inside [] or plain numeric w/out JSON
                    if (preg_match('/^[0-9]+$/', $raw)) {
                        $ids = [(int)$raw];
                    } else {
                        $ids = [];
                    }
                }
                $items = [];
                foreach ($ids as $id) {
                    if (!is_numeric($id)) continue;
                    $item = $this->getArmorById((int)$id);
                    if ($item) $items[] = $item;
                }
                if ($items) $parts[$slot] = $items; // only add if we found items
            }

            // Skills
            $skills = $this->parseSkillList($row['skill'] ?? null);
            $shieldSkills = $this->parseSkillList($row['shield_skill'] ?? null);
            $enchant6Skill = $this->parseSingleSkill($row['enchant6skill'] ?? null);

            // Stats (attributes) – only non-zero values
            $attrCodes = ['str' => 'STR', 'int' => 'INT', 'con' => 'CON', 'men' => 'MEN', 'dex' => 'DEX', 'wit' => 'WIT'];
            $attributes = [];
            foreach ($attrCodes as $col => $label) {
                $val = $row[$col] ?? 0;
                if (is_numeric($val) && (int)$val !== 0) {
                    $attributes[] = [
                        'code' => $label,
                        'label' => $label,
                        'value' => (int)$val,
                        'formatted' => (($val > 0 ? '+' : '') . (int)$val),
                    ];
                }
            }

            $set = [
                'id' => (int)$row['id'],
                'type_raw' => $typeRaw,
                'grade' => $gradeLetter,
                'chest' => $chestItem,
                'parts' => $parts,
                'skills' => $skills,
                'shield_skills' => $shieldSkills,
                'enchant6_skill' => $enchant6Skill,
                'attributes' => $attributes,
            ];
            $grouped[$gradeLetter][] = $set;
        }
        // Ensure stable order inside each grade (by chest name)
        foreach ($grouped as $g => &$list) {
            usort($list, function ($a, $b) {
                return strcmp($a['chest']['name'] ?? '', $b['chest']['name'] ?? '');
            });
        }
        unset($list);
        return $grouped;
    }
}
