<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

$SessionConfig = array
(
	# Login Expires: default = One week;
	# If you want the session to expire on browser's window close,
	# then set this value to 0.
	'expires'		=> 60*60*24*7,

	# The name of the cookie set for session
	'cookie_name'	=> 'csession',

	# For valid session require the user's IP to match
	'require_ip'	=> false,

	# For valid session require the user's agent to match
	'require_agent'	=> true,

	# Driver:
	#   json: for flat file storage
	#   db  : use database
	'driver'		=> 'json',

	# JSON driver configuration
	'json'			=> array
	(
		'users_filename'	=> Plug::GetDatabasePath('session/users.json'),
		'sessions_filename'	=> Plug::GetDatabasePath('session/sessions.json'),
	),

	# Database driver configuration
	'db'		=> array
	(
		# Table must have at least following fields: id, uname, password, active
		'users_table'		=> 'users',

		# Table must have at least following fields: id, user_id, ip, agent, expires
		'sessions_table'	=> 'users_sessions',

		# Tables to be auto-created if not expists
		'Tables'			=> array(
			'users_table'	=>
				'CREATE TABLE IF NOT EXISTS users (
					id			INT	PRIMARY KEY	AUTOINCREMENT	NOT NULL,
					uname		VARCHAR(200)					NOT NULL,
					password	TEXT							NOT NULL,
					active		INT(1)							NOT NULL
				)',
			'sessions_table' =>
				'CREATE TABLE IF NOT EXISTS users_sessions (
					id			VARCHAR(255)	NOT NULL,
					user_id		VARCHAR(255)	NOT NULL,
					ip			VARCHAR(16)		NOT NULL,
					agent		VARCHAR(255)	NOT NULL,
					expires		INT(12)			NOT NULL
				)',
		),
	),

	# Default users to insert upon initialization of this plug
	'defaults'   => array
	(
		array(
			# root@domain.tld / root
			'uname'		=> 'root@domain.tld',
			'password'	=> 'root',
		),
	),

);
