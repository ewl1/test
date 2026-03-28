<?php

namespace App\MiniCMS\Infusions;

abstract class SimplePanelModule extends AbstractInfusionModule
{
    abstract protected function panelTitle(array $panelData = []): string;

    abstract protected function panelBody(array $panelData = []): string;

    protected function panelOptions(array $panelData = []): array
    {
        return [];
    }

    public function renderPanel(array $panelData = []): string
    {
        return \render_side_panel(
            $this->panelTitle($panelData),
            $this->panelBody($panelData),
            $this->panelOptions($panelData)
        );
    }
}
