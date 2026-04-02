<?php

function badwords_enabled()
{
    return setting('badwords_enabled', '1') !== '0';
}

function badwords_terms()
{
    $raw = trim((string)setting('badwords_list', ''));
    if ($raw === '') {
        $raw = trim((string)setting('content_comments_badwords', ''));
    }

    if ($raw === '') {
        return [];
    }

    $parts = preg_split('/[\r\n,;]+/u', $raw) ?: [];
    $terms = [];
    foreach ($parts as $part) {
        $term = trim(mb_strtolower((string)$part));
        if ($term !== '') {
            $terms[] = $term;
        }
    }

    return array_values(array_unique($terms));
}

function badwords_match($text)
{
    if (!badwords_enabled()) {
        return null;
    }

    $haystack = mb_strtolower(trim((string)$text));
    if ($haystack === '') {
        return null;
    }

    foreach (badwords_terms() as $term) {
        if ($term !== '' && mb_stripos($haystack, $term) !== false) {
            return $term;
        }
    }

    return null;
}

function badwords_validate($text, $label = 'Turinyje')
{
    $match = badwords_match($text);
    if ($match === null) {
        return [true, null];
    }

    return [false, $label . ' yra neleistinas zodis: ' . $match . '.'];
}
