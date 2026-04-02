<?php

function image_upload_policy_defaults()
{
    return [
        'enabled' => setting('image_uploads_enabled', '1') === '1',
        'max_size_kb' => max(64, (int)setting('image_upload_max_size_kb', '2048')),
        'max_width' => max(64, (int)setting('image_upload_max_width', '4096')),
        'max_height' => max(64, (int)setting('image_upload_max_height', '4096')),
        'quota_per_user_mb' => max(0, (int)setting('image_upload_quota_per_user_mb', '25')),
        'allowed_mime' => image_upload_allowed_mime_types(),
    ];
}

function image_upload_allowed_mime_types()
{
    $raw = trim((string)setting('image_upload_allowed_mime', 'image/jpeg,image/png,image/gif,image/webp'));
    if ($raw === '') {
        return ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    }

    $items = preg_split('/[\s,]+/', $raw) ?: [];
    $items = array_values(array_filter(array_map(static fn ($item) => trim((string)$item), $items)));

    return $items !== [] ? $items : ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
}

function image_upload_allowed_extensions_from_mime(array $mimeTypes)
{
    $map = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/gif' => ['gif'],
        'image/webp' => ['webp'],
    ];

    $extensions = [];
    foreach ($mimeTypes as $mimeType) {
        foreach ($map[$mimeType] ?? [] as $extension) {
            $extensions[$extension] = true;
        }
    }

    return array_keys($extensions);
}

function image_upload_user_quota_usage_bytes($userId)
{
    $userId = (int)$userId;
    if ($userId < 1 || !is_dir(IMAGES . 'avatars')) {
        return 0;
    }

    $usage = 0;
    foreach ((array)glob(IMAGES . 'avatars' . DIRECTORY_SEPARATOR . '*') as $path) {
        if (!is_file($path)) {
            continue;
        }

        if (preg_match('/(?:^|[-_])' . preg_quote((string)$userId, '/') . '(?:[-_]|$)/', basename($path))) {
            $usage += (int)@filesize($path);
        }
    }

    return $usage;
}

function validate_image_upload_policy(array $file, $context = 'Paveiksliukas', $userId = null)
{
    $policy = image_upload_policy_defaults();

    if (!$policy['enabled']) {
        return [false, $context . ' ikelimas siuo metu isjungtas.'];
    }

    [$ok, $validated] = validate_upload_file($file, [
        'required' => true,
        'max_size' => $policy['max_size_kb'] * 1024,
        'allowed_extensions' => image_upload_allowed_extensions_from_mime($policy['allowed_mime']),
        'allowed_mime_types' => $policy['allowed_mime'],
        'verify_image' => true,
    ]);

    if (!$ok) {
        return [false, $validated];
    }

    $imageInfo = @getimagesize($validated['tmp_name']);
    if ($imageInfo === false) {
        return [false, $context . ' nera galiojantis paveiksliukas.'];
    }

    $width = (int)($imageInfo[0] ?? 0);
    $height = (int)($imageInfo[1] ?? 0);

    if ($width > $policy['max_width'] || $height > $policy['max_height']) {
        return [false, $context . ' virsija leistinus matmenis (' . $policy['max_width'] . 'x' . $policy['max_height'] . ').'];
    }

    $quotaMb = (int)$policy['quota_per_user_mb'];
    if ($quotaMb > 0 && $userId !== null) {
        $currentUsage = image_upload_user_quota_usage_bytes((int)$userId);
        $projectedUsage = $currentUsage + (int)$validated['size'];
        $quotaBytes = $quotaMb * 1024 * 1024;

        if ($projectedUsage > $quotaBytes) {
            return [false, $context . ' virsija vartotojo kvota (' . $quotaMb . ' MB).'];
        }
    }

    $validated['width'] = $width;
    $validated['height'] = $height;

    return [true, $validated];
}
