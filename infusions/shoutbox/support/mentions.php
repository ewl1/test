<?php

function shoutbox_normalize_mention_value($value)
{
    $value = preg_replace('/\s+/u', ' ', trim((string)$value));
    return mb_strtolower((string)$value);
}

function shoutbox_mention_directory()
{
    static $directory = null;
    if ($directory !== null) {
        return $directory;
    }

    $directory = [];

    try {
        $stmt = $GLOBALS['pdo']->query("
            SELECT id, username
            FROM users
            WHERE is_active = 1
            ORDER BY id ASC
        ");

        foreach ($stmt->fetchAll() as $user) {
            $username = trim((string)($user['username'] ?? ''));
            if ($username === '') {
                continue;
            }

            $directory[shoutbox_normalize_mention_value($username)] = [
                'id' => (int)($user['id'] ?? 0),
                'username' => $username,
            ];
        }
    } catch (Throwable $e) {
        $directory = [];
    }

    return $directory;
}

function shoutbox_replace_mentions_in_text($text)
{
    $directory = shoutbox_mention_directory();
    if ($text === '' || !$directory) {
        return $text;
    }

    $resolveMention = function ($rawValue) use ($directory) {
        $candidate = trim((string)$rawValue);
        $suffix = '';

        while ($candidate !== '') {
            $normalized = shoutbox_normalize_mention_value($candidate);
            if ($normalized !== '' && isset($directory[$normalized])) {
                return [$directory[$normalized], $suffix];
            }

            if (!preg_match('/([,!?;:)\].]+)$/u', $candidate, $matches)) {
                break;
            }

            $suffix = $matches[1] . $suffix;
            $candidate = mb_substr($candidate, 0, mb_strlen($candidate) - mb_strlen($matches[1]));
            $candidate = rtrim($candidate);
        }

        return [null, ''];
    };

    $replace = function ($matches) use ($resolveMention) {
        [$user, $suffix] = $resolveMention($matches[1] ?? '');
        if (!$user) {
            return $matches[0];
        }

        return '<a class="shoutbox-mention" href="' . e(user_profile_url((int)$user['id'])) . '">@' . e($user['username']) . '</a>' . $suffix;
    };

    $text = preg_replace_callback(
        '/(?<![\p{L}\p{N}_\-.])@"([^"\r\n]{2,50})"/u',
        $replace,
        (string)$text
    );

    return preg_replace_callback(
        '/(?<![\p{L}\p{N}_\-.])@([\p{L}\p{N}_\-.]{2,50})/u',
        $replace,
        (string)$text
    );
}

function shoutbox_apply_mentions($html)
{
    $parts = preg_split('/(<[^>]+>)/u', (string)$html, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (!is_array($parts)) {
        return (string)$html;
    }

    $insideCode = false;
    $insideAnchor = false;

    foreach ($parts as $index => $part) {
        if ($part === '') {
            continue;
        }

        if ($part[0] === '<') {
            if (preg_match('/^<\s*\/\s*(code|pre)\b/i', $part)) {
                $insideCode = false;
            } elseif (preg_match('/^<\s*\/\s*a\b/i', $part)) {
                $insideAnchor = false;
            } elseif (preg_match('/^<\s*(code|pre)\b/i', $part)) {
                $insideCode = true;
            } elseif (preg_match('/^<\s*a\b/i', $part)) {
                $insideAnchor = true;
            }

            continue;
        }

        if ($insideCode || $insideAnchor) {
            continue;
        }

        $parts[$index] = shoutbox_replace_mentions_in_text($part);
    }

    return implode('', $parts);
}
