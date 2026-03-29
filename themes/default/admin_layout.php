<?php

if (!function_exists('admin_layout_preset_class')) {
    function admin_layout_preset_class(string $preset, string $extra = ''): string
    {
        $classes = ['admin-layout'];
        $preset = trim(strtolower($preset));
        if ($preset !== '') {
            $preset = (string)preg_replace('/[^a-z0-9_-]+/', '-', $preset);
            $classes[] = 'admin-layout-' . $preset;
        }

        $extra = trim($extra);
        if ($extra !== '') {
            $classes[] = $extra;
        }

        return implode(' ', $classes);
    }
}

if (!function_exists('admin_render_page_header')) {
    function admin_render_page_header(array $config = []): void
    {
        $title = trim((string)($config['title'] ?? ''));
        $subtitle = trim((string)($config['subtitle'] ?? ''));
        $badgeHtml = (string)($config['badge_html'] ?? '');
        $actionsHtml = (string)($config['actions_html'] ?? '');
        $variant = trim((string)($config['variant'] ?? 'default'));
        $variant = (string)preg_replace('/[^a-z0-9_-]+/i', '-', $variant);
        $headerClass = 'admin-layout-header';
        if ($variant !== '') {
            $headerClass .= ' admin-layout-header--' . strtolower($variant);
        }

        $actions = $config['actions'] ?? [];
        if (!is_array($actions)) {
            $actions = [];
        }

        echo '<div class="' . e($headerClass) . '">';
        echo '<div class="admin-layout-header-main">';
        if ($title !== '') {
            echo '<h1 class="admin-layout-title">' . e($title) . '</h1>';
        }
        if ($subtitle !== '') {
            echo '<div class="admin-layout-subtitle">' . e($subtitle) . '</div>';
        }
        echo '</div>';

        if ($badgeHtml !== '' || $actionsHtml !== '' || $actions !== []) {
            echo '<div class="admin-layout-header-actions">';
            if ($badgeHtml !== '') {
                echo '<div class="admin-layout-header-meta">' . $badgeHtml . '</div>';
            }

            foreach ($actions as $action) {
                if (!is_array($action)) {
                    continue;
                }

                $label = trim((string)($action['label'] ?? ''));
                $href = trim((string)($action['href'] ?? ''));
                if ($label === '' || $href === '') {
                    continue;
                }

                $class = trim((string)($action['class'] ?? 'btn btn-outline-secondary admin-action-button'));
                $icon = trim((string)($action['icon'] ?? ''));
                $titleAttr = trim((string)($action['title'] ?? ''));
                $target = trim((string)($action['target'] ?? ''));

                echo '<a class="' . e($class) . '" href="' . e($href) . '"';
                if ($titleAttr !== '') {
                    echo ' title="' . e($titleAttr) . '"';
                }
                if ($target !== '') {
                    echo ' target="' . e($target) . '"';
                }
                echo '>';
                if ($icon !== '') {
                    echo '<i class="' . e($icon) . '" aria-hidden="true"></i> ';
                }
                echo '<span>' . e($label) . '</span></a>';
            }

            if ($actionsHtml !== '') {
                echo $actionsHtml;
            }

            echo '</div>';
        }

        echo '</div>';
    }
}

if (!function_exists('admin_render_stat_strip')) {
    function admin_render_stat_strip(array $items): void
    {
        if ($items === []) {
            return;
        }

        echo '<div class="admin-layout-stat-strip">';
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $label = trim((string)($item['label'] ?? ''));
            $value = trim((string)($item['value'] ?? ''));
            if ($label === '' && $value === '') {
                continue;
            }

            $tone = trim((string)($item['tone'] ?? 'default'));
            $tone = (string)preg_replace('/[^a-z0-9_-]+/i', '-', $tone);
            $class = 'admin-layout-stat';
            if ($tone !== '') {
                $class .= ' admin-layout-stat--' . strtolower($tone);
            }

            $icon = trim((string)($item['icon'] ?? ''));

            echo '<div class="' . e($class) . '">';
            if ($icon !== '') {
                echo '<div class="admin-layout-stat-icon"><i class="' . e($icon) . '" aria-hidden="true"></i></div>';
            }
            echo '<div class="admin-layout-stat-body">';
            if ($label !== '') {
                echo '<div class="admin-layout-stat-label">' . e($label) . '</div>';
            }
            if ($value !== '') {
                echo '<div class="admin-layout-stat-value">' . e($value) . '</div>';
            }
            echo '</div></div>';
        }
        echo '</div>';
    }
}
