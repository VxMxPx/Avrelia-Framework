<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

# Set routes
$AvreliaConfig['system']['routes'] = array
(
	# If there's no parameteres set in our URL, this will be called.
	0 => 'home->index()',

	# The 404 route.
	# If not provided / not found, the system will look for 404.php view;
	# if that won't be found either, only 404 plain message will be shown.
	404 => 'home->not_found_404()',

	# Match home/$method/$Parameters
	# Controller and method can consist only of: a-z 0-9 _
	# Parameter can be any length and contain (almost) any character.
	'/([a-z0-9_-]*)\/?([a-zA-Z0-9\/!=\-+_.,;?]*)/' => 'home->%1(%2)',

	# Match $controller/$method/$Parametera
	# Controller and method can consist only of: a-z 0-9 _
	# Parameter can be any length and contain (almost) any character.
	'/([a-z0-9_-]*)\/?([a-z0-9_-]*)\/?([a-zA-Z0-9\/!=\-+_.,;?]*)/' => '%1->%2(%3)',
);

# List of enabled plugs
$AvreliaConfig['plug']['enabled'] = array('html', 'jquery');

# For more configurations see system/config/main.php
