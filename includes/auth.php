<?php
function auth_client_ip()
{
    $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
}

function login_rate_limit_config()
{
    return [
        'window_seconds' => 15 * 60,
        'lockout_seconds' => 15 * 60,
        'email_max_attempts' => 5,
        'ip_max_attempts' => 20,
    ];
}

function ensure_auth_rate_limit_table()
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $ensured = true;

    try {
        $GLOBALS['pdo']->exec("
            CREATE TABLE IF NOT EXISTS auth_rate_limits (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                scope VARCHAR(32) NOT NULL,
                identifier VARCHAR(191) NOT NULL,
                attempts INT NOT NULL DEFAULT 0,
                first_attempt_at DATETIME NOT NULL,
                last_attempt_at DATETIME NOT NULL,
                locked_until DATETIME NULL DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_auth_rate_limits_scope_identifier (scope, identifier),
                KEY idx_auth_rate_limits_scope_locked (scope, locked_until),
                KEY idx_auth_rate_limits_last_attempt (last_attempt_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } catch (Throwable $e) {
    }
}

function auth_rate_limit_targets($email, $ip)
{
    $config = login_rate_limit_config();
    $targets = [];

    $email = trim((string)$email);
    if ($email !== '') {
        $targets[] = [
            'scope' => 'login_email',
            'identifier' => $email,
            'limit' => (int)$config['email_max_attempts'],
        ];
    }

    $ip = trim((string)$ip);
    if ($ip !== '') {
        $targets[] = [
            'scope' => 'login_ip',
            'identifier' => $ip,
            'limit' => (int)$config['ip_max_attempts'],
        ];
    }

    return $targets;
}

function fetch_auth_rate_limit_row($scope, $identifier)
{
    ensure_auth_rate_limit_table();

    try {
        $stmt = $GLOBALS['pdo']->prepare("
            SELECT *
            FROM auth_rate_limits
            WHERE scope = :scope AND identifier = :identifier
            LIMIT 1
        ");
        $stmt->execute([
            ':scope' => (string)$scope,
            ':identifier' => (string)$identifier,
        ]);

        return $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function write_auth_rate_limit_row($scope, $identifier, $attempts, $firstAttemptAt, $lastAttemptAt, $lockedUntil = null)
{
    ensure_auth_rate_limit_table();

    try {
        $stmt = $GLOBALS['pdo']->prepare("
            INSERT INTO auth_rate_limits (scope, identifier, attempts, first_attempt_at, last_attempt_at, locked_until)
            VALUES (:scope, :identifier, :attempts, :first_attempt_at, :last_attempt_at, :locked_until)
            ON DUPLICATE KEY UPDATE
                attempts = VALUES(attempts),
                first_attempt_at = VALUES(first_attempt_at),
                last_attempt_at = VALUES(last_attempt_at),
                locked_until = VALUES(locked_until)
        ");
        $stmt->execute([
            ':scope' => (string)$scope,
            ':identifier' => (string)$identifier,
            ':attempts' => (int)$attempts,
            ':first_attempt_at' => (string)$firstAttemptAt,
            ':last_attempt_at' => (string)$lastAttemptAt,
            ':locked_until' => $lockedUntil !== null ? (string)$lockedUntil : null,
        ]);
    } catch (Throwable $e) {
    }
}

function clear_auth_rate_limit_row($scope, $identifier)
{
    ensure_auth_rate_limit_table();

    try {
        $stmt = $GLOBALS['pdo']->prepare("
            DELETE FROM auth_rate_limits
            WHERE scope = :scope AND identifier = :identifier
        ");
        $stmt->execute([
            ':scope' => (string)$scope,
            ':identifier' => (string)$identifier,
        ]);
    } catch (Throwable $e) {
    }
}

function format_wait_time($seconds)
{
    $seconds = max(1, (int)$seconds);
    $minutes = intdiv($seconds, 60);
    $remainingSeconds = $seconds % 60;

    if ($minutes > 0 && $remainingSeconds > 0) {
        return $minutes . ' min. ' . $remainingSeconds . ' s.';
    }
    if ($minutes > 0) {
        return $minutes . ' min.';
    }

    return $remainingSeconds . ' s.';
}

function login_rate_limit_status($email, $ip)
{
    $config = login_rate_limit_config();
    $now = time();
    $blockedUntilTs = 0;

    foreach (auth_rate_limit_targets($email, $ip) as $target) {
        $row = fetch_auth_rate_limit_row($target['scope'], $target['identifier']);
        if (!$row) {
            continue;
        }

        $lastAttemptTs = strtotime((string)($row['last_attempt_at'] ?? '')) ?: 0;
        $lockedUntilTs = strtotime((string)($row['locked_until'] ?? '')) ?: 0;
        $isWindowExpired = $lastAttemptTs > 0 && ($now - $lastAttemptTs) > (int)$config['window_seconds'];

        if ($isWindowExpired && $lockedUntilTs <= $now) {
            clear_auth_rate_limit_row($target['scope'], $target['identifier']);
            continue;
        }

        if ($lockedUntilTs > $now) {
            $blockedUntilTs = max($blockedUntilTs, $lockedUntilTs);
        }
    }

    return [
        'blocked' => $blockedUntilTs > $now,
        'retry_after' => max(0, $blockedUntilTs - $now),
        'blocked_until' => $blockedUntilTs > $now ? date('Y-m-d H:i:s', $blockedUntilTs) : null,
    ];
}

function record_failed_login_attempts($email, $ip)
{
    $config = login_rate_limit_config();
    $now = time();
    $nowSql = date('Y-m-d H:i:s', $now);

    foreach (auth_rate_limit_targets($email, $ip) as $target) {
        $row = fetch_auth_rate_limit_row($target['scope'], $target['identifier']);

        $attempts = 1;
        $firstAttemptAt = $nowSql;
        if ($row) {
            $lastAttemptTs = strtotime((string)($row['last_attempt_at'] ?? '')) ?: 0;
            if ($lastAttemptTs > 0 && ($now - $lastAttemptTs) <= (int)$config['window_seconds']) {
                $attempts = (int)($row['attempts'] ?? 0) + 1;
                $firstAttemptAt = (string)($row['first_attempt_at'] ?? $nowSql);
            }
        }

        $lockedUntil = null;
        if ($attempts >= (int)$target['limit']) {
            $lockedUntil = date('Y-m-d H:i:s', $now + (int)$config['lockout_seconds']);
        }

        write_auth_rate_limit_row(
            $target['scope'],
            $target['identifier'],
            $attempts,
            $firstAttemptAt,
            $nowSql,
            $lockedUntil
        );
    }
}

function clear_failed_login_attempts($email, $ip)
{
    foreach (auth_rate_limit_targets($email, $ip) as $target) {
        clear_auth_rate_limit_row($target['scope'], $target['identifier']);
    }
}

function fetch_user_for_session($userId)
{
    $stmt = $GLOBALS['pdo']->prepare("
        SELECT u.*, r.name AS role_name, r.slug AS role_slug, r.level AS role_level
        FROM users u
        LEFT JOIN roles r ON r.id = u.role_id
        WHERE u.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => (int)$userId]);
    return $stmt->fetch() ?: null;
}

function login($email, $password)
{
    $email = normalize_email($email);
    $ip = auth_client_ip();
    $rateLimit = login_rate_limit_status($email, $ip);

    if ($rateLimit['blocked']) {
        audit_log(null, 'login_blocked', 'users', null, [
            'email' => $email,
            'ip' => $ip,
            'retry_after_seconds' => $rateLimit['retry_after'],
        ]);
        flash('auth_error', 'Per daug nesėkmingų prisijungimų. Bandykite po ' . format_wait_time($rateLimit['retry_after']));
        return false;
    }

    $stmt = $GLOBALS['pdo']->prepare("
        SELECT u.*, r.name AS role_name, r.slug AS role_slug, r.level AS role_level
        FROM users u
        LEFT JOIN roles r ON r.id = u.role_id
        WHERE u.email = ?
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        record_failed_login_attempts($email, $ip);
        audit_log(null, 'login_failed', 'users', null, ['email' => $email, 'reason' => 'user_not_found']);
        flash('auth_error', 'Neteisingi prisijungimo duomenys.');
        return false;
    }

    if (!password_verify((string)$password, $user['password'])) {
        record_failed_login_attempts($email, $ip);
        audit_log((int)$user['id'], 'login_failed', 'users', (int)$user['id'], ['email' => $email, 'reason' => 'invalid_password']);
        flash('auth_error', 'Neteisingi prisijungimo duomenys.');
        return false;
    }

    if ((int)$user['is_active'] !== 1 || ($user['status'] ?? 'inactive') !== 'active') {
        record_failed_login_attempts($email, $ip);
        audit_log((int)$user['id'], 'login_failed', 'users', (int)$user['id'], ['email' => $email, 'reason' => 'inactive_account']);
        flash('auth_error', 'Paskyra dar neaktyvi arba yra užblokuota.');
        return false;
    }

    clear_failed_login_attempts($email, $ip);
    session_regenerate_id(true);
    $_SESSION['user'] = $user;
    audit_log((int)$user['id'], 'login_success', 'users', (int)$user['id']);
    return true;
}

function register_user($username, $email, $password)
{
    $payload = [
        'username' => trim((string)$username),
        'email' => normalize_email($email),
        'password' => (string)$password,
        'role_id' => 4,
        'status' => 'active',
    ];

    $ip = auth_client_ip();
    $rateLimitTargets = [
        [
            'scope' => 'register_email',
            'identifier' => $payload['email'],
            'max_attempts' => 3,
            'window_seconds' => 30 * 60,
            'lockout_seconds' => 30 * 60,
        ],
        [
            'scope' => 'register_ip',
            'identifier' => $ip,
            'max_attempts' => 10,
            'window_seconds' => 30 * 60,
            'lockout_seconds' => 30 * 60,
        ],
    ];

    $rateLimit = rate_limit_status($rateLimitTargets);
    if ($rateLimit['blocked']) {
        audit_log(null, 'register_blocked', 'users', null, [
            'email' => $payload['email'],
            'ip' => $ip,
            'retry_after_seconds' => $rateLimit['retry_after'],
        ]);
        return ['Per daug registracijos bandymų. Bandykite po ' . format_wait_time($rateLimit['retry_after']) . '.'];
    }

    rate_limit_hit($rateLimitTargets);

    $errors = validate_user_payload($payload, 'create');
    if ($errors) {
        return $errors;
    }

    $stmt = $GLOBALS['pdo']->prepare("
        INSERT INTO users (username, email, password, role_id, is_active, status, created_at)
        VALUES (:username, :email, :password, :role_id, 1, 'active', NOW())
    ");

    $stmt->execute([
        ':username' => $payload['username'],
        ':email' => $payload['email'],
        ':password' => password_hash($payload['password'], PASSWORD_DEFAULT),
        ':role_id' => $payload['role_id'],
    ]);

    $userId = (int)$GLOBALS['pdo']->lastInsertId();
    audit_log($userId, 'register_success', 'users', $userId, ['email' => $payload['email']]);
    return [];
}

function sync_session_user($pdoOrUserId = null, $userId = null)
{
    if ($pdoOrUserId instanceof PDO) {
        $userId = (int)$userId;
    } elseif ($userId === null) {
        $userId = (int)$pdoOrUserId;
    }

    if ($userId < 1) {
        $userId = (int)($_SESSION['user']['id'] ?? 0);
    }
    if ($userId < 1) {
        unset($_SESSION['user']);
        return null;
    }

    $user = fetch_user_for_session($userId);
    if (
        !$user ||
        (int)($user['is_active'] ?? 0) !== 1 ||
        ($user['status'] ?? 'inactive') !== 'active'
    ) {
        unset($_SESSION['user']);
        return null;
    }

    $_SESSION['user'] = $user;
    return $user;
}

function current_user()
{
    static $resolved = false;
    static $cached = null;

    if (!$resolved) {
        $cached = empty($_SESSION['user']['id']) ? null : sync_session_user((int)$_SESSION['user']['id']);
        $resolved = true;
    }

    return $cached;
}

function auth_error()
{
    return flash('auth_error');
}

function require_login()
{
    if (current_user()) {
        return;
    }

    $target = defined('IN_ADMIN') ? 'administration/login.php' : 'login.php';
    redirect(public_path($target));
}

function require_login_page()
{
    require_login();
}
