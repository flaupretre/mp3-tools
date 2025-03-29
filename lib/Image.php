<?php

$GLOBALS['img_suffixes']=array('jpg' => 'image/jpg'
	,'jpeg' => 'image/jpg'
	,'png' => 'image/png'
	,'gif' => 'image/gif');

class Image
{

//-----------------------

public static function is_image_path($path)
{
return self::is_image_suffix(Phool\File::fileSuffix($path));
}

//-----------------------

public static function is_image_suffix($ext)
{
return (array_key_exists($ext,$GLOBALS['img_suffixes']));
}

//-----------------------

public static function mime_type($path)
{
return $GLOBALS['img_suffixes'][Phool\File::fileSuffix($path)];
}

//-----------------------

public static function mk_image($data)
{
if (($img=@imagecreatefromstring($data))===false)
	throw new Exception("Cannot get image");
return $img;
}

//-----------------------

public static function image_to_jpeg($img,&$data,&$mime)
{
ob_start();
imagejpeg($img);
$data=ob_get_clean();
$mime='image/jpeg';
}

//-----------------------

public static function mk_image_from_file($path)
{
return self::mk_image(file_get_contents($path));
}

//-----------------------
// Returns true if image modified, false if not

public static function normalize(&$img)
{
$modified=false;

$w=imagesx($img);
$h=imagesy($img);

//-- Cut left/bottom square (tolerate 20% diff)

$mi=min($w,$h);
$d=abs($w-$h);
if (($d*5) > $mi)
	{
	//Phool\Display::debug('Cutting bottom/left square');
	if ($h>$w) Phool\Display::debug('Note : Image height is larger');
	$len=$mi;
	$img2=imagecreatetruecolor($len,$len);
	imagecopy($img2,$img,0,0,$w-$len,$h-$len,$len,$len);
	$img=$img2;
	$w=$h=$len;
	unset($img2);
	$modified=true;
	}

//- Resize to IMG_MAX_SIZE max

$ma=max($w,$h);
if ($ma > IMG_MAX_SIZE)
	{
	$ratio=$ma/IMG_MAX_SIZE;
	$w2=round($w/$ratio);
	$h2=round($h/$ratio);
	Phool\Display::debug("Resizing image: ${w}x${h} to ${w2}x${h2}");
	$img2=imagecreatetruecolor($w2,$h2);
	imagecopyresampled($img2,$img,0,0,0,0,$w2,$h2,$w,$h);
	$img=$img2;
	unset($img2);
	$modified=true;
	}

return $modified;
}

//-----------------------
}
//-----------------------
?>