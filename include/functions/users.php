<?php
function get_all_users(PDO $pdo)
{
    $stmt = $pdo->query("
        SELECT u.*, r.name AS role_name
        FROM users u
        LEFT JOIN roles r ON r.id = u.role_id
        ORDER BY u.id DESC
    ");
    return $stmt->fetchAll();
}

function get_user(PDO $pdo, $id)
{
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

function update_user_profile(PDO $pdo, $id, array $data)
{
    $username = trim($data['username'] ?? '');
    $email = trim($data['email'] ?? '');
    if ($username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Neteisingi duomenys.'];
    }

    $stmt = $pdo->prepare("UPDATE users SET username=:username, email=:email WHERE id=:id");
    $stmt->execute([':username'=>$username, ':email'=>$email, ':id'=>$id]);
    audit_log($pdo, $_SESSION['user']['id'] ?? null, 'user_edit', 'users', $id);
    return [true, 'Vartotojas atnaujintas.'];
}

function change_user_status(PDO $pdo, $id, $status)
{
    $allowed = ['active','inactive','blocked','deleted'];
    if (!in_array($status, $allowed, true)) {
        return;
    }
    $isActive = $status === 'active' ? 1 : 0;
    $stmt = $pdo->prepare("UPDATE users SET status=:status, is_active=:is_active WHERE id=:id");
    $stmt->execute([':status'=>$status, ':is_active'=>$isActive, ':id'=>$id]);
    audit_log($pdo, $_SESSION['user']['id'] ?? null, 'user_status_change', 'users', $id, ['status' => $status]);
}

function delete_user(PDO $pdo, $id)
{
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute([':id'=>$id]);
    audit_log($pdo, $_SESSION['user']['id'] ?? null, 'user_delete', 'users', $id);
}
