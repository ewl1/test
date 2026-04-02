<?php

namespace App\Forum;

use App\MiniCMS\Infusions\AbstractInfusionModule;
use App\MiniCMS\Infusions\ModuleSearchContract;
use App\MiniCMS\Infusions\ModuleSettingsContract;

final class ForumModule extends AbstractInfusionModule implements ModuleSearchContract, ModuleSettingsContract
{
    public function boot(): void
    {
        $this->registerStyle('assets/css/forum.css');
        $this->registerScript('assets/js/forum.js');
    }

    public function searchMetadata(): array
    {
        return [
            [
                'key' => 'forum_topics',
                'indexed_fields' => ['title', 'body'],
                'result_url' => 'forum-topic.php?id={id}',
                'title' => 'title',
                'summary' => 'body',
                'category' => 'forum',
                'type' => 'thread',
                'permission_filter' => 'forum.view',
                'weight' => 1.0,
            ],
            [
                'key' => 'forum_posts',
                'indexed_fields' => ['message'],
                'result_url' => 'forum-topic.php?id={topic_id}#reply-{id}',
                'title' => 'topic_title',
                'summary' => 'message',
                'category' => 'forum',
                'type' => 'reply',
                'permission_filter' => 'forum.view',
                'weight' => 0.7,
            ],
        ];
    }

    public function settingsSections(): array
    {
        return [
            [
                'key' => 'forum_general',
                'title' => 'Bendri forumo nustatymai',
                'description' => 'Rodymo, reputacijos ir pranešimų elgsena.',
                'icon' => 'fa-solid fa-comments',
            ],
            [
                'key' => 'forum_posts',
                'title' => 'Pranešimų nustatymai',
                'description' => 'Priedų, redagavimo ir viešo rodymo taisyklės.',
                'icon' => 'fa-solid fa-file-lines',
            ],
        ];
    }

    public function settingsFormSchema(): array
    {
        return [
            ['key' => 'threads_per_page', 'type' => 'number', 'label' => 'Temų puslapyje', 'section' => 'forum_general', 'default' => '12'],
            ['key' => 'posts_per_page', 'type' => 'number', 'label' => 'Pranešimų puslapyje', 'section' => 'forum_general', 'default' => '10'],
            ['key' => 'recent_threads_limit', 'type' => 'number', 'label' => 'Paskutinės temos', 'section' => 'forum_general', 'default' => '5'],
            ['key' => 'popular_thread_days', 'type' => 'number', 'label' => 'Populiarios temos trukmė', 'section' => 'forum_general', 'default' => '14'],
            ['key' => 'thread_notification', 'type' => 'checkbox', 'label' => 'Temos pranešimai', 'section' => 'forum_general', 'default' => '0'],
            ['key' => 'show_reputation', 'type' => 'checkbox', 'label' => 'Rodyti reputaciją', 'section' => 'forum_general', 'default' => '1'],
            ['key' => 'enable_ranks', 'type' => 'checkbox', 'label' => 'Įjungti forumo rangus', 'section' => 'forum_general', 'default' => '1'],
            ['key' => 'max_photo_size_kb', 'type' => 'number', 'label' => 'Maks. nuotraukos dydis', 'section' => 'forum_posts', 'default' => '2048'],
            ['key' => 'attachments_max_size_kb', 'type' => 'number', 'label' => 'Priedų maks. dydis', 'section' => 'forum_posts', 'default' => '5120'],
            ['key' => 'attachments_max_count', 'type' => 'number', 'label' => 'Priedų kiekis', 'section' => 'forum_posts', 'default' => '5'],
            ['key' => 'allowed_file_types', 'type' => 'text', 'label' => 'Leidžiami failų tipai', 'section' => 'forum_posts', 'default' => 'jpg,jpeg,png,gif,webp,pdf,txt,zip'],
            ['key' => 'edit_time_limit_minutes', 'type' => 'number', 'label' => 'Redagavimo laiko limitas', 'section' => 'forum_posts', 'default' => '30'],
            ['key' => 'show_ip_publicly', 'type' => 'checkbox', 'label' => 'Rodyti IP viešai', 'section' => 'forum_posts', 'default' => '0'],
            ['key' => 'show_last_post_avatar', 'type' => 'checkbox', 'label' => 'Rodyti paskutinio pranešimo avatarą', 'section' => 'forum_posts', 'default' => '1'],
            ['key' => 'lock_edit', 'type' => 'checkbox', 'label' => 'Užrakinti redagavimą', 'section' => 'forum_posts', 'default' => '1'],
            ['key' => 'update_time_on_edit', 'type' => 'checkbox', 'label' => 'Atnaujinti laiką po redagavimo', 'section' => 'forum_posts', 'default' => '1'],
        ];
    }

    public function settingsValidationRules(): array
    {
        return [
            'threads_per_page' => ['type' => 'int', 'min' => 5, 'max' => 100],
            'posts_per_page' => ['type' => 'int', 'min' => 5, 'max' => 100],
            'recent_threads_limit' => ['type' => 'int', 'min' => 1, 'max' => 20],
            'popular_thread_days' => ['type' => 'int', 'min' => 1, 'max' => 365],
            'thread_notification' => ['type' => 'bool'],
            'show_reputation' => ['type' => 'bool'],
            'enable_ranks' => ['type' => 'bool'],
            'max_photo_size_kb' => ['type' => 'int', 'min' => 256],
            'attachments_max_size_kb' => ['type' => 'int', 'min' => 256],
            'attachments_max_count' => ['type' => 'int', 'min' => 0, 'max' => 20],
            'allowed_file_types' => ['type' => 'string'],
            'edit_time_limit_minutes' => ['type' => 'int', 'min' => 0, 'max' => 1440],
            'show_ip_publicly' => ['type' => 'bool'],
            'show_last_post_avatar' => ['type' => 'bool'],
            'lock_edit' => ['type' => 'bool'],
            'update_time_on_edit' => ['type' => 'bool'],
        ];
    }
}
