<?php
function profile_table_exists($table)
{
    static $cache = [];
    $table = (string)$table;
    if (isset($cache[$table])) {
        return $cache[$table];
    }

    try {
        $stmt = $GLOBALS['pdo']->query('SHOW TABLES LIKE ' . $GLOBALS['pdo']->quote($table));
        $cache[$table] = (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        $cache[$table] = false;
    }

    return $cache[$table];
}

function profile_column_exists($table, $column)
{
    static $cache = [];
    $key = $table . '.' . $column;
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    try {
        $stmt = $GLOBALS['pdo']->prepare('SHOW COLUMNS FROM `' . str_replace('`', '``', (string)$table) . '` LIKE :column');
        $stmt->execute([':column' => (string)$column]);
        $cache[$key] = (bool)$stmt->fetch();
    } catch (Throwable $e) {
        $cache[$key] = false;
    }

    return $cache[$key];
}

function profile_rating_table()
{
    return 'user_profile_ratings';
}

function profile_comment_table()
{
    return 'user_profile_comments';
}

function ensure_user_profile_schema()
{
    static $ensured = false;
    if ($ensured || !profile_table_exists('users')) {
        return;
    }

    $ensured = true;

    try {
        if (!profile_column_exists('users', 'signature')) {
            $GLOBALS['pdo']->exec('ALTER TABLE users ADD COLUMN signature TEXT NULL AFTER avatar');
        }
    } catch (Throwable $e) {
    }

    try {
        if (!profile_column_exists('users', 'admin_password')) {
            $GLOBALS['pdo']->exec('ALTER TABLE users ADD COLUMN admin_password VARCHAR(255) NULL AFTER password');
        }
    } catch (Throwable $e) {
    }

    try {
        $GLOBALS['pdo']->exec("
            CREATE TABLE IF NOT EXISTS " . profile_rating_table() . " (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                profile_user_id INT UNSIGNED NOT NULL,
                author_user_id INT UNSIGNED NOT NULL,
                rating TINYINT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_profile_rating (profile_user_id, author_user_id),
                KEY idx_profile_rating_profile (profile_user_id, updated_at, id),
                KEY idx_profile_rating_author (author_user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } catch (Throwable $e) {
    }

    try {
        $GLOBALS['pdo']->exec("
            CREATE TABLE IF NOT EXISTS " . profile_comment_table() . " (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                profile_user_id INT UNSIGNED NOT NULL,
                author_user_id INT UNSIGNED NOT NULL,
                content TEXT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_profile_comment_profile (profile_user_id, created_at, id),
                KEY idx_profile_comment_author (author_user_id, created_at),
                KEY idx_profile_comment_recent (created_at, id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } catch (Throwable $e) {
    }
}

function clean_user_signature($signature, $maxLength = 500)
{
    $signature = trim((string)$signature);
    if ($signature === '') {
        return '';
    }

    $signature = preg_replace("/\r\n?/", "\n", $signature);
    $signature = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $signature);
    if (mb_strlen($signature) > $maxLength) {
        $signature = mb_substr($signature, 0, $maxLength);
    }

    return trim($signature);
}

function render_user_signature($signature)
{
    $signature = clean_user_signature($signature);
    if ($signature === '') {
        return '';
    }

    return nl2br(e($signature));
}

function user_profile_url($userId)
{
    return public_path('user.php?id=' . (int)$userId);
}

function profile_comment_url($userId, $commentId)
{
    return user_profile_url((int)$userId) . '#profile-comment-' . (int)$commentId;
}

function fetch_public_user_profile($userId)
{
    ensure_user_profile_schema();

    $stmt = $GLOBALS['pdo']->prepare("
        SELECT u.*, r.name AS role_name, r.slug AS role_slug, r.level AS role_level
        FROM users u
        LEFT JOIN roles r ON r.id = u.role_id
        WHERE u.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => (int)$userId]);

    return $stmt->fetch() ?: null;
}

function fetch_user_latest_ip($userId)
{
    if (!profile_table_exists('audit_logs')) {
        return null;
    }

    $stmt = $GLOBALS['pdo']->prepare("
        SELECT COALESCE(NULLIF(INET6_NTOA(ip_address), ''), NULLIF(CAST(ip_address AS CHAR(45)), ''), '') AS ip_text
        FROM audit_logs
        WHERE user_id = :id
          AND ip_address IS NOT NULL
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([':id' => (int)$userId]);
    $ip = trim((string)$stmt->fetchColumn());

    return $ip !== '' ? $ip : null;
}

function fetch_ip_ban_status($ip)
{
    $ip = trim((string)$ip);
    if ($ip === '' || !profile_table_exists('ip_bans')) {
        return null;
    }

    try {
        $stmt = $GLOBALS['pdo']->prepare("
            SELECT reason, banned_until, is_permanent
            FROM ip_bans
            WHERE ip_address = INET6_ATON(:ip)
              AND (is_permanent = 1 OR banned_until IS NULL OR banned_until > NOW())
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([':ip' => $ip]);
        $ban = $stmt->fetch();

        return $ban ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function count_user_shoutbox_messages($userId)
{
    $userId = (int)$userId;
    if ($userId < 1) {
        return 0;
    }

    $table = profile_table_exists('infusion_shoutbox_messages') ? 'infusion_shoutbox_messages' : (profile_table_exists('shouts') ? 'shouts' : null);
    if ($table === null) {
        return 0;
    }

    $stmt = $GLOBALS['pdo']->prepare('SELECT COUNT(*) FROM ' . $table . ' WHERE user_id = :user_id');
    $stmt->execute([':user_id' => $userId]);

    return (int)$stmt->fetchColumn();
}

function count_user_forum_messages($userId)
{
    $userId = (int)$userId;
    if ($userId < 1) {
        return 0;
    }

    if (!profile_table_exists('infusion_forum_topics') || !profile_table_exists('infusion_forum_posts')) {
        return 0;
    }

    $topicStmt = $GLOBALS['pdo']->prepare('SELECT COUNT(*) FROM infusion_forum_topics WHERE user_id = :user_id');
    $topicStmt->execute([':user_id' => $userId]);
    $topics = (int)$topicStmt->fetchColumn();

    $replyStmt = $GLOBALS['pdo']->prepare('SELECT COUNT(*) FROM infusion_forum_posts WHERE user_id = :user_id');
    $replyStmt->execute([':user_id' => $userId]);
    $replies = (int)$replyStmt->fetchColumn();

    return $topics + $replies;
}

function profile_rating_options()
{
    return [1, 2, 3, 4, 5];
}

function profile_comment_allowed_tags()
{
    return ['b', 'i', 'u', 'quote', 'url'];
}

function profile_comment_smileys()
{
    return [
        ':)' => '&#128578;',
        ';)' => '&#128521;',
        ':D' => '&#128516;',
        ':(' => '&#128577;',
        ':P' => '&#128539;',
        '<3' => '&#10084;&#65039;',
    ];
}

function profile_prepare_comment_body($content, $maxLength = 2000)
{
    $content = sanitize_bbcode_input((string)$content, profile_comment_allowed_tags(), (int)$maxLength);
    $content = trim(preg_replace("/\r\n?/", "\n", $content));

    return $content;
}

function profile_render_comment_body($content)
{
    $html = bbcode_to_html((string)$content, [
        'allowed_tags' => profile_comment_allowed_tags(),
        'max_length' => 2000,
    ]);

    foreach (profile_comment_smileys() as $code => $emoji) {
        $html = str_replace(escape_html($code), '<span class="profile-comment-smiley">' . $emoji . '</span>', $html);
    }

    return $html;
}

function profile_comment_excerpt($content, $length = 120)
{
    $plain = preg_replace('/\[(\/?)[a-z]+(?:=[^\]]*)?\]/i', '', (string)$content);
    $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($plain)));

    if ($plain === '' || mb_strlen($plain) <= $length) {
        return $plain;
    }

    return rtrim(mb_substr($plain, 0, $length - 1)) . '...';
}

function fetch_profile_rating_summary($profileUserId)
{
    ensure_user_profile_schema();

    $stmt = $GLOBALS['pdo']->prepare('
        SELECT COUNT(*) AS rating_count, COALESCE(AVG(rating), 0) AS average_rating
        FROM ' . profile_rating_table() . '
        WHERE profile_user_id = :profile_user_id
    ');
    $stmt->execute([':profile_user_id' => (int)$profileUserId]);
    $summary = $stmt->fetch();

    return [
        'rating_count' => (int)($summary['rating_count'] ?? 0),
        'average_rating' => round((float)($summary['average_rating'] ?? 0), 1),
    ];
}

function fetch_profile_rating_for_viewer($profileUserId, $viewerUserId)
{
    $profileUserId = (int)$profileUserId;
    $viewerUserId = (int)$viewerUserId;
    if ($profileUserId < 1 || $viewerUserId < 1) {
        return 0;
    }

    ensure_user_profile_schema();

    $stmt = $GLOBALS['pdo']->prepare('
        SELECT rating
        FROM ' . profile_rating_table() . '
        WHERE profile_user_id = :profile_user_id
          AND author_user_id = :author_user_id
        LIMIT 1
    ');
    $stmt->execute([
        ':profile_user_id' => $profileUserId,
        ':author_user_id' => $viewerUserId,
    ]);

    return (int)$stmt->fetchColumn();
}

function save_profile_rating($profileUserId, $authorUserId, $rating)
{
    ensure_user_profile_schema();

    $profileUserId = (int)$profileUserId;
    $authorUserId = (int)$authorUserId;
    $rating = (int)$rating;

    if ($profileUserId < 1 || $authorUserId < 1) {
        return [false, 'Prisijungimas reikalingas.'];
    }
    if (!in_array($rating, profile_rating_options(), true)) {
        return [false, 'Pasirinktas neteisingas ivertinimas.'];
    }
    if (!fetch_public_user_profile($profileUserId)) {
        return [false, 'Profilis nerastas.'];
    }

    $stmt = $GLOBALS['pdo']->prepare('
        INSERT INTO ' . profile_rating_table() . ' (profile_user_id, author_user_id, rating, created_at, updated_at)
        VALUES (:profile_user_id, :author_user_id, :rating, NOW(), NOW())
        ON DUPLICATE KEY UPDATE rating = VALUES(rating), updated_at = NOW()
    ');
    $stmt->execute([
        ':profile_user_id' => $profileUserId,
        ':author_user_id' => $authorUserId,
        ':rating' => $rating,
    ]);

    audit_log($authorUserId, 'profile_rating_save', profile_rating_table(), $profileUserId, [
        'rating' => $rating,
    ]);

    return [true, 'Ivertinimas issaugotas.'];
}

function count_profile_comments($profileUserId)
{
    ensure_user_profile_schema();

    $stmt = $GLOBALS['pdo']->prepare('
        SELECT COUNT(*)
        FROM ' . profile_comment_table() . '
        WHERE profile_user_id = :profile_user_id
    ');
    $stmt->execute([':profile_user_id' => (int)$profileUserId]);

    return (int)$stmt->fetchColumn();
}

function fetch_profile_comment($commentId)
{
    ensure_user_profile_schema();

    $stmt = $GLOBALS['pdo']->prepare('
        SELECT c.*,
               author.username AS author_username,
               author.avatar AS author_avatar,
               author.email AS author_email,
               profile_user.username AS profile_username
        FROM ' . profile_comment_table() . ' c
        LEFT JOIN users author ON author.id = c.author_user_id
        LEFT JOIN users profile_user ON profile_user.id = c.profile_user_id
        WHERE c.id = :id
        LIMIT 1
    ');
    $stmt->execute([':id' => (int)$commentId]);

    return $stmt->fetch() ?: null;
}

function fetch_profile_comments($profileUserId, $limit = 20)
{
    ensure_user_profile_schema();

    $limit = max(1, min(100, (int)$limit));
    $stmt = $GLOBALS['pdo']->prepare('
        SELECT c.*,
               author.username AS author_username,
               author.avatar AS author_avatar,
               author.email AS author_email,
               profile_user.username AS profile_username
        FROM ' . profile_comment_table() . ' c
        LEFT JOIN users author ON author.id = c.author_user_id
        LEFT JOIN users profile_user ON profile_user.id = c.profile_user_id
        WHERE c.profile_user_id = :profile_user_id
        ORDER BY c.created_at DESC, c.id DESC
        LIMIT ' . $limit
    );
    $stmt->execute([':profile_user_id' => (int)$profileUserId]);

    return $stmt->fetchAll();
}

function fetch_latest_profile_comments($limit = 5)
{
    ensure_user_profile_schema();

    $limit = max(1, min(20, (int)$limit));
    $stmt = $GLOBALS['pdo']->query('
        SELECT c.*,
               author.username AS author_username,
               author.avatar AS author_avatar,
               author.email AS author_email,
               profile_user.username AS profile_username
        FROM ' . profile_comment_table() . ' c
        LEFT JOIN users author ON author.id = c.author_user_id
        LEFT JOIN users profile_user ON profile_user.id = c.profile_user_id
        ORDER BY c.created_at DESC, c.id DESC
        LIMIT ' . $limit
    );

    return $stmt->fetchAll();
}

function can_manage_profile_comment(array $comment, $actor = null)
{
    $actor = $actor ?: current_user();
    if (!$actor || empty($actor['id'])) {
        return false;
    }

    $actorId = (int)$actor['id'];
    if ($actorId === (int)$comment['author_user_id'] || $actorId === (int)$comment['profile_user_id']) {
        return true;
    }

    return has_permission($GLOBALS['pdo'], $actorId, 'admin.access');
}

function create_profile_comment($profileUserId, $authorUserId, $content)
{
    ensure_user_profile_schema();

    $profileUserId = (int)$profileUserId;
    $authorUserId = (int)$authorUserId;
    $content = profile_prepare_comment_body($content, 2000);

    if ($profileUserId < 1 || $authorUserId < 1) {
        return [false, 'Prisijungimas reikalingas.', null];
    }
    if (!fetch_public_user_profile($profileUserId)) {
        return [false, 'Profilis nerastas.', null];
    }
    if ($content === '') {
        return [false, 'Komentaras negali buti tuscias.', null];
    }

    $stmt = $GLOBALS['pdo']->prepare('
        INSERT INTO ' . profile_comment_table() . ' (profile_user_id, author_user_id, content, created_at, updated_at)
        VALUES (:profile_user_id, :author_user_id, :content, NOW(), NOW())
    ');
    $stmt->execute([
        ':profile_user_id' => $profileUserId,
        ':author_user_id' => $authorUserId,
        ':content' => $content,
    ]);

    $commentId = (int)$GLOBALS['pdo']->lastInsertId();
    audit_log($authorUserId, 'profile_comment_create', profile_comment_table(), $commentId, [
        'profile_user_id' => $profileUserId,
    ]);

    return [true, 'Komentaras paskelbtas.', $commentId];
}

function delete_profile_comment($commentId, $actor = null)
{
    ensure_user_profile_schema();

    $comment = fetch_profile_comment($commentId);
    if (!$comment) {
        return [false, 'Komentaras nerastas.', null];
    }
    if (!can_manage_profile_comment($comment, $actor)) {
        return [false, 'Nepakanka teisiu istrinti komentara.', null];
    }

    $stmt = $GLOBALS['pdo']->prepare('DELETE FROM ' . profile_comment_table() . ' WHERE id = :id');
    $stmt->execute([':id' => (int)$comment['id']]);

    $actorId = $actor['id'] ?? current_user()['id'] ?? null;
    audit_log($actorId, 'profile_comment_delete', profile_comment_table(), (int)$comment['id'], [
        'profile_user_id' => (int)$comment['profile_user_id'],
    ]);

    return [true, 'Komentaras istrintas.', (int)$comment['profile_user_id']];
}

function render_profile_rating_stars($averageRating, $ratingCount)
{
    $averageRating = max(0, min(5, (float)$averageRating));
    $filled = (int)round($averageRating);
    $label = $ratingCount > 0
        ? number_format($averageRating, 1) . ' / 5'
        : 'Kol kas neivertinta';

    $html = '<span class="rating-stars" aria-label="' . e($label) . '">';
    foreach (profile_rating_options() as $option) {
        $class = $option <= $filled ? ' is-active' : '';
        $html .= '<span class="rating-star' . $class . '">&#9733;</span>';
    }
    $html .= '</span>';

    return $html;
}

ensure_user_profile_schema();
