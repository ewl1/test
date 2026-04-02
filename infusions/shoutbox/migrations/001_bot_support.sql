-- Virtualus asistentas: bot palaikymas shoutbox moduliui
-- Esamos instaliacijos: paleisti rankiniu būdu per phpMyAdmin

-- 1. Stulpelis is_bot žinutėms
ALTER TABLE `infusion_shoutbox_messages`
    ADD COLUMN IF NOT EXISTS `is_bot` TINYINT(1) NOT NULL DEFAULT 0;

-- 2. Bot trigerių lentelė
CREATE TABLE IF NOT EXISTS `shoutbox_bot_triggers` (
    `id`                    INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
    `keyword`               VARCHAR(200)     NOT NULL,
    `response`              TEXT             NOT NULL,
    `use_levenshtein`       TINYINT(1)       NOT NULL DEFAULT 0,
    `levenshtein_threshold` TINYINT UNSIGNED NOT NULL DEFAULT 2,
    `is_active`             TINYINT(1)       NOT NULL DEFAULT 1,
    `created_at`            DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Boto vartotojas (INSERT IF NOT EXISTS)
INSERT INTO `users` (username, email, password, role_id, is_active, status, created_at)
SELECT 'Asistentas', 'bot@localhost.invalid', '!', 4, 1, 'active', NOW()
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE username = 'Asistentas');
