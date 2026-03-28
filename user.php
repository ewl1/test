<?php
require_once __DIR__ . '/includes/bootstrap.php';

$userId = (int)($_GET['id'] ?? 0);
$profile = fetch_public_user_profile($userId);
if (!$profile) {
    abort_http(404, 'Narys nerastas.');
}

$viewer = current_user();
$viewerIsAdmin = $viewer && has_permission($GLOBALS['pdo'], (int)$viewer['id'], 'admin.access');
$ratingError = null;
$commentError = null;
$commentDraft = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();
    if (!$viewer) {
        abort_http(401, 'Prisijungimas reikalingas.');
    }

    $action = (string)($_POST['profile_action'] ?? '');
    if ($action === 'rate') {
        [$ok, $message] = save_profile_rating((int)$profile['id'], (int)$viewer['id'], (int)($_POST['rating'] ?? 0));
        if ($ok) {
            flash('profile_success', $message);
            redirect(user_profile_url((int)$profile['id']));
        }
        $ratingError = $message;
    } elseif ($action === 'comment') {
        $commentDraft = (string)($_POST['comment'] ?? '');
        [$ok, $message, $commentId] = create_profile_comment((int)$profile['id'], (int)$viewer['id'], $commentDraft);
        if ($ok) {
            flash('profile_success', $message);
            redirect(profile_comment_url((int)$profile['id'], (int)$commentId));
        }
        $commentError = $message;
    } elseif ($action === 'delete_comment') {
        [$ok, $message] = delete_profile_comment((int)($_POST['comment_id'] ?? 0), $viewer);
        if ($ok) {
            flash('profile_success', $message);
            redirect(user_profile_url((int)$profile['id']));
        }
        $commentError = $message;
    }
}

$latestIp = $viewerIsAdmin ? fetch_user_latest_ip((int)$profile['id']) : null;
$banStatus = $viewerIsAdmin && $latestIp ? fetch_ip_ban_status($latestIp) : null;
$shoutCount = count_user_shoutbox_messages((int)$profile['id']);
$forumMessageCount = count_user_forum_messages((int)$profile['id']);
$ratingSummary = fetch_profile_rating_summary((int)$profile['id']);
$viewerRating = $viewer ? fetch_profile_rating_for_viewer((int)$profile['id'], (int)$viewer['id']) : 0;
$profileCommentCount = count_profile_comments((int)$profile['id']);
$profileComments = fetch_profile_comments((int)$profile['id'], 20);
$successMessage = flash('profile_success');
$statusLabels = [
    'active' => 'Aktyvus',
    'inactive' => 'Neaktyvus',
    'blocked' => 'Blokuotas',
    'deleted' => 'Ištrintas',
];

include __DIR__ . '/themes/default/header.php';
?>
<div class="row g-4">
    <div class="col-xl-8">
        <?php if ($successMessage): ?>
            <div class="alert alert-success"><?= e($successMessage) ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-md-center gap-4">
                    <img src="<?= escape_url(user_avatar_url($profile)) ?>" alt="" class="user-profile-avatar">
                    <div class="flex-grow-1">
                        <h1 class="h3 mb-1"><?= e($profile['username']) ?></h1>
                        <div class="text-secondary mb-2"><?= e($profile['role_name'] ?? __('member.none')) ?></div>
                        <div class="d-flex align-items-center gap-3 flex-wrap mb-3">
                            <div class="user-rating-display">
                                <?= render_profile_rating_stars((float)$ratingSummary['average_rating'], (int)$ratingSummary['rating_count']) ?>
                            </div>
                            <div class="text-secondary small">
                                <?= e(number_format((float)$ratingSummary['average_rating'], 1)) ?> / 5
                                · balsai: <?= (int)$ratingSummary['rating_count'] ?>
                            </div>
                        </div>
                        <?php if (!empty($profile['signature'])): ?>
                            <div class="user-signature"><?= render_user_signature($profile['signature']) ?></div>
                        <?php else: ?>
                            <div class="text-secondary"><?= e(__('profile.signature.empty')) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><?= e(__('profile.rating')) ?></div>
            <div class="card-body">
                <?php if ($ratingError): ?>
                    <div class="alert alert-danger"><?= e($ratingError) ?></div>
                <?php endif; ?>

                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <div class="h4 mb-1"><?= e(number_format((float)$ratingSummary['average_rating'], 1)) ?> / 5</div>
                        <div class="text-secondary small"><?= e(__('profile.rating.total', ['count' => (int)$ratingSummary['rating_count']])) ?></div>
                    </div>
                    <div class="user-rating-display">
                        <?= render_profile_rating_stars((float)$ratingSummary['average_rating'], (int)$ratingSummary['rating_count']) ?>
                    </div>
                </div>

                <?php if (!$viewer): ?>
                    <div class="alert alert-info mt-3 mb-0"><?= e(__('profile.rating.login')) ?></div>
                <?php else: ?>
                    <form method="post" class="mt-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="profile_action" value="rate">
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <?php foreach (profile_rating_options() as $option): ?>
                                <button class="btn <?= $viewerRating === $option ? 'btn-primary' : 'btn-outline-primary' ?>" type="submit" name="rating" value="<?= (int)$option ?>">
                                    <?= (int)$option ?> ★
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-text mt-2">
                            <?php if ($viewerRating > 0): ?>
                                <?= e(__('profile.rating.last', ['rating' => (int)$viewerRating])) ?>
                            <?php else: ?>
                                <?= e(__('profile.rating.choose')) ?>
                            <?php endif; ?>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><?= e(__('profile.comments')) ?></div>
            <div class="card-body">
                <?php if ($commentError): ?>
                    <div class="alert alert-danger"><?= e($commentError) ?></div>
                <?php endif; ?>

                <?php if (!$viewer): ?>
                    <div class="alert alert-info"><?= e(__('profile.comments.login')) ?></div>
                <?php else: ?>
                    <form method="post" class="mb-4">
                        <?= csrf_field() ?>
                        <input type="hidden" name="profile_action" value="comment">
                        <div class="mb-2 d-flex flex-wrap gap-2">
                            <?php foreach (profile_comment_bbcode_buttons() as $button): ?>
                                <button
                                    class="btn btn-sm btn-outline-secondary"
                                    type="button"
                                    data-bbcode-target="profile-comment"
                                    data-bbcode-insert="<?= e($button['insert']) ?>"
                                ><?= e($button['label']) ?></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="profile-comment"><?= e(__('profile.comment')) ?></label>
                            <textarea class="form-control" id="profile-comment" name="comment" rows="5" maxlength="2000" required><?= e($commentDraft) ?></textarea>
                            <div class="form-text"><?= e(__('profile.comment.allowed')) ?></div>
                        </div>
                        <button class="btn btn-primary"><?= e(__('profile.comment.publish')) ?></button>
                    </form>
                <?php endif; ?>

                <div class="profile-comments-list">
                    <?php if (!$profileComments): ?>
                        <div class="text-secondary"><?= e(__('profile.comments.empty')) ?></div>
                    <?php else: ?>
                        <?php foreach ($profileComments as $comment): ?>
                            <article class="profile-comment-item" id="profile-comment-<?= (int)$comment['id'] ?>">
                                <div class="d-flex align-items-start gap-3">
                                    <a href="<?= user_profile_url((int)$comment['author_user_id']) ?>" class="text-decoration-none">
                                        <img
                                            src="<?= escape_url(user_avatar_url([
                                                'avatar' => $comment['author_avatar'] ?? null,
                                                'email' => $comment['author_email'] ?? null,
                                            ])) ?>"
                                            alt=""
                                            class="member-panel-avatar"
                                        >
                                    </a>
                                    <div class="min-w-0 flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                            <div>
                                                <a class="fw-semibold text-decoration-none" href="<?= user_profile_url((int)$comment['author_user_id']) ?>"><?= e($comment['author_username'] ?? __('member.none')) ?></a>
                                                <div class="small text-secondary"><?= e(format_dt($comment['created_at'])) ?></div>
                                            </div>
                                            <?php if (can_manage_profile_comment($comment, $viewer)): ?>
                                                <form method="post">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="profile_action" value="delete_comment">
                                                    <input type="hidden" name="comment_id" value="<?= (int)$comment['id'] ?>">
                                                    <button class="btn btn-sm btn-outline-danger" type="submit" data-confirm-message="<?= e(__('profile.comment.delete.confirm')) ?>"><?= e(__('forum.reply.delete')) ?></button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                        <div class="profile-comment-body mt-2"><?= profile_render_comment_body($comment['content']) ?></div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <aside class="col-xl-4">
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-12">
                <div class="user-stat-card">
                    <div class="small text-secondary mb-1"><?= e(__('profile.stat.shoutbox')) ?></div>
                    <div class="h3 mb-0"><?= (int)$shoutCount ?></div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-12">
                <div class="user-stat-card">
                    <div class="small text-secondary mb-1"><?= e(__('profile.stat.forum')) ?></div>
                    <div class="h3 mb-0"><?= (int)$forumMessageCount ?></div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-12">
                <div class="user-stat-card">
                    <div class="small text-secondary mb-1"><?= e(__('profile.stat.comments')) ?></div>
                    <div class="h3 mb-0"><?= (int)$profileCommentCount ?></div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-12">
                <div class="user-stat-card">
                    <div class="small text-secondary mb-1"><?= e(__('profile.stat.joined')) ?></div>
                    <div class="h6 mb-0"><?= e(format_dt($profile['created_at'])) ?></div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-12">
                <div class="user-stat-card">
                    <div class="small text-secondary mb-1"><?= e(__('profile.stat.status')) ?></div>
                    <div class="h6 mb-0"><?= e($statusLabels[$profile['status'] ?? 'active'] ?? ($profile['status'] ?? 'active')) ?></div>
                </div>
            </div>
        </div>

        <?php if ($viewerIsAdmin): ?>
            <div class="card">
                <div class="card-header"><?= e(__('profile.admin_info')) ?></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="small text-secondary mb-1"><?= e(__('profile.last_ip')) ?></div>
                            <div><?= e($latestIp ?: __('profile.no_data')) ?></div>
                        </div>
                        <div class="col-12">
                            <div class="small text-secondary mb-1"><?= e(__('profile.ban')) ?></div>
                            <?php if ($banStatus): ?>
                                <div class="fw-semibold text-danger">Taip</div>
                                <div class="small text-secondary"><?= e(__('profile.reason')) ?>: <?= e($banStatus['reason'] ?? '-') ?></div>
                                <div class="small text-secondary"><?= e(__('profile.until')) ?>: <?= e(format_dt($banStatus['banned_until'], __('profile.unlimited'))) ?></div>
                            <?php else: ?>
                                <div class="fw-semibold text-success">Ne</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </aside>
</div>
<?php include __DIR__ . '/themes/default/footer.php'; ?>
