<?php
function auth_security_log_table_name()
{
    return 'security_logs';
}

function ensure_auth_security_log_schema()
{
    static $ensured = false;
    if ($ensured || empty($GLOBALS['pdo']) || !($GLOBALS['pdo'] instanceof PDO)) {
        return;
    }

    $ensured = true;

    try {
        $GLOBALS['pdo']->exec("
            CREATE TABLE IF NOT EXISTS " . auth_security_log_table_name() . " (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED DEFAULT NULL,
                event VARCHAR(64) NOT NULL,
                status VARCHAR(16) NOT NULL DEFAULT 'info',
                severity VARCHAR(16) NOT NULL DEFAULT 'info',
                subject_type VARCHAR(64) DEFAULT NULL,
                subject_id BIGINT UNSIGNED DEFAULT NULL,
                subject_label VARCHAR(190) DEFAULT NULL,
                email VARCHAR(190) DEFAULT NULL,
                reason VARCHAR(255) DEFAULT NULL,
                ip_address VARBINARY(16) DEFAULT NULL,
                user_agent VARCHAR(255) DEFAULT NULL,
                method VARCHAR(16) DEFAULT NULL,
                url VARCHAR(255) DEFAULT NULL,
                details TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_security_logs_user (user_id, created_at),
                KEY idx_security_logs_event (event, created_at),
                KEY idx_security_logs_status (status, created_at),
                KEY idx_security_logs_subject (subject_type, subject_id),
                KEY idx_security_logs_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } catch (Throwable $e) {
    }
}

function auth_security_event_meta($event)
{
    $meta = [
        'login_success' => ['label' => 'Sėkmingas prisijungimas', 'status' => 'success', 'severity' => 'info'],
        'login_failed' => ['label' => 'Nesėkmingas prisijungimas', 'status' => 'failed', 'severity' => 'warning'],
        'login_blocked' => ['label' => 'Prisijungimas užblokuotas', 'status' => 'blocked', 'severity' => 'warning'],
        'logout' => ['label' => 'Atsijungimas', 'status' => 'success', 'severity' => 'info'],
        'admin_login_success' => ['label' => 'Sėkmingas admin prisijungimas', 'status' => 'success', 'severity' => 'info'],
        'admin_login_failed' => ['label' => 'Nesėkmingas admin prisijungimas', 'status' => 'failed', 'severity' => 'warning'],
        'admin_login_blocked' => ['label' => 'Admin prisijungimas užblokuotas', 'status' => 'blocked', 'severity' => 'warning'],
        'register_success' => ['label' => 'Sėkminga registracija', 'status' => 'success', 'severity' => 'info'],
        'register_blocked' => ['label' => 'Registracija užblokuota', 'status' => 'blocked', 'severity' => 'warning'],
        'password_reset_requested' => ['label' => 'Paprašytas slaptažodžio atstatymas', 'status' => 'success', 'severity' => 'info'],
        'password_reset_request_blocked' => ['label' => 'Slaptažodžio atstatymas užblokuotas', 'status' => 'blocked', 'severity' => 'warning'],
        'password_reset_email_fallback' => ['label' => 'Slaptažodžio atstatymo laiško fallback', 'status' => 'warning', 'severity' => 'warning'],
        'password_reset_submit_blocked' => ['label' => 'Slaptažodžio keitimas užblokuotas', 'status' => 'blocked', 'severity' => 'warning'],
        'password_reset_invalid_token' => ['label' => 'Netinkamas atstatymo raktas', 'status' => 'failed', 'severity' => 'warning'],
        'password_reset_completed' => ['label' => 'Slaptažodis pakeistas', 'status' => 'success', 'severity' => 'info'],
        'user_password_change' => ['label' => 'Pakeistas paskyros slaptažodis', 'status' => 'success', 'severity' => 'info'],
        'admin_password_update' => ['label' => 'Pakeistas admin slaptažodis', 'status' => 'success', 'severity' => 'warning'],
        'csrf_invalid' => ['label' => 'Neteisingas CSRF raktas', 'status' => 'failed', 'severity' => 'warning'],
    ];

    return $meta[(string)$event] ?? [
        'label' => (string)$event,
        'status' => 'info',
        'severity' => 'info',
    ];
}

function auth_security_log($userId, $event, $subjectType = null, $subjectId = null, array $payload = [])
{
    ensure_auth_security_log_schema();

    try {
        $meta = auth_security_event_meta($event);
        $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
        $packedIp = filter_var($ip, FILTER_VALIDATE_IP) ? @inet_pton($ip) : null;

        $details = $payload['details'] ?? null;
        if ($details === null) {
            $details = $payload;
            unset(
                $details['subject_label'],
                $details['email'],
                $details['reason'],
                $details['status'],
                $details['severity']
            );
        }

        $detailsJson = null;
        if (is_array($details) && $details !== []) {
            $detailsJson = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif (is_string($details) && trim($details) !== '') {
            $detailsJson = $details;
        }

        $stmt = $GLOBALS['pdo']->prepare("
            INSERT INTO " . auth_security_log_table_name() . "
                (user_id, event, status, severity, subject_type, subject_id, subject_label, email, reason, ip_address, user_agent, method, url, details, created_at)
            VALUES
                (:user_id, :event, :status, :severity, :subject_type, :subject_id, :subject_label, :email, :reason, :ip_address, :user_agent, :method, :url, :details, NOW())
        ");
        $stmt->execute([
            ':user_id' => $userId ? (int)$userId : null,
            ':event' => trim((string)$event),
            ':status' => trim((string)($payload['status'] ?? $meta['status'])),
            ':severity' => trim((string)($payload['severity'] ?? $meta['severity'])),
            ':subject_type' => $subjectType !== null && $subjectType !== '' ? (string)$subjectType : null,
            ':subject_id' => $subjectId !== null ? (int)$subjectId : null,
            ':subject_label' => ($payload['subject_label'] ?? '') !== '' ? (string)$payload['subject_label'] : null,
            ':email' => ($payload['email'] ?? '') !== '' ? (string)$payload['email'] : null,
            ':reason' => ($payload['reason'] ?? '') !== '' ? (string)$payload['reason'] : null,
            ':ip_address' => $packedIp,
            ':user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ':method' => substr((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'), 0, 16),
            ':url' => substr((string)($_SERVER['REQUEST_URI'] ?? ''), 0, 255),
            ':details' => $detailsJson,
        ]);
    } catch (Throwable $e) {
    }
}

function auth_security_pretty_details($value)
{
    $decoded = json_decode((string)$value, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    return (string)$value;
}

function auth_security_sync_from_audit($userId, $action, $entityType, $entityId, $details = null)
{
    $action = (string)$action;
    $entityType = (string)$entityType;
    $decodedDetails = is_array($details) ? $details : (json_decode((string)$details, true) ?: []);

    $supported = [
        'login_success',
        'login_failed',
        'login_blocked',
        'admin_login_success',
        'admin_login_failed',
        'admin_login_blocked',
        'register_success',
        'register_blocked',
        'password_reset_requested',
        'password_reset_request_blocked',
        'password_reset_email_fallback',
        'password_reset_submit_blocked',
        'password_reset_invalid_token',
        'password_reset_completed',
        'user_password_change',
        'admin_password_update',
    ];

    if (!in_array($action, $supported, true)) {
        return;
    }

    $subjectType = match ($action) {
        'login_success', 'login_failed', 'login_blocked',
        'admin_login_success', 'admin_login_failed', 'admin_login_blocked',
        'register_success', 'register_blocked',
        'user_password_change', 'admin_password_update' => 'user',
        'password_reset_requested', 'password_reset_request_blocked',
        'password_reset_email_fallback', 'password_reset_submit_blocked',
        'password_reset_invalid_token', 'password_reset_completed' => 'password_reset',
        default => $entityType !== '' ? $entityType : 'security',
    };

    $subjectLabel = '';
    if (!empty($decodedDetails['username'])) {
        $subjectLabel = (string)$decodedDetails['username'];
    } elseif (!empty($decodedDetails['email'])) {
        $subjectLabel = (string)$decodedDetails['email'];
    } elseif ($entityId) {
        $subjectLabel = ucfirst(str_replace('_', ' ', $subjectType)) . ' #' . (int)$entityId;
    }

    auth_security_log($userId, $action, $subjectType, $entityId ?: null, [
        'subject_label' => $subjectLabel,
        'email' => (string)($decodedDetails['email'] ?? ''),
        'reason' => (string)($decodedDetails['reason'] ?? ''),
        'details' => $decodedDetails,
    ]);
}

ensure_auth_security_log_schema();
