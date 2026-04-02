<?php

function shoutbox_escape_and_format($message)
{
    $message = bbcode_to_html((string)$message, [
        'allowed_tags' => shoutbox_allowed_tags(),
        'max_length' => 500,
    ]);

    $message = apply_site_smileys($message, 'shoutbox-smiley');

    return shoutbox_apply_mentions($message);
}

function shoutbox_get_messages($limit = 50, $offset = 0, $order = null)
{
    $limit = max(1, (int)$limit);
    $offset = max(0, (int)$offset);
    $sqlOrder = strtoupper(shoutbox_normalize_order($order));

    $stmt = $GLOBALS['pdo']->prepare("
        SELECT m.*, u.username, u.avatar, u.email
        FROM " . shoutbox_table_name() . " m
        LEFT JOIN users u ON u.id = m.user_id
        ORDER BY m.created_at {$sqlOrder}, m.id {$sqlOrder}
        LIMIT {$limit} OFFSET {$offset}
    ");
    $stmt->execute();

    return $stmt->fetchAll();
}

function shoutbox_message_path($messageId)
{
    $stmt = $GLOBALS['pdo']->prepare('SELECT id, created_at FROM ' . shoutbox_table_name() . ' WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => (int)$messageId]);
    $message = $stmt->fetch();
    if (!$message) {
        return 'shoutbox.php';
    }

    $operator = shoutbox_message_order() === 'desc' ? '>' : '<';
    $stmt = $GLOBALS['pdo']->prepare("
        SELECT COUNT(*)
        FROM " . shoutbox_table_name() . "
        WHERE created_at {$operator} :created_at_compare
           OR (created_at = :created_at_exact AND id {$operator} :id)
    ");
    $stmt->execute([
        ':created_at_compare' => $message['created_at'],
        ':created_at_exact' => $message['created_at'],
        ':id' => (int)$message['id'],
    ]);

    $position = (int)$stmt->fetchColumn() + 1;
    $page = max(1, (int)ceil($position / shoutbox_messages_per_page()));
    $path = 'shoutbox.php';
    if ($page > 1) {
        $path .= '?page=' . $page;
    }

    return $path . '#shoutbox-message-' . (int)$messageId;
}

function shoutbox_message_url($messageId)
{
    return public_path(shoutbox_message_path($messageId));
}

function shoutbox_create_message($message)
{
    $user = current_user();
    if (!$user) {
        return [false, __('shoutbox.post.login')];
    }

    $message = sanitize_bbcode_input($message, shoutbox_allowed_tags(), 500);
    if ($message === '') {
        return [false, __('shoutbox.message.empty')];
    }

    $stmt = $GLOBALS['pdo']->prepare("
        INSERT INTO " . shoutbox_table_name() . " (user_id, message, created_at, updated_at)
        VALUES (:user_id, :message, NOW(), NOW())
    ");
    $stmt->execute([
        ':user_id' => (int)$user['id'],
        ':message' => $message,
    ]);

    audit_log((int)$user['id'], 'shoutbox_post', 'infusion_shoutbox_messages', (int)$GLOBALS['pdo']->lastInsertId());
    if (function_exists('shoutbox_bot_try_respond')) {
        shoutbox_bot_try_respond($message);
    }
    return [true, __('shoutbox.message.created')];
}

function shoutbox_delete_message($id)
{
    $messageStmt = $GLOBALS['pdo']->prepare('SELECT id, message, user_id FROM ' . shoutbox_table_name() . ' WHERE id = :id LIMIT 1');
    $messageStmt->execute([':id' => (int)$id]);
    $message = $messageStmt->fetch();

    $stmt = $GLOBALS['pdo']->prepare('DELETE FROM ' . shoutbox_table_name() . ' WHERE id = :id');
    $stmt->execute([':id' => (int)$id]);
    audit_log(current_user()['id'] ?? null, 'shoutbox_delete', 'infusion_shoutbox_messages', (int)$id);
    if ($message) {
        moderation_log(current_user()['id'] ?? null, 'shoutbox_message_deleted', 'shoutbox_message', (int)$id, [
            'target_label' => moderation_log_excerpt((string)$message['message']),
            'context_type' => 'shoutbox',
            'context_id' => (int)$id,
            'details' => [
                'message_user_id' => isset($message['user_id']) ? (int)$message['user_id'] : null,
            ],
        ]);
    }
}
