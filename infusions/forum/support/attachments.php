<?php
function forum_normalize_upload_array(array $files)
{
    if (!isset($files['name']) || !is_array($files['name'])) {
        return [];
    }

    $normalized = [];
    foreach (array_keys($files['name']) as $index) {
        $normalized[] = [
            'name' => $files['name'][$index] ?? '',
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$index] ?? 0,
        ];
    }

    return $normalized;
}

function forum_attachment_directory_absolute()
{
    return BASEDIR . 'uploads/forum';
}

function forum_attachment_directory_public()
{
    return 'uploads/forum';
}

function forum_attachment_allowed_mime_map()
{
    return [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'pdf' => ['application/pdf'],
        'txt' => ['text/plain'],
        'zip' => ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip'],
    ];
}

function forum_validate_attachment_file(array $file)
{
    if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [true, null];
    }

    if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [false, upload_error_message((int)$file['error'])];
    }

    if (!is_uploaded_file((string)$file['tmp_name'])) {
        return [false, 'Įkeltas failas neatpažintas kaip saugus upload failas.'];
    }

    $size = (int)($file['size'] ?? 0);
    if ($size <= 0) {
        return [false, 'Prisegtas failas yra tuščias.'];
    }
    if ($size > forum_attachment_max_size_bytes()) {
        return [false, 'Prisegtas failas viršija maksimalų leistiną dydį.'];
    }

    $originalName = trim((string)($file['name'] ?? 'file'));
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($extension === '') {
        return [false, 'Prisegtas failas privalo turėti plėtinį.'];
    }

    $allowed = forum_attachment_allowed_extensions();
    if ($allowed && !in_array($extension, $allowed, true)) {
        return [false, 'Šio tipo failo prisegti negalima.'];
    }

    $mimeType = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mimeType = (string)finfo_file($finfo, (string)$file['tmp_name']);
            finfo_close($finfo);
        }
    }

    $mimeMap = forum_attachment_allowed_mime_map();
    if (isset($mimeMap[$extension]) && $mimeType !== '' && !in_array($mimeType, $mimeMap[$extension], true)) {
        return [false, 'Failo MIME tipas neatitinka jo plėtinio.'];
    }

    $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    if ($isImage) {
        if ($size > forum_max_photo_size_bytes()) {
            return [false, 'Paveikslėlis viršija maksimalų leistiną nuotraukos dydį.'];
        }
        if (@getimagesize((string)$file['tmp_name']) === false) {
            return [false, 'Paveikslėlio failas neatpažintas kaip galiojantis paveikslėlis.'];
        }
    }

    $safeBase = normalize_slug(pathinfo($originalName, PATHINFO_FILENAME));
    if ($safeBase === '') {
        $safeBase = 'attachment';
    }

    return [true, [
        'original_name' => $originalName,
        'extension' => $extension,
        'mime_type' => $mimeType,
        'size' => $size,
        'is_image' => $isImage,
        'safe_base' => $safeBase,
        'tmp_name' => (string)$file['tmp_name'],
    ]];
}

function forum_store_attachment_files($forumId, $topicId, $postId, $userId, array $files)
{
    $normalized = forum_normalize_upload_array($files);
    if (!$normalized) {
        return [true, []];
    }

    if (count($normalized) > forum_attachment_max_count()) {
        return [false, 'Pridėta per daug prisegtų failų vienam pranešimui.'];
    }

    $baseDir = forum_attachment_directory_absolute();
    $relativeBase = forum_attachment_directory_public();
    $subdir = date('Y/m');
    $absoluteDir = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subdir);
    if (!is_dir($absoluteDir) && !@mkdir($absoluteDir, 0755, true) && !is_dir($absoluteDir)) {
        return [false, 'Nepavyko sukurti prisegtų failų katalogo.'];
    }

    $saved = [];
    foreach ($normalized as $file) {
        [$ok, $validated] = forum_validate_attachment_file($file);
        if (!$ok) {
            foreach ($saved as $attachment) {
                @unlink(BASEDIR . $attachment['stored_name']);
            }
            return [false, $validated];
        }
        if ($validated === null) {
            continue;
        }

        $storedName = $relativeBase . '/' . $subdir . '/' . $validated['safe_base'] . '-' . bin2hex(random_bytes(6)) . '.' . $validated['extension'];
        $absolutePath = BASEDIR . str_replace('/', DIRECTORY_SEPARATOR, $storedName);
        if (!move_uploaded_file($validated['tmp_name'], $absolutePath)) {
            foreach ($saved as $attachment) {
                @unlink(BASEDIR . $attachment['stored_name']);
            }
            return [false, 'Nepavyko išsaugoti prisegto failo.'];
        }

        $saved[] = [
            'forum_id' => (int)$forumId,
            'topic_id' => (int)$topicId,
            'post_id' => $postId !== null ? (int)$postId : null,
            'user_id' => (int)$userId,
            'original_name' => $validated['original_name'],
            'stored_name' => $storedName,
            'mime_type' => $validated['mime_type'],
            'file_ext' => $validated['extension'],
            'file_size' => $validated['size'],
            'is_image' => $validated['is_image'] ? 1 : 0,
        ];
    }

    if (!$saved) {
        return [true, []];
    }

    $stmt = $GLOBALS['pdo']->prepare('
        INSERT INTO ' . forum_table_attachments() . ' (
            forum_id, topic_id, post_id, user_id, original_name, stored_name, mime_type, file_ext, file_size, is_image, created_at
        ) VALUES (
            :forum_id, :topic_id, :post_id, :user_id, :original_name, :stored_name, :mime_type, :file_ext, :file_size, :is_image, NOW()
        )
    ');

    foreach ($saved as $attachment) {
        $stmt->execute([
            ':forum_id' => $attachment['forum_id'],
            ':topic_id' => $attachment['topic_id'],
            ':post_id' => $attachment['post_id'],
            ':user_id' => $attachment['user_id'],
            ':original_name' => $attachment['original_name'],
            ':stored_name' => $attachment['stored_name'],
            ':mime_type' => $attachment['mime_type'],
            ':file_ext' => $attachment['file_ext'],
            ':file_size' => $attachment['file_size'],
            ':is_image' => $attachment['is_image'],
        ]);
    }

    return [true, $saved];
}

function forum_get_attachments_for_topic($topicId)
{
    $stmt = $GLOBALS['pdo']->prepare('SELECT * FROM ' . forum_table_attachments() . ' WHERE topic_id = :topic_id AND post_id IS NULL ORDER BY id ASC');
    $stmt->execute([':topic_id' => (int)$topicId]);
    return $stmt->fetchAll();
}

function forum_get_attachments_for_posts(array $postIds)
{
    $postIds = array_values(array_filter(array_map('intval', $postIds)));
    if (!$postIds) {
        return [];
    }

    $sql = 'SELECT * FROM ' . forum_table_attachments() . ' WHERE post_id IN (' . implode(',', $postIds) . ') ORDER BY id ASC';
    $rows = $GLOBALS['pdo']->query($sql)->fetchAll();
    $result = [];
    foreach ($rows as $row) {
        $result[(int)$row['post_id']][] = $row;
    }

    return $result;
}

function forum_delete_attachment_records(array $attachments)
{
    foreach ($attachments as $attachment) {
        if (!empty($attachment['stored_name'])) {
            @unlink(BASEDIR . str_replace('/', DIRECTORY_SEPARATOR, (string)$attachment['stored_name']));
        }
    }
}

function forum_delete_attachments_for_topic($topicId)
{
    $attachments = forum_get_attachments_for_topic((int)$topicId);
    $postIds = $GLOBALS['pdo']->query('SELECT id FROM ' . forum_table_posts() . ' WHERE topic_id = ' . (int)$topicId)->fetchAll(PDO::FETCH_COLUMN);
    $replyMap = forum_get_attachments_for_posts(array_map('intval', $postIds));
    foreach ($replyMap as $rows) {
        $attachments = array_merge($attachments, $rows);
    }
    forum_delete_attachment_records($attachments);
    $stmt = $GLOBALS['pdo']->prepare('DELETE FROM ' . forum_table_attachments() . ' WHERE topic_id = :topic_id');
    $stmt->execute([':topic_id' => (int)$topicId]);
}

function forum_delete_attachments_for_post($postId)
{
    $stmt = $GLOBALS['pdo']->prepare('SELECT * FROM ' . forum_table_attachments() . ' WHERE post_id = :post_id');
    $stmt->execute([':post_id' => (int)$postId]);
    $attachments = $stmt->fetchAll();
    forum_delete_attachment_records($attachments);
    $delete = $GLOBALS['pdo']->prepare('DELETE FROM ' . forum_table_attachments() . ' WHERE post_id = :post_id');
    $delete->execute([':post_id' => (int)$postId]);
}

function forum_render_attachments(array $attachments)
{
    if (!$attachments) {
        return '';
    }

    $html = '<div class="forum-attachments">';
    foreach ($attachments as $attachment) {
        $url = public_path(ltrim((string)$attachment['stored_name'], '/'));
        $name = e((string)$attachment['original_name']);
        if (!empty($attachment['is_image'])) {
            $html .= '<a class="forum-attachment forum-attachment-image" href="' . escape_url($url) . '" target="_blank" rel="noopener"><img src="' . escape_url($url) . '" alt="' . $name . '"><span>' . $name . '</span></a>';
        } else {
            $html .= '<a class="forum-attachment forum-attachment-file" href="' . escape_url($url) . '" target="_blank" rel="noopener"><i class="fa-solid fa-paperclip" aria-hidden="true"></i><span>' . $name . '</span></a>';
        }
    }
    $html .= '</div>';

    return $html;
}

