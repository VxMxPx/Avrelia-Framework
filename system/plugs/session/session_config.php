<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

$SessionConfig = array
(
	'expires'   => 60*60*24*7, # Login Expires: default = One week
	'driver'    => 'json',     # Driver, only json available for now
	'json'      => array       # JSON driver configuration
	(
		'users_filename'    => Plug::GetDatabasePath('session/users.json'),
		'sessions_filename' => Plug::GetDatabasePath('session/sessions.json'),
	),
	'users'   => array
	(
		'bba1f3f3ec912d259ab7e9ad979351b9a33ead50' => array
		(
			# root@domain.tld / root
			'id'       => 'bba1f3f3ec912d259ab7e9ad979351b9a33ead50',
			'email'    => 'root@domain.tld',
			'password' => '6750aa99153180d387e4a9d8f5205e955faabeb4',
			'salt'     => '-UM0W'
		),
	),
);
