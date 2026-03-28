<?php
function shoutbox_register_assets()
{
    register_page_style('infusions/shoutbox/assets/css/shoutbox.css');
    register_page_script('infusions/shoutbox/assets/js/shoutbox.js');
}

function shoutbox_smileys()
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

function shoutbox_table_name()
{
    return 'infusion_shoutbox_messages';
}

function shoutbox_allowed_tags()
{
    return ['b', 'i', 'u', 'quote', 'code', 'url'];
}

function shoutbox_default_order()
{
    return 'desc';
}

function shoutbox_normalize_order($value = null)
{
    $order = strtolower((string)($value ?? shoutbox_default_order()));
    return $order === 'asc' ? 'asc' : 'desc';
}

function shoutbox_message_order()
{
    return shoutbox_normalize_order(setting('shoutbox_order', shoutbox_default_order()));
}

function shoutbox_messages_per_page()
{
    $value = (int)setting('shoutbox_messages_per_page', '20');
    return max(5, min(100, $value));
}

function shoutbox_panel_messages_limit()
{
    $value = (int)setting('shoutbox_panel_messages', '5');
    return max(3, min(20, $value));
}

function shoutbox_count_messages()
{
    return (int)$GLOBALS['pdo']->query('SELECT COUNT(*) FROM ' . shoutbox_table_name())->fetchColumn();
}

function shoutbox_bbcode_buttons()
{
    return [
        ['label' => 'B', 'insert' => '[b][/b]'],
        ['label' => 'I', 'insert' => '[i][/i]'],
        ['label' => 'U', 'insert' => '[u][/u]'],
        ['label' => 'Code', 'insert' => '[code][/code]'],
        ['label' => 'Quote', 'insert' => '[quote][/quote]'],
        ['label' => 'Link', 'insert' => '[url=https://][/url]'],
    ];
}

function shoutbox_flash_key($context, $type)
{
    return 'shoutbox_' . $context . '_' . $type;
}

function shoutbox_escape_and_format($message)
{
    $message = sanitize_bbcode_input($message, shoutbox_allowed_tags(), 500);
    $message = escape_html($message);

    $patterns = [
        '/\[b\](.*?)\[\/b\]/is' => '<strong>$1</strong>',
        '/\[i\](.*?)\[\/i\]/is' => '<em>$1</em>',
        '/\[u\](.*?)\[\/u\]/is' => '<u>$1</u>',
        '/\[quote\](.*?)\[\/quote\]/is' => '<blockquote class="border-start ps-3 text-secondary">$1</blockquote>',
        '/\[code\](.*?)\[\/code\]/is' => '<code>$1</code>',
    ];
    foreach ($patterns as $pattern => $replacement) {
        $message = preg_replace($pattern, $replacement, $message);
    }

    $message = preg_replace_callback('/\[url=(https?:\/\/[^\]\s]+)\](.*?)\[\/url\]/is', function ($matches) {
        $url = trim((string)$matches[1]);
        $label = $matches[2];
        if (validate_url_value($url, true, __('shoutbox.link_label'), ['http', 'https'], false) !== null) {
            return $label;
        }

        return '<a href="' . escape_url($url) . '" target="_blank" rel="nofollow ugc noopener noreferrer">' . $label . '</a>';
    }, $message);

    foreach (shoutbox_smileys() as $code => $emoji) {
        $message = str_replace(escape_html($code), '<span class="shoutbox-smiley">' . $emoji . '</span>', $message);
    }

    return nl2br($message);
}

function shoutbox_plain_excerpt($message, $length = 120)
{
    $message = preg_replace('/\[(\/?)[a-z]+(?:=[^\]]*)?\]/i', '', (string)$message);
    $message = trim(preg_replace('/\s+/u', ' ', $message));
    if (mb_strlen($message) <= $length) {
        return $message;
    }

    return rtrim(mb_substr($message, 0, $length - 1)) . '...';
}

function shoutbox_get_messages($limit = 50, $offset = 0, $order = null)
{
    $limit = max(1, (int)$limit);
    $offset = max(0, (int)$offset);
    $sqlOrder = strtoupper(shoutbox_normalize_order($order));

    $stmt = $GLOBALS['pdo']->prepare("
        SELECT m.*, u.username, u.avatar, u.email
        FROM " . shoutbox_table_name() . " m
        LEFT JOIN users u ON u.id = m.user_id
        ORDER BY m.created_at {$sqlOrder}, m.id {$sqlOrder}
        LIMIT {$limit} OFFSET {$offset}
    ");
    $stmt->execute();

    return $stmt->fetchAll();
}

function shoutbox_message_path($messageId)
{
    $stmt = $GLOBALS['pdo']->prepare('SELECT id, created_at FROM ' . shoutbox_table_name() . ' WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => (int)$messageId]);
    $message = $stmt->fetch();
    if (!$message) {
        return 'shoutbox.php';
    }

    $operator = shoutbox_message_order() === 'desc' ? '>' : '<';
    $stmt = $GLOBALS['pdo']->prepare("
        SELECT COUNT(*)
        FROM " . shoutbox_table_name() . "
        WHERE created_at {$operator} :created_at_compare
           OR (created_at = :created_at_exact AND id {$operator} :id)
    ");
    $stmt->execute([
        ':created_at_compare' => $message['created_at'],
        ':created_at_exact' => $message['created_at'],
        ':id' => (int)$message['id'],
    ]);

    $position = (int)$stmt->fetchColumn() + 1;
    $page = max(1, (int)ceil($position / shoutbox_messages_per_page()));
    $path = 'shoutbox.php';
    if ($page > 1) {
        $path .= '?page=' . $page;
    }

    return $path . '#shoutbox-message-' . (int)$messageId;
}

function shoutbox_message_url($messageId)
{
    return public_path(shoutbox_message_path($messageId));
}

function shoutbox_create_message($message)
{
    $user = current_user();
    if (!$user) {
        return [false, __('shoutbox.post.login')];
    }

    $message = sanitize_bbcode_input($message, shoutbox_allowed_tags(), 500);
    if ($message === '') {
        return [false, __('shoutbox.message.empty')];
    }

    $stmt = $GLOBALS['pdo']->prepare("
        INSERT INTO " . shoutbox_table_name() . " (user_id, message, created_at, updated_at)
        VALUES (:user_id, :message, NOW(), NOW())
    ");
    $stmt->execute([
        ':user_id' => (int)$user['id'],
        ':message' => $message,
    ]);

    audit_log((int)$user['id'], 'shoutbox_post', 'infusion_shoutbox_messages', (int)$GLOBALS['pdo']->lastInsertId());
    return [true, __('shoutbox.message.created')];
}

function shoutbox_delete_message($id)
{
    $stmt = $GLOBALS['pdo']->prepare('DELETE FROM ' . shoutbox_table_name() . ' WHERE id = :id');
    $stmt->execute([':id' => (int)$id]);
    audit_log(current_user()['id'] ?? null, 'shoutbox_delete', 'infusion_shoutbox_messages', (int)$id);
}

function shoutbox_render_editor($context = 'page', $textareaId = 'shoutbox-message', $redirectPath = 'shoutbox.php', $compact = false)
{
    $success = flash(shoutbox_flash_key($context, 'success'));
    $error = flash(shoutbox_flash_key($context, 'error'));

    if ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif;
    if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif;

    if (!current_user()): ?>
        <div class="alert alert-info mb-0"><?= e(__('shoutbox.post.login')) ?> <a href="<?= public_path('login.php') ?>"><?= e(__('nav.login')) ?></a>.</div>
        <?php
        return;
    endif;
    ?>

    <form method="post" class="shoutbox-editor-form <?= $compact ? 'shoutbox-editor-form-compact' : 'shoutbox-editor-form-page' ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="shoutbox_action" value="post">
        <input type="hidden" name="shoutbox_context" value="<?= e($context) ?>">
        <input type="hidden" name="redirect_to" value="<?= e($redirectPath) ?>">

        <div class="mb-2 d-flex flex-wrap gap-2 shoutbox-toolbar">
            <?php foreach (shoutbox_bbcode_buttons() as $button): ?>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-shoutbox-editor-target="<?= e($textareaId) ?>" data-shoutbox-insert-text="<?= e($button['insert']) ?>"><?= e($button['label']) ?></button>
            <?php endforeach; ?>
        </div>
        <div class="mb-2 d-flex flex-wrap gap-2 shoutbox-smiley-toolbar">
            <?php foreach (shoutbox_smileys() as $code => $emoji): ?>
                <button type="button" class="btn btn-sm btn-outline-warning" data-shoutbox-editor-target="<?= e($textareaId) ?>" data-shoutbox-smiley-code="<?= e($code) ?>"><?= $emoji ?></button>
            <?php endforeach; ?>
        </div>

        <div class="mb-3 shoutbox-editor-field">
            <label class="form-label"><?= e($compact ? __('shoutbox.comment') : __('shoutbox.message')) ?></label>
            <textarea class="form-control shoutbox-editor-textarea" id="<?= e($textareaId) ?>" name="message" rows="<?= $compact ? 3 : 4 ?>" maxlength="500" placeholder="<?= e($compact ? __('shoutbox.comment.placeholder') : __('shoutbox.message.placeholder')) ?>"></textarea>
            <div class="form-text"><?= e(__('shoutbox.allowed_bbcode')) ?></div>
        </div>
        <button class="btn btn-primary"><?= e($compact ? __('shoutbox.comment.send') : __('shoutbox.send')) ?></button>
    </form>

    <?php
}

function shoutbox_handle_request()
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }

    if (($_POST['shoutbox_action'] ?? '') !== 'post') {
        return;
    }

    verify_csrf();

    $context = ($_POST['shoutbox_context'] ?? 'panel') === 'panel' ? 'panel' : 'page';
    [$ok, $message] = shoutbox_create_message($_POST['message'] ?? '');
    flash(shoutbox_flash_key($context, $ok ? 'success' : 'error'), $message);

    $fallback = $context === 'panel' ? 'index.php' : 'shoutbox.php';
    $redirectPath = normalize_local_path($_POST['redirect_to'] ?? '', $fallback);

    if ($ok && $context === 'page') {
        if (shoutbox_message_order() === 'desc') {
            $redirectPath = 'shoutbox.php';
        } else {
            $lastPage = max(1, (int)ceil(shoutbox_count_messages() / shoutbox_messages_per_page()));
            $redirectPath = $lastPage > 1 ? 'shoutbox.php?page=' . $lastPage : 'shoutbox.php';
        }
    }

    redirect(redirect_target_url($redirectPath, $fallback));
}

function render_shoutbox_page()
{
    $perPage = shoutbox_messages_per_page();
    $page = max(1, (int)($_GET['page'] ?? 1));
    $total = shoutbox_count_messages();
    $pager = paginate($total, $perPage, $page);

    if (($pager['pages'] ?? 0) > 0 && $page > (int)$pager['pages']) {
        $page = (int)$pager['pages'];
        $pager = paginate($total, $perPage, $page);
    }

    $messages = shoutbox_get_messages($perPage, (int)$pager['offset']);
    include THEMES . setting('current_theme', CURRENT_THEME) . '/header.php';
    ?>
    <div class="row justify-content-center shoutbox-page">
        <div class="col-lg-8">
            <div class="card mb-3 shoutbox-composer-card">
                <div class="card-body">
                    <h1 class="h4 mb-3"><?= e(__('shoutbox.title')) ?></h1>
                    <?php shoutbox_render_editor('page', 'shoutbox-message', 'shoutbox.php', false); ?>
                </div>
            </div>

            <div class="card shoutbox-messages-card">
                <div class="card-body">
                    <?php if (!$messages): ?>
                        <p class="text-secondary mb-0"><?= e(__('shoutbox.empty')) ?></p>
                    <?php endif; ?>

                    <?php foreach ($messages as $message): ?>
                        <article class="shoutbox-message-item border-bottom py-3" id="shoutbox-message-<?= (int)$message['id'] ?>">
                            <div class="d-flex justify-content-between gap-3 align-items-start">
                                <div class="shoutbox-message-meta">
                                    <?php if (!empty($message['user_id'])): ?>
                                        <strong><a class="text-decoration-none" href="<?= user_profile_url((int)$message['user_id']) ?>"><?= e($message['username'] ?? __('member.none')) ?></a></strong>
                                    <?php else: ?>
                                        <strong><?= e($message['username'] ?? __('member.guest')) ?></strong>
                                    <?php endif; ?>
                                    <div class="text-secondary small"><?= e(format_dt($message['created_at'])) ?></div>
                                </div>
                            </div>
                            <div class="mt-2 shoutbox-message-body"><?= shoutbox_escape_and_format($message['message']) ?></div>
                        </article>
                    <?php endforeach; ?>

                    <?php $pagination = render_pagination(public_path('shoutbox.php'), $pager); ?>
                    <?php if ($pagination !== ''): ?>
                        <div class="mt-3"><?= $pagination ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    include THEMES . setting('current_theme', CURRENT_THEME) . '/footer.php';
}

shoutbox_register_assets();
shoutbox_handle_request();
