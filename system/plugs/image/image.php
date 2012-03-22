<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Immage Main Object
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2009, Avrelia.com
 * @license    http://avrelia.com/license
 * @link       http://avrelia.com
 * @since      Version 0.80
 * @since      sre dec 07 16:33:56 2011
 */

class cImage
{
	# Allowed image extensions
	private $Allow       = array('jpg','jpeg','gif','png');

	# Informations about source image
	private $Source      = array();

	# Informations about destination
	private $Destination = array();


	/**
	 * Create new image from filename
	 * ---
	 * @param $filename -- full absolute path to the image
	 * ---
	 * @return void
	 */
	public function __construct($filename)
	{
		Log::Add('INF', "Will load image `{$filename}`.", __LINE__, __FILE__);

		# Check if GD is enabled
		if (!extension_loaded('gd') || !function_exists('gd_info')) {
			Log::Add('ERR', "Can't get image object, the GD extension isn't loaded.", __LINE__, __FILE__);
			return false;
		}

		# File exists?
		if (!file_exists($filename)) {
			Log::Add('WAR', 'Source file doesn\'t exists: `'.$filename.'`.', __LINE__, __FILE__);
			return false;
		}

		# Get Image extension
		$imageExt = strtolower(FileSystem::Extension($filename));

		# Check if extension is valid
		if (!in_array($imageExt, $this->Allow)) {
			Log::Add('WAR', 'Invalid file type: `'.$imageExt.'`.', __LINE__, __FILE__);
			return false;
		}

		$this->Source = array(
			'file' => $filename,
			'ext'  => $imageExt,
		);
	}
	//-

	/**
	 * Will add watermark (set text to false, to turn off watermar)
	 * ---
	 * @param string $text
	 * @param array  $Params -- can contain: array(
	 *	color     => #ffffff // Text color  - must be full! hexcolor value
	 *	shadow    => #000000 // Text shadow - must be full! hexcolor value OR false
	 *  fontPath  => // full path to ttf font, or false for default font
	 *  fontSize  => // Integer, font site in px
	 *  angle     => // Integer, font angle
	 *  offsetX   => // Integer x offset
	 *  offsetY   => // Integer y offset
	 * )
	 * ---
	 * @return $this
	 */
	public function setWatermark($text, $Params=array())
	{
		if (!$text) {
			$this->Destination['Watermark'] = false;
		}

		# Set it up!
		$this->Destination['Watermark'] = array_merge(
			array(
				'text'     => $text,
				'color'    => '#ffffff',
				'shadow'   => '#000000',
				'fontPath' => ds(dirname(__FILE__) . '/fonts/ambitsek.ttf'),
				'fontSize' => 6,
				'angle'    => 0,
				'offsetX'  => 5,
				'offsetY'  => 2,
			),
			$Params
		);

		Log::Add('INF', "Watermark with following properties will be added to image: " . dumpVar($this->Destination['Watermark'], false, true), __LINE__, __FILE__);

		return $this;
	}
	//-

	/**
	 * Set sharpening to true or false
	 * ---
	 * @param bool $enabled -- set to false, to disable sharpening
	 * ---
	 * @return $this
	 */
	public function setSharpening($enabled=false)
	{
		$this->Destination['sharpening'] = $enabled;
		Log::Add('INF', "Sharpening will be: " . ($enabled ? 'enabled' : 'disabled'), __LINE__, __FILE__);

		return $this;
	}
	//-

	/**
	 * Set Destination Path
	 * ---
	 * @param string $path -- only directory, must be valid!
	 * ---
	 * @return $this
	 */
	public function setDestination($path)
	{
		$path = ds($path);

		if (!is_dir($path)) {
			Log::Add('WAR', "Destination is not valid directory: `{$path}`.", __LINE__, __FILE__);
		}
		else {
			$this->Destination['path'] = $path;
			Log::Add('INF', "Destination was set to: `{$path}`.", __LINE__, __FILE__);
		}

		return $this;
	}
	//-

	/**
	 * Will Set Image Quality
	 * ---
	 * @param int $quality
	 * ---
	 * @return $this
	 */
	public function setQuality($quality=75)
	{
		$quality = (int) $quality;

		if (!is_numeric($quality) || $quality < 10 || $quality > 100) {
			Log::Add('WAR', "Invalid quality value: `{$quality}`.", __LINE__, __FILE__);
		}
		else {
			$this->Destination['quality'] = $quality;
			Log::Add('INF', "Quality will be set to `{$quality}`.", __LINE__, __FILE__);
		}

		return $this;
	}
	//-

	/**
	 * Will set dimension for output image
	 * ---
	 * @param int $width  -- if set to false we'll calculate it dynamicly
	 * @param int $height -- if set to false we'll calculate it dynamicly
	 * ---
	 * @return $this
	 */
	public function setDimension($width, $height)
	{
		if ($width  === false) { $width  = 0; }
		if ($height === false) { $height = 0; }

		if (!is_numeric($width) || !is_numeric($height)) {
			Log::Add('WAR', "Both, width({$width}) and height({$height}) must be numeric.", __LINE__, __FILE__);
			# Set both to 0
			$width = $height = 0;
		}

		$this->Destination['width']  = $width;
		$this->Destination['height'] = $height;

		return $this;
	}
	//-

	/**
	 * Will Save Image (with all previous settings)
	 * Return saved image full path or false on error!
	 * ---
	 * @param string $fileName -- filename without path (if you have set destination before)
	 * ---
	 * @return string / false
	 */
	public function save($fileName)
	{
		if (!$fileName) {
			Log::Add('WAR', "Filename must be set, to save image.", __LINE__, __FILE__);
			return false;
		}

		$destination  = isset($this->Destination['path']) ? $this->Destination['path'] : '';
		$fullFilename = ds($destination . '/' . $fileName);

		return $this->process($fullFilename) ? $fullFilename : false;
	}
	//-


	/*  ****************************************************** *
	 *          Private Methods
	 *  **************************************  */

	/**
	 * Apply all setting on selected image.
	 * ---
	 * @param string $destinationPath
	 * ---
	 * @return boolean
	 */
	private function process($destinationPath)
	{
		# Check If File Exists
		if (!file_exists($this->Source['file']) || !is_file($this->Source['file'])) {
			Log::Add('ERR', 'Source image doesn\'t exists: `'.$this->Source['file'].'`.', __LINE__, __FILE__);
			return false;
		}

		# Check if destination folder exists
		if (!is_dir(dirname($destinationPath))) {
			Log::Add('WAR', 'Invalid directory provided: `' . dirname($fullFilename) . '`.', __LINE__, __FILE__);
			return false;
		}

		# Check if file already exists
		if (file_exists($destinationPath)) {
			Log::Add('WAR', "Destination file exists: `{$destinationPath}`.", __LINE__, __FILE__);
			return false;
		}

		# Check if quality is set, and if it's not, set it to default value
		if (!isset($this->Destination['quality'])) {
			$this->setQuality();
		}

		# Get source's width an height and type
		list($srcWidth, $srcHeight, $srcType) = getimagesize($this->Source['file']);

		# Make new image resource
		switch ($srcType) {
			case 1 :
				$srcHandle = imagecreatefromgif($this->Source['file']);
				break;

			case 2 :
				$srcHandle = imagecreatefromjpeg($this->Source['file']);
				break;

			case 3 :
				$srcHandle = imagecreatefrompng($this->Source['file']);
				# Set alpha to true
				imagealphablending($srcHandle, true);
				# Save it!
				// imagesavealpha($srcHandle, true);
				break;

			default :
				Log::Add('ERR', "Invalid image file type: `{$srcType}`.", __LINE__, __FILE__);
				return false;
		}

		# Must be valid resource
		if (!is_resource($srcHandle)) {
			Log::Add('ERR', "Failed to create a valid resource.", __LINE__, __FILE__);
			return false;
		}

		# Make some shortcuts
		$destHeight = isset($this->Destination['height']) ? $this->Destination['height'] : 0;
		$destWidth  = isset($this->Destination['width'])  ? $this->Destination['width']  : 0;

		# Calculate Missing Info (width and/or height)
		if ($destHeight == 0 && $destWidth == 0) {
			$destHeight  = $srcHeight;
			$destWidth   = $srcWidth;
		}
		elseif ($destHeight == 0) {
			$divBy = $srcWidth / $destWidth;
			if ($divBy < 1) {
				$destHeight = $srcHeight;
			}
			else {
				$newHeight  = $srcHeight / $divBy;
				$destHeight = round($newHeight);
			}
		}
		elseif ($this->Destination['width'] == 0) {
			$divBy = $srcHeight / $destHeight;
			if ($divBy < 1) {
				$destWidth = $srcWidth;
			}
			else {
				$newWidth  = $srcWidth / $divBy;
				$destWidth = round($newWidth);
			}
		}

		# If New Height or Width is greater than source we'll leave it as it is
		if ($destHeight > $srcHeight) $destHeight = $srcHeight;
		if ($destWidth  > $srcWidth ) $destWidth  = $srcWidth;

		# Calclulations for resize
		if ($srcHeight < $srcWidth) { # Source has a horizontal Shape

			$ratio    = (double)($srcHeight / $destHeight);
			$cpyWidth = round($destWidth * $ratio);

			if ($cpyWidth > $srcWidth) {
				$ratio     = (double)($srcWidth / $destWidth);
				$cpyWidth  = $srcWidth;
				$cpyHeight = round($destHeight * $ratio);
				$xOffset   = 0;
				$yOffset   = round(($srcHeight - $cpyHeight) / 2);
			}
			else {
				$cpyHeight = $srcHeight;
				$xOffset = round(($srcWidth - $cpyWidth) / 2);
				$yOffset = 0;
			}
		}
		else { # Source has a Vertical Shape
			$ratio     = (double)($srcWidth / $destWidth);
			$cpyHeight = round($destHeight * $ratio);

			if ($cpyHeight > $srcHeight) {
				$ratio     = (double)($srcHeight / $destHeight);
				$cpyHeight = $srcHeight;
				$cpyWidth  = round($destWidth * $ratio);
				$xOffset   = round(($srcWidth - $cpyWidth) / 2);
				$yOffset   = 0;
			}
			else {
				$cpyWidth = $srcWidth;
				$xOffset  = 0;
				$yOffset  = round(($srcHeight - $cpyHeight) / 2);
			}
		}

		$dstHandle = imagecreatetruecolor($destWidth, $destHeight);
		imagealphablending($dstHandle, false);
		imagesavealpha($dstHandle, true);

		# bool imagecopyresampled ( resource dst_image, resource src_image, int dst_x, int dst_y, int src_x, int src_y, int dst_w, int dst_h, int src_w, int src_h )
		if (!imagecopyresampled($dstHandle, $srcHandle, 0, 0, $xOffset, $yOffset, $destWidth, $destHeight, $cpyWidth, $cpyHeight)) {
			imagedestroy($srcHandle);
			Log::Add('ERR', "Failed to resize image calling function: imagecopyresized(\$dstHandle, \$srcHandle, 0, 0, {$xOffset}, {$yOffset}, {$destWidth}, {$destHeight}, {$cpyWidth}, {$cpyHeight})", __LINE__, __FILE__);
			return false;
		}

		# Destroy source handler
		imagedestroy($srcHandle);

		# Sharpening
		if ($this->Destination['sharpening']) {
			$dstHandle = $this->processUnsharpMask($dstHandle);
		}

		# Watermark
		if ($this->Destination['Watermark']['text']) {
			$dstHandle = $this->processWatermark($dstHandle, $srcType);
		}

		switch ($srcType) {
			case 1 :
				$return = imagegif($dstHandle, $destinationPath);
				break;

			case 2 :
				$return = imagejpeg($dstHandle, $destinationPath, $this->Destination['quality']);
				break;

			case 3 :
				$return = imagepng($dstHandle, $destinationPath);
				break;

			default :
				Log::Add('ERR', "Inavlid image type.", __LINE__, __FILE__);
				$return = false;
		}

		# End
		imagedestroy($dstHandle);
		return $return;
	}
	//-

	/**
	 * Add watermark to image
	 * ---
	 * @param resource $image
	 * @param integer  $type
	 * ---
	 * @return resource
	 */
	private function processWatermark($image, $imageType)
	{
		# Determine image size and type
		$sizeX     = $this->Destination['width'];
		$sizeX     = $this->Destination['height'];
		$imageType = $imageType;

		// Translate color to decimal
		$color  = sscanf($this->Destination['Watermark']['color'], '#%2x%2x%2x');
		$colorR = $color[0];
		$colorG = $color[1];
		$colorB = $color[2];

		# Calculate TTF text size
		$fontSize = $this->Destination['Watermark']['fontSize'];
		$fontPath = $this->Destination['Watermark']['fontPath'];
		$angle    = $this->Destination['Watermark']['angle'];
		$text     = $this->Destination['Watermark']['text'];
		$ttfsize  = imagettfbbox($fontSize, $angle, $fontPath, $text);

		# Set Offset
		$offsetX = $this->Destination['Watermark']['offsetX'];
		$offsetY = $this->Destination['Watermark']['offsetY'];

		# Add custom insets
		$ttfx = $offsetX + max($ttfsize[0],$ttfsize[2],$ttfsize[4],$ttfsize[6]);
		$ttfy = $offsetY + max($ttfsize[1],$ttfsize[3],$ttfsize[5],$ttfsize[7]);

		# Shadow
		if ($this->Destination['Watermark']['shadow']) {

			# Get Shadow Color
			$shadowColor = $this->Destination['Watermark']['shadow'];

			# Translate color to decimal
			$scolor   = sscanf($shadowColor, '#%2x%2x%2x');
			$scolorR = $scolor[0];
			$scolorG = $scolor[1];
			$scolorB = $scolor[2];

			$textColor = imagecolorallocate($image, $scolorR, $scolorG, $scolorB);
			imagettftext($image, $fontSize,
				$angle, // angle
				$sizeX - $ttfx - 2, // left inset
				$sizeY - $ttfy - 2, // top inset
				$textColor, $fontPath, $text);
		}

		# Render text
		$textColor = imagecolorallocate($image, $colorR, $colorG, $colorB);
		imagettftext($image, $fontSize,
			$angle, // angle
			$sizeX - $ttfx - 3, // left inset
			$sizeY - $ttfy - 3, // top inset
			$textColor, $fontPath, $text);

		return $image;
	}
	//-

	/**
	 * Unsharp Mask for PHP - version 2.1.1
	 * Unsharp mask algorithm by Torstein Hønsi 2003-07. thoensi_at_netcom_dot_no.
	 * ---
	 * @param resource $image
	 * @param integer  $amount
	 * @param integer  $radius
	 * @param integer  $threshold
	 * ---
	 * @return resource
	 */
	private function processUnsharpMask($image, $amount=50, $radius=0.5, $threshold=3)
	{
		# $img is an image that is already created within php using
		# imgcreatetruecolor. No url! $img must be a truecolor image.

		# Attempt to calibrate the parameters to Photoshop:
		if ($amount > 500)    $amount = 500;
		$amount = $amount * 0.016;

		if ($radius > 50)    $radius = 50;
		$radius = $radius * 2;

		if ($threshold > 255)    $threshold = 255;

		$radius = abs(round($radius));     # Only integers make sense.
		if ($radius == 0) {
			return;
		}

		$w = imagesx($image);
		$h = imagesy($image);

		$imgCanvas = imagecreatetruecolor($w, $h);
		$imgBlur   = imagecreatetruecolor($w, $h);


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
			imagecopy($imgBlur, $image, 0, 0, 0, 0, $w, $h);
			imageconvolution($imgBlur, $matrix, 16, 0);
		}
		else {
			# Move copies of the image around one pixel at the time and merge them with weight
			# according to the matrix. The same matrix is simply repeated for higher radii.
			for ($i = 0; $i < $radius; $i++) {
				imagecopy      ($imgBlur, $image, 0, 0, 1, 0, $w - 1, $h); # left
				imagecopymerge ($imgBlur, $image, 1, 0, 0, 0, $w, $h, 50); # right
				imagecopymerge ($imgBlur, $image, 0, 0, 0, 0, $w, $h, 50); # center
				imagecopy      ($imgCanvas, $imgBlur, 0, 0, 0, 0, $w, $h);

				imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 33.33333 ); # up
				imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 25);            # down
			}
		}


		if ($threshold > 0) {
			# Calculate the difference between the blurred pixels and the original
			# and set the pixels
			for ($x = 0; $x < $w-1; $x++) { # each row
				for ($y = 0; $y < $h; $y++) { # each pixel

					$rgbOrig = ImageColorAt($image, $x, $y);
					$rOrig   = (($rgbOrig >> 16) & 0xFF);
					$gOrig   = (($rgbOrig >> 8) & 0xFF);
					$bOrig   = ($rgbOrig & 0xFF);

					$rgbBlur = ImageColorAt($imgBlur, $x, $y);

					$rBlur = (($rgbBlur >> 16) & 0xFF);
					$gBlur = (($rgbBlur >> 8) & 0xFF);
					$bBlur = ($rgbBlur & 0xFF);

					# When the masked pixels differ less from the original
					# than the threshold specifies, they are set to their original value.
					$rNew = (abs($rOrig - $rBlur) >= $threshold)
						? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig))
						: $rOrig;
					$gNew = (abs($gOrig - $gBlur) >= $threshold)
						? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig))
						: $gOrig;
					$bNew = (abs($bOrig - $bBlur) >= $threshold)
						? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig))
						: $bOrig;

					if (($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew)) {
						$pixCol = ImageColorAllocate($image, $rNew, $gNew, $bNew);
						ImageSetPixel($image, $x, $y, $pixCol);
					}
				}
			}
		}
		else {
			for ($x = 0; $x < $w; $x++) { # each row
				for ($y = 0; $y < $h; $y++) { # each pixel
					$rgbOrig = ImageColorAt($image, $x, $y);
					$rOrig = (($rgbOrig >> 16) & 0xFF);
					$gOrig = (($rgbOrig >> 8) & 0xFF);
					$bOrig = ($rgbOrig & 0xFF);

					$rgbBlur = ImageColorAt($imgBlur, $x, $y);

					$rBlur = (($rgbBlur >> 16) & 0xFF);
					$gBlur = (($rgbBlur >> 8) & 0xFF);
					$bBlur = ($rgbBlur & 0xFF);

					$rNew = ($amount * ($rOrig - $rBlur)) + $rOrig;
						if($rNew>255){$rNew=255;}
						elseif($rNew<0){$rNew=0;}
					$gNew = ($amount * ($gOrig - $gBlur)) + $gOrig;
						if($gNew>255){$gNew=255;}
						elseif($gNew<0){$gNew=0;}
					$bNew = ($amount * ($bOrig - $bBlur)) + $bOrig;
						if($bNew>255){$bNew=255;}
						elseif($bNew<0){$bNew=0;}
					$rgbNew = ($rNew << 16) + ($gNew <<8) + $bNew;
					ImageSetPixel($image, $x, $y, $rgbNew);
				}
			}
		}

		imagedestroy($imgCanvas);
		imagedestroy($imgBlur);
		return $image;
	}
	//-
}
//--
