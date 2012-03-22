<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Will Take Care of Uploaded Files
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2011-06-29
 */

class uUpload
{
	# Those Messages Are For Log Only!
	private static $FileMessages = array(
		'0' => 'There is no error, the file uploaded with success.',
		'1' => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
		'2' => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
		'3' => 'The uploaded file was only partially uploaded.',
		'4' => 'No file was uploaded.',
		'5' => 'Missing a temporary folder.',
		'6' => 'Failed to write file to disk.',
		'7' => 'File upload stopped by extension.',
	);

	# Mime List
	private static $MimeList = array(
		'application/atom+xml'     => array('atom', 'xml'),
		'application/EDI-X12'      => null,
		'application/EDIFACT'      => null,
		'application/json'         => 'json',
		'application/javascript'   => 'js',
		'application/octet-stream' => null,
		'application/ogg'          => 'ogg',
		'application/pdf'          => 'pdf',
		'application/postscript'   => 'ps',
		'application/soap+xml'     => null,
		'application/x-woff'       => 'woff',
		'application/xhtml+xml'    => array('xhtml', 'xht', 'xml', 'html', 'htm'),
		'application/xml-dtd'      => 'dtd',
		'application/xop+xml'      => null,
		'application/zip'          => 'zip',
		'application/x-gzip'       => 'gz',

		'audio/basic'          => 'au',
		'audio/basic'          => 'snd',
		'audio/mid'            => 'mid',
		'audio/mid'            => 'rmi',
		'audio/mpeg'           => 'mp3',
		'audio/x-aiff'         => 'aif',
		'audio/x-aiff'         => 'aifc',
		'audio/x-aiff'         => 'aiff',
		'audio/x-mpegurl'      => 'm3u',
		'audio/x-pn-realaudio' => 'ra',
		'audio/x-pn-realaudio' => 'ram',
		'audio/x-wav'          => 'wav',

		'image/bmp'                => 'bmp',
		'image/cis-cod'            => 'cod',
		'image/gif'                => 'gif',
		'image/ief'                => 'ief',
		'image/jpeg'               => array('jpe', 'jpeg', 'jpg'),
		'image/pipeg'              => 'jfif',
		'image/pjpeg'              => 'jpeg',
		'image/png'                => 'png',
		'image/svg+xml'            => 'svg',
		'image/tiff'               => array('tif', 'tiff'),
		'image/x-cmu-raster'       => 'ras',
		'image/x-cmx'              => 'cmx',
		'image/x-icon'             => 'ico',
		'image/x-png'              => 'png',
		'image/x-portable-anymap'  => 'pnm',
		'image/x-portable-bitmap'  => 'pbm',
		'image/x-portable-graymap' => 'pgm',
		'image/x-portable-pixmap'  => 'ppm',
		'image/x-rgb'              => 'rgb',
		'image/x-xbitmap'          => 'xbm',
		'image/x-xpixmap'          => 'xpm',
		'image/x-xwindowdump'      => 'xwd',

		'message/rfc822' => 'mht',
		'message/rfc822' => 'mhtml',
		'message/rfc822' => 'nws',

		'text/css'                  => 'css',
		'text/h323'                 => '323',
		'text/html'                 => 'htm',
		'text/html'                 => 'html',
		'text/html'                 => 'stm',
		'text/iuls'                 => 'uls',
		'text/plain'                => 'bas',
		'text/plain'                => 'c',
		'text/plain'                => 'h',
		'text/plain'                => 'txt',
		'text/richtext'             => 'rtx',
		'text/scriptlet'            => 'sct',
		'text/tab-separated-values' => 'tsv',
		'text/webviewhtml'          => 'htt',
		'text/x-component'          => 'htc',
		'text/x-setext'             => 'etx',
		'text/x-vcard'              => 'vcf',

		'video/mpeg'        => 'mp2',
		'video/mpeg'        => 'mpa',
		'video/mpeg'        => 'mpe',
		'video/mpeg'        => 'mpeg',
		'video/mpeg'        => 'mpg',
		'video/mpeg'        => 'mpv2',
		'video/quicktime'   => 'mov',
		'video/quicktime'   => 'qt',
		'video/x-la-asf'    => 'lsf',
		'video/x-la-asf'    => 'lsx',
		'video/x-ms-asf'    => 'asf',
		'video/x-ms-asf'    => 'asr',
		'video/x-ms-asf'    => 'asx',
		'video/x-msvideo'   => 'avi',
		'video/x-sgi-movie' => 'movie',

		'x-world/x-vrml' => 'flr',
		'x-world/x-vrml' => 'vrml',
		'x-world/x-vrml' => 'wrl',
		'x-world/x-vrml' => 'wrz',
		'x-world/x-vrml' => 'xaf',
		'x-world/x-vrml' => 'xof',
	);


	/**
	 * Will return information about uploaded file in array format!
	 * This will process some additional informations and set error on invalid file request.
	 *
	 * @param mixed $file -- either name of the upload field, or sequence number, if none is provided, the array of all uploaded files will be returned!
	 *
	 * @return array(
	 * 			filename => temp filename (e.g.: my-picture.jpg)
	 *			fullpath => full path and filename (e.g.: /tmp/my-picture)
	 *  		size     => file size in bytes
	 *  		type     => file type (e.g.: image/jpeg)
	 *  		ext      => file extention (e.g.: jpg)
	 */
	public static function Get($file=null)
	{
		if (is_numeric($file)) {
			$num = 0;
			foreach ($_FILES as $fileKey => $fileRes) {
				if ($num == $file) {
					return self::process($fileKey);
				}
			}
			Log::Add('WAR', "Invalid file sequence: {$file}.", __LINE__, __FILE__);
			return array('error' => 'Invalid file number');
		}
		elseif (is_string($file)) {
			return self::process($file);
		}
		else {
			$Return = array();
			foreach ($_FILES as $fileKey => $fileRes) {
				$Return[$fileKey] = self::process($fileKey);
			}
			if (empty($Return)) {
				return array('error' => 'No uploaded files.');
			}
			else {
				return $Return;
			}
		}
	}
	//-

	/**
	 * Will move uploaded file and return new filename on success and false on failure.
	 *
	 * @param string $file         -- uploaded file id (field name)
	 * @param string $destination  -- directory to where file will be moved
	 * @param mixed  $filename     -- if null will be auto-generated (current date+time & seq number) - no extention!!
	 * 								- if you set it to true, the upload filename will be used (but cleaned before)
	 *
	 * @return mixed (filename on success and false on failure)
	 */
	public static function Move($file, $destination, $filename=null)
	{
		if (!self::Exists($file)) {
			return false;
		}

		# Get File Info...
		$File = self::Get($file);
		$ext  = $File['ext'];

		if (is_null($filename)) {
			$filename = date('Y_m_d_H_i_s');
			if (file_exists(ds($destination . '/' . $filename . '.' . $ext))) {
				$n    = 1;
				$base = $filename;
				do {
					$filename = $base . '_' . $n . '.' . $ext;
					$n++;
				}
				while(file_exists(ds($destination . '/' . $filename)));
			}
			else {
				$filename .= '.'.$ext;
			}
		}
		elseif ($filename === true) {
			$filename = vString::Clean(basename($File['filename']), 200, 'a A 1 c', '_-') . '.' . $ext;
		}

		if (move_uploaded_file($File['fullpath'], ds($destination.'/'.$filename))) {
			return $filename;
		}
		else {
			return false;
		}
	}
	//-

	/**
	 * Will chek if file was uploaded and exists (without error.)
	 *
	 * @param string $file -- uploaded file id (field name)
	 *
	 * @return bool
	 */
	public static function Exists($file)
	{
		if (!isset($_FILES[$file])) {
			Log::Add('ERR', 'Invalid file id provided.', __LINE__, __FILE__);
			return false;
		}

		if ($_FILES[$file]['error'] !== 0) {
			Log::Add('ERR', 'It seems there was some error in file upload: `' . self::$FileMessages[$_FILES[$file]['error']] . '`.', __LINE__, __FILE__);
			return false;
		}

		return true;
	}
	//-

	/**
	 * Check if uploaded file is allowed
	 *
	 * @param string $file     -- uploaded file id (field name)
	 * @param array $extension -- provide an array of extensions array('jpg', 'jpeg')
	 * 							- if is only one can be string
	 * @param bool $primitive  -- if set to true, we'll check only extension of uploaded file, not the one set by mime!
	 *
	 * @return bool
	 */
	public static function IsAllowed($file, $extension, $primitive=true)
	{
		if (!self::Exists($file)) {
			return false;
		}

		if (!is_array($extension) && is_string($extension)) {
			$extension = array($extension);
		}

		if (!is_array($extension) || empty($extension)) {
			Log::Add('WAR', 'No extension provided to method.', __LINE__, __FILE__);
			return false;
		}

		if ($primitive) {
			$fileExt = FileSystem::Extension($_FILES[$file]['name']);
			if (!in_array($fileExt, $extension)) {
				Log::Add('WAR', "Invalid extension actual: `{$fileExt}`, allowed: `" . implode(', ', $extension) . "`.", __LINE__, __FILE__);
				return false;
			}
			else {
				Log::Add('INF', "Extension is valid: `{$fileExt}`, allowed list: `" . implode(', ', $extension) . "`.", __LINE__, __FILE__);
				return true;
			}
		}

		if ($mime = self::MimeType($file, $primitive)) {
			# We'll check extension and mime type...
			$extByMime = isset(self::$MimeList[$mime]) ? self::$MimeList[$mime] : false;
			if (!$extByMime) {
				Log::Add('WAR', "We didn't get extension from mime: `{$mime}`.", __LINE__, __FILE__);
				return false;
			}
			if (!is_array($extByMime)) {
				$extByMime = array($extByMime);
			}
			foreach ($extension as $ext) {
				if (in_array($ext, $extByMime)) {
					Log::Add('INF', "Extension found in mime: `{$mime}`, list: `" . implode(', ', $extByMime) . "`, match: `{$ext}`.", __LINE__, __FILE__);
					return true;
				}
			}

			# Refused!
			Log::Add('WAR', "Extension isn't allowed; determinant by mime: `{$mime}`, actual: `" . implode(', ', $extByMime) . "`, allowed: `".implode(',', $extension)."`.", __LINE__, __FILE__);
			return false;
		}
		else {
			Log::Add('WAR', 'Failed to get mime type for file.', __LINE__, __FILE__);
			return false;
		}
	}
	//-

	/**
	 * Return the mime type of an uploaded file
	 *
	 * @param string $file   -- uploaded file id (field name)
	 * @param bool   $simple -- if set to true, we'll check only mime send by 'http header', not the one by php function!
	 *
	 * @return string (false on error)
	 */
	public static function MimeType($file, $simple=true)
	{
		if (!self::Exists($file)) {
			return false;
		}

		# Okay Check File Type Now!
		$typeHead  = $_FILES[$file]['type'];

		if ($simple) {
			return $typeHead;
		}

		$finfo     = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
		$typeInfo  = finfo_file($finfo, $_FILES[$file]['tmp_name']);
		finfo_close($finfo);

		# Check if they're the same....
		if ($typeHead != $typeInfo) {
			Log::Add('WAR', 'On checking file type there\'s mismatch on what we got from PHP function and http header;
					 finfo_file: `' . $typeInfo . '`, http header: `' . $typeHead . '`.', __LINE__, __FILE__);
		}

		return $typeInfo;
	}
	//-


	// ------- PRIVATE -----------------------------

	/**
	 * Will process uploaded file and return array!
	 *
	 * @param string $file
	 *
	 * @return array
	 */
	private static function process($file)
	{
		if (!isset($_FILES[$file])) {
			Log::Add('WAR', 'Requested file was not set in global $_FILES variable!', __LINE__, __FILE__);
			return array('error' => 'Requested file was not set in global $_FILES variable!');
		}

		# Set File
		$File = $_FILES[$file];

		# Add Info About File For Debuging
		Log::Add('INF', 'Uploading file: ' . print_r($File, true), __LINE__, __FILE__);

		# Check If File Is Array
		if (!is_array($File)) {
			Log::Add('ERR', 'File info is not array!', __LINE__, __FILE__);
			return array('error' => 'File info is not array!');
		}

		# Get File Info
		$fileName  = $File['name'];
		$fileType  = self::MimeType($file, true);
		$fileTemp  = $File['tmp_name'];
		$fileError = $File['error'];
		$fileSize  = $File['size'];
		$fileExt   = FileSystem::Extension($fileName);

		# Is Error ?
		if ($fileError === 0) {
			Log::Add('INF', 'File message: "' . self::$FileMessages[$fileError] . '"', __LINE__, __FILE__);
		}
		elseif ($fileError === 4) {
			Log::add('INF', 'File upload warning: "' . self::$FileMessages[$fileError] . '"', __LINE__, __FILE__);
			return array('error' => 'File upload warning: "' . self::$FileMessages[$fileError] . '"');
		}
		else {
			Log::add('ERR', 'File upload error: "' . self::$FileMessages[$fileError] . '"', __LINE__, __FILE__);
			return array('error' => 'File upload error: "' . self::$FileMessages[$fileError] . '"');
		}

		return array(
			'filename' => $fileName,
			'fullpath' => $fileTemp,
			'size'     => $fileSize,
			'type'     => $fileType,
			'ext'      => $fileExt
		);
	}
	//-
}
//--
