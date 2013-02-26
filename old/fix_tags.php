<?php

define('BLANKS',' 	');
define('SEPAR','-_');
define('BSEPAR',BLANKS.SEPAR);
define('DIGITS','0123456789');

$GLOBALS['img_extensions']=array('jpg' => 'image/jpg'
	,'png' => 'image/png'
	,'gif' => 'image/gif');

$GLOBALS['music_extensions']=array('mp3' => 1,'mpc' => 1);

$GLOBALS['cover_patterns']=array(
	'front$',
	'recto$',
	'front',
	'recto',
	'frontal',
	'large$',
	'albumart');

//-------

if (!extension_loaded('ktaglib')) dl('ktaglib.so');

require('/usr/libexec/phool.phk');

//-----------------------

class mp3_file
{

private $artist_dir;
private $album_dir;
private $fname;
private $path;
private $mfile;
private $audio;
private $id1;
private $id2;
private $modified;
private $dest_fname;
private $cover_path;
private $has_picture;

//-----------------------

public function __construct($artist_dir,$album_dir,$fname,$path,$cover_path)
{
$this->artist_dir=$artist_dir;
$this->album_dir=$album_dir;
$this->fname=$this->dest_fname=$fname;
$this->path=$path;
$this->cover_path=$cover_path;

$this->mfile=new KTaglib_MPEG_File($path);
$this->audio=$this->mfile->getAudioProperties();
$this->id1=$this->mfile->getID3v1Tag();
$this->id2=$this->mfile->getID3v2Tag();
$this->has_picture=false;
foreach($this->id2->getFrameList() as $frame)
	{
	if (get_class($frame)==='KTaglib_ID3v2_AttachedPictureFrame')
		{
		$this->has_picture=true;
		break;
		}
	}
	
$this->clear_modified();
}

//----------------

public function get_artist()
{
$artist=$this->id1->getArtist();
if ($artist=='') $artist=$this->id2->getArtist();
return $artist;
}

//----------------

public function set_artist($artist)
{
$this->id1->setArtist($artist);
$this->id2->setArtist($artist);
$this->set_modified();
}

//----------------

public function get_album()
{
$album=$this->id1->getAlbum();
if ($album=='') $album=$this->id2->getAlbum();
return $album;
}

//----------------

public function set_album($album)
{
$this->id1->setAlbum($album);
$this->id2->setAlbum($album);
$this->set_modified();
}

//----------------

public function get_title()
{
$title=$this->id1->getTitle();
if ($title=='') $title=$this->id2->getTitle();
return $title;
}

//----------------

public function set_title($title)
{
$this->id1->setTitle($title);
$this->id2->setTitle($title);
$this->set_modified();
}

//----------------

public function fix()
{
PHO_Display::trace('======= '.$this->path);

//---- Cover	

if (!$this->has_picture)
	{
	if ($this->cover_path!=='')
		{
		PHO_Display::trace('Setting picture to '.$this->cover_path);
		$pframe=new KTaglib_ID3v2_AttachedPictureFrame($this->cover_path);
		$this->id2->addFrame($pframe);
		$pframe->setMimeType('image/jpg');//mime_type($this->cover_path));
		$pframe->setType(KTaglib_ID3v2_AttachedPictureFrame::FrontCover);
		$this->set_modified();
		}
	else
		{
		PHO_Display::trace('No picture available');
		}
	}

//-- Fix artist

$artist=$artist_orig=$this->get_artist();
if ($artist !== $this->artist_dir)
	{
	PHO_Display::trace('Artist: <'.$artist.'> -> <'.$this->artist_dir.'>');
	$this->set_artist($artist=$this->artist_dir);
	}

//-- Fix album

if ((($album=$this->get_album())=='') && ($this->album_dir!=''))
	{
	PHO_Display::trace('Album: <'.$album.'> -> <'.$this->album_dir.'>');
	$this->set_album($this->album_dir);
	}

//-- Fix filename

$fname=$this->fname;
$fname=suppress_prefix($fname,$artist);
if ($artist !== $artist_orig) $fname=suppress_prefix($fname,$artist_orig);
$fname=suppress_prefix($fname,$this->album_dir);

$this->dest_fname=$fname;
if ($this->dest_fname != $this->fname)
	PHO_Display::trace('Filename: <'.$this->fname.'> -> <'.$this->dest_fname.'>');

//-- Title if not set

if (($title=$this->get_title())=='')
	{
	// Get title from fname
	$t=trim(basename($this->dest_fname),BSEPAR);
	if (strpos(DIGITS,$t{0})!==false) // Suppress track number prefix if present
		{
		$t2=trim($t,BLANKS.DIGITS);
		if (strpos(SEPAR,$t2{0})!==false) $t=trim($t2,BSEPAR);
		}
	PHO_Display::trace('Title: <'.$title.'> -> <'.$t.'>');
	$this->set_title($t);
	}
}

//----------------

public function save()
{
if ($this->modified)
	{
	$this->mfile->save();
	$this->clear_modified();
	}

//-- Rename file if needed

if ($this->dest_fname !== $this->fname)
	{
	$dest_path=dirname($this->path).'/'.$this->dest_fname;
	rename($this->path,$dest_path);
	$this->path=$dest_path;
	$this->fname=$this->dest_fname;
	}
}

//----------------

public function info()
{
echo '=============== '.$this->path."\n";
echo 'BitRate: '.$this->audio->getBitrate()."\n";
echo 'Channels: '.$this->audio->getChannels()."\n";
echo 'Layer: '.$this->audio->getLayer()."\n";
echo 'Length: '.$this->audio->getLength()."\n";

echo 'Artist: '.$this->id1->getArtist()."\n";
echo 'Title: '.$this->id1->getTitle()."\n";
}

//----------------

private function set_modified()
{
$this->modified=true;
}

//----------------

private function clear_modified()
{
$this->modified=false;
}

//----------------


} // End of class

//-----------------------

function suppress_prefix($string,$prefix)
{
$orig_string=$string=trim($string,BSEPAR);
$prefix=trim($prefix,BSEPAR);

$plen=strlen($prefix);
if (strcasecmp(substr($string,0,$plen),$prefix)==0)
	{
	$str2=trim(substr($string,$plen),BLANKS);
	if (strpos(SEPAR,$str2{0})!==false) return trim($str2,BSEPAR);
	}
return $orig_string;
}

//-----------------------

function my_scandir($path)
{
$res=array();

foreach(scandir($path) as $fname)
	{
	if ($fname!='.' && ($fname!='..')) $res[]=$fname;
	}
return $res;
}

//-----------------------

function file_extension($path)
{
return strtolower(substr(strrchr($path,'.'),1));
}

//-----------------------

function is_music_extension($ext)
{
return (array_key_exists($ext,$GLOBALS['music_extensions']));
}

//-----------------------

function is_image_extension($ext)
{
return (array_key_exists($ext,$GLOBALS['img_extensions']));
}

//-----------------------

function mime_type($path)
{
return $GLOBALS['img_extensions'][file_extension($path)];
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

function scan_fix($path,$artist_dir,$album_dir)
{
$music_files=array();
$img_paths=array();
$img_bases=array();
$subdirs=array();

//-- First, Split entries in categories

foreach (my_scandir($path) as $fname)
	{
	$fpath=$path.'/'.$fname;
	$ext=file_extension($fname);
	if (is_dir($fpath))
		{
		$subdirs[]=$fname;
		}
	elseif (is_music_extension($ext))
		{
		$music_files[]=$fname;
		}
	elseif (is_image_extension($ext))
		{
		$img_paths[]=$fpath;
		$img_bases[]=strtolower(trim(basename($fname)));
		}
	else
		{
		Pho_Display::warning("Found unknown file extension: $ext");
		}
	}

//-- Find best cover image

$cover_path='';
switch (count($img_paths))
	{
	case 0:
		break;
	case 1:
		$cover_path=$img_paths[0];
		break;
	default: // Multiple images in dir
		foreach($GLOBALS['cover_patterns'] as $pattern)
			{
			if (($idx=array_find_pattern($img_bases,$pattern))!==false)
				{
				$cover_path=$img_paths[$idx];
				break;
				}
			}
		// If no pattern match, take first image (alpha order)
		if ($cover_path=='') $cover_path=$img_paths[0];
	}

//-- Fix files

foreach($music_files as $fname)
	{
	$f=new mp3_file($artist_dir,$album_dir,$fname,$path.'/'.$fname,$cover_path);
	$f->fix();
	$f->save();
	}

//-- Recurse

foreach($subdirs as $dir)
	{
	$adir=$dir;
	if ((strlen($dir)>=2)&&(strtolower(substr($dir,0,2))==='cd'))
		$adir=$album_dir.' - '.$dir;
	scan_fix($path.'/'.$dir,$artist_dir,$adir);
	}
}

//================ MAIN =======================

PHO_Display::set_verbose(2);

$base=$argv[1];
$cover_path='';

foreach(my_scandir($base) as $artist_dir)
	{
	$artist_path=$base.'/'.$artist_dir;
	if (!is_dir($artist_path)) continue;
	scan_fix($artist_path,$artist_dir,$artist_dir);
	}

?>