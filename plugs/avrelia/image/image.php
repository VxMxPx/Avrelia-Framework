<?php

namespace Plug\Avrelia;

use Avrelia\Core\Log        as Log;
use Avrelia\Core\FileSystem as FileSystem;

/**
 * Image Plug
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Image
{
    # Allowed image extensions
    private $allow       = array('jpg','jpeg','gif','png');

    # Info about source image
    private $source      = array();

    # Info about destination
    private $destination = array();

    /**
     * Create new image from filename
     * --
     * @param   string  $filename  Full absolute path to the image
     * --
     * @return  void
     */
    public function __construct($filename)
    {
        Log::inf("Will load image `{$filename}`.");

        # Check if GD is enabled
        if (!extension_loaded('gd') || !function_exists('gd_info')) {
            Log::err("Can't get image object, the GD extension isn't loaded.");
            return false;
        }

        # File exists?
        if (!file_exists($filename)) {
            Log::war("Source file doesn't exists: `{$filename}`.");
            return false;
        }

        # Get Image extension
        $image_ext = strtolower(FileSystem::Extension($filename));

        # Check if extension is valid
        if (!in_array($image_ext, $this->allow)) {
            Log::war("Invalid file type: `{$image_ext}`.");
            return false;
        }

        $this->source = array(
            'file' => $filename,
            'ext'  => $image_ext,
        );
    }
    //-

    /**
     * Will add watermark (set text to false, to turn off watermar)
     * --
     * @param   string  $text
     * @param   array   $params Can contain: array(
     *      color       => #ffffff // Text color  - must be full! hexcolor value
     *      shadow      => #000000 // Text shadow - must be full! hexcolor value OR false
     *      font_path   => // full path to ttf font, or false for default font
     *      font_size   => // Integer, font site in px
     *      angle       => // Integer, font angle
     *      offset_x    => // Integer x offset
     *      offset_y    => // Integer y offset
     *  )
     * --
     * @return  $this
     */
    public function set_watermark($text, $params = array())
    {
        if (!$text) {
            $this->destination['watermark'] = false;
        }

        # Set it up!
        $this->destination['watermark'] = array_merge(
            array(
                'text'       => $text,
                'color'      => '#ffffff',
                'shadow'     => '#000000',
                'font_path'  => ds(dirname(__FILE__) . '/fonts/ambitsek.ttf'),
                'font_size'  => 6,
                'angle'      => 0,
                'offset_x'   => 5,
                'offset_y'   => 2,
            ),
            $params
        );

        Log::inf("Watermark with following properties will be added to image: ".
                    dump_r($this->destination['watermark']));

        return $this;
    }

    /**
     * Set sharpening to true or false
     * --
     * @param   booleam $enabled    Set to false, to disable sharpening
     * --
     * @return  $this
     */
    public function set_sharpening($enabled = false)
    {
        $this->destination['sharpening'] = $enabled;
        Log::inf('Sharpening will be: ' . ($enabled ? 'enabled' : 'disabled'));
        return $this;
    }

    /**
     * Set Destination Path
     * --
     * @param   string  $path   Only directory, must be valid!
     * --
     * @return  $this
     */
    public function set_destination($path)
    {
        $path = ds($path);

        if (!is_dir($path)) {
            Log::war("Destination is not valid directory: `{$path}`.");
        }
        else {
            $this->destination['path'] = $path;
            Log::inf("Destination was set to: `{$path}`.");
        }

        return $this;
    }

    /**
     * Will Set Image Quality
     * --
     * @param   integer $quality  Default value is 75
     * --
     * @return  $this
     */
    public function set_quality($quality = 75)
    {
        $quality = (int) $quality;

        if (!is_numeric($quality) || $quality < 10 || $quality > 100) {
            Log::war("Invalid quality value: `{$quality}`.");
        }
        else {
            $this->destination['quality'] = $quality;
            Log::inf("Quality will be set to `{$quality}`.");
        }

        return $this;
    }

    /**
     * Will set dimension for output image
     * --
     * @param   integer $width   If set to false we'll calculate it dynamicly
     * @param   integer $height  If set to false we'll calculate it dynamicly
     * --
     * @return  $this
     */
    public function set_dimension($width, $height)
    {
        if ($width  === false) { $width  = 0; }
        if ($height === false) { $height = 0; }

        if (!is_numeric($width) || !is_numeric($height)) {
            Log::war("Both, width({$width}) and height({$height}) must be numeric.");
            # Set both to 0
            $width = $height = 0;
        }

        $this->destination['width']  = $width;
        $this->destination['height'] = $height;

        return $this;
    }

    /**
     * Will Save Image (with all previous settings)
     * Return saved image full path or false on error!
     * --
     * @param   string  $fileName   Filename without path --
     *                              if you have set destination before
     * --
     * @return  mixed
     */
    public function save($fileName)
    {
        if (!$fileName) {
            Log::war("Filename must be set, in order to save image.");
            return false;
        }

        $destination  = isset($this->destination['path'])
                            ? $this->destination['path']
                            : '';
        $full_filename = ds($destination . '/' . $fileName);

        return $this->process($full_filename) ? $full_filename : false;
    }

    /**
     * Apply all setting on selected image.
     * --
     * @param   string  $destination_path
     * --
     * @return  boolean
     */
    private function process($destination_path)
    {
        # Check If File Exists
        if (!file_exists($this->source['file']) || !is_file($this->source['file'])) {
            Log::err('Source image doesn\'t exists: `'.$this->source['file'].'`.');
            return false;
        }

        # Check if destination folder exists
        if (!is_dir(dirname($destination_path))) {
            Log::war('Invalid directory provided: `' . dirname($destination_path) . '`.');
            return false;
        }

        # Check if file already exists
        if (file_exists($destination_path)) {
            Log::war("Destination file exists: `{$destination_path}`.");
            return false;
        }

        # Check if quality is set, and if it's not, set it to default value
        if (!isset($this->destination['quality'])) {
            $this->set_quality();
        }

        # Get source's width an height and type
        list($src_width, $src_height, $src_type) = getimagesize($this->source['file']);

        # Make new image resource
        switch ($src_type) {
            case 1 :
                $src_handle = imagecreatefromgif($this->source['file']);
                break;

            case 2 :
                $src_handle = imagecreatefromjpeg($this->source['file']);
                break;

            case 3 :
                $src_handle = imagecreatefrompng($this->source['file']);
                # Set alpha to true
                imagealphablending($src_handle, true);
                # Save it!
                // imagesavealpha($src_handle, true);
                break;

            default :
                Log::err("Invalid image file type: `{$src_type}`.");
                return false;
        }

        # Must be valid resource
        if (!is_resource($src_handle)) {
            Log::err("Failed to create a valid resource.");
            return false;
        }

        # Make some shortcuts
        $dest_height = isset($this->destination['height']) ? $this->destination['height'] : 0;
        $dest_width  = isset($this->destination['width'])  ? $this->destination['width']  : 0;

        # Calculate Missing Info (width and/or height)
        if ($dest_height == 0 && $dest_width == 0) {
            $dest_height  = $src_height;
            $dest_width   = $src_width;
        }
        elseif ($dest_height == 0) {
            $div_by = $src_width / $dest_width;
            if ($div_by < 1) {
                $dest_height = $src_height;
            }
            else {
                $new_height  = $src_height / $div_by;
                $dest_height = round($new_height);
            }
        }
        elseif ($this->destination['width'] == 0) {
            $div_by = $src_height / $dest_height;
            if ($div_by < 1) {
                $dest_width = $src_width;
            }
            else {
                $new_width  = $src_width / $div_by;
                $dest_width = round($new_width);
            }
        }

        # If New Height or Width is greater than source we'll leave it as it is
        if ($dest_height > $src_height) $dest_height = $src_height;
        if ($dest_width  > $src_width ) $dest_width  = $src_width;

        # Calclulations for resize
        if ($src_height < $src_width) { # Source has a horizontal Shape

            $ratio     = (double)($src_height / $dest_height);
            $cpy_width = round($dest_width * $ratio);

            if ($cpy_width > $src_width) {
                $ratio      = (double)($src_width / $dest_width);
                $cpy_width  = $src_width;
                $cpy_height = round($dest_height * $ratio);
                $x_offset   = 0;
                $y_offset   = round(($src_height - $cpy_height) / 2);
            }
            else {
                $cpy_height = $src_height;
                $x_offset   = round(($src_width - $cpy_width) / 2);
                $y_offset   = 0;
            }
        }
        else { # Source has a Vertical Shape
            $ratio      = (double)($src_width / $dest_width);
            $cpy_height = round($dest_height * $ratio);

            if ($cpy_height > $src_height) {
                $ratio      = (double)($src_height / $dest_height);
                $cpy_height = $src_height;
                $cpy_width  = round($dest_width * $ratio);
                $x_offset   = round(($src_width - $cpy_width) / 2);
                $y_offset   = 0;
            }
            else {
                $cpy_width = $src_width;
                $x_offset  = 0;
                $y_offset  = round(($src_height - $cpy_height) / 2);
            }
        }

        $dst_handle = imagecreatetruecolor($dest_width, $dest_height);
        imagealphablending($dst_handle, false);
        imagesavealpha($dst_handle, true);

        # bool imagecopyresampled ( resource dst_image, resource src_image, int dst_x, int dst_y, int src_x, int src_y, int dst_w, int dst_h, int src_w, int src_h )
        if (!imagecopyresampled($dst_handle, $src_handle, 0, 0, $x_offset, $y_offset, $dest_width, $dest_height, $cpy_width, $cpy_height)) {
            imagedestroy($src_handle);
            Log::err("Failed to resize image calling function:
                imagecopyresized(\$dst_handle, \$src_handle, 0, 0, {$x_offset}, {$y_offset}, {$dest_width}, {$dest_height}, {$cpy_width}, {$cpy_height})");
            return false;
        }

        # Destroy source handler
        imagedestroy($src_handle);

        # Sharpening
        if ($this->destination['sharpening']) {
            $dst_handle = $this->process_unsharp_mask($dst_handle);
        }

        # Watermark
        if ($this->destination['watermark']['text']) {
            $dst_handle = $this->process_watermark($dst_handle, $src_type);
        }

        switch ($src_type) {
            case 1 :
                $return = imagegif($dst_handle, $destination_path);
                break;

            case 2 :
                $return = imagejpeg($dst_handle, $destination_path, $this->destination['quality']);
                break;

            case 3 :
                $return = imagepng($dst_handle, $destination_path);
                break;

            default :
                Log::err('Inavlid image type.');
                $return = false;
        }

        # End
        imagedestroy($dst_handle);
        return $return;
    }

    /**
     * Add watermark to image
     * --
     * @param   resource $image
     * --
     * @return  resource
     */
    private function process_watermark($image)
    {
        # Determine image size and type
        $sizeX      = $this->destination['width'];
        $sizeX      = $this->destination['height'];

        // Translate color to decimal
        $color  = sscanf($this->destination['watermark']['color'], '#%2x%2x%2x');
        $color_r = $color[0];
        $color_g = $color[1];
        $color_b = $color[2];

        # Calculate TTF text size
        $font_size = $this->destination['watermark']['font_size'];
        $font_path = $this->destination['watermark']['font_path'];
        $angle     = $this->destination['watermark']['angle'];
        $text      = $this->destination['watermark']['text'];
        $ttfsize   = imagettfbbox($font_size, $angle, $font_path, $text);

        # Set Offset
        $offset_x = $this->destination['watermark']['offset_x'];
        $offset_y = $this->destination['watermark']['offset_y'];

        # Add custom insets
        $ttfx = $offset_x + max($ttfsize[0],$ttfsize[2],$ttfsize[4],$ttfsize[6]);
        $ttfy = $offset_y + max($ttfsize[1],$ttfsize[3],$ttfsize[5],$ttfsize[7]);

        # Shadow
        if ($this->destination['watermark']['shadow']) {

            # Get Shadow Color
            $shadow_color = $this->destination['watermark']['shadow'];

            # Translate color to decimal
            $scolor   = sscanf($shadow_color, '#%2x%2x%2x');
            $scolor_r = $scolor[0];
            $scolor_g = $scolor[1];
            $scolor_b = $scolor[2];

            $text_color = imagecolorallocate($image, $scolor_r, $scolor_g, $scolor_b);
            imagettftext($image, $font_size,
                $angle, // angle
                $sizeX - $ttfx - 2, // left inset
                $sizeY - $ttfy - 2, // top inset
                $text_color, $font_path, $text);
        }

        # Render text
        $text_color = imagecolorallocate($image, $color_r, $color_g, $color_b);
        imagettftext($image, $font_size,
            $angle, // angle
            $sizeX - $ttfx - 3, // left inset
            $sizeY - $ttfy - 3, // top inset
            $text_color, $font_path, $text);

        return $image;
    }

    /**
     * Unsharp Mask for PHP - version 2.1.1
     * Unsharp mask algorithm by Torstein Hønsi 2003-07. thoensi_at_netcom_dot_no.
     * --
     * @param   resource $image
     * @param   integer  $amount
     * @param   integer  $radius
     * @param   integer  $threshold
     * --
     * @return  resource
     */
    private function process_unsharp_mask(
        $image,
        $amount = 50,
        $radius = 0.5,
        $threshold = 3)
    {
        # $img is an image that is already created within php using
        # imgcreatetruecolor. No url! $img must be a truecolor image.

        # Attempt to calibrate the parameters to Photoshop:
        if ($amount > 500) {
            $amount = 500;
        }
        $amount = $amount * 0.016;

        if ($radius > 50) {
            $radius = 50;
        }
        $radius = $radius * 2;

        if ($threshold > 255) {
            $threshold = 255;
        }

        # Only integers make sense.
        $radius = abs(round($radius));
        if ($radius == 0) {
            return;
        }

        $w = imagesx($image);
        $h = imagesy($image);

        $img_canvas = imagecreatetruecolor($w, $h);
        $img_blur   = imagecreatetruecolor($w, $h);


        # Gaussian blur matrix:
        #
        #    1    2    1
        #    2    4    2
        #    1    2    1
        #
        if (function_exists('imageconvolution')) { # PHP >= 5.1
            $matrix = array(
                array( 1, 2, 1 ),
                array( 2, 4, 2 ),
                array( 1, 2, 1 )
            );
            imagecopy($img_blur, $image, 0, 0, 0, 0, $w, $h);
            imageconvolution($img_blur, $matrix, 16, 0);
        }
        else {
            # Move copies of the image around one pixel at the time and merge them with weight
            # according to the matrix. The same matrix is simply repeated for higher radii.
            for ($i = 0; $i < $radius; $i++) {
                imagecopy      ($img_blur, $image, 0, 0, 1, 0, $w - 1, $h); # left
                imagecopymerge ($img_blur, $image, 1, 0, 0, 0, $w, $h, 50); # right
                imagecopymerge ($img_blur, $image, 0, 0, 0, 0, $w, $h, 50); # center
                imagecopy      ($img_canvas, $img_blur, 0, 0, 0, 0, $w, $h);

                imagecopymerge ($img_blur, $img_canvas, 0, 0, 0, 1, $w, $h - 1, 33.33333 ); # up
                imagecopymerge ($img_blur, $img_canvas, 0, 1, 0, 0, $w, $h, 25);            # down
            }
        }


        if ($threshold > 0) {
            # Calculate the difference between the blurred pixels and the original
            # and set the pixels
            for ($x = 0; $x < $w-1; $x++) { # each row
                for ($y = 0; $y < $h; $y++) { # each pixel

                    $rgb_orig = ImageColorAt($image, $x, $y);
                    $r_orig   = (($rgb_orig >> 16) & 0xFF);
                    $g_orig   = (($rgb_orig >> 8) & 0xFF);
                    $b_orig   = ($rgb_orig & 0xFF);

                    $rgb_blur = ImageColorAt($img_blur, $x, $y);

                    $r_blur = (($rgb_blur >> 16) & 0xFF);
                    $g_blur = (($rgb_blur >> 8) & 0xFF);
                    $b_blur = ($rgb_blur & 0xFF);

                    # When the masked pixels differ less from the original
                    # than the threshold specifies, they are set to their original value.
                    $r_new = (abs($r_orig - $r_blur) >= $threshold)
                        ? max(0, min(255, ($amount * ($r_orig - $r_blur)) + $r_orig))
                        : $r_orig;
                    $g_new = (abs($g_orig - $g_blur) >= $threshold)
                        ? max(0, min(255, ($amount * ($g_orig - $g_blur)) + $g_orig))
                        : $g_orig;
                    $b_new = (abs($b_orig - $b_blur) >= $threshold)
                        ? max(0, min(255, ($amount * ($b_orig - $b_blur)) + $b_orig))
                        : $b_orig;

                    if (($r_orig != $r_new) || ($g_orig != $g_new) || ($b_orig != $b_new)) {
                        $pix_col = ImageColorAllocate($image, $r_new, $g_new, $b_new);
                        ImageSetPixel($image, $x, $y, $pix_col);
                    }
                }
            }
        }
        else {
            for ($x = 0; $x < $w; $x++) { # each row
                for ($y = 0; $y < $h; $y++) { # each pixel
                    $rgb_orig = ImageColorAt($image, $x, $y);
                    $r_orig = (($rgb_orig >> 16) & 0xFF);
                    $g_orig = (($rgb_orig >> 8) & 0xFF);
                    $b_orig = ($rgb_orig & 0xFF);

                    $rgb_blur = ImageColorAt($img_blur, $x, $y);

                    $r_blur = (($rgb_blur >> 16) & 0xFF);
                    $g_blur = (($rgb_blur >> 8) & 0xFF);
                    $b_blur = ($rgb_blur & 0xFF);

                    $r_new = ($amount * ($r_orig - $r_blur)) + $r_orig;
                        if($r_new>255){$r_new=255;}
                        elseif($r_new<0){$r_new=0;}
                    $g_new = ($amount * ($g_orig - $g_blur)) + $g_orig;
                        if($g_new>255){$g_new=255;}
                        elseif($g_new<0){$g_new=0;}
                    $b_new = ($amount * ($b_orig - $b_blur)) + $b_orig;
                        if($b_new>255){$b_new=255;}
                        elseif($b_new<0){$b_new=0;}
                    $rgb_new = ($r_new << 16) + ($g_new <<8) + $b_new;
                    ImageSetPixel($image, $x, $y, $rgb_new);
                }
            }
        }

        imagedestroy($img_canvas);
        imagedestroy($img_blur);
        return $image;
    }

}
