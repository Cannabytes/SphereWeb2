<?php

namespace Ofey\Logan22\component\plugins\wiki;

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
    private array $tableColumnsCache = []; // table => [col => true]

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
     * Check if a table contains a column, cached for performance.
     */
    private function tableHasColumn(string $table, string $column): bool
    {
        $table = trim($table);
        $column = strtolower(trim($column));
        if ($table === '') return false;
        if (!isset($this->tableColumnsCache[$table])) {
            $cols = [];
            $res = @$this->db->query("PRAGMA table_info(\"$table\")");
            if ($res) {
                while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
                    if (!empty($row['name'])) {
                        $cols[strtolower($row['name'])] = true;
                    }
                }
            }
            $this->tableColumnsCache[$table] = $cols;
        }
        return isset($this->tableColumnsCache[$table][$column]);
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
     * Fetch recipes that produce given item id (either production_id or productionRare_id).
     * Returns a list of enriched rows similar to getAllRecipes() enrichment, but filtered.
     * @param int $productId
     * @return array<int,array<string,mixed>>
     */
    public function getRecipesByProductionId(int $productId): array
    {
        $productId = (int)$productId;
        if ($productId <= 0) return [];
        $table = $this->getRecipesTableName();
        $stmt = $this->db->prepare("SELECT * FROM $table WHERE production_id = :pid OR productionRare_id = :pid ORDER BY craftLevel ASC, name");
        if (!$stmt) return [];
        $stmt->bindValue(':pid', $productId, SQLITE3_INTEGER);
        $res = $stmt->execute();
        if (!$res) return [];
        $recipes = [];
        // Track seen composite keys to avoid duplicates (some DBs may contain 2 rows referencing same recipe/product)
        $seen = [];
        $neededIds = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $ridKey = ($row['recipeId'] ?? '') . '|' . ($row['production_id'] ?? '') . '|' . ($row['productionRare_id'] ?? '');
            if ($ridKey !== '||' && isset($seen[$ridKey])) {
                continue; // skip duplicated logical entry
            }
            $seen[$ridKey] = true;
            // Parse ingredients JSON
            $ing = [];
            if (!empty($row['ingredient'])) {
                $decoded = json_decode($row['ingredient'], true);
                if (is_array($decoded)) {
                    foreach ($decoded as $entry) {
                        if (!isset($entry['id'], $entry['count'])) continue;
                        $iid = (int)$entry['id'];
                        $cnt = (int)$entry['count'];
                        $ing[] = ['id' => $iid, 'count' => $cnt];
                        if ($iid > 0) $neededIds[$iid] = true;
                    }
                }
            }
            $row['_ingredients'] = $ing;
            // collect referenced items to resolve
            foreach (['production_id', 'productionRare_id', 'recipeId'] as $col) {
                if (!empty($row[$col]) && (int)$row[$col] > 0) {
                    $neededIds[(int)$row[$col]] = true;
                }
            }
            $recipes[] = $row;
        }
        if (!$recipes) return [];
        // Resolve all
        $this->bulkLoadItems(array_keys($neededIds));
        // Enrich
        foreach ($recipes as $idx => $r) {
            if (!empty($r['recipeId'])) {
                $rid = (int)$r['recipeId'];
                if (isset($this->itemCache[$rid])) $recipes[$idx]['_recipe'] = $this->itemCache[$rid];
            }
            if (!empty($r['production_id'])) {
                $prod = $this->itemCache[(int)$r['production_id']] ?? null;
                if ($prod) $recipes[$idx]['_product'] = $prod + ['count' => (int)($r['production_count'] ?? 1)];
            }
            if (!empty($r['productionRare_id'])) {
                $rare = $this->itemCache[(int)$r['productionRare_id']] ?? null;
                if ($rare) $recipes[$idx]['_product_rare'] = $rare + ['count' => (int)($r['productionRare_count'] ?? 1)];
            }
            $ingredientsDetailed = [];
            foreach ($r['_ingredients'] as $ing) {
                $meta = $this->itemCache[$ing['id']] ?? null;
                $ingredientsDetailed[] = $ing + [
                    'name' => $meta['name'] ?? null,
                    'icon' => $meta['icon'] ?? null,
                    'source' => $meta['source'] ?? null,
                ];
            }
            $recipes[$idx]['_ingredients'] = $ingredientsDetailed;
        }
        return $recipes;
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
            // Build selects per table with robust fallbacks for optional columns
            $selects = [];

            // weapons
            $w_crystal = $this->tableHasColumn('weapons', 'crystal_type') ? 'crystal_type' : 'NULL AS crystal_type';
            $w_drop   = $this->tableHasColumn('weapons', 'is_drop')   ? 'COALESCE(is_drop,0)   AS is_drop'   : '0 AS is_drop';
            // spoil flag often stored as is_sweep, sometimes as is_spoil
            $w_sweep  = $this->tableHasColumn('weapons', 'is_sweep')  ? 'COALESCE(is_sweep,0)  AS is_sweep' : ($this->tableHasColumn('weapons','is_spoil') ? 'COALESCE(is_spoil,0) AS is_sweep' : '0 AS is_sweep');
            $w_craft  = $this->tableHasColumn('weapons', 'is_craft')  ? 'COALESCE(is_craft,0)  AS is_craft' : '0 AS is_craft';
            $selects[] = "SELECT id,name,icon,$w_crystal,$w_drop,$w_sweep,$w_craft,'weapon' AS source FROM weapons WHERE id IN ($idList)";

            // armors
            $a_crystal = $this->tableHasColumn('armors', 'crystal_type') ? 'crystal_type' : 'NULL AS crystal_type';
            $a_drop   = $this->tableHasColumn('armors', 'is_drop')   ? 'COALESCE(is_drop,0)   AS is_drop'   : '0 AS is_drop';
            $a_sweep  = $this->tableHasColumn('armors', 'is_sweep')  ? 'COALESCE(is_sweep,0)  AS is_sweep' : ($this->tableHasColumn('armors','is_spoil') ? 'COALESCE(is_spoil,0) AS is_sweep' : '0 AS is_sweep');
            $a_craft  = $this->tableHasColumn('armors', 'is_craft')  ? 'COALESCE(is_craft,0)  AS is_craft' : '0 AS is_craft';
            $selects[] = "SELECT id,name,icon,$a_crystal,$a_drop,$a_sweep,$a_craft,'armor' AS source FROM armors WHERE id IN ($idList)";

            // etcitems
            $e_crystal = $this->tableHasColumn('etcitems', 'crystal_type') ? 'crystal_type' : 'NULL AS crystal_type';
            $e_drop   = $this->tableHasColumn('etcitems', 'is_drop')   ? 'COALESCE(is_drop,0)   AS is_drop'   : '0 AS is_drop';
            $e_sweep  = $this->tableHasColumn('etcitems', 'is_sweep')  ? 'COALESCE(is_sweep,0)  AS is_sweep' : ($this->tableHasColumn('etcitems','is_spoil') ? 'COALESCE(is_spoil,0) AS is_sweep' : '0 AS is_sweep');
            // craft can be absent; additionally, treat non-zero recipe_id as craft indicator if column exists
            if ($this->tableHasColumn('etcitems', 'is_craft')) {
                $e_craft = 'COALESCE(is_craft,0) AS is_craft';
            } elseif ($this->tableHasColumn('etcitems', 'recipe_id')) {
                $e_craft = '(CASE WHEN COALESCE(recipe_id,0) != 0 THEN 1 ELSE 0 END) AS is_craft';
            } else {
                $e_craft = '0 AS is_craft';
            }
            $selects[] = "SELECT id,name,icon,$e_crystal,$e_drop,$e_sweep,$e_craft,'etcitem' AS source FROM etcitems WHERE id IN ($idList)";

            $unionSql = implode("\nUNION ALL\n", $selects);
            $res = $this->db->query($unionSql);
            while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
                $iid = (int)$row['id'];
                $this->itemCache[$iid] = [
                    'id' => $iid,
                    'name' => $row['name'] ?? ('Item ' . $iid),
                    'icon' => $row['icon'] ?? null,
                    'source' => $row['source'],
                    'crystal_type' => $row['crystal_type'] ?? null,
                    // flags used by UI (drop/spoil/craft)
                    'is_drop' => (int)($row['is_drop'] ?? 0),
                    'is_sweep' => (int)($row['is_sweep'] ?? 0),
                    'is_craft' => (int)($row['is_craft'] ?? 0),
                ];
            }
        }
    }
}
