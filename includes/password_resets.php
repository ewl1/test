<?php
function ensure_password_reset_table()
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $ensured = true;

    try {
        $GLOBALS['pdo']->exec("
            CREATE TABLE IF NOT EXISTS password_resets (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                email VARCHAR(190) NOT NULL,
                token_hash CHAR(64) NOT NULL,
                requested_ip VARCHAR(45) DEFAULT NULL,
                user_agent VARCHAR(255) DEFAULT NULL,
                expires_at DATETIME NOT NULL,
                used_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_password_resets_token_hash (token_hash),
                KEY idx_password_resets_user_id (user_id),
                KEY idx_password_resets_email (email),
                KEY idx_password_resets_expires_at (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } catch (Throwable $e) {
    }
}

function password_reset_cleanup()
{
    ensure_password_reset_table();

    try {
        $stmt = $GLOBALS['pdo']->prepare("
            DELETE FROM password_resets
            WHERE expires_at < DATE_SUB(NOW(), INTERVAL 1 DAY)
               OR used_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt->execute();
    } catch (Throwable $e) {
    }
}

function password_reset_rate_limit_targets($email = '', $scopePrefix = 'password_reset_request')
{
    $email = normalize_email($email);
    $ip = rate_limit_client_ip();

    $targets = [
        [
            'scope' => $scopePrefix . '_ip',
            'identifier' => $ip,
            'max_attempts' => $scopePrefix === 'password_reset_submit' ? 10 : 8,
            'window_seconds' => 30 * 60,
            'lockout_seconds' => 30 * 60,
        ],
    ];

    if ($email !== '') {
        $targets[] = [
            'scope' => $scopePrefix . '_email',
            'identifier' => $email,
            'max_attempts' => $scopePrefix === 'password_reset_submit' ? 5 : 3,
            'window_seconds' => 30 * 60,
            'lockout_seconds' => 30 * 60,
        ];
    }

    return $targets;
}

function password_reset_log_delivery_fallback($email, $url)
{
    $logDir = BASEDIR . 'logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $line = sprintf(
        "[%s] Password reset fallback for %s: %s%s",
        date('Y-m-d H:i:s'),
        normalize_email($email),
        $url,
        PHP_EOL
    );

    @file_put_contents($logDir . '/password-reset.log', $line, FILE_APPEND);
}

function password_reset_token_state(PDO $pdo, $token)
{
    ensure_password_reset_table();
    password_reset_cleanup();

    $token = trim((string)$token);
    if ($token === '' || !preg_match('/^[a-f0-9]{32,128}$/i', $token)) {
        return [
            'valid' => false,
            'message' => 'Slaptažodžio atstatymo nuoroda negalioja arba jau pasibaigė.',
            'record' => null,
        ];
    }

    $tokenHash = hash('sha256', $token);

    try {
        $stmt = $pdo->prepare("
            SELECT pr.*, u.username, u.email, u.status, u.is_active
            FROM password_resets pr
            INNER JOIN users u ON u.id = pr.user_id
            WHERE pr.token_hash = :token_hash
            LIMIT 1
        ");
        $stmt->execute([':token_hash' => $tokenHash]);
        $record = $stmt->fetch();
    } catch (Throwable $e) {
        $record = null;
    }

    if (!$record) {
        return [
            'valid' => false,
            'message' => 'Slaptažodžio atstatymo nuoroda negalioja arba jau pasibaigė.',
            'record' => null,
        ];
    }

    $expiresAt = strtotime((string)($record['expires_at'] ?? '')) ?: 0;
    $usedAt = trim((string)($record['used_at'] ?? ''));

    if ($usedAt !== '' || $expiresAt <= time()) {
        return [
            'valid' => false,
            'message' => 'Slaptažodžio atstatymo nuoroda negalioja arba jau pasibaigė.',
            'record' => null,
        ];
    }

    if ((int)($record['is_active'] ?? 0) !== 1 || ($record['status'] ?? 'inactive') !== 'active') {
        return [
            'valid' => false,
            'message' => 'Šiai paskyrai slaptažodžio atstatymas šiuo metu negalimas.',
            'record' => null,
        ];
    }

    return [
        'valid' => true,
        'message' => null,
        'record' => $record,
    ];
}

function create_password_reset(PDO $pdo, $email)
{
    ensure_password_reset_table();
    password_reset_cleanup();

    $email = normalize_email($email);
    if ($message = validate_email_address($email)) {
        return [false, $message];
    }

    $rateLimitTargets = password_reset_rate_limit_targets($email, 'password_reset_request');
    $rateLimit = rate_limit_status($rateLimitTargets);
    if ($rateLimit['blocked']) {
        audit_log(null, 'password_reset_request_blocked', 'password_resets', null, [
            'email' => $email,
            'ip' => rate_limit_client_ip(),
            'retry_after_seconds' => $rateLimit['retry_after'],
        ]);
        return [false, 'Per daug slaptažodžio atstatymo bandymų. Bandykite po ' . format_wait_time($rateLimit['retry_after']) . '.'];
    }

    rate_limit_hit($rateLimitTargets);

    $stmt = $pdo->prepare("
        SELECT id, username, email, is_active, status
        FROM users
        WHERE email = :email
        LIMIT 1
    ");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    $genericMessage = 'Jei toks el. pašto adresas yra sistemoje, išsiuntėme slaptažodžio atstatymo nuorodą.';
    if (
        !$user ||
        (int)($user['is_active'] ?? 0) !== 1 ||
        ($user['status'] ?? 'inactive') !== 'active'
    ) {
        audit_log(null, 'password_reset_requested', 'password_resets', null, [
            'email' => $email,
            'resolved_user' => false,
        ]);
        return [true, $genericMessage];
    }

    $token = random_token(32);
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', time() + 60 * 60);

    try {
        $pdo->prepare("DELETE FROM password_resets WHERE user_id = :user_id")->execute([
            ':user_id' => (int)$user['id'],
        ]);

        $insert = $pdo->prepare("
            INSERT INTO password_resets (user_id, email, token_hash, requested_ip, user_agent, expires_at, created_at)
            VALUES (:user_id, :email, :token_hash, :requested_ip, :user_agent, :expires_at, NOW())
        ");
        $insert->execute([
            ':user_id' => (int)$user['id'],
            ':email' => $user['email'],
            ':token_hash' => $tokenHash,
            ':requested_ip' => rate_limit_client_ip() ?: null,
            ':user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ':expires_at' => $expiresAt,
        ]);
    } catch (Throwable $e) {
        return [false, 'Nepavyko paruošti slaptažodžio atstatymo užklausos.'];
    }

    $resetUrl = public_path('reset-password.php?token=' . rawurlencode($token));
    $subject = 'Slaptažodžio atstatymas';
    $html = '<p>Gavome slaptažodžio atstatymo užklausą.</p>'
        . '<p><a href="' . escape_url($resetUrl) . '">Nustatyti naują slaptažodį</a></p>'
        . '<p>Nuoroda galioja 1 valandą.</p>';
    $text = "Gavome slaptažodžio atstatymo užklausą.\n\n"
        . "Nustatyti naują slaptažodį: {$resetUrl}\n\n"
        . "Nuoroda galioja 1 valandą.";

    $mailSent = send_mail_message($user['email'], $user['username'] ?? $user['email'], $subject, $html, $text);
    if (!$mailSent) {
        password_reset_log_delivery_fallback($user['email'], $resetUrl);
        audit_log((int)$user['id'], 'password_reset_email_fallback', 'users', (int)$user['id'], [
            'email' => $user['email'],
        ]);
    } else {
        audit_log((int)$user['id'], 'password_reset_requested', 'users', (int)$user['id'], [
            'email' => $user['email'],
        ]);
    }

    return [true, $genericMessage];
}

function reset_password_by_token(PDO $pdo, $token, $password)
{
    ensure_password_reset_table();
    password_reset_cleanup();

    $token = trim((string)$token);
    $tokenHash = $token !== '' ? hash('sha256', $token) : '';
    $rateLimitTargets = array_merge(
        password_reset_rate_limit_targets('', 'password_reset_submit'),
        [[
            'scope' => 'password_reset_submit_token',
            'identifier' => $tokenHash,
            'max_attempts' => 5,
            'window_seconds' => 30 * 60,
            'lockout_seconds' => 30 * 60,
        ]]
    );

    $rateLimit = rate_limit_status($rateLimitTargets);
    if ($rateLimit['blocked']) {
        audit_log(null, 'password_reset_submit_blocked', 'password_resets', null, [
            'ip' => rate_limit_client_ip(),
            'retry_after_seconds' => $rateLimit['retry_after'],
        ]);
        return [false, 'Per daug slaptažodžio keitimo bandymų. Bandykite po ' . format_wait_time($rateLimit['retry_after']) . '.'];
    }

    rate_limit_hit($rateLimitTargets);

    if ($message = validate_password_strength($password, true)) {
        return [false, $message];
    }

    $tokenState = password_reset_token_state($pdo, $token);
    if (!$tokenState['valid']) {
        audit_log(null, 'password_reset_invalid_token', 'password_resets', null, [
            'ip' => rate_limit_client_ip(),
        ]);
        return [false, $tokenState['message']];
    }

    $record = $tokenState['record'];

    try {
        $pdo->beginTransaction();

        $updateUser = $pdo->prepare("
            UPDATE users
            SET password = :password
            WHERE id = :id
        ");
        $updateUser->execute([
            ':password' => password_hash((string)$password, PASSWORD_DEFAULT),
            ':id' => (int)$record['user_id'],
        ]);

        $markUsed = $pdo->prepare("
            UPDATE password_resets
            SET used_at = NOW()
            WHERE user_id = :user_id AND used_at IS NULL
        ");
        $markUsed->execute([
            ':user_id' => (int)$record['user_id'],
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return [false, 'Nepavyko išsaugoti naujo slaptažodžio.'];
    }

    clear_failed_login_attempts($record['email'], rate_limit_client_ip());
    audit_log((int)$record['user_id'], 'password_reset_completed', 'users', (int)$record['user_id'], [
        'email' => $record['email'],
    ]);

    return [true, 'Slaptažodis sėkmingai pakeistas. Dabar galite prisijungti.'];
}
