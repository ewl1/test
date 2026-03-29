<?php
require_permission('forum.admin');
forum_ensure_extended_schema();

$message = null;
$messageType = 'success';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();
    $action = (string)($_POST['forum_admin_action'] ?? '');

    if ($action === 'create_node') {
        [$ok, $message] = forum_admin_create_node($_POST, $_FILES);
        $messageType = $ok ? 'success' : 'danger';
    } elseif ($action === 'save_settings') {
        [$ok, $message] = forum_save_settings($_POST);
        $messageType = $ok ? 'success' : 'danger';
    } elseif ($action === 'save_rank') {
        [$ok, $message] = forum_save_rank($_POST, $_FILES['rank_image'] ?? []);
        $messageType = $ok ? 'success' : 'danger';
    } elseif ($action === 'delete_rank') {
        [$ok, $message] = forum_delete_rank((int)($_POST['id'] ?? 0));
        $messageType = $ok ? 'success' : 'danger';
    } elseif ($action === 'save_mood') {
        [$ok, $message] = forum_save_mood($_POST);
        $messageType = $ok ? 'success' : 'danger';
    } elseif ($action === 'delete_mood') {
        [$ok, $message] = forum_delete_mood((int)($_POST['id'] ?? 0));
        $messageType = $ok ? 'success' : 'danger';
    }
}

$categories = forum_fetch_categories();
$forumsByCategory = forum_get_index_data();
$forumOptions = forum_get_forum_options();
$topicCount = (int)$GLOBALS['pdo']->query('SELECT COUNT(*) FROM ' . forum_table_topics())->fetchColumn();
$postCount = (int)$GLOBALS['pdo']->query('SELECT COUNT(*) FROM ' . forum_table_posts())->fetchColumn();
$ranks = forum_get_ranks(false);
$moods = forum_get_moods(false);

$editRankId = (int)($_GET['edit_rank'] ?? 0);
$editingRank = null;
foreach ($ranks as $rankRow) {
    if ((int)$rankRow['id'] === $editRankId) {
        $editingRank = $rankRow;
        break;
    }
}

$editMoodId = (int)($_GET['edit_mood'] ?? 0);
$editingMood = null;
foreach ($moods as $moodRow) {
    if ((int)$moodRow['id'] === $editMoodId) {
        $editingMood = $moodRow;
        break;
    }
}
?>
<div class="d-flex justify-content-between align-items-center mb-3 gap-3 flex-wrap">
    <div>
        <h2 class="h4 mb-1">Forumo valdymas</h2>
        <p class="text-secondary mb-0">Čia valdote forumo struktūrą, nustatymus, rangus, nuotaikas ir priedų elgseną.</p>
    </div>
    <a class="btn btn-outline-primary" href="<?= forum_index_url() ?>" target="_blank" rel="noopener">Atidaryti forumą</a>
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
                <div class="display-6 mb-0"><?= $topicCount ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="small text-secondary mb-2">Atsakymai</div>
                <div class="display-6 mb-0"><?= $postCount ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xxl-7">
        <div class="card mb-4">
            <div class="card-header">Forumo kūrimas</div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data" class="row g-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="forum_admin_action" value="create_node">

                    <div class="col-md-4">
                        <label class="form-label">Forumo tipas</label>
                        <select class="form-select" name="node_type">
                            <option value="category">Kategorija</option>
                            <option value="forum" selected>Forumas</option>
                            <option value="help">Pagalba ir atsakymai</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Forumas priklauso</label>
                        <select class="form-select" name="category_id">
                            <option value="">Pasirinkite kategoriją</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int)$category['id'] ?>"><?= e($category['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Pagrindinis / tėvinis forumas</label>
                        <select class="form-select" name="parent_id">
                            <option value="0">Pagrindinis forumas</option>
                            <?php foreach ($forumOptions as $forumOption): ?>
                                <?php if ((int)$forumOption['parent_id'] === 0): ?>
                                    <option value="<?= (int)$forumOption['id'] ?>"><?= e($forumOption['category_title'] . ' / ' . $forumOption['title']) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Pavadinimas</label>
                        <input class="form-control" name="title" maxlength="190" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Forumo alias</label>
                        <input class="form-control" name="slug" maxlength="190" placeholder="mano-forumas">
                    </div>

                    <div class="col-12">
                        <div class="d-flex flex-wrap gap-2 mb-2"><?php forum_render_editor_toolbar('forum-admin-description'); ?></div>
                        <div class="d-flex flex-wrap gap-2 mb-2"><?php forum_render_smileys('forum-admin-description'); ?></div>
                        <label class="form-label">Forumo aprašymas</label>
                        <textarea class="form-control" id="forum-admin-description" name="description" rows="4"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Forumo raktažodžiai</label>
                        <textarea class="form-control" name="keywords" rows="4" placeholder="php&#10;cms&#10;pagalba"></textarea>
                        <div class="form-text">Po kiekvieno raktažodžio paspauskite Enter.</div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex flex-wrap gap-2 mb-2"><?php forum_render_editor_toolbar('forum-admin-rules'); ?></div>
                        <div class="d-flex flex-wrap gap-2 mb-2"><?php forum_render_smileys('forum-admin-rules'); ?></div>
                        <label class="form-label">Forumo taisyklės ir perspėjimai</label>
                        <textarea class="form-control" id="forum-admin-rules" name="rules_content" rows="4"></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Forumo ikona</label>
                        <input class="form-control" name="icon_class" placeholder="fa-solid fa-comments">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Forumo tvarka</label>
                        <input class="form-control" type="number" name="sort_order" value="1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kopijuoti leidimų nustatymus</label>
                        <select class="form-select" name="copy_settings_from">
                            <option value="0">Nekopijuoti</option>
                            <?php foreach ($forumOptions as $forumOption): ?>
                                <option value="<?= (int)$forumOption['id'] ?>"><?= e($forumOption['category_title'] . ' / ' . $forumOption['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Pradinė direktorija</label>
                        <select class="form-select" name="image_source">
                            <option value="local">Local server</option>
                            <option value="url">URL</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Paveikslėlio nuoroda</label>
                        <input class="form-control" name="image_path" placeholder="/images/forum/forumas.jpg arba https://...">
                        <div class="form-text">Jeigu paveiksliuko failas yra įkeltas, ši nuoroda nebus naudojama.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Forumo nuotrauka</label>
                        <input class="form-control" type="file" name="forum_image" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">
                    </div>

                    <div class="col-md-6 col-xl-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_locked" id="forum-is-locked" value="1">
                            <label class="form-check-label" for="forum-is-locked">Užrakinti šį forumą</label>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="show_participants" id="forum-show-participants" value="1" checked>
                            <label class="form-check-label" for="forum-show-participants">Rodyti dalyvaujančius narius</label>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="enable_quick_reply" id="forum-quick-reply" value="1" checked>
                            <label class="form-check-label" for="forum-quick-reply">Įjungti greitą atsakymą</label>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="enable_post_merge" id="forum-post-merge" value="1">
                            <label class="form-check-label" for="forum-post-merge">Įjungti pranešimų sujungimą</label>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="allow_attachments" id="forum-attachments" value="1">
                            <label class="form-check-label" for="forum-attachments">Leisti prisegti failus</label>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="enable_polls" id="forum-polls" value="1">
                            <label class="form-check-label" for="forum-polls">Įjungti apklausas</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <button class="btn btn-primary">Sukurti forumo įrašą</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Esama struktūra</div>
            <div class="card-body">
                <?php if (!$forumsByCategory): ?>
                    <div class="text-secondary">Forumo struktūra dar nesukurta.</div>
                <?php else: ?>
                    <div class="vstack gap-4">
                        <?php foreach ($forumsByCategory as $category): ?>
                            <section>
                                <div class="d-flex justify-content-between align-items-center mb-2 gap-3 flex-wrap">
                                    <div>
                                        <h3 class="h6 mb-1"><?= e($category['title']) ?></h3>
                                        <?php if (!empty($category['description_html'])): ?>
                                            <div class="small text-secondary"><?= $category['description_html'] ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge text-bg-secondary"><?= count($category['forums']) ?> forumai</span>
                                </div>
                                <div class="list-group">
                                    <?php foreach ($category['forums'] as $forum): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                                <div>
                                                    <div class="fw-semibold d-flex align-items-center gap-2 flex-wrap">
                                                        <span><i class="<?= e($forum['icon_class'] ?: forum_default_icon_for_type($forum['forum_type'] ?? 'forum')) ?>"></i></span>
                                                        <a class="text-decoration-none" href="<?= forum_forum_url((int)$forum['id']) ?>" target="_blank" rel="noopener"><?= e($forum['title']) ?></a>
                                                        <?php if (($forum['forum_type'] ?? 'forum') === 'help'): ?>
                                                            <span class="badge text-bg-info">Pagalba</span>
                                                        <?php endif; ?>
                                                        <?php if (!empty($forum['is_locked'])): ?>
                                                            <span class="badge text-bg-dark">Užrakintas</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if (!empty($forum['description_html'])): ?>
                                                        <div class="small text-secondary mt-1"><?= $forum['description_html'] ?></div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($forum['keywords_list'])): ?>
                                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                                            <?php foreach ($forum['keywords_list'] as $keyword): ?>
                                                                <span class="badge text-bg-light"><?= e($keyword) ?></span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="badge text-bg-light"><?= (int)$forum['topics_count'] ?> temos</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-xxl-5">
        <div class="card mb-4">
            <div class="card-header">Forumo nustatymai</div>
            <div class="card-body">
                <form method="post" class="row g-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="forum_admin_action" value="save_settings">

                    <div class="col-12"><h3 class="h6 mb-3">General Forum Settings</h3></div>
                    <div class="col-md-6">
                        <label class="form-label">Temų per puslapį</label>
                        <input class="form-control" type="number" name="threads_per_page" value="<?= e(forum_setting('threads_per_page', '12')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Atsakymų per puslapį</label>
                        <input class="form-control" type="number" name="posts_per_page" value="<?= e(forum_setting('posts_per_page', '10')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Naujausių temų kiekis</label>
                        <input class="form-control" type="number" name="recent_threads_limit" value="<?= e(forum_setting('recent_threads_limit', '5')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Populiarios temos laikotarpis (dienomis)</label>
                        <input class="form-control" type="number" name="popular_thread_days" value="<?= e(forum_setting('popular_thread_days', '14')) ?>">
                    </div>

                    <div class="col-12 mt-2"><h3 class="h6 mb-3">General Display Settings</h3></div>
                    <div class="col-md-6">
                        <label class="form-label">Forumo paveikslėlio stilius</label>
                        <select class="form-select" name="picture_style">
                            <option value="image" <?= forum_picture_style() === 'image' ? 'selected' : '' ?>>Paveikslėlis</option>
                            <option value="icon" <?= forum_picture_style() === 'icon' ? 'selected' : '' ?>>Ikona</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Forumo rango stilius</label>
                        <select class="form-select" name="rank_style">
                            <option value="label" <?= forum_rank_style() === 'label' ? 'selected' : '' ?>>Žyma</option>
                            <option value="image" <?= forum_rank_style() === 'image' ? 'selected' : '' ?>>Paveikslėlis</option>
                        </select>
                    </div>

                    <div class="col-12 mt-2"><h3 class="h6 mb-3">Notifications ir forumo reputacija</h3></div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="thread_notification" id="thread-notification" value="1" <?= forum_setting('thread_notification', '0') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="thread-notification">Temų pranešimai</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="show_reputation" id="show-reputation" value="1" <?= forum_setting('show_reputation', '1') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="show-reputation">Rodyti reputaciją</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="enable_ranks" id="enable-ranks" value="1" <?= forum_setting('enable_ranks', '1') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="enable-ranks">Įjungti forumo rangus</label>
                        </div>
                    </div>

                    <div class="col-12 mt-2"><h3 class="h6 mb-3">Post File & Image Attachments</h3></div>
                    <div class="col-md-6">
                        <label class="form-label">Maksimalus nuotraukos dydis (KB)</label>
                        <input class="form-control" type="number" name="max_photo_size_kb" value="<?= e(forum_setting('max_photo_size_kb', '2048')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Maksimalus priedų dydis (KB)</label>
                        <input class="form-control" type="number" name="attachments_max_size_kb" value="<?= e(forum_setting('attachments_max_size_kb', '5120')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Maksimalus priedų kiekis</label>
                        <input class="form-control" type="number" name="attachments_max_count" value="<?= e(forum_setting('attachments_max_count', '5')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Leidžiami failų tipai</label>
                        <input class="form-control" name="allowed_file_types" value="<?= e(forum_setting('allowed_file_types', 'jpg,jpeg,png,gif,webp,pdf,txt,zip')) ?>">
                    </div>

                    <div class="col-12 mt-2"><h3 class="h6 mb-3">Post Behaviour Configurations</h3></div>
                    <div class="col-md-6">
                        <label class="form-label">Redagavimo laiko limitas (min.)</label>
                        <input class="form-control" type="number" name="edit_time_limit_minutes" value="<?= e(forum_setting('edit_time_limit_minutes', '30')) ?>">
                    </div>
                    <div class="col-12">
                        <div class="row g-2">
                            <?php foreach ([
                                'show_latest_posts_below_reply_form' => 'Rodyti naujausius atsakymus po atsakymo forma?',
                                'show_ip_publicly' => 'Rodyti IP viešai',
                                'show_last_post_avatar' => 'Rodyti paskutinio atsakymo avatarą',
                                'lock_edit' => 'Riboti redagavimą laike',
                                'update_time_on_edit' => 'Atnaujinti laiką po redagavimo',
                            ] as $field => $label): ?>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="<?= e($field) ?>" id="<?= e($field) ?>" value="1" <?= forum_setting($field, '0') === '1' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="<?= e($field) ?>"><?= e($label) ?></label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="col-12">
                        <button class="btn btn-primary">Išsaugoti nustatymus</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header">Forumo statusai / reitingai</div>
            <div class="card-body">
                <p class="small text-secondary">Forumo reitingai yra naudojami vartotojų aktyvumui valdyti ir jų pasiekimams parodyti.</p>
                <form method="post" enctype="multipart/form-data" class="row g-3 mb-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="forum_admin_action" value="save_rank">
                    <input type="hidden" name="id" value="<?= (int)($editingRank['id'] ?? 0) ?>">
                    <input type="hidden" name="existing_image_path" value="<?= e($editingRank['image_path'] ?? '') ?>">
                    <div class="col-md-6"><label class="form-label">Pavadinimas</label><input class="form-control" name="title" value="<?= e($editingRank['title'] ?? '') ?>"></div>
                    <div class="col-md-6"><label class="form-label">Alias</label><input class="form-control" name="slug" value="<?= e($editingRank['slug'] ?? '') ?>"></div>
                    <div class="col-md-4"><label class="form-label">Nuo žinučių</label><input class="form-control" type="number" name="min_posts" value="<?= e($editingRank['min_posts'] ?? '0') ?>"></div>
                    <div class="col-md-4"><label class="form-label">Ikona</label><input class="form-control" name="icon_class" value="<?= e($editingRank['icon_class'] ?? '') ?>" placeholder="fa-solid fa-medal"></div>
                    <div class="col-md-4"><label class="form-label">Tvarka</label><input class="form-control" type="number" name="sort_order" value="<?= e($editingRank['sort_order'] ?? '1') ?>"></div>
                    <div class="col-md-8"><label class="form-label">Rango paveikslėlis</label><input class="form-control" type="file" name="rank_image" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp"></div>
                    <div class="col-md-4 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" id="rank-active" value="1" <?= !isset($editingRank['is_active']) || !empty($editingRank['is_active']) ? 'checked' : '' ?>><label class="form-check-label" for="rank-active">Aktyvus</label></div></div>
                    <div class="col-12"><button class="btn btn-primary"><?= $editingRank ? 'Išsaugoti rangą' : 'Sukurti rangą' ?></button></div>
                </form>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Pavadinimas</th><th>Nuo</th><th>Ikona</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($ranks as $rank): ?>
                            <tr>
                                <td><?= e($rank['title']) ?></td>
                                <td><?= (int)$rank['min_posts'] ?></td>
                                <td>
                                    <?php if (!empty($rank['image_path'])): ?>
                                        <img src="<?= escape_url(public_path(ltrim((string)$rank['image_path'], '/'))) ?>" alt="" class="forum-admin-rank-image">
                                    <?php else: ?>
                                        <i class="<?= e($rank['icon_class'] ?: 'fa-solid fa-medal') ?>"></i>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="?folder=forum&edit_rank=<?= (int)$rank['id'] ?>">Redaguoti</a>
                                    <form method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="forum_admin_action" value="delete_rank">
                                        <input type="hidden" name="id" value="<?= (int)$rank['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Trinti</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Forumo nuotaikos</div>
            <div class="card-body">
                <form method="post" class="row g-3 mb-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="forum_admin_action" value="save_mood">
                    <input type="hidden" name="id" value="<?= (int)($editingMood['id'] ?? 0) ?>">
                    <div class="col-md-5"><label class="form-label">Pavadinimas</label><input class="form-control" name="title" value="<?= e($editingMood['title'] ?? '') ?>"></div>
                    <div class="col-md-4"><label class="form-label">Alias</label><input class="form-control" name="slug" value="<?= e($editingMood['slug'] ?? '') ?>"></div>
                    <div class="col-md-3"><label class="form-label">Tvarka</label><input class="form-control" type="number" name="sort_order" value="<?= e($editingMood['sort_order'] ?? '1') ?>"></div>
                    <div class="col-md-8"><label class="form-label">Ikona</label><input class="form-control" name="icon_class" value="<?= e($editingMood['icon_class'] ?? '') ?>" placeholder="fa-regular fa-face-smile"></div>
                    <div class="col-md-4 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" id="mood-active" value="1" <?= !isset($editingMood['is_active']) || !empty($editingMood['is_active']) ? 'checked' : '' ?>><label class="form-check-label" for="mood-active">Aktyvi</label></div></div>
                    <div class="col-12"><button class="btn btn-primary"><?= $editingMood ? 'Išsaugoti nuotaiką' : 'Sukurti nuotaiką' ?></button></div>
                </form>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Nuotaika</th><th>Ikona</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($moods as $mood): ?>
                            <tr>
                                <td><?= e($mood['title']) ?></td>
                                <td><i class="<?= e($mood['icon_class'] ?: 'fa-regular fa-face-meh') ?>"></i></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="?folder=forum&edit_mood=<?= (int)$mood['id'] ?>">Redaguoti</a>
                                    <form method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="forum_admin_action" value="delete_mood">
                                        <input type="hidden" name="id" value="<?= (int)$mood['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Trinti</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
