<?php

require_permission('news.admin');
require_once __DIR__ . '/support/load.php';

news_handle_admin_request();
news_render_admin_page();
