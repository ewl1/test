<?php

namespace App\Shoutbox;

final class ShoutboxService
{
    public function recentMessages(int $limit = 20, int $offset = 0, ?string $order = null): array
    {
        return function_exists('shoutbox_get_messages')
            ? shoutbox_get_messages($limit, $offset, $order)
            : [];
    }

    public function createMessage(string $message): array
    {
        if (!function_exists('shoutbox_create_message')) {
            return [false, 'Shoutbox service is unavailable.'];
        }

        return shoutbox_create_message($message);
    }

    public function deleteMessage(int $messageId): bool
    {
        if (!function_exists('shoutbox_delete_message')) {
            return false;
        }

        shoutbox_delete_message($messageId);
        return true;
    }
}
