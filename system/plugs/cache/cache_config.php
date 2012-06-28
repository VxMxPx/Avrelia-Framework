<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

$CacheConfig = array(
	# Which type to use (file or php's apc)
	# Options: file || apc
	# To read more about apc, see: http://www.php.net/manual/en/ref.apc.php
	'driver'     => 'file',

	# Where to store type:file cache
	'location'   => DATPATH . '/cache',

	# Apc prefix (to avoid confict with other applications)
	'apc_prefix' => 'avrelia_framework_',
);