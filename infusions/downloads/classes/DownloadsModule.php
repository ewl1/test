<?php

namespace App\Downloads;

use App\MiniCMS\Infusions\AbstractInfusionModule;
use App\MiniCMS\Infusions\ModuleSearchContract;

final class DownloadsModule extends AbstractInfusionModule implements ModuleSearchContract
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
}