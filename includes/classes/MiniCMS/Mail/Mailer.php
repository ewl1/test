<?php

namespace App\MiniCMS\Mail;

class Mailer
{
    public function isAvailable(): bool
    {
        return function_exists('ensure_phpmailer_loaded') && ensure_phpmailer_loaded();
    }

    public function send(string $to, string $subject, string $html, ?string $text = null): bool
    {
        if (!function_exists('send_mail')) {
            return false;
        }

        return send_mail($to, $subject, $html, $text);
    }
}
