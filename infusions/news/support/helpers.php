<?php

function news_table_name()
{
    return 'infusion_news';
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
    $summary = trim((string)$summary);
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
