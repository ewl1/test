<?php
function moderation_log_table_name()
{
    return 'moderation_logs';
}

function ensure_moderation_log_schema()
{
    static $ensured = false;
    if ($ensured || empty($GLOBALS['pdo']) || !($GLOBALS['pdo'] instanceof PDO)) {
        return;
    }

    $ensured = true;

    try {
        $GLOBALS['pdo']->exec("
            CREATE TABLE IF NOT EXISTS " . moderation_log_table_name() . " (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                moderator_user_id INT UNSIGNED DEFAULT NULL,
                action VARCHAR(64) NOT NULL,
                target_type VARCHAR(64) NOT NULL,
                target_id BIGINT UNSIGNED DEFAULT NULL,
                target_label VARCHAR(190) DEFAULT NULL,
                context_type VARCHAR(64) DEFAULT NULL,
                context_id BIGINT UNSIGNED DEFAULT NULL,
                reason VARCHAR(255) DEFAULT NULL,
                ip_address VARBINARY(16) DEFAULT NULL,
                user_agent VARCHAR(255) DEFAULT NULL,
                url VARCHAR(255) DEFAULT NULL,
                details TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_moderation_logs_moderator (moderator_user_id, created_at),
                KEY idx_moderation_logs_action (action, created_at),
                KEY idx_moderation_logs_target (target_type, target_id),
                KEY idx_moderation_logs_context (context_type, context_id),
                KEY idx_moderation_logs_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } catch (Throwable $e) {
    }
}

function moderation_log_excerpt($value, $length = 140)
{
    $value = trim((string)$value);
    $value = preg_replace('/\[(\/?)[a-z]+(?:=[^\]]*)?\]/i', '', $value);
    $value = trim(preg_replace('/\s+/u', ' ', strip_tags($value)));

    if ($value === '' || mb_strlen($value) <= $length) {
        return $value;
    }

    return rtrim(mb_substr($value, 0, $length - 1)) . '...';
}

function moderation_log($moderatorUserId, $action, $targetType, $targetId = null, array $payload = [])
{
    ensure_moderation_log_schema();

    try {
        $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
        $packedIp = filter_var($ip, FILTER_VALIDATE_IP) ? @inet_pton($ip) : null;

        $targetLabel = trim((string)($payload['target_label'] ?? ''));
        $contextType = trim((string)($payload['context_type'] ?? ''));
        $contextId = isset($payload['context_id']) ? (int)$payload['context_id'] : null;
        $reason = trim((string)($payload['reason'] ?? ''));

        $details = $payload['details'] ?? null;
        if ($details === null) {
            $details = $payload;
            unset($details['target_label'], $details['context_type'], $details['context_id'], $details['reason']);
        }

        $detailsJson = null;
        if (is_array($details) && $details !== []) {
            $detailsJson = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif (is_string($details) && trim($details) !== '') {
            $detailsJson = $details;
        }

        $stmt = $GLOBALS['pdo']->prepare("
            INSERT INTO " . moderation_log_table_name() . "
                (moderator_user_id, action, target_type, target_id, target_label, context_type, context_id, reason, ip_address, user_agent, url, details, created_at)
            VALUES
                (:moderator_user_id, :action, :target_type, :target_id, :target_label, :context_type, :context_id, :reason, :ip_address, :user_agent, :url, :details, NOW())
        ");
        $stmt->execute([
            ':moderator_user_id' => $moderatorUserId ? (int)$moderatorUserId : null,
            ':action' => trim((string)$action),
            ':target_type' => trim((string)$targetType),
            ':target_id' => $targetId !== null ? (int)$targetId : null,
            ':target_label' => $targetLabel !== '' ? $targetLabel : null,
            ':context_type' => $contextType !== '' ? $contextType : null,
            ':context_id' => $contextId ?: null,
            ':reason' => $reason !== '' ? $reason : null,
            ':ip_address' => $packedIp,
            ':user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ':url' => substr((string)($_SERVER['REQUEST_URI'] ?? ''), 0, 255),
            ':details' => $detailsJson,
        ]);
    } catch (Throwable $e) {
    }
}

function moderation_pretty_details($value)
{
    $decoded = json_decode((string)$value, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    return (string)$value;
}

function moderation_action_label($action)
{
    return [
        'forum_topic_updated' => 'Forumo tema redaguota',
        'forum_topic_pinned' => 'Forumo tema prisegta',
        'forum_topic_unpinned' => 'Forumo temos prisegimas nuimtas',
        'forum_topic_locked' => 'Forumo tema užrakinta',
        'forum_topic_unlocked' => 'Forumo tema atrakinta',
        'forum_topic_deleted' => 'Forumo tema ištrinta',
        'forum_reply_updated' => 'Forumo atsakymas redaguotas',
        'forum_reply_deleted' => 'Forumo atsakymas ištrintas',
        'shoutbox_message_deleted' => 'Šaukyklos žinutė ištrinta',
        'legacy_shout_deleted' => 'Seno tipo šaukyklos žinutė ištrinta',
        'profile_comment_deleted' => 'Profilio komentaras ištrintas',
        'user_activated' => 'Vartotojas aktyvuotas',
        'user_deactivated' => 'Vartotojas deaktyvuotas',
        'user_blocked' => 'Vartotojas užblokuotas',
        'user_deleted' => 'Vartotojas ištrintas',
        'ip_ban_saved' => 'IP draudimas atnaujintas',
        'post_deleted' => 'Įrašas ištrintas',
    ][(string)$action] ?? (string)$action;
}

function moderation_sync_from_audit($userId, $action, $entityType, $entityId, $details = null)
{
    $action = (string)$action;
    $entityType = (string)$entityType;

    if (in_array($action, ['forum_topic_update', 'forum_topic_pin_toggle', 'forum_topic_lock_toggle', 'forum_topic_delete', 'forum_reply_update', 'forum_reply_delete', 'shoutbox_delete', 'profile_comment_delete', 'shout_delete', 'post_delete', 'user_status_change'], true)) {
        return;
    }

    $decodedDetails = is_array($details) ? $details : (json_decode((string)$details, true) ?: []);

    if (in_array($action, ['user_activate', 'user_deactivate', 'user_block', 'user_delete'], true) && $entityType === 'users') {
        $label = 'User #' . (int)$entityId;
        if ($action !== 'user_delete') {
            try {
                $stmt = $GLOBALS['pdo']->prepare('SELECT username, email FROM users WHERE id = :id LIMIT 1');
                $stmt->execute([':id' => (int)$entityId]);
                $user = $stmt->fetch();
                if ($user) {
                    $label = (string)($user['username'] ?? $label);
                    $decodedDetails['email'] = (string)($user['email'] ?? '');
                }
            } catch (Throwable $e) {
            }
        }

        moderation_log($userId, match ($action) {
            'user_activate' => 'user_activated',
            'user_deactivate' => 'user_deactivated',
            'user_block' => 'user_blocked',
            default => 'user_deleted',
        }, 'user', $entityId, [
            'target_label' => $label,
            'details' => $decodedDetails,
        ]);
        return;
    }

    if ($action === 'ip_ban_save' && $entityType === 'ip_bans') {
        moderation_log($userId, 'ip_ban_saved', 'ip_ban', $entityId, [
            'target_label' => (string)($decodedDetails['ip'] ?? 'IP ban'),
            'details' => $decodedDetails,
        ]);
    }
}

ensure_moderation_log_schema();
