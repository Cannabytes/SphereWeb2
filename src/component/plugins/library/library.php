<?php

namespace Ofey\Logan22\component\plugins\library;

use Ofey\Logan22\component\image\client_icon;
use Ofey\Logan22\template\tpl;

class library
{
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

    public function show(): void
    {
        tpl::displayPlugin("/library/tpl/index.html");
    }

    /**
     * Weapons list page. If $type omitted, default to SWORD.
     * @param string|null $type
     */
    public function weapons(?string $type = null): void
    {
        $time_total_start = microtime(true);
        $time_db_open_start = microtime(true);
        $repo = new WeaponRepository();
        $time_db_open_end = microtime(true);
        $time_read_start = microtime(true);
        $types = $repo->getWeaponTypes();
        $type = $type ? strtoupper($type) : 'SWORD';
        if (!in_array($type, $types)) $type = $types[0] ?? 'SWORD';
        $grouped = $repo->getWeaponsGroupedByCrystal($type);
        $typeLabels = $this->weaponTypes;
        foreach ($types as $t) if (!isset($typeLabels[$t])) $typeLabels[$t] = ucfirst(strtolower($t));
        $gradeOrder = ['R', 'S84', 'S80', 'S', 'A', 'B', 'C', 'D', 'NG'];
        $gradeInfo = [
            'NG' => ['title' => 'No grade', 'level' => '1+', 'desc' => 'Можно носить с самого начала игры.'],
            'D' => ['title' => 'D Grade', 'level' => '20+', 'desc' => 'Доступно с 20 уровня.'],
            'C' => ['title' => 'C Grade', 'level' => '40+', 'desc' => 'Доступно с 40 уровня.'],
            'B' => ['title' => 'B Grade', 'level' => '52+', 'desc' => 'Доступно с 52 уровня.'],
            'A' => ['title' => 'A Grade', 'level' => '61+', 'desc' => 'Доступно с 61 уровня.'],
            'S' => ['title' => 'S Grade', 'level' => '76+', 'desc' => 'Доступно с 76 уровня.'],
            'S80' => ['title' => 'S80 Grade', 'level' => '80+', 'desc' => 'Доступно с 80 уровня.'],
            'S84' => ['title' => 'S84 Grade', 'level' => '84+', 'desc' => 'Доступно с 84 уровня.'],
            'R' => ['title' => 'R Grade', 'level' => '??', 'desc' => 'Иногда встречается (R Grade).'],
        ];
        $orderedGrouped = [];
        foreach ($gradeOrder as $g) if (isset($grouped[$g])) $orderedGrouped[$g] = $grouped[$g];
        foreach ($grouped as $g => $items) if (!isset($orderedGrouped[$g])) $orderedGrouped[$g] = $items;
        tpl::addVar('weaponTypes', $types);
        tpl::addVar('weaponTypeLabels', $typeLabels);
        tpl::addVar('currentWeaponType', $type);
        tpl::addVar('weaponsByCrystal', $orderedGrouped);
        tpl::addVar('weaponGradeInfo', $gradeInfo);
        $stateLabels = StatLabels::all();
        foreach ($orderedGrouped as $grade => $items) {
            foreach ($items as $idx => $row) {
                $stats = [];
                $mainCodes = ['pAtk', 'mAtk', 'critRate', 'pAtkSpd'];
                $mainBase = [];
                $mainEnchant = [];
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
                        $entry = ['id' => $sid, 'level' => $lvl, 'name' => $meta['name'] ?? ('Skill ' . $sid), 'icon' => $meta['icon'] ?? null];
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
        tpl::displayPlugin('/library/tpl/weapons.html');
    }

    /** Armor list page */
    public function armors(?string $bodypart = null): void
    {
        $time_total_start = microtime(true);
        $time_db_open_start = microtime(true);
        $repo = new ArmorRepository();
        $time_db_open_end = microtime(true);
        $time_read_start = microtime(true);
        $parts = $repo->getArmorBodyparts();
        $bodypart = $bodypart ? strtoupper($bodypart) : ($parts[0] ?? 'CHEST');
        if (!in_array($bodypart, $parts)) $bodypart = $parts[0] ?? 'CHEST';
        $grouped = $repo->getArmorsGroupedByCrystal($bodypart);
        $gradeOrder = ['R', 'S84', 'S80', 'S', 'A', 'B', 'C', 'D', 'NG'];
        $gradeInfo = [
            'NG'  => ['title' => 'No grade', 'level' => '1+',  'desc' => 'Можно носить с самого начала игры.'],
            'D'   => ['title' => 'D Grade',  'level' => '20+', 'desc' => 'Доступно с 20 уровня.'],
            'C'   => ['title' => 'C Grade',  'level' => '40+', 'desc' => 'Доступно с 40 уровня.'],
            'B'   => ['title' => 'B Grade',  'level' => '52+', 'desc' => 'Доступно с 52 уровня.'],
            'A'   => ['title' => 'A Grade',  'level' => '61+', 'desc' => 'Доступно с 61 уровня.'],
            'S'   => ['title' => 'S Grade',  'level' => '76+', 'desc' => 'Доступно с 76 уровня.'],
            'S80' => ['title' => 'S80 Grade', 'level' => '80+', 'desc' => 'Доступно с 80 уровня.'],
            'S84' => ['title' => 'S84 Grade', 'level' => '84+', 'desc' => 'Доступно с 84 уровня.'],
            'R'   => ['title' => 'R Grade',  'level' => '??',  'desc' => 'Иногда встречается (R Grade).'],
        ];
        $orderedGrouped = [];
        foreach ($gradeOrder as $g) if (isset($grouped[$g])) $orderedGrouped[$g] = $grouped[$g];
        foreach ($grouped as $g => $items) if (!isset($orderedGrouped[$g])) $orderedGrouped[$g] = $items;
        $bodypartLabels = [
            'CHEST' => 'Нагрудник',
            'LEGS' => 'Поножи',
            'FULLARMOR' => 'Комплект',
            'HEAD' => 'Шлем',
            'GLOVES' => 'Перчатки',
            'FEET' => 'Сапоги',
            'SHIELD' => 'Щит',
            'BACK' => 'Плащ',
            'UNDERWEAR' => 'Нижнее бельё',
            'HAIR' => 'Украшение (Hair)',
            'HAIR2' => 'Украшение 2',
            'HAIRALL' => 'Украшение (все)'
        ];
        $bodypartShort = [
            'CHEST' => 'Грудь',
            'LEGS' => 'Ноги',
            'FULLARMOR' => 'Комплект',
            'HEAD' => 'Голова',
            'GLOVES' => 'Руки',
            'FEET' => 'Ноги',
            'SHIELD' => 'Щит',
            'BACK' => 'Спина',
            'UNDERWEAR' => 'Бельё',
            'HAIR' => 'Hair',
            'HAIR2' => 'Hair2',
            'HAIRALL' => 'Hair*'
        ];
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
        tpl::addVar('currentArmorPart', $bodypart);
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
        tpl::displayPlugin('/library/tpl/armors.html');
    }

    /** Jewelry list page */
    public function jewelry(?string $filter = null): void
    {
        $time_total_start = microtime(true);
        $time_db_open_start = microtime(true);
        $repo = new ArmorRepository();
        $time_db_open_end = microtime(true);
        $time_read_start = microtime(true);
        $parts = $repo->getArmorBodyparts();
        $jewelryParts = ['NECK', 'RBRACELET', 'REAR;LEAR', 'RFINGER;LFINGER'];
        $availableJewelry = array_values(array_intersect($jewelryParts, $parts));
        $filter = $filter ? strtoupper($filter) : ($availableJewelry[0] ?? 'NECK');
        if (!in_array($filter, $availableJewelry)) $filter = $availableJewelry[0] ?? 'NECK';
        $grouped = $repo->getArmorsGroupedByCrystal('JEWELRY');
        $gradeOrder = ['R', 'S84', 'S80', 'S', 'A', 'B', 'C', 'D', 'NG'];
        $gradeInfo = ['NG' => ['title' => 'No grade', 'level' => '1+', 'desc' => 'Можно носить с самого начала игры.'], 'D' => ['title' => 'D Grade', 'level' => '20+', 'desc' => 'Доступно с 20 уровня.'], 'C' => ['title' => 'C Grade', 'level' => '40+', 'desc' => 'Доступно с 40 уровня.'], 'B' => ['title' => 'B Grade', 'level' => '52+', 'desc' => 'Доступно с 52 уровня.'], 'A' => ['title' => 'A Grade', 'level' => '61+', 'desc' => 'Доступно с 61 уровня.'], 'S' => ['title' => 'S Grade', 'level' => '76+', 'desc' => 'Доступно с 76 уровня.'], 'S80' => ['title' => 'S80 Grade', 'level' => '80+', 'desc' => 'Доступно с 80 уровня.'], 'S84' => ['title' => 'S84 Grade', 'level' => '84+', 'desc' => 'Доступно с 84 уровня.'], 'R' => ['title' => 'R Grade', 'level' => '??', 'desc' => 'Иногда встречается (R Grade).']];
        $orderedGrouped = [];
        foreach ($gradeOrder as $g) if (isset($grouped[$g])) $orderedGrouped[$g] = $grouped[$g];
        foreach ($grouped as $g => $items) if (!isset($orderedGrouped[$g])) $orderedGrouped[$g] = $items;
        $bodypartLabels = ['NECK' => 'Ожерелье', 'RBRACELET' => 'Браслеты', 'REAR;LEAR' => 'Серьги', 'RFINGER;LFINGER' => 'Кольца'];
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
        tpl::addVar('armorBodypartShort', []);
        tpl::addVar('currentArmorPart', $filter);
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
        tpl::displayPlugin('/library/tpl/armors.html');
    }

    /** Recipes list */
    public function recipes(): void
    {
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
        tpl::displayPlugin('/library/tpl/recipes.html');
    }

    /** Etc items list */
    public function etcitems(): void
    {
        $time_total_start = microtime(true);
        $time_db_open_start = microtime(true);
        $repo = new EtcItemRepository();
        $time_db_open_end = microtime(true);
        $time_read_start = microtime(true);
        $etcitems = $repo->getAllEtcItemsGrouped();
        $time_read_end = microtime(true);
        tpl::addVar('etcitems', $etcitems);
        $time_total_end = microtime(true);
        $timing = ['db_open_s' => number_format(($time_db_open_end - $time_db_open_start), 6, '.', ''), 'read_s' => number_format(($time_read_end - $time_read_start), 6, '.', ''), 'total_s' => number_format(($time_total_end - $time_total_start), 6, '.', '')];
        $timing['db_open_ms'] = number_format(($time_db_open_end - $time_db_open_start) * 1000, 3, '.', '');
        $timing['read_ms'] = number_format(($time_read_end - $time_read_start) * 1000, 3, '.', '');
        $timing['total_ms'] = number_format(($time_total_end - $time_total_start) * 1000, 3, '.', '');
        tpl::addVar('db_timing', $timing);
        tpl::displayPlugin('/library/tpl/etcitems.html');
    }

    /** NPC index */
    public function npcs(): void
    {
        tpl::addVar('npc_data_url', '/library/npcs/data');
        tpl::displayPlugin('/library/tpl/npcs.html');
    }

    public function npcsMonsters(?string $range = null): void
    {
        if ($range === null || $range === '') {
            $range = '1-10';
        }

        $repo = new NpcRepository();
        // Use strict type value instead of pattern to avoid broad LIKE scans and memory overhead
        $types = ['Monster'];
        $min = null;
        $max = null;
        if ($range) {
            if (preg_match('/^(\d+)-(\d+)$/', $range, $m)) {
                $min = (int)$m[1];
                $max = (int)$m[2];
            } elseif (preg_match('/^(\d+)\+$/', $range, $m2)) {
                $min = (int)$m2[1];
                $max = null;
            }
        }
        $list = $repo->getAllWithType($types, false, null, 'level', 'ASC', $min, $max);
        tpl::addVar('npc_list', $list);
        tpl::addVar('selected_range', $range);
        tpl::displayPlugin('/library/tpl/npcs_monsters.html');
    }
    public function npcsRaidboses(): void
    {
        $repo = new NpcRepository();
        $types = ['RaidBoss', 'GrandBoss'];
        $list = $repo->getAllWithType($types, false, null, 'level', 'ASC');
        tpl::addVar('npc_list', $list);
        tpl::displayPlugin('/library/tpl/npcs_raidboses.html');
    }
    public function npcsOther(): void
    {
        $repo = new NpcRepository();
        // Strict matching for types; exclude monsters and bosses without using LIKE patterns
        $types = ['Monster', 'RaidBoss', 'GrandBoss'];
        $list = $repo->getAllWithType($types, true, null, 'level', 'ASC');
        tpl::addVar('npc_list', $list);
        tpl::displayPlugin('/library/tpl/npcs_other.html');
    }
    /** Generic NPC type page */
    public function npcsType(string $type): void
    {
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
            tpl::displayPlugin('/library/tpl/npcs_type.html');
            return;
        }

        // Если тип monster, перенаправляем на спец. страницу
        if ($type === 'Monster') {
            header('Location: /library/npcs/monsters', 302);
            exit;
        }

        $repo = new NpcRepository();
        $list = $repo->getAllWithType([$type], false, null, 'level', 'ASC');
        tpl::addVar('npc_type', $type);
        tpl::addVar('npc_list', $list);
        tpl::displayPlugin('/library/tpl/npcs_type.html');
    }

    public function npcsData(?string $filter = null): void
    {
        $repo = new NpcRepository();
        $draw = $_GET['draw'] ?? 0;
        $start = $_GET['start'] ?? 0;
        $length = $_GET['length'] ?? 25;
        $search = $_GET['search']['value'] ?? null;
        $orderColIdx = $_GET['order'][0]['column'] ?? 2;
        $orderDir = $_GET['order'][0]['dir'] ?? 'asc';
        $columns = ['id', 'name', 'level', 'type', 'race', 'hp', 'mp', 'attack_physical', 'attack_magical', 'defence_physical', 'defence_magical', 'attack_attack_speed', 'attack_critical', 'attack_accuracy', 'defence_evasion', 'skills'];
        $orderCol = $columns[$orderColIdx] ?? 'level';
        $types = null;
        $exclude = false;
        if ($filter === 'monsters') $types = ['Monster'];
        elseif ($filter === 'raidboses') $types = ['RaidBoss', 'GrandBoss'];
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
            $nameHtml = '<div class="fw-semibold">' . htmlspecialchars($r['name'] ?? '') . '</div>';
            if (!empty($r['title'])) $nameHtml .= '<div class="small text-muted">' . htmlspecialchars($r['title']) . '</div>';
            $data[] = [(string)($r['id'] ?? ''), $nameHtml, htmlspecialchars($r['level'] ?? ''), htmlspecialchars($r['type'] ?? ''), htmlspecialchars($r['race'] ?? ''), htmlspecialchars($r['hp'] ?? ''), htmlspecialchars($r['mp'] ?? ''), htmlspecialchars($r['attack_physical'] ?? ''), htmlspecialchars($r['attack_magical'] ?? ''), htmlspecialchars($r['defence_physical'] ?? ''), htmlspecialchars($r['defence_magical'] ?? ''), htmlspecialchars($r['attack_attack_speed'] ?? ''), htmlspecialchars($r['attack_critical'] ?? ''), htmlspecialchars($r['attack_accuracy'] ?? ''), htmlspecialchars($r['defence_evasion'] ?? ''), $skillsHtml];
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['draw' => (int)$draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Armor sets page */
    public function armorsets(): void
    {
        $time_total_start = microtime(true);
        $time_db_open_start = microtime(true);
        $repo = new ArmorSetRepository();
        $time_db_open_end = microtime(true);
        $time_read_start = microtime(true);
        $setsByGrade = $repo->getAllSetsGrouped();
        $time_read_end = microtime(true);
        $time_total_end = microtime(true);
        $gradeOrder = ['R', 'S84', 'S80', 'S', 'A', 'B', 'C', 'D', 'NG'];
        $ordered = [];
        foreach ($gradeOrder as $gr) if (isset($setsByGrade[$gr])) $ordered[$gr] = $setsByGrade[$gr];
        foreach ($setsByGrade as $gr => $list) if (!isset($ordered[$gr])) $ordered[$gr] = $list;
        $gradeInfo = ['NG' => ['title' => 'No grade', 'level' => '1+', 'desc' => 'Начальные комплекты.'], 'D' => ['title' => 'D Grade', 'level' => '20+', 'desc' => 'Доступно с 20 уровня.'], 'C' => ['title' => 'C Grade', 'level' => '40+', 'desc' => 'Доступно с 40 уровня.'], 'B' => ['title' => 'B Grade', 'level' => '52+', 'desc' => 'Доступно с 52 уровня.'], 'A' => ['title' => 'A Grade', 'level' => '61+', 'desc' => 'Доступно с 61 уровня.'], 'S' => ['title' => 'S Grade', 'level' => '76+', 'desc' => 'Доступно с 76 уровня.'], 'S80' => ['title' => 'S80 Grade', 'level' => '80+', 'desc' => 'Доступно с 80 уровня.'], 'S84' => ['title' => 'S84 Grade', 'level' => '84+', 'desc' => 'Доступно с 84 уровня.'], 'R' => ['title' => 'R Grade', 'level' => '??', 'desc' => 'Поздние хроники.']];
        tpl::addVar('armorSetsByGrade', $ordered);
        tpl::addVar('armorSetGradeInfo', $gradeInfo);
        $timing = ['db_open_s' => number_format(($time_db_open_end - $time_db_open_start), 6, '.', ''), 'read_s' => number_format(($time_read_end - $time_read_start), 6, '.', ''), 'total_s' => number_format(($time_total_end - $time_total_start), 6, '.', '')];
        $timing['db_open_ms'] = number_format(($time_db_open_end - $time_db_open_start) * 1000, 3, '.', '');
        $timing['read_ms'] = number_format(($time_read_end - $time_read_start) * 1000, 3, '.', '');
        $timing['total_ms'] = number_format(($time_total_end - $time_total_start) * 1000, 3, '.', '');
        tpl::addVar('db_timing', $timing);
        tpl::displayPlugin('/library/tpl/armorsets.html');
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
}
