<?php
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
        if (validate_url_value($url, true, 'Nuoroda', ['http', 'https'], false) !== null) {
            return $label;
        }

        return '<a href="' . escape_url($url) . '" target="_blank" rel="nofollow ugc noopener noreferrer">' . $label . '</a>';
    }, $message);

    foreach (shoutbox_smileys() as $code => $emoji) {
        $message = str_replace(escape_html($code), '<span class="shoutbox-smiley">' . $emoji . '</span>', $message);
    }

    return nl2br($message);
}

function shoutbox_get_messages($limit = 50)
{
    $stmt = $GLOBALS['pdo']->prepare("
        SELECT m.*, u.username
        FROM " . shoutbox_table_name() . " m
        LEFT JOIN users u ON u.id = m.user_id
        ORDER BY m.created_at DESC
        LIMIT " . (int)$limit
    );
    $stmt->execute();
    return array_reverse($stmt->fetchAll());
}

function shoutbox_create_message($message)
{
    $user = current_user();
    if (!$user) {
        return [false, 'Rašyti gali tik prisijungę nariai.'];
    }

    $message = sanitize_bbcode_input($message, shoutbox_allowed_tags(), 500);
    if ($message === '') {
        return [false, 'Žinutė negali būti tuščia.'];
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
    return [true, 'Žinutė paskelbta.'];
}

function shoutbox_delete_message($id)
{
    $stmt = $GLOBALS['pdo']->prepare("DELETE FROM " . shoutbox_table_name() . " WHERE id = :id");
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
        <div class="alert alert-info mb-0">Rašyti gali tik prisijungę nariai. <a href="<?= public_path('login.php') ?>">Prisijunkite</a>.</div>
        <?php
        return;
    endif;
    ?>

    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="shoutbox_action" value="post">
        <input type="hidden" name="shoutbox_context" value="<?= e($context) ?>">
        <input type="hidden" name="redirect_to" value="<?= e($redirectPath) ?>">

        <div class="mb-2 d-flex flex-wrap gap-2">
            <?php foreach (shoutbox_bbcode_buttons() as $button): ?>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-target="<?= e($textareaId) ?>" data-insert="<?= e($button['insert']) ?>"><?= e($button['label']) ?></button>
            <?php endforeach; ?>
        </div>
        <div class="mb-2 d-flex flex-wrap gap-2">
            <?php foreach (shoutbox_smileys() as $code => $emoji): ?>
                <button type="button" class="btn btn-sm btn-outline-warning" data-target="<?= e($textareaId) ?>" data-smiley="<?= e($code) ?>"><?= $emoji ?></button>
            <?php endforeach; ?>
        </div>

        <div class="mb-3">
            <label class="form-label"><?= $compact ? 'Komentaras' : 'Žinutė' ?></label>
            <textarea class="form-control" id="<?= e($textareaId) ?>" name="message" rows="<?= $compact ? 3 : 4 ?>" maxlength="500" placeholder="<?= $compact ? 'Parašykite žinutę...' : 'Rašykite žinutę...' ?>"></textarea>
            <div class="form-text">Leidžiamas BBCode: [b], [i], [u], [quote], [code], [url=...][/url]</div>
        </div>
        <button class="btn btn-primary"><?= $compact ? 'Komentuoti' : 'Siųsti' ?></button>
    </form>

    <script>
    (function () {
        function insertText(textarea, value) {
            if (!textarea) return;
            var start = textarea.selectionStart || 0;
            var end = textarea.selectionEnd || 0;
            var current = textarea.value;
            textarea.value = current.slice(0, start) + value + current.slice(end);
            textarea.focus();
            var cursor = start + value.length;
            textarea.setSelectionRange(cursor, cursor);
        }

        document.querySelectorAll('[data-target="<?= e($textareaId) ?>"][data-insert]').forEach(function (button) {
            button.addEventListener('click', function () {
                insertText(document.getElementById(button.getAttribute('data-target')), button.getAttribute('data-insert'));
            });
        });

        document.querySelectorAll('[data-target="<?= e($textareaId) ?>"][data-smiley]').forEach(function (button) {
            button.addEventListener('click', function () {
                insertText(document.getElementById(button.getAttribute('data-target')), ' ' + button.getAttribute('data-smiley') + ' ');
            });
        });
    }());
    </script>
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

    $context = ($_POST['shoutbox_context'] ?? 'page') === 'panel' ? 'panel' : 'page';
    [$ok, $message] = shoutbox_create_message($_POST['message'] ?? '');
    flash(shoutbox_flash_key($context, $ok ? 'success' : 'error'), $message);

    $fallback = $context === 'panel' ? 'index.php' : 'shoutbox.php';
    redirect(redirect_target_url($_POST['redirect_to'] ?? '', $fallback));
}

function render_shoutbox_page()
{
    $messages = shoutbox_get_messages(100);
    include THEMES . setting('current_theme', CURRENT_THEME) . '/header.php';
    ?>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <h1 class="h4 mb-3">Šaukykla</h1>
                    <?php shoutbox_render_editor('page', 'shoutbox-message', 'shoutbox.php', false); ?>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <?php if (!$messages): ?>
                        <p class="text-secondary mb-0">Kol kas žinučių nėra.</p>
                    <?php endif; ?>

                    <?php foreach ($messages as $message): ?>
                        <div class="border-bottom py-3">
                            <div class="d-flex justify-content-between gap-3">
                                <div>
                                    <strong><?= e($message['username'] ?? 'Svečias') ?></strong>
                                    <div class="text-secondary small"><?= e(format_dt($message['created_at'])) ?></div>
                                </div>
                            </div>
                            <div class="mt-2"><?= shoutbox_escape_and_format($message['message']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    include THEMES . setting('current_theme', CURRENT_THEME) . '/footer.php';
}

shoutbox_handle_request();
