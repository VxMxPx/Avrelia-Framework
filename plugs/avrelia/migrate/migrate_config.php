<?php if (!defined('AVRELIA')) die('Access is denied!');

$migrate_config = array(
    // Be sure that this directory exists before using it!!
    'directory'    => app_path('migrations'),

    // Table to which migration status will be saved
    'status_table' => 'avrelia_migrations',
);