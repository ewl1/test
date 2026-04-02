<?php

function editor_normalize_mode($mode, array $allowedModes = ['bbcode', 'tinymce', 'mixed'], $default = 'bbcode')
{
    $mode = trim((string)$mode);
    return in_array($mode, $allowedModes, true) ? $mode : $default;
}

function editor_register_tinymce_assets()
{
    register_page_script('includes/js/tinymce/tinymce.min.js');
}

function editor_tinymce_default_config(array $overrides = [])
{
    $config = [
        'menubar' => false,
        'branding' => false,
        'promotion' => false,
        'height' => 320,
        'plugins' => 'link lists image code emoticons',
        'toolbar' => 'undo redo | bold italic underline | bullist numlist blockquote | link image emoticons | removeformat code',
        'block_formats' => 'Paragraph=p;Heading 2=h2;Heading 3=h3',
        'convert_urls' => false,
        'relative_urls' => false,
    ];

    foreach ($overrides as $key => $value) {
        $config[$key] = $value;
    }

    return $config;
}

function editor_tinymce_config_json(array $overrides = [])
{
    return json_encode(
        editor_tinymce_default_config($overrides),
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    ) ?: '{}';
}
