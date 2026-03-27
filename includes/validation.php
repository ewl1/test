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
        return 'Vartotojo vardas privalomas.';
    }
    if (mb_strlen($username) < 3 || mb_strlen($username) > 50) {
        return 'Vartotojo vardas turi būti 3-50 simbolių.';
    }
    if (!preg_match('/^[\p{L}\p{N}_\-. ]+$/u', $username)) {
        return 'Vartotojo varde yra neleistinų simbolių.';
    }
    return null;
}

function validate_email_address($email)
{
    $email = normalize_email($email);
    if ($email === '') {
        return 'El. paštas privalomas.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Neteisingas el. pašto formatas.';
    }
    if (mb_strlen($email) > 190) {
        return 'El. paštas per ilgas.';
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
        return 'Slaptažodis turi būti bent 8 simbolių.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return 'Slaptažodyje turi būti bent viena didžioji raidė.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        return 'Slaptažodyje turi būti bent viena mažoji raidė.';
    }
    if (!preg_match('/\d/', $password)) {
        return 'Slaptažodyje turi būti bent vienas skaičius.';
    }
    return null;
}

function validate_slug_value($slug, $field = 'Slug', $required = true, $min = 2, $max = 120)
{
    $slug = normalize_slug($slug);
    if ($slug === '') {
        return $required ? $field . ' privalomas.' : null;
    }

    if (strlen($slug) < $min || strlen($slug) > $max) {
        return $field . ' turi būti ' . $min . '-' . $max . ' simbolių.';
    }

    if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
        return $field . ' gali turėti tik mažąsias raides, skaičius ir -.';
    }

    return null;
}

function validate_url_value($url, $required = false, $field = 'URL', array $allowedSchemes = ['http', 'https', 'mailto', 'tel'], $allowRelative = true)
{
    $url = normalize_url_value($url);
    if ($url === '') {
        return $required ? $field . ' privalomas.' : null;
    }

    if (!is_safe_output_url($url, $allowedSchemes, $allowRelative)) {
        return $field . ' formatas neteisingas arba schema neleidžiama.';
    }

    if (mb_strlen($url) > 2048) {
        return $field . ' per ilgas.';
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
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Failas per didelis.',
        UPLOAD_ERR_PARTIAL => 'Failas įkeltas tik dalinai.',
        UPLOAD_ERR_NO_FILE => 'Failas nepasirinktas.',
        UPLOAD_ERR_NO_TMP_DIR => 'Serveryje nerastas laikinas aplankas.',
        UPLOAD_ERR_CANT_WRITE => 'Nepavyko įrašyti failo į diską.',
        UPLOAD_ERR_EXTENSION => 'Failo įkėlimą sustabdė PHP plėtinys.',
        default => 'Nepavyko įkelti failo.',
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
        return [false, 'Netinkamas failo įkėlimo užklausos formatas.'];
    }

    if ((int)$file['error'] === UPLOAD_ERR_NO_FILE) {
        return $options['required'] ? [false, upload_error_message(UPLOAD_ERR_NO_FILE)] : [true, null];
    }

    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        return [false, upload_error_message((int)$file['error'])];
    }

    if (!is_uploaded_file((string)$file['tmp_name'])) {
        return [false, 'Failas negautas saugiu būdu.'];
    }

    $size = (int)($file['size'] ?? 0);
    if ($size < 1) {
        return [false, 'Failas tuščias.'];
    }
    if ($size > (int)$options['max_size']) {
        return [false, 'Failas viršija leidžiamą dydį.'];
    }

    $originalName = trim((string)($file['name'] ?? 'file'));
    if ($originalName === '' || preg_match('/[\x00-\x1F\x7F]/', $originalName)) {
        return [false, 'Netinkamas failo pavadinimas.'];
    }

    $extension = mb_strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($extension === '') {
        return [false, 'Failas turi turėti plėtinį.'];
    }
    if (!empty($options['allowed_extensions']) && !in_array($extension, $options['allowed_extensions'], true)) {
        return [false, 'Neleidžiamas failo plėtinys.'];
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
        return [false, 'Neleidžiamas failo tipas.'];
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
        return [false, 'Failo plėtinys neatitinka realaus MIME tipo.'];
    }

    if (!empty($options['verify_image'])) {
        $imageInfo = @getimagesize((string)$file['tmp_name']);
        if ($imageInfo === false) {
            return [false, 'Pateiktas failas nėra galiojantis paveikslėlis.'];
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
        return [false, 'Nepavyko sukurti avatarų aplanko.'];
    }

    $filename = $validated['safe_name'] . '-' . bin2hex(random_bytes(8)) . '.' . $validated['extension'];
    $target = $avatarDir . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($validated['tmp_name'], $target)) {
        return [false, 'Nepavyko išsaugoti avataro.'];
    }

    return [true, $filename];
}

function validate_role_payload($name, $slug, $level)
{
    $errors = [];
    $name = trim((string)$name);
    $level = (int)$level;

    if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors[] = 'Rolės pavadinimas turi būti 2-100 simbolių.';
    }
    if ($msg = validate_slug_value($slug, 'Rolės slug', true, 2, 100)) {
        $errors[] = $msg;
    }
    if ($level < 0 || $level > 1000) {
        $errors[] = 'Rolės lygis turi būti tarp 0 ir 1000.';
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
        $errors[] = 'Toks el. paštas jau naudojamas.';
    }
    if ($msg = validate_password_strength($data['password'] ?? '', $mode === 'create')) {
        $errors[] = $msg;
    }

    $status = $data['status'] ?? 'inactive';
    if (!in_array($status, ['active', 'inactive', 'blocked', 'deleted'], true)) {
        $errors[] = 'Neteisinga vartotojo būsena.';
    }

    $roleId = (int)($data['role_id'] ?? 0);
    if ($roleId < 1) {
        $errors[] = 'Privaloma pasirinkti rolę.';
    } else {
        $stmt = $GLOBALS['pdo']->prepare("SELECT COUNT(*) FROM roles WHERE id = :id");
        $stmt->execute([':id' => $roleId]);
        if ((int)$stmt->fetchColumn() === 0) {
            $errors[] = 'Pasirinkta rolė neegzistuoja.';
        }
    }

    return $errors;
}
