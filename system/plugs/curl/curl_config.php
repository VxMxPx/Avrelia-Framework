<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

$curl_config = array(
	# Default timeout in seconds, used in ::get and ::post
	'timeout' => 5,

	# User's agent setup
	'user_agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1',

	# Cookies file for curl
	'cookie_file' => dat_path('curl/cookies')
);