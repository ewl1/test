<?php
function normalize_email($email)
{
    return mb_strtolower(trim((string)$email));
}

function normalize_slug($value, $separator = '-')
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    $map = [
        'ą' => 'a', 'č' => 'c', 'ę' => 'e', 'ė' => 'e', 'į' => 'i', 'š' => 's', 'ų' => 'u', 'ū' => 'u', 'ž' => 'z',
        'Ą' => 'a', 'Č' => 'c', 'Ę' => 'e', 'Ė' => 'e', 'Į' => 'i', 'Š' => 's', 'Ų' => 'u', 'Ū' => 'u', 'Ž' => 'z',
    ];
    $value = strtr($value, $map);

    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($converted !== false) {
            $value = $converted;
        }
    }

    $value = mb_strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', $separator, $value);
    $value = preg_replace('/' . preg_quote($separator, '/') . '+/', $separator, (string)$value);
    return trim((string)$value, $separator);
}

function normalize_url_value($url)
{
    return trim((string)$url);
}

function validate_username($username)
{
    $username = trim((string)$username);
    if ($username === '') {
        return __('validation.username.required');
    }
    if (mb_strlen($username) < 3 || mb_strlen($username) > 50) {
        return __('validation.username.length');
    }
    if (!preg_match('/^[\p{L}\p{N}_\-. ]+$/u', $username)) {
        return __('validation.username.characters');
    }
    return null;
}

function validate_email_address($email)
{
    $email = normalize_email($email);
    if ($email === '') {
        return __('validation.email.required');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return __('validation.email.invalid');
    }
    if (mb_strlen($email) > 190) {
        return __('validation.email.too_long');
    }
    return null;
}

function validate_password_strength($password, $required = false)
{
    $password = (string)$password;
    if ($password === '' && !$required) {
        return null;
    }
    if (strlen($password) < 8) {
        return __('validation.password.min');
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return __('validation.password.uppercase');
    }
    if (!preg_match('/[a-z]/', $password)) {
        return __('validation.password.lowercase');
    }
    if (!preg_match('/\d/', $password)) {
        return __('validation.password.number');
    }
    return null;
}

function validate_slug_value($slug, $field = 'Slug', $required = true, $min = 2, $max = 120)
{
    $slug = normalize_slug($slug);
    if ($slug === '') {
        return $required ? __('validation.slug.required', ['field' => $field]) : null;
    }

    if (strlen($slug) < $min || strlen($slug) > $max) {
        return __('validation.slug.length', ['field' => $field, 'min' => $min, 'max' => $max]);
    }

    if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
        return __('validation.slug.characters', ['field' => $field]);
    }

    return null;
}

function validate_url_value($url, $required = false, $field = 'URL', array $allowedSchemes = ['http', 'https', 'mailto', 'tel'], $allowRelative = true)
{
    $url = normalize_url_value($url);
    if ($url === '') {
        return $required ? __('validation.url.required', ['field' => $field]) : null;
    }

    if (!is_safe_output_url($url, $allowedSchemes, $allowRelative)) {
        return __('validation.url.invalid', ['field' => $field]);
    }

    if (mb_strlen($url) > 2048) {
        return __('validation.url.too_long', ['field' => $field]);
    }

    return null;
}

function sanitize_bbcode_input($text, array $allowedTags = ['b', 'i', 'u', 'quote', 'code', 'url'], $maxLength = 5000)
{
    $text = trim((string)$text);
    if ($text === '') {
        return '';
    }

    $text = preg_replace("/\r\n?/", "\n", $text);
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

    $allowedMap = array_fill_keys(array_map('mb_strtolower', $allowedTags), true);
    $text = preg_replace_callback('/\[(\/?)([a-z]+)(?:=([^\]]*))?\]/i', function ($matches) use ($allowedMap) {
        $closing = $matches[1] === '/';
        $tag = mb_strtolower((string)$matches[2]);
        $argument = isset($matches[3]) ? trim((string)$matches[3]) : null;

        if (!isset($allowedMap[$tag])) {
            return '';
        }

        if ($closing) {
            return '[/' . $tag . ']';
        }

        if ($tag === 'url') {
            if ($argument === null || $argument === '') {
                return '[url]';
            }

            if (validate_url_value($argument, true, 'Nuoroda', ['http', 'https'], false) !== null) {
                return '';
            }

            return '[url=' . $argument . ']';
        }

        if (in_array($tag, ['img', 'youtube'], true)) {
            return '[' . $tag . ']';
        }

        return '[' . $tag . ']';
    }, $text);

    if (mb_strlen($text) > $maxLength) {
        $text = mb_substr($text, 0, $maxLength);
    }

    return trim($text);
}

function upload_validation_profiles()
{
    return [
        'image' => [
            'max_size' => 2 * 1024 * 1024,
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            'verify_image' => true,
        ],
        'document' => [
            'max_size' => 5 * 1024 * 1024,
            'allowed_extensions' => ['pdf', 'txt'],
            'allowed_mime_types' => ['application/pdf', 'text/plain'],
            'verify_image' => false,
        ],
    ];
}

function upload_profile_options($profile)
{
    $profile = trim((string)$profile);
    $profiles = upload_validation_profiles();
    return $profiles[$profile] ?? [];
}

function upload_error_message($errorCode)
{
    return match ((int)$errorCode) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => __('validation.upload.too_large'),
        UPLOAD_ERR_PARTIAL => __('validation.upload.partial'),
        UPLOAD_ERR_NO_FILE => __('validation.upload.no_file'),
        UPLOAD_ERR_NO_TMP_DIR => __('validation.upload.no_tmp_dir'),
        UPLOAD_ERR_CANT_WRITE => __('validation.upload.cant_write'),
        UPLOAD_ERR_EXTENSION => __('validation.upload.extension'),
        default => __('validation.upload.failed'),
    };
}

function validate_upload_file(array $file, array $options = [])
{
    $options = array_merge([
        'required' => true,
        'max_size' => 2 * 1024 * 1024,
        'profile' => null,
        'allowed_extensions' => [],
        'allowed_mime_types' => [],
        'verify_image' => false,
    ], $options);

    if (!empty($options['profile'])) {
        $options = array_merge($options, upload_profile_options($options['profile']));
    }

    if (!isset($file['error']) || !array_key_exists('tmp_name', $file)) {
        return [false, __('validation.upload.invalid_request')];
    }

    if ((int)$file['error'] === UPLOAD_ERR_NO_FILE) {
        return $options['required'] ? [false, upload_error_message(UPLOAD_ERR_NO_FILE)] : [true, null];
    }

    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        return [false, upload_error_message((int)$file['error'])];
    }

    if (!is_uploaded_file((string)$file['tmp_name'])) {
        return [false, __('validation.upload.unsafe')];
    }

    $size = (int)($file['size'] ?? 0);
    if ($size < 1) {
        return [false, __('validation.upload.empty')];
    }
    if ($size > (int)$options['max_size']) {
        return [false, __('validation.upload.limit')];
    }

    $originalName = trim((string)($file['name'] ?? 'file'));
    if ($originalName === '' || preg_match('/[\x00-\x1F\x7F]/', $originalName)) {
        return [false, __('validation.upload.invalid_name')];
    }

    $extension = mb_strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($extension === '') {
        return [false, __('validation.upload.extension_required')];
    }
    if (!empty($options['allowed_extensions']) && !in_array($extension, $options['allowed_extensions'], true)) {
        return [false, __('validation.upload.extension_not_allowed')];
    }

    $mimeType = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mimeType = (string)finfo_file($finfo, (string)$file['tmp_name']);
            finfo_close($finfo);
        }
    }

    if (!empty($options['allowed_mime_types']) && !in_array($mimeType, $options['allowed_mime_types'], true)) {
        return [false, __('validation.upload.mime_not_allowed')];
    }

    $extensionMimeMap = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'pdf' => ['application/pdf'],
        'txt' => ['text/plain'],
    ];
    if (isset($extensionMimeMap[$extension]) && $mimeType !== '' && !in_array($mimeType, $extensionMimeMap[$extension], true)) {
        return [false, __('validation.upload.extension_mismatch')];
    }

    if (!empty($options['verify_image'])) {
        $imageInfo = @getimagesize((string)$file['tmp_name']);
        if ($imageInfo === false) {
            return [false, __('validation.upload.not_image')];
        }
    }

    $baseName = normalize_slug(pathinfo($originalName, PATHINFO_FILENAME));
    if ($baseName === '') {
        $baseName = 'file';
    }

    return [true, [
        'tmp_name' => (string)$file['tmp_name'],
        'original_name' => $originalName,
        'safe_name' => $baseName,
        'extension' => $extension,
        'mime_type' => $mimeType,
        'size' => $size,
    ]];
}

function upload_avatar(array $file)
{
    [$ok, $validated] = validate_upload_file($file, [
        'required' => true,
        'profile' => 'image',
    ]);

    if (!$ok) {
        return [false, $validated];
    }

    $avatarDir = BASEDIR . 'uploads/avatars';
    if (!is_dir($avatarDir) && !@mkdir($avatarDir, 0755, true) && !is_dir($avatarDir)) {
        return [false, __('validation.upload.avatar_dir_failed')];
    }

    $filename = $validated['safe_name'] . '-' . bin2hex(random_bytes(8)) . '.' . $validated['extension'];
    $target = $avatarDir . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($validated['tmp_name'], $target)) {
        return [false, __('validation.upload.avatar_save_failed')];
    }

    return [true, $filename];
}

function validate_role_payload($name, $slug, $level)
{
    $errors = [];
    $name = trim((string)$name);
    $level = (int)$level;

    if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors[] = __('validation.role.name_length');
    }
    if ($msg = validate_slug_value($slug, __('validation.role.slug_label'), true, 2, 100)) {
        $errors[] = $msg;
    }
    if ($level < 0 || $level > 1000) {
        $errors[] = __('validation.role.level');
    }
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
    $params = [':slug' => normalize_slug($slug)];
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
    if ($msg = validate_username($data['username'] ?? '')) {
        $errors[] = $msg;
    }
    if ($msg = validate_email_address($data['email'] ?? '')) {
        $errors[] = $msg;
    }
    if (user_email_exists($data['email'] ?? '', $excludeId)) {
        $errors[] = __('validation.email.taken');
    }
    if ($msg = validate_password_strength($data['password'] ?? '', $mode === 'create')) {
        $errors[] = $msg;
    }

    $status = $data['status'] ?? 'inactive';
    if (!in_array($status, ['active', 'inactive', 'blocked', 'deleted'], true)) {
        $errors[] = __('validation.user.status_invalid');
    }

    $roleId = (int)($data['role_id'] ?? 0);
    if ($roleId < 1) {
        $errors[] = __('validation.user.role_required');
    } else {
        $stmt = $GLOBALS['pdo']->prepare("SELECT COUNT(*) FROM roles WHERE id = :id");
        $stmt->execute([':id' => $roleId]);
        if ((int)$stmt->fetchColumn() === 0) {
            $errors[] = __('validation.user.role_missing');
        }
    }

    return $errors;
}
