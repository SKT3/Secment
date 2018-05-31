<?php

//define('SWEBOO_IMAGICK', ((class_exists('IMagick') && class_exists('ImagickDraw') && class_exists('ImagickPixel')) ? true : false));
define('SWEBOO_IMAGICK', true);

class Images {
	/**
	 * @param string $source_image
	 * @param string $destination_image
	 * @param array $new_size
	 * @return boolean
	 * @desc Crop and resize an image to fit best
	*/
	static function crop_and_resize_to_fit($source_image, $destination_image, $new_size, $gravity) {
		return (SWEBOO_IMAGICK) ? ImagesImagick::crop_and_resize_to_fit($source_image, $destination_image, $new_size,$gravity) : ImagesGD::crop_and_resize_to_fit($source_image, $destination_image, $new_size,$gravity);
	}

	/**
	 * Make thumbnail of image preserving width or height
	 *
	 * @param string $source_image
	 * @param string $destination_image
	 * @param array $size
	 * @param int $mode
	 */
	static function thumbnail($source_image, $destination_image, $size) {
		return (SWEBOO_IMAGICK) ? ImagesImagick::thumbnail($source_image, $destination_image, $size) : ImagesGD::thumbnail($source_image, $destination_image, $size);
	}

	/**
	 * @param string $source_image
	 * @param string $destination_image
	 * @param int $width
	 * @param int $height
	 * @param string $gravity
	 * @return boolean
	 * @desc Crops an image
	*/
	static function crop($source_image, $destination_image, $width, $height, $gravity) {
		return (SWEBOO_IMAGICK) ? ImagesImagick::crop($source_image, $destination_image, $width, $height, $gravity) : ImagesGD::crop($source_image, $destination_image, $width, $height, $gravity);
	}

	/**
	 * Make thumbnail of image best fitting in it the given size
	 *
	 * @param string $source_image
	 * @param string $destination_image
	 * @param array $size
	 * @param string $background
	 */
	static function fit($source_image, $destination_image, $size, $background) {
		return (SWEBOO_IMAGICK) ? ImagesImagick::fit($source_image, $destination_image, $size, $background) : ImagesGD::fit($source_image, $destination_image, $size, $background);
	}

	/**
	 * Make thumbnail of image best fitting in it the given size
	 *
	 * @param string $source_image
	 * @param string $destination_image
	 * @param array $size
	 * @param string $background
	 */
	static function pad($source_image, $destination_image, $width, $height, $background = '#ffffff') {
	    return (SWEBOO_IMAGICK) ? ImagesImagick::pad($source_image, $destination_image, $width, $height, $background) : ImagesGD::pad($source_image, $destination_image, $width, $height, $background);
	}

	/**
	 * @param string $source_image
	 * @param string $destination_image
	 * @return boolean
	 * @desc grayscales an image
	*/
	static function grayscale($source_image, $destination_image) {
		return (SWEBOO_IMAGICK) ? ImagesImagick::grayscale($source_image, $destination_image) : ImagesGD::grayscale($source_image, $destination_image);
	}

	/**
	 * @param string $source_image
	 * @param string $destination_image
	 * @param string $watermark_image
	 * @param string $gravity
	 * @param int $offset
	 * @param int $pct
	 * @return boolean
	 * @desc Watermarks an image
	*/
	static function watermark($source_image, $destination_image, $watermark_image, $gravity, $offset, $opacity) {
		return (SWEBOO_IMAGICK) ? ImagesImagick::watermark($source_image, $destination_image, $watermark_image, $gravity, $offset, $opacity) : ImagesGD::watermark($source_image, $destination_image, $watermark_image, $gravity, $offset, $opacity);
	}

	/**
	 * @param string $source_image
	 * @param string $destination_image
	 * @return boolean
	 * @desc Creates a vertical mirror image
	*/
	static function flip($source_image, $destination_image) {
		return (SWEBOO_IMAGICK) ? ImagesImagick::flip($source_image, $destination_image) : ImagesGD::flip($source_image, $destination_image);
	}

	/**
	 * @param string $source_image
	 * @param string $destination_image
	 * @return boolean
	 * @desc Creates a horizontal mirror image
	*/
	static function flop($source_image, $destination_image) {
		return (SWEBOO_IMAGICK) ? ImagesImagick::flop($source_image, $destination_image) : ImagesGD::flop($source_image, $destination_image);
	}

	/**
	 * Adds wet floor reflection to the image
	 *
	 * @param string $source_image
	 * @param string $destination_image
	 * @param int $gradient_height
	 * @return boolean
	 */
	static function reflect($source_image, $destination_image, $gradient_height, $gradient_color = array()) {
		return (SWEBOO_IMAGICK) ? ImagesImagick::reflect($source_image, $destination_image, $gradient_height, $gradient_color) : ImagesGD::reflect($source_image, $destination_image, $gradient_height, $gradient_color);
	}

	/**
	 * Rotate image left or right by 90 degrees
	 *
	 * @param string $source_image
	 * @param string $destination_image
	 * @param boolean $left_or_right
	 */
	static function rotate($source_image, $destination_image, $left_or_right) {
		return (SWEBOO_IMAGICK) ? ImagesImagick::rotate($source_image, $destination_image, $left_or_right) : ImagesGD::rotate($source_image, $destination_image, $left_or_right);
	}

	static function blur($source_image, $destination_image) {

	}
}


interface iImages {
	/**
	 * @param string $source_image
	 * @param string $destination_image
	 * @param array $new_size
	 * @param string gravity
	 * @return boolean
	 * @desc Crop and resize an image to fit best
	*/
	static function crop_and_resize_to_fit($source_image, $destination_image, $new_size, $gravity);

	/**
	 * Make thumbnail of image preserving width or height
	 *
	 * @param string $source_image
	 * @param string $destination_image
	 * @param array $size
	 * @param int $mode
	 */
	static function thumbnail($source_image, $destination_image, $size);

	/**
	 * @param string $source_image
	 * @param string $destination_image
	 * @param int $width
	 * @param int $height
	 * @param string $gravity
	 * @return boolean
	 * @desc Crops an image
	*/
	static function crop($source_image, $destination_image, $width, $height, $gravity);

	/**
	 * Make thumbnail of image best fitting in it the given size
	 *
	 * @param string $source_image
	 * @param string $destination_image
	 * @param array $size
	 * @param string $background
	 */
	static function fit($source_image, $destination_image, $size, $background);

	/**
	 * Make thumbnail of image best fitting in it the given size
	 *
	 * @param string $source_image
	 * @param string $destination_image
	 * @param strin $width
	 * @param string $height
	 * @param string $background
	 */
	static function pad($source_image, $destination_image, $width, $height, $background = '#ffffff');

	/**
	 * @param string $source_image
	 * @param string $destination_image
	 * @return boolean
	 * @desc grayscales an image
	*/
	static function grayscale($source_image, $destination_image);

	/**
	 * @param string $source_image
	 * @param string $destination_image
	 * @param string $watermark_image
	 * @param string $gravity
	 * @param int $offset
	 * @param int $pct
	 * @return boolean
	 * @desc Watermarks an image
	*/
	static function watermark($source_image, $destination_image, $watermark_image, $gravity, $offset = 0, $pct = 0);

	/**
	 * @param string $source_image
	 * @param string $destination_image
	 * @return boolean
	 * @desc Creates a vertical mirror image
	*/
	static function flip($source_image, $destination_image);

	/**
	 * @param string $source_image
	 * @param string $destination_image
	 * @return boolean
	 * @desc Creates a horizontal mirror image
	*/
	static function flop($source_image, $destination_image);

	/**
	 * Adds wet floor reflection to the image
	 *
	 * @param string $source_image
	 * @param string $destination_image
	 * @param int $gradient_height
	 * @param int $gradient_color
	 * @return boolean
	 */
	static function reflect($source_image, $destination_image, $gradient_height, $gradient_color = array());

	/**
	 * Rotate image left or right by 90 degrees
	 *
	 * @param string $source_image
	 * @param string $destination_image
	 * @param boolean $left_or_right
	 */
	static function rotate($source_image, $destination_image, $left_or_right);

}


class ImagesImagick implements iImages {
	/**
	 * @param string $source_image
	 * @param string $destination_image
	 * @param array $new_size
	 * @param string $gravity
	 * @return boolean
	 * @desc Crop and resize an image to fit best
	*/
	static function crop_and_resize_to_fit($source_image, $destination_image, $new_size, $gravity) {
		$im = new Imagick($source_image);
		$im->cropThumbnailImage($new_size[0], $new_size[1]);
		$im->setImagePage($new_size[0], $new_size[1], 0, 0);
		$im->writeImage($destination_image);
	}

	/**
	 * Make thumbnail of image preserving width or height
	 *
	 * @param string $source_image
	 * @param string $destination_image
	 * @param array $size -- if either dim is set to null it tries to preserve the other
	 */
	static function thumbnail($source_image, $destination_image, $size) {
		$im = new Imagick($source_image);
		$im->thumbnailImage($size[0], $size[1]);
		$im->writeImage($destination_image);
	}

	/**
	 * @param string $source_image
	 * @param string $destination_image
	 * @param int $width
	 * @param int $height
	 * @param string $gravity
	 * @return boolean
	 * @desc Crops an image
	*/
	static function crop($source_image, $destination_image, $width, $height, $gravity) {
		$gravities = array("TL", "TC", "TR", "ML", "MC", "MR", "BL", "BC", "BR");
		$gravity = (in_array($gravity, $gravities)) ? $gravity : 'MC';
		$im = new Imagick($source_image);
		$sw = $im->getImageWidth();
		$sh = $im->getImageHeight();

		switch($gravity){
			case 'TL': $im->cropImage($width, $height, 0, 0);
				 	   break;
			case 'TC': $im->cropImage($width, $height, ($sw-$width)/2, 0);
				 	   break;
			case 'TR': $im->cropImage($width, $height, $sw-$width, 0);
				 	   break;
			case 'ML': $im->cropImage($width, $height, 0, ($sw-$width)/2);
				 	   break;
			case 'MC': $im->cropImage($width, $height, ($sw-$width)/2, ($sh-$height)/2);
				 	   break;
			case 'MR': $im->cropImage($width, $height, $sw-$width, ($sh-$height)/2);
				 	   break;
			case 'BL': $im->cropImage($width, $height, 0, ($sh-$height));
				 	   break;
			case 'BC': $im->cropImage($width, $height, ($sw-$width)/2, $sh-$height);
				 	   break;
			case 'BR': $im->cropImage($width, $height, $sw-$width, $sh-$height);
				 	   break;
		}

		$im->writeImage($destination_image);
	}

	/**
	 * Make thumbnail of image best fitting in it the given size
	 *
	 * @param string $source_image
	 * @param string $destination_image
	 * @param array $size
	 * @param string $background
	 */
	static function fit($source_image, $destination_image, $size, $background) {
		$im = new Imagick($source_image);
		$sw = $im->getImageWidth();
		$sh = $im->getImageHeight();

		if(($size[0]/$sw) < ($size[1]/$sh)){
			$im->thumbnailImage($size[0], 0, false);
		} else {
			$im->thumbnailImage(0, $size[1], false);
		}

		$canvas = new Imagick();
		$canvas->newImage($size[0], $size[1], new ImagickPixel($background));

		$geometry = $im->getImageGeometry();
		$offsetx = ($size[0] - $geometry['width']) / 2;
		$offsety = ($size[1] - $geometry['height']) / 2;

		$canvas->compositeImage($im, $im->getImageCompose(), $offsetx, $offsety);
		$canvas->writeImage($destination_image);
	}

    public static function pad($src, $dst, $width, $height, $background = '#ffffff') {
        if (!file_exists($src)) {
            return false;
        }

        if (!$dst) {
            $dst = $src;
        }

        $img = new Imagick($src);

        $src_width = $img->getImageWidth();
        $src_height = $img->getImageHeight();
        $width = (int)$width > 0 ? $width : $src_width;
        $height = (int)$height > 0 ? $height : $src_height;

        if (($ratio_x = $src_width / $width) > ($ratio_y = $src_height / $height)) {
            $dst_width = $width;
            $dst_height = 0;
            $x = 0;
            $y = ($height - $src_height / $ratio_x) / 2;
        } else {
            $dst_width = 0;
            $dst_height = $height;
            $x = ($width - $src_width / $ratio_y) / 2;
            $y = 0;
        }

        $img->thumbnailImage($dst_width, $dst_height);

        if ($background) {
            if (@getimagesize($background) === false) {
                $img->setImageBackgroundColor($background);
                $img->extentImage($width, $height, -$x, -$y);
                $img->writeImages($dst, true);
            } else {
                $canvas_bgr = new Imagick();
                $canvas_bgr->newImage($width, $height, 'none', 'png');
                $canvas_bgr = $canvas_bgr->textureImage(new Imagick($background));

                $canvas = new Imagick();
                $canvas->readImageBlob($canvas_bgr->getImagesBlob());
                unset($canvas_bgr);

                $canvas->compositeImage($img, Imagick::COMPOSITE_OVER, $x, $y);
                $canvas->writeImages($dst, true);
                unset($canvas);
            }
        } else {
            $img->setImagePage($width, $height, 0, 0);
            $img->writeImages($dst, true);
        }

        unset($img);
        return true;
    }

	/**
	 * @param string $source_image
	 * @param string $destination_image
	 * @return boolean
	 * @desc grayscales an image
	*/
	static function grayscale($source_image, $destination_image) {
		$im = new Imagick($source_image);
		$im->setImageColorSpace(Imagick::COLORSPACE_GRAY);
		$im->writeImage($destination_image);
	}

	/**
	 * @param string $source_image
	 * @param string $destination_image
	 * @param string $watermark_image
	 * @param string $gravity
	 * @param int $offset
	 * @param int $pct
	 * @return boolean
	 * @desc Watermarks an image
	*/
	static function watermark($source_image, $destination_image, $watermark_image, $gravity, $offset = 0, $pct = 0) {
		$gravities = array("TL", "TC", "TR", "ML", "MC", "MR", "BL", "BC", "BR");
		$gravity = (in_array($gravity, $gravities)) ? $gravity : 'BR';
		$im = new Imagick($source_image);
		$sw = $im->getImageWidth();
		$sh = $im->getImageHeight();
		$watermark = new Imagick($watermark_image);
		$wmw = $watermark->getImageWidth();
		$wmh = $watermark->getImageHeight();
		$watermark->setImageOpacity($pct/100);

		switch ($gravity) {
			case "TL": // Top Left
				$offsetx = $offset;
				$offsety = $offset;
				break;
			case "TC": // Top middle
				$offsetx = intval(($sw - $wmw) / 2);
				$offsety = $offset;
				break;
			case "TR": // Top right
				$offsetx = $sw - $wmw - $offset;
				$offsety = $offset;
				break;
			case "ML": // Center left
				$offsetx = $offset;
				$offsety = intval(($sh - $wmh) / 2);
				break;
			case "MC": // Center
				$offsetx = intval(($sw - $wmw) / 2);
				$offsety = intval(($sh - $wmh) / 2);
				break;
			case "MR": // Center right
				$offsetx = $sw - $wmw - $offset;
				$offsety = intval(($sh - $wmh) / 2);
				break;
			default:
			case "BL": // Bottom left (the default)
				$offsetx = $offset;
				$offsety = $sh - $wmh - $offset;
				break;
			case "BC": // Bottom middle
				$offsetx = intval(($sw - $wmw) / 2);
				$offsety = $sh - $wmh - $offset;
				break;
			case "BR": // Bottom right
				$offsetx = $sw - $wmw - $offset;
				$offsety = $sh - $wmh - $offset;
				break;
		}
		$im->compositeImage($watermark, $watermark->getImageCompose(), $offsetx, $offsety);
		$im->writeImage($destination_image);
	}

	/**
	 * @param string $source_image
	 * @param string $destination_image
	 * @return boolean
	 * @desc Creates a vertical mirror image
	*/
	static function flip($source_image, $destination_image) {
		$im = new Imagick($source_image);
		$im->flipImage();
		$im->writeImage($destination_image);
	}

	/**
	 * @param string $source_image
	 * @param string $destination_image
	 * @return boolean
	 * @desc Creates a horizontal mirror image
	*/
	static function flop($source_image, $destination_image) {
		$im = new Imagick($source_image);
		$im->flopImage();
		$im->writeImage($destination_image);
	}

	/**
	 * Adds wet floor reflection to the image
	 *
	 * @param string $source_image
	 * @param string $destination_image
	 * @param int $gradient_height
	 * @param int $gradient_color
	 * @return boolean
	 */
	static function reflect($source_image, $destination_image, $gradient_height, $gradient_color = array()) {
		$gradparts = (empty($gradient_color)) ? array(255, 255, 255) : $gradient_color;  // get the parts of the colour (RRR,GGG,BBB)

		$im = new Imagick($source_image);
		$reflection = $im->clone();
		$reflection->flipImage();
		$reflection->cropImage($im->getImageWidth(), $gradient_height, 0, 0);

		$canvas = new Imagick();
		$canvas->newImage($im->getImageWidth(), $im->getImageHeight() + $gradient_height, new ImagickPixel("rgb(".implode(',', $gradparts).")"));
		$canvas->compositeImage($im, imagick::COMPOSITE_OVER, 0, 0);

		$transparency = 85; //from 85 to 0
		$transparency_ratio = (85 / $gradient_height);

		for ($i = 0; $i <= $gradient_height; $i++) {
			$gradient = $reflection->clone();
			$gradient->cropImage($im->getImageWidth(), 1, 0, $i);
			$gradient->setImageOpacity($transparency / 100);
			$transparency = ($transparency <= 0) ? 0 : $transparency - $transparency_ratio;
			$canvas->compositeImage($gradient, imagick::COMPOSITE_OVER, 0, $im->getImageHeight() + $i);
		}
		$canvas->writeImage($destination_image);
	}

	/**
	 * Rotate image left or right by 90 degrees
	 *
	 * @param string $source_image
	 * @param string $destination_image
	 * @param boolean $left_or_right
	 */
	static function rotate($source_image, $destination_image, $left_or_right) {
		$degree = (boolean)($left_or_right) ? 270 : 90;
		$im = new Imagick($source_image);
		$im->rotateImage(new ImagickPixel('black'), $degree);
		$im->writeImage($destination_image);
	}


}


class ImagesGD implements iImages {
	static protected $gd = null;

	/**
	 * @param void
	 * @return boolean
	 * @desc Check if we have GD installed
	*/
	static function check_gd(){
		if (!is_null(self::$gd)) return self::$gd;
		if (extension_loaded('gd') && function_exists('gd_info')) {
			$gdinfo = gd_info();
			self::$gd = (strpos($gdinfo['GD Version'], '2.0') !== false) ? true : false;
		} else {
			self::$gd = false;
		}
		return self::$gd;
	}

	/**
	 * @param string $source_image
	 * @param string $destination_image
	 * @param array $new_size
	 * @param string $gravity
	 * @return boolean
	 * @desc Crop and resize an image to fit best
	*/
	static function crop_and_resize_to_fit($source_image, $destination_image, $new_size, $gravity) {
		$info = getimagesize($source_image) ; // see EXIF for faster way
		
		switch ($info['mime']){
			case 'image/gif':
				if (imagetypes() & IMG_GIF){ // not the same as IMAGETYPE
					$source_image = imagecreatefromgif($source_image);
				} else {
					throw new Exception('Error! GD: GIF images are not supported');
				}
				break;
			case 'image/jpeg':
				if (imagetypes() & IMG_JPG){ // not the same as IMAGETYPE
					//ini_set("memory_limit", "300M");

					try {
						$source_image = imagecreatefromjpeg($source_image);
					} catch (Exception $e) {
						$source_image = null;
					}
					if(!$source_image) {
						return;
					}

				} else {
					throw new Exception('Error! GD: JPG images are not supported');
				}
				break;
			case 'image/png':
				if (imagetypes() & IMG_PNG){ // not the same as IMAGETYPE
					$source_image = imagecreatefrompng($source_image);
				} else {
					throw new Exception('Error! GD: PNG images are not supported');
				}
				break;
			case 'image/wbmp':
				if (imagetypes() & IMG_WBMP){ // not the same as IMAGETYPE
					$source_image = imagecreatefromwbmp($source_image);
				} else {
					throw new Exception('Error! GD: BMP images are not supported');
				}
				break;
		}

		if(!$source_image) {
			return;
		}



		$gravities = array("TL", "TC", "TR", "ML", "MC", "MR", "BL", "BC", "BR");
		$gravity = (in_array($gravity, $gravities)) ? $gravity : 'TC';

		$w = $new_size[0];
		$h = $new_size[1];

		$sw = imagesx($source_image);
		$sh = imagesy($source_image);

		if($h==0) {$h = $sh/( $sw / $w);}
		if($w==0) {$w = $sw/( $sh / $h);}

		$width_ratio = $sw / $w;
		$height_ratio = $sh / $h;

		if ($height_ratio < $width_ratio) {
			$crop_width = @round($height_ratio * $w);
			$crop_height = @round($height_ratio * $h);
		} else {
			$crop_width = @round($width_ratio * $w);
			$crop_height = @round($width_ratio * $h);
		}


		$dest_image = imagecreatetruecolor($w, $h);
		switch ($info['mime']){
			case 'image/gif': 	imagecolortransparent($dest_image, imagecolorallocate($dest_image, 255, 255, 255));
								break;
			case 'image/png':	imagealphablending($dest_image, false);
								imagesavealpha($dest_image, true);
								break;

		}


		switch($gravity){
			case 'TL':
						imagecopyresampled($dest_image, $source_image, 0, 0, 0, 0, $w, $h, $crop_width, $crop_height);
				 	   	break;
			case 'TC':
						imagecopyresampled($dest_image, $source_image, 0, 0, ($sw - $crop_width)/2, 0, $w, $h, $crop_width, $crop_height);
						break;
			case 'TR':
						imagecopyresampled($dest_image, $source_image, 0, 0, ($sw - $crop_width), 0, $w, $h, $crop_width, $crop_height);
						break;
			case 'ML':
						imagecopyresampled($dest_image, $source_image, 0, 0, 0, ($sh/2 - $crop_height/2), $w, $h, $crop_width, $crop_height);
				 	   	break;
			case 'MC':
						imagecopyresampled($dest_image, $source_image, 0, 0, ($sw - $crop_width)/2, ($sh/2 - $crop_height/2), $w, $h, $crop_width, $crop_height);
				 	   	break;
			case 'MR':
						imagecopyresampled($dest_image, $source_image, 0, 0, ($sw - $crop_width), ($sh/2 - $crop_height/2), $w, $h, $crop_width, $crop_height);
				 	   	break;
			case 'BL':
						imagecopyresampled($dest_image, $source_image, 0, 0, 0, $sh-$crop_height, $w, $h, $crop_width, $crop_height);
						break;
			case 'BC':
						imagecopyresampled($dest_image, $source_image, 0, 0, ($sw - $crop_width)/2, $sh-$crop_height, $w, $h, $crop_width, $crop_height);
						break;
			case 'BR':
						imagecopyresampled($dest_image, $source_image, 0, 0, ($sw - $crop_width), $sh-$crop_height, $w, $h, $crop_width, $crop_height);
						break;
		}

		switch ($info['mime']){
			case 'image/gif':
				imagegif($dest_image, $destination_image);
				break;
			case 'image/jpeg':
				imageJPEG($dest_image, $destination_image, 95);
				break;
			case 'image/png':
				imagepng($dest_image, $destination_image, 9);
				break;
			case 'image/wbmp':
				image2wbmp($dest_image, $destination_image);
				break;
		}
		@imagedestroy($source_image);
		@imagedestroy($dest_image);

	}

	/**
	 * Make thumbnail of image preserving width or height
	 *
	 * @param string $source_image
	 * @param string $destination_image
	 * @param array $size -- if either dim is set to null it tries to preserve the other
	 */
	static function thumbnail($source_image, $destination_image, $size) {
		$info = getimagesize($source_image) ; // see EXIF for faster way
		switch ($info['mime']){
			case 'image/gif':
				if (imagetypes() & IMG_GIF){ // not the same as IMAGETYPE
					$source_image = imagecreatefromgif($source_image);
				}else{
					throw new Exception('Error! GD: GIF images are not supported');
				}
				break;
			case 'image/jpeg':
				if (imagetypes() & IMG_JPG){ // not the same as IMAGETYPE
					$source_image = imagecreatefromjpeg($source_image);
				}else{
					throw new Exception('Error! GD: JPG images are not supported');
				}
				break;
			case 'image/png':
				if (imagetypes() & IMG_PNG){ // not the same as IMAGETYPE
					$source_image = imagecreatefrompng($source_image);
				}else{
					throw new Exception('Error! GD: PNG images are not supported');
				}
				break;
			case 'image/wbmp':
				if (imagetypes() & IMG_WBMP){ // not the same as IMAGETYPE
					$source_image = imagecreatefromwbmp($source_image);
				}else{
					throw new Exception('Error! GD: BMP images are not supported');
				}
				break;
		}
		$w = $size[0];
		$h = $size[1];

		$sw = imagesx($source_image);
		$sh = imagesy($source_image);

		// we want to preserve width
		if (is_null($h)) {
			$new_height = $sh * ($w / $sw);
			$new_width = $w;
		}
		// we want to preserve height
		else {
			$new_width = $sw * ($h / $sh);
			$new_height = $h;
		}

		if (!self::check_gd()){
			$dest_image = imagecreate($new_width, $new_height);
			imagecopyresized($dest_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $sw, $sh);
		}else{
			$dest_image = imagecreatetruecolor($new_width, $new_height);
			switch ($info['mime']){
				case 'image/gif': 	imagecolortransparent($dest_image, imagecolorallocate($dest_image, 0, 0, 0));
									break;
				case 'image/png':	imagealphablending($dest_image, false);
									imagesavealpha($dest_image, true);
									break;

			}
			imagecopyresampled($dest_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $sw, $sh);
		}

		switch ($info['mime']){
			case 'image/gif':
				imagegif($dest_image, $destination_image);
				break;
			case 'image/jpeg':
				imageJPEG($dest_image, $destination_image, 95);
				break;
			case 'image/png':
				imagepng($dest_image, $destination_image, 9);
				break;
			case 'image/wbmp':
				image2wbmp($dest_image, $destination_image);
				break;
		}
		@imagedestroy($source_image);
		@imagedestroy($dest_image);
	}

	/**
	 * @param string $source_image
	 * @param string $destination_image
	 * @param int $width
	 * @param int $height
	 * @param string $gravity
	 * @return boolean
	 * @desc Crops an image
	*/
	static function crop($source_image, $destination_image, $width, $height, $gravity) {
		$info = getimagesize($source_image) ; // see EXIF for faster way
		switch ($info['mime']){
			case 'image/gif':
				if (imagetypes() & IMG_GIF){ // not the same as IMAGETYPE
					$source_image = imagecreatefromgif($source_image);
				}else{
					throw new Exception('Error! GD: GIF images are not supported');
				}
				break;
			case 'image/jpeg':
				if (imagetypes() & IMG_JPG){ // not the same as IMAGETYPE
					$source_image = imagecreatefromjpeg($source_image);
				}else{
					throw new Exception('Error! GD: JPG images are not supported');
				}
				break;
			case 'image/png':
				if (imagetypes() & IMG_PNG){ // not the same as IMAGETYPE
					$source_image = imagecreatefrompng($source_image);
				}else{
					throw new Exception('Error! GD: PNG images are not supported');
				}
				break;
			case 'image/wbmp':
				if (imagetypes() & IMG_WBMP){ // not the same as IMAGETYPE
					$source_image = imagecreatefromwbmp($source_image);
				}else{
					throw new Exception('Error! GD: BMP images are not supported');
				}
				break;
		}

		$sw = imagesx($source_image);
		$sh = imagesy($source_image);

		if (!self::check_gd()){
			$dest_image = imagecreate($width, $height);
		}else{
			$dest_image = imagecreatetruecolor($width, $height);
			switch ($info['mime']){
				case 'image/gif': 	imagecolortransparent($dest_image, imagecolorallocate($dest_image, 0, 0, 0));
									break;
				case 'image/png':	imagealphablending($dest_image, false);
									imagesavealpha($dest_image, true);
									break;
			}
		}

		switch($gravity){
			case 'TL': imagecopy($dest_image, $source_image, 0, 0, 0, 0 , $width, $height);
				 	   break;
			case 'TC': imagecopy($dest_image, $source_image, 0, 0, ($sw-$width)/2, 0, $width, $height);
				 	   break;
			case 'TR': imagecopy($dest_image, $source_image, 0, 0, $sw-$width, 0, $width, $height);
				 	   break;
			case 'ML': imagecopy($dest_image, $source_image, 0, 0, 0, ($sw-$width)/2, $width, $height);
				 	   break;
			case 'MC': imagecopy($dest_image, $source_image, 0, 0, ($sw-$width)/2, ($sh-$height)/2, $width, $height);
				 	   break;
			case 'MR': imagecopy($dest_image, $source_image, 0, 0, $sw-$width, ($sh-$height)/2, $width, $height);
				 	   break;
			case 'BL': imagecopy($dest_image, $source_image, 0, 0, 0, $sh-$height, $width, $height);
				 	   break;
			case 'BC': imagecopy($dest_image, $source_image, 0, 0, ($sw-$width)/2, $sh-$height, $width, $height);
				 	   break;
			case 'BR': imagecopy($dest_image, $source_image, 0, 0, $sw-$width, $sh-$height, $width, $height);
				 	   break;
		}

		switch ($info['mime']){
			case 'image/gif':
				imagegif($dest_image, $destination_image);
				break;
			case 'image/jpeg':
				imageJPEG($dest_image, $destination_image, 95);
				break;
			case 'image/png':
				imagepng($dest_image, $destination_image, 9);
				break;
			case 'image/wbmp':
				image2wbmp($dest_image, $destination_image);
				break;
		}

		@imagedestroy($source_image);
		@imagedestroy($dest_image);
	}

	/**
	 * Make thumbnail of image best fitting in it the given size
	 *
	 * @param string $source_image
	 * @param string $destination_image
	 * @param array $size
	 * @param string $background
	 */
	static function fit($source_image, $destination_image, $size, $background) {
		$info = getimagesize($source_image) ; // see EXIF for faster way
		switch ($info['mime']){
			case 'image/gif':
				if (imagetypes() & IMG_GIF){ // not the same as IMAGETYPE
					$source_image = imagecreatefromgif($source_image);
				}else{
					throw new Exception('Error! GD: GIF images are not supported');
				}
				break;
			case 'image/jpeg':
				if (imagetypes() & IMG_JPG){ // not the same as IMAGETYPE
					$source_image = imagecreatefromjpeg($source_image);
				}else{
					throw new Exception('Error! GD: JPG images are not supported');
				}
				break;
			case 'image/png':
				if (imagetypes() & IMG_PNG){ // not the same as IMAGETYPE
					$source_image = imagecreatefrompng($source_image);
				}else{
					throw new Exception('Error! GD: PNG images are not supported');
				}
				break;
			case 'image/wbmp':
				if (imagetypes() & IMG_WBMP){ // not the same as IMAGETYPE
					$source_image = imagecreatefromwbmp($source_image);
				}else{
					throw new Exception('Error! GD: BMP images are not supported');
				}
				break;
		}
		$w = $size[0];
		$h = $size[1];

		$sw = imagesx($source_image);
		$sh = imagesy($source_image);

		$width_ratio = $sw / $w;
		$height_ratio = $sh / $h;

		// if we have to fit by width
		if ($sw >= $height_ratio * $w) {
			$new_height = $sh * ($w / $sw);
			$new_width = $w;
			$preserve = 'width';
		}

		// if we have to fit by height
		if ($sh > $width_ratio * $h) {
			$new_width = $sw * ($h / $sh);
			$new_height = $h;
			$preserve = 'height';
		}

		$background = sscanf($background, '#%2x%2x%2x');

		if (!self::check_gd()){
			$dest_image = imagecreate($w, $h);
			$background_color = imagecolorallocate($dest_image, $background[0], $background[1], $background[2]);
			imagefilledrectangle($dest_image, 0, 0, $w, $h, $background_color);
			if ($preserve == 'height') {
				imagecopyresized($dest_image, $source_image, ($w - $new_width) / 2, 0, 0, 0, $new_width, $new_height, $sw, $sh);
			}
			else {
				imagecopyresized($dest_image, $source_image, 0, ($h - $new_height) / 2, 0, 0, $new_width, $new_height, $sw, $sh);
			}
		}else{
			$dest_image = imagecreatetruecolor($w, $h);
			$background_color = imagecolorallocate($dest_image, $background[0], $background[1], $background[2]);
			imagefilledrectangle($dest_image, 0, 0, $w, $h, $background_color);
			switch ($info['mime']){
				case 'image/gif': 	imagecolortransparent($dest_image, imagecolorallocate($dest_image, 0, 0, 0));
									break;
				case 'image/png':	imagealphablending($dest_image, false);
									imagesavealpha($dest_image, true);
									break;

			}
			if ($preserve == 'height') {
				imagecopyresampled($dest_image, $source_image, ($w - $new_width) / 2, 0, 0, 0, $new_width, $new_height, $sw, $sh);
			} else {
				imagecopyresampled($dest_image, $source_image, 0, ($h - $new_height) / 2, 0, 0, $new_width, $new_height, $sw, $sh);
			}
		}

		switch ($info['mime']){
			case 'image/gif':
				imagegif($dest_image, $destination_image);
				break;
			case 'image/jpeg':
				imageJPEG($dest_image, $destination_image, 95);
				break;
			case 'image/png':
				imagepng($dest_image, $destination_image, 9);
				break;
			case 'image/wbmp':
				image2wbmp($dest_image, $destination_image);
				break;
		}
		@imagedestroy($source_image);
		@imagedestroy($dest_image);
	}

	static function pad($src, $dst, $width, $height, $background = '#ffffff') {
		if (!file_exists ( $src )) {
			return false;
		}

		if (!$background) {
			return self::fit ( $src, $dst, $width, $height );
		}

		if (!$dst) {
			$dst = $src;
		}

		list ($src_width, $src_height) = getimagesize ( $src );
		$img = self::imagecreatefromfile ( $src );
		$out = imagecreatetruecolor ( $width, $height );

		if (($ratio_x = ($src_width / $width)) > ($ratio_y = ($src_height / $height))) {
			$dst_width = $width;
			$dst_height = $src_height / $ratio_x;
			$x = 0;
			$y = ($height - $src_height / $ratio_x) / 2;
		}
		else {
			$dst_width = $src_width / $ratio_y;
			$dst_height = $height;
			$x = ($width - $src_width / $ratio_y) / 2;
			$y = 0;
		}

		if ($background {0} == '#' || (!file_exists ( $background ) && ($background = '#ffffff'))) {
			imagefilledrectangle ( $out, 0, 0, $width - 1, $height - 1, hexdec ( substr ( $background, 1 ) ) );
		}
		else if (file_exists ( $background )) {
			list ($bg_width, $bg_height) = getimagesize ( $background );
			$bg_x = abs ( $width - $bg_width ) / 2;
			$bg_y = abs ( $height - $bg_height ) / 2;
			$background = self::imagecreatefromfile ( $background );
			imagecopy ( $out, $background, 0, 0, $bg_x, $bg_y, $bg_width, $bg_height );
			imagedestroy ( $background );
		}

		imagecopyresampled ( $out, $img, $x, $y, 0, 0, $dst_width, $dst_height, $src_width, $src_height );
		imagedestroy ( $img );

		return self::imagesavetofile ( $out, $dst );
	}

	/**
	 * @param string $source_image
	 * @param string $destination_image
	 * @return boolean
	 * @desc grayscales an image
	*/
	static function grayscale($source_image, $destination_image) {
		$info = getimagesize($source_image) ; // see EXIF for faster way
		switch ($info['mime']){
			case 'image/gif':
				if (imagetypes() & IMG_GIF){ // not the same as IMAGETYPE
					$source_image = imagecreatefromgif($source_image);
				}else{
					throw new Exception('Error! GD: GIF images are not supported');
				}
				break;
			case 'image/jpeg':
				if (imagetypes() & IMG_JPG){ // not the same as IMAGETYPE
					$source_image = imagecreatefromjpeg($source_image);
				}else{
					throw new Exception('Error! GD: JPG images are not supported');
				}
				break;
			case 'image/png':
				if (imagetypes() & IMG_PNG){ // not the same as IMAGETYPE
					$source_image = imagecreatefrompng($source_image);
				}else{
					throw new Exception('Error! GD: PNG images are not supported');
				}
				break;
			case 'image/wbmp':
				if (imagetypes() & IMG_WBMP){ // not the same as IMAGETYPE
					$source_image = imagecreatefromwbmp($source_image);
				}else{
					throw new Exception('Error! GD: BMP images are not supported');
				}
				break;
		}

		$sw = imagesx($source_image);
		$sh = imagesy($source_image);

		if (!self::check_gd()){
			$new_image = imagecreate($sw, $sh);
		}else{
			$new_image = imagecreatetruecolor($sw, $sh);
			switch ($info['mime']){
				case 'image/gif': 	imagecolortransparent($new_image, imagecolorallocate($new_image, 0, 0, 0));
									break;
				case 'image/png':	imagealphablending($new_image, false);
									imagesavealpha($new_image, true);
									break;

			}
		}

		//Creates the 256 color palette
		for ($color = 0; $color < 256; $color++) {
			$pallete[$color] = imagecolorallocate($new_image, $color, $color, $color);
		}
		for ($posy = 0; $posy < $sh; $posy++) {
			for ($posx = 0; $posx < $sw; $posx++) {
				$rgb = imagecolorat($source_image, $posx, $posy);
				$red = ($rgb >> 16) & 0xFF;
				$green = ($rgb >> 8) & 0xFF;
				$blue = $rgb & 0xFF;

				//This is where we actually use yiq to modify our rbg values, and then convert them to our grayscale palette
				$grayscale = ($red * 0.299) + ($green * 0.587) + ($blue * 0.114);
				imagesetpixel($new_image, $posx, $posy, $pallete[$grayscale]);
			}
		}

		switch ($info['mime']){
			case 'image/gif':
				imagegif($new_image, $destination_image);
				break;
			case 'image/jpeg':
				imageJPEG($new_image, $destination_image, 95);
				break;
			case 'image/png':
				imagepng($new_image, $destination_image, 9);
				break;
			case 'image/wbmp':
				image2wbmp($new_image, $destination_image);
				break;
		}

		@imagedestroy($source_image);
		@imagedestroy($new_image);
	}

	/**
	 * @param string $source_image
	 * @param string $destination_image
	 * @param string $watermark_image
	 * @param string $gravity
	 * @param int $offset
	 * @param int $pct
	 * @return boolean
	 * @desc Watermarks an image
	*/
	static function watermark($source_image, $destination_image, $watermark_image, $gravity, $offset = 0, $opacity = 100) {

		$gravities = array("TL", "TC", "TR", "ML", "MC", "MR", "BL", "BC", "BR");
		$watermark_info = getimagesize($watermark_image) ; // see EXIF for faster way
		switch ($watermark_info['mime']){
			case 'image/gif':
				if (imagetypes() & IMG_GIF){ // not the same as IMAGETYPE
					$watermark_image = imagecreatefromgif($watermark_image);
				}else{
					throw new Exception('Error! GD: GIF images are not supported');
				}
				break;
			case 'image/jpeg':
				if (imagetypes() & IMG_JPG){ // not the same as IMAGETYPE
					$watermark_image = imagecreatefromjpeg($watermark_image);
				}else{
					throw new Exception('Error! GD: JPG images are not supported');
				}
				break;
			case 'image/png':
				if (imagetypes() & IMG_PNG){ // not the same as IMAGETYPE
					$watermark_image = imagecreatefrompng($watermark_image);
					imagealphablending($watermark_image, false);
					imagesavealpha($watermark_image, true);
				}else{
					throw new Exception('Error! GD: PNG images are not supported');
				}
				break;
			case 'image/wbmp':
				if (imagetypes() & IMG_WBMP){ // not the same as IMAGETYPE
					$watermark_image = imagecreatefromwbmp($watermark_image);
				}else{
					throw new Exception('Error! GD: BMP images are not supported');
				}
				break;
		}

		$wmw = imagesx($watermark_image) ;
		$wmh = imagesy($watermark_image) ;

		$gravity = strtoupper($gravity);
		if (in_array($gravity, $gravities)) {
			$watermark_gravity = $gravity;
		}else {
			$watermark_gravity = 'BR';
		}

		$info = getimagesize($source_image) ; // see EXIF for faster way
		switch ($info['mime']){
			case 'image/gif':
				if (imagetypes() & IMG_GIF){ // not the same as IMAGETYPE
					$source_image = imagecreatefromgif($source_image);
				}else{
					throw new Exception('Error! GD: GIF images are not supported');
				}
				break;
			case 'image/jpeg':
				if (imagetypes() & IMG_JPG){ // not the same as IMAGETYPE
					$source_image = imagecreatefromjpeg($source_image);
				}else{
					throw new Exception('Error! GD: JPG images are not supported');
				}
				break;
			case 'image/png':
				if (imagetypes() & IMG_PNG){ // not the same as IMAGETYPE
					$source_image = imagecreatefrompng($source_image);
					imagealphablending($source_image, false);
					imagesavealpha($source_image, true);
				}else{
					throw new Exception('Error! GD: PNG images are not supported');
				}
				break;
			case 'image/wbmp':
				if (imagetypes() & IMG_WBMP){ // not the same as IMAGETYPE
					$source_image = imagecreatefromwbmp($source_image);
				}else{
					throw new Exception('Error! GD: BMP images are not supported');
				}
				break;
		}

		$sw = imagesx($source_image);
		$sh = imagesy($source_image);
		switch ($watermark_gravity) {
			case "TL": // Top Left
				$offsetx = $offset;
				$offsety = $offset;
				break;
			case "TC": // Top middle
				$offsetx = intval(($sw - $wmw) / 2);
				$offsety = $offset;
				break;
			case "TR": // Top right
				$offsetx = $sw - $wmw - $offset;
				$offsety = $offset;
				break;
			case "ML": // Center left
				$offsetx = $offset;
				$offsety = intval(($sh - $wmh) / 2);
				break;
			case "MC": // Center
				$offsetx = intval(($sw - $wmw) / 2);
				$offsety = intval(($sh - $wmh) / 2);
				break;
			case "MR": // Center right
				$offsetx = $sw - $wmw - $offset;
				$offsety = intval(($sh - $wmh) / 2);
				break;
			default:
			case "BL": // Bottom left (the default)
				$offsetx = $offset;
				$offsety = $sh - $wmh - $offset;
				break;
			case "BC": // Bottom middle
				$offsetx = intval(($sw - $wmw) / 2);
				$offsety = $sh - $wmh - $offset;
				break;
			case "BR": // Bottom right
				$offsetx = $sw - $wmw - $offset;
				$offsety = $sh - $wmh - $offset;
				break;
		}

		if (!self::check_gd()){
			$cut_image = imagecreate($sw, $sh);
		}else{
			$cut_image = imagecreatetruecolor($sw, $sh);
		}

		// copying that section of the background to the cut
		imagecopy($cut_image, $source_image, 0, 0, $offsetx, $offsety, $wmw, $wmh);
		// placing the watermark now
		imagecopy($cut_image, $watermark_image, 0, 0, 0, 0, $wmw, $wmh);
		imagecopymerge($source_image, $cut_image, $offsetx, $offsety, 0, 0, $wmw, $wmh, $opacity);

		switch ($info['mime']){
			case 'image/gif':  imagegif($source_image, $destination_image);
				break;
			case 'image/jpeg': imagejpeg($source_image, $destination_image, 95);
				break;
			case 'image/png':  imagepng($source_image, $destination_image, 9);
				break;
			case 'image/wbmp': image2wbmp($source_image, $destination_image);
				break;
		}

		@imagedestroy($source_image);
		@imagedestroy($watermark_image);
	}

	/**
	 * @param string $source_image
	 * @param string $destination_image
	 * @return boolean
	 * @desc Creates a vertical mirror image
	*/
	static function flip($source_image, $destination_image) {
		$info = getimagesize($source_image) ; // see EXIF for faster way
		switch ($info['mime']){
			case 'image/gif':
				if (imagetypes() & IMG_GIF){ // not the same as IMAGETYPE
					$source_image = imagecreatefromgif($source_image);
				}else{
					throw new Exception('Error! GD: GIF images are not supported');
				}
				break;
			case 'image/jpeg':
				if (imagetypes() & IMG_JPG){ // not the same as IMAGETYPE
					$source_image = imagecreatefromjpeg($source_image);
				}else{
					throw new Exception('Error! GD: JPG images are not supported');
				}
				break;
			case 'image/png':
				if (imagetypes() & IMG_PNG){ // not the same as IMAGETYPE
					$source_image = imagecreatefrompng($source_image);
				}else{
					throw new Exception('Error! GD: PNG images are not supported');
				}
				break;
			case 'image/wbmp':
				if (imagetypes() & IMG_WBMP){ // not the same as IMAGETYPE
					$source_image = imagecreatefromwbmp($source_image);
				}else{
					throw new Exception('Error! GD: BMP images are not supported');
				}
				break;
		}

		$sw = imagesx($source_image);
		$sh = imagesy($source_image);

		if (!self::check_gd()){
			$dest_image = imagecreate($sw, $sh);
		}else{
			$dest_image = imagecreatetruecolor($sw, $sh);
			switch ($info['mime']){
				case 'image/gif': 	imagecolortransparent($dest_image, imagecolorallocate($dest_image, 0, 0, 0));
									break;
				case 'image/png':	imagealphablending($dest_image, false);
									imagesavealpha($dest_image, true);
									break;

			}
		}

		imagecopyresampled($dest_image, $source_image, 0, 0, 0, ($sh-1), $sw, $sh, $sw, 0-$sh);

		switch ($info['mime']){
			case 'image/gif':
				imagegif($dest_image, $destination_image);
				break;
			case 'image/jpeg':
				imageJPEG($dest_image, $destination_image, 95);
				break;
			case 'image/png':
				imagepng($dest_image, $destination_image, 9);
				break;
			case 'image/wbmp':
				image2wbmp($dest_image, $destination_image);
				break;
		}

		@imagedestroy($source_image);
		@imagedestroy($dest_image);
	}

	/**
	 * @param string $source_image
	 * @param string $destination_image
	 * @return boolean
	 * @desc Creates a horizontal mirror image
	*/
	static function flop($source_image, $destination_image) {
		$info = getimagesize($source_image) ; // see EXIF for faster way
		switch ($info['mime']){
			case 'image/gif':
				if (imagetypes() & IMG_GIF){ // not the same as IMAGETYPE
					$source_image = imagecreatefromgif($source_image);
				}else{
					throw new Exception('Error! GD: GIF images are not supported');
				}
				break;
			case 'image/jpeg':
				if (imagetypes() & IMG_JPG){ // not the same as IMAGETYPE
					$source_image = imagecreatefromjpeg($source_image);
				}else{
					throw new Exception('Error! GD: JPG images are not supported');
				}
				break;
			case 'image/png':
				if (imagetypes() & IMG_PNG){ // not the same as IMAGETYPE
					$source_image = imagecreatefrompng($source_image);
					imagealphablending($source_image, false);
					imagesavealpha($source_image, true);
				}else{
					throw new Exception('Error! GD: PNG images are not supported');
				}
				break;
			case 'image/wbmp':
				if (imagetypes() & IMG_WBMP){ // not the same as IMAGETYPE
					$source_image = imagecreatefromwbmp($source_image);
				}else{
					throw new Exception('Error! GD: BMP images are not supported');
				}
				break;
		}

		$sw = imagesx($source_image);
		$sh = imagesy($source_image);

		if (!self::check_gd()){
			$dest_image = imagecreate($sw, $sh);
		}else{
			$dest_image = imagecreatetruecolor($sw, $sh);
			switch ($info['mime']){
				case 'image/gif': 	imagecolortransparent($dest_image, imagecolorallocate($dest_image, 0, 0, 0));
									break;
				case 'image/png':	imagealphablending($dest_image, false);
									imagesavealpha($dest_image, true);
									break;

			}
		}

		imagecopyresampled($dest_image, $source_image, 0, 0, ($sw-1), 0, $sw, $sh, 0-$sw, $sh);

		switch ($info['mime']){
			case 'image/gif':
				imagegif($dest_image, $destination_image);
				break;
			case 'image/jpeg':
				imageJPEG($dest_image, $destination_image, 95);
				break;
			case 'image/png':
				imagepng($dest_image, $destination_image, 9);
				break;
			case 'image/wbmp':
				image2wbmp($dest_image, $destination_image);
				break;
		}

		@imagedestroy($source_image);
		@imagedestroy($dest_image);
	}

	/**
	 * Adds wet floor reflection to the image
	 *
	 * @param string $source_image
	 * @param string $destination_image
	 * @param int $gradient_height
	 * @return boolean
	 */
	static function reflect($source_image, $destination_image, $gradient_height, $gradient_color = array()) {
		$info = getimagesize($source_image) ; // see EXIF for faster way
		switch ($info['mime']){
			case 'image/gif':
				if (imagetypes() & IMG_GIF){ // not the same as IMAGETYPE
					$source_image = imagecreatefromgif($source_image);
				}else{
					throw new Exception('Error! GD: GIF images are not supported');
				}
				break;
			case 'image/jpeg':
				if (imagetypes() & IMG_JPG){ // not the same as IMAGETYPE
					$source_image = imagecreatefromjpeg($source_image);
				}else{
					throw new Exception('Error! GD: JPG images are not supported');
				}
				break;
			case 'image/png':
				if (imagetypes() & IMG_PNG){ // not the same as IMAGETYPE
					$source_image = imagecreatefrompng($source_image);
				}else{
					throw new Exception('Error! GD: PNG images are not supported');
				}
				break;
			case 'image/wbmp':
				if (imagetypes() & IMG_WBMP){ // not the same as IMAGETYPE
					$source_image = imagecreatefromwbmp($source_image);
				}else{
					throw new Exception('Error! GD: BMP images are not supported');
				}
				break;
		}

		$sw = imagesx($source_image);
		$sh = imagesy($source_image);

		if (!self::check_gd()){
			$dest_image = imagecreate($sw, $sh + $gradient_height);
		}else{
			$dest_image = imagecreatetruecolor($sw, $sh + $gradient_height);
			switch ($info['mime']){
				case 'image/gif': 	imagecolortransparent($dest_image, imagecolorallocate($dest_image, 0, 0, 0));
									break;
				case 'image/png':	imagealphablending($dest_image, false);
									imagesavealpha($dest_image, true);
									break;

			}
		}

		$gradparts = (empty($gradient_color)) ? array(255, 255, 255) : $gradient_color;  // get the parts of the colour (RRR,GGG,BBB)
		$gradient_color = imagecolorallocate($dest_image, $gradparts[0], $gradparts[1], $gradparts[2]);

		imagecopyresampled($dest_image, $source_image,  0, 0, 0, 0, $sw, $sh, $sw, $sh);
		imagefilledrectangle($dest_image, 0, $sh, $sw, $sh + $gradient_height, $gradient_color);
		$i = 0;
		$gradient_y_startpoint = 0;
		$transparency = 85; //from 85 to 0
		$transparency_ratio = (85 / $gradient_height);
		while ($i < $gradient_height) {
			imagecopymerge($dest_image, $source_image, 0, ($sh + $gradient_y_startpoint), 0, ($sh - $gradient_y_startpoint - 1), $sw, 1, $transparency);
			++$i;
			++$gradient_y_startpoint;
			$transparency = ($transparency <= 0) ? 0 : $transparency - $transparency_ratio;
		}

		switch ($info['mime']){
			case 'image/gif':
				imagegif($dest_image, $destination_image);
				break;
			case 'image/jpeg':
				imageJPEG($dest_image, $destination_image, 95);
				break;
			case 'image/png':
				imagepng($dest_image, $destination_image, 9);
				break;
			case 'image/wbmp':
				image2wbmp($dest_image, $destination_image);
				break;
		}

		@imagedestroy($source_image);
		@imagedestroy($dest_image);

	}

	/**
	 * Rotate image left or right by 90 degrees
	 *
	 * @param string $source_image
	 * @param string $destination_image
	 * @param boolean $left_or_right
	 */
	static function rotate($source_image, $destination_image, $left_or_right) {
		$degree = (boolean)($left_or_right) ? 90 : 270;
		$info = getimagesize($source_image) ; // see EXIF for faster way
		switch ($info['mime']){
			case 'image/gif':
				if (imagetypes() & IMG_GIF){ // not the same as IMAGETYPE
					$source_image = imagecreatefromgif($source_image);
				}else{
					throw new Exception('Error! GD: GIF images are not supported');
				}
				break;
			case 'image/jpeg':
				if (imagetypes() & IMG_JPG){ // not the same as IMAGETYPE
					$source_image = imagecreatefromjpeg($source_image);
				}else{
					throw new Exception('Error! GD: JPG images are not supported');
				}
				break;
			case 'image/png':
				if (imagetypes() & IMG_PNG){ // not the same as IMAGETYPE
					$source_image = imagecreatefrompng($source_image);
				}else{
					throw new Exception('Error! GD: PNG images are not supported');
				}
				break;
			case 'image/wbmp':
				if (imagetypes() & IMG_WBMP){ // not the same as IMAGETYPE
					$source_image = imagecreatefromwbmp($source_image);
				}else{
					throw new Exception('Error! GD: BMP images are not supported');
				}
				break;
		}

		$dest_image = imagerotate($source_image, $degree, 0);

		switch ($info['mime']){
			case 'image/gif':
				imagegif($dest_image, $destination_image);
				break;
			case 'image/jpeg':
				imageJPEG($dest_image, $destination_image, 95);
				break;
			case 'image/png':
				imagepng($dest_image, $destination_image, 9);
				break;
			case 'image/wbmp':
				image2wbmp($dest_image, $destination_image);
				break;
		}

		@imagedestroy($source_image);
		@imagedestroy($dest_image);

	}
}

?>