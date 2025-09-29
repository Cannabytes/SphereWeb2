<?php

namespace Ofey\Logan22\component\plugins\wiki;

use SQLite3;
use RuntimeException;

class EtcItemRepository
{
    private string $dbPath;
    private ?SQLite3 $db = null;
    /**
     * SQL condition detecting quest items (truthy values in is_questitem)
     */
    private const QUEST_WHERE = "(is_questitem IN (1,'1') OR LOWER(is_questitem) IN ('true','yes'))";
    /**
     * SQL condition detecting NON quest items (null/empty/false/0/no)
     */
    private const NON_QUEST_WHERE = "(is_questitem IS NULL OR TRIM(is_questitem) = '' OR is_questitem IN (0,'0') OR LOWER(is_questitem) IN ('false','no'))";

    public function __construct(?string $dbPath = null)
    {
        $this->dbPath = $dbPath ?? WikiDb::getSelectedPath();
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
     * Fetch all etcitems ordered by name.
     * Returns array grouped by etcitem_type => [ rows ]
     * @return array<string,array<int,array<string,mixed>>>
     */
    public function getAllEtcItemsGrouped(): array
    {
        $sql = "SELECT * FROM etcitems ORDER BY etcitem_type, name";
        $res = $this->db->query($sql);
        $grouped = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $type = $row['etcitem_type'] ?? 'other';
            if (!isset($grouped[$type])) $grouped[$type] = [];

            // parse 'for' into normalized stats (reuse simple parsing rules)
            $stats = [];
            if (!empty($row['for'])) {
                $decoded = json_decode($row['for'], true);
                if (is_array($decoded)) {
                    foreach ($decoded as $entry) {
                        if (!isset($entry['stat'], $entry['type'], $entry['val'])) continue;
                        $s = $entry['stat'];
                        $t = $entry['type'];
                        $v = $entry['val'];
                        // skip enchant zero
                        if ($t === 'enchant' && (int)$v === 0) continue;
                        $stats[] = ['code' => $s, 'type' => $t, 'value' => $v];
                    }
                }
            }
            $row['stats'] = $stats;

            // format price
            $price = $row['price'] ?? '';
            if (is_numeric($price)) {
                $row['price_formatted'] = number_format((float)$price, 0, '.', ' ');
            } else {
                $row['price_formatted'] = $price;
            }

            $grouped[$type][] = $row;
        }

        return $grouped;
    }

    /**
     * Return available types with counts: [ type => count ]
     * @return array<string,int>
     */
    public function getTypesWithCounts(): array
    {
        // Counts per type only for NON quest items
        $countsRes = $this->db->query("SELECT COALESCE(NULLIF(TRIM(etcitem_type), ''), 'other') AS type, COUNT(*) AS c FROM etcitems WHERE " . self::NON_QUEST_WHERE . " GROUP BY COALESCE(NULLIF(TRIM(etcitem_type), ''), 'other') ORDER BY type");
        $types = [];
        // Добавить проверку на SQL ошибки запроса  
        if (!$countsRes) {
            throw new RuntimeException('Database query error: ' . $this->db->lastErrorMsg());
        }
        while ($row = $countsRes->fetchArray(SQLITE3_ASSOC)) {
            $t = $row['type'] ?? 'other';
            $types[$t] = (int)($row['c'] ?? 0);
        }
        // Quest bucket separate (only quest items)
        $questCount = (int)$this->db->querySingle("SELECT COUNT(*) FROM etcitems WHERE " . self::QUEST_WHERE);
        if ($questCount > 0) {
            // move quest to end
            $types['quest'] = $questCount;
        }
        return $types;
    }

    /**
     * Fetch paginated items for a given type.
     * Returns ['items'=>[], 'total'=>int]
     */
    public function getItemsByType(string $type, int $page = 1, int $perPage = 50): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;
        $questIncluded = false; // for BC, indicates if quest items were mixed into non-quest list (now always false)

        if ($type === 'quest') {
            $total = (int)$this->db->querySingle("SELECT COUNT(*) FROM etcitems WHERE " . self::QUEST_WHERE);
            $sql = "SELECT * FROM etcitems WHERE " . self::QUEST_WHERE . " ORDER BY id ASC LIMIT $perPage OFFSET $offset";
        } elseif ($type === 'other') {
            $total = (int)$this->db->querySingle("SELECT COUNT(*) FROM etcitems WHERE (etcitem_type IS NULL OR TRIM(etcitem_type) = '') AND " . self::NON_QUEST_WHERE);
            $sql = "SELECT * FROM etcitems WHERE (etcitem_type IS NULL OR TRIM(etcitem_type) = '') AND " . self::NON_QUEST_WHERE . " ORDER BY id ASC LIMIT $perPage OFFSET $offset";
        } else {
            $esc = SQLite3::escapeString($type);
            $total = (int)$this->db->querySingle("SELECT COUNT(*) FROM etcitems WHERE etcitem_type = '$esc' AND " . self::NON_QUEST_WHERE);
            $sql = "SELECT * FROM etcitems WHERE etcitem_type = '$esc' AND " . self::NON_QUEST_WHERE . " ORDER BY id ASC LIMIT $perPage OFFSET $offset";
        }
        $res = $this->db->query($sql);
        $items = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            // parse minimal stats and format price as before
            $stats = [];
            if (!empty($row['for'])) {
                $decoded = json_decode($row['for'], true);
                if (is_array($decoded)) {
                    foreach ($decoded as $entry) {
                        if (!isset($entry['stat'], $entry['type'], $entry['val'])) continue;
                        $s = $entry['stat'];
                        $t = $entry['type'];
                        $v = $entry['val'];
                        if ($t === 'enchant' && (int)$v === 0) continue;
                        $stats[] = ['code' => $s, 'type' => $t, 'value' => $v];
                    }
                }
            }
            $row['stats'] = $stats;
            $price = $row['price'] ?? '';
            if (is_numeric($price)) {
                $row['price_formatted'] = number_format((float)$price, 0, '.', ' ');
            } else {
                $row['price_formatted'] = $price;
            }
            $items[] = $row;
        }

        return ['items' => $items, 'total' => $total, 'quest_included' => $questIncluded];
    }
}
