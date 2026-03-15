<?php
function paginate($total, $per_page = 10, $page = 1)
{
    $total = max(0, (int)$total); $per_page = max(1, (int)$per_page); $page = max(1, (int)$page);
    return ['total'=>$total, 'per_page'=>$per_page, 'page'=>$page, 'pages'=>(int)ceil($total/$per_page), 'offset'=>($page-1)*$per_page];
}
function render_pagination($base_url, array $pager)
{
    if (($pager['pages'] ?? 0) < 2) return '';
    $html = '<nav><ul class="pagination">';
    for ($i=1; $i<=$pager['pages']; $i++) {
        $sep = str_contains($base_url, '?') ? '&' : '?';
        $active = $i === (int)$pager['page'] ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . e($base_url . $sep . 'page=' . $i) . '">' . $i . '</a></li>';
    }
    return $html . '</ul></nav>';
}
