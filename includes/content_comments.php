<?php

function content_comments_table()
{
    return 'content_comments';
}

function content_comment_editable_types()
{
    return ['profile', 'news', 'download'];
}

function ensure_content_comments_schema()
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $ensured = true;

    try {
        $GLOBALS['pdo']->exec("
            CREATE TABLE IF NOT EXISTS " . content_comments_table() . " (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                content_type VARCHAR(50) NOT NULL,
                content_id INT UNSIGNED NOT NULL,
                author_user_id INT UNSIGNED NOT NULL,
                content TEXT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_content_comments_target (content_type, content_id, created_at, id),
                KEY idx_content_comments_author (author_user_id, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } catch (Throwable $e) {
    }
}

function content_comments_per_page_setting()
{
    $value = (int)setting('content_comments_per_page', 10);
    return max(1, min(100, $value));
}

function content_comment_flood_window_setting()
{
    $value = (int)setting('content_comments_flood_seconds', 30);
    return max(3, min(3600, $value));
}

function content_comment_rate_limit_setting()
{
    $value = (int)setting('content_comments_rate_limit_count', 5);
    return max(1, min(100, $value));
}

function content_comment_rate_limit_window_setting()
{
    $value = (int)setting('content_comments_rate_limit_window_seconds', 300);
    return max(30, min(86400, $value));
}

function content_comment_badwords_setting()
{
    return function_exists('badwords_terms') ? badwords_terms() : [];
}

function content_comment_allowed_tags()
{
    return ['b', 'i', 'u', 'quote', 'url', 'img', 'youtube'];
}

function content_comment_prepare_body($content, $maxLength = 3000)
{
    $content = sanitize_bbcode_input((string)$content, content_comment_allowed_tags(), (int)$maxLength);
    return trim(preg_replace("/\r\n?/", "\n", $content));
}

function content_comment_render_body($content)
{
    $html = bbcode_to_html((string)$content, [
        'allowed_tags' => content_comment_allowed_tags(),
        'max_length' => 3000,
    ]);

    return apply_site_smileys($html, 'content-comment-smiley');
}

function content_comment_rate_limit_targets($contentType, $authorUserId)
{
    $targets = [];
    $authorUserId = (int)$authorUserId;
    $ip = rate_limit_client_ip();
    $maxAttempts = content_comment_rate_limit_setting();
    $windowSeconds = content_comment_rate_limit_window_setting();

    if ($authorUserId > 0) {
        $targets[] = [
            'scope' => 'content_comment.user.' . trim((string)$contentType),
            'identifier' => (string)$authorUserId,
            'max_attempts' => $maxAttempts,
            'window_seconds' => $windowSeconds,
            'lockout_seconds' => $windowSeconds,
        ];
    }

    if ($ip !== '') {
        $targets[] = [
            'scope' => 'content_comment.ip.' . trim((string)$contentType),
            'identifier' => $ip,
            'max_attempts' => $maxAttempts,
            'window_seconds' => $windowSeconds,
            'lockout_seconds' => $windowSeconds,
        ];
    }

    return $targets;
}

function fetch_latest_content_comment_timestamp($contentType, $authorUserId)
{
    ensure_content_comments_schema();

    $stmt = $GLOBALS['pdo']->prepare('
        SELECT created_at
        FROM ' . content_comments_table() . '
        WHERE content_type = :content_type
          AND author_user_id = :author_user_id
        ORDER BY id DESC
        LIMIT 1
    ');
    $stmt->execute([
        ':content_type' => (string)$contentType,
        ':author_user_id' => (int)$authorUserId,
    ]);

    return $stmt->fetchColumn() ?: null;
}

function content_comment_badwords_match($content)
{
    $haystack = mb_strtolower(trim((string)$content));
    if ($haystack === '') {
        return null;
    }

    foreach (content_comment_badwords_setting() as $word) {
        if ($word !== '' && mb_stripos($haystack, $word) !== false) {
            return $word;
        }
    }

    return null;
}

function content_comment_spam_score($content)
{
    $score = 0;
    $content = trim((string)$content);
    if ($content === '') {
        return $score;
    }

    if (preg_match_all('~https?://~i', $content) >= 3) {
        $score += 2;
    }

    if (preg_match('/(.)\\1{7,}/u', $content)) {
        $score += 1;
    }

    if (preg_match('/[A-ZĄČĘĖĮŠŲŪŽ]{12,}/u', $content)) {
        $score += 1;
    }

    if (mb_strlen($content) > 0) {
        $upper = preg_match_all('/\p{Lu}/u', $content);
        $letters = preg_match_all('/\p{L}/u', $content);
        if ($letters > 0 && ($upper / $letters) > 0.7) {
            $score += 1;
        }
    }

    return $score;
}

function validate_content_comment_submission($contentType, $contentId, $authorUserId, $content)
{
    $contentType = trim((string)$contentType);
    $contentId = (int)$contentId;
    $authorUserId = (int)$authorUserId;

    if ($contentType === '' || $contentId < 1 || $authorUserId < 1) {
        return [false, 'Komentuoti galima tik prisijungus ir ant esamo turinio.'];
    }

    $rateLimit = rate_limit_status(content_comment_rate_limit_targets($contentType, $authorUserId));
    if (!empty($rateLimit['blocked'])) {
        return [false, 'Komentuojate per daznai. Palaukite ' . format_wait_time((int)$rateLimit['retry_after']) . '.'];
    }

    $lastCreatedAt = fetch_latest_content_comment_timestamp($contentType, $authorUserId);
    if ($lastCreatedAt) {
        $lastTs = strtotime((string)$lastCreatedAt) ?: 0;
        $wait = content_comment_flood_window_setting() - (time() - $lastTs);
        if ($wait > 0) {
            return [false, 'Tarp komentaru turi buti bent ' . content_comment_flood_window_setting() . ' s pertrauka. Likusios ' . $wait . ' s.'];
        }
    }

    [$badwordsOk, $badwordsMessage] = function_exists('badwords_validate')
        ? badwords_validate($content, 'Komentare')
        : [true, null];
    if (!$badwordsOk) {
        rate_limit_hit(content_comment_rate_limit_targets($contentType, $authorUserId));
        return [false, $badwordsMessage];
    }

    if (content_comment_spam_score($content) >= 2) {
        rate_limit_hit(content_comment_rate_limit_targets($contentType, $authorUserId));
        return [false, 'Komentaras atrodo kaip spam arba triuksmingas tekstas. Pataisykite ji ir bandykite dar karta.'];
    }

    return [true, null];
}

function content_comments_count($contentType, $contentId)
{
    ensure_content_comments_schema();

    $stmt = $GLOBALS['pdo']->prepare('
        SELECT COUNT(*)
        FROM ' . content_comments_table() . '
        WHERE content_type = :content_type
          AND content_id = :content_id
    ');
    $stmt->execute([
        ':content_type' => (string)$contentType,
        ':content_id' => (int)$contentId,
    ]);

    return (int)$stmt->fetchColumn();
}

function fetch_content_comments($contentType, $contentId, $limit = 10, $offset = 0)
{
    ensure_content_comments_schema();

    $limit = max(1, min(100, (int)$limit));
    $offset = max(0, (int)$offset);

    $stmt = $GLOBALS['pdo']->prepare('
        SELECT c.*,
               author.username AS author_username,
               author.avatar AS author_avatar,
               author.email AS author_email
        FROM ' . content_comments_table() . ' c
        LEFT JOIN users author ON author.id = c.author_user_id
        WHERE c.content_type = :content_type
          AND c.content_id = :content_id
        ORDER BY c.created_at DESC, c.id DESC
        LIMIT ' . $limit . ' OFFSET ' . $offset
    );
    $stmt->execute([
        ':content_type' => (string)$contentType,
        ':content_id' => (int)$contentId,
    ]);

    return $stmt->fetchAll();
}

function fetch_content_comment($commentId)
{
    ensure_content_comments_schema();

    $stmt = $GLOBALS['pdo']->prepare('
        SELECT c.*,
               author.username AS author_username,
               author.avatar AS author_avatar,
               author.email AS author_email
        FROM ' . content_comments_table() . ' c
        LEFT JOIN users author ON author.id = c.author_user_id
        WHERE c.id = :id
        LIMIT 1
    ');
    $stmt->execute([':id' => (int)$commentId]);

    return $stmt->fetch() ?: null;
}

function create_content_comment($contentType, $contentId, $authorUserId, $content)
{
    ensure_content_comments_schema();

    $contentType = trim((string)$contentType);
    $contentId = (int)$contentId;
    $authorUserId = (int)$authorUserId;
    $content = content_comment_prepare_body($content, 3000);

    if ($content === '') {
        return [false, 'Komentaras negali buti tuscias.', null];
    }

    [$valid, $error] = validate_content_comment_submission($contentType, $contentId, $authorUserId, $content);
    if (!$valid) {
        return [false, $error, null];
    }

    $stmt = $GLOBALS['pdo']->prepare('
        INSERT INTO ' . content_comments_table() . ' (content_type, content_id, author_user_id, content, created_at, updated_at)
        VALUES (:content_type, :content_id, :author_user_id, :content, NOW(), NOW())
    ');
    $stmt->execute([
        ':content_type' => $contentType,
        ':content_id' => $contentId,
        ':author_user_id' => $authorUserId,
        ':content' => $content,
    ]);

    $commentId = (int)$GLOBALS['pdo']->lastInsertId();
    audit_log($authorUserId, 'content_comment_create', content_comments_table(), $commentId, [
        'content_type' => $contentType,
        'content_id' => $contentId,
    ]);
    rate_limit_hit(content_comment_rate_limit_targets($contentType, $authorUserId));

    return [true, 'Komentaras paskelbtas.', $commentId];
}

function can_manage_content_comment(array $comment, $actor = null)
{
    $actor = $actor ?: current_user();
    if (!$actor || empty($actor['id'])) {
        return false;
    }

    if ((int)$actor['id'] === (int)$comment['author_user_id']) {
        return true;
    }

    return has_permission($GLOBALS['pdo'], (int)$actor['id'], 'admin.access');
}

function delete_content_comment($commentId, $actor = null)
{
    $comment = fetch_content_comment($commentId);
    if (!$comment) {
        return [false, 'Komentaras nerastas.', null];
    }
    if (!can_manage_content_comment($comment, $actor)) {
        return [false, 'Nepakanka teisiu istrinti komentaro.', null];
    }

    $stmt = $GLOBALS['pdo']->prepare('DELETE FROM ' . content_comments_table() . ' WHERE id = :id');
    $stmt->execute([':id' => (int)$comment['id']]);

    $actorId = $actor['id'] ?? current_user()['id'] ?? null;
    audit_log($actorId, 'content_comment_delete', content_comments_table(), (int)$comment['id'], [
        'content_type' => (string)$comment['content_type'],
        'content_id' => (int)$comment['content_id'],
    ]);
    if ($actorId) {
        moderation_log($actorId, 'content_comment_deleted', 'content_comment', (int)$comment['id'], [
            'target_label' => moderation_log_excerpt((string)$comment['content']),
            'context_type' => (string)$comment['content_type'],
            'context_id' => (int)$comment['content_id'],
        ]);
    }

    return [true, 'Komentaras istrintas.', [
        'content_type' => (string)$comment['content_type'],
        'content_id' => (int)$comment['content_id'],
    ]];
}
