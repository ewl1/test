<?php
function current_user()
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in()
{
    return !empty($_SESSION['user']['id']);
}

function has_permission(PDO $pdo, $permissionSlug)
{
    if (empty($_SESSION['user']['id'])) {
        return false;
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM users u
        JOIN role_permissions rp ON rp.role_id = u.role_id
        JOIN permissions p ON p.id = rp.permission_id
        WHERE u.id = :user_id
          AND u.status = 'active'
          AND u.is_active = 1
          AND p.slug = :slug
    ");
    $stmt->execute([
        ':user_id' => $_SESSION['user']['id'],
        ':slug' => $permissionSlug,
    ]);

    return (int)$stmt->fetchColumn() > 0;
}

function require_login_page()
{
    if (!is_logged_in()) {
        flash('error', 'Pirmiausia prisijunkite.');
        redirect('login.php');
    }
}

function require_permission(PDO $pdo, $permissionSlug)
{
    if (!has_permission($pdo, $permissionSlug)) {
        audit_log($pdo, $_SESSION['user']['id'] ?? null, 'permission_denied', 'permission', null, ['slug' => $permissionSlug]);
        http_response_code(403);
        exit('Neturite teisės atlikti šio veiksmo.');
    }
}
