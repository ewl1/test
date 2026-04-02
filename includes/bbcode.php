<?php
function site_smiley_table_name()
{
    return 'site_smileys';
}

function site_smiley_defaults()
{
    return [
        ['code' => ':)', 'title' => 'Šypsena', 'type' => 'emoji', 'value' => html_entity_decode('&#128578;', ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'sort_order' => 10],
        ['code' => ';)', 'title' => 'Mirktelėjimas', 'type' => 'emoji', 'value' => html_entity_decode('&#128521;', ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'sort_order' => 20],
        ['code' => ':D', 'title' => 'Juokas', 'type' => 'emoji', 'value' => html_entity_decode('&#128516;', ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'sort_order' => 30],
        ['code' => ':(', 'title' => 'Liūdna', 'type' => 'emoji', 'value' => html_entity_decode('&#128577;', ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'sort_order' => 40],
        ['code' => ':P', 'title' => 'Liežuvis', 'type' => 'emoji', 'value' => html_entity_decode('&#128539;', ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'sort_order' => 50],
        ['code' => '<3', 'title' => 'Širdis', 'type' => 'emoji', 'value' => html_entity_decode('&#10084;&#65039;', ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'sort_order' => 60],
    ];
}

function site_smiley_default_value_for_code($code)
{
    $code = site_smiley_normalize_code($code);
    if ($code === '') {
        return '';
    }

    $builtin = [
        ':)' => html_entity_decode('&#128578;', ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ';)' => html_entity_decode('&#128521;', ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ':D' => html_entity_decode('&#128516;', ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ':(' => html_entity_decode('&#128577;', ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ':|' => html_entity_decode('&#128528;', ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ':P' => html_entity_decode('&#128539;', ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        '<3' => html_entity_decode('&#10084;&#65039;', ENT_QUOTES | ENT_HTML5, 'UTF-8'),
    ];

    if (isset($builtin[$code])) {
        return $builtin[$code];
    }

    foreach (site_smiley_defaults() as $smiley) {
        if (($smiley['type'] ?? 'emoji') !== 'emoji') {
            continue;
        }

        if (site_smiley_normalize_code($smiley['code'] ?? '') === $code) {
            return (string)($smiley['value'] ?? '');
        }
    }

    return '';
}

function ensure_site_smiley_schema()
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $ensured = true;

    $GLOBALS['pdo']->exec("
        CREATE TABLE IF NOT EXISTS " . site_smiley_table_name() . " (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(32) NOT NULL,
            title VARCHAR(120) NOT NULL,
            type ENUM('emoji','image') NOT NULL DEFAULT 'emoji',
            value VARCHAR(255) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_site_smiley_code (code),
            KEY idx_site_smiley_active_sort (is_active, sort_order, id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $count = (int)$GLOBALS['pdo']->query('SELECT COUNT(*) FROM ' . site_smiley_table_name())->fetchColumn();
    if ($count > 0) {
        return;
    }

    $stmt = $GLOBALS['pdo']->prepare('
        INSERT INTO ' . site_smiley_table_name() . ' (code, title, type, value, sort_order, is_active, created_at, updated_at)
        VALUES (:code, :title, :type, :value, :sort_order, 1, NOW(), NOW())
    ');

    foreach (site_smiley_defaults() as $smiley) {
        $stmt->execute([
            ':code' => $smiley['code'],
            ':title' => $smiley['title'],
            ':type' => $smiley['type'],
            ':value' => $smiley['value'],
            ':sort_order' => (int)$smiley['sort_order'],
        ]);
    }
}

function site_smiley_reset_cache()
{
    unset($GLOBALS['_site_smiley_cache']);
}

function site_smiley_cache_key($activeOnly)
{
    return $activeOnly ? 'active' : 'all';
}

function site_smiley_normalize_code($code)
{
    $code = trim((string)$code);
    $code = preg_replace('/[\x00-\x1F\x7F]/u', '', $code);
    if (mb_strlen($code) > 32) {
        $code = mb_substr($code, 0, 32);
    }

    return trim((string)$code);
}

function site_smiley_normalize_title($title)
{
    $title = trim((string)$title);
    $title = preg_replace('/[\x00-\x1F\x7F]/u', '', $title);
    if (mb_strlen($title) > 120) {
        $title = mb_substr($title, 0, 120);
    }

    return trim((string)$title);
}

function site_smiley_normalize_type($type)
{
    return trim((string)$type) === 'image' ? 'image' : 'emoji';
}

function site_smiley_code_exists($code, $excludeId = 0)
{
    ensure_site_smiley_schema();

    $sql = 'SELECT COUNT(*) FROM ' . site_smiley_table_name() . ' WHERE code = :code';
    $params = [':code' => (string)$code];

    if ((int)$excludeId > 0) {
        $sql .= ' AND id <> :exclude_id';
        $params[':exclude_id'] = (int)$excludeId;
    }

    $stmt = $GLOBALS['pdo']->prepare($sql);
    $stmt->execute($params);

    return (int)$stmt->fetchColumn() > 0;
}

function site_smiley_is_valid_local_image_path($path)
{
    $path = trim((string)$path);
    if ($path === '' || strpos($path, '..') !== false || preg_match('/[\x00-\x1F\x7F]/', $path)) {
        return false;
    }

    if (preg_match('/^[a-z][a-z0-9+\-.]*:/i', $path) || str_starts_with($path, '//')) {
        return false;
    }

    $normalized = str_replace('\\', '/', ltrim($path, '/'));
    $parsedPath = (string)(parse_url($normalized, PHP_URL_PATH) ?? $normalized);
    $extension = mb_strtolower(pathinfo($parsedPath, PATHINFO_EXTENSION));

    return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
}

function upload_smiley_image(array $file)
{
    [$ok, $validated] = validate_upload_file($file, [
        'required' => true,
        'profile' => 'image',
    ]);

    if (!$ok) {
        return [false, $validated];
    }

    $smileyDir = BASEDIR . 'images/smilies';
    if (!is_dir($smileyDir) && !@mkdir($smileyDir, 0755, true) && !is_dir($smileyDir)) {
        return [false, 'Nepavyko sukurti šypsenėlių katalogo.'];
    }

    $filename = $validated['safe_name'] . '-' . bin2hex(random_bytes(8)) . '.' . $validated['extension'];
    $target = $smileyDir . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($validated['tmp_name'], $target)) {
        return [false, 'Nepavyko išsaugoti šypsenėlės paveikslėlio.'];
    }

    return [true, 'images/smilies/' . $filename];
}

function site_smileys($activeOnly = true)
{
    ensure_site_smiley_schema();

    if (!isset($GLOBALS['_site_smiley_cache']) || !is_array($GLOBALS['_site_smiley_cache'])) {
        $GLOBALS['_site_smiley_cache'] = [];
    }

    $cacheKey = site_smiley_cache_key((bool)$activeOnly);
    if (isset($GLOBALS['_site_smiley_cache'][$cacheKey])) {
        return $GLOBALS['_site_smiley_cache'][$cacheKey];
    }

    $sql = 'SELECT * FROM ' . site_smiley_table_name();
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, id ASC';

    $rows = $GLOBALS['pdo']->query($sql)->fetchAll();
    $GLOBALS['_site_smiley_cache'][$cacheKey] = $rows;

    return $rows;
}

function site_smiley_find($id)
{
    ensure_site_smiley_schema();

    $stmt = $GLOBALS['pdo']->prepare('SELECT * FROM ' . site_smiley_table_name() . ' WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => (int)$id]);

    return $stmt->fetch() ?: null;
}

function site_smiley_preview_html(array $smiley, $extraClass = '')
{
    $extraClass = trim((string)$extraClass);
    $baseClass = trim('site-smiley ' . $extraClass . ($smiley['type'] === 'image' ? ' site-smiley-image' : ' site-smiley-emoji'));
    $label = trim((string)($smiley['title'] ?? $smiley['code'] ?? 'Šypsenėlė'));

    if (($smiley['type'] ?? 'emoji') === 'image') {
        $path = (string)($smiley['value'] ?? '');
        if (!site_smiley_is_valid_local_image_path($path)) {
            return '<span class="' . e($baseClass) . '">' . e((string)($smiley['code'] ?? '')) . '</span>';
        }

        return '<img src="' . escape_url(public_path(ltrim($path, '/'))) . '" alt="' . e($label) . '" class="' . e($baseClass) . '" loading="lazy" decoding="async">';
    }

    return '<span class="' . e($baseClass) . '" aria-label="' . e($label) . '">' . e((string)($smiley['value'] ?? '')) . '</span>';
}

function site_smiley_button_html(array $smiley, $extraClass = '')
{
    return site_smiley_preview_html($smiley, trim('site-smiley-button-icon ' . $extraClass));
}

function apply_site_smileys($html, $extraClass = '')
{
    foreach (site_smileys(true) as $smiley) {
        $code = (string)($smiley['code'] ?? '');
        if ($code === '') {
            continue;
        }

        $html = str_replace(escape_html($code), site_smiley_preview_html($smiley, $extraClass), $html);
    }

    return $html;
}

function save_site_smiley(array $data, array $imageFile = [], $id = 0)
{
    ensure_site_smiley_schema();

    $id = (int)$id;
    $existing = $id > 0 ? site_smiley_find($id) : null;
    if ($id > 0 && !$existing) {
        return [false, 'Šypsenėlė nerasta.', null];
    }

    $code = site_smiley_normalize_code($data['code'] ?? '');
    $title = site_smiley_normalize_title($data['title'] ?? '');
    $type = site_smiley_normalize_type($data['type'] ?? 'emoji');
    $sortOrder = (int)($data['sort_order'] ?? 0);
    $isActive = !empty($data['is_active']) ? 1 : 0;

    if ($code === '') {
        return [false, 'Kodas yra privalomas.', null];
    }
    if (site_smiley_code_exists($code, $id)) {
        return [false, 'Toks šypsenėlės kodas jau naudojamas.', null];
    }
    if ($title === '') {
        $title = $code;
    }

    if ($type === 'image') {
        $hasUpload = isset($imageFile['error']) && (int)$imageFile['error'] !== UPLOAD_ERR_NO_FILE;
        if ($hasUpload) {
            [$ok, $imagePath] = upload_smiley_image($imageFile);
            if (!$ok) {
                return [false, $imagePath, null];
            }
            $value = $imagePath;
        } elseif ($existing && ($existing['type'] ?? '') === 'image' && !empty($existing['value'])) {
            $value = (string)$existing['value'];
        } else {
                return [false, 'Pasirinkite šypsenėlės paveikslėlį.', null];
        }
    } else {
        $value = trim((string)($data['emoji_value'] ?? ''));
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
        if ($value === '' && $existing && ($existing['type'] ?? '') === 'emoji' && !empty($existing['value'])) {
            $value = trim((string)$existing['value']);
        }
        if ($value === '') {
            $value = site_smiley_default_value_for_code($code);
        }
        if ($value === '' && preg_match('/[^\x20-\x7E]/u', $code)) {
            $value = $code;
        }
        if ($value === '') {
            return [false, 'Jaustukas yra privalomas.', null];
        }
        if (mb_strlen($value) > 32) {
            $value = mb_substr($value, 0, 32);
        }
    }

    if ($id > 0) {
        $stmt = $GLOBALS['pdo']->prepare('
            UPDATE ' . site_smiley_table_name() . '
            SET code = :code,
                title = :title,
                type = :type,
                value = :value,
                sort_order = :sort_order,
                is_active = :is_active,
                updated_at = NOW()
            WHERE id = :id
        ');
        $stmt->execute([
            ':code' => $code,
            ':title' => $title,
            ':type' => $type,
            ':value' => $value,
            ':sort_order' => $sortOrder,
            ':is_active' => $isActive,
            ':id' => $id,
        ]);

        audit_log(current_user()['id'] ?? null, 'smiley_update', site_smiley_table_name(), $id, [
            'code' => $code,
            'type' => $type,
        ]);
        site_smiley_reset_cache();

        return [true, 'Šypsenėlė atnaujinta.', $id];
    }

    $stmt = $GLOBALS['pdo']->prepare('
        INSERT INTO ' . site_smiley_table_name() . ' (code, title, type, value, sort_order, is_active, created_at, updated_at)
        VALUES (:code, :title, :type, :value, :sort_order, :is_active, NOW(), NOW())
    ');
    $stmt->execute([
        ':code' => $code,
        ':title' => $title,
        ':type' => $type,
        ':value' => $value,
        ':sort_order' => $sortOrder,
        ':is_active' => $isActive,
    ]);

    $newId = (int)$GLOBALS['pdo']->lastInsertId();
    audit_log(current_user()['id'] ?? null, 'smiley_create', site_smiley_table_name(), $newId, [
        'code' => $code,
        'type' => $type,
    ]);
    site_smiley_reset_cache();

    return [true, 'Šypsenėlė sukurta.', $newId];
}

function delete_site_smiley($id)
{
    ensure_site_smiley_schema();

    $smiley = site_smiley_find($id);
    if (!$smiley) {
        return [false, 'Šypsenėlė nerasta.'];
    }

    $stmt = $GLOBALS['pdo']->prepare('DELETE FROM ' . site_smiley_table_name() . ' WHERE id = :id');
    $stmt->execute([':id' => (int)$id]);

    audit_log(current_user()['id'] ?? null, 'smiley_delete', site_smiley_table_name(), (int)$id, [
        'code' => $smiley['code'],
    ]);
    site_smiley_reset_cache();

    return [true, 'Šypsenėlė ištrinta.'];
}

function toggle_site_smiley_status($id, $enabled)
{
    ensure_site_smiley_schema();

    $smiley = site_smiley_find($id);
    if (!$smiley) {
        return [false, 'Šypsenėlė nerasta.'];
    }

    $stmt = $GLOBALS['pdo']->prepare('UPDATE ' . site_smiley_table_name() . ' SET is_active = :enabled, updated_at = NOW() WHERE id = :id');
    $stmt->execute([
        ':enabled' => $enabled ? 1 : 0,
        ':id' => (int)$id,
    ]);

    audit_log(current_user()['id'] ?? null, 'smiley_toggle', site_smiley_table_name(), (int)$id, [
        'enabled' => $enabled ? 1 : 0,
        'code' => $smiley['code'],
    ]);
    site_smiley_reset_cache();

    return [true, $enabled ? 'Šypsenėlė įjungta.' : 'Šypsenėlė išjungta.'];
}

function bbcode_allowed_map(array $allowedTags)
{
    return array_fill_keys(array_map('mb_strtolower', $allowedTags), true);
}

function bbcode_normalize_image_source($value)
{
    $url = trim(html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url)) {
        return null;
    }

    if (preg_match('/^\s*(javascript|data|vbscript):/i', $url)) {
        return null;
    }

    $parts = parse_url($url);
    if ($parts === false) {
        return null;
    }

    if (!empty($parts['scheme'])) {
        if (validate_url_value($url, true, 'Paveikslėlis', ['https'], false) !== null) {
            return null;
        }

        $path = (string)($parts['path'] ?? '');
        $resolved = $url;
    } else {
        if (!site_smiley_is_valid_local_image_path($url)) {
            return null;
        }

        $path = (string)(parse_url($url, PHP_URL_PATH) ?? $url);
        $resolved = public_path(ltrim(str_replace('\\', '/', $url), '/'));
    }

    $extension = mb_strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        return null;
    }

    return $resolved;
}

function bbcode_extract_youtube_video_id($value)
{
    $value = trim(html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($value === '' || preg_match('/[\x00-\x1F\x7F]/', $value)) {
        return null;
    }

    if (preg_match('/^[A-Za-z0-9_-]{11}$/', $value)) {
        return $value;
    }

    if (validate_url_value($value, true, 'YouTube', ['http', 'https'], false) !== null) {
        return null;
    }

    $parts = parse_url($value);
    if ($parts === false) {
        return null;
    }

    $host = mb_strtolower((string)($parts['host'] ?? ''));
    $host = preg_replace('/^www\./', '', $host);
    $host = preg_replace('/^m\./', '', $host);
    $path = trim((string)($parts['path'] ?? ''), '/');

    if ($host === 'youtu.be') {
        $candidate = explode('/', $path)[0] ?? '';
        return preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate) ? $candidate : null;
    }

    if (!in_array($host, ['youtube.com', 'youtube-nocookie.com'], true)) {
        return null;
    }

    parse_str((string)($parts['query'] ?? ''), $query);
    $candidate = '';
    if (!empty($query['v'])) {
        $candidate = (string)$query['v'];
    } elseif (str_starts_with($path, 'embed/')) {
        $candidate = explode('/', substr($path, 6))[0] ?? '';
    } elseif (str_starts_with($path, 'shorts/')) {
        $candidate = explode('/', substr($path, 7))[0] ?? '';
    } elseif (str_starts_with($path, 'live/')) {
        $candidate = explode('/', substr($path, 5))[0] ?? '';
    }

    return preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate) ? $candidate : null;
}

function bbcode_youtube_embed_url($videoId)
{
    return 'https://www.youtube-nocookie.com/embed/' . rawurlencode((string)$videoId) . '?rel=0';
}

function bbcode_render_url_tag($url, $label)
{
    $url = trim((string)$url);
    $label = (string)$label;
    if (validate_url_value($url, true, 'Nuoroda', ['http', 'https'], false) !== null) {
        return $label;
    }

    return '<a href="' . escape_url($url) . '" target="_blank" rel="nofollow ugc noopener noreferrer">' . $label . '</a>';
}

function bbcode_render_img_tag($value)
{
    $source = bbcode_normalize_image_source($value);
    if ($source === null) {
        return escape_html((string)$value);
    }

    return '<img src="' . escape_url($source) . '" alt="" class="bbcode-image img-fluid" loading="lazy" decoding="async">';
}

function bbcode_render_youtube_tag($value)
{
    $videoId = bbcode_extract_youtube_video_id($value);
    if ($videoId === null) {
        return escape_html((string)$value);
    }

    $embedUrl = bbcode_youtube_embed_url($videoId);

    return '<div class="bbcode-video-embed"><iframe src="' . escape_url($embedUrl) . '" title="YouTube video" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div>';
}

function bbcode_to_html($text, array $options = [])
{
    $allowedTags = $options['allowed_tags'] ?? ['b', 'i', 'u', 'quote', 'code', 'url'];
    $maxLength = (int)($options['max_length'] ?? 5000);
    $allowedMap = bbcode_allowed_map($allowedTags);

    $text = sanitize_bbcode_input($text, $allowedTags, $maxLength);
    $text = escape_html($text);

    $patterns = [
        '/\[b\](.*?)\[\/b\]/is' => '<strong>$1</strong>',
        '/\[i\](.*?)\[\/i\]/is' => '<em>$1</em>',
        '/\[u\](.*?)\[\/u\]/is' => '<u>$1</u>',
        '/\[quote\](.*?)\[\/quote\]/is' => '<blockquote class="border-start ps-3 text-secondary">$1</blockquote>',
        '/\[code\](.*?)\[\/code\]/is' => '<code>$1</code>',
    ];

    foreach ($patterns as $pattern => $replace) {
        $text = preg_replace($pattern, $replace, $text);
    }

    if (isset($allowedMap['url'])) {
        $text = preg_replace_callback('/\[url=(https?:\/\/[^\]\s]+)\](.*?)\[\/url\]/is', function ($matches) {
            return bbcode_render_url_tag(html_entity_decode((string)$matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'), (string)$matches[2]);
        }, $text);
    }

    if (isset($allowedMap['img'])) {
        $text = preg_replace_callback('/\[img\](.*?)\[\/img\]/is', function ($matches) {
            return bbcode_render_img_tag($matches[1]);
        }, $text);
    }

    if (isset($allowedMap['youtube'])) {
        $text = preg_replace_callback('/\[youtube\](.*?)\[\/youtube\]/is', function ($matches) {
            return bbcode_render_youtube_tag($matches[1]);
        }, $text);
    }

    return nl2br($text);
}
