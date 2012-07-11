<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

# ============================================================ #
#                WARNING: DON'T EDIT THIS FILE!                #
# ------------------------------------------------------------ #
#  If you want to change anything, put the file with same name #
#      into application/config folder, to rewrite values.      #
# ============================================================ #


# Default System's Configurations
$AvreliaConfig = array
(
	# System Configuration
	'system' => array
	(
		# Set regex routing; first match from top to bottom get called, examples:
		#	index			'<index>'   => 'home->index()',
		#	not found		'<404>'     => 'home->not_found()',
		#	before route	'<before>'  => 'home->before()',
		#	after route		'<after>'   => 'home->after()',
		#   simple          'hi/world'  => 'home->hi()',
		#   simple #2       '<az>/hi'   => 'home->%2(%1)'
		#	regular 		'/([a-z0-9_-]*)\/?([a-zA-Z0-9\/!=\-+_.,;?]*)/' => 'home->%1(%2)',
		'routes' => array(),

		# Languages
		# List of default languages, this is used, if you're loading language with % in filename.
		# For example: my_language.%.lng, and for loading plug's default language.
		# The languages will be loaded in order provided bellow, meaning,
		# first 0, then if 0 can't be found, load 1, etc...
		'languages'        => array('en'),

		# Full URL (domain name) (http://example.com)
		'full_url'         => '',

		# Is application off-line?
		'offline'           => false,

		# If application is off-line, what kind of message do we want to send to our users?
		# This can be any string, or view:view_name, will be loaded from application/views folder.
		'offline_message'   => 'view:offline',

		# Default System Timezone.
		'timezone'         => 'GMT',

		# While URI cleanup (this isn't used in routes, as they have their own filter)
		# It's used with Input::Get();
		'input_get_filter' => '/[^a-zA-Z0-9\/!=\-+_.,;?]/',

		# When we set <*> what should be matched?
		'route_all_tag'    => 'a-zA-Z0-9!=\-+_.,;?',

		# If set to true, dispatcher will check controller's response
		# If response will be === false, the 404 will be displayed
		# (as if no route was not found!)
		'dispatcher_check_response' => false,

		# Turn debug on?
		'debug'            => true,

		# Allow redirects? Useful for debugging.
		'allow_redirects'  => true,

		# The files / folder which we need to ignore when copying / removing multiple files...
		'fs_ignore'   => array('.svn'),

		# Convert manually written \n character in language file to <br />
		'lang_n_to_br' => true,
	),

	# Plugs settings.
	'plug' => array
	(
		# Which plugs do we want to use in our application.
		'enabled'    => array(),

		# Which plugs do we want to auto-load at the beginning.
		# Plug must be on enabled list, in order to be auto loaded.
		'auto_load'  => array(),

		# Plugs default public directory
		'public_dir' => 'plugs',

		# Plugs force refresh public, this mean, on every request we drop
		# public folder for plugs and copy it again. This is useful only
		# for plug development. Set it to false otherwise!
		'debug'      => false,
	),

	# Cookies settings.
	'cookie' => array
	(
		# Enter domain name (example.com).
		'domain' => '',

		# Cookie prefix (can be empty if you want so).
		'prefix' => 'avrelia_',

		# Default timeout (seconds).
		'timeout' => 86400,
	),

	# Log settings.
	'log' => array
	(
		# Is log enabled (writing to file)?
		# Please before you enabled log, make sure your log folder is writable.
		'enabled' => false,

		# Full log path (full path + filename) - where all log messages will be saved.
		'path'    => APPPATH . '/log/' . date('Y') . '/' . date('m') . '/' . date('d') . '.log',

		# Log types. Select which type of messages should be saved. INF and OK isn't recommended to be saved.
		'types'   => array
		(
			'ERR' => true,
			'WAR' => true,
			'INF' => false,
			'OK'  => false,
		),

		# PHP system errors are handled by Avrelia Framework, 
		# therefore we're simplifying them. 
		# Framework has only three levels: WAR, INF, ERR, (OK) 
		# and react differently when one of them is triggered.
		# Note: ERR type will stop script execution.
		'map'     => array
		(
			E_ERROR              => 'ERR',
			E_WARNING            => 'WAR',
			E_PARSE              => 'ERR',
			E_NOTICE             => 'INF',
			E_CORE_ERROR         => 'ERR',
			E_CORE_WARNING       => 'WAR',
			E_COMPILE_ERROR      => 'ERR',
			E_COMPILE_WARNING    => 'WAR',
			E_USER_ERROR         => 'ERR',
			E_USER_WARNING       => 'WAR',
			E_USER_NOTICE        => 'INF',
			E_STRICT             => 'WAR',
			E_RECOVERABLE_ERROR  => 'ERR',
			E_DEPRECATED         => 'WAR',
			E_USER_DEPRECATED    => 'WAR',
		),

		# If set to true, every log message will be saved individually -
		# if set to false, all messages will be saved at the end of script execution.
		'write_individual'   => true,

		# Write all on fatal error. Set to true, special file will be created,
		# which will contain only one fatal event, with whole session informations (it will include ERR, WAR, INF).
		'write_all_on_fatal' => true,

		# Filename for fatal error (only if you set 'write_all_on_fatal' to true).
		'fatal_path'         => APPPATH . '/log/' . date('Y') . '/' . date('m') . '/fatal/' . date('Y-m-d__H-i-s') . '.log',
	),
);
