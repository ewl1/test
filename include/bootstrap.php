<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/mail.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/ipban.php';
require_once __DIR__ . '/ratelimit.php';
require_once __DIR__ . '/bbcode.php';
require_once __DIR__ . '/functions/posts.php';
require_once __DIR__ . '/functions/users.php';
require_once __DIR__ . '/functions/shouts.php';

if (!is_dir(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0775, true);
}
if (!is_dir(AVATAR_DIR)) {
    @mkdir(AVATAR_DIR, 0775, true);
}
