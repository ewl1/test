<?php

function security_headers_defaults()
{
    return [
        'security_headers_enabled' => '1',
        'security_header_hsts' => '1',
        'security_header_frame_options' => '1',
        'security_header_content_type_options' => '1',
        'security_header_referrer_policy' => 'strict-origin-when-cross-origin',
        'security_header_permissions_policy' => 'camera=(), microphone=(), geolocation=()',
        'security_header_coop' => 'same-origin',
        'security_header_corp' => 'same-site',
    ];
}

function security_header_setting($key)
{
    $defaults = security_headers_defaults();
    $fallback = $defaults[$key] ?? '';

    if (function_exists('setting')) {
        return (string)setting($key, $fallback);
    }

    return (string)$fallback;
}

function security_headers_manager_config()
{
    return [
        'enabled' => security_header_setting('security_headers_enabled') !== '0',
        'hsts' => security_header_setting('security_header_hsts') !== '0',
        'frame_options' => security_header_setting('security_header_frame_options') !== '0',
        'content_type_options' => security_header_setting('security_header_content_type_options') !== '0',
        'referrer_policy' => trim(security_header_setting('security_header_referrer_policy')),
        'permissions_policy' => trim(security_header_setting('security_header_permissions_policy')),
        'coop' => trim(security_header_setting('security_header_coop')),
        'corp' => trim(security_header_setting('security_header_corp')),
    ];
}

function security_headers_csp_value()
{
    return implode('; ', [
        "default-src 'self'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'self'",
        "img-src 'self' data: https:",
        "style-src 'self'",
        "style-src-elem 'self'",
        "style-src-attr 'none'",
        "script-src 'self'",
        "script-src-elem 'self'",
        "script-src-attr 'none'",
        "font-src 'self' data:",
        "connect-src 'self'",
        "frame-src 'self' https://www.youtube-nocookie.com",
        "manifest-src 'self'",
        "media-src 'self'",
        "object-src 'none'",
    ]);
}

function security_headers_active_list()
{
    $config = security_headers_manager_config();
    $headers = [
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        'Pragma' => 'no-cache',
        'Expires' => '0',
        'Content-Security-Policy' => security_headers_csp_value(),
    ];

    if ($config['coop'] !== '') {
        $headers['Cross-Origin-Opener-Policy'] = $config['coop'];
    }

    if ($config['corp'] !== '') {
        $headers['Cross-Origin-Resource-Policy'] = $config['corp'];
    }

    if ($config['frame_options']) {
        $headers['X-Frame-Options'] = 'SAMEORIGIN';
    }

    if ($config['content_type_options']) {
        $headers['X-Content-Type-Options'] = 'nosniff';
    }

    if ($config['referrer_policy'] !== '') {
        $headers['Referrer-Policy'] = $config['referrer_policy'];
    }

    if ($config['permissions_policy'] !== '') {
        $headers['Permissions-Policy'] = $config['permissions_policy'];
    }

    if ($config['hsts'] && function_exists('request_is_secure') && request_is_secure()) {
        $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
    }

    return $headers;
}

function send_security_headers()
{
    if (headers_sent()) {
        return;
    }

    $config = security_headers_manager_config();
    if (!$config['enabled']) {
        return;
    }

    header_remove('X-Powered-By');

    foreach (security_headers_active_list() as $name => $value) {
        header($name . ': ' . $value);
    }
}

