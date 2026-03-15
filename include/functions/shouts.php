<?php
function get_shouts(PDO $pdo, $limit = 30)
{
    $stmt = $pdo->prepare("
        SELECT s.*, u.username, u.email, u.avatar
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
    $message = trim($message);
    if ($message === '') {
        return [false, 'Tuščia žinutė.'];
    }
    $message = mb_substr($message, 0, 500);
    $stmt = $pdo->prepare("INSERT INTO shouts (user_id, message, created_at, updated_at) VALUES (:user_id, :message, NOW(), NOW())");
    $stmt->execute([':user_id' => $_SESSION['user']['id'] ?? null, ':message' => $message]);
    $id = (int)$pdo->lastInsertId();
    audit_log($pdo, $_SESSION['user']['id'] ?? null, 'shout_create', 'shouts', $id);
    return [true, 'Žinutė paskelbta.'];
}

function update_shout(PDO $pdo, $id, $message)
{
    $message = trim($message);
    if ($message === '') {
        return [false, 'Tuščia žinutė.'];
    }
    $stmt = $pdo->prepare("UPDATE shouts SET message = :message, updated_at = NOW() WHERE id = :id");
    $stmt->execute([':message'=>$message, ':id'=>$id]);
    audit_log($pdo, $_SESSION['user']['id'] ?? null, 'shout_update', 'shouts', $id);
    return [true, 'Žinutė atnaujinta.'];
}

function delete_shout(PDO $pdo, $id)
{
    $stmt = $pdo->prepare("DELETE FROM shouts WHERE id = :id");
    $stmt->execute([':id'=>$id]);
    audit_log($pdo, $_SESSION['user']['id'] ?? null, 'shout_delete', 'shouts', $id);
}
