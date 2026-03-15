<?php
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_input()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf()
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Neteisingas CSRF token.');
    }
}

function clean_html($html)
{
    $allowed = '<b><strong><i><em><u><a><p><br><ul><ol><li><blockquote><code>';
    $html = strip_tags((string)$html, $allowed);
    $html = preg_replace('/on\w+\s*=\s*"[^"]*"/i', '', $html);
    $html = preg_replace("/on\w+\s*=\s*'[^']*'/i", '', $html);
    $html = preg_replace('/javascript\s*:/i', '', $html);
    return $html;
}

function upload_avatar(array $file)
{
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return [false, 'Nepavyko įkelti failo.'];
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        return [false, 'Avataras per didelis. Maks. 2MB.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    if (!isset($allowed[$mime])) {
        return [false, 'Neleistinas paveikslėlio tipas.'];
    }

    $filename = 'avatar_' . bin2hex(random_bytes(10)) . '.' . $allowed[$mime];
    $target = AVATAR_DIR . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return [false, 'Nepavyko išsaugoti avataro.'];
    }

    return [true, $filename];
}
