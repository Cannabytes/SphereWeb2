<?php

namespace Ofey\Logan22\component\plugins\wiki;

use Ofey\Logan22\template\tpl;
use Ofey\Logan22\component\lang\lang;

class quest
{
    /**
     * Show quest detail page. If a repository exists, try to use it; otherwise render with sample data.
     * @param int $id
     */
    public function show(int $id): void
    {
        // Try to use a QuestRepository if present
        $questData = null;
    $repoClass = __NAMESPACE__ . '\\QuestRepository';
    if (class_exists($repoClass)) {
            try {
        $repo = new $repoClass();
                $questData = $repo->findById($id);
            } catch (\Throwable $e) {
                $questData = null;
            }
        }

        // Fallback: sample data for preview
        if (!$questData) {
            $questData = [
                'id' => $id,
                'name' => sprintf(lang::get_phrase('Demo quest number'), (string)$id),
                'level' => 12,
                'type' => 'Main',
                'is_repeatable' => false,
                'giver' => ['id' => 101, 'name' => lang::get_phrase('NPC Giver')],
                'description' => lang::get_phrase('Demo quest description'),
                'objectives' => [
                    ['type' => 'Kill', 'text' => sprintf(lang::get_phrase('Kill %d Forest Wolves'), 10), 'progress' => '0/10'],
                    ['type' => 'Collect', 'text' => sprintf(lang::get_phrase('Collect %d Hides'), 5), 'progress' => '0/5'],
                ],
                'steps' => [
                    ['title' => lang::get_phrase('Start'), 'text' => lang::get_phrase('Talk to the giver.')],
                    ['title' => lang::get_phrase('Gathering'), 'text' => lang::get_phrase('Collect the required items.')],
                    ['title' => lang::get_phrase('Completion'), 'text' => lang::get_phrase('Return to the giver and submit the quest.')],
                ],
                'rewards' => [
                    ['id' => 201, 'name' => lang::get_phrase('Armor piece'), 'icon' => null, 'count' => 1, 'chance' => 100],
                    ['id' => 202, 'name' => lang::get_phrase('Gold'), 'icon' => null, 'count' => 500, 'chance' => 100],
                ],
                'related' => [
                    ['id' => $id + 1, 'name' => lang::get_phrase('Next quest')],
                ],
                'notes' => lang::get_phrase('Additional quest notes'),
            ];
        }

        tpl::addVar('quest', $questData);
        tpl::displayPlugin('/wiki/tpl/quest.html');
    }
}
