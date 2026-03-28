<?php
function permission_candidates($permission)
{
    $permission = (string)$permission;
    $map = [
        'users.view' => ['users.view', 'users.manage'],
        'users.create' => ['users.create', 'users.manage'],
        'users.edit' => ['users.edit', 'users.manage'],
        'users.status' => ['users.status', 'users.manage'],
        'users.delete' => ['users.delete', 'users.manage'],
        'themes.manage' => ['themes.manage', 'settings.manage'],
        'navigation.manage' => ['navigation.manage', 'settings.manage'],
    ];

    return $map[$permission] ?? [$permission];
}

function has_permission($pdo, $user_id, $perm)
{
    $user_id = (int)$user_id;
    if ($user_id < 1) {
        return false;
    }

    static $roleCache = [];
    if (!array_key_exists($user_id, $roleCache)) {
        $stmt = $pdo->prepare("
            SELECT r.slug, r.level
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE u.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $user_id]);
        $roleCache[$user_id] = $stmt->fetch() ?: ['slug' => null, 'level' => 0];
    }

    $role = $roleCache[$user_id];
    if (($role['slug'] ?? '') === 'super_admin' || (int)($role['level'] ?? 0) >= 100) {
        return true;
    }

    $candidates = permission_candidates($perm);
    $placeholders = implode(',', array_fill(0, count($candidates), '?'));
    $sql = "SELECT COUNT(*) FROM users
            JOIN role_permissions rp ON rp.role_id = users.role_id
            JOIN permissions p ON p.id = rp.permission_id
            WHERE users.id = ? AND p.slug IN ($placeholders)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge([$user_id], $candidates));
    return (int)$stmt->fetchColumn() > 0;
}

function require_permission($permissionOrPdo, $legacyPermission = null)
{
    $permission = $permissionOrPdo instanceof PDO ? (string)$legacyPermission : (string)$permissionOrPdo;
    $user = current_user();

    if (!$user) {
        require_login();
        return;
    }

    if (!has_permission($GLOBALS['pdo'], $user['id'], $permission)) {
        abort_http(403, __('permissions.denied_specific', ['permission' => $permission]));
    }
}

function require_any_permission(array $permissions)
{
    $user = current_user();
    if (!$user) {
        require_login();
        return;
    }

    foreach ($permissions as $permission) {
        if (has_permission($GLOBALS['pdo'], $user['id'], $permission)) {
            return;
        }
    }

    abort_http(403, __('permissions.denied'));
}

function can_manage_role_id($roleId)
{
    $user = current_user();
    if (!$user) {
        return false;
    }
    if (has_permission($GLOBALS['pdo'], (int)$user['id'], 'admin.access') && (($user['role_slug'] ?? '') === 'super_admin' || (int)($user['role_level'] ?? 0) >= 100)) {
        return true;
    }

    $stmt = $GLOBALS['pdo']->prepare("SELECT level FROM roles WHERE id = :id");
    $stmt->execute([':id' => (int)$roleId]);
    $targetLevel = (int)$stmt->fetchColumn();

    $myStmt = $GLOBALS['pdo']->prepare("SELECT level FROM roles WHERE id = :id");
    $myStmt->execute([':id' => (int)$user['role_id']]);
    $myLevel = (int)$myStmt->fetchColumn();

    return $myLevel >= $targetLevel;
}
