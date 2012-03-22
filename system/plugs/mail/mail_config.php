<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

$MailConfig = array
(
	'useragent'		=> 'Avrelia Framework',		# User Agent
	'mailpath'		=> '/usr/sbin/sendmail',	# Sendmail path
	'protocol'		=> 'sendmail',				# Options: mail/sendmail/smtp
	'SMTP'          => array(
		'host'      => false,	# SMTP Server. Example: mail.earthlink.net
		'user'      => false,	# SMTP Username
		'pass'      => false,	# SMTP Password
		'port'      => 25,	# SMTP Port
		'timeout'   => 5,	# SMTP Timeout in seconds
	),
	'wordwrap'		=> true,	# TRUE/FALSE  Turns word-wrap on/off
	'wrapchars'		=> 76,		# Number of characters to wrap at
	'charset'		=> 'utf-8',	# Default char set: iso-8859-1 or us-ascii
	'multipart'		=> 'mixed',	# "mixed" (in the body) or "related" (separate)
	'priority'		=> 3,		# Mail priority (1 - 5), default is 3
	'newline'		=> "\n",		# Default newline. "\r\n" or "\n" (Use "\r\n" to comply with RFC 822)
	'crlf'			=> "\n",		# The RFC 2045 compliant CRLF for quoted-printable is "\r\n".  Apparently some servers,
									# even on the receiving end think they need to muck with CRLFs, so using "\n", while
									# distasteful, is the only thing that seems to work for all environments
	'send_multipart'	=> true,	# TRUE/FALSE - Yahoo does not like multipart alternative, so this is an override. Set to FALSE for Yahoo
	'bcc_batch_mode'	=> false,	# TRUE/FALSE  Turns on/off Bcc batch feature
	'bcc_batch_size'	=> 200,		# If bcc_batch_mode = TRUE, sets max number of Bccs in each batch
);
