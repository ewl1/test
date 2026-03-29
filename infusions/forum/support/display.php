<?php
function forum_render_node_visual(array $node, $class = 'forum-node-visual')
{
    $pictureStyle = forum_picture_style();
    $imageUrl = trim((string)($node['image_url'] ?? ''));
    $iconClass = trim((string)($node['icon_class'] ?? forum_default_icon_for_type($node['forum_type'] ?? 'forum')));

    if ($pictureStyle === 'image' && $imageUrl !== '') {
        return '<span class="' . e($class) . ' forum-node-visual-image"><img src="' . escape_url($imageUrl) . '" alt=""></span>';
    }

    return '<span class="' . e($class) . ' forum-node-visual-icon"><i class="' . e($iconClass !== '' ? $iconClass : 'fa-solid fa-comments') . '" aria-hidden="true"></i></span>';
}

