<?php
// Rollback 1.1.0 — Pašalina bot palaikymą

$GLOBALS['pdo']->exec("DROP TABLE IF EXISTS shoutbox_bot_triggers");

try {
    $GLOBALS['pdo']->exec("ALTER TABLE infusion_shoutbox_messages DROP COLUMN IF EXISTS is_bot");
} catch (Throwable $e) {
    // Stulpelio nebuvo — tęsiame
}

// Boto vartotojo nepašaliname — jis gali turėti žinutes
