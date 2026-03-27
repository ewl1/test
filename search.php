<?php
require_once __DIR__ . '/includes/bootstrap.php';

function search_like_term($query)
{
    return '%' . trim((string)$query) . '%';
}

function search_excerpt($text, $length = 220)
{
    $plain = trim(preg_replace('/\s+/u', ' ', strip_tags((string)$text)));
    if ($plain === '') {
        return '';
    }

    if (mb_strlen($plain) <= $length) {
        return $plain;
    }

    return rtrim(mb_substr($plain, 0, $length - 1)) . '...';
}

function search_navigation_results(PDO $pdo, $term)
{
    $stmt = $pdo->prepare("
        SELECT id, title, url
        FROM navigation_links
        WHERE is_active = 1
          AND (title LIKE :term OR url LIKE :term)
        ORDER BY parent_id IS NOT NULL, sort_order ASC, id ASC
        LIMIT 8
    ");
    $stmt->execute([':term' => $term]);
    return $stmt->fetchAll();
}

function search_navigation_count(PDO $pdo, $term)
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM navigation_links
        WHERE is_active = 1
          AND (title LIKE :term OR url LIKE :term)
    ");
    $stmt->execute([':term' => $term]);
    return (int)$stmt->fetchColumn();
}

function search_shoutbox_results(PDO $pdo, $term)
{
    if (!profile_table_exists('infusion_shoutbox_messages')) {
        return [];
    }

    $stmt = $pdo->prepare("
        SELECT m.id, m.user_id, m.message, m.created_at, u.username
        FROM infusion_shoutbox_messages m
        LEFT JOIN users u ON u.id = m.user_id
        WHERE m.message LIKE :term OR COALESCE(u.username, '') LIKE :term
        ORDER BY m.created_at DESC, m.id DESC
        LIMIT 8
    ");
    $stmt->execute([':term' => $term]);
    return $stmt->fetchAll();
}

function search_shoutbox_count(PDO $pdo, $term)
{
    if (!profile_table_exists('infusion_shoutbox_messages')) {
        return 0;
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM infusion_shoutbox_messages m
        LEFT JOIN users u ON u.id = m.user_id
        WHERE m.message LIKE :term OR COALESCE(u.username, '') LIKE :term
    ");
    $stmt->execute([':term' => $term]);
    return (int)$stmt->fetchColumn();
}

$query = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$searched = $query !== '';

$postResults = [];
$navigationResults = [];
$shoutboxResults = [];
$postTotal = 0;
$navigationTotal = 0;
$shoutboxTotal = 0;
$pager = paginate(0, $perPage, 1);

if ($searched) {
    $term = search_like_term($query);

    $countStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM posts
        WHERE status = 'published'
          AND (title LIKE :term OR content LIKE :term)
    ");
    $countStmt->execute([':term' => $term]);
    $postTotal = (int)$countStmt->fetchColumn();

    $pager = paginate($postTotal, $perPage, $page);
    if (($pager['pages'] ?? 0) > 0 && $page > (int)$pager['pages']) {
        $page = (int)$pager['pages'];
        $pager = paginate($postTotal, $perPage, $page);
    }

    $offset = (int)$pager['offset'];
    $postStmt = $pdo->prepare("
        SELECT id, user_id, title, content, created_at
        FROM posts
        WHERE status = 'published'
          AND (title LIKE :term OR content LIKE :term)
        ORDER BY created_at DESC, id DESC
        LIMIT {$perPage} OFFSET {$offset}
    ");
    $postStmt->execute([':term' => $term]);
    $postResults = $postStmt->fetchAll();

    $navigationResults = search_navigation_results($pdo, $term);
    $navigationTotal = search_navigation_count($pdo, $term);

    $shoutboxResults = search_shoutbox_results($pdo, $term);
    $shoutboxTotal = search_shoutbox_count($pdo, $term);
}

$overallTotal = $postTotal + $navigationTotal + $shoutboxTotal;
include THEMES . setting('current_theme', CURRENT_THEME) . '/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card mb-4">
            <div class="card-body">
                <h1 class="h3 mb-3">Paieška</h1>
                <form method="get" action="<?= public_path('search.php') ?>" class="row g-2 align-items-end">
                    <div class="col-md-10">
                        <label class="form-label" for="search-query">Rakto žodis</label>
                        <input class="form-control" id="search-query" name="q" type="search" maxlength="100" value="<?= e($query) ?>" placeholder="Ieškoti įrašuose, navigacijoje ir šaukykloje">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-primary" type="submit">Ieškoti</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($searched): ?>
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div class="text-secondary">
                    Rasta iš viso: <strong><?= (int)$overallTotal ?></strong> pagal užklausą "<?= e($query) ?>"
                </div>
                <div class="small text-secondary">
                    Įrašai: <?= (int)$postTotal ?> · Navigacija: <?= (int)$navigationTotal ?> · Šaukykla: <?= (int)$shoutboxTotal ?>
                </div>
            </div>

            <div class="card mb-4 search-section-card">
                <div class="card-header">Turinys</div>
                <div class="card-body">
                    <?php if (!$postResults): ?>
                        <div class="text-secondary">Įrašų pagal šią užklausą nerasta.</div>
                    <?php else: ?>
                        <div class="vstack gap-3">
                            <?php foreach ($postResults as $result): ?>
                                <article class="search-result-card border-bottom pb-3">
                                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                        <div>
                                            <span class="badge text-bg-primary search-type-badge mb-2">Įrašas</span>
                                            <h2 class="h5 mb-2">
                                                <a class="text-decoration-none" href="<?= public_path('post.php?id=' . (int)$result['id']) ?>">
                                                    <?= e($result['title'] ?: 'Be pavadinimo') ?>
                                                </a>
                                            </h2>
                                        </div>
                                        <div class="small text-secondary"><?= e(format_dt($result['created_at'])) ?></div>
                                    </div>
                                    <p class="mb-0"><?= e(search_excerpt($result['content'])) ?></p>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <?php
                        $pagination = render_pagination(public_path('search.php?q=' . rawurlencode($query)), $pager);
                        if ($pagination !== ''):
                        ?>
                            <div class="mt-4"><?= $pagination ?></div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-4 search-section-card">
                <div class="card-header">Navigacija</div>
                <div class="card-body">
                    <?php if (!$navigationResults): ?>
                        <div class="text-secondary">Navigacijos nuorodų nerasta.</div>
                    <?php else: ?>
                        <div class="vstack gap-3">
                            <?php foreach ($navigationResults as $result): ?>
                                <article class="search-result-card border-bottom pb-3">
                                    <span class="badge text-bg-secondary search-type-badge mb-2">Navigacija</span>
                                    <h3 class="h6 mb-2"><a class="text-decoration-none" href="<?= escape_url($result['url']) ?>"><?= e($result['title']) ?></a></h3>
                                    <div class="small text-secondary"><?= e($result['url']) ?></div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card search-section-card">
                <div class="card-header">Šaukykla</div>
                <div class="card-body">
                    <?php if (!$shoutboxResults): ?>
                        <div class="text-secondary">Šaukyklos žinučių nerasta.</div>
                    <?php else: ?>
                        <div class="vstack gap-3">
                            <?php foreach ($shoutboxResults as $result): ?>
                                <?php
                                $resultUrl = function_exists('shoutbox_message_url')
                                    ? shoutbox_message_url((int)$result['id'])
                                    : public_path('shoutbox.php');
                                ?>
                                <article class="search-result-card border-bottom pb-3">
                                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                        <div>
                                            <span class="badge text-bg-warning search-type-badge mb-2">Šaukykla</span>
                                            <div class="fw-semibold">
                                                <?php if (!empty($result['user_id'])): ?>
                                                    <a class="text-decoration-none" href="<?= user_profile_url((int)$result['user_id']) ?>"><?= e($result['username'] ?? 'Narys') ?></a>
                                                <?php else: ?>
                                                    <?= e($result['username'] ?? 'Svečias') ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="small text-secondary"><?= e(format_dt($result['created_at'])) ?></div>
                                    </div>
                                    <p class="mb-2"><?= e(search_excerpt($result['message'])) ?></p>
                                    <a class="small text-decoration-none" href="<?= $resultUrl ?>">Atidaryti žinutę</a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-secondary">
                    Įveskite raktažodį ir ieškokite įrašuose, meniu nuorodose bei šaukykloje.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include THEMES . setting('current_theme', CURRENT_THEME) . '/footer.php'; ?>
