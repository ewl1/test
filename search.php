<?php
require_once __DIR__ . '/includes/bootstrap.php';

function search_like_term($query)
{
    $query = trim((string)$query);
    $query = strtr($query, [
        '!' => '!!',
        '%' => '!%',
        '_' => '!_',
    ]);

    return '%' . $query . '%';
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
          AND (title LIKE :title_term ESCAPE '!' OR url LIKE :url_term ESCAPE '!')
        ORDER BY parent_id IS NOT NULL, sort_order ASC, id ASC
        LIMIT 8
    ");
    $stmt->execute([
        ':title_term' => $term,
        ':url_term' => $term,
    ]);

    return $stmt->fetchAll();
}

function search_navigation_count(PDO $pdo, $term)
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM navigation_links
        WHERE is_active = 1
          AND (title LIKE :title_term ESCAPE '!' OR url LIKE :url_term ESCAPE '!')
    ");
    $stmt->execute([
        ':title_term' => $term,
        ':url_term' => $term,
    ]);

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
        WHERE m.message LIKE :message_term ESCAPE '!'
           OR COALESCE(u.username, '') LIKE :username_term ESCAPE '!'
        ORDER BY m.created_at DESC, m.id DESC
        LIMIT 8
    ");
    $stmt->execute([
        ':message_term' => $term,
        ':username_term' => $term,
    ]);

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
        WHERE m.message LIKE :message_term ESCAPE '!'
           OR COALESCE(u.username, '') LIKE :username_term ESCAPE '!'
    ");
    $stmt->execute([
        ':message_term' => $term,
        ':username_term' => $term,
    ]);

    return (int)$stmt->fetchColumn();
}

function search_member_results(PDO $pdo, $term)
{
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.avatar, u.signature, u.created_at, r.name AS role_name
        FROM users u
        LEFT JOIN roles r ON r.id = u.role_id
        WHERE u.is_active = 1
          AND u.status = 'active'
          AND (
              u.username LIKE :username_term ESCAPE '!'
              OR COALESCE(u.signature, '') LIKE :signature_term ESCAPE '!'
              OR COALESCE(r.name, '') LIKE :role_term ESCAPE '!'
          )
        ORDER BY u.username ASC, u.id ASC
        LIMIT 8
    ");
    $stmt->execute([
        ':username_term' => $term,
        ':signature_term' => $term,
        ':role_term' => $term,
    ]);

    return $stmt->fetchAll();
}

function search_member_count(PDO $pdo, $term)
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM users u
        LEFT JOIN roles r ON r.id = u.role_id
        WHERE u.is_active = 1
          AND u.status = 'active'
          AND (
              u.username LIKE :username_term ESCAPE '!'
              OR COALESCE(u.signature, '') LIKE :signature_term ESCAPE '!'
              OR COALESCE(r.name, '') LIKE :role_term ESCAPE '!'
          )
    ");
    $stmt->execute([
        ':username_term' => $term,
        ':signature_term' => $term,
        ':role_term' => $term,
    ]);

    return (int)$stmt->fetchColumn();
}

function search_profile_comment_results(PDO $pdo, $term)
{
    if (!profile_table_exists(profile_comment_table())) {
        return [];
    }

    $stmt = $pdo->prepare("
        SELECT c.id,
               c.profile_user_id,
               c.author_user_id,
               c.content,
               c.created_at,
               author.username AS author_username,
               profile_user.username AS profile_username
        FROM " . profile_comment_table() . " c
        LEFT JOIN users author ON author.id = c.author_user_id
        LEFT JOIN users profile_user ON profile_user.id = c.profile_user_id
        WHERE c.content LIKE :content_term ESCAPE '!'
           OR COALESCE(author.username, '') LIKE :author_term ESCAPE '!'
           OR COALESCE(profile_user.username, '') LIKE :profile_term ESCAPE '!'
        ORDER BY c.created_at DESC, c.id DESC
        LIMIT 8
    ");
    $stmt->execute([
        ':content_term' => $term,
        ':author_term' => $term,
        ':profile_term' => $term,
    ]);

    return $stmt->fetchAll();
}

function search_profile_comment_count(PDO $pdo, $term)
{
    if (!profile_table_exists(profile_comment_table())) {
        return 0;
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM " . profile_comment_table() . " c
        LEFT JOIN users author ON author.id = c.author_user_id
        LEFT JOIN users profile_user ON profile_user.id = c.profile_user_id
        WHERE c.content LIKE :content_term ESCAPE '!'
           OR COALESCE(author.username, '') LIKE :author_term ESCAPE '!'
           OR COALESCE(profile_user.username, '') LIKE :profile_term ESCAPE '!'
    ");
    $stmt->execute([
        ':content_term' => $term,
        ':author_term' => $term,
        ':profile_term' => $term,
    ]);

    return (int)$stmt->fetchColumn();
}

function search_forum_topic_results(PDO $pdo, $term)
{
    if (!profile_table_exists('infusion_forum_topics') || !profile_table_exists('infusion_forum_forums')) {
        return [];
    }

    $stmt = $pdo->prepare("
        SELECT t.id,
               t.user_id,
               t.title,
               t.content,
               t.created_at,
               t.last_post_at,
               f.title AS forum_title,
               u.username
        FROM infusion_forum_topics t
        INNER JOIN infusion_forum_forums f ON f.id = t.forum_id
        LEFT JOIN users u ON u.id = t.user_id
        WHERE t.title LIKE :title_term ESCAPE '!'
           OR t.content LIKE :content_term ESCAPE '!'
           OR COALESCE(u.username, '') LIKE :username_term ESCAPE '!'
        ORDER BY t.last_post_at DESC, t.id DESC
        LIMIT 8
    ");
    $stmt->execute([
        ':title_term' => $term,
        ':content_term' => $term,
        ':username_term' => $term,
    ]);

    return $stmt->fetchAll();
}

function search_forum_topic_count(PDO $pdo, $term)
{
    if (!profile_table_exists('infusion_forum_topics') || !profile_table_exists('infusion_forum_forums')) {
        return 0;
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM infusion_forum_topics t
        LEFT JOIN users u ON u.id = t.user_id
        WHERE t.title LIKE :title_term ESCAPE '!'
           OR t.content LIKE :content_term ESCAPE '!'
           OR COALESCE(u.username, '') LIKE :username_term ESCAPE '!'
    ");
    $stmt->execute([
        ':title_term' => $term,
        ':content_term' => $term,
        ':username_term' => $term,
    ]);

    return (int)$stmt->fetchColumn();
}

$query = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$searched = $query !== '';

$postResults = [];
$memberResults = [];
$forumTopicResults = [];
$profileCommentResults = [];
$navigationResults = [];
$shoutboxResults = [];

$postTotal = 0;
$memberTotal = 0;
$forumTopicTotal = 0;
$profileCommentTotal = 0;
$navigationTotal = 0;
$shoutboxTotal = 0;

$pager = paginate(0, $perPage, 1);

if ($searched) {
    $term = search_like_term($query);

    $countStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM posts
        WHERE status = 'published'
          AND (title LIKE :title_term ESCAPE '!' OR content LIKE :content_term ESCAPE '!')
    ");
    $countStmt->execute([
        ':title_term' => $term,
        ':content_term' => $term,
    ]);
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
          AND (title LIKE :title_term ESCAPE '!' OR content LIKE :content_term ESCAPE '!')
        ORDER BY created_at DESC, id DESC
        LIMIT {$perPage} OFFSET {$offset}
    ");
    $postStmt->execute([
        ':title_term' => $term,
        ':content_term' => $term,
    ]);
    $postResults = $postStmt->fetchAll();

    $memberResults = search_member_results($pdo, $term);
    $memberTotal = search_member_count($pdo, $term);

    $forumTopicResults = search_forum_topic_results($pdo, $term);
    $forumTopicTotal = search_forum_topic_count($pdo, $term);

    $profileCommentResults = search_profile_comment_results($pdo, $term);
    $profileCommentTotal = search_profile_comment_count($pdo, $term);

    $navigationResults = search_navigation_results($pdo, $term);
    $navigationTotal = search_navigation_count($pdo, $term);

    $shoutboxResults = search_shoutbox_results($pdo, $term);
    $shoutboxTotal = search_shoutbox_count($pdo, $term);
}

$overallTotal = $postTotal + $memberTotal + $forumTopicTotal + $profileCommentTotal + $navigationTotal + $shoutboxTotal;
include THEMES . setting('current_theme', CURRENT_THEME) . '/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card mb-4">
            <div class="card-body">
                <h1 class="h3 mb-3"><?= e(__('search.title')) ?></h1>
                <form method="get" action="<?= public_path('search.php') ?>" class="row g-2 align-items-end">
                    <div class="col-md-10">
                        <label class="form-label" for="search-query"><?= e(__('search.keyword')) ?></label>
                        <input class="form-control" id="search-query" name="q" type="search" maxlength="100" value="<?= e($query) ?>" placeholder="<?= e(__('search.placeholder')) ?>">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-primary" type="submit"><?= e(__('search.submit')) ?></button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($searched): ?>
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div class="text-secondary">
                    <?= e(__('search.results_summary', ['count' => (int)$overallTotal, 'query' => $query])) ?>
                </div>
                <div class="small text-secondary">
                    <?= e(__('search.breakdown', [
                        'posts' => (int)$postTotal,
                        'members' => (int)$memberTotal,
                        'forum' => (int)$forumTopicTotal,
                        'comments' => (int)$profileCommentTotal,
                        'navigation' => (int)$navigationTotal,
                        'shoutbox' => (int)$shoutboxTotal,
                    ])) ?>
                </div>
            </div>

            <div class="card mb-4 search-section-card">
                <div class="card-header"><?= e(__('search.section.content')) ?></div>
                <div class="card-body">
                    <?php if (!$postResults): ?>
                        <div class="text-secondary"><?= e(__('search.empty.posts')) ?></div>
                    <?php else: ?>
                        <div class="vstack gap-3">
                            <?php foreach ($postResults as $result): ?>
                                <article class="search-result-card border-bottom pb-3">
                                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                        <div>
                                            <span class="badge text-bg-primary search-type-badge mb-2"><?= e(__('search.type.post')) ?></span>
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
                <div class="card-header"><?= e(__('search.section.members')) ?></div>
                <div class="card-body">
                    <?php if (!$memberResults): ?>
                        <div class="text-secondary"><?= e(__('search.empty.members')) ?></div>
                    <?php else: ?>
                        <div class="vstack gap-3">
                            <?php foreach ($memberResults as $result): ?>
                                <article class="search-result-card border-bottom pb-3">
                                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                        <div class="d-flex align-items-start gap-3">
                                            <img src="<?= escape_url(user_avatar_url($result)) ?>" alt="" class="member-panel-avatar">
                                            <div>
                                                <span class="badge text-bg-info search-type-badge mb-2"><?= e(__('search.type.member')) ?></span>
                                                <h3 class="h6 mb-1">
                                                    <a class="text-decoration-none" href="<?= user_profile_url((int)$result['id']) ?>"><?= e($result['username']) ?></a>
                                                </h3>
                                                <div class="small text-secondary"><?= e($result['role_name'] ?? __('member.none')) ?></div>
                                            </div>
                                        </div>
                                        <div class="small text-secondary"><?= e(format_dt($result['created_at'])) ?></div>
                                    </div>
                                    <?php if (!empty($result['signature'])): ?>
                                        <p class="mb-0"><?= e(search_excerpt($result['signature'], 140)) ?></p>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-4 search-section-card">
                <div class="card-header"><?= e(__('search.section.forum')) ?></div>
                <div class="card-body">
                    <?php if (!$forumTopicResults): ?>
                        <div class="text-secondary"><?= e(__('search.empty.forum')) ?></div>
                    <?php else: ?>
                        <div class="vstack gap-3">
                            <?php foreach ($forumTopicResults as $result): ?>
                                <article class="search-result-card border-bottom pb-3">
                                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                        <div>
                                            <span class="badge text-bg-warning search-type-badge mb-2"><?= e(__('search.type.forum')) ?></span>
                                            <h3 class="h6 mb-1">
                                                <a class="text-decoration-none" href="<?= public_path('forum-topic.php?id=' . (int)$result['id']) ?>"><?= e($result['title']) ?></a>
                                            </h3>
                                            <div class="small text-secondary">
                                                <?= e($result['forum_title'] ?? 'Forumas') ?>
                                                <?php if (!empty($result['username'])): ?>
                                                    · <?= e($result['username']) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="small text-secondary"><?= e(format_dt($result['last_post_at'] ?: $result['created_at'])) ?></div>
                                    </div>
                                    <p class="mb-0"><?= e(search_excerpt($result['content'])) ?></p>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-4 search-section-card">
                <div class="card-header"><?= e(__('search.section.comments')) ?></div>
                <div class="card-body">
                    <?php if (!$profileCommentResults): ?>
                        <div class="text-secondary"><?= e(__('search.empty.comments')) ?></div>
                    <?php else: ?>
                        <div class="vstack gap-3">
                            <?php foreach ($profileCommentResults as $result): ?>
                                <article class="search-result-card border-bottom pb-3">
                                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                        <div>
                                            <span class="badge text-bg-secondary search-type-badge mb-2"><?= e(__('search.type.comment')) ?></span>
                                            <div class="fw-semibold mb-1">
                                                <a class="text-decoration-none" href="<?= user_profile_url((int)$result['author_user_id']) ?>"><?= e($result['author_username'] ?? __('member.none')) ?></a>
                                                <span class="text-secondary fw-normal">apie</span>
                                                <a class="text-decoration-none" href="<?= profile_comment_url((int)$result['profile_user_id'], (int)$result['id']) ?>"><?= e($result['profile_username'] ?? 'profilį') ?></a>
                                            </div>
                                        </div>
                                        <div class="small text-secondary"><?= e(format_dt($result['created_at'])) ?></div>
                                    </div>
                                    <p class="mb-0"><?= e(search_excerpt(profile_comment_excerpt($result['content'], 180), 180)) ?></p>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-4 search-section-card">
                <div class="card-header"><?= e(__('search.section.navigation')) ?></div>
                <div class="card-body">
                    <?php if (!$navigationResults): ?>
                        <div class="text-secondary"><?= e(__('search.empty.navigation')) ?></div>
                    <?php else: ?>
                        <div class="vstack gap-3">
                            <?php foreach ($navigationResults as $result): ?>
                                <article class="search-result-card border-bottom pb-3">
                                    <span class="badge text-bg-secondary search-type-badge mb-2"><?= e(__('search.type.navigation')) ?></span>
                                    <h3 class="h6 mb-2"><a class="text-decoration-none" href="<?= escape_url($result['url']) ?>"><?= e($result['title']) ?></a></h3>
                                    <div class="small text-secondary"><?= e($result['url']) ?></div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card search-section-card">
                <div class="card-header"><?= e(__('search.section.shoutbox')) ?></div>
                <div class="card-body">
                    <?php if (!$shoutboxResults): ?>
                        <div class="text-secondary"><?= e(__('search.empty.shoutbox')) ?></div>
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
                                            <span class="badge text-bg-warning search-type-badge mb-2"><?= e(__('search.type.shoutbox')) ?></span>
                                            <div class="fw-semibold">
                                                <?php if (!empty($result['user_id'])): ?>
                                                    <a class="text-decoration-none" href="<?= user_profile_url((int)$result['user_id']) ?>"><?= e($result['username'] ?? __('member.none')) ?></a>
                                                <?php else: ?>
                                                    <?= e($result['username'] ?? __('member.guest')) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="small text-secondary"><?= e(format_dt($result['created_at'])) ?></div>
                                    </div>
                                    <p class="mb-2"><?= e(search_excerpt($result['message'])) ?></p>
                                    <a class="small text-decoration-none" href="<?= $resultUrl ?>"><?= e(__('search.open_message')) ?></a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-secondary">
                    <?= e(__('search.empty.prompt')) ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include THEMES . setting('current_theme', CURRENT_THEME) . '/footer.php'; ?>
