<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

$database_config = array
(
    'driver' => 'sqlite', # Driver, sqlite/mysql
    'sqlite' => array     # SQLite Settings
    (
        # Database filename
        'filename' => 'default.sqlite',
    ),
    'mysql' => array # MySQL Settings
    (
        'username' => '',
        'password' => '',
        'database' => '',
        'hostname' => 'localhost',
    ),
);
