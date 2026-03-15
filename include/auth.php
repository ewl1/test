<?php
function sync_session_user(PDO $pdo, $userId)
{
    $stmt = $pdo->prepare("
        SELECT u.*, r.name AS role_name, r.slug AS role_slug
        FROM users u
        LEFT JOIN roles r ON r.id = u.role_id
        WHERE u.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $userId]);
    $_SESSION['user'] = $stmt->fetch() ?: null;
}

function login_user(PDO $pdo, $email, $password)
{
    $ip = client_ip();
    if (is_ip_banned($pdo, $ip)) {
        return [false, 'Jūsų IP užblokuotas.'];
    }

    if (!rate_limit_check($pdo, 'login', 5, 900)) {
        audit_log($pdo, null, 'login_rate_limited', 'auth', null, ['email' => $email]);
        return [false, 'Per daug prisijungimo bandymų.'];
    }

    $stmt = $pdo->prepare("
        SELECT u.*, r.name AS role_name, r.slug AS role_slug
        FROM users u
        LEFT JOIN roles r ON r.id = u.role_id
        WHERE u.email = :email
        LIMIT 1
    ");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        audit_log($pdo, null, 'login_failed', 'users', null, ['email' => $email]);
        return [false, 'Neteisingi prisijungimo duomenys.'];
    }

    if ((int)$user['is_active'] !== 1 || $user['status'] !== 'active') {
        return [false, 'Paskyra neaktyvi arba užblokuota.'];
    }

    session_regenerate_id(true);
    $_SESSION['user'] = $user;
    audit_log($pdo, $user['id'], 'login_success', 'users', $user['id']);
    return [true, 'Prisijungta.'];
}

function logout_user(PDO $pdo)
{
    if (!empty($_SESSION['user']['id'])) {
        audit_log($pdo, $_SESSION['user']['id'], 'logout', 'users', $_SESSION['user']['id']);
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', !empty($_SERVER['HTTPS']), true);
    }
    session_destroy();
}

function register_user(PDO $pdo, array $data)
{
    $username = trim($data['username'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = (string)($data['password'] ?? '');

    if ($username === '' || $email === '' || $password === '') {
        return [false, 'Užpildykite visus laukus.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Neteisingas el. paštas.'];
    }

    if (mb_strlen($password) < 8) {
        return [false, 'Slaptažodis per trumpas.'];
    }

    $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email OR username = :username");
    $check->execute([':email' => $email, ':username' => $username]);
    if ($check->fetchColumn()) {
        return [false, 'Toks vartotojas jau egzistuoja.'];
    }

    $token = random_token(16);
    $expires = date('Y-m-d H:i:s', time() + 86400);

    $stmt = $pdo->prepare("
        INSERT INTO users
            (username, email, password, role_id, is_active, activation_token, activation_expires, status, created_at)
        VALUES
            (:username, :email, :password, 4, 0, :token, :expires, 'inactive', NOW())
    ");
    $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':password' => password_hash($password, PASSWORD_DEFAULT),
        ':token' => $token,
        ':expires' => $expires,
    ]);

    $userId = (int)$pdo->lastInsertId();
    $link = SITE_URL . '/activate.php?token=' . urlencode($token);

    send_mail_message(
        $email,
        $username,
        'Paskyros aktyvacija',
        '<p>Sveiki,</p><p>Aktyvuokite paskyrą: <a href="' . e($link) . '">' . e($link) . '</a></p>',
        'Aktyvuokite paskyrą: ' . $link
    );

    audit_log($pdo, $userId, 'register', 'users', $userId);
    return [true, 'Registracija sėkminga. Patikrinkite el. paštą.'];
}

function activate_user_by_token(PDO $pdo, $token)
{
    $stmt = $pdo->prepare("SELECT id, is_active, activation_expires FROM users WHERE activation_token = :token LIMIT 1");
    $stmt->execute([':token' => $token]);
    $user = $stmt->fetch();

    if (!$user) {
        return [false, 'Neteisingas aktyvacijos kodas.'];
    }
    if ((int)$user['is_active'] === 1) {
        return [false, 'Paskyra jau aktyvuota.'];
    }
    if (strtotime($user['activation_expires']) < time()) {
        return [false, 'Aktyvacijos kodas nebegalioja.'];
    }

    $up = $pdo->prepare("
        UPDATE users
        SET is_active = 1, status = 'active', activation_token = NULL, activation_expires = NULL
        WHERE id = :id
    ");
    $up->execute([':id' => $user['id']]);
    audit_log($pdo, $user['id'], 'activate', 'users', $user['id']);
    return [true, 'Paskyra aktyvuota.'];
}

function create_password_reset(PDO $pdo, $email)
{
    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    if (!$user) {
        return [true, 'Jei toks el. paštas egzistuoja, išsiųsta atstatymo nuoroda.'];
    }

    $token = random_token(16);
    $expires = date('Y-m-d H:i:s', time() + 3600);
    $up = $pdo->prepare("UPDATE users SET reset_token = :token, reset_expires = :expires WHERE id = :id");
    $up->execute([':token' => $token, ':expires' => $expires, ':id' => $user['id']]);
    $link = SITE_URL . '/reset-password.php?token=' . urlencode($token);

    send_mail_message(
        $user['email'],
        $user['username'],
        'Slaptažodžio atstatymas',
        '<p>Atstatymo nuoroda: <a href="' . e($link) . '">' . e($link) . '</a></p>',
        'Atstatymo nuoroda: ' . $link
    );

    audit_log($pdo, $user['id'], 'password_reset_request', 'users', $user['id']);
    return [true, 'Jei toks el. paštas egzistuoja, išsiųsta atstatymo nuoroda.'];
}

function reset_password_by_token(PDO $pdo, $token, $password)
{
    $stmt = $pdo->prepare("SELECT id, reset_expires FROM users WHERE reset_token = :token LIMIT 1");
    $stmt->execute([':token' => $token]);
    $user = $stmt->fetch();

    if (!$user) {
        return [false, 'Neteisingas reset kodas.'];
    }
    if (strtotime($user['reset_expires']) < time()) {
        return [false, 'Reset kodas nebegalioja.'];
    }
    if (mb_strlen($password) < 8) {
        return [false, 'Slaptažodis per trumpas.'];
    }

    $up = $pdo->prepare("
        UPDATE users
        SET password = :password, reset_token = NULL, reset_expires = NULL
        WHERE id = :id
    ");
    $up->execute([
        ':password' => password_hash($password, PASSWORD_DEFAULT),
        ':id' => $user['id']
    ]);
    audit_log($pdo, $user['id'], 'password_reset', 'users', $user['id']);
    return [true, 'Slaptažodis pakeistas.'];
}

function change_password(PDO $pdo, $userId, $currentPassword, $newPassword)
{
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $userId]);
    $hash = $stmt->fetchColumn();

    if (!$hash || !password_verify($currentPassword, $hash)) {
        return [false, 'Dabartinis slaptažodis neteisingas.'];
    }
    if (mb_strlen($newPassword) < 8) {
        return [false, 'Naujas slaptažodis per trumpas.'];
    }

    $up = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
    $up->execute([
        ':password' => password_hash($newPassword, PASSWORD_DEFAULT),
        ':id' => $userId,
    ]);

    audit_log($pdo, $userId, 'password_change', 'users', $userId);
    return [true, 'Slaptažodis pakeistas.'];
}
