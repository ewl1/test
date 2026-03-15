<?php
require_once dirname(__DIR__) . '/include/bootstrap.php';
require_login_page();
require_permission($pdo, 'admin.access');
