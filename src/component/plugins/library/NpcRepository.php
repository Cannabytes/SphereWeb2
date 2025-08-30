<?php

namespace Ofey\Logan22\component\plugins\library;

use RuntimeException;
use SQLite3;

/**
 * Repository for reading NPC data from plugin SQLite DB (highfive.db).
 * Read-only operations; lightweight similar to other repositories.
 */
class NpcRepository
{
    private string $dbPath;
    private ?SQLite3 $db = null;
    /**
     * In-memory cache for skill metadata to avoid repeated DB queries during request.
     * Keys are skill IDs, values are arrays with keys: name, icon, isdebuff
     * @var array<int,array<string,mixed>>
     */
    private array $skillCache = [];
    /**
     * Static shared cache across repository instances (within same PHP process) to avoid
     * re-querying skills table on multiple page components during one request lifecycle.
     * Key: skill id, Value: meta row or null (if not found).
     */
    private static array $globalSkillCache = [];
    /**
     * Simple caches and helpers
     */

    /**
     * Cache for extractSkillIds(): raw skillList JSON -> array of ids
     * @var array<string,array<int>>
     */
    private array $extractCache = [];

    /**
     * Cache parsed skill effect metadata per skill id: skillId -> [metaEffects, metaEffectText]
     * @var array<int,array>
     */
    private array $skillEffectsCache = [];

    /**
     * Cache level-specific built effects per skill id and level: "{skillId}:{level}" -> [effects, text]
     * @var array<string,array>
     */
    private array $skillEffectsByLevelCache = [];

    public function __construct(?string $dbPath = null)
    {
        $this->dbPath = $dbPath ?? __DIR__ . '/db/highfive.db';
        // no debug logging here
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
     * Fetch all NPCs (legacy full-load; use cautiously for large DB).
     */
    public function getAll(): array
    {
        $res = $this->db->query('SELECT * FROM npcs ORDER BY CAST(level AS INTEGER) ASC, name');
        $rows = [];
        $skillIds = [];
        $count = 0;
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $count++;
            // Collect skill ids first (cheap JSON decode) for bulk preload
            $skillIds = array_merge($skillIds, $this->extractSkillIds($row['skillList'] ?? ''));
            $rows[] = $row;
        }
        if ($skillIds) {
            $metaMap = $this->loadSkillMetaBulk($skillIds);
            $this->attachSkillsToRows($rows, $metaMap, 12);
        } else {
            // ensure skillList removed even if empty
            foreach ($rows as &$r) {
                unset($r['skillList']);
                $r['skills'] = [];
            }
        }
        return $rows;
    }

    /**
     * Fetch single NPC by id with full skill list (no per-NPC limit) and parsed skill meta.
     * Returns associative array or null if not found.
     * @param int $id
     * @return array<string,mixed>|null
     */
    public function findById(int $id): ?array
    {
        if ($id <= 0) return null;
        $stmt = $this->db->prepare('SELECT * FROM npcs WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $row = $res->fetchArray(SQLITE3_ASSOC);
        if ($row === false) return null;

        // Preload all skills for this NPC (no limit) for efficiency.
        $skillIds = $this->extractSkillIds($row['skillList'] ?? '');
        if ($skillIds) {
            $this->loadSkillMetaBulk($skillIds); // populates caches
            // parseSkillList() will now hit in-memory cache
            $skills = $this->parseSkillList($row['skillList'] ?? '', 0); // 0 = unlimited
        } else {
            $skills = [];
        }
        unset($row['skillList']);
        $row['skills'] = $skills;
        $row['skill_count'] = count($skills);
        return $row;
    }

    public function countAll(): int
    {
        return (int)$this->db->querySingle('SELECT COUNT(*) FROM npcs');
    }

    public function countFiltered(?string $search): int
    {
        if (!$search) return $this->countAll();
        $stmt = $this->db->prepare('SELECT COUNT(*) AS c FROM npcs WHERE name LIKE :s OR title LIKE :s');
        $stmt->bindValue(':s', '%' . $search . '%', SQLITE3_TEXT);
        $res = $stmt->execute();
        $row = $res->fetchArray(SQLITE3_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    /**
     * Count with optional search and type filtering.
     * $types may be an array of allowed types (IN) or a string starting with '!' to indicate exclusion.
     */
    public function countFilteredWithType(?string $search, ?array $types = null, bool $exclude = false): int
    {
        $where = [];
        $params = [];
        if ($search) {
            $where[] = '(name LIKE :s OR title LIKE :s)';
            $params[':s'] = '%' . $search . '%';
        }
        if ($types !== null && count($types) > 0) {
            $placeholders = [];
            foreach ($types as $i => $t) {
                $ph = ':t' . $i;
                $placeholders[] = $ph;
                $params[$ph] = $t;
            }
            $op = $exclude ? 'NOT IN' : 'IN';
            $where[] = 'type ' . $op . ' (' . implode(',', $placeholders) . ')';
        }
        $sql = 'SELECT COUNT(*) AS c FROM npcs' . (count($where) ? ' WHERE ' . implode(' AND ', $where) : '');
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, SQLITE3_TEXT);
        }
        $res = $stmt->execute();
        $row = $res->fetchArray(SQLITE3_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    /**
     * Paginated fetch for DataTables.
     */
    public function getPage(int $start, int $length, ?string $search, string $orderCol, string $orderDir): array
    {
        $allowedCols = ['id', 'name', 'level', 'type', 'race', 'hp', 'mp', 'attack_physical', 'attack_magical', 'defence_physical', 'defence_magical', 'attack_attack_speed', 'attack_critical', 'attack_accuracy', 'defence_evasion'];
        if (!in_array($orderCol, $allowedCols, true)) $orderCol = 'level';
        $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
        $start = max(0, $start);
        $length = $length <= 0 ? 25 : min(500, $length);
        $where = '';
        if ($search) {
            $where = 'WHERE name LIKE :s OR title LIKE :s';
        }
        if ($orderCol === 'level') {
            $order = "ORDER BY CAST(level AS INTEGER) $orderDir, name";
        } else {
            $order = "ORDER BY $orderCol $orderDir";
        }
        $sql = "SELECT * FROM npcs $where $order LIMIT :len OFFSET :start";
        $stmt = $this->db->prepare($sql);
        if ($search) {
            $stmt->bindValue(':s', '%' . $search . '%', SQLITE3_TEXT);
        }
        $stmt->bindValue(':len', $length, SQLITE3_INTEGER);
        $stmt->bindValue(':start', $start, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $rawRows = [];
        $skillIds = [];
        $count = 0;
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $count++;
            $skillIds = array_merge($skillIds, $this->extractSkillIds($row['skillList'] ?? ''));
            $rawRows[] = $row;
        }
        if ($skillIds) {
            $metaMap = $this->loadSkillMetaBulk($skillIds);
            $this->attachSkillsToRows($rawRows, $metaMap, 12);
        } else {
            foreach ($rawRows as &$r) {
                unset($r['skillList']);
                $r['skills'] = [];
            }
        }
        return $rawRows;
    }

    /**
     * Paginated fetch with optional type filtering (include or exclude types).
     * @param int $start
     * @param int $length
     * @param string|null $search
     * @param string $orderCol
     * @param string $orderDir
     * @param array|null $types
     * @param bool $exclude
     * @return array
     */
    public function getPageWithType(int $start, int $length, ?string $search, string $orderCol, string $orderDir, ?array $types = null, bool $exclude = false): array
    {
        $allowedCols = ['id', 'name', 'level', 'type', 'race', 'hp', 'mp', 'attack_physical', 'attack_magical', 'defence_physical', 'defence_magical', 'attack_attack_speed', 'attack_critical', 'attack_accuracy', 'defence_evasion'];
        if (!in_array($orderCol, $allowedCols, true)) $orderCol = 'level';
        $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
        $start = max(0, $start);
        $length = $length <= 0 ? 25 : min(500, $length);
        $whereParts = [];
        if ($search) {
            $whereParts[] = '(name LIKE :s OR title LIKE :s)';
        }
        if ($types !== null && count($types) > 0) {
            $phs = [];
            foreach ($types as $i => $t) {
                $ph = ':t' . $i;
                $phs[] = $ph;
            }
            $op = $exclude ? 'NOT IN' : 'IN';
            $whereParts[] = 'type ' . $op . ' (' . implode(',', $phs) . ')';
        }
        if ($orderCol === 'level') {
            $order = "ORDER BY CAST(level AS INTEGER) $orderDir, name";
        } else {
            $order = "ORDER BY $orderCol $orderDir";
        }
        $sql = "SELECT * FROM npcs" . (count($whereParts) ? ' WHERE ' . implode(' AND ', $whereParts) : '') . " $order LIMIT :len OFFSET :start";
        $stmt = $this->db->prepare($sql);
        if ($search) {
            $stmt->bindValue(':s', '%' . $search . '%', SQLITE3_TEXT);
        }
        if ($types !== null && count($types) > 0) {
            foreach ($types as $i => $t) {
                $stmt->bindValue(':t' . $i, $t, SQLITE3_TEXT);
            }
        }
        $stmt->bindValue(':len', $length, SQLITE3_INTEGER);
        $stmt->bindValue(':start', $start, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $rawRows = [];
        $skillIds = [];
        $count = 0;
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $count++;
            $skillIds = array_merge($skillIds, $this->extractSkillIds($row['skillList'] ?? ''));
            $rawRows[] = $row;
        }
        if ($skillIds) {
            $metaMap = $this->loadSkillMetaBulk($skillIds);
            $this->attachSkillsToRows($rawRows, $metaMap, 12);
        } else {
            foreach ($rawRows as &$r) {
                unset($r['skillList']);
                $r['skills'] = [];
            }
        }
        return $rawRows;
    }

    /**
     * Count with optional type include/exclude AND optional level range.
     */
    public function countFilteredWithTypeAndLevel(?array $types = null, bool $exclude = false, ?string $search = null, ?int $minLevel = null, ?int $maxLevel = null): int
    {
        $where = [];
        $params = [];
        if ($search) {
            $where[] = '(name LIKE :s OR title LIKE :s)';
            $params[':s'] = '%' . $search . '%';
        }
        if ($types !== null && count($types) > 0) {
            $parts = [];
            foreach ($types as $i => $t) {
                $ph = ':t' . $i;
                $params[$ph] = $t;
                $parts[] = 'type = ' . $ph;
            }
            $expr = '(' . implode(' OR ', $parts) . ')';
            if ($exclude) $expr = 'NOT ' . $expr;
            $where[] = $expr;
        }
        if ($minLevel !== null && $maxLevel !== null) {
            $where[] = 'CAST(level AS INTEGER) BETWEEN :minLevel AND :maxLevel';
            $params[':minLevel'] = $minLevel;
            $params[':maxLevel'] = $maxLevel;
        } elseif ($minLevel !== null) {
            $where[] = 'CAST(level AS INTEGER) >= :minLevel';
            $params[':minLevel'] = $minLevel;
        } elseif ($maxLevel !== null) {
            $where[] = 'CAST(level AS INTEGER) <= :maxLevel';
            $params[':maxLevel'] = $maxLevel;
        }
        $sql = 'SELECT COUNT(*) AS c FROM npcs' . (count($where) ? ' WHERE ' . implode(' AND ', $where) : '');
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? SQLITE3_INTEGER : SQLITE3_TEXT);
        }
        $res = $stmt->execute();
        $row = $res->fetchArray(SQLITE3_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    /**
     * Paginated fetch with type filters and optional level min/max.
     */
    public function getPageWithTypeAndLevel(int $start, int $length, ?string $search, string $orderCol, string $orderDir, ?array $types = null, bool $exclude = false, ?int $minLevel = null, ?int $maxLevel = null): array
    {
        $allowedCols = ['id', 'name', 'level', 'type', 'race', 'hp', 'mp', 'attack_physical', 'attack_magical', 'defence_physical', 'defence_magical', 'attack_attack_speed', 'attack_critical', 'attack_accuracy', 'defence_evasion'];
        if (!in_array($orderCol, $allowedCols, true)) $orderCol = 'level';
        $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
        $start = max(0, $start);
        $length = $length <= 0 ? 30 : min(500, $length);
        $where = [];
        $params = [];
        if ($search) {
            $where[] = '(name LIKE :s OR title LIKE :s)';
            $params[':s'] = '%' . $search . '%';
        }
        if ($types !== null && count($types) > 0) {
            $parts = [];
            foreach ($types as $i => $t) {
                $ph = ':t' . $i;
                $params[$ph] = $t;
                $parts[] = 'type = ' . $ph;
            }
            $expr = '(' . implode(' OR ', $parts) . ')';
            if ($exclude) $expr = 'NOT ' . $expr;
            $where[] = $expr;
        }
        if ($minLevel !== null && $maxLevel !== null) {
            $where[] = 'CAST(level AS INTEGER) BETWEEN :minLevel AND :maxLevel';
            $params[':minLevel'] = $minLevel;
            $params[':maxLevel'] = $maxLevel;
        } elseif ($minLevel !== null) {
            $where[] = 'CAST(level AS INTEGER) >= :minLevel';
            $params[':minLevel'] = $minLevel;
        } elseif ($maxLevel !== null) {
            $where[] = 'CAST(level AS INTEGER) <= :maxLevel';
            $params[':maxLevel'] = $maxLevel;
        }
        if ($orderCol === 'level') {
            $order = "ORDER BY CAST(level AS INTEGER) $orderDir, name";
        } else {
            $order = "ORDER BY $orderCol $orderDir";
        }
        $sql = 'SELECT * FROM npcs' . (count($where) ? ' WHERE ' . implode(' AND ', $where) : '') . " $order LIMIT :len OFFSET :start";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? SQLITE3_INTEGER : SQLITE3_TEXT);
        }
        $stmt->bindValue(':len', $length, SQLITE3_INTEGER);
        $stmt->bindValue(':start', $start, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $rawRows = [];
        $skillIds = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $skillIds = array_merge($skillIds, $this->extractSkillIds($row['skillList'] ?? ''));
            $rawRows[] = $row;
        }
        if ($skillIds) {
            $metaMap = $this->loadSkillMetaBulk($skillIds);
            $this->attachSkillsToRows($rawRows, $metaMap, 12);
        } else {
            foreach ($rawRows as &$r) {
                unset($r['skillList']);
                $r['skills'] = [];
            }
        }
        return $rawRows;
    }

    /**
     * Fetch all rows optionally filtered by types (supports exact and LIKE patterns) and optional search.
     * Useful for server-side rendered pages.
     * @param array|null $types array of values or patterns (use % for LIKE)
     * @param bool $exclude if true, exclude the matching types
     * @param string|null $search search term applied to name/title
     * @param string $orderCol
     * @param string $orderDir
     * @return array
     */
    public function getAllWithType(?array $types = null, bool $exclude = false, ?string $search = null, string $orderCol = 'level', string $orderDir = 'ASC', ?int $minLevel = null, ?int $maxLevel = null): array
    {
        $allowedCols = ['id', 'name', 'level', 'type', 'race', 'hp', 'mp', 'attack_physical', 'attack_magical', 'defence_physical', 'defence_magical', 'attack_attack_speed', 'attack_critical', 'attack_accuracy', 'defence_evasion'];
        if (!in_array($orderCol, $allowedCols, true)) $orderCol = 'level';
        $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

        $whereParts = [];
        $params = [];
        if ($search) {
            $whereParts[] = '(name LIKE :s OR title LIKE :s)';
            $params[':s'] = '%' . $search . '%';
        }
        if ($types !== null && count($types) > 0) {
            $parts = [];
            foreach ($types as $i => $t) {
                $ph = ':t' . $i;
                $params[$ph] = mb_strtoupper($t, 'UTF-8');
                if (strpos($t, '%') !== false || strpos($t, '_') !== false) {
                    $parts[] = "UPPER(type) LIKE $ph";
                } else {
                    $parts[] = "UPPER(type) = $ph";
                }
            }
            $expr = '(' . implode(' OR ', $parts) . ')';
            if ($exclude) {
                $whereParts[] = 'NOT ' . $expr;
            } else {
                $whereParts[] = $expr;
            }
        }

        // Level filtering (levels are stored as text; cast to integer)
        if ($minLevel !== null && $maxLevel !== null) {
            $whereParts[] = 'CAST(level AS INTEGER) BETWEEN :minLevel AND :maxLevel';
            $params[':minLevel'] = $minLevel;
            $params[':maxLevel'] = $maxLevel;
        } elseif ($minLevel !== null) {
            $whereParts[] = 'CAST(level AS INTEGER) >= :minLevel';
            $params[':minLevel'] = $minLevel;
        } elseif ($maxLevel !== null) {
            $whereParts[] = 'CAST(level AS INTEGER) <= :maxLevel';
            $params[':maxLevel'] = $maxLevel;
        }

        if ($orderCol === 'level') {
            $order = "ORDER BY CAST(level AS INTEGER) $orderDir, name";
        } else {
            $order = "ORDER BY $orderCol $orderDir";
        }

        $sql = "SELECT * FROM npcs" . (count($whereParts) ? ' WHERE ' . implode(' AND ', $whereParts) : '') . " $order";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, SQLITE3_TEXT);
        }
        $res = $stmt->execute();
        $rawRows = [];
        $skillIds = [];
        $count = 0;
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $count++;
            $skillIds = array_merge($skillIds, $this->extractSkillIds($row['skillList'] ?? ''));
            $rawRows[] = $row;
        }
        if ($skillIds) {
            $metaMap = $this->loadSkillMetaBulk($skillIds);
            $this->attachSkillsToRows($rawRows, $metaMap, 12);
        } else {
            foreach ($rawRows as &$r) {
                unset($r['skillList']);
                $r['skills'] = [];
            }
        }
        return $rawRows;
    }

    /**
     * Parse skillList JSON and fetch meta for each skill.
     * @param string $json
     * @return array<int,array<string,mixed>>
     */
    private function parseSkillList(string $json, int $limit = 0): array
    {
        if (!$json) return [];
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) return [];
        $skills = [];
        foreach ($decoded as $s) {
            if (!isset($s['id'])) continue;
            $id = (int)$s['id'];
            $level = $s['level'] ?? null;
            // Use cached meta to reduce DB hits
            $meta = $this->getSkillMetaById($id);
            $skill = [
                'id' => $id,
                'level' => $level,
            ];
            // Из $meta['description'] получить элемент массива который находится на позиции $level-1
            $desc = null;
            if ($meta && isset($meta['description']) && is_string($meta['description'])) {
                $description = json_decode($meta['description'], true);
                if (is_array($description)) {
                    // If only one description entry provided, use it for any level (requirement)
                    if (count($description) === 1) {
                        $only = reset($description);
                        if (is_string($only) && $only !== '') {
                            $desc = trim($only);
                        }
                    } elseif ($level !== null && isset($description[$level - 1])) {
                        $desc = trim($description[$level - 1]);
                    }
                }
            }

            if ($meta) {
                // Only attach fields we need from skills table (cached)
                $skill['name'] = $meta['name'] ?? null;
                $skill['icon'] = $meta['icon'] ?? null;
                $skill['isdebuff'] = $meta['isdebuff'] ?? null;
                $skill['description'] = $desc;
                $skill['for'] = $meta['for'] ?? null;
                // Parse effects from "for" JSON (same logic style as Armor/Weapon repositories)
                if (!empty($meta['for']) && strtolower(trim($meta['for'])) !== 'null') {
                    // use cached parsed metadata for this skill 'for' payload
                    $forRaw = $meta['for'];
                    if (isset($this->skillEffectsCache[$id])) {
                        [$metaEffects, $metaEffectText] = $this->skillEffectsCache[$id];
                    } else {
                        [$metaEffects, $metaEffectText] = $this->parseSkillEffects($forRaw, $id);
                        $this->skillEffectsCache[$id] = [$metaEffects, $metaEffectText];
                    }
                    if ($metaEffects) {
                        // Build level-specific formatted effects (handles raw_values arrays)
                        $levelInt = (int)($level ?? 1);
                        $lvlKey = $id . ':' . $levelInt;
                        if (isset($this->skillEffectsByLevelCache[$lvlKey])) {
                            [$lvlEffects, $lvlText] = $this->skillEffectsByLevelCache[$lvlKey];
                        } else {
                            [$lvlEffects, $lvlText] = $this->buildEffectsForLevel($metaEffects, $levelInt, $id);
                            $this->skillEffectsByLevelCache[$lvlKey] = [$lvlEffects, $lvlText];
                        }
                        if ($lvlEffects) {
                            $skill['effects'] = $lvlEffects;
                            if ($lvlText) $skill['effect_text'] = $lvlText;
                        } elseif ($metaEffectText) { // fallback generic text
                            $skill['effect_text'] = $metaEffectText;
                        }
                    } elseif ($metaEffectText) {
                        $skill['effect_text'] = $metaEffectText;
                    }
                }
            }
            $skills[] = $skill;
            if ($limit > 0 && count($skills) >= $limit) break;
        }
        return $skills;
    }

    /**
     * Bulk load skill meta for unique skill IDs with chunking (returns id=>meta map).
     * Reuses existing caches and populates them if new rows loaded.
     * @param int[] $ids
     * @return array<int,array|null>
     */
    private function loadSkillMetaBulk(array $ids): array
    {
        $result = [];
        if (!$ids) return $result;
        $unique = [];
        foreach ($ids as $id) {
            $i = (int)$id;
            if ($i <= 0) continue;
            $unique[$i] = true;
        }
        if (!$unique) return $result;
        $idList = array_keys($unique);
        $toQuery = [];
        foreach ($idList as $i) {
            if (array_key_exists($i, $this->skillCache)) {
                $result[$i] = $this->skillCache[$i];
            } elseif (array_key_exists($i, self::$globalSkillCache)) {
                $result[$i] = self::$globalSkillCache[$i];
            } else {
                $toQuery[] = $i;
            }
        }
        if ($toQuery) {
            $chunkSize = 800;
            for ($off = 0; $off < count($toQuery); $off += $chunkSize) {
                $chunk = array_slice($toQuery, $off, $chunkSize);
                if (!$chunk) continue;
                $placeholders = implode(',', array_fill(0, count($chunk), '?'));
                $sql = 'SELECT id, name, icon, isdebuff, for, description FROM skills WHERE id IN (' . $placeholders . ')';
                $stmt = $this->db->prepare($sql);
                foreach ($chunk as $idx => $sid) {
                    $stmt->bindValue($idx + 1, $sid, SQLITE3_INTEGER);
                }
                $res = $stmt->execute();
                $foundIds = [];
                while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
                    $sid = (int)$row['id'];
                    if (isset($row['isdebuff'])) {
                        $val = $row['isdebuff'];
                        if ($val === '0' || $val === 0 || $val === 'false' || $val === '') $row['isdebuff'] = false;
                        else $row['isdebuff'] = (bool)$val;
                    }
                    $this->skillCache[$sid] = $row;
                    self::$globalSkillCache[$sid] = $row;
                    $result[$sid] = $row;
                    $foundIds[$sid] = true;
                }
                // mark missing as null
                foreach ($chunk as $sid) {
                    if (!isset($foundIds[$sid])) {
                        $this->skillCache[$sid] = null;
                        self::$globalSkillCache[$sid] = null;
                        $result[$sid] = null;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Attach parsed skills to NPC rows using preloaded meta map (id=>meta). Modifies rows by reference.
     * @param array<int,array<string,mixed>> $rows
     * @param array<int,array|null> $skillMetaMap
     * @param int $limitPerNpc
     */
    private function attachSkillsToRows(array &$rows, array $skillMetaMap, int $limitPerNpc = 12): void
    {
        foreach ($rows as &$row) {
            $json = $row['skillList'] ?? '';
            $decoded = $json ? json_decode($json, true) : null;
            $skills = [];
            if (is_array($decoded)) {
                foreach ($decoded as $s) {
                    if (!isset($s['id'])) continue;
                    $id = (int)$s['id'];
                    $level = $s['level'] ?? null;
                    $meta = $skillMetaMap[$id] ?? null;
                    $skill = ['id' => $id, 'level' => $level];
                    $desc = null;
                    if ($meta && isset($meta['description']) && is_string($meta['description'])) {
                        $description = json_decode($meta['description'], true);
                        if (is_array($description)) {
                            if (count($description) === 1) {
                                $only = reset($description);
                                if (is_string($only) && $only !== '') $desc = trim($only);
                            } elseif ($level !== null && isset($description[$level - 1])) {
                                $desc = trim($description[$level - 1]);
                            }
                        }
                    }
                    if ($meta) {
                        $skill['name'] = $meta['name'] ?? null;
                        $skill['icon'] = $meta['icon'] ?? null;
                        $skill['isdebuff'] = $meta['isdebuff'] ?? null;
                        $skill['description'] = $desc;
                        $skill['for'] = $meta['for'] ?? null;
                        if (!empty($meta['for']) && strtolower(trim($meta['for'])) !== 'null') {
                            if (isset($this->skillEffectsCache[$id])) {
                                [$metaEffects, $metaEffectText] = $this->skillEffectsCache[$id];
                            } else {
                                [$metaEffects, $metaEffectText] = $this->parseSkillEffects($meta['for'], $id);
                                $this->skillEffectsCache[$id] = [$metaEffects, $metaEffectText];
                            }
                            if ($metaEffects) {
                                $levelInt = (int)($level ?? 1);
                                $lvlKey = $id . ':' . $levelInt;
                                if (isset($this->skillEffectsByLevelCache[$lvlKey])) {
                                    [$lvlEffects, $lvlText] = $this->skillEffectsByLevelCache[$lvlKey];
                                } else {
                                    [$lvlEffects, $lvlText] = $this->buildEffectsForLevel($metaEffects, $levelInt, $id);
                                    $this->skillEffectsByLevelCache[$lvlKey] = [$lvlEffects, $lvlText];
                                }
                                if ($lvlEffects) {
                                    $skill['effects'] = $lvlEffects;
                                    if ($lvlText) $skill['effect_text'] = $lvlText;
                                } elseif ($metaEffectText) {
                                    $skill['effect_text'] = $metaEffectText;
                                }
                            } elseif ($metaEffectText ?? null) {
                                $skill['effect_text'] = $metaEffectText;
                            }
                        }
                    }
                    $skills[] = $skill;
                    if ($limitPerNpc > 0 && count($skills) >= $limitPerNpc) break;
                }
            }
            $row['skills'] = $skills;
            unset($row['skillList']);
        }
    }

    /**
     * Fetch minimal skill metadata (name, icon, isdebuff) with in-memory caching.
     * @param int $skillId
     * @return array<string,mixed>|null
     */
    public function getSkillMetaById(int $skillId): ?array
    {
        if (array_key_exists($skillId, $this->skillCache)) return $this->skillCache[$skillId];
        if (array_key_exists($skillId, self::$globalSkillCache)) return self::$globalSkillCache[$skillId];
        $stmt = $this->db->prepare('SELECT name, icon, isdebuff, for, description FROM skills WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $skillId, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $row = $res->fetchArray(SQLITE3_ASSOC);
        if ($row === false) {
            $this->skillCache[$skillId] = null;
            self::$globalSkillCache[$skillId] = null;
            return null;
        }
        // Normalize boolean-ish isdebuff (some DBs may store 0/1 or 'true')
        if (isset($row['isdebuff'])) {
            $val = $row['isdebuff'];
            if ($val === '0' || $val === 0 || $val === 'false' || $val === '') $row['isdebuff'] = false;
            else $row['isdebuff'] = (bool)$val;
        }
        $this->skillCache[$skillId] = $row;
        self::$globalSkillCache[$skillId] = $row;
        return $row;
    }

    /**
     * Bulk preload skill meta for a set of skill IDs to drastically reduce per-row queries.
     * - De-duplicates IDs.
     * - Skips IDs already cached (local or global).
     * - Batches to respect SQLite max host parameters (default 999).
     */
    private function preloadSkillMeta(array $ids): void
    {
        if (!$ids) return;
        $unique = [];
        foreach ($ids as $id) {
            $i = (int)$id;
            if ($i <= 0) continue;
            if (array_key_exists($i, $this->skillCache) || array_key_exists($i, self::$globalSkillCache)) continue;
            $unique[$i] = true;
        }
        if (!$unique) return;
        $idList = array_keys($unique);
        $chunkSize = 800; // leave headroom under 999 limit
        for ($offset = 0; $offset < count($idList); $offset += $chunkSize) {
            $chunk = array_slice($idList, $offset, $chunkSize);
            if (!$chunk) continue;

            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $sql = 'SELECT id, name, icon, isdebuff, for, description FROM skills WHERE id IN (' . $placeholders . ')';
            $stmt = $this->db->prepare($sql);
            foreach ($chunk as $idx => $skillId) {
                $stmt->bindValue($idx + 1, $skillId, SQLITE3_INTEGER); // positional binding
            }
            $res = $stmt->execute();
            $found = 0;
            while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
                $found++;
                $sid = (int)$row['id'];
                if (isset($row['isdebuff'])) {
                    $val = $row['isdebuff'];
                    if ($val === '0' || $val === 0 || $val === 'false' || $val === '') $row['isdebuff'] = false;
                    else $row['isdebuff'] = (bool)$val;
                }
                $this->skillCache[$sid] = $row;
                self::$globalSkillCache[$sid] = $row;
            }
            // Mark any not found as null to avoid repeat queries
            foreach ($chunk as $sid) {
                if (!array_key_exists($sid, $this->skillCache)) {
                    $this->skillCache[$sid] = null;
                    self::$globalSkillCache[$sid] = null;
                }
            }
        }
    }

    /** Quickly extract skill IDs from the raw JSON skillList without loading meta. */
    private function extractSkillIds(string $json): array
    {
        if ($json === '' || $json === '[]') return [];
        // cache by raw string to avoid repeated json_decode
        if (isset($this->extractCache[$json])) return $this->extractCache[$json];
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            $this->extractCache[$json] = [];
            return [];
        }
        $ids = [];
        foreach ($decoded as $s) {
            if (isset($s['id'])) $ids[] = (int)$s['id'];
        }
        $this->extractCache[$json] = $ids;

        return $ids;
    }

    /**
     * Fetch skill information from skills table.
     */
    public function getSkillById(int $skillId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM skills WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $skillId, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $row = $res->fetchArray(SQLITE3_ASSOC);
        return $row === false ? null : $row;
    }

    /** Parse skills."for" JSON extracting stat entries (stat, tag, val/value) with StatLabels labels. */
    /**
     * Parse skills."for" JSON extracting stat entries (stat, tag, val/value) with StatLabels labels.
     * Accepts optional skillId to allow caching per-skill.
     * @param string $raw
     * @param int|null $skillId
     * @return array
     */
    private function parseSkillEffects(string $raw, ?int $skillId = null): array
    {
        $raw = trim($raw);
        // try cache by skillId first, else by raw payload hash
        if ($skillId !== null && isset($this->skillEffectsCache[$skillId])) {
            return $this->skillEffectsCache[$skillId];
        }
        $rawHashKey = 'raw:' . md5($raw);
        if (isset($this->skillEffectsCache[$rawHashKey])) {
            return $this->skillEffectsCache[$rawHashKey];
        }
        if ($raw === '' || strtolower($raw) === 'null') {
            $this->skillEffectsCache[$rawHashKey] = [[], null];
            return [[], null];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            $this->skillEffectsCache[$rawHashKey] = [[], null];
            return [[], null];
        }
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
            $tag = strtolower(trim((string)($obj['tag'] ?? '')));
            $valRaw = $obj['val'] ?? ($obj['value'] ?? null);
            if ($stat === null || $valRaw === null) continue;
            $label = $labels[$stat] ?? $stat;
            if (is_array($valRaw)) {
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
        $effectText = implode(', ', array_map(fn($e) => $e['text'] ?? ($e['formatted'] ?? ''), array_filter($effects, fn($e) => isset($e['text']))));
        $effectText = $effectText !== '' ? $effectText : null;

        $res = [$effects, $effectText];
        if ($skillId !== null) {
            $this->skillEffectsCache[$skillId] = $res;
        }
        $this->skillEffectsCache[$rawHashKey] = $res;
        return $res;
    }

    /** Build level-specific effects replacing raw_values arrays with single formatted entry for chosen level. */
    /**
     * Build level-specific effects replacing raw_values arrays with single formatted entry for chosen level.
     * Accepts optional skillId for cache keying when called externally.
     * @param array $metaEffects
     * @param int $level
     * @param int|null $skillId
     * @return array
     */
    private function buildEffectsForLevel(array $metaEffects, int $level, ?int $skillId = null): array
    {
        // caching by skillId:level is handled by caller (parseSkillList) to keep signature simple

        $effects = [];
        foreach ($metaEffects as $e) {
            if (isset($e['raw_values'])) {
                $values = $e['raw_values'];
                if (!is_array($values) || !$values) continue;
                $idx = max(0, $level - 1);
                $chosen = $values[$idx] ?? end($values);
                // Heuristic: many skills include a baseline multiplier 1 at index 0 (no change) 
                // and real level 1 effect starts at index 1 (e.g. [1,1.1,1.21,...]).
                // If first value == 1 and second > 1 and tag is multiplicative and requested level >= 1
                // then shift index by +1 (if exists) so level 1 shows +10% not +0%.
                if (strtolower(trim($e['tag'] ?? '')) === 'mul' && isset($values[0], $values[1]) && (float)$values[0] === 1.0 && (float)$values[1] > 1.0) {
                    // Only shift if we originally selected the baseline producing +0%
                    if ($idx === 0 && isset($values[1])) {
                        $chosen = $values[1];
                    } elseif ($idx > 0 && isset($values[$idx + 1])) {
                        // For higher levels also shift forward by one to stay aligned
                        $chosen = $values[$idx + 1];
                    }
                }
                $formatted = $this->formatEffectValue(strtolower(trim($e['tag'] ?? 'add')), $e['stat'] ?? '', $chosen);
                $effects[] = [
                    'stat' => $e['stat'] ?? null,
                    'label' => $e['label'] ?? ($e['stat'] ?? ''),
                    'tag' => $e['tag'] ?? 'add',
                    'raw' => $chosen,
                    'formatted' => $formatted,
                    'text' => $formatted . ' ' . ($e['label'] ?? ($e['stat'] ?? '')),
                ];
            } else {
                // ensure tag normalization on pre-formatted entries
                if (isset($e['tag'])) $e['tag'] = strtolower(trim($e['tag']));
                $effects[] = $e; // already formatted
            }
        }
        $text = implode(', ', array_map(fn($x) => $x['text'] ?? ($x['formatted'] ?? ''), $effects));
        return [$effects, $text ?: null];
    }

    private function formatEffectValue(string $tag, string $stat, $val): string
    {
        if (!is_numeric($val)) return (string)$val;
        $num = (float)$val;
        if (in_array($tag, ['mul', 'multiply', 'mult'], true)) {
            // Absolute multiplier representation: 0.91 => +91%, 1.00 => +100%, 1.10 => +110%, 2.35 => +235%
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

    /**
     * Start a named timer.
     */
    // removed debug timers and file logging

    private function trimNumber(float $num): string
    {
        $formatted = number_format($num, 2, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');
        return $formatted === '' ? '0' : $formatted;
    }
}
