<?php

function sitemap_path(): string
{
    return BASEDIR . 'sitemap.xml';
}

function sitemap_base_url(): string
{
    return rtrim((string)(defined('SITE_URL') ? SITE_URL : ''), '/');
}

function sitemap_table_exists(string $table): bool
{
    try {
        $stmt = $GLOBALS['pdo']->prepare('SHOW TABLES LIKE :table');
        $stmt->execute([':table' => $table]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function sitemap_collect_entries(): array
{
    $entries = [];
    $base = sitemap_base_url();

    $add = static function (string $path, ?string $lastmod = null, string $changefreq = 'weekly', string $priority = '0.5') use (&$entries, $base): void {
        $entries[] = [
            'loc' => $base . '/' . ltrim($path, '/'),
            'lastmod' => $lastmod,
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    };

    $staticPages = [
        ['index.php', date('c'), 'daily', '1.0'],
        ['forum.php', null, 'daily', '0.9'],
        ['downloads.php', null, 'weekly', '0.8'],
        ['shoutbox.php', null, 'daily', '0.7'],
        ['members.php', null, 'weekly', '0.7'],
        ['search.php', null, 'weekly', '0.4'],
    ];

    foreach ($staticPages as [$path, $lastmod, $changefreq, $priority]) {
        if (is_file(BASEDIR . $path)) {
            $add($path, $lastmod, $changefreq, $priority);
        }
    }

    if (sitemap_table_exists('users')) {
        try {
            $stmt = $GLOBALS['pdo']->query("
                SELECT id, COALESCE(last_visit_at, last_login_at, created_at) AS updated_at
                FROM users
                WHERE is_active = 1 AND status = 'active'
                ORDER BY id DESC
                LIMIT 500
            ");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $add('user.php?id=' . (int)$row['id'], !empty($row['updated_at']) ? date('c', strtotime((string)$row['updated_at'])) : null, 'weekly', '0.5');
            }
        } catch (Throwable $e) {
        }
    }

    if (sitemap_table_exists('infusion_forum_forums')) {
        try {
            $stmt = $GLOBALS['pdo']->query("
                SELECT id, COALESCE(updated_at, created_at) AS updated_at
                FROM infusion_forum_forums
                WHERE is_active = 1
                ORDER BY id DESC
            ");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $add('forum-view.php?id=' . (int)$row['id'], !empty($row['updated_at']) ? date('c', strtotime((string)$row['updated_at'])) : null, 'weekly', '0.7');
            }
        } catch (Throwable $e) {
        }
    }

    if (sitemap_table_exists('infusion_forum_topics')) {
        try {
            $stmt = $GLOBALS['pdo']->query("
                SELECT id, COALESCE(updated_at, created_at) AS updated_at
                FROM infusion_forum_topics
                ORDER BY id DESC
                LIMIT 1000
            ");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $add('forum-topic.php?id=' . (int)$row['id'], !empty($row['updated_at']) ? date('c', strtotime((string)$row['updated_at'])) : null, 'weekly', '0.8');
            }
        } catch (Throwable $e) {
        }
    }

    return $entries;
}

function sitemap_render_xml(array $entries): string
{
    $lines = [
        '<?xml version="1.0" encoding="UTF-8"?>',
        '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
    ];

    foreach ($entries as $entry) {
        $lines[] = '  <url>';
        $lines[] = '    <loc>' . e($entry['loc']) . '</loc>';
        if (!empty($entry['lastmod'])) {
            $lines[] = '    <lastmod>' . e($entry['lastmod']) . '</lastmod>';
        }
        if (!empty($entry['changefreq'])) {
            $lines[] = '    <changefreq>' . e($entry['changefreq']) . '</changefreq>';
        }
        if (!empty($entry['priority'])) {
            $lines[] = '    <priority>' . e($entry['priority']) . '</priority>';
        }
        $lines[] = '  </url>';
    }

    $lines[] = '</urlset>';

    return implode("\n", $lines) . "\n";
}

function sitemap_rebuild(): array
{
    $entries = sitemap_collect_entries();
    $xml = sitemap_render_xml($entries);
    $path = sitemap_path();

    $bytes = file_put_contents($path, $xml);
    if ($bytes === false) {
        return [
            'ok' => false,
            'path' => $path,
            'entries' => count($entries),
            'bytes' => 0,
        ];
    }

    return [
        'ok' => true,
        'path' => $path,
        'entries' => count($entries),
        'bytes' => (int)$bytes,
    ];
}
