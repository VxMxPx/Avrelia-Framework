<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

# Set routes
$avrelia_config['system']['routes'] = array
(
    # If there's no parameteres set in our URL, this will be called.
    '<index>' => 'home->index()',

    # The 404 route.
    # If not provided / not found, the system will look for 404.php view;
    # if that won't be found either, only 404 plain message will be shown.
    '<404>'   => 'home->not_found_404()',

    # Match home/$method/$Parameters
    # Controller and method can consist only of: a-z 0-9 _
    # Parameter can be any length and contain (almost) any character.
    '<az09_->/?<*>' => 'home->%1(%2)',

    # Match $controller/$method/$Parametera
    # Controller and method can consist only of: a-z 0-9 _
    # Parameter can be any length and contain (almost) any character.
    '<az09_->/?<1>/<*>' => '%1->%2(%3)',
);

# List of enabled plugs + map
$avrelia_config['plug']['enabled']   = array(
    'Avrelia\\Plug\\Cookie',
    'Avrelia\\Plug\\Cache',
    'Avrelia\\Plug\\Database',
    'Avrelia\\Plug\\Form',
    'Avrelia\\Plug\\Image',
    'Avrelia\\Plug\\Mailer',
    'Avrelia\\Plug\\Session',
    'Avrelia\\Plug\\Validate',
    'Avrelia\\Plug\\HTML',
    'Avrelia\\Plug\\JQuery',
    'Avrelia\\Plug\\Debug'
);

# List of plugs which will be auto-loaded
$avrelia_config['plug']['auto_load'] = array(
    'Avrelia\\Plug\\Cache',
    'Avrelia\\Plug\\Session',
    'Avrelia\\Plug\\Debug'
);

# For more configurations see system/config/main.php