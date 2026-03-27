<?php
function get_all_users(PDO $pdo)
{
    ensure_user_profile_schema();

    $stmt = $pdo->query("
        SELECT u.*, r.name AS role_name, r.slug AS role_slug
        FROM users u
        LEFT JOIN roles r ON r.id = u.role_id
        ORDER BY u.id DESC
    ");
    return $stmt->fetchAll();
}

function get_user(PDO $pdo, $id)
{
    ensure_user_profile_schema();

    $stmt = $pdo->prepare("
        SELECT u.*, r.name AS role_name, r.slug AS role_slug
        FROM users u
        LEFT JOIN roles r ON r.id = u.role_id
        WHERE u.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => (int)$id]);
    return $stmt->fetch() ?: null;
}

function update_user_profile(PDO $pdo, $id, array $data)
{
    ensure_user_profile_schema();

    $id = (int)$id;
    $username = trim((string)($data['username'] ?? ''));
    $email = normalize_email($data['email'] ?? '');
    $signature = clean_user_signature($data['signature'] ?? '');

    if ($message = validate_username($username)) {
        return [false, $message];
    }
    if ($message = validate_email_address($email)) {
        return [false, $message];
    }
    if (user_email_exists($email, $id)) {
        return [false, 'Toks el. paštas jau naudojamas.'];
    }

    $stmt = $pdo->prepare('UPDATE users SET username = :username, email = :email, signature = :signature WHERE id = :id');
    $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':signature' => $signature !== '' ? $signature : null,
        ':id' => $id,
    ]);

    audit_log(current_user()['id'] ?? $id, 'user_edit', 'users', $id);
    return [true, 'Profilis atnaujintas.'];
}

function update_user_avatar(PDO $pdo, $id, array $file)
{
    ensure_user_profile_schema();

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || empty($file['name'])) {
        return [true, null];
    }

    [$ok, $uploaded] = upload_avatar($file);
    if (!$ok) {
        return [false, $uploaded];
    }

    $current = get_user($pdo, $id);
    $stmt = $pdo->prepare('UPDATE users SET avatar = :avatar WHERE id = :id');
    $stmt->execute([
        ':avatar' => $uploaded,
        ':id' => (int)$id,
    ]);

    if (!empty($current['avatar'])) {
        $oldPath = BASEDIR . 'uploads/avatars/' . $current['avatar'];
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    audit_log(current_user()['id'] ?? $id, 'user_avatar_update', 'users', (int)$id);
    return [true, 'Avataras atnaujintas.'];
}

function change_password(PDO $pdo, $id, $currentPassword, $newPassword)
{
    $user = get_user($pdo, $id);
    if (!$user) {
        return [false, 'Vartotojas nerastas.'];
    }

    if (!password_verify((string)$currentPassword, (string)$user['password'])) {
        return [false, 'Neteisingas dabartinis slaptažodis.'];
    }

    if ($message = validate_password_strength($newPassword, true)) {
        return [false, $message];
    }

    $stmt = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
    $stmt->execute([
        ':password' => password_hash((string)$newPassword, PASSWORD_DEFAULT),
        ':id' => (int)$id,
    ]);

    audit_log(current_user()['id'] ?? $id, 'user_password_change', 'users', (int)$id);
    return [true, 'Slaptažodis pakeistas.'];
}

function update_admin_password(PDO $pdo, $id, $currentPassword, $newPassword, $confirmPassword)
{
    ensure_user_profile_schema();

    $user = get_user($pdo, $id);
    if (!$user) {
        return [false, 'Vartotojas nerastas.'];
    }

    if (!has_permission($GLOBALS['pdo'], (int)$id, 'admin.access')) {
        return [false, 'Ši paskyra neturi administratoriaus teisių.'];
    }

    if (!password_verify((string)$currentPassword, (string)$user['password'])) {
        return [false, 'Neteisingas paskyros slaptažodis.'];
    }

    if ((string)$newPassword !== (string)$confirmPassword) {
        return [false, 'Admin slaptažodžiai nesutampa.'];
    }

    if ($message = validate_password_strength($newPassword, true)) {
        return [false, $message];
    }

    $stmt = $pdo->prepare('UPDATE users SET admin_password = :password WHERE id = :id');
    $stmt->execute([
        ':password' => password_hash((string)$newPassword, PASSWORD_DEFAULT),
        ':id' => (int)$id,
    ]);

    audit_log(current_user()['id'] ?? $id, 'admin_password_update', 'users', (int)$id);
    return [true, 'Admin slaptažodis atnaujintas.'];
}

function change_user_status(PDO $pdo, $id, $status)
{
    $allowed = ['active', 'inactive', 'blocked', 'deleted'];
    if (!in_array($status, $allowed, true)) {
        return;
    }

    $isActive = $status === 'active' ? 1 : 0;
    $stmt = $pdo->prepare('UPDATE users SET status = :status, is_active = :is_active WHERE id = :id');
    $stmt->execute([
        ':status' => $status,
        ':is_active' => $isActive,
        ':id' => (int)$id,
    ]);
    audit_log(current_user()['id'] ?? null, 'user_status_change', 'users', (int)$id, ['status' => $status]);
}

function delete_user(PDO $pdo, $id)
{
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
    $stmt->execute([':id' => (int)$id]);
    audit_log(current_user()['id'] ?? null, 'user_delete', 'users', (int)$id);
}
