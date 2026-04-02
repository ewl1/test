<?php

function news_table_name()
{
    return 'infusion_news';
}

function news_setting($key, $default = null)
{
    return setting('news_' . $key, $default);
}

function news_save_setting($key, $value)
{
    return save_setting('news_' . $key, (string)$value);
}

function news_editor_mode()
{
    return editor_normalize_mode(news_setting('editor_mode', 'bbcode'));
}

function news_allowed_bbcode_tags()
{
    return ['b', 'i', 'u', 'quote', 'code', 'url', 'img', 'youtube'];
}

function news_bbcode_buttons()
{
    return [
        ['label' => 'B', 'insert' => '[b][/b]', 'html' => '<strong></strong>'],
        ['label' => 'I', 'insert' => '[i][/i]', 'html' => '<em></em>'],
        ['label' => 'U', 'insert' => '[u][/u]', 'html' => '<u></u>'],
        ['label' => 'Quote', 'insert' => '[quote][/quote]', 'html' => '<blockquote><p></p></blockquote>'],
        ['label' => 'Code', 'insert' => '[code][/code]', 'html' => '<pre><code></code></pre>'],
        ['label' => 'URL', 'insert' => '[url=https://][/url]', 'html' => '<a href="https://"></a>'],
        ['label' => 'IMG', 'insert' => '[img][/img]', 'html' => '<img src="" alt="">'],
        ['label' => 'YouTube', 'insert' => '[youtube]https://youtu.be/VIDEO_ID[/youtube]', 'html' => '<p>https://youtu.be/VIDEO_ID</p>'],
    ];
}

function news_sanitize_html($html)
{
    $html = trim((string)$html);
    if ($html === '') {
        return '';
    }

    $allowedTags = '<p><br><strong><b><em><i><u><blockquote><code><pre><ul><ol><li><a><img><h2><h3>';
    $html = strip_tags($html, $allowedTags);
    $html = preg_replace('/\s+on[a-z]+\s*=\s*("|\').*?\1/i', '', $html) ?? $html;
    $html = preg_replace('/\s+style\s*=\s*("|\').*?\1/i', '', $html) ?? $html;

    if (preg_match_all('/<a\b[^>]*href=("|\')(.*?)\1/i', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $url = trim((string)($match[2] ?? ''));
            if (!is_safe_output_url($url, ['http', 'https', 'mailto'], false)) {
                $html = str_replace($match[0], '<a>', $html);
            }
        }
    }

    if (preg_match_all('/<img\b[^>]*src=("|\')(.*?)\1/i', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $url = trim((string)($match[2] ?? ''));
            if (!is_safe_output_url($url, ['http', 'https'], true)) {
                $html = str_replace($match[0], '', $html);
            }
        }
    }

    return $html;
}

function news_store_summary($summary)
{
    $mode = news_editor_mode();
    if ($mode === 'tinymce' || $mode === 'mixed') {
        return news_sanitize_html($summary);
    }

    return sanitize_bbcode_input((string)$summary, news_allowed_bbcode_tags(), 20000);
}

function news_summary_plain_text($summary)
{
    $mode = news_editor_mode();
    if ($mode === 'tinymce' || $mode === 'mixed') {
        return trim(html_entity_decode(strip_tags((string)$summary), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    return trim(html_entity_decode(strip_tags(bbcode_to_html((string)$summary, [
        'allowed_tags' => news_allowed_bbcode_tags(),
        'max_length' => 20000,
    ])), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

function news_summary_excerpt($summary, $limit = 180)
{
    $text = news_summary_plain_text($summary);
    if ($text === '') {
        return '';
    }

    $limit = max(20, (int)$limit);
    if (mb_strlen($text) <= $limit) {
        return $text;
    }

    return rtrim(mb_substr($text, 0, $limit - 1)) . '…';
}

function news_has_slug_column()
{
    static $hasSlug = null;
    if ($hasSlug !== null) {
        return $hasSlug;
    }

    $hasSlug = (bool)$GLOBALS['pdo']->query("SHOW COLUMNS FROM " . news_table_name() . " LIKE 'slug'")->fetchAll();
    return $hasSlug;
}

function news_select_columns()
{
    return news_has_slug_column()
        ? 'title, summary, created_at, slug'
        : 'title, summary, created_at';
}

function news_recent_items($limit = 20)
{
    $limit = max(1, (int)$limit);
    $stmt = $GLOBALS['pdo']->query("SELECT " . news_select_columns() . " FROM " . news_table_name() . " ORDER BY id DESC LIMIT " . $limit);
    return $stmt->fetchAll();
}

function news_create_item($title, $summary)
{
    $title = trim((string)$title);
    $summary = news_store_summary($summary);
    if ($title === '') {
        return false;
    }

    if (news_has_slug_column()) {
        $stmt = $GLOBALS['pdo']->prepare("INSERT INTO " . news_table_name() . " (title, summary, slug) VALUES (:t, :s, :slug)");
        $stmt->execute([
            ':t' => $title,
            ':s' => $summary,
            ':slug' => mb_strtolower(str_replace(' ', '-', $title)),
        ]);
    } else {
        $stmt = $GLOBALS['pdo']->prepare("INSERT INTO " . news_table_name() . " (title, summary) VALUES (:t, :s)");
        $stmt->execute([
            ':t' => $title,
            ':s' => $summary,
        ]);
    }

    return true;
}
