<?php

namespace Ofey\Logan22\component\plugins\library;

use SQLite3;
use RuntimeException;

/**
 * Repository for reading crafting recipes from highfive.db (table repices / recipes) and resolving ingredient/product items.
 */
class RecipeRepository
{
    private string $dbPath;
    private ?SQLite3 $db = null;
    private array $itemCache = []; // id => [id,name,icon,source]

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
     * Detect actual recipes table name (user mentioned typo repices). Returns first existing in priority order.
     */
    private function getRecipesTableName(): string
    {
        $candidates = ['repices', 'recipes'];
        foreach ($candidates as $t) {
            $res = @$this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$t' LIMIT 1");
            if ($res && $res->fetchArray()) {
                return $t;
            }
        }
        // default to provided name
        return 'repices';
    }

    /**
     * Fetch all recipes sorted by craftLevel ascending, enriched with parsed ingredient arrays.
     * @return array<int,array<string,mixed>>
     */
    public function getAllRecipes(): array
    {
        $table = $this->getRecipesTableName();
        $sql = "SELECT * FROM $table ORDER BY craftLevel ASC, name";
        $res = $this->db->query($sql);
        $recipes = [];
        $neededIds = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            // Parse ingredients JSON into structured array
            $ing = [];
            if (!empty($row['ingredient'])) {
                $decoded = json_decode($row['ingredient'], true);
                if (is_array($decoded)) {
                    foreach ($decoded as $entry) {
                        if (!isset($entry['id'], $entry['count'])) continue;
                        $iid = (int)$entry['id'];
                        $cnt = (int)$entry['count'];
                        $ing[] = ['id' => $iid, 'count' => $cnt];
                        $neededIds[$iid] = true;
                    }
                }
            }
            $row['_ingredients'] = $ing;
            // collect product ids
            foreach (['production_id', 'productionRare_id'] as $col) {
                if (!empty($row[$col]) && (int)$row[$col] > 0) {
                    $neededIds[(int)$row[$col]] = true;
                }
            }
            // collect recipe item id itself
            if (!empty($row['recipeId']) && (int)$row['recipeId'] > 0) {
                $neededIds[(int)$row['recipeId']] = true;
            }
            $recipes[] = $row;
        }
        // Resolve all needed item ids in one pass
        $this->bulkLoadItems(array_keys($neededIds));

        // Enrich each recipe with product meta and ingredient meta
        foreach ($recipes as $idx => $r) {
            // recipe scroll/item meta
            if (!empty($r['recipeId'])) {
                $rid = (int)$r['recipeId'];
                if (isset($this->itemCache[$rid])) {
                    $recipes[$idx]['_recipe'] = $this->itemCache[$rid];
                }
            }
            $prod = $this->itemCache[$r['production_id']] ?? null;
            if ($prod) {
                $recipes[$idx]['_product'] = $prod + ['count' => (int)($r['production_count'] ?? 1)];
            }
            if (!empty($r['productionRare_id'])) {
                $rare = $this->itemCache[$r['productionRare_id']] ?? null;
                if ($rare) {
                    $recipes[$idx]['_product_rare'] = $rare + ['count' => (int)($r['productionRare_count'] ?? 1)];
                }
            }
            $ingredientsDetailed = [];
            foreach ($r['_ingredients'] as $ing) {
                $meta = $this->itemCache[$ing['id']] ?? null;
                $ingredientsDetailed[] = $ing + ['name' => $meta['name'] ?? null, 'icon' => $meta['icon'] ?? null, 'source' => $meta['source'] ?? null];
            }
            $recipes[$idx]['_ingredients'] = $ingredientsDetailed;
        }
        // Group into dwarven vs common (default to common when unknown)
        $grouped = ['dwarven' => [], 'common' => []];
        foreach ($recipes as $r) {
            $t = strtolower(trim((string)($r['type'] ?? '')));
            if (strpos($t, 'dwarf') !== false || strpos($t, 'dwarven') !== false) {
                $grouped['dwarven'][] = $r;
            } elseif (strpos($t, 'comm') !== false || $t === 'common') {
                $grouped['common'][] = $r;
            } else {
                // unknown types go to common by default
                $grouped['common'][] = $r;
            }
        }

        return $grouped;
    }

    /**
     * Load item metadata for a list of IDs into cache using UNION ALL to minimize round trips.
     * @param int[] $ids
     */
    private function bulkLoadItems(array $ids): void
    {
        $ids = array_filter(array_unique(array_map('intval', $ids)), fn($v) => $v > 0);
        if (!$ids) return;
        // Filter out already cached ids
        $want = array_values(array_diff($ids, array_keys($this->itemCache)));
        if (!$want) return;
        // Chunk to avoid SQLite max variable limits (~999)
        $chunks = array_chunk($want, 400);
        foreach ($chunks as $chunk) {
            $idList = implode(',', $chunk);
            $unionSql = "SELECT id,name,icon,crystal_type,'weapon' AS source FROM weapons WHERE id IN ($idList)
                UNION ALL SELECT id,name,icon,crystal_type,'armor' AS source FROM armors WHERE id IN ($idList)
                UNION ALL SELECT id,name,icon,NULL AS crystal_type,'etcitem' AS source FROM etcitems WHERE id IN ($idList)";
            $res = $this->db->query($unionSql);
            while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
                $iid = (int)$row['id'];
                $this->itemCache[$iid] = [
                    'id' => $iid,
                    'name' => $row['name'] ?? ('Item ' . $iid),
                    'icon' => $row['icon'] ?? null,
                    'source' => $row['source'],
                    'crystal_type' => $row['crystal_type'] ?? null,
                ];
            }
        }
    }
}
