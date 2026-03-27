<?php
$GLOBALS['pdo']->exec('DROP TABLE IF EXISTS ' . forum_table_posts());
$GLOBALS['pdo']->exec('DROP TABLE IF EXISTS ' . forum_table_topics());
$GLOBALS['pdo']->exec('DROP TABLE IF EXISTS ' . forum_table_forums());
$GLOBALS['pdo']->exec('DROP TABLE IF EXISTS ' . forum_table_categories());
$GLOBALS['pdo']->exec('DROP TABLE IF EXISTS infusion_forum_threads');
