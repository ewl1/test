<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login_page();
redirect(public_path('profile.php'));
