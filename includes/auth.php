<?php
function login($email, $password)
{
    $stmt = $GLOBALS['pdo']->prepare("SELECT u.*, r.name AS role_name FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.email=? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
        return true;
    }
    return false;
}
function current_user(){ return $_SESSION['user'] ?? null; }
function require_login(){ if (!current_user()) redirect(public_path('login.php')); }
