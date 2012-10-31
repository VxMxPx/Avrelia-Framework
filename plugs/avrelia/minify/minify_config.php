<?php if (!defined('AVRELIA')) die('Access is denied!');

$minify_config = array(
    'javascript' => array(
        'enabled' => true,
        'coffee'  => false,
        'uglify'  => true,
        // Enter BOTH directory and filename
        'input'   => pub_path('js/main.dev.js'),
        'output'  => pub_path('js/main.js'),
    ),
    'css' => array(
        'enabled' => true,
        // Enter ONLY directoried as input and output
        'input'   => pub_path('css/main.stylus'),
        'output'  => pub_path('css/'),
    ),
);