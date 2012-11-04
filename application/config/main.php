<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

# List of enabled plugs + map
$avrelia_config['core']['plug']['enabled']   = array(
    'Plug\\Avrelia\\HTML',
    'Plug\\Avrelia\\JQuery',
    'Plug\\Avrelia\\LogWritter',
    'Plug\\Avrelia\\Debug',
);

# List of plugs which will be auto-loaded
$avrelia_config['core']['plug']['auto_load'] = array(
    'Plug\\Avrelia\\LogWritter',
    'Plug\\Avrelia\\Debug',
);

# For more configurations see system/config/main.php