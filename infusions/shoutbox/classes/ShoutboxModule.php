<?php

namespace App\Shoutbox;

use App\MiniCMS\Infusions\AbstractInfusionModule;
use App\MiniCMS\Infusions\ModuleSearchContract;
use App\MiniCMS\Infusions\ModuleSettingsContract;

final class ShoutboxModule extends AbstractInfusionModule implements ModuleSearchContract, ModuleSettingsContract
{
    public function boot(): void
    {
        $this->registerStyle('assets/css/shoutbox.css');
        $this->registerScript('assets/js/shoutbox.js');
    }

    public function searchMetadata(): array
    {
        return [
            [
                'key' => 'shoutbox_messages',
                'indexed_fields' => ['message'],
                'result_url' => 'shoutbox.php',
                'title' => 'username',
                'summary' => 'message',
                'category' => 'shoutbox',
                'type' => 'message',
                'permission_filter' => null,
                'weight' => 0.6,
            ],
        ];
    }

    public function settingsSections(): array
    {
        return [
            [
                'key' => 'shoutbox_general',
                'title' => 'Šaukyklos nustatymai',
                'description' => 'Valdo žinučių eiliškumą ir rodymą puslapyje bei panelėje.',
                'icon' => 'fa-solid fa-comment-dots',
            ],
        ];
    }

    public function settingsFormSchema(): array
    {
        return [
            [
                'key' => 'shoutbox_order',
                'type' => 'select',
                'label' => 'Žinučių eiliškumas',
                'section' => 'shoutbox_general',
                'default' => 'desc',
                'options' => [
                    'desc' => 'Naujausios viršuje',
                    'asc' => 'Seniausios viršuje',
                ],
            ],
            [
                'key' => 'shoutbox_messages_per_page',
                'type' => 'number',
                'label' => 'Žinučių puslapyje',
                'section' => 'shoutbox_general',
                'default' => '20',
            ],
            [
                'key' => 'shoutbox_panel_messages',
                'type' => 'number',
                'label' => 'Žinučių panelėje',
                'section' => 'shoutbox_general',
                'default' => '5',
            ],
        ];
    }

    public function settingsValidationRules(): array
    {
        return [
            'shoutbox_order' => [
                'required' => true,
                'type' => 'string',
                'choices' => ['asc', 'desc'],
            ],
            'shoutbox_messages_per_page' => [
                'type' => 'int',
                'min' => 5,
                'max' => 100,
            ],
            'shoutbox_panel_messages' => [
                'type' => 'int',
                'min' => 3,
                'max' => 20,
            ],
        ];
    }
}
