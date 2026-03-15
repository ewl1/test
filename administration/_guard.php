<?php
define('IN_ADMIN', true);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
require_permission('admin.access');
