<?php
function has_permission($pdo, $user_id, $perm)
{
    $sql = "SELECT COUNT(*) FROM users
            JOIN role_permissions rp ON rp.role_id=users.role_id
            JOIN permissions p ON p.id=rp.permission_id
            WHERE users.id=? AND p.slug=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([(int)$user_id, (string)$perm]);
    return (int)$stmt->fetchColumn() > 0;
}

function require_permission($permission)
{
    $user = current_user();
    if (!$user) {
        http_response_code(403);
        die('Reikia prisijungti.');
    }
    if (!has_permission($GLOBALS['pdo'], $user['id'], $permission)) {
        http_response_code(403);
        die('Nepakanka teisių: ' . e($permission));
    }
}

function require_any_permission(array $permissions)
{
    $user = current_user();
    if (!$user) {
        http_response_code(403);
        die('Reikia prisijungti.');
    }
    foreach ($permissions as $permission) {
        if (has_permission($GLOBALS['pdo'], $user['id'], $permission)) {
            return;
        }
    }
    http_response_code(403);
    die('Nepakanka teisių.');
}

function can_manage_role_id($roleId)
{
    $user = current_user();
    if (!$user) return false;

    $stmt = $GLOBALS['pdo']->prepare("SELECT level FROM roles WHERE id = :id");
    $stmt->execute([':id' => (int)$roleId]);
    $targetLevel = (int)$stmt->fetchColumn();

    $myStmt = $GLOBALS['pdo']->prepare("SELECT level FROM roles WHERE id = :id");
    $myStmt->execute([':id' => (int)$user['role_id']]);
    $myLevel = (int)$myStmt->fetchColumn();

    return $myLevel >= $targetLevel;
}
