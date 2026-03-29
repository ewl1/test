<?php

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
