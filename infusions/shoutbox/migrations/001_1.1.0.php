<?php
// Migration 1.1.0 — Virtualus asistentas (bot palaikymas)

// 1. is_bot stulpelis žinutėms
try {
    $GLOBALS['pdo']->exec("
        ALTER TABLE infusion_shoutbox_messages
            ADD COLUMN IF NOT EXISTS is_bot TINYINT(1) NOT NULL DEFAULT 0
    ");
} catch (Throwable $e) {
    // Stulpelis jau egzistuoja — tęsiame
}

// 2. Bot trigerių lentelė
$GLOBALS['pdo']->exec("
    CREATE TABLE IF NOT EXISTS shoutbox_bot_triggers (
        id                    INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
        keyword               VARCHAR(200)     NOT NULL,
        response              TEXT             NOT NULL,
        use_levenshtein       TINYINT(1)       NOT NULL DEFAULT 0,
        levenshtein_threshold TINYINT UNSIGNED NOT NULL DEFAULT 2,
        is_active             TINYINT(1)       NOT NULL DEFAULT 1,
        created_at            DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at            DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_is_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// 3. Boto vartotojas
$GLOBALS['pdo']->exec("
    INSERT INTO users (username, email, password, role_id, is_active, status, created_at)
    SELECT 'Asistentas', 'bot@localhost.invalid', '!', 4, 1, 'active', NOW()
    WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'Asistentas')
");
