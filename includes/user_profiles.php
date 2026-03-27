<?php
function profile_table_exists($table)
{
    static $cache = [];
    $table = (string)$table;
    if (isset($cache[$table])) {
        return $cache[$table];
    }

    try {
        $stmt = $GLOBALS['pdo']->query('SHOW TABLES LIKE ' . $GLOBALS['pdo']->quote($table));
        $cache[$table] = (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        $cache[$table] = false;
    }

    return $cache[$table];
}

function profile_column_exists($table, $column)
{
    static $cache = [];
    $key = $table . '.' . $column;
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    try {
        $stmt = $GLOBALS['pdo']->prepare('SHOW COLUMNS FROM `' . str_replace('`', '``', (string)$table) . '` LIKE :column');
        $stmt->execute([':column' => (string)$column]);
        $cache[$key] = (bool)$stmt->fetch();
    } catch (Throwable $e) {
        $cache[$key] = false;
    }

    return $cache[$key];
}

function ensure_user_profile_schema()
{
    static $ensured = false;
    if ($ensured || !profile_table_exists('users')) {
        return;
    }

    $ensured = true;

    try {
        if (!profile_column_exists('users', 'signature')) {
            $GLOBALS['pdo']->exec('ALTER TABLE users ADD COLUMN signature TEXT NULL AFTER avatar');
        }
    } catch (Throwable $e) {
    }

    try {
        if (!profile_column_exists('users', 'admin_password')) {
            $GLOBALS['pdo']->exec('ALTER TABLE users ADD COLUMN admin_password VARCHAR(255) NULL AFTER password');
        }
    } catch (Throwable $e) {
    }
}

function clean_user_signature($signature, $maxLength = 500)
{
    $signature = trim((string)$signature);
    if ($signature === '') {
        return '';
    }

    $signature = preg_replace("/\r\n?/", "\n", $signature);
    $signature = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $signature);
    if (mb_strlen($signature) > $maxLength) {
        $signature = mb_substr($signature, 0, $maxLength);
    }

    return trim($signature);
}

function render_user_signature($signature)
{
    $signature = clean_user_signature($signature);
    if ($signature === '') {
        return '';
    }

    return nl2br(e($signature));
}

function user_profile_url($userId)
{
    return public_path('user.php?id=' . (int)$userId);
}

function fetch_public_user_profile($userId)
{
    ensure_user_profile_schema();

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

function fetch_user_latest_ip($userId)
{
    if (!profile_table_exists('audit_logs')) {
        return null;
    }

    $stmt = $GLOBALS['pdo']->prepare("
        SELECT COALESCE(NULLIF(INET6_NTOA(ip_address), ''), NULLIF(CAST(ip_address AS CHAR(45)), ''), '') AS ip_text
        FROM audit_logs
        WHERE user_id = :id
          AND ip_address IS NOT NULL
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([':id' => (int)$userId]);
    $ip = trim((string)$stmt->fetchColumn());

    return $ip !== '' ? $ip : null;
}

function fetch_ip_ban_status($ip)
{
    $ip = trim((string)$ip);
    if ($ip === '' || !profile_table_exists('ip_bans')) {
        return null;
    }

    try {
        $stmt = $GLOBALS['pdo']->prepare("
            SELECT reason, banned_until, is_permanent
            FROM ip_bans
            WHERE ip_address = INET6_ATON(:ip)
              AND (is_permanent = 1 OR banned_until IS NULL OR banned_until > NOW())
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([':ip' => $ip]);
        $ban = $stmt->fetch();

        return $ban ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function count_user_shoutbox_messages($userId)
{
    $userId = (int)$userId;
    if ($userId < 1) {
        return 0;
    }

    $table = profile_table_exists('infusion_shoutbox_messages') ? 'infusion_shoutbox_messages' : (profile_table_exists('shouts') ? 'shouts' : null);
    if ($table === null) {
        return 0;
    }

    $stmt = $GLOBALS['pdo']->prepare('SELECT COUNT(*) FROM ' . $table . ' WHERE user_id = :user_id');
    $stmt->execute([':user_id' => $userId]);

    return (int)$stmt->fetchColumn();
}

ensure_user_profile_schema();
