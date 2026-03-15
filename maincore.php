<?php
if (!defined('BASEDIR')) define('BASEDIR', __DIR__ . '/');

define("ADMIN", BASEDIR."administration/");
define("CLASSES", BASEDIR."includes/classes/");
define("INFUSIONS", BASEDIR."infusions/");
define("IMAGES", BASEDIR."images/");
define("INCLUDES", BASEDIR."includes/");
define("THEMES", BASEDIR."themes/");

require_once BASEDIR . 'config.php';

if (!defined('CURRENT_THEME')) define('CURRENT_THEME', 'default');
if (!defined('ADMIN_THEME')) define('ADMIN_THEME', CURRENT_THEME);
if (!defined('TIMEZONE')) define('TIMEZONE', 'Europe/Vilnius');
date_default_timezone_set(TIMEZONE);

function public_path($path = '') { return SITE_URL . '/' . ltrim($path, '/'); }
function redirect($path) { header('Location: ' . $path); exit; }
