<?php

namespace Ofey\Logan22\component\plugins\sphere_forum\struct;

use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;

class ForumPoll
{
    private int $id;
    private int $threadId;
    private string $question;
    private bool $isMultiple;
    private bool $isClosed;
    private ?string $expiresAt;
    private array $options = [];

    public function __construct(array $pollData)
    {
        $this->id = (int)$pollData['id'];
        $this->threadId = (int)$pollData['thread_id'];
        $this->question = $pollData['question'];
        $this->isMultiple = (bool)$pollData['is_multiple'];
        $this->isClosed = (bool)$pollData['is_closed'];
        $this->expiresAt = $pollData['expires_at'];
        $this->loadOptions();
    }

    public function getTotalVotes(): int
    {
        return array_sum(array_column($this->options, 'votes_count'));
    }

    public function getVotePercentages(): array
    {
        $totalVotes = $this->getTotalVotes();
        return array_map(function ($option) use ($totalVotes) {
            return $totalVotes > 0
                ? round(($option['votes_count'] / $totalVotes) * 100, 2)
                : 0;
        }, $this->options);
    }

    private function loadOptions(): void
    {
        $this->options = sql::getRows(
            "SELECT id, text, votes_count 
            FROM forum_poll_options 
            WHERE poll_id = ? 
            ORDER BY id",
            [$this->id]
        );
    }

    public function hasUserVoted(int $userId): bool
    {
        return sql::getValue(
                "SELECT COUNT(*) FROM forum_poll_votes 
            WHERE poll_id = ? AND user_id = ?",
                [$this->id, $userId]
            ) > 0;
    }

    public function vote(int $userId, array $optionIds): bool
    {
        if ($this->isClosed || $this->hasUserVoted($userId)) {
            return false;
        }

        if (!$this->isMultiple && count($optionIds) > 1) {
            return false;
        }

        sql::beginTransaction();
        try {
            foreach ($optionIds as $optionId) {
                sql::run(
                    "INSERT INTO forum_poll_votes (poll_id, option_id, user_id) VALUES (?, ?, ?)",
                    [$this->id, $optionId, $userId]
                );

                sql::run(
                    "UPDATE forum_poll_options SET votes_count = votes_count + 1 WHERE id = ?",
                    [$optionId]
                );
            }
            sql::commit();
            return true;
        } catch (\Exception $e) {
            sql::rollback();
            return false;
        }
    }

    // Геттеры и другие методы
    public function getId(): int
    {
        return $this->id;
    }

    public function getQuestion(): string
    {
        return $this->question;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function isMultiple(): bool
    {
        return $this->isMultiple;
    }

    public function isClosed(): bool
    {
        return $this->isClosed;
    }


public function update(array $data): bool {
    sql::beginTransaction();
    try {
        sql::run(
            "UPDATE forum_polls SET
            question = ?,
            is_multiple = ?,
            expires_at = ?
            WHERE id = ?",
            [$data['question'], $data['isMultiple'], $data['expiresAt'], $this->id]
        );

        $currentOptions = sql::getRows(
            "SELECT id, text, votes_count
             FROM forum_poll_options
             WHERE poll_id = ?
             ORDER BY id",
            [$this->id]
        );

        foreach ($data['options'] as $index => $newOption) {
            if (isset($currentOptions[$index])) {
                // Обновляем текст опции, независимо от наличия голосов
                sql::run(
                    "UPDATE forum_poll_options
                     SET text = ?
                     WHERE id = ? AND poll_id = ?",
                    [$newOption['text'], $currentOptions[$index]['id'], $this->id]
                );
            } else {
                sql::run(
                    "INSERT INTO forum_poll_options (poll_id, text, votes_count)
                     VALUES (?, ?, 0)",
                    [$this->id, $newOption['text']]
                );
            }
        }

        // Удаляем лишние опции, сохраняя те, за которые голосовали
        if (count($data['options']) < count($currentOptions)) {
            $keepOptionIds = array_column(array_slice($currentOptions, 0, count($data['options'])), 'id');
            $placeholders = str_repeat('?,', count($keepOptionIds) - 1) . '?';

            sql::run(
                "DELETE FROM forum_poll_options
                 WHERE poll_id = ?
                 AND votes_count = 0
                 AND id NOT IN ($placeholders)",
                array_merge([$this->id], $keepOptionIds)
            );
        }

        sql::commit();
        return true;

    } catch (\Exception $e) {
        sql::rollback();
        throw $e;
    }
}

}