<?php
function get_shouts(PDO $pdo, $limit = 30)
{
    $stmt = $pdo->prepare("
        SELECT s.*, u.username, u.email
        FROM shouts s
        LEFT JOIN users u ON u.id = s.user_id
        ORDER BY s.created_at DESC
        LIMIT " . (int)$limit
    );
    $stmt->execute();
    return array_reverse($stmt->fetchAll());
}

function create_shout(PDO $pdo, $message)
{
    $message = trim((string)$message);
    if ($message === '') {
        return [false, 'Tuščia žinutė.'];
    }

    $user = current_user();
    if (!$user) {
        return [false, 'Rašyti gali tik prisijungę nariai.'];
    }

    $message = mb_substr($message, 0, 500);
    $stmt = $pdo->prepare("INSERT INTO shouts (user_id, message, created_at, updated_at) VALUES (:user_id, :message, NOW(), NOW())");
    $stmt->execute([
        ':user_id' => (int)$user['id'],
        ':message' => $message
    ]);

    $id = (int)$pdo->lastInsertId();
    audit_log((int)$user['id'], 'shout_create', 'shouts', $id);
    return [true, 'Žinutė paskelbta.'];
}

function update_shout(PDO $pdo, $id, $message)
{
    $message = trim((string)$message);
    if ($message === '') {
        return [false, 'Tuščia žinutė.'];
    }

    $stmt = $pdo->prepare("UPDATE shouts SET message = :message, updated_at = NOW() WHERE id = :id");
    $stmt->execute([':message' => mb_substr($message, 0, 500), ':id' => (int)$id]);
    audit_log(current_user()['id'] ?? null, 'shout_update', 'shouts', (int)$id);
    return [true, 'Žinutė atnaujinta.'];
}

function delete_shout(PDO $pdo, $id)
{
    $stmt = $pdo->prepare("DELETE FROM shouts WHERE id = :id");
    $stmt->execute([':id' => (int)$id]);
    audit_log(current_user()['id'] ?? null, 'shout_delete', 'shouts', (int)$id);
}
