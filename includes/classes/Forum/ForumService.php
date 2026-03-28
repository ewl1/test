<?php

namespace App\Forum;

class ForumService
{
    public function getTopic(int $topicId): ?array
    {
        return function_exists('forum_get_topic') ? (forum_get_topic($topicId) ?: null) : null;
    }

    public function createTopic(int $forumId, string $title, string $content): array
    {
        if (!function_exists('forum_create_topic')) {
            return [false, 'Forum topic service is unavailable.', null];
        }

        return forum_create_topic($forumId, $title, $content);
    }

    public function createReply(int $topicId, string $content): array
    {
        if (!function_exists('forum_create_reply')) {
            return [false, 'Forum reply service is unavailable.', null];
        }

        return forum_create_reply($topicId, $content);
    }
}
