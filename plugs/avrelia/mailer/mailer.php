<?php namespace Plug\Avrelia; if (!defined('AVRELIA')) die('Access is denied!');

use Avrelia\Core\Plug as Plug;
use Avrelia\Core\Log  as Log;

/**
 * Mailer Class
 * -----------------------------------------------------------------------------
 * Bases on CodeIgniter's Email Class
 * http://codeigniter.com/user_guide/license.html
 * ----
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Mailer
{
    protected $config           = array();    # array     All the configurations for this instance
    protected $safe_mode        = false;      # boolean   Is PHP safe-mode turned on?
    protected $subject          = '';         # string    The subject of message
    protected $body_html        = null;       # string    HTML version of body
    protected $body_plain       = null;       # string    Plain version of body
    protected $body_final       = null;       # string
    protected $alt_boundary     = '';         # string
    protected $atc_boundary     = '';         # string
    protected $header_str       = '';         # string
    protected $smtp_connection  = '';         # string
    protected $encoding         = '8bit';     # string
    protected $ip               = false;      # string
    protected $smtp_auth        = false;      # boolean
    protected $reply_to_falg    = false;      # boolean
    protected $recipients       = array();    # array
    protected $cc_array         = array();    # array
    protected $bcc_array        = array();    # array
    protected $headers          = array();    # array
    protected $attach_name      = array();    # array
    protected $attach_type      = array();    # array
    protected $attach_disp      = array();    # array
    protected $protocols        = array(      # array     Allowed protocols
                                'mail', 'sendmail', 'smtp');
    protected $base_characters  = array(      # array     7-bit charsets (excluding language suffix)
                                'us-ascii', 'iso-2022-');
    protected $bit_depths       = array(      # array
                                '7bit', '8bit');
    protected $priorities       = array(      # array
                                '1 (Highest)', '2 (High)', '3 (Normal)', '4 (Low)', '5 (Lowest)');

    /**
     * Set some default configs
     * --
     * @return  void
     */
    public function __construct()
    {
        $this->config = Plug::get_config(__FILE__);

        $this->smtp_auth = ($this->config['SMTP']['user'] && $this->config['SMTP']['pass']);
        $this->safe_mode = ((boolean)ini_get('safe_mode') === false) ? false : true;
    }

    /*  ****************************************************** *
     *          Public Methods
     *  **************************************  */

    /**
     * Who is sending this mail
     * --
     * @param   string  $from
     * @param   string  $name
     * --
     * @return  $this
     */
    public function from($from, $name=null)
    {
        # In case someone set it as First Last <first.last@domain.tld>
        if (preg_match( '/\<(.*)\>/', $from, $match)) {
            $from = $match['1'];
        }

        $this->_validate_email($this->_str_to_array($from));

        # Prepare the display name
        if ($name) {
            # Only use Q encoding if there are characters that would require it
            if (!preg_match('/[\200-\377]/', $name)) {
                # Add slashes for non-printing characters, slashes, and double quotes,
                # and surround it in double quotes
                $name = '"'.addcslashes($name, "\0..\37\177'\"\\").'"';
            }
            else {
                $name = $this->_prep_q_encoding($name, true);
            }
        }

        $this->_set_header('From', $name.' <'.$from.'>');
        $this->_set_header('Return-Path', '<'.$from.'>');

        return $this;
    }

    /**
     * ReplyTo, if not set, from will be used.
     * --
     * @param   string  $replyto
     * @param   string  $name
     * --
     * @return  $this
     */
    public function reply_to($replyto, $name=null)
    {
        # In case someone set it as First Last <first.last@domain.tld>
        if (preg_match('/\<(.*)\>/', $replyto, $match)) {
            $replyto = $match['1'];
        }

        $this->_validate_email($this->_str_to_array($replyto));

        if ($name) {
            $name = $replyto;
        }

        if (strncmp($name, '"', 1) != 0) {
            $name = '"'.$name.'"';
        }

        $this->_set_header('Reply-To', $name.' <'.$replyto.'>');
        $this->reply_to_falg = true;

        return $this;
    }

    /**
     * Set Recipients
     * --
     * @param   string  $to
     * --
     * @return  $this
     */
    public function to($to)
    {
        $to = $this->_str_to_array($to);
        $to = $this->_clean_email($to);


        $this->_validate_email($to);

        if ($this->_get_protocol() != 'mail') {
            $this->_set_header('To', implode(", ", $to));
        }

        switch ($this->_get_protocol())
        {
            case 'smtp':
                $this->recipients = $to;
                break;

            case 'sendmail' :
            case 'mail'     :
                $this->recipients = implode(', ', $to);
                break;
        }

        return $this;
    }

    /**
     * Set CC
     * --
     * @param   string  $cc
     * --
     * @return  $this
     */
    public function cc($cc)
    {
        $cc = $this->_str_to_array($cc);
        $cc = $this->_clean_email($cc);

        $this->_validate_email($cc);

        $this->_set_header('Cc', implode(', ', $cc));

        if ($this->_get_protocol() == 'smtp') {
            $this->cc_array = $cc;
        }

        return $this;
    }

    /**
     * Set BCC
     * --
     * @param   string  $bcc
     * @param   integer $limit
     * --
     * @return  $this
     */
    public function bcc($bcc, $limit=false)
    {
        if ($limit) {
            $this->config['bcc_batch_mode'] = true;
            $this->config['bcc_batch_size'] = (int) $limit;
        }

        $bcc = $this->_str_to_array($bcc);
        $bcc = $this->_clean_email($bcc);

        $this->_validate_email($bcc);

        if ($this->_get_protocol() == 'smtp' ||
                ($this->config['bcc_batch_mode'] && count($bcc) > $this->config['bcc_batch_size']))
        {
            $this->bcc_array = $bcc;
        }
        else {
            $this->_set_header('Bcc', implode(', ', $bcc));
        }

        return $this;
    }

    /**
     * Set Email Subject
     * --
     * @param   string  $subject
     * --
     * @return  $this
     */
    public function subject($subject)
    {
        $subject = $this->_prep_q_encoding($subject);
        $this->_set_header('Subject', $subject);

        return $this;
    }

    /**
     * Set message's HTML body
     * --
     * @param  string $message
     * --
     * @return $this
     */
    public function message_html($message)
    {
        $this->body_html = stripslashes(rtrim($message));
        return $this;
    }

    /**
     * Set message's plain body
     * --
     * @param  string $message
     * --
     * @return $this
     */
    public function message_plain($message)
    {
        $this->body_plain = str_replace('\n', "\n", $plain);
        return $this;
    }

    /**
     * Assign file attachments
     * --
     * @param   mixed   $filename       List of files
     * @param   string  $disposition    Attachments || inline
     * --
     * @return  $this
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
                Log::war("File not found: `{$filename}`.");
            }
            else {
                $this->attach_name[] = $filename;
                $this->attach_type[] = $this->_mime_types(pathinfo($filename, PATHINFO_EXTENSION));
                $this->attach_disp[] = $disposition; # Can also be 'inline'  Not sure if it matters
            }
        }

        return $this;
    }

    /**
     * Set Priority
     * --
     * @param   integer $priority
     * --
     * @return  $this
     */
    public function priority($priority=3)
    {
        if ($priority < 1 || $priority > 5) {
            Log::war("Invalid priority");
        }
        else {
            $this->config['priority'] = $priority;
        }

        return $this;
    }

    /**
     * Send Email
     * --
     * @return  boolean
     */
    public function send()
    {
        if ($this->reply_to_falg == false) {
            $this->reply_to($this->headers['From']);
        }

        if ((!isset($this->recipients) && !isset($this->headers['To']))  &&
            (!isset($this->bcc_array)   && !isset($this->headers['Bcc'])) &&
            (!isset($this->headers['Cc'])))
        {
            Log::war("Email has no recipients.");
            return false;
        }

        $this->_build_headers();

        Log::inf("The e-mail with following parameters will be send:\n ". print_r($this->headers, true));

        if ($this->config['bcc_batch_mode'] && count($this->bcc_array) > 0) {
            if (count($this->bcc_array) > $this->config['bcc_batch_size']) {
                return $this->_batch_bcc_send();
            }
        }

        $this->_build_message();

        return $this->_spool_email();
    }

    /*  ****************************************************** *
     *          Private Methods
     *  **************************************  */

    /**
     * Add a Header Item
     * --
     * @param   string  $header
     * @param   mixed   $value
     * --
     * @return  void
     */
    protected function _set_header($header, $value)
    {
        $this->headers[$header] = $value;
    }

    /**
     * Convert a String to an Array
     * --
     * @param   string  $email
     * --
     * @return  array
     */
    protected function _str_to_array($email)
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

    /**
     * Set Message Boundary
     * --
     * @return  void
     */
    protected function _set_boundaries()
    {
        $this->alt_boundary = "B_ALT_".uniqid(''); # multipart/alternative
        $this->atc_boundary = "B_ATC_".uniqid(''); # attachment boundary
    }

    /**
     * Get the Message ID
     * --
     * @return  string
     */
    protected function _get_message_id()
    {
        $from = $this->headers['Return-Path'];
        $from = str_replace(">", "", $from);
        $from = str_replace("<", "", $from);

        return  "<".uniqid('').strstr($from, '@').">";
    }

    /**
     * Get Mail Protocol
     * --
     * @param   boolean $return
     * --
     * @return  mixed
     */
    protected function _get_protocol($return=true)
    {
        $this->config['protocol'] = strtolower($this->config['protocol']);
        $this->config['protocol'] = (!in_array($this->config['protocol'], $this->protocols, true)) ? 'mail' : $this->config['protocol'];

        if ($return) {
            return $this->config['protocol'];
        }
    }

    /**
     * Get Mail Encoding
     * --
     * @param   boolean $return
     * --
     * @return  mixed
     */
    protected function _get_encoding($return=true)
    {
        $this->encoding = (!in_array($this->encoding, $this->bit_depths)) ? '8bit' : $this->encoding;

        foreach ($this->base_characters as $charset)
        {
            if (strncmp($charset, $this->config['charset'], strlen($charset)) == 0) {
                $this->encoding = '7bit';
            }
        }

        if ($return) {
            return $this->encoding;
        }
    }

    /**
     * Get content type (text/html/attachment)
     * --
     * @return  string
     */
    protected function _get_content_type()
    {
        $this->body_html = trim($this->body_html);

        if  ($this->body_html && count($this->attach_name) == 0) {
            return 'html';
        }
        elseif ($this->body_html && count($this->attach_name)  > 0) {
            return 'html-attach';
        }
        elseif  (count($this->attach_name)  > 0) {
            return 'plain-attach';
        }
        else {
            return 'plain';
        }
    }

    /**
     * Mime message
     * --
     * @return  string
     */
    protected function _get_mime_message()
    {
        return 'This is a multi-part message in MIME format.'
                .$this->config['newline'].
                'Your email application may not support this format.';
    }

    /**
     * Validate Email Address
     * --
     * @param   string  $email
     * --
     * @return  boolean
     */
    protected function _validate_email($email)
    {
        if (!is_array($email)) {
            Log::war("Email must be an array: `{$email}`.");
            return false;
        }

        foreach ($email as $val)
        {
            if (!filter_var($val, FILTER_VALIDATE_EMAIL)) {
                Log::war("The e-mail might be invalid: `{$val}`.");
                return false;
            }
        }

        return false;
    }

    /**
     * Clean Extended Email Address: Joe Smith <joe@smith.com>
     * --
     * @param   mixed   $email
     * --
     * @return  mixed
     */
    protected function _clean_email($email)
    {
        if (is_array($email)) {
            $Result = array();

            foreach ($email as $val) {
                $Result[] = $this->_clean_email($val);
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

    /**
     * Build alternative plain text message
     *
     * This public function provides the raw message for use
     * in plain-text headers of HTML-formatted emails.
     * If the user hasn't specified his own alternative message
     * it creates one by stripping the HTML
     * --
     * @return  string
     */
    protected function _get_alt_message()
    {
        if ($this->body_plain) {
            return $this->_word_wrap($this->body_plain, '76');
        }

        if (preg_match('/\<body.*?\>(.*)\<\/body\>/si', $this->body_html, $match)) {
            $body = $match['1'];
        }
        else {
            $body = $this->body_html;
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

        return $this->_word_wrap($body, '76');
    }

    /**
     * Word Wrap
     * --
     * @param   string  $str
     * @param   integer $charlim
     * --
     * @return  string
     */
    protected function _word_wrap($str, $charlim=false)
    {
        # Set the character limit
        if ($charlim) {
            $charlim = (!$this->config['wrapchars']) ? 76 : $this->config['wrapchars'];
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
                $output .= $line.$this->config['newline'];
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
                $output .= $temp.$this->config['newline'].$line;
            }
            else {
                $output .= $line;
            }

            $output .= $this->config['newline'];
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

    /**
     * Build final headers
     * --
     * @return  void
     */
    protected function _build_headers()
    {
        $this->_set_header('X-Sender',     $this->_clean_email($this->headers['From']));
        $this->_set_header('X-Mailer',     $this->config['useragent']);
        $this->_set_header('X-Priority',   $this->priorities[$this->config['priority'] - 1]);
        $this->_set_header('Message-ID',   $this->_get_message_id());
        $this->_set_header('Mime-Version', '1.0');
    }

    /**
     * Write Headers as a string
     * --
     * @return  void
     */
    protected function _write_headers()
    {
        if ($this->config['protocol'] == 'mail') {
            $this->subject = $this->headers['Subject'];
            unset($this->headers['Subject']);
        }

        reset($this->headers);
        $this->header_str = '';

        foreach ($this->headers as $key => $val)
        {
            $val = trim($val);

            if ($val != '') {
                $this->header_str .= $key.": ".$val.$this->config['newline'];
            }
        }

        if ($this->_get_protocol() == 'mail') {
            $this->header_str = rtrim($this->header_str);
        }
    }

    /**
     * Build Final Body and attachments
     * --
     * @return  void
     */
    protected function _build_message()
    {
        if ($this->config['wordwrap'] === true && !$this->body_html) {
            $this->body_html = $this->_word_wrap($this->body_html);
        }

        $this->_set_boundaries();
        $this->_write_headers();

        $hdr = ($this->_get_protocol() == 'mail') ? $this->config['newline'] : '';
        $body = '';

        switch ($this->_get_content_type())
        {
            case 'plain' :

                $hdr .= "Content-Type: text/plain; charset=" . $this->config['charset'] . $this->config['newline'];
                $hdr .= "Content-Transfer-Encoding: " . $this->_get_encoding();

                if ($this->_get_protocol() == 'mail') {
                    $this->header_str .= $hdr;
                    $this->body_final = $this->body_plain;
                }
                else {
                    $this->body_final = $hdr . $this->config['newline'] . $this->config['newline'] . $this->body_plain;
                }

                return;
                break;

            case 'html' :

                if ($this->config['send_multipart'] === false) {
                    $hdr .= "Content-Type: text/html; charset=" . $this->config['charset'] . $this->config['newline'];
                    $hdr .= "Content-Transfer-Encoding: quoted-printable";
                }
                else {
                    $hdr .= "Content-Type: multipart/alternative; boundary=\"" . $this->alt_boundary . "\"" . $this->config['newline'] . $this->config['newline'];

                    $body .= $this->_get_mime_message() . $this->config['newline'] . $this->config['newline'];
                    $body .= "--" . $this->alt_boundary . $this->config['newline'];

                    $body .= "Content-Type: text/plain; charset=" . $this->config['charset'] . $this->config['newline'];
                    $body .= "Content-Transfer-Encoding: " . $this->_get_encoding() . $this->config['newline'] . $this->config['newline'];
                    $body .= $this->_get_alt_message() . $this->config['newline'] . $this->config['newline'] . "--" . $this->alt_boundary . $this->config['newline'];

                    $body .= "Content-Type: text/html; charset=" . $this->config['charset'] . $this->config['newline'];
                    $body .= "Content-Transfer-Encoding: quoted-printable" . $this->config['newline'] . $this->config['newline'];
                }

                $this->body_final = $body . $this->_prep_quoted_printable($this->body_html) . $this->config['newline'] . $this->config['newline'];

                if ($this->_get_protocol() == 'mail') {
                    $this->header_str .= $hdr;
                }
                else {
                    $this->body_final = $hdr . $this->body_final;
                }


                if ($this->config['send_multipart'] !== false) {
                    $this->body_final .= "--" . $this->alt_boundary . "--";
                }

                return;
                break;

            case 'plain-attach' :

                $hdr .= "Content-Type: multipart/".$this->config['multipart']."; boundary=\"" . $this->atc_boundary."\"" . $this->config['newline'] . $this->config['newline'];

                if ($this->_get_protocol() == 'mail') {
                    $this->header_str .= $hdr;
                }

                $body .= $this->_get_mime_message() . $this->config['newline'] . $this->config['newline'];
                $body .= "--" . $this->atc_boundary . $this->config['newline'];

                $body .= "Content-Type: text/plain; charset=" . $this->config['charset'] . $this->config['newline'];
                $body .= "Content-Transfer-Encoding: " . $this->_get_encoding() . $this->config['newline'] . $this->config['newline'];

                $body .= $this->body_plain . $this->config['newline'] . $this->config['newline'];

                break;

            case 'html-attach' :

                $hdr .= "Content-Type: multipart/".$this->config['multipart']."; boundary=\"" . $this->atc_boundary."\"" . $this->config['newline'] . $this->config['newline'];

                if ($this->_get_protocol() == 'mail') {
                    $this->header_str .= $hdr;
                }

                $body .= $this->_get_mime_message() . $this->config['newline'] . $this->config['newline'];
                $body .= "--" . $this->atc_boundary . $this->config['newline'];

                $body .= "Content-Type: multipart/alternative; boundary=\"" . $this->alt_boundary . "\"" . $this->config['newline'] .$this->config['newline'];
                $body .= "--" . $this->alt_boundary . $this->config['newline'];

                $body .= "Content-Type: text/plain; charset=" . $this->config['charset'] . $this->config['newline'];
                $body .= "Content-Transfer-Encoding: " . $this->_get_encoding() . $this->config['newline'] . $this->config['newline'];
                $body .= $this->_get_alt_message() . $this->config['newline'] . $this->config['newline'] . "--" . $this->alt_boundary . $this->config['newline'];

                $body .= "Content-Type: text/html; charset=" . $this->config['charset'] . $this->config['newline'];
                $body .= "Content-Transfer-Encoding: quoted-printable" . $this->config['newline'] . $this->config['newline'];

                $body .= $this->_prep_quoted_printable($this->body_html) . $this->config['newline'] . $this->config['newline'];
                $body .= "--" . $this->alt_boundary . "--" . $this->config['newline'] . $this->config['newline'];

                break;
        }

        $attachment = array();

        $z = 0;

        for ($i=0; $i < count($this->attach_name); $i++)
        {
            $filename = $this->attach_name[$i];
            $basename = basename($filename);
            $ctype = $this->attach_type[$i];

            $h  = "--".$this->atc_boundary.$this->config['newline'];
            $h .= "Content-type: ".$ctype."; ";
            $h .= "name=\"".$basename."\"".$this->config['newline'];
            $h .= "Content-Disposition: ".$this->attach_disp[$i].";".$this->config['newline'];
            $h .= "Content-Transfer-Encoding: base64".$this->config['newline'];

            $attachment[$z++] = $h;
            $file = filesize($filename) +1;

            if (!$fp = fopen($filename, 'rb')) {
                Log::err("Email attachment unreadable: `{$filename}`.");
                return false;
            }

            $attachment[$z++] = chunk_split(base64_encode(fread($fp, $file)));
            fclose($fp);
        }

        $body .= implode($this->config['newline'], $attachment).$this->config['newline']."--".$this->atc_boundary."--";

        if ($this->_get_protocol() == 'mail') {
            $this->body_final = $body;
        }
        else {
            $this->body_final = $hdr . $body;
        }

        return;
    }

    /**
     * Prep Quoted Printable
     *
     * Prepares string for Quoted-Printable Content-Transfer-Encoding
     * Refer to RFC 2045 http://www.ietf.org/rfc/rfc2045.txt
     * --
     * @param   string  $str
     * @param   integer $charlim
     * --
     * @return  string
     */
    protected function _prep_quoted_printable($str, $charlim=false)
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
                    $output .= $temp.$escape.$this->config['crlf'];
                    $temp = '';
                }

                # Add the character to our temporary line
                $temp .= $char;
            }

            # Add our completed line to the output
            $output .= $temp.$this->config['crlf'];
        }

        # Get rid of extra CRLF tacked onto the end
        $output = substr($output, 0, strlen($this->config['crlf']) * -1);

        return $output;
    }

    /**
     * Prep Q Encoding
     *
     * Performs "Q Encoding" on a string for use in email headers.  It's related
     * but not identical to quoted-printable, so it has its own method
     * --
     * @param   string  $str
     * @param   boolean $from   Set to true for processing From: headers
     * --
     * @return  string
     */
    protected function _prep_q_encoding($str, $from=false)
    {
        $str = str_replace(array("\r", "\n"), array('', ''), $str);

        # Line length must not exceed 76 characters, so we adjust for
        # a space, 7 extra characters =??Q??=, and the charset that we will add to each line
        $limit = 75 - 7 - strlen($this->config['charset']);

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
                $output .= $temp.$this->config['crlf'];
                $temp = '';
            }

            # Add the character to our temporary line
            $temp .= $char;
        }

        $str = $output.$temp;

        # Wrap each line with the shebang, charset, and transfer encoding
        # the preceding space on successive lines is required for header "folding"
        $str = trim(preg_replace('/^(.*)$/m', ' =?'.$this->config['charset'].'?Q?$1?=', $str));

        return $str;
    }

    /**
     * Batch Bcc Send. Sends groups of BCCs in batches.
     * --
     * @return  boolean
     */
    protected function _batch_bcc_send()
    {
        $float = $this->config['bcc_batch_size'] -1;

        $set = '';

        $chunk = array();

        for ($i = 0; $i < count($this->bcc_array); $i++)
        {
            if (isset($this->bcc_array[$i])) {
                $set .= ", ".$this->bcc_array[$i];
            }

            if ($i == $float) {
                $chunk[] = substr($set, 1);
                $float = $float + $this->config['bcc_batch_size'];
                $set = '';
            }

            if ($i == count($this->bcc_array)-1) {
                $chunk[] = substr($set, 1);
            }
        }

        for ($i = 0; $i < count($chunk); $i++)
        {
            unset($this->headers['Bcc']);
            unset($bcc);

            $bcc = $this->_str_to_array($chunk[$i]);
            $bcc = $this->_clean_email($bcc);

            if ($this->config['protocol'] != 'smtp') {
                $this->_set_header('Bcc', implode(", ", $bcc));
            }
            else {
                $this->bcc_array = $bcc;
            }

            $this->_build_message();
            $this->_spool_email();
        }
    }

    /**
     * Unwrap special elements
     * --
     * @return  void
     */
    protected function _unwrap_specials()
    {
        $this->body_final = preg_replace_callback("/\{unwrap\}(.*?)\{\/unwrap\}/si", array($this, '_remove_nl_callback'), $this->body_final);
    }

    /**
     * Strip line-breaks via callback
     * --
     * @param   array   $matches
     * --
     * @return  string
     */
    protected function _remove_nl_callback($matches)
    {
        if (strpos($matches[1], "\r") !== false || strpos($matches[1], "\n") !== false) {
            $matches[1] = str_replace(array("\r\n", "\r", "\n"), '', $matches[1]);
        }

        return $matches[1];
    }

    /**
     * Spool mail to the mail server
     * --
     * @return  boolean
     */
    protected function _spool_email()
    {
        $this->_unwrap_specials();

        switch ($this->_get_protocol())
        {
            case 'mail':
                if (!$this->_send_with_mail()) {
                    Log::err("PHPMAIL: failed.");
                    return false;
                }
                break;

            case 'sendmail':
                if (!$this->_send_with_sendmail()) {
                    Log::err("SENDMAIL: failed.");
                    return false;
                }
                break;

            case 'smtp' :
                if (!$this->_send_with_smtp()) {
                    Log::err("SMTP: failed.");
                    return false;
                }
                break;

            default:
                Log::err("Invalid protocol: `" . $this->_get_protocol() . '`.');
                return false;
        }

        Log::inf(strtoupper($this->_get_protocol()) . ': mail was successfully sent!');
        return true;
    }

    /**
     * Send using mail()
     * --
     * @return  boolean
     */
    protected function _send_with_mail()
    {
        if ($this->safe_mode) {
            return mail($this->recipients, $this->subject, $this->body_final, $this->header_str);
        }
        else
        {
            # Most documentation of sendmail using the "-f" flag lacks a space after it, however
            # we've encountered servers that seem to require it to be in place.
            return mail($this->recipients, $this->subject, $this->body_final, $this->header_str, "-f ".$this->_clean_email($this->headers['From']));
        }
    }

    /**
     * Send using Sendmail
     * --
     * @return  boolean
     */
    protected function _send_with_sendmail()
    {
        $fp = @popen($this->config['mailpath'] . ' -oi -f ' . $this->_clean_email($this->headers['From']) . ' -t', 'w');

        if ($fp === false || $fp === NULL) {
            # Server probably has popen disabled, so nothing we can do to get a verbose error.
            Log::err('It seems server has `popen` disabled.');
            return false;
        }

        fputs($fp, $this->header_str);
        fputs($fp, $this->body_final);

        $status = pclose($fp);

        if (version_compare(PHP_VERSION, '4.2.3') == -1) {
            $status = $status >> 8 & 0xFF;
        }

        if ($status != 0) {
            Log::err('Email exit status: ' . $status);
            return false;
        }

        return true;
    }

    /**
     * Send using SMTP
     * --
     * @return  boolean
     */
    protected function _send_with_smtp()
    {
        if (!$this->config['SMTP']['host']) {
            Log::war('SMTP has no host set!');
            return false;
        }

        $this->_smtp_connect();
        $this->_smtp_authenticate();

        $this->_send_command('from', $this->_clean_email($this->headers['From']));

        foreach ($this->recipients as $val) {
            $this->_send_command('to', $val);
        }

        if (count($this->cc_array) > 0) {
            foreach ($this->cc_array as $val)
            {
                if ($val != '') {
                    $this->_send_command('to', $val);
                }
            }
        }

        if (count($this->bcc_array) > 0) {
            foreach ($this->bcc_array as $val)
            {
                if ($val != '') {
                    $this->_send_command('to', $val);
                }
            }
        }

        $this->_send_command('data');

        # Perform dot transformation on any lines that begin with a dot
        $this->_send_data($this->header_str . preg_replace('/^\./m', '..$1', $this->body_final));

        $this->_send_data('.');

        $reply = $this->_get_smtp_data();

        if (strncmp($reply, '250', 3) != 0) {
            Log::err('SMTP error: ' . $reply);
            return false;
        }
        else {
            Log::inf('SMTP replay: ' . $reply);
        }

        $this->_send_command('quit');
        return true;
    }

    /**
     * SMTP Connect
     * --
     * @return  string
     */
    protected function _smtp_connect()
    {
        $this->smtp_connection = fsockopen(
                                $this->config['SMTP']['host'],
                                $this->config['SMTP']['port'],
                                $errno,
                                $errstr,
                                $this->config['SMTP']['timeout']);

        if (!is_resource($this->smtp_connection)) {
            Log::err("SMTP error: `{$errno}`, `{$errstr}`.");
            return false;
        }

        Log::inf("SMTP data: " . $this->_get_smtp_data());
        return $this->_send_command('hello');
    }

    /**
     * Send SMTP command
     * --
     * @param   string  $cmd
     * @param   string  $data
     * --
     * @return  boolean
     */
    protected function _send_command($cmd, $data='')
    {
        switch ($cmd)
        {
            case 'hello':
                if ($this->smtp_auth || $this->_get_encoding() == '8bit') {
                    $this->_send_data('EHLO '.$this->_get_hostname());
                }
                else {
                    $this->_send_data('HELO '.$this->_get_hostname());
                }
                $resp = 250;
                break;

            case 'from':
                $this->_send_data('MAIL FROM:<'.$data.'>');
                $resp = 250;
                break;

            case 'to':
                $this->_send_data('RCPT TO:<'.$data.'>');
                $resp = 250;
                break;

            case 'data':
                $this->_send_data('DATA');
                $resp = 354;
                break;

            case 'quit':
                $this->_send_data('QUIT');
                $resp = 221;
                break;
        }

        $reply = $this->_get_smtp_data();

        if (substr($reply, 0, 3) != $resp) {
            Log::err("Command failed: `{$cmd}`, reply: {$reply}");
            return false;
        }
        else {
            Log::inf("Command: `{$cmd}`, reply: {$reply}");
        }

        if ($cmd == 'quit') {
            fclose($this->smtp_connection);
        }

        return true;
    }

    /**
     * SMTP Authenticate
     * --
     * @return  boolean
     */
    protected function _smtp_authenticate()
    {
        if (!$this->smtp_auth) {
            return true;
        }

        if (!$this->config['SMTP']['user'] && !$this->config['SMTP']['pass']) {
            Log::war("SMTP username or password isn't set.");
            return false;
        }

        $this->_send_data('AUTH LOGIN');

        $reply = $this->_get_smtp_data();

        if (strncmp($reply, '334', 3) != 0) {
            Log::err("SMTP failed to connect: {$reply}");
            return false;
        }

        $this->_send_data(base64_encode($this->config['SMTP']['user']));

        $reply = $this->_get_smtp_data();

        if (strncmp($reply, '334', 3) != 0) {
            Log::err("SMTP invalid username: {$reply}");
            return false;
        }

        $this->_send_data(base64_encode($this->config['SMTP']['pass']));

        $reply = $this->_get_smtp_data();

        if (strncmp($reply, '235', 3) != 0) {
            Log::err("SMTP invalid password: {$reply}");
            return false;
        }

        return true;
    }

    /**
     * Send SMTP data
     * --
     * @return  boolean
     */
    protected function _send_data($data)
    {
        if (!fwrite($this->smtp_connection, $data . $this->config['newline'])) {
            Log::err("SMTP data failed: {$data}");
            return false;
        }
        else
        {
            return true;
        }
    }

    /**
     * Get SMTP data
     * --
     * @return  string
     */
    protected function _get_smtp_data()
    {
        $data = '';

        while ($str = fgets($this->smtp_connection, 512))
        {
            $data .= $str;

            if (substr($str, 3, 1) == ' ') {
                break;
            }
        }

        return $data;
    }

    /**
     * Get Hostname
     * --
     * @return  string
     */
    protected function _get_hostname()
    {
        return (isset($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : 'localhost.localdomain';
    }

    /**
     * Mime Types
     * --
     * @param   string  $ext
     * --
     * @return  string
     */
    protected function _mime_types($ext='')
    {
        $mimes = array( 'hqx'   =>  'application/mac-binhex40',
                        'cpt'   =>  'application/mac-compactpro',
                        'doc'   =>  'application/msword',
                        'bin'   =>  'application/macbinary',
                        'dms'   =>  'application/octet-stream',
                        'lha'   =>  'application/octet-stream',
                        'lzh'   =>  'application/octet-stream',
                        'exe'   =>  'application/octet-stream',
                        'class' =>  'application/octet-stream',
                        'psd'   =>  'application/octet-stream',
                        'so'    =>  'application/octet-stream',
                        'sea'   =>  'application/octet-stream',
                        'dll'   =>  'application/octet-stream',
                        'oda'   =>  'application/oda',
                        'pdf'   =>  'application/pdf',
                        'ai'    =>  'application/postscript',
                        'eps'   =>  'application/postscript',
                        'ps'    =>  'application/postscript',
                        'smi'   =>  'application/smil',
                        'smil'  =>  'application/smil',
                        'mif'   =>  'application/vnd.mif',
                        'xls'   =>  'application/vnd.ms-excel',
                        'ppt'   =>  'application/vnd.ms-powerpoint',
                        'wbxml' =>  'application/vnd.wap.wbxml',
                        'wmlc'  =>  'application/vnd.wap.wmlc',
                        'dcr'   =>  'application/x-director',
                        'dir'   =>  'application/x-director',
                        'dxr'   =>  'application/x-director',
                        'dvi'   =>  'application/x-dvi',
                        'gtar'  =>  'application/x-gtar',
                        'php'   =>  'application/x-httpd-php',
                        'php4'  =>  'application/x-httpd-php',
                        'php3'  =>  'application/x-httpd-php',
                        'phtml' =>  'application/x-httpd-php',
                        'phps'  =>  'application/x-httpd-php-source',
                        'js'    =>  'application/x-javascript',
                        'swf'   =>  'application/x-shockwave-flash',
                        'sit'   =>  'application/x-stuffit',
                        'tar'   =>  'application/x-tar',
                        'tgz'   =>  'application/x-tar',
                        'xhtml' =>  'application/xhtml+xml',
                        'xht'   =>  'application/xhtml+xml',
                        'zip'   =>  'application/zip',
                        'mid'   =>  'audio/midi',
                        'midi'  =>  'audio/midi',
                        'mpga'  =>  'audio/mpeg',
                        'mp2'   =>  'audio/mpeg',
                        'mp3'   =>  'audio/mpeg',
                        'aif'   =>  'audio/x-aiff',
                        'aiff'  =>  'audio/x-aiff',
                        'aifc'  =>  'audio/x-aiff',
                        'ram'   =>  'audio/x-pn-realaudio',
                        'rm'    =>  'audio/x-pn-realaudio',
                        'rpm'   =>  'audio/x-pn-realaudio-plugin',
                        'ra'    =>  'audio/x-realaudio',
                        'rv'    =>  'video/vnd.rn-realvideo',
                        'wav'   =>  'audio/x-wav',
                        'bmp'   =>  'image/bmp',
                        'gif'   =>  'image/gif',
                        'jpeg'  =>  'image/jpeg',
                        'jpg'   =>  'image/jpeg',
                        'jpe'   =>  'image/jpeg',
                        'png'   =>  'image/png',
                        'tiff'  =>  'image/tiff',
                        'tif'   =>  'image/tiff',
                        'css'   =>  'text/css',
                        'html'  =>  'text/html',
                        'htm'   =>  'text/html',
                        'shtml' =>  'text/html',
                        'txt'   =>  'text/plain',
                        'text'  =>  'text/plain',
                        'log'   =>  'text/plain',
                        'rtx'   =>  'text/richtext',
                        'rtf'   =>  'text/rtf',
                        'xml'   =>  'text/xml',
                        'xsl'   =>  'text/xml',
                        'mpeg'  =>  'video/mpeg',
                        'mpg'   =>  'video/mpeg',
                        'mpe'   =>  'video/mpeg',
                        'qt'    =>  'video/quicktime',
                        'mov'   =>  'video/quicktime',
                        'avi'   =>  'video/x-msvideo',
                        'movie' =>  'video/x-sgi-movie',
                        'doc'   =>  'application/msword',
                        'word'  =>  'application/msword',
                        'xl'    =>  'application/excel',
                        'eml'   =>  'message/rfc822'
                    );

        return (!isset($mimes[strtolower($ext)])) ? 'application/x-unknown-content-type' : $mimes[strtolower($ext)];
    }
}
