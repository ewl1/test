<?php

foreach ([
    __DIR__ . '/schema.php',
    __DIR__ . '/settings.php',
    __DIR__ . '/meta.php',
    __DIR__ . '/moods.php',
    __DIR__ . '/ranks.php',
    __DIR__ . '/display.php',
    __DIR__ . '/topic_behavior.php',
    __DIR__ . '/attachments.php',
    __DIR__ . '/admin.php',
] as $supportFile) {
    require_once $supportFile;
}
