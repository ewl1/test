<?php
session_start();
require_once dirname(__DIR__) . '/maincore.php';
require_once INCLUDES . 'db.php';
require_once INCLUDES . 'security.php';
require_once INCLUDES . 'settings.php';
require_once INCLUDES . 'auth.php';
require_once INCLUDES . 'validation.php';
require_once INCLUDES . 'permissions.php';
require_once INCLUDES . 'audit.php';
require_once INCLUDES . 'panels.php';
require_once INCLUDES . 'functions/pagination.php';
require_once INCLUDES . 'functions/output.php';

if ((setting('site_maintenance', MAINTENANCE_MODE ? '1' : '0') === '1') && !defined('IN_ADMIN')) {
    require BASEDIR . 'maintenance.php';
    exit;
}

require_once INCLUDES . 'infusions.php';
load_enabled_infusions();
