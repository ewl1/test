<?php
require_once __DIR__ . '/includes/bootstrap.php';

require_post_request();
verify_csrf();

if (function_exists('auth_security_log')) {
    $user = current_user();
    if ($user) {
        auth_security_log((int)$user['id'], 'logout', 'user', (int)$user['id'], [
            'subject_label' => (string)($user['username'] ?? ('User #' . (int)$user['id'])),
            'email' => (string)($user['email'] ?? ''),
        ]);
    }
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        [
            'expires' => time() - 42000,
            'path' => $params['path'] ?? '/',
            'domain' => $params['domain'] ?? '',
            'secure' => (bool)($params['secure'] ?? false),
            'httponly' => (bool)($params['httponly'] ?? true),
            'samesite' => $params['samesite'] ?? 'Lax',
        ]
    );
}

session_destroy();
redirect(public_path('index.php'));
