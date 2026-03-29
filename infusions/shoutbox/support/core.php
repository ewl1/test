<?php

function shoutbox_register_assets()
{
    register_page_style('infusions/shoutbox/assets/css/shoutbox.css');
    register_page_script('infusions/shoutbox/assets/js/shoutbox.js');
}

function shoutbox_smileys()
{
    return site_smileys(true);
}

function shoutbox_table_name()
{
    return 'infusion_shoutbox_messages';
}

function shoutbox_allowed_tags()
{
    return ['b', 'i', 'u', 'quote', 'code', 'url'];
}

function shoutbox_default_order()
{
    return 'desc';
}

function shoutbox_normalize_order($value = null)
{
    $order = strtolower((string)($value ?? shoutbox_default_order()));
    return $order === 'asc' ? 'asc' : 'desc';
}

function shoutbox_message_order()
{
    return shoutbox_normalize_order(setting('shoutbox_order', shoutbox_default_order()));
}

function shoutbox_messages_per_page()
{
    $value = (int)setting('shoutbox_messages_per_page', '20');
    return max(5, min(100, $value));
}

function shoutbox_panel_messages_limit()
{
    $value = (int)setting('shoutbox_panel_messages', '5');
    return max(3, min(20, $value));
}

function shoutbox_count_messages()
{
    return (int)$GLOBALS['pdo']->query('SELECT COUNT(*) FROM ' . shoutbox_table_name())->fetchColumn();
}

function shoutbox_bbcode_buttons()
{
    return [
        ['label' => 'B', 'insert' => '[b][/b]'],
        ['label' => 'I', 'insert' => '[i][/i]'],
        ['label' => 'U', 'insert' => '[u][/u]'],
        ['label' => 'Code', 'insert' => '[code][/code]'],
        ['label' => 'Quote', 'insert' => '[quote][/quote]'],
        ['label' => 'Link', 'insert' => '[url=https://][/url]'],
    ];
}

function shoutbox_flash_key($context, $type)
{
    return 'shoutbox_' . $context . '_' . $type;
}

function shoutbox_plain_excerpt($message, $length = 120)
{
    $message = preg_replace('/\[(\/?)[a-z]+(?:=[^\]]*)?\]/i', '', (string)$message);
    $message = trim(preg_replace('/\s+/u', ' ', $message));
    if (mb_strlen($message) <= $length) {
        return $message;
    }

    return rtrim(mb_substr($message, 0, $length - 1)) . '...';
}
