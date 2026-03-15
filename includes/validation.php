<?php
function normalize_email($email)
{
    return mb_strtolower(trim((string)$email));
}

function validate_username($username)
{
    $username = trim((string)$username);
    if ($username === '') return 'Vartotojo vardas privalomas.';
    if (mb_strlen($username) < 3 || mb_strlen($username) > 50) return 'Vartotojo vardas turi būti 3-50 simbolių.';
    if (!preg_match('/^[\p{L}\p{N}_\-. ]+$/u', $username)) return 'Vartotojo varde yra neleistinų simbolių.';
    return null;
}

function validate_email_address($email)
{
    $email = normalize_email($email);
    if ($email === '') return 'El. paštas privalomas.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return 'Neteisingas el. pašto formatas.';
    if (mb_strlen($email) > 190) return 'El. paštas per ilgas.';
    return null;
}

function validate_password_strength($password, $required = false)
{
    $password = (string)$password;
    if ($password === '' && !$required) return null;
    if (strlen($password) < 8) return 'Slaptažodis turi būti bent 8 simbolių.';
    if (!preg_match('/[A-Z]/', $password)) return 'Slaptažodyje turi būti bent viena didžioji raidė.';
    if (!preg_match('/[a-z]/', $password)) return 'Slaptažodyje turi būti bent viena mažoji raidė.';
    if (!preg_match('/\d/', $password)) return 'Slaptažodyje turi būti bent vienas skaičius.';
    return null;
}

function validate_role_payload($name, $slug, $level)
{
    $errors = [];
    $name = trim((string)$name);
    $slug = trim((string)$slug);
    $level = (int)$level;

    if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 100) $errors[] = 'Rolės pavadinimas turi būti 2-100 simbolių.';
    if ($slug === '' || !preg_match('/^[a-z0-9_\-]+$/', $slug)) $errors[] = 'Rolės slug gali turėti tik mažąsias raides, skaičius, _ ir -.';
    if ($level < 0 || $level > 1000) $errors[] = 'Rolės lygis turi būti tarp 0 ir 1000.';
    return $errors;
}

function user_email_exists($email, $excludeId = 0)
{
    $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
    $params = [':email' => normalize_email($email)];
    if ($excludeId > 0) {
        $sql .= " AND id != :id";
        $params[':id'] = (int)$excludeId;
    }
    $stmt = $GLOBALS['pdo']->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn() > 0;
}

function role_slug_exists($slug, $excludeId = 0)
{
    $sql = "SELECT COUNT(*) FROM roles WHERE slug = :slug";
    $params = [':slug' => trim((string)$slug)];
    if ($excludeId > 0) {
        $sql .= " AND id != :id";
        $params[':id'] = (int)$excludeId;
    }
    $stmt = $GLOBALS['pdo']->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn() > 0;
}

function validate_user_payload(array $data, $mode = 'create', $excludeId = 0)
{
    $errors = [];
    if ($msg = validate_username($data['username'] ?? '')) $errors[] = $msg;
    if ($msg = validate_email_address($data['email'] ?? '')) $errors[] = $msg;
    if (user_email_exists($data['email'] ?? '', $excludeId)) $errors[] = 'Toks el. paštas jau naudojamas.';
    if ($msg = validate_password_strength($data['password'] ?? '', $mode === 'create')) $errors[] = $msg;

    $status = $data['status'] ?? 'inactive';
    if (!in_array($status, ['active','inactive','blocked','deleted'], true)) $errors[] = 'Neteisinga vartotojo būsena.';

    $roleId = (int)($data['role_id'] ?? 0);
    if ($roleId < 1) $errors[] = 'Privaloma pasirinkti rolę.';
    else {
        $stmt = $GLOBALS['pdo']->prepare("SELECT COUNT(*) FROM roles WHERE id = :id");
        $stmt->execute([':id' => $roleId]);
        if ((int)$stmt->fetchColumn() === 0) $errors[] = 'Pasirinkta rolė neegzistuoja.';
    }

    return $errors;
}
