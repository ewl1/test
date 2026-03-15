<?php
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

function flash($key, $message = null)
{
    if ($message === null) {
        if (!isset($_SESSION['_flash'][$key])) {
            return null;
        }
        $msg = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);
        return $msg;
    }
    $_SESSION['_flash'][$key] = $message;
}

function old($key, $default = '')
{
    return $_POST[$key] ?? $default;
}

function now()
{
    return date('Y-m-d H:i:s');
}

function client_ip()
{
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

function format_dt($value)
{
    if (!$value) {
        return '';
    }
    return date('Y-m-d H:i', strtotime($value));
}

function user_avatar_url($user)
{
    if (!empty($user['avatar'])) {
        return 'uploads/avatars/' . rawurlencode($user['avatar']);
    }
    return 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user['email'] ?? ''))) . '?d=mp&s=80';
}

function random_token($bytes = 32)
{
    return bin2hex(random_bytes($bytes));
}

function post_limit_setting(PDO $pdo)
{
    $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='posts_per_page' LIMIT 1");
    $value = $stmt->fetchColumn();
    return max(1, (int)($value ?: 10));
}
