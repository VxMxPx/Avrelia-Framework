<?php

use Avrelia\Core\FileSystem as FileSystem;
use Avrelia\Core\Log        as Log;
use Avrelia\Core\Str        as Str;

namespace Plug\Avrelia;

/**
 * Upload Plug
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Upload
{
    # Those Messages Are For Log Only!
    private static $file_messages = array(
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
    private static $mime_list = array(
        'application/atom+xml'      => array('atom', 'xml'),
        'application/EDI-X12'       => null,
        'application/EDIFACT'       => null,
        'application/json'          => 'json',
        'application/javascript'    => 'js',
        'application/octet-stream'  => null,
        'application/ogg'           => 'ogg',
        'application/pdf'           => 'pdf',
        'application/postscript'    => 'ps',
        'application/soap+xml'      => null,
        'application/x-woff'        => 'woff',
        'application/xhtml+xml'     => array('xhtml', 'xht', 'xml', 'html', 'htm'),
        'application/xml-dtd'       => 'dtd',
        'application/xop+xml'       => null,
        'application/zip'           => 'zip',
        'application/x-gzip'        => 'gz',

        'audio/basic'               => 'au',
        'audio/basic'               => 'snd',
        'audio/mid'                 => 'mid',
        'audio/mid'                 => 'rmi',
        'audio/mpeg'                => 'mp3',
        'audio/x-aiff'              => 'aif',
        'audio/x-aiff'              => 'aifc',
        'audio/x-aiff'              => 'aiff',
        'audio/x-mpegurl'           => 'm3u',
        'audio/x-pn-realaudio'      => 'ra',
        'audio/x-pn-realaudio'      => 'ram',
        'audio/x-wav'               => 'wav',

        'image/bmp'                 => 'bmp',
        'image/cis-cod'             => 'cod',
        'image/gif'                 => 'gif',
        'image/ief'                 => 'ief',
        'image/jpeg'                => array('jpe', 'jpeg', 'jpg'),
        'image/pipeg'               => 'jfif',
        'image/pjpeg'               => 'jpeg',
        'image/png'                 => 'png',
        'image/svg+xml'             => 'svg',
        'image/tiff'                => array('tif', 'tiff'),
        'image/x-cmu-raster'        => 'ras',
        'image/x-cmx'               => 'cmx',
        'image/x-icon'              => 'ico',
        'image/x-png'               => 'png',
        'image/x-portable-anymap'   => 'pnm',
        'image/x-portable-bitmap'   => 'pbm',
        'image/x-portable-graymap'  => 'pgm',
        'image/x-portable-pixmap'   => 'ppm',
        'image/x-rgb'               => 'rgb',
        'image/x-xbitmap'           => 'xbm',
        'image/x-xpixmap'           => 'xpm',
        'image/x-xwindowdump'       => 'xwd',

        'message/rfc822'            => 'mht',
        'message/rfc822'            => 'mhtml',
        'message/rfc822'            => 'nws',

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

        'video/mpeg'                => 'mp2',
        'video/mpeg'                => 'mpa',
        'video/mpeg'                => 'mpe',
        'video/mpeg'                => 'mpeg',
        'video/mpeg'                => 'mpg',
        'video/mpeg'                => 'mpv2',
        'video/quicktime'           => 'mov',
        'video/quicktime'           => 'qt',
        'video/x-la-asf'            => 'lsf',
        'video/x-la-asf'            => 'lsx',
        'video/x-ms-asf'            => 'asf',
        'video/x-ms-asf'            => 'asr',
        'video/x-ms-asf'            => 'asx',
        'video/x-msvideo'           => 'avi',
        'video/x-sgi-movie'         => 'movie',

        'x-world/x-vrml'            => 'flr',
        'x-world/x-vrml'            => 'vrml',
        'x-world/x-vrml'            => 'wrl',
        'x-world/x-vrml'            => 'wrz',
        'x-world/x-vrml'            => 'xaf',
        'x-world/x-vrml'            => 'xof',
    );

    /**
     * Will return information about uploaded file in array format!
     * This will process some additional informations
     * and set error on invalid file request.
     * --
     * @param  mixed $file  Either name of the upload field, or sequence number,
     *                      if none is provided, the array of all uploaded files
     *                      will be returned!
     * --
     * @return array(
     *          filename => temp filename (e.g.: my-picture.jpg)
     *          fullpath => full path and filename (e.g.: /tmp/my-picture)
     *          size     => file size in bytes
     *          type     => file type (e.g.: image/jpeg)
     *          ext      => file extention (e.g.: jpg))
     */
    public static function get($file = null)
    {
        if (is_numeric($file)) {
            $num = 0;
            foreach ($_FILES as $file_key => $file_res) {
                if ($num == $file) {
                    return self::process($file_key);
                }
            }
            Log::war("Invalid file sequence: {$file}.");
            return array('error' => 'Invalid file number');
        }
        elseif (is_string($file)) {
            return self::process($file);
        }
        else {
            $return = array();
            foreach ($_FILES as $file_key => $file_res) {
                $return[$file_key] = self::process($file_key);
            }
            if (empty($return)) {
                return array('error' => 'No uploaded files.');
            }
            else {
                return $return;
            }
        }
    }

    /**
     * Will move uploaded file
     * and return new filename on success and false on failure.
     * --
     * @param  string $file         Uploaded file id (field name)
     * @param  string $destination  Directory to where file will be moved
     * @param  mixed  $filename     If not provided it will be auto-generated
     *                              current date, time and seq number, no extention
     *                              If you set it to true, the upload filename
     *                              will be used (but cleaned before)
     * --
     * @return mixed                Filename on success and false on failure
     */
    public static function move($file, $destination, $filename = null)
    {
        if (!self::exists($file)) {
            return false;
        }

        # Get File Info...
        $file_info = self::get($file);
        $ext = $file_info['ext'];

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
                $filename .= '.' . $ext;
            }
        }
        elseif ($filename === true) {
            $filename = Str::clean(basename($file_info['filename']), 'aA1', '_-') . '.' . $ext;
        }

        if (move_uploaded_file($file_info['fullpath'], ds($destination.'/'.$filename))) {
            return $filename;
        }
        else {
            return false;
        }
    }

    /**
     * Will chek if file was uploaded and exists (without error.)
     * --
     * @param  string $file  Uploaded file id (field name)
     * --
     * @return boolean
     */
    public static function exists($file)
    {
        if (!isset($_FILES[$file])) {
            Log::err('Invalid file id provided.');
            return false;
        }

        if ($_FILES[$file]['error'] !== 0) {
            Log::err('There was an error in file upload: `' .
                        self::$file_messages[$_FILES[$file]['error']] . '`.');
            return false;
        }

        return true;
    }

    /**
     * Check if uploaded file is allowed
     * --
     * @param  string  $file       Uploaded file id (field name)
     * @param  array   $extension  Provide an array of extensions ['jpg', ...]
     *                             If is only one can be string
     * @param  boolean $primitive  If set to true, we'll check only extension
     *                             of uploaded file, not the one set by mime!
     * --
     * @return boolean
     */
    public static function is_allowed($file, $extension, $primitive = true)
    {
        if (!self::exists($file)) {
            return false;
        }

        if (!is_array($extension) && is_string($extension)) {
            $extension = array($extension);
        }

        if (!is_array($extension) || empty($extension)) {
            Log::war('No extension provided to method.');
            return false;
        }

        if ($primitive) {
            $file_ext = FileSystem::Extension($_FILES[$file]['name']);
            if (!in_array($file_ext, $extension)) {
                Log::war("Invalid extension actual: `{$file_ext}`, allowed: `".
                            implode(', ', $extension).'`.');
                return false;
            }
            else {
                Log::inf("Extension is valid: `{$file_ext}`, allowed list: `" .
                            implode(', ', $extension) . "`.");
                return true;
            }
        }

        if ($mime = self::mime_type($file, $primitive)) {
            # We'll check extension and mime type...
            $ext_by_mime = isset(self::$mime_list[$mime])
                            ? self::$mime_list[$mime]
                            : false;
            if (!$ext_by_mime) {
                Log::war("We didn't get extension from mime: `{$mime}`.");
                return false;
            }
            if (!is_array($ext_by_mime)) {
                $ext_by_mime = array($ext_by_mime);
            }
            foreach ($extension as $ext) {
                if (in_array($ext, $ext_by_mime)) {
                    Log::inf("Extension found in mime: `{$mime}`, list: `".
                        implode(', ', $ext_by_mime) . "`, match: `{$ext}`.");
                    return true;
                }
            }

            # Refused!
            Log::war("Extension isn't allowed; determinant by mime: `{$mime}`, actual: `".
                implode(', ', $ext_by_mime) . "`, allowed: `" .
                implode(',', $extension) . "`.");
            return false;
        }
        else {
            Log::war('Failed to get mime type for file.');
            return false;
        }
    }

    /**
     * Return the mime type of an uploaded file
     * --
     * @param  string  $file    Uploaded file id (field name)
     * @param  boolean $simple  If set to true, we'll check only mime send by
     *                          'http header', not the one by php function!
     * --
     * @return string           false on error
     */
    public static function mime_type($file, $simple = true)
    {
        if (!self::exists($file)) {
            return false;
        }

        # Okay Check File Type Now!
        $type_head  = $_FILES[$file]['type'];

        if ($simple) {
            return $type_head;
        }

        // Return mime type ala mimetype extension
        $finfo     = finfo_open(FILEINFO_MIME_TYPE);
        $type_info = finfo_file($finfo, $_FILES[$file]['tmp_name']);
        finfo_close($finfo);

        # Check if they're the same....
        if ($type_head != $type_info) {
            Log::war('On checking file type there\'s mismatch on what we got
                from PHP function and http header; finfo_file: `' . $type_info .
                '`, http header: `' . $type_head . '`.');
        }

        return $type_info;
    }

    /**
     * Will process uploaded file and return array!
     * --
     * @param  string $file
     * --
     * @return array
     */
    private static function process($file)
    {
        if (!isset($_FILES[$file])) {
            Log::war('Requested file was not set in global $_FILES variable!');
            return array('error' =>
                'Requested file was not set in global $_FILES variable!');
        }

        # Set File
        $file_info = $_FILES[$file];

        # Add Info About File For Debuging
        Log::inf('Uploading file: ' . print_r($file_info, true));

        # Check If File Is Array
        if (!is_array($file_info)) {
            Log::err('File info is not array!');
            return array('error' => 'File info is not array!');
        }

        # Get File Info
        $file_name  = $file_info['name'];
        $file_type  = self::mime_type($file, true);
        $file_temp  = $file_info['tmp_name'];
        $file_error = $file_info['error'];
        $file_size  = $file_info['size'];
        $file_ext   = FileSystem::Extension($file_name);

        # Is Error ?
        if ($file_error === 0) {
            Log::inf('File message: "' .
                self::$file_messages[$file_error] . '"');
        }
        elseif ($file_error === 4) {
            Log::inf('File upload warning: "' .
                self::$file_messages[$file_error] . '"');
            return array('error' => 'File upload warning: "' .
                self::$file_messages[$file_error] . '"');
        }
        else {
            Log::err('File upload error: "' .
                self::$file_messages[$file_error] . '"');
            return array('error' => 'File upload error: "' .
                self::$file_messages[$file_error] . '"');
        }

        return array(
            'filename' => $file_name,
            'fullpath' => $file_temp,
            'size'     => $file_size,
            'type'     => $file_type,
            'ext'      => $file_ext
        );
    }

}