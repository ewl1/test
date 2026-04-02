<?php

namespace App\Shoutbox;

final class ShoutboxManager
{
    public function allTriggers(): array
    {
        $stmt = $GLOBALS['pdo']->query('SELECT * FROM shoutbox_bot_triggers ORDER BY id ASC');
        return $stmt->fetchAll();
    }

    public function triggerById(int $id): ?array
    {
        $stmt = $GLOBALS['pdo']->prepare('SELECT * FROM shoutbox_bot_triggers WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createTrigger(
        string $keyword,
        string $response,
        bool $useLevenshtein,
        int $threshold,
        bool $isActive
    ): int {
        $stmt = $GLOBALS['pdo']->prepare('
            INSERT INTO shoutbox_bot_triggers
                (keyword, response, use_levenshtein, levenshtein_threshold, is_active)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $keyword,
            $response,
            $useLevenshtein ? 1 : 0,
            max(1, min(5, $threshold)),
            $isActive ? 1 : 0,
        ]);
        return (int)$GLOBALS['pdo']->lastInsertId();
    }

    public function deleteTrigger(int $id): bool
    {
        $stmt = $GLOBALS['pdo']->prepare('DELETE FROM shoutbox_bot_triggers WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function toggleActive(int $id, bool $active): bool
    {
        $stmt = $GLOBALS['pdo']->prepare('UPDATE shoutbox_bot_triggers SET is_active = ? WHERE id = ?');
        $stmt->execute([$active ? 1 : 0, $id]);
        return $stmt->rowCount() > 0;
    }
}
