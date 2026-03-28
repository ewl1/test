<?php
function register_page_style($path)
{
    $path = ltrim((string)$path, '/');
    if ($path === '') {
        return;
    }

    if (!isset($GLOBALS['registered_page_styles']) || !is_array($GLOBALS['registered_page_styles'])) {
        $GLOBALS['registered_page_styles'] = [];
    }

    $GLOBALS['registered_page_styles'][$path] = $path;
}

function register_page_script($path)
{
    $path = ltrim((string)$path, '/');
    if ($path === '') {
        return;
    }

    if (!isset($GLOBALS['registered_page_scripts']) || !is_array($GLOBALS['registered_page_scripts'])) {
        $GLOBALS['registered_page_scripts'] = [];
    }

    $GLOBALS['registered_page_scripts'][$path] = $path;
}

function get_registered_page_styles()
{
    return array_values($GLOBALS['registered_page_styles'] ?? []);
}

function get_registered_page_scripts()
{
    return array_values($GLOBALS['registered_page_scripts'] ?? []);
}
