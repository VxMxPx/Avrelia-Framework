<?php if (!defined('AVRELIA')) die('Access is denied!');

$minify_config = array(
    'javascript' => array(
        'enabled' => true,
        'input'   => pub_path('js/main.dev.js'),
        'output'  => pub_path('js/main.all.js'),
        'coffee'  => false,
        'uglify'  => true,
    ),
    'css' => array(
        'enabled' => true,
    ),
);