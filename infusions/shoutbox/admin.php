<?php

require_permission('shoutbox.moderate');
require_once __DIR__ . '/support/load.php';

shoutbox_handle_admin_request();
shoutbox_render_admin_page();
