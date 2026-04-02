<?php

namespace App\Downloads;

use App\MiniCMS\Infusions\AbstractInfusionModule;
use App\MiniCMS\Infusions\ModuleSearchContract;
use App\MiniCMS\Infusions\ModuleSettingsContract;

final class DownloadsModule extends AbstractInfusionModule implements ModuleSearchContract, ModuleSettingsContract
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
                'key'               => 'downloads',
                'indexed_fields'    => ['download_title', 'download_description'],
                'result_url'        => 'downloads.php?action=download&id={id}',
                'title'             => 'download_title',
                'summary'           => 'download_description',
                'category'          => 'downloads',
                'type'              => 'download',
                'permission_filter' => 'downloads.view',
                'weight'            => 1.0,
            ],
        ];
    }

    public function settingsSections(): array
    {
        return [
            [
                'key' => 'downloads_general',
                'title' => 'Siuntinių nustatymai',
                'description' => 'Valdo failų dydžius, miniatiūras ir bendrą modulio elgseną.',
                'icon' => 'fa-solid fa-download',
            ],
        ];
    }

    public function settingsFormSchema(): array
    {
        return [
            [
                'key' => 'max_file_size',
                'type' => 'number',
                'label' => 'Maksimalus failo dydis',
                'section' => 'downloads_general',
                'default' => '52428800',
            ],
            [
                'key' => 'show_thumbnails',
                'type' => 'checkbox',
                'label' => 'Rodyti miniatiūras',
                'section' => 'downloads_general',
                'default' => '1',
            ],
        ];
    }

    public function settingsValidationRules(): array
    {
        return [
            'max_file_size' => [
                'required' => true,
                'type' => 'int',
                'min' => 1048576,
            ],
            'show_thumbnails' => [
                'type' => 'bool',
            ],
        ];
    }
}
