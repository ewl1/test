<?php
function site_locale()
{
    static $locale = null;
    if ($locale !== null) {
        return $locale;
    }

    $candidate = (string)setting('site_locale', 'lt');
    if (!preg_match('/^[a-z]{2}(?:-[A-Z]{2})?$/', $candidate)) {
        $candidate = 'lt';
    }

    $localePath = BASEDIR . 'locale/' . $candidate . '.php';
    if (!is_file($localePath)) {
        $candidate = 'lt';
    }

    $locale = $candidate;
    return $locale;
}

function locale_messages()
{
    static $messages = null;
    if ($messages !== null) {
        return $messages;
    }

    $messages = [];
    $localePath = BASEDIR . 'locale/' . site_locale() . '.php';
    if (is_file($localePath)) {
        $loaded = require $localePath;
        if (is_array($loaded)) {
            $messages = $loaded;
        }
    }

    return $messages;
}

function __($key, array $replace = [], $default = null)
{
    $messages = locale_messages();
    $text = array_key_exists($key, $messages)
        ? $messages[$key]
        : ($default !== null ? $default : $key);

    foreach ($replace as $name => $value) {
        $text = str_replace(':' . $name, (string)$value, (string)$text);
    }

    return (string)$text;
}
