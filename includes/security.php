<?php
function csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_input()
{
    return csrf_field();
}

function verify_csrf()
{
    $sessionToken = (string)($_SESSION['csrf'] ?? '');
    $requestToken = (string)($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));

    if ($sessionToken === '' || $requestToken === '' || !hash_equals($sessionToken, $requestToken)) {
        if (function_exists('auth_security_log')) {
            $user = function_exists('current_user') ? current_user() : null;
            auth_security_log($user['id'] ?? null, 'csrf_invalid', 'csrf', null, [
                'subject_label' => 'csrf',
                'reason' => 'token_mismatch',
                'details' => [
                    'session_token_present' => $sessionToken !== '',
                    'request_token_present' => $requestToken !== '',
                ],
            ]);
        }
        abort_http(400, __('security.csrf'));
    }
}

function require_post_request()
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        abort_http(400, __('security.post_only'));
    }
}

function e($value)
{
    return escape_html($value);
}

function flash($key, $value = null)
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }

    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }

    $tmp = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $tmp;
}
