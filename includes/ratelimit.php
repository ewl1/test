<?php
function rate_limit_client_ip()
{
    $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
}

function ensure_security_rate_limit_table()
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $ensured = true;

    try {
        $GLOBALS['pdo']->exec("
            CREATE TABLE IF NOT EXISTS security_rate_limits (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                scope VARCHAR(64) NOT NULL,
                identifier VARCHAR(191) NOT NULL,
                attempts INT NOT NULL DEFAULT 0,
                first_attempt_at DATETIME NOT NULL,
                last_attempt_at DATETIME NOT NULL,
                locked_until DATETIME NULL DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_security_rate_limits_scope_identifier (scope, identifier),
                KEY idx_security_rate_limits_scope_locked (scope, locked_until),
                KEY idx_security_rate_limits_last_attempt (last_attempt_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } catch (Throwable $e) {
    }
}

function normalize_rate_limit_targets(array $targets)
{
    $normalized = [];

    foreach ($targets as $target) {
        $scope = trim((string)($target['scope'] ?? ''));
        $identifier = trim((string)($target['identifier'] ?? ''));
        if ($scope === '' || $identifier === '') {
            continue;
        }

        $normalized[] = [
            'scope' => $scope,
            'identifier' => $identifier,
            'max_attempts' => max(1, (int)($target['max_attempts'] ?? 5)),
            'window_seconds' => max(60, (int)($target['window_seconds'] ?? 900)),
            'lockout_seconds' => max(60, (int)($target['lockout_seconds'] ?? ($target['window_seconds'] ?? 900))),
        ];
    }

    return $normalized;
}

function fetch_security_rate_limit_row($scope, $identifier)
{
    ensure_security_rate_limit_table();

    try {
        $stmt = $GLOBALS['pdo']->prepare("
            SELECT *
            FROM security_rate_limits
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

function write_security_rate_limit_row($scope, $identifier, $attempts, $firstAttemptAt, $lastAttemptAt, $lockedUntil = null)
{
    ensure_security_rate_limit_table();

    try {
        $stmt = $GLOBALS['pdo']->prepare("
            INSERT INTO security_rate_limits (scope, identifier, attempts, first_attempt_at, last_attempt_at, locked_until)
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

function clear_security_rate_limit_row($scope, $identifier)
{
    ensure_security_rate_limit_table();

    try {
        $stmt = $GLOBALS['pdo']->prepare("
            DELETE FROM security_rate_limits
            WHERE scope = :scope AND identifier = :identifier
        ");
        $stmt->execute([
            ':scope' => (string)$scope,
            ':identifier' => (string)$identifier,
        ]);
    } catch (Throwable $e) {
    }
}

function rate_limit_status(array $targets)
{
    $targets = normalize_rate_limit_targets($targets);
    if (!$targets) {
        return [
            'blocked' => false,
            'retry_after' => 0,
            'blocked_until' => null,
        ];
    }

    $now = time();
    $blockedUntilTs = 0;

    foreach ($targets as $target) {
        $row = fetch_security_rate_limit_row($target['scope'], $target['identifier']);
        if (!$row) {
            continue;
        }

        $lastAttemptTs = strtotime((string)($row['last_attempt_at'] ?? '')) ?: 0;
        $lockedUntilTs = strtotime((string)($row['locked_until'] ?? '')) ?: 0;
        $windowExpired = $lastAttemptTs > 0 && ($now - $lastAttemptTs) > $target['window_seconds'];

        if ($windowExpired && $lockedUntilTs <= $now) {
            clear_security_rate_limit_row($target['scope'], $target['identifier']);
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

function rate_limit_hit(array $targets)
{
    $targets = normalize_rate_limit_targets($targets);
    if (!$targets) {
        return;
    }

    $now = time();
    $nowSql = date('Y-m-d H:i:s', $now);

    foreach ($targets as $target) {
        $row = fetch_security_rate_limit_row($target['scope'], $target['identifier']);
        $attempts = 1;
        $firstAttemptAt = $nowSql;

        if ($row) {
            $lastAttemptTs = strtotime((string)($row['last_attempt_at'] ?? '')) ?: 0;
            if ($lastAttemptTs > 0 && ($now - $lastAttemptTs) <= $target['window_seconds']) {
                $attempts = (int)($row['attempts'] ?? 0) + 1;
                $firstAttemptAt = (string)($row['first_attempt_at'] ?? $nowSql);
            }
        }

        $lockedUntil = null;
        if ($attempts >= $target['max_attempts']) {
            $lockedUntil = date('Y-m-d H:i:s', $now + $target['lockout_seconds']);
        }

        write_security_rate_limit_row(
            $target['scope'],
            $target['identifier'],
            $attempts,
            $firstAttemptAt,
            $nowSql,
            $lockedUntil
        );
    }
}

function rate_limit_clear(array $targets)
{
    foreach (normalize_rate_limit_targets($targets) as $target) {
        clear_security_rate_limit_row($target['scope'], $target['identifier']);
    }
}
