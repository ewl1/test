<?php
session_start();

$root = __DIR__;
$step = $_GET['step'] ?? 'welcome';
$error = '';
$success = '';

install_require_classes($root);

function install_require_classes($root)
{
    $files = [
        '/includes/classes/MiniCMS/Installer/ConfigWriter.php',
        '/includes/classes/MiniCMS/Installer/DatabaseSchema.php',
        '/includes/classes/MiniCMS/Installer/DatabaseInstaller.php',
        '/includes/classes/MiniCMS/Installer/AdminAccountInstaller.php',
    ];

    foreach ($files as $file) {
        require_once $root . $file;
    }
}

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function can_write($file)
{
    return file_exists($file) ? is_writable($file) : is_writable(dirname($file));
}

function install_detect_site_url()
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/install.php'));
    $scriptDir = $scriptDir === '/' ? '' : rtrim($scriptDir, '/');

    return $scheme . '://' . $host . $scriptDir;
}

function install_defaults($root)
{
    $configPath = $root . '/config.php';
    if (is_file($configPath)) {
        require_once $configPath;
    }

    return [
        'app_name' => defined('APP_NAME') ? APP_NAME : 'Mini CMS Pro',
        'site_url' => defined('SITE_URL') ? SITE_URL : install_detect_site_url(),
        'db_host' => defined('DB_HOST') ? DB_HOST : 'localhost',
        'db_name' => defined('DB_NAME') ? DB_NAME : 'minicms',
        'db_user' => defined('DB_USER') ? DB_USER : 'root',
        'db_pass' => defined('DB_PASS') ? DB_PASS : '',
        'mail_host' => defined('MAIL_HOST') ? MAIL_HOST : 'smtp.example.com',
        'mail_port' => defined('MAIL_PORT') ? (string)MAIL_PORT : '587',
        'mail_user' => defined('MAIL_USERNAME') ? MAIL_USERNAME : 'user@example.com',
        'mail_pass' => defined('MAIL_PASSWORD') ? MAIL_PASSWORD : '',
        'mail_from' => defined('MAIL_FROM') ? MAIL_FROM : 'noreply@example.com',
        'mail_from_name' => defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Mini CMS Pro',
        'current_theme' => defined('CURRENT_THEME') ? CURRENT_THEME : 'default',
        'admin_theme' => defined('ADMIN_THEME') ? ADMIN_THEME : 'default',
        'timezone' => defined('TIMEZONE') ? TIMEZONE : 'Europe/Vilnius',
    ];
}

function install_request_config()
{
    return [
        'app_name' => trim((string)($_POST['app_name'] ?? 'Mini CMS Pro')),
        'site_url' => trim((string)($_POST['site_url'] ?? install_detect_site_url())),
        'db_host' => trim((string)($_POST['db_host'] ?? 'localhost')),
        'db_name' => trim((string)($_POST['db_name'] ?? 'minicms')),
        'db_user' => trim((string)($_POST['db_user'] ?? 'root')),
        'db_pass' => (string)($_POST['db_pass'] ?? ''),
        'mail_host' => trim((string)($_POST['mail_host'] ?? 'smtp.example.com')),
        'mail_port' => (string)($_POST['mail_port'] ?? '587'),
        'mail_user' => trim((string)($_POST['mail_user'] ?? 'user@example.com')),
        'mail_pass' => (string)($_POST['mail_pass'] ?? ''),
        'mail_from' => trim((string)($_POST['mail_from'] ?? 'noreply@example.com')),
        'mail_from_name' => trim((string)($_POST['mail_from_name'] ?? 'Mini CMS Pro')),
        'current_theme' => trim((string)($_POST['current_theme'] ?? 'default')),
        'admin_theme' => trim((string)($_POST['admin_theme'] ?? 'default')),
        'timezone' => trim((string)($_POST['timezone'] ?? 'Europe/Vilnius')),
    ];
}

function install_runtime_config($root)
{
    $configPath = $root . '/config.php';
    if (!is_file($configPath)) {
        throw new RuntimeException('Pirmiausia issaugokite config.php.');
    }

    require_once $configPath;

    return [
        'db_host' => defined('DB_HOST') ? DB_HOST : 'localhost',
        'db_name' => defined('DB_NAME') ? DB_NAME : 'minicms',
        'db_user' => defined('DB_USER') ? DB_USER : 'root',
        'db_pass' => defined('DB_PASS') ? DB_PASS : '',
    ];
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
            $config = install_request_config();
            $configWriter = new \App\MiniCMS\Installer\ConfigWriter();
            $configPath = $root . '/config.php';

            if (!can_write($configPath)) {
                throw new RuntimeException('Nepavyko irasyti config.php');
            }

            $configWriter->write($configPath, $config);
            header('Location: install.php?step=db');
            exit;
        }

        if (isset($_POST['run_sql'])) {
            $config = install_runtime_config($root);
            $databaseInstaller = new \App\MiniCMS\Installer\DatabaseInstaller(
                $config['db_host'],
                $config['db_name'],
                $config['db_user'],
                $config['db_pass'],
                new \App\MiniCMS\Installer\DatabaseSchema()
            );
            $databaseInstaller->install();
            header('Location: install.php?step=admin');
            exit;
        }

        if (isset($_POST['create_admin'])) {
            $config = install_runtime_config($root);
            $databaseInstaller = new \App\MiniCMS\Installer\DatabaseInstaller(
                $config['db_host'],
                $config['db_name'],
                $config['db_user'],
                $config['db_pass'],
                new \App\MiniCMS\Installer\DatabaseSchema()
            );
            $adminInstaller = new \App\MiniCMS\Installer\AdminAccountInstaller($databaseInstaller->connect());
            $adminInstaller->create(
                (string)($_POST['username'] ?? ''),
                (string)($_POST['email'] ?? ''),
                (string)($_POST['password'] ?? '')
            );

            $success = 'Administratoriaus paskyra sukurta.';
            $step = 'done';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$defaults = install_defaults($root);
?>
<!doctype html>
<html lang="lt">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mini CMS diegimas</title>
<link rel="stylesheet" href="themes/default/bootstrap.min.css">
</head>
<body class="bg-body-tertiary">
<div class="container py-5">
  <div class="mx-auto" style="max-width: 860px;">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <h1 class="h3 mb-3">Mini CMS diegimas</h1>
        <p class="text-secondary">Diegimas dabar naudoja Installer klases is <code>includes/classes/MiniCMS/Installer/</code>.</p>

        <?php if ($error): ?>
          <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
          <div class="alert alert-success"><?= h($success) ?></div>
        <?php endif; ?>

        <?php if ($step === 'welcome'): ?>
          <form method="post" class="row g-3">
            <?= install_csrf_field() ?>
            <div class="col-md-6"><label class="form-label">APP_NAME</label><input class="form-control" name="app_name" value="<?= h($defaults['app_name']) ?>"></div>
            <div class="col-md-6"><label class="form-label">SITE_URL</label><input class="form-control" name="site_url" value="<?= h($defaults['site_url']) ?>"></div>
            <div class="col-md-6"><label class="form-label">DB_HOST</label><input class="form-control" name="db_host" value="<?= h($defaults['db_host']) ?>"></div>
            <div class="col-md-6"><label class="form-label">DB_NAME</label><input class="form-control" name="db_name" value="<?= h($defaults['db_name']) ?>"></div>
            <div class="col-md-6"><label class="form-label">DB_USER</label><input class="form-control" name="db_user" value="<?= h($defaults['db_user']) ?>"></div>
            <div class="col-md-6"><label class="form-label">DB_PASS</label><input class="form-control" type="password" name="db_pass" value="<?= h($defaults['db_pass']) ?>"></div>
            <div class="col-md-6"><label class="form-label">MAIL_HOST</label><input class="form-control" name="mail_host" value="<?= h($defaults['mail_host']) ?>"></div>
            <div class="col-md-6"><label class="form-label">MAIL_PORT</label><input class="form-control" name="mail_port" value="<?= h($defaults['mail_port']) ?>"></div>
            <div class="col-md-6"><label class="form-label">Svetaines tema</label><input class="form-control" name="current_theme" value="<?= h($defaults['current_theme']) ?>"></div>
            <div class="col-md-6"><label class="form-label">Admin tema</label><input class="form-control" name="admin_theme" value="<?= h($defaults['admin_theme']) ?>"></div>
            <div class="col-md-6"><label class="form-label">MAIL_USERNAME</label><input class="form-control" name="mail_user" value="<?= h($defaults['mail_user']) ?>"></div>
            <div class="col-md-6"><label class="form-label">MAIL_PASSWORD</label><input class="form-control" type="password" name="mail_pass" value="<?= h($defaults['mail_pass']) ?>"></div>
            <div class="col-md-6"><label class="form-label">MAIL_FROM</label><input class="form-control" name="mail_from" value="<?= h($defaults['mail_from']) ?>"></div>
            <div class="col-md-6"><label class="form-label">MAIL_FROM_NAME</label><input class="form-control" name="mail_from_name" value="<?= h($defaults['mail_from_name']) ?>"></div>
            <div class="col-md-6"><label class="form-label">TIMEZONE</label><input class="form-control" name="timezone" value="<?= h($defaults['timezone']) ?>"></div>
            <div class="col-12"><button class="btn btn-primary" name="save_config" value="1">Issaugoti config</button></div>
          </form>
        <?php elseif ($step === 'db'): ?>
          <div class="alert alert-info">Bus sukurta duombaze, pagrindines lenteles ir numatytieji moduliai.</div>
          <form method="post">
            <?= install_csrf_field() ?>
            <button class="btn btn-primary" name="run_sql" value="1">Sukurti ir importuoti DB</button>
          </form>
        <?php elseif ($step === 'admin'): ?>
          <form method="post" class="row g-3">
            <?= install_csrf_field() ?>
            <div class="col-md-6"><label class="form-label">Naudotojo vardas</label><input class="form-control" name="username" value="admin"></div>
            <div class="col-md-6"><label class="form-label">El. pastas</label><input class="form-control" name="email" value="admin@example.com"></div>
            <div class="col-md-6"><label class="form-label">Slaptazodis</label><input class="form-control" type="password" name="password"></div>
            <div class="col-12"><button class="btn btn-success" name="create_admin" value="1">Sukurti administratoriu</button></div>
          </form>
        <?php else: ?>
          <div class="alert alert-success">Diegimas baigtas. Prisijunkite per <code>/administration/login.php</code>.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</body>
</html>
