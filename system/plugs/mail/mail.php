<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Mail Lib Model
 * Bases on CodeIgniter's Email Class
 * http://codeigniter.com/user_guide/license.html
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2011-07-05
 * ---
 * @property	array	$Config
 * @property	boolean	$safeMode
 * @property	string	$subject
 * @property	string	$bodyHtml
 * @property	string	$bodyPlain
 * @property	string	$bodyFinal
 * @property	string	$altBoundary
 * @property	string	$atcBoundary
 * @property	string	$headerStr
 * @property	string	$encoding
 * @property	string	$IP
 * @property	string	$smtpAuth
 * @property	boolean	$replyToFlag
 * @property	array	$recipients
 * @property	array	$ccArray
 * @property	array	$bccArray
 * @property	array	$headers
 * @property	array	$attachName
 * @property	array	$attachType
 * @property	array	$attachDisp
 * @property	array	$protocols
 * @property	array	$baseCharsets
 * @property	array	$bitDepths
 * @property	array	$priorities
 */
class cMail
{
	private $Config       = array();
	private $safeMode     = false;
	private $subject      = '';
	private $bodyHtml     = '';
	private $bodyPlain    = '';
	private $bodyFinal    = '';
	private $altBoundary  = '';
	private $atcBoundary  = '';
	private $headerStr    = '';
	private $smtpConnect  = '';
	private $encoding     = '8bit';
	private $IP           = false;
	private $smtpAuth     = false;
	private $replyToFlag  = false;
	private $recipients   = array();
	private $ccArray      = array();
	private $bccArray     = array();
	private $headers      = array();
	private $attachName   = array();
	private $attachType   = array();
	private $attachDisp   = array();
	private $protocols    = array('mail', 'sendmail', 'smtp');	# Allowed protocols
	private $baseCharsets = array('us-ascii', 'iso-2022-');		# 7-bit charsets (excluding language suffix)
	private $bitDepths    = array('7bit', '8bit');
	private $priorities   = array('1 (Highest)', '2 (High)', '3 (Normal)', '4 (Low)', '5 (Lowest)');

	/**
	 * Set some default configs
	 * --
	 * @return	void
	 */
	public function __construct()
	{
		$this->Config = Plug::GetConfig(__FILE__);

		$this->smtpAuth = ($this->Config['SMTP']['user'] && $this->Config['SMTP']['pass']);
		$this->safeMode = ((boolean)ini_get('safe_mode') === false) ? false : true;
	}
	//-

	/*  ****************************************************** *
	 *          Public Methods
	 *  **************************************  */

	/**
	 * Who is sending this mail
	 * --
	 * @param	string	$from
	 * @param	string	$name
	 * --
	 * @return	$this
	 */
	public function from($from, $name=null)
	{
		# In case someone set it as First Last <first.last@domain.tld>
		if (preg_match( '/\<(.*)\>/', $from, $match)) {
			$from = $match['1'];
		}

		$this->validateEmail($this->strToArray($from));

		# Prepare the display name
		if ($name) {
			# Only use Q encoding if there are characters that would require it
			if (!preg_match('/[\200-\377]/', $name)) {
				# Add slashes for non-printing characters, slashes, and double quotes,
				# and surround it in double quotes
				$name = '"'.addcslashes($name, "\0..\37\177'\"\\").'"';
			}
			else {
				$name = $this->prepQEncoding($name, true);
			}
		}

		$this->setHeader('From', $name.' <'.$from.'>');
		$this->setHeader('Return-Path', '<'.$from.'>');

		return $this;
	}
	//-

	/**
	 * ReplyTo, if not set, from will be used.
	 * --
	 * @param	string	$replyto
	 * @param	string	$name
	 * --
	 * @return	$this
	 */
	public function replyTo($replyto, $name=null)
	{
		# In case someone set it as First Last <first.last@domain.tld>
		if (preg_match('/\<(.*)\>/', $replyto, $match)) {
			$replyto = $match['1'];
		}

		$this->validateEmail($this->strToArray($replyto));

		if ($name) {
			$name = $replyto;
		}

		if (strncmp($name, '"', 1) != 0) {
			$name = '"'.$name.'"';
		}

		$this->setHeader('Reply-To', $name.' <'.$replyto.'>');
		$this->replyToFlag = true;

		return $this;
	}
	//-

	/**
	 * Set Recipients
	 * --
	 * @param	string	$to
	 * --
	 * @return	$this
	 */
	public function to($to)
	{
		$to = $this->strToArray($to);
		$to = $this->cleanEmail($to);


		$this->validateEmail($to);

		if ($this->getProtocol() != 'mail') {
			$this->setHeader('To', implode(", ", $to));
		}

		switch ($this->getProtocol())
		{
			case 'smtp':
				$this->recipients = $to;
				break;

			case 'sendmail'	:
			case 'mail'		:
				$this->recipients = implode(', ', $to);
				break;
		}

		return $this;
	}
	//-

	/**
	 * Set CC
	 * --
	 * @param	string	$cc
	 * --
	 * @return	$this
	 */
	public function cc($cc)
	{
		$cc = $this->strToArray($cc);
		$cc = $this->cleanEmail($cc);

		$this->validateEmail($cc);

		$this->setHeader('Cc', implode(', ', $cc));

		if ($this->getProtocol() == 'smtp') {
			$this->ccArray = $cc;
		}

		return $this;
	}
	//-

	/**
	 * Set BCC
	 * --
	 * @param	string	$bcc
	 * @param	integer	$limit
	 * --
	 * @return	$this
	 */
	public function bcc($bcc, $limit=false)
	{
		if ($limit) {
			$this->Config['bcc_batch_mode'] = true;
			$this->Config['bcc_batch_size'] = (int) $limit;
		}

		$bcc = $this->strToArray($bcc);
		$bcc = $this->cleanEmail($bcc);

		$this->validateEmail($bcc);

		if ($this->getProtocol() == 'smtp' ||
				($this->Config['bcc_batch_mode'] && count($bcc) > $this->Config['bcc_batch_size']))
		{
			$this->bccArray = $bcc;
		}
		else {
			$this->setHeader('Bcc', implode(', ', $bcc));
		}

		return $this;
	}
	//-

	/**
	 * Set Email Subject
	 * --
	 * @param	string	$subject
	 * --
	 * @return	$this
	 */
	public function subject($subject)
	{
		$subject = $this->prepQEncoding($subject);
		$this->setHeader('Subject', $subject);

		return $this;
	}
	//-

	/**
	 * Set Body
	 * ---
	 * @param	string	$html
	 * @param	string	$plain
	 * --
	 * @return	$this
	 */
	public function message($html=false, $plain=false)
	{
		if ($html) {
			$this->bodyHtml = stripslashes(rtrim(str_replace("\r", '', $html)));
		}

		if ($plain) {
			$this->bodyPlain = $plain;
		}

		return $this;
	}
	//-

	/**
	 * Assign file attachments
	 * --
	 * @param	mixed	$filename		List of files
	 * @param	string	$disposition	Attachments || inline
	 * --
	 * @return	$this
	 */
	public function attach($filename, $disposition='attachment')
	{
		if (is_array($filename)) {
			foreach ($filename as $f) {
				$this->attach($filename, $disposition);
			}
		}
		else {
			$filename = ds($filename);

			if (!file_exists($filename)) {
				Log::Add('WAR', "File not found: `{$filename}`.", __LINE__, __FILE__);
			}
			else {
				$this->attachName[] = $filename;
				$this->attachType[] = $this->mimeTypes(pathinfo($filename, PATHINFO_EXTENSION));
				$this->attachDisp[] = $disposition; # Can also be 'inline'  Not sure if it matters
			}
		}

		return $this;
	}
	//-

	/**
	 * Set Priority
	 * --
	 * @param	integer	$priority
	 * --
	 * @return	$this
	 */
	public function priority($priority=3)
	{
		if ($priority < 1 || $priority > 5) {
			Log::Add('WAR', "Invalid priority", __LINE__, __FILE__);
		}
		else {
			$this->Config['priority'] = $priority;
		}

		return $this;
	}
	//-

	/**
	 * Send Email
	 * --
	 * @return	boolean
	 */
	public function send()
	{
		if ($this->replyToFlag == false) {
			$this->replyTo($this->headers['From']);
		}

		if ((!isset($this->recipients) && !isset($this->headers['To']))  &&
			(!isset($this->bccArray)   && !isset($this->headers['Bcc'])) &&
			(!isset($this->headers['Cc'])))
		{
			Log::Add('WAR', "Email has no recipients.", __LINE__, __FILE__);
			return false;
		}

		$this->buildHeaders();

		Log::Add('INF', "The e-mail with following parameters will be send:\n ". print_r($this->headers, true), __LINE__, __FILE__);

		if ($this->Config['bcc_batch_mode'] && count($this->bccArray) > 0) {
			if (count($this->bccArray) > $this->Config['bcc_batch_size']) {
				return $this->batchBccSend();
			}
		}

		$this->buildMessage();

		return $this->spoolEmail();
	}
	//-

	/*  ****************************************************** *
	 *          Private Methods
	 *  **************************************  */

	/**
	 * Add a Header Item
	 * --
	 * @param	string	$header
	 * @param	mixed	$value
	 * --
	 * @return	void
	 */
	private function setHeader($header, $value)
	{
		$this->headers[$header] = $value;
	}
	//-

	/**
	 * Convert a String to an Array
	 * --
	 * @param	string	$email
	 * --
	 * @return	array
	 */
	private function strToArray($email)
	{
		if (!is_array($email)) {
			if (strpos($email, ',') !== false) {
				$email = preg_split('/[\s,]/', $email, -1, PREG_SPLIT_NO_EMPTY);
			}
			else {
				$email = trim($email);
				settype($email, 'array');
			}
		}

		return $email;
	}
	//-

	/**
	 * Set Message Boundary
	 * --
	 * @return	void
	 */
	private function setBoundaries()
	{
		$this->altBoundary = "B_ALT_".uniqid(''); # multipart/alternative
		$this->atcBoundary = "B_ATC_".uniqid(''); # attachment boundary
	}
	//-

	/**
	 * Get the Message ID
	 * --
	 * @return	string
	 */
	private function getMessageId()
	{
		$from = $this->headers['Return-Path'];
		$from = str_replace(">", "", $from);
		$from = str_replace("<", "", $from);

		return  "<".uniqid('').strstr($from, '@').">";
	}
	//-

	/**
	 * Get Mail Protocol
	 * --
	 * @param	boolean	$return
	 * --
	 * @return	mixed
	 */
	private function getProtocol($return=true)
	{
		$this->Config['protocol'] = strtolower($this->Config['protocol']);
		$this->Config['protocol'] = (!in_array($this->Config['protocol'], $this->protocols, true)) ? 'mail' : $this->Config['protocol'];

		if ($return) {
			return $this->Config['protocol'];
		}
	}
	//-

	/**
	 * Get Mail Encoding
	 * --
	 * @param	boolean	$return
	 * --
	 * @return	mixed
	 */
	private function getEncoding($return=true)
	{
		$this->encoding = (!in_array($this->encoding, $this->bitDepths)) ? '8bit' : $this->encoding;

		foreach ($this->baseCharsets as $charset)
		{
			if (strncmp($charset, $this->Config['charset'], strlen($charset)) == 0) {
				$this->encoding = '7bit';
			}
		}

		if ($return) {
			return $this->encoding;
		}
	}
	//-

	/**
	 * Get content type (text/html/attachment)
	 * --
	 * @return	string
	 */
	private function getContentType()
	{
		if	($this->bodyHtml && count($this->attachName) == 0) {
			return 'html';
		}
		elseif ($this->bodyHtml && count($this->attachName)  > 0) {
			return 'html-attach';
		}
		elseif	(count($this->attachName)  > 0) {
			return 'plain-attach';
		}
		else {
			return 'plain';
		}
	}
	//-

	/**
	 * Set RFC 822 Date
	 * --
	 * @return	string
	 */
	private function setDate()
	{
		$timezone = date('Z');
		$operator = (strncmp($timezone, '-', 1) == 0) ? '-' : '+';
		$timezone = abs($timezone);
		$timezone = floor($timezone/3600) * 100 + ($timezone % 3600 ) / 60;

		return sprintf("%s %s%04d", date("D, j M Y H:i:s"), $operator, $timezone);
	}
	//-

	/**
	 * Mime message
	 * --
	 * @return	string
	 */
	private function getMimeMessage()
	{
		return 'This is a multi-part message in MIME format.'
				.$this->Config['newline'].
				'Your email application may not support this format.';
	}
	//-

	/**
	 * Validate Email Address
	 * --
	 * @param	string	$email
	 * --
	 * @return	boolean
	 */
	private function validateEmail($email)
	{
		if (!is_array($email)) {
			Log::Add('WAR', "Email must be an array: `{$email}`.", __LINE__, __FILE__);
			return false;
		}

		foreach ($email as $val)
		{
			if (!filter_var($val, FILTER_VALIDATE_EMAIL)) {
				Log::Add('WAR', "The e-mail might be invalid: `{$val}`.", __LINE__, __FILE__);
				return false;
			}
		}

		return false;
	}
	//-

	/**
	 * Clean Extended Email Address: Joe Smith <joe@smith.com>
	 * --
	 * @param	mixed	$email
	 * --
	 * @return	mixed
	 */
	private function cleanEmail($email)
	{
		if (is_array($email)) {
			$Result = array();

			foreach ($email as $val) {
				$Result[] = $this->cleanEmail($val);
			}

			return $Result;
		}
		else {
			if (preg_match('/\<(.*)\>/', $email, $match)) {
				return $match['1'];
			}
			else {
				return $email;
			}
		}
	}
	//-

	/**
	 * Build alternative plain text message
	 *
	 * This public function provides the raw message for use
	 * in plain-text headers of HTML-formatted emails.
	 * If the user hasn't specified his own alternative message
	 * it creates one by stripping the HTML
	 * --
	 * @return	string
	 */
	private function getAltMessage()
	{
		if ($this->bodyPlain) {
			return $this->wordWrap($this->bodyPlain, '76');
		}

		if (preg_match('/\<body.*?\>(.*)\<\/body\>/si', $this->bodyHtml, $match)) {
			$body = $match['1'];
		}
		else {
			$body = $this->bodyHtml;
		}

		$body = trim(strip_tags($body));
		$body = preg_replace('#<!--(.*)--\>#', '', $body);
		$body = str_replace("\t", '', $body);

		for ($i = 20; $i >= 3; $i--)
		{
			$n = '';

			for ($x = 1; $x <= $i; $x ++)
			{
				$n .= "\n";
			}

			$body = str_replace($n, "\n\n", $body);
		}

		return $this->wordWrap($body, '76');
	}
	//-

	/**
	 * Word Wrap
	 * --
	 * @param	string	$str
	 * @param	integer	$charlim
	 * --
	 * @return	string
	 */
	private function wordWrap($str, $charlim=false)
	{
		# Set the character limit
		if ($charlim) {
			$charlim = (!$this->Config['wrapchars']) ? 76 : $this->Config['wrapchars'];
		}

		# Reduce multiple spaces
		$str = preg_replace("| +|", " ", $str);

		# Standardize newlines
		if (strpos($str, "\r") !== false) {
			$str = str_replace(array("\r\n", "\r"), "\n", $str);
		}

		# If the current word is surrounded by {unwrap} tags we'll
		# strip the entire chunk and replace it with a marker.
		$unwrap = array();
		if (preg_match_all("|(\{unwrap\}.+?\{/unwrap\})|s", $str, $matches))
		{
			for ($i = 0; $i < count($matches['0']); $i++)
			{
				$unwrap[] = $matches['1'][$i];
				$str = str_replace($matches['1'][$i], "{{unwrapped".$i."}}", $str);
			}
		}

		# Use PHP's native public function to do the initial wordwrap.
		# We set the cut flag to FALSE so that any individual words that are
		# too long get left alone.  In the next step we'll deal with them.
		$str = wordwrap($str, $charlim, "\n", false);

		# Split the string into individual lines of text and cycle through them
		$output = '';

		foreach (explode("\n", $str) as $line)
		{
			# Is the line within the allowed character count?
			# If so we'll join it to the output and continue
			if (strlen($line) <= $charlim) {
				$output .= $line.$this->Config['newline'];
				continue;
			}

			$temp = '';
			while ((strlen($line)) > $charlim)
			{
				# If the over-length word is a URL we won't wrap it
				if (preg_match("!\[url.+\]|://|wwww.!", $line)) {
					break;
				}

				# Trim the word down
				$temp .= substr($line, 0, $charlim-1);
				$line = substr($line, $charlim-1);
			}

			# If $temp contains data it means we had to split up an over-length
			# word into smaller chunks so we'll add it back to our current line
			if ($temp != '') {
				$output .= $temp.$this->Config['newline'].$line;
			}
			else {
				$output .= $line;
			}

			$output .= $this->Config['newline'];
		}

		# Put our markers back
		if (count($unwrap) > 0)
		{
			foreach ($unwrap as $key => $val)
			{
				$output = str_replace("{{unwrapped".$key."}}", $val, $output);
			}
		}

		return $output;
	}
	//-

	/**
	 * Build final headers
	 * --
	 * @return	void
	 */
	private function buildHeaders()
	{
		$this->setHeader('X-Sender',     $this->cleanEmail($this->headers['From']));
		$this->setHeader('X-Mailer',     $this->Config['useragent']);
		$this->setHeader('X-Priority',   $this->priorities[$this->Config['priority'] - 1]);
		$this->setHeader('Message-ID',   $this->getMessageId());
		$this->setHeader('Mime-Version', '1.0');
	}
	//-

	/**
	 * Write Headers as a string
	 * --
	 * @return	void
	 */
	private function writeHeaders()
	{
		if ($this->Config['protocol'] == 'mail') {
			$this->subject = $this->headers['Subject'];
			unset($this->headers['Subject']);
		}

		reset($this->headers);
		$this->headerStr = '';

		foreach ($this->headers as $key => $val)
		{
			$val = trim($val);

			if ($val != '') {
				$this->headerStr .= $key.": ".$val.$this->Config['newline'];
			}
		}

		if ($this->getProtocol() == 'mail') {
			$this->headerStr = rtrim($this->headerStr);
		}
	}
	//-

	/**
	 * Build Final Body and attachments
	 * --
	 * @return	void
	 */
	private function buildMessage()
	{
		if ($this->Config['wordwrap'] === true && !$this->bodyHtml) {
			$this->bodyHtml = $this->wordWrap($this->bodyHtml);
		}

		$this->setBoundaries();
		$this->writeHeaders();

		$hdr = ($this->getProtocol() == 'mail') ? $this->Config['newline'] : '';
		$body = '';

		switch ($this->getContentType())
		{
			case 'plain' :

				$hdr .= "Content-Type: text/plain; charset=" . $this->Config['charset'] . $this->Config['newline'];
				$hdr .= "Content-Transfer-Encoding: " . $this->getEncoding();

				if ($this->getProtocol() == 'mail') {
					$this->headerStr .= $hdr;
					$this->bodyFinal = $this->bodyHtml;
				}
				else {
					$this->bodyFinal = $hdr . $this->Config['newline'] . $this->Config['newline'] . $this->bodyHtml;
				}

				return;
				break;

			case 'html' :

				if ($this->Config['send_multipart'] === false) {
					$hdr .= "Content-Type: text/html; charset=" . $this->Config['charset'] . $this->Config['newline'];
					$hdr .= "Content-Transfer-Encoding: quoted-printable";
				}
				else {
					$hdr .= "Content-Type: multipart/alternative; boundary=\"" . $this->altBoundary . "\"" . $this->Config['newline'] . $this->Config['newline'];

					$body .= $this->getMimeMessage() . $this->Config['newline'] . $this->Config['newline'];
					$body .= "--" . $this->altBoundary . $this->Config['newline'];

					$body .= "Content-Type: text/plain; charset=" . $this->Config['charset'] . $this->Config['newline'];
					$body .= "Content-Transfer-Encoding: " . $this->getEncoding() . $this->Config['newline'] . $this->Config['newline'];
					$body .= $this->getAltMessage() . $this->Config['newline'] . $this->Config['newline'] . "--" . $this->altBoundary . $this->Config['newline'];

					$body .= "Content-Type: text/html; charset=" . $this->Config['charset'] . $this->Config['newline'];
					$body .= "Content-Transfer-Encoding: quoted-printable" . $this->Config['newline'] . $this->Config['newline'];
				}

				$this->bodyFinal = $body . $this->prepQuotedPrintable($this->bodyHtml) . $this->Config['newline'] . $this->Config['newline'];

				if ($this->getProtocol() == 'mail') {
					$this->headerStr .= $hdr;
				}
				else {
					$this->bodyFinal = $hdr . $this->bodyFinal;
				}


				if ($this->Config['send_multipart'] !== false) {
					$this->bodyFinal .= "--" . $this->altBoundary . "--";
				}

				return;
				break;

			case 'plain-attach' :

				$hdr .= "Content-Type: multipart/".$this->Config['multipart']."; boundary=\"" . $this->atcBoundary."\"" . $this->Config['newline'] . $this->Config['newline'];

				if ($this->getProtocol() == 'mail') {
					$this->headerStr .= $hdr;
				}

				$body .= $this->getMimeMessage() . $this->Config['newline'] . $this->Config['newline'];
				$body .= "--" . $this->atcBoundary . $this->Config['newline'];

				$body .= "Content-Type: text/plain; charset=" . $this->Config['charset'] . $this->Config['newline'];
				$body .= "Content-Transfer-Encoding: " . $this->getEncoding() . $this->Config['newline'] . $this->Config['newline'];

				$body .= $this->bodyHtml . $this->Config['newline'] . $this->Config['newline'];

				break;

			case 'html-attach' :

				$hdr .= "Content-Type: multipart/".$this->Config['multipart']."; boundary=\"" . $this->atcBoundary."\"" . $this->Config['newline'] . $this->Config['newline'];

				if ($this->getProtocol() == 'mail') {
					$this->headerStr .= $hdr;
				}

				$body .= $this->getMimeMessage() . $this->Config['newline'] . $this->Config['newline'];
				$body .= "--" . $this->atcBoundary . $this->Config['newline'];

				$body .= "Content-Type: multipart/alternative; boundary=\"" . $this->altBoundary . "\"" . $this->Config['newline'] .$this->Config['newline'];
				$body .= "--" . $this->altBoundary . $this->Config['newline'];

				$body .= "Content-Type: text/plain; charset=" . $this->Config['charset'] . $this->Config['newline'];
				$body .= "Content-Transfer-Encoding: " . $this->getEncoding() . $this->Config['newline'] . $this->Config['newline'];
				$body .= $this->getAltMessage() . $this->Config['newline'] . $this->Config['newline'] . "--" . $this->altBoundary . $this->Config['newline'];

				$body .= "Content-Type: text/html; charset=" . $this->Config['charset'] . $this->Config['newline'];
				$body .= "Content-Transfer-Encoding: quoted-printable" . $this->Config['newline'] . $this->Config['newline'];

				$body .= $this->prepQuotedPrintable($this->bodyHtml) . $this->Config['newline'] . $this->Config['newline'];
				$body .= "--" . $this->altBoundary . "--" . $this->Config['newline'] . $this->Config['newline'];

				break;
		}

		$attachment = array();

		$z = 0;

		for ($i=0; $i < count($this->attachName); $i++)
		{
			$filename = $this->attachName[$i];
			$basename = basename($filename);
			$ctype = $this->attachType[$i];

			$h  = "--".$this->atcBoundary.$this->Config['newline'];
			$h .= "Content-type: ".$ctype."; ";
			$h .= "name=\"".$basename."\"".$this->Config['newline'];
			$h .= "Content-Disposition: ".$this->attachDisp[$i].";".$this->Config['newline'];
			$h .= "Content-Transfer-Encoding: base64".$this->Config['newline'];

			$attachment[$z++] = $h;
			$file = filesize($filename) +1;

			if (!$fp = fopen($filename, 'rb')) {
				Log::Add('ERR', "Email attachment unreadable: `{$filename}`.", __LINE__, __FILE__);
				return false;
			}

			$attachment[$z++] = chunk_split(base64_encode(fread($fp, $file)));
			fclose($fp);
		}

		$body .= implode($this->Config['newline'], $attachment).$this->Config['newline']."--".$this->atcBoundary."--";

		if ($this->getProtocol() == 'mail') {
			$this->bodyFinal = $body;
		}
		else {
			$this->bodyFinal = $hdr . $body;
		}

		return;
	}
	//-

	/**
	 * Prep Quoted Printable
	 *
	 * Prepares string for Quoted-Printable Content-Transfer-Encoding
	 * Refer to RFC 2045 http://www.ietf.org/rfc/rfc2045.txt
	 * --
	 * @param	string	$str
	 * @param	integer	$charlim
	 * --
	 * @return	string
	 */
	private function prepQuotedPrintable($str, $charlim=false)
	{
		# Set the character limit
		# Don't allow over 76, as that will make servers and MUAs barf
		# all over quoted-printable data
		if (!$charlim || $charlim > 76) {
			$charlim = 76;
		}

		# Reduce multiple spaces
		$str = preg_replace("| +|", " ", $str);

		# kill nulls
		$str = preg_replace('/\x00+/', '', $str);

		# Standardize newlines
		if (strpos($str, "\r") !== false) {
			$str = str_replace(array("\r\n", "\r"), "\n", $str);
		}

		# We are intentionally wrapping so mail servers will encode characters
		# properly and MUAs will behave, so {unwrap} must go!
		$str = str_replace(array('{unwrap}', '{/unwrap}'), '', $str);

		# Break into an array of lines
		$lines = explode("\n", $str);

		$escape = '=';
		$output = '';

		foreach ($lines as $line)
		{
			$length = strlen($line);
			$temp = '';

			# Loop through each character in the line to add soft-wrap
			# characters at the end of a line " =\r\n" and add the newly
			# processed line(s) to the output (see comment on $crlf class property)
			for ($i = 0; $i < $length; $i++)
			{
				# Grab the next character
				$char = substr($line, $i, 1);
				$ascii = ord($char);

				# Convert spaces and tabs but only if it's the end of the line
				if ($i == ($length - 1)) {
					$char = ($ascii == '32' OR $ascii == '9') ? $escape.sprintf('%02s', dechex($ascii)) : $char;
				}

				# encode = signs
				if ($ascii == '61') {
					$char = $escape.strtoupper(sprintf('%02s', dechex($ascii)));
				}

				# If we're at the character limit, add the line to the output,
				# reset our temp variable, and keep on chuggin'
				if ((strlen($temp) + strlen($char)) >= $charlim) {
					$output .= $temp.$escape.$this->Config['crlf'];
					$temp = '';
				}

				# Add the character to our temporary line
				$temp .= $char;
			}

			# Add our completed line to the output
			$output .= $temp.$this->Config['crlf'];
		}

		# Get rid of extra CRLF tacked onto the end
		$output = substr($output, 0, strlen($this->Config['crlf']) * -1);

		return $output;
	}
	//-

	/**
	 * Prep Q Encoding
	 *
	 * Performs "Q Encoding" on a string for use in email headers.  It's related
	 * but not identical to quoted-printable, so it has its own method
	 * --
	 * @param	string	$str
	 * @param	boolean	$from	Set to true for processing From: headers
	 * --
	 * @return	string
	 */
	private function prepQEncoding($str, $from=false)
	{
		$str = str_replace(array("\r", "\n"), array('', ''), $str);

		# Line length must not exceed 76 characters, so we adjust for
		# a space, 7 extra characters =??Q??=, and the charset that we will add to each line
		$limit = 75 - 7 - strlen($this->Config['charset']);

		# These special characters must be converted too
		$convert = array('_', '=', '?');

		if ($from === true) {
			$convert[] = ',';
			$convert[] = ';';
		}

		$output = '';
		$temp = '';

		for ($i = 0, $length = strlen($str); $i < $length; $i++)
		{
			# Grab the next character
			$char = substr($str, $i, 1);
			$ascii = ord($char);

			# convert ALL non-printable ASCII characters and our specials
			if ($ascii < 32 OR $ascii > 126 OR in_array($char, $convert)) {
				$char = '='.dechex($ascii);
			}

			# Handle regular spaces a bit more compactly than =20
			if ($ascii == 32) {
				$char = '_';
			}

			# If we're at the character limit, add the line to the output,
			# reset our temp variable, and keep on chuggin'
			if ((strlen($temp) + strlen($char)) >= $limit) {
				$output .= $temp.$this->Config['crlf'];
				$temp = '';
			}

			# Add the character to our temporary line
			$temp .= $char;
		}

		$str = $output.$temp;

		# Wrap each line with the shebang, charset, and transfer encoding
		# the preceding space on successive lines is required for header "folding"
		$str = trim(preg_replace('/^(.*)$/m', ' =?'.$this->Config['charset'].'?Q?$1?=', $str));

		return $str;
	}
	//-

	/**
	 * Batch Bcc Send. Sends groups of BCCs in batches.
	 * --
	 * @return	boolean
	 */
	private function batchBccSend()
	{
		$float = $this->Config['bcc_batch_size'] -1;

		$set = '';

		$chunk = array();

		for ($i = 0; $i < count($this->bccArray); $i++)
		{
			if (isset($this->bccArray[$i])) {
				$set .= ", ".$this->bccArray[$i];
			}

			if ($i == $float) {
				$chunk[] = substr($set, 1);
				$float = $float + $this->Config['bcc_batch_size'];
				$set = '';
			}

			if ($i == count($this->bccArray)-1) {
				$chunk[] = substr($set, 1);
			}
		}

		for ($i = 0; $i < count($chunk); $i++)
		{
			unset($this->headers['Bcc']);
			unset($bcc);

			$bcc = $this->strToArray($chunk[$i]);
			$bcc = $this->cleanEmail($bcc);

			if ($this->Config['protocol'] != 'smtp') {
				$this->setHeader('Bcc', implode(", ", $bcc));
			}
			else {
				$this->bccArray = $bcc;
			}

			$this->buildMessage();
			$this->spoolEmail();
		}
	}
	//-

	/**
	 * Unwrap special elements
	 * --
	 * @return	void
	 */
	private function unwrapSpecials()
	{
		$this->bodyFinal = preg_replace_callback("/\{unwrap\}(.*?)\{\/unwrap\}/si", array($this, 'removeNlCallback'), $this->bodyFinal);
	}
	//-

	/**
	 * Strip line-breaks via callback
	 * --
	 * @param	array	$matches
	 * --
	 * @return	string
	 */
	private function removeNlCallback($matches)
	{
		if (strpos($matches[1], "\r") !== false || strpos($matches[1], "\n") !== false) {
			$matches[1] = str_replace(array("\r\n", "\r", "\n"), '', $matches[1]);
		}

		return $matches[1];
	}
	//-

	/**
	 * Spool mail to the mail server
	 * --
	 * @return	boolean
	 */
	private function spoolEmail()
	{
		$this->unwrapSpecials();

		switch ($this->getProtocol())
		{
			case 'mail':
				if (!$this->sendWithMail()) {
					Log::Add('ERR', "PHPMAIL: failed.", __LINE__, __FILE__);
					return false;
				}
				break;

			case 'sendmail':
				if (!$this->sendWithSendmail()) {
					Log::Add('ERR', "SENDMAIL: failed.", __LINE__, __FILE__);
					return false;
				}
				break;

			case 'smtp'	:
				if (!$this->sendWithSmtp()) {
					Log::Add('ERR', "SMTP: failed.", __LINE__, __FILE__);
					return false;
				}
				break;

			default:
				Log::Add('ERR', "Invalid protocol: `" . $this->getProtocol() . '`.', __LINE__, __FILE__);
				return false;
		}

		Log::Add('INF', strtoupper($this->getProtocol()) . ': mail was successfully sent!', __LINE__, __FILE__);
		return true;
	}
	//-

	/**
	 * Send using mail()
	 * --
	 * @return	boolean
	 */
	private function sendWithMail()
	{
		if ($this->safeMode) {
			return mail($this->recipients, $this->subject, $this->bodyFinal, $this->headerStr);
		}
		else
		{
			# Most documentation of sendmail using the "-f" flag lacks a space after it, however
			# we've encountered servers that seem to require it to be in place.
			return mail($this->recipients, $this->subject, $this->bodyFinal, $this->headerStr, "-f ".$this->cleanEmail($this->headers['From']));
		}
	}
	//-

	/**
	 * Send using Sendmail
	 * --
	 * @return	boolean
	 */
	private function sendWithSendmail()
	{
		$fp = @popen($this->Config['mailpath'] . " -oi -f ".$this->cleanEmail($this->headers['From'])." -t", 'w');

		if ($fp === false || $fp === NULL) {
			# server probably has popen disabled, so nothing we can do to get a verbose error.
			Log::Add('ERR', "It seems server has `popen` disabled.", __LINE__, __FILE__);
			return false;
		}

		fputs($fp, $this->headerStr);
		fputs($fp, $this->bodyFinal);

		$status = pclose($fp);

		if (version_compare(PHP_VERSION, '4.2.3') == -1) {
			$status = $status >> 8 & 0xFF;
		}

		if ($status != 0) {
			Log::Add('ERR', "Email exit status: " . $status, __LINE__, __FILE__);
			return false;
		}

		return true;
	}
	//-

	/**
	 * Send using SMTP
	 * --
	 * @return	boolean
	 */
	private function sendWithSmtp()
	{
		if (!$this->Config['SMTP']['host']) {
			Log::Add('WAR', "SMTP has no host set!", __LINE__, __FILE__);
			return false;
		}

		$this->smtpConnect();
		$this->smtpAuthenticate();

		$this->sendCommand('from', $this->cleanEmail($this->headers['From']));

		foreach ($this->recipients as $val) {
			$this->sendCommand('to', $val);
		}

		if (count($this->ccArray) > 0) {
			foreach ($this->ccArray as $val)
			{
				if ($val != '') {
					$this->sendCommand('to', $val);
				}
			}
		}

		if (count($this->bccArray) > 0) {
			foreach ($this->bccArray as $val)
			{
				if ($val != '') {
					$this->sendCommand('to', $val);
				}
			}
		}

		$this->sendCommand('data');

		# Perform dot transformation on any lines that begin with a dot
		$this->sendData($this->headerStr . preg_replace('/^\./m', '..$1', $this->bodyFinal));

		$this->sendData('.');

		$reply = $this->getSmtpData();

		if (strncmp($reply, '250', 3) != 0) {
			Log::Add('ERR', "SMTP error: " . $reply, __LINE__, __FILE__);
			return false;
		}
		else {
			Log::Add('INF', "SMTP replay: " . $reply, __LINE__, __FILE__);
		}

		$this->sendCommand('quit');
		return true;
	}
	//-

	/**
	 * SMTP Connect
	 * --
	 * @return	string
	 */
	private function smtpConnect()
	{
		$this->smtpConnect = fsockopen(
								$this->Config['SMTP']['host'],
								$this->Config['SMTP']['port'],
								$errno,
								$errstr,
								$this->Config['SMTP']['timeout']);

		if (!is_resource($this->smtpConnect)) {
			Log::Add('ERR', "SMTP error: `{$errno}`, `{$errstr}`.", __LINE__, __FILE__);
			return false;
		}

		Log::Add('INF', "SMTP data: " . $this->getSmtpData(), __LINE__, __FILE__);
		return $this->sendCommand('hello');
	}
	//-

	/**
	 * Send SMTP command
	 * --
	 * @param	string	$cmd
	 * @param	string	$data
	 * --
	 * @return	boolean
	 */
	private function sendCommand($cmd, $data='')
	{
		switch ($cmd)
		{
			case 'hello':
				if ($this->smtpAuth || $this->getEncoding() == '8bit') {
					$this->sendData('EHLO '.$this->getHostname());
				}
				else {
					$this->sendData('HELO '.$this->getHostname());
				}
				$resp = 250;
				break;

			case 'from':
				$this->sendData('MAIL FROM:<'.$data.'>');
				$resp = 250;
				break;

			case 'to':
				$this->sendData('RCPT TO:<'.$data.'>');
				$resp = 250;
				break;

			case 'data':
				$this->sendData('DATA');
				$resp = 354;
				break;

			case 'quit':
				$this->sendData('QUIT');
				$resp = 221;
				break;
		}

		$reply = $this->getSmtpData();

		if (substr($reply, 0, 3) != $resp) {
			Log::Add('ERR', "Command failed: `{$cmd}`, reply: {$reply}", __LINE__, __FILE__);
			return false;
		}
		else {
			Log::Add('INF', "Command: `{$cmd}`, reply: {$reply}", __LINE__, __FILE__);
		}

		if ($cmd == 'quit') {
			fclose($this->smtpConnect);
		}

		return true;
	}
	//-

	/**
	 * SMTP Authenticate
	 * --
	 * @return	boolean
	 */
	private function smtpAuthenticate()
	{
		if (!$this->smtpAuth) {
			return true;
		}

		if (!$this->Config['SMTP']['user'] && !$this->Config['SMTP']['pass']) {
			Log::Add('WAR', "SMTP username or password isn't set.", __LINE__, __FILE__);
			return false;
		}

		$this->sendData('AUTH LOGIN');

		$reply = $this->getSmtpData();

		if (strncmp($reply, '334', 3) != 0) {
			Log::Add('ERR', "SMTP failed to connect: {$reply}", __LINE__, __FILE__);
			return false;
		}

		$this->sendData(base64_encode($this->Config['SMTP']['user']));

		$reply = $this->getSmtpData();

		if (strncmp($reply, '334', 3) != 0) {
			Log::Add('ERR', "SMTP invalid username: {$reply}", __LINE__, __FILE__);
			return false;
		}

		$this->sendData(base64_encode($this->Config['SMTP']['pass']));

		$reply = $this->getSmtpData();

		if (strncmp($reply, '235', 3) != 0) {
			Log::Add('ERR', "SMTP invalid password: {$reply}", __LINE__, __FILE__);
			return false;
		}

		return true;
	}
	//-

	/**
	 * Send SMTP data
	 * --
	 * @return	boolean
	 */
	private function sendData($data)
	{
		if (!fwrite($this->smtpConnect, $data . $this->Config['newline'])) {
			Log::Add('ERR', "SMTP data failed: {$data}", __LINE__, __FILE__);
			return false;
		}
		else
		{
			return true;
		}
	}
	//-

	/**
	 * Get SMTP data
	 * --
	 * @return	string
	 */
	private function getSmtpData()
	{
		$data = '';

		while ($str = fgets($this->smtpConnect, 512))
		{
			$data .= $str;

			if (substr($str, 3, 1) == ' ') {
				break;
			}
		}

		return $data;
	}
	//-

	/**
	 * Get Hostname
	 * --
	 * @return	string
	 */
	private function getHostname()
	{
		return (isset($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : 'localhost.localdomain';
	}
	//-

	/**
	 * Get IP
	 * --
	 * @return	string
	 */
	private function getIp()
	{
		if ($this->IP !== false) {
			return $this->IP;
		}

		$cip = (isset($_SERVER['HTTP_CLIENT_IP'])       && $_SERVER['HTTP_CLIENT_IP']       != '') ? $_SERVER['HTTP_CLIENT_IP']       : false;
		$rip = (isset($_SERVER['REMOTE_ADDR'])          && $_SERVER['REMOTE_ADDR']          != '') ? $_SERVER['REMOTE_ADDR']          : false;
		$fip = (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != '') ? $_SERVER['HTTP_X_FORWARDED_FOR'] : false;

		if ($cip && $rip)	$this->IP = $cip;
		elseif ($rip)		$this->IP = $rip;
		elseif ($cip)		$this->IP = $cip;
		elseif ($fip)		$this->IP = $fip;

		if (strpos($this->IP, ',') !== false) {
			$x = explode(',', $this->IP);
			$this->IP = end($x);
		}

		if (!preg_match( "/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/", $this->IP)) {
			$this->IP = '0.0.0.0';
		}

		unset($cip);
		unset($rip);
		unset($fip);

		return $this->IP;
	}
	//-

	/**
	 * Mime Types
	 * --
	 * @param	string	$ext
	 * --
	 * @return	string
	 */
	private function mimeTypes($ext='')
	{
		$mimes = array(	'hqx'	=>	'application/mac-binhex40',
						'cpt'	=>	'application/mac-compactpro',
						'doc'	=>	'application/msword',
						'bin'	=>	'application/macbinary',
						'dms'	=>	'application/octet-stream',
						'lha'	=>	'application/octet-stream',
						'lzh'	=>	'application/octet-stream',
						'exe'	=>	'application/octet-stream',
						'class'	=>	'application/octet-stream',
						'psd'	=>	'application/octet-stream',
						'so'	=>	'application/octet-stream',
						'sea'	=>	'application/octet-stream',
						'dll'	=>	'application/octet-stream',
						'oda'	=>	'application/oda',
						'pdf'	=>	'application/pdf',
						'ai'	=>	'application/postscript',
						'eps'	=>	'application/postscript',
						'ps'	=>	'application/postscript',
						'smi'	=>	'application/smil',
						'smil'	=>	'application/smil',
						'mif'	=>	'application/vnd.mif',
						'xls'	=>	'application/vnd.ms-excel',
						'ppt'	=>	'application/vnd.ms-powerpoint',
						'wbxml'	=>	'application/vnd.wap.wbxml',
						'wmlc'	=>	'application/vnd.wap.wmlc',
						'dcr'	=>	'application/x-director',
						'dir'	=>	'application/x-director',
						'dxr'	=>	'application/x-director',
						'dvi'	=>	'application/x-dvi',
						'gtar'	=>	'application/x-gtar',
						'php'	=>	'application/x-httpd-php',
						'php4'	=>	'application/x-httpd-php',
						'php3'	=>	'application/x-httpd-php',
						'phtml'	=>	'application/x-httpd-php',
						'phps'	=>	'application/x-httpd-php-source',
						'js'	=>	'application/x-javascript',
						'swf'	=>	'application/x-shockwave-flash',
						'sit'	=>	'application/x-stuffit',
						'tar'	=>	'application/x-tar',
						'tgz'	=>	'application/x-tar',
						'xhtml'	=>	'application/xhtml+xml',
						'xht'	=>	'application/xhtml+xml',
						'zip'	=>	'application/zip',
						'mid'	=>	'audio/midi',
						'midi'	=>	'audio/midi',
						'mpga'	=>	'audio/mpeg',
						'mp2'	=>	'audio/mpeg',
						'mp3'	=>	'audio/mpeg',
						'aif'	=>	'audio/x-aiff',
						'aiff'	=>	'audio/x-aiff',
						'aifc'	=>	'audio/x-aiff',
						'ram'	=>	'audio/x-pn-realaudio',
						'rm'	=>	'audio/x-pn-realaudio',
						'rpm'	=>	'audio/x-pn-realaudio-plugin',
						'ra'	=>	'audio/x-realaudio',
						'rv'	=>	'video/vnd.rn-realvideo',
						'wav'	=>	'audio/x-wav',
						'bmp'	=>	'image/bmp',
						'gif'	=>	'image/gif',
						'jpeg'	=>	'image/jpeg',
						'jpg'	=>	'image/jpeg',
						'jpe'	=>	'image/jpeg',
						'png'	=>	'image/png',
						'tiff'	=>	'image/tiff',
						'tif'	=>	'image/tiff',
						'css'	=>	'text/css',
						'html'	=>	'text/html',
						'htm'	=>	'text/html',
						'shtml'	=>	'text/html',
						'txt'	=>	'text/plain',
						'text'	=>	'text/plain',
						'log'	=>	'text/plain',
						'rtx'	=>	'text/richtext',
						'rtf'	=>	'text/rtf',
						'xml'	=>	'text/xml',
						'xsl'	=>	'text/xml',
						'mpeg'	=>	'video/mpeg',
						'mpg'	=>	'video/mpeg',
						'mpe'	=>	'video/mpeg',
						'qt'	=>	'video/quicktime',
						'mov'	=>	'video/quicktime',
						'avi'	=>	'video/x-msvideo',
						'movie'	=>	'video/x-sgi-movie',
						'doc'	=>	'application/msword',
						'word'	=>	'application/msword',
						'xl'	=>	'application/excel',
						'eml'	=>	'message/rfc822'
					);

		return (!isset($mimes[strtolower($ext)])) ? 'application/x-unknown-content-type' : $mimes[strtolower($ext)];
	}
	//-
}
//--
