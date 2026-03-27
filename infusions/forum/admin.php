<?php
require_permission('forum.admin');

$message = null;
$messageType = 'success';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();
    $action = (string)($_POST['forum_admin_action'] ?? '');

    if ($action === 'create_category') {
        [$ok, $message] = forum_create_category(
            $_POST['title'] ?? '',
            $_POST['description'] ?? '',
            (int)($_POST['sort_order'] ?? 0)
        );
        $messageType = $ok ? 'success' : 'danger';
    } elseif ($action === 'create_forum') {
        [$ok, $message] = forum_create_forum(
            (int)($_POST['category_id'] ?? 0),
            (int)($_POST['parent_id'] ?? 0),
            $_POST['title'] ?? '',
            $_POST['description'] ?? '',
            (int)($_POST['sort_order'] ?? 0)
        );
        $messageType = $ok ? 'success' : 'danger';
    }
}

$categories = forum_fetch_categories();
$forumsByCategory = forum_get_index_data();
$forumOptions = forum_get_forum_options();
$topicCount = (int)$GLOBALS['pdo']->query('SELECT COUNT(*) FROM ' . forum_table_topics())->fetchColumn();
$postCount = (int)$GLOBALS['pdo']->query('SELECT COUNT(*) FROM ' . forum_table_posts())->fetchColumn();
?>
<div class="d-flex justify-content-between align-items-center mb-3 gap-3 flex-wrap">
    <div>
        <h2 class="h4 mb-1">Forumo valdymas</h2>
        <p class="text-secondary mb-0">Kurti kategorijas, forumus ir subforumus, kurie iskart matomi viesame puslapyje.</p>
    </div>
    <a class="btn btn-outline-primary" href="<?= forum_index_url() ?>" target="_blank" rel="noopener">Atidaryti foruma</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= e($messageType) ?>"><?= e($message) ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="small text-secondary mb-2">Kategorijos</div>
                <div class="display-6 mb-0"><?= count($categories) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="small text-secondary mb-2">Temos</div>
                <div class="display-6 mb-0"><?= (int)$topicCount ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="small text-secondary mb-2">Atsakymai</div>
                <div class="display-6 mb-0"><?= (int)$postCount ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-5">
        <div class="card mb-4">
            <div class="card-header">Nauja kategorija</div>
            <div class="card-body">
                <form method="post" class="row g-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="forum_admin_action" value="create_category">
                    <div class="col-12">
                        <label class="form-label">Pavadinimas</label>
                        <input class="form-control" name="title" maxlength="190" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Aprasymas</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Rikiavimas</label>
                        <input class="form-control" type="number" name="sort_order" value="1">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary">Sukurti kategorija</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Naujas forumas arba subforumas</div>
            <div class="card-body">
                <form method="post" class="row g-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="forum_admin_action" value="create_forum">
                    <div class="col-md-6">
                        <label class="form-label">Kategorija</label>
                        <select class="form-select" name="category_id" required>
                            <option value="">Pasirinkite</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int)$category['id'] ?>"><?= e($category['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Parent forumas</label>
                        <select class="form-select" name="parent_id">
                            <option value="0">Be parent (paprastas forumas)</option>
                            <?php foreach ($forumOptions as $forum): ?>
                                <?php if ((int)$forum['parent_id'] === 0): ?>
                                    <option value="<?= (int)$forum['id'] ?>"><?= e($forum['category_title'] . ' / ' . $forum['title']) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Pavadinimas</label>
                        <input class="form-control" name="title" maxlength="190" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Aprasymas</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Rikiavimas</label>
                        <input class="form-control" type="number" name="sort_order" value="1">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary">Sukurti foruma</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header">Esama struktura</div>
            <div class="card-body">
                <?php if (!$forumsByCategory): ?>
                    <div class="text-secondary">Forumo struktura dar nesukurta.</div>
                <?php else: ?>
                    <div class="vstack gap-4">
                        <?php foreach ($forumsByCategory as $category): ?>
                            <section>
                                <div class="d-flex justify-content-between align-items-center mb-2 gap-3 flex-wrap">
                                    <div>
                                        <h3 class="h6 mb-1"><?= e($category['title']) ?></h3>
                                        <?php if (!empty($category['description'])): ?>
                                            <div class="small text-secondary"><?= e($category['description']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge text-bg-secondary"><?= count($category['forums']) ?> forumai</span>
                                </div>

                                <?php if (!$category['forums']): ?>
                                    <div class="small text-secondary">Kategorijoje forumu dar nera.</div>
                                <?php else: ?>
                                    <div class="list-group">
                                        <?php foreach ($category['forums'] as $forum): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-start gap-3">
                                                    <div>
                                                        <div class="fw-semibold">
                                                            <a class="text-decoration-none" href="<?= forum_forum_url((int)$forum['id']) ?>" target="_blank" rel="noopener"><?= e($forum['title']) ?></a>
                                                        </div>
                                                        <?php if (!empty($forum['description'])): ?>
                                                            <div class="small text-secondary"><?= e($forum['description']) ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span class="badge text-bg-light"><?= (int)$forum['topics_count'] ?> temos</span>
                                                </div>

                                                <?php if (!empty($forum['subforums'])): ?>
                                                    <div class="mt-2 ps-3 border-start">
                                                        <?php foreach ($forum['subforums'] as $subforum): ?>
                                                            <div class="py-1">
                                                                <div class="fw-semibold">
                                                                    <a class="text-decoration-none" href="<?= forum_forum_url((int)$subforum['id']) ?>" target="_blank" rel="noopener"><?= e($subforum['title']) ?></a>
                                                                </div>
                                                                <?php if (!empty($subforum['description'])): ?>
                                                                    <div class="small text-secondary"><?= e($subforum['description']) ?></div>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </section>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
