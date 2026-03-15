<?php
function bbcode_to_html($text)
{
    $text = e($text);
    $patterns = [
        '/\[b\](.*?)\[\/b\]/is' => '<strong>$1</strong>',
        '/\[i\](.*?)\[\/i\]/is' => '<em>$1</em>',
        '/\[u\](.*?)\[\/u\]/is' => '<u>$1</u>',
        '/\[code\](.*?)\[\/code\]/is' => '<code>$1</code>',
        '/\[quote\](.*?)\[\/quote\]/is' => '<blockquote>$1</blockquote>',
        '/\[url=(.*?)\](.*?)\[\/url\]/is' => '<a href="$1" rel="nofollow noopener" target="_blank">$2</a>',
    ];
    foreach ($patterns as $pattern => $replace) {
        $text = preg_replace($pattern, $replace, $text);
    }
    return nl2br($text);
}
