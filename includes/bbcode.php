<?php
function bbcode_to_html($text, array $options = [])
{
    $allowedTags = $options['allowed_tags'] ?? ['b', 'i', 'u', 'quote', 'code', 'url'];
    $maxLength = (int)($options['max_length'] ?? 5000);

    $text = sanitize_bbcode_input($text, $allowedTags, $maxLength);
    $text = escape_html($text);

    $patterns = [
        '/\[b\](.*?)\[\/b\]/is' => '<strong>$1</strong>',
        '/\[i\](.*?)\[\/i\]/is' => '<em>$1</em>',
        '/\[u\](.*?)\[\/u\]/is' => '<u>$1</u>',
        '/\[quote\](.*?)\[\/quote\]/is' => '<blockquote class="border-start ps-3 text-secondary">$1</blockquote>',
        '/\[code\](.*?)\[\/code\]/is' => '<code>$1</code>',
    ];

    foreach ($patterns as $pattern => $replace) {
        $text = preg_replace($pattern, $replace, $text);
    }

    $text = preg_replace_callback('/\[url=(https?:\/\/[^\]\s]+)\](.*?)\[\/url\]/is', function ($matches) {
        $url = trim((string)$matches[1]);
        $label = $matches[2];
        if (validate_url_value($url, true, 'Nuoroda', ['http', 'https'], false) !== null) {
            return $label;
        }

        return '<a href="' . escape_url($url) . '" target="_blank" rel="nofollow ugc noopener noreferrer">' . $label . '</a>';
    }, $text);

    return nl2br($text);
}
