<?php

namespace App\Shoutbox;

final class Assistant
{
    /**
     * Returns the bot user ID (cached in $GLOBALS for the request lifetime).
     */
    public static function botUserId(): ?int
    {
        if (isset($GLOBALS['_shoutbox_bot_user_id'])) {
            return $GLOBALS['_shoutbox_bot_user_id'] ?: null;
        }

        $stmt = $GLOBALS['pdo']->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $stmt->execute(['Asistentas']);
        $id = $stmt->fetchColumn();
        $GLOBALS['_shoutbox_bot_user_id'] = $id ? (int)$id : 0;

        return $GLOBALS['_shoutbox_bot_user_id'] ?: null;
    }

    /**
     * Loads all active bot triggers from DB.
     * Returns empty array if table does not exist yet (migration not run).
     */
    public static function loadTriggers(): array
    {
        try {
            $stmt = $GLOBALS['pdo']->query(
                'SELECT * FROM shoutbox_bot_triggers WHERE is_active = 1 ORDER BY id ASC'
            );
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Finds the first trigger that matches $plainMessage.
     * Returns the trigger row or null.
     */
    public static function matchTrigger(string $plainMessage, array $triggers): ?array
    {
        foreach ($triggers as $trigger) {
            $keyword = (string)$trigger['keyword'];
            if (empty($keyword)) {
                continue;
            }

            if (!empty($trigger['use_levenshtein'])) {
                $threshold = max(1, min(5, (int)$trigger['levenshtein_threshold']));
                if (self::matchLevenshtein($keyword, $plainMessage, $threshold)) {
                    return $trigger;
                }
            } else {
                if (mb_stripos($plainMessage, $keyword) !== false) {
                    return $trigger;
                }
            }
        }

        return null;
    }

    /**
     * Posts a bot reply directly via PDO (bypasses shoutbox_create_message to avoid recursion).
     * Returns the inserted message ID, or 0 on failure.
     */
    public static function postReply(int $botUserId, string $response): int
    {
        try {
            $stmt = $GLOBALS['pdo']->prepare('
                INSERT INTO infusion_shoutbox_messages (user_id, message, is_bot, created_at, updated_at)
                VALUES (?, ?, 1, NOW(), NOW())
            ');
            $stmt->execute([$botUserId, $response]);
            $insertId = (int)$GLOBALS['pdo']->lastInsertId();

            if ($insertId && function_exists('audit_log')) {
                audit_log($botUserId, 'shoutbox_bot_reply', 'infusion_shoutbox_messages', $insertId);
            }

            return $insertId;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Splits haystack into words and checks if any word is within Levenshtein distance of needle.
     */
    private static function matchLevenshtein(string $needle, string $haystack, int $threshold): bool
    {
        $needle = mb_strtolower($needle);
        $tokens = preg_split('/\s+/', mb_strtolower($haystack), -1, PREG_SPLIT_NO_EMPTY);
        foreach ($tokens as $token) {
            // levenshtein() only works reliably on ASCII; use substr as safety guard
            if (levenshtein(substr($needle, 0, 255), substr($token, 0, 255)) <= $threshold) {
                return true;
            }
        }
        return false;
    }
}
