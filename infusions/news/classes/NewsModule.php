<?php

namespace App\News;

use App\MiniCMS\Infusions\AbstractInfusionModule;
use App\MiniCMS\Infusions\ModuleSearchContract;
use App\MiniCMS\Infusions\ModuleSettingsContract;

final class NewsModule extends AbstractInfusionModule implements ModuleSearchContract, ModuleSettingsContract
{
    public function boot(): void
    {
        $this->registerStyle('assets/css/' . $this->context->folder() . '.css');
        $this->registerScript('assets/js/' . $this->context->folder() . '.js');
    }

    public function searchMetadata(): array
    {
        return [
            [
                'key' => 'news',
                'indexed_fields' => ['title', 'summary', 'slug'],
                'result_url' => 'news.php?id={id}',
                'title' => 'title',
                'summary' => 'summary',
                'category' => 'news',
                'type' => 'article',
                'permission_filter' => null,
                'weight' => 1.0,
            ],
        ];
    }

    public function settingsSections(): array
    {
        return [
            [
                'key' => 'news_editor',
                'title' => 'Naujienų redaktorius',
                'description' => 'Valdo, ar naujienoms naudojamas BBCode, TinyMCE ar mišrus režimas.',
                'icon' => 'fa-solid fa-newspaper',
            ],
        ];
    }

    public function settingsFormSchema(): array
    {
        return [
            [
                'key' => 'editor_mode',
                'type' => 'select',
                'label' => 'Redaktoriaus režimas',
                'section' => 'news_editor',
                'default' => 'bbcode',
                'options' => [
                    'bbcode' => 'Tik BBCode',
                    'tinymce' => 'Tik TinyMCE',
                    'mixed' => 'TinyMCE + BBCode',
                ],
            ],
        ];
    }

    public function settingsValidationRules(): array
    {
        return [
            'editor_mode' => [
                'required' => true,
                'type' => 'string',
                'choices' => ['bbcode', 'tinymce', 'mixed'],
            ],
        ];
    }
}
