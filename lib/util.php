<?php

$GLOBALS['img_suffixes']=array('jpg' => 'image/jpg'
	,'jpeg' => 'image/jpg'
	,'png' => 'image/png'
	,'gif' => 'image/gif');

$GLOBALS['music_suffixes']=array('mp3' => 1);

//-----------------------

function is_music_suffix($ext)
{
return (array_key_exists($ext,$GLOBALS['music_suffixes']));
}

//-----------------------

function is_image_suffix($ext)
{
return (array_key_exists($ext,$GLOBALS['img_suffixes']));
}

//-----------------------

function mime_type($path)
{
return $GLOBALS['img_suffixes'][PHO_File::file_suffix($path)];
}

//-----------------------

function array_find_pattern($a,$pattern)
{
foreach($a as $idx => $value)
	{
	if (preg_match('/'.$pattern.'/',$value)>0) return $idx;
	}
return false;
}

//-----------------------

function get_subdirs($path)
{
$a=array();

foreach(PHO_File::scandir($path) as $entry)
	{
	if (is_dir(PHO_FILE::combine_path($path,$entry))) $a[]=basename($entry);
	}
natcasesort($a);
return $a;
}

//-----------------------

function add_prefix($string,$prefix)
{
return (starts_with($string,$prefix) ? $string : $prefix.$string);
}
//-----------------------

function starts_with($string,$prefix)
{
$len=strlen($prefix);
return ((strlen($string)>=$len)&&(strcasecmp(substr($string,0,$len),$prefix)==0));
}

//-----------------------

function ends_with($string,$suffix)
{
$len=strlen($suffix);
return ((strlen($string)>=$len)&&(strcasecmp(substr($string,-$len),$suffix)==0));
}

//-----------------------

function btrim($string)
{
return trim($string,BSEPAR);
}

//-----------------------

function fix_spaces($string)
{
return btrim(preg_replace('/\s+/u',' ',$string));
}

//-----------------------

function suppress_prefix(&$string,$prefix)
{
$prefix=trim($prefix,BSEPAR);
if ($prefix=='') return $string;

$string=trim($string,BSEPAR);

if (suppress_prefix_2($string,$prefix)) return true;
if (suppress_prefix_2($string,str_replace(' ','',$prefix))) return true;

return false;
}

//-----------------------


function suppress_prefix_2(&$string,$prefix) // private
{
$plen=strlen($prefix);
if (strcasecmp(substr($string,0,$plen),$prefix)==0)
	{
	$str2=trim(substr($string,$plen),BLANKS);
	if (($str2!=='') && (strpos(SEPAR,$str2{0})!==false))
		{
		$str2=trim($str2,BSEPAR);
		if ($str2!=='')
			{
			$string=$str2;
			return true;
			}
		}
	}
return false;
}

//-----------------------

function normalize_string($string)
{
$string=str_replace('/','-',$string);
$string=str_replace('(','',$string);
$string=str_replace(')','-',$string);

return $string;
}

//-----------------------

function clear_key(&$a,$key)
{
if (isset($a[$key])) unset($a[$key]);
}

//-----------------------

function mk_image($data)
{
if (($img=@imagecreatefromstring($data))===false)
	throw new Exception("Cannot get image");
return $img;
}

//-----------------------

function image_to_jpeg($img,&$data,&$mime)
{
ob_start();
imagejpeg($img);
$data=ob_get_clean();
$mime='image/jpeg';
}

//-----------------------

function mk_image_from_file($path)
{
return mk_image(file_get_contents($path));
}

//-----------------------
// Returns true if image modified, false if not

function normalize_image(&$img)
{
$modified=false;

$w=imagesx($img);
$h=imagesy($img);

//-- Cut left/bottom square (tolerate 20% diff)

$mi=min($w,$h);
$d=abs($w-$h);
if (($d*5) > $mi)
	{
	//PHO_Display::trace('Cutting bottom/left square');
	if ($h>$w) PHO_Display::debug('Note : Image height is larger');
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
	//PHO_Display::trace("Resizing image: ${w}x${h} to ${w2}x${h2}");
	$img2=imagecreatetruecolor($w2,$h2);
	imagecopyresampled($img2,$img,0,0,0,0,$w2,$h2,$w,$h);
	$img=$img2;
	unset($img2);
	$modified=true;
	}

return $modified;
}

//-----------------------

function fname_to_utf($fname)
{
if (!strlen($fname)) return '';

//PHO_Display::trace("fname_to_utf: input: $fname - ".bin2hex($fname));

if ($GLOBALS['fname_encoding']=='UTF-8') return $fname;
//return iconv($GLOBALS['fname_encoding'],'UTF-8',$fname);
$res=mb_convert_encoding($fname,'UTF-8',$GLOBALS['fname_encoding']);

//PHO_Display::trace("fname_to_utf: output: $res - ".bin2hex($res));
return $res;
}

//-----------------------

function utf_to_fname($string)
{
if (!strlen($string)) return '';

//PHO_Display::trace("utf_to_fname: input: $string - ".bin2hex($string));

if ($GLOBALS['fname_encoding']=='UTF-8') return $string;
//return iconv('UTF-8',$GLOBALS['fname_encoding'],$string);
$res=mb_convert_encoding($string,$GLOBALS['fname_encoding'],'UTF-8');

//PHO_Display::trace("utf_to_fname: output: $res - ".bin2hex($string));
return $res;
}

//-----------------------

function get_options()
{
$args=PHO_Getopt::readPHPArgv();
array_shift($args);
list($options,$args)=PHO_Getopt::getopt($args,'nv',array('verbose','noexec'));

foreach($options as $option)
	{
	list($opt,$arg)=$option;
	switch($opt)
		{
		case 'v':
		case '--verbose':
			PHO_Display::inc_verbose();
			break;

		case 'n':
		case '--noexec':
			$GLOBALS['do_changes']=false;
			break;
		}
	}
$GLOBALS['argv']=$args;
}

//-----------------------
?>