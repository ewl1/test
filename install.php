<?php
session_start();

$root = __DIR__;
$step = $_GET['step'] ?? 'welcome';
$error = '';
$success = '';

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function can_write($file)
{
    return file_exists($file) ? is_writable($file) : is_writable(dirname($file));
}

function install_csrf_token()
{
    if (empty($_SESSION['install_csrf'])) {
        $_SESSION['install_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['install_csrf'];
}

function install_csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . h(install_csrf_token()) . '">';
}

function verify_install_csrf()
{
    $sessionToken = (string)($_SESSION['install_csrf'] ?? '');
    $requestToken = (string)($_POST['csrf_token'] ?? '');
    if ($sessionToken === '' || $requestToken === '' || !hash_equals($sessionToken, $requestToken)) {
        throw new RuntimeException('Netinkamas formos saugos raktas.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_install_csrf();

        if (isset($_POST['save_config'])) {
            $cfg = [
                'app_name' => trim($_POST['app_name'] ?? 'Mini CMS Pro'),
                'site_url' => trim($_POST['site_url'] ?? 'http://localhost/mini-cms-pro'),
                'db_host' => trim($_POST['db_host'] ?? 'localhost'),
                'db_name' => trim($_POST['db_name'] ?? 'mini_cms'),
                'db_user' => trim($_POST['db_user'] ?? 'root'),
                'db_pass' => (string)($_POST['db_pass'] ?? ''),
                'mail_host' => trim($_POST['mail_host'] ?? 'smtp.example.com'),
                'mail_port' => (int)($_POST['mail_port'] ?? 587),
                'mail_user' => trim($_POST['mail_user'] ?? 'user@example.com'),
                'mail_pass' => (string)($_POST['mail_pass'] ?? ''),
                'mail_from' => trim($_POST['mail_from'] ?? 'noreply@example.com'),
                'mail_from_name' => trim($_POST['mail_from_name'] ?? 'Mini CMS Pro'),
                'current_theme' => trim($_POST['current_theme'] ?? 'default'),
                'admin_theme' => trim($_POST['admin_theme'] ?? 'default'),
                'timezone' => trim($_POST['timezone'] ?? 'Europe/Vilnius'),
            ];

            $content = "<?php\n"
                . "define('APP_NAME', " . var_export($cfg['app_name'], true) . ");\n"
                . "define('SITE_URL', " . var_export($cfg['site_url'], true) . ");\n"
                . "define('DB_HOST', " . var_export($cfg['db_host'], true) . ");\n"
                . "define('DB_NAME', " . var_export($cfg['db_name'], true) . ");\n"
                . "define('DB_USER', " . var_export($cfg['db_user'], true) . ");\n"
                . "define('DB_PASS', " . var_export($cfg['db_pass'], true) . ");\n"
                . "define('MAIL_HOST', " . var_export($cfg['mail_host'], true) . ");\n"
                . "define('MAIL_PORT', " . (int)$cfg['mail_port'] . ");\n"
                . "define('MAIL_USERNAME', " . var_export($cfg['mail_user'], true) . ");\n"
                . "define('MAIL_PASSWORD', " . var_export($cfg['mail_pass'], true) . ");\n"
                . "define('MAIL_FROM', " . var_export($cfg['mail_from'], true) . ");\n"
                . "define('MAIL_FROM_NAME', " . var_export($cfg['mail_from_name'], true) . ");\n"
                . "define('CURRENT_THEME', " . var_export($cfg['current_theme'], true) . ");\n"
                . "define('ADMIN_THEME', " . var_export($cfg['admin_theme'], true) . ");\n"
                . "define('TIMEZONE', " . var_export($cfg['timezone'], true) . ");\n"
                . "define('APP_VERSION', '1.0.0');\n"
                . "define('MAINTENANCE_MODE', false);\n";

            if (!can_write($root . '/config.php')) {
                $error = 'Nepavyko įrašyti config.php';
            } else {
                file_put_contents($root . '/config.php', $content);
                header('Location: install.php?step=db');
                exit;
            }
        }

        if (isset($_POST['run_sql'])) {
            require_once $root . '/maincore.php';
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $pdo->exec(file_get_contents($root . '/database.sql'));
            header('Location: install.php?step=admin');
            exit;
        }

        if (isset($_POST['create_admin'])) {
            require_once $root . '/maincore.php';
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, role_id, is_active, status, created_at)
                VALUES (:u, :e, :p, 1, 1, 'active', NOW())
            ");
            $stmt->execute([
                ':u' => trim((string)($_POST['username'] ?? '')),
                ':e' => trim((string)($_POST['email'] ?? '')),
                ':p' => password_hash((string)($_POST['password'] ?? ''), PASSWORD_DEFAULT),
            ]);
            $success = 'Admin sukurtas.';
            $step = 'done';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="lt">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Install</title>
<link rel="stylesheet" href="themes/default/bootstrap.min.css">
</head>
<body class="bg-body-tertiary">
<div class="container py-5">
<div class="mx-auto" style="max-width: 820px;">
<div class="card shadow-sm">
<div class="card-body p-4">
<h1 class="h3 mb-3">Mini CMS diegimas</h1>
<?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>

<?php if ($step === 'welcome'): ?>
<form method="post" class="row g-3">
<?= install_csrf_field() ?>
<div class="col-md-6"><label class="form-label">APP_NAME</label><input class="form-control" name="app_name" value="Mini CMS Pro"></div>
<div class="col-md-6"><label class="form-label">SITE_URL</label><input class="form-control" name="site_url" value="http://localhost/mini-cms-pro"></div>
<div class="col-md-6"><label class="form-label">DB_HOST</label><input class="form-control" name="db_host" value="localhost"></div>
<div class="col-md-6"><label class="form-label">DB_NAME</label><input class="form-control" name="db_name" value="mini_cms"></div>
<div class="col-md-6"><label class="form-label">DB_USER</label><input class="form-control" name="db_user" value="root"></div>
<div class="col-md-6"><label class="form-label">DB_PASS</label><input class="form-control" type="password" name="db_pass"></div>
<div class="col-md-6"><label class="form-label">MAIL_HOST</label><input class="form-control" name="mail_host" value="smtp.example.com"></div>
<div class="col-md-6"><label class="form-label">MAIL_PORT</label><input class="form-control" name="mail_port" value="587"></div>
<div class="col-md-6"><label class="form-label">Svetainės tema</label><input class="form-control" name="current_theme" value="default"></div>
<div class="col-md-6"><label class="form-label">Admin tema</label><input class="form-control" name="admin_theme" value="default"></div>
<div class="col-md-6"><label class="form-label">MAIL_USERNAME</label><input class="form-control" name="mail_user" value="user@example.com"></div>
<div class="col-md-6"><label class="form-label">MAIL_PASSWORD</label><input class="form-control" type="password" name="mail_pass"></div>
<div class="col-md-6"><label class="form-label">MAIL_FROM</label><input class="form-control" name="mail_from" value="noreply@example.com"></div>
<div class="col-md-6"><label class="form-label">MAIL_FROM_NAME</label><input class="form-control" name="mail_from_name" value="Mini CMS Pro"></div>
<div class="col-md-6"><label class="form-label">TIMEZONE</label><input class="form-control" name="timezone" value="Europe/Vilnius"></div>
<div class="col-12"><button class="btn btn-primary" name="save_config" value="1">Išsaugoti config</button></div>
</form>
<?php elseif ($step === 'db'): ?>
<form method="post">
<?= install_csrf_field() ?>
<button class="btn btn-primary" name="run_sql" value="1">Importuoti DB</button>
</form>
<?php elseif ($step === 'admin'): ?>
<form method="post" class="row g-3">
<?= install_csrf_field() ?>
<div class="col-md-6"><label class="form-label">Username</label><input class="form-control" name="username"></div>
<div class="col-md-6"><label class="form-label">Email</label><input class="form-control" name="email"></div>
<div class="col-md-6"><label class="form-label">Password</label><input class="form-control" type="password" name="password"></div>
<div class="col-12"><button class="btn btn-success" name="create_admin" value="1">Sukurti admin</button></div>
</form>
<?php else: ?>
<div class="alert alert-success">Baigta. Prisijunk per /administration/login.php</div>
<?php endif; ?>
</div>
</div>
</div>
</div>
</body>
</html>
