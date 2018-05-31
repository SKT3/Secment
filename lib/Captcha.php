<?php

class Captcha {
  
  protected $fontPath = '';
  protected $fontFile = 'arialbd.gdf';
  protected $imageWidth = 159;
  protected $imageHeight = 48;
  protected $allowedChars = '1234567890';
  protected $stringLength = 4;
  protected $charWidth = 20;
  protected $blurRadius = 3;
  protected $secretKey = 'hO$#@!ADFr7($(^';
  
  public function __construct($options = array()) {
    @session_start();

  $this->fontPath = Config()->LIB_PATH.'fonts';
  
    if (is_array($options)) {
      $allowedOptions = array ('sessionName', 'fontPath', 'fontFile',
             'imageWidth', 'imageHeight', 'allowedChars', 'stringLength',
             'charWidth', 'blurRadius', 'secretKey'
        );
      $allowedOptionsCount = count($allowedOptions);
      for ($i = 0; $i < $allowedOptionsCount; $i++) {
        if (isset($options[$allowedOptions[$i]])) {
          $this->$allowedOptions[$i] = $options[$allowedOptions[$i]]; 
        }
      }
    }
  }

  public function getCaptcha($for) {
    $rand = $this->randomString();
  $code = md5($rand.$this->secretKey);

  Registry()->session->{'captcha_security_key'.$for} = $code;
  
    $this->generateValidationImage($rand);
  }

  public function isKeyRight($key,$for='') {
    $isKeyRight = Registry()->session->{'captcha_security_key'.$for} == md5($key.$this->secretKey);
    unset(Registry()->session->{'captcha_security_key'.$for});
    if ($isKeyRight) {
      return true;
    }
    return false;
  }

  protected function randomString() {
    $chars = $this->allowedChars;
    $s = "";
    for ($i = 0; $i < $this->stringLength; $i++) {
      $int         = rand(0, strlen($chars)-1);
      $rand_letter = $chars[$int];
      $s           = $s . $rand_letter;
    }
    return $s;
  }

  protected function generateValidationImage($rand) {
    $width = $this->imageWidth;
    $height = $this->imageHeight;
    $image = imagecreate($width, $height);
    $bgColor = imagecolorallocate ($image, 103, 103, 103);
    $textColor = imagecolorallocate ($image, 255, 255, 255);

    // add random noise
    for ($i = 0; $i < 10; $i++) {
      $rx1 = rand(0, $width);
      $rx2 = rand(0, $width);
      $ry1 = rand(0, $height);
      $ry2 = rand(0, $height);
      $rcVal = rand(200, 255);
      $rc1 = imagecolorallocate($image, $rcVal, $rcVal, $rcVal);
      imageline($image, $rx1, $ry1, $rx2, $ry2, $rc1);
    }

    // write the random number
    $font = imageloadfont($this->fontPath."/".$this->fontFile);
    for ($i = 0; $i < $this->stringLength; $i ++) {
      imagestring($image, $font, 40 + ($i * $this->charWidth), 5, $rand[$i], $textColor);
    }

    $this->blur($image, $this->blurRadius);

    // send several headers to make sure the image is not cached
    // date in the past
    header("Expires: Mon, 23 Jul 1993 05:00:00 GMT");

    // always modified
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

    // HTTP/1.1
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);

    // HTTP/1.0
    header("Pragma: no-cache");

    // send the content type header so the image is displayed properly
    header('Content-type: image/jpeg');

    imagejpeg($image);
    imagedestroy($image);
  }

  protected function blur(&$gdimg, $radius = 5.0) {

    $radius = round(max(0, min($radius, 50)) * 2);
    if (!$radius) {
      return false;
    }

    $w = ImageSX($gdimg);
    $h = ImageSY($gdimg);
    if ($imgBlur = ImageCreateTrueColor($w, $h)) {
      // Gaussian blur matrix:
      //    1    2    1
      //    2    4    2
      //    1    2    1

      // Move copies of the image around one pixel at the time and merge them with weight
      // according to the matrix. The same matrix is simply repeated for higher radii.
      for ($i = 0; $i < $radius; $i++)    {
        ImageCopy     ($imgBlur, $gdimg, 0, 0, 1, 1, $w - 1, $h - 1);            // up left
        ImageCopyMerge($imgBlur, $gdimg, 1, 1, 0, 0, $w,     $h,     50.00000);  // down right
        ImageCopyMerge($imgBlur, $gdimg, 0, 1, 1, 0, $w - 1, $h,     33.33333);  // down left
        ImageCopyMerge($imgBlur, $gdimg, 1, 0, 0, 1, $w,     $h - 1, 25.00000);  // up right
        ImageCopyMerge($imgBlur, $gdimg, 0, 0, 1, 0, $w - 1, $h,     33.33333);  // left
        ImageCopyMerge($imgBlur, $gdimg, 1, 0, 0, 0, $w,     $h,     25.00000);  // right
        ImageCopyMerge($imgBlur, $gdimg, 0, 0, 0, 1, $w,     $h - 1, 20.00000);  // up
        ImageCopyMerge($imgBlur, $gdimg, 0, 1, 0, 0, $w,     $h,     16.666667); // down
        ImageCopyMerge($imgBlur, $gdimg, 0, 0, 0, 0, $w,     $h,     50.000000); // center
        ImageCopy     ($gdimg, $imgBlur, 0, 0, 0, 0, $w,     $h);
      }
      return true;
    }
    return false;
  }
}
