<?php

namespace Ofey\Logan22\component\plugins\wiki;

use SQLite3;
use RuntimeException;

/**
 * Repository for reading class data from highfive.db (table: classlist).
 */
class ClassRepository
{
    private string $dbPath;
    private ?SQLite3 $db = null;

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
     * Get all classes with their hierarchy.
     * @return array
     */
    public function getAllClasses(): array
    {
        $sql = "SELECT * FROM classlist ORDER BY id";
        $res = $this->db->query($sql);
        if ($res === false) {
            echo($this->db->lastErrorMsg()); exit;
        }
        $classes = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $classes[] = $row;
        }
        return $classes;
    }

    /**
     * Build class tree by race.
     * @return array
     */
    public function getClassesByRace(): array
    {
        $classes = $this->getAllClasses();
        $classMap = [];
        $roots = [];
        foreach ($classes as $class) {
            $classMap[$class['id']] = $class;
            $classMap[$class['id']]['children'] = [];
            if ($class['parent_id'] === null || $class['parent_id'] == '') {
                $roots[] = $class['id'];
            }
        }
        foreach ($classes as $class) {
            if ($class['parent_id'] !== null && $class['parent_id'] != '') {
                $classMap[$class['parent_id']]['children'][] = $class['id'];
            }
        }

        // Define races based on root ids
        $races = [
            'Human Fighter' => [0],
            'Human Mage' => [10],
            'Elf Fighter' => [18],
            'Elf Mage' => [25],
            'Dark Elf Fighter' => [31],
            'Dark Elf Mage' => [38],
            'Orc Fighter' => [44],
            'Orc Mage' => [49],
            'Dwarf' => [53],
            'Kamael' => [123, 124],
            'Ertheia' => [182, 183],
        ];

        // Invert race mapping for quick lookup of a root id -> race name
        $rootToRace = [];
        foreach ($races as $raceName => $rootIds) {
            foreach ($rootIds as $rid) {
                $rootToRace[$rid] = $raceName;
            }
        }

        $result = [];

        // Iterate actual roots found in DB. If a root id maps to a known race,
        // add it there; otherwise skip it (do not output noneRace).
        foreach ($roots as $rootId) {
            if (!isset($classMap[$rootId])) {
                continue;
            }
            if (!isset($rootToRace[$rootId])) {
                // Unknown root — skip, do not include under any race or noneRace
                continue;
            }
            $raceName = $rootToRace[$rootId];
            if (!isset($result[$raceName])) {
                $result[$raceName] = [];
            }
            $result[$raceName][] = $this->buildTree($classMap, $rootId);
        }

        return $result;
    }

    private function buildTree($classMap, $id): array
    {
        $class = $classMap[$id];
        $class['children'] = [];
        foreach ($classMap[$id]['children'] as $childId) {
            $class['children'][] = $this->buildTree($classMap, $childId);
        }
        return $class;
    }
}
