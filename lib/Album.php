<?php

$GLOBALS['ignored_files']=array(
	'thumbs.db' => 1,
	'desktop.ini' => 1);

$GLOBALS['ignored_extensions']=array(
	'txt' => 1,
	'mpg' => 1,
	'doc' => 1,
	'cue' => 1,
	'pdf' => 1,
	'nfo' => 1);

$GLOBALS['cover_patterns']=array(
	'folder',
	'front$',
	'recto$',
	'front',
	'recto',
	'cover',
	'frontal',
	'large$',
	'albumart');

//--------------------------

class Album
{
public $dir;	// Relative path from artist dir
public $name;	// Album name (UTF)
public $songs;	// Array of Song objects
public $default_year='';

public $cover_data=null;	// null if no cover
public $cover_mime;

public $artist ; // backlink

//------

public function __construct($artist,$dir)
{
Phool\Display::debug("Creating album: ".(($dir === '') ? '<Root>' : $dir));

$this->artist=$artist;
$this->dir=$dir;
$this->name=fname_to_utf($this->dir);
$this->songs=array();

//---- Populate

$images=array();

$this->_get_dir_elements('',(!$this->is_root()),$images);

// No album if it contains no song

if (count($this->songs)===0) throw new Exception('No song');

//-- Find best cover image

$this->cover_data=$this->cover_mime=$cover_file=null;
switch (count($images))
	{
	case 0:
		break;
	case 1:
		$cover_file=$images[0];
		break;
	default: // Multiple images in dir
		$img_bases=array();
		foreach($images as $rpath)
			{
			$bn=basename($rpath);
			$img_bases[]=strtolower(substr($bn,0,strrpos($bn,'.')));
			}
		foreach($GLOBALS['cover_patterns'] as $pattern)
			{
			if (($idx=array_find_pattern($img_bases,$pattern))!==false)
				{
				$cover_file=$images[$idx];
				break;
				}
			}
		// If no pattern match, take first image (alpha order)
		if (is_null($cover_file)) $cover_file=$images[0];
	}
if (!is_null($cover_file)) $this->get_cover_from_file($this->path($cover_file));

//-- If we don't have a cover image, try to get one from a song

if (!$this->has_cover())
	{
	Phool\Display::debug($this->relpath().': Searching for cover in songs');
	foreach($this->songs as $song)
		{
		if ($song->has_cover())
			// Warning: Song cover is not normalized at this time
			{
			Phool\Display::debug($song->fname.': song has cover');
			try
				{
				$img=Image::mk_image($song->cover_data);
				if (Image::normalize($img))
					{
					Phool\Display::debug($this->relpath().': Normalizing album cover image');
					Image::image_to_jpeg($img,$this->cover_data,$this->cover_mime);
					}
				else
					{
					$this->cover_data=$song->cover_data;
					$this->cover_mime=$song->cover_mime;
					}
				unset($img);
				}
			catch (Exception $e)	// Exception: Ignore image and try the next one
				{
				Phool\Display::trace($song->relpath()
					.': Error getting album cover from song:'.$e->getMessage());
				} 
			if ($this->has_cover()) break;
			}
		}
	}
}

//----

private function _get_dir_elements($rdir,$recurse,&$images)
{
$absdir=$this->path($rdir);
$files=Phool\File::scandir($absdir);

usort($files,'strcasecmp');

//$coll=new Collator('fr_FR');
//$coll->sort($files);

//natcasesort($files);

foreach ($files as $fname)
	{
	$abspath=Phool\File::combinePath($absdir,$fname);
	$rpath=Phool\File::combinePath($rdir,$fname);
	$ext=Phool\File::fileSuffix($fname);
	if (is_dir($abspath))
		{
		if ($recurse) $this->_get_dir_elements($rpath,true,$images);
		continue;
		}
	if (Song::is_a_song($abspath))
		{
		try
			{
			$this->songs[]=$song=new Song($this,$rdir,$fname);
			if (($this->default_year==='')&&($song->year!=''))
				$this->default_year=$song->year;
			}
		catch (Exception $e)
			{
			$msg=$e->getMessage();
			if ($msg!='') Phool\Display::warning($this->relpath().': Ignoring song <'
				.$fname.'> - Reason: '.$msg);
			}
		}
	elseif (Image::is_image_suffix($ext))
		{
		$images[]=$rpath;
		}
	else
		{
		if ((!array_key_exists(strtolower($fname),$GLOBALS['ignored_files']))
			&& (!array_key_exists($ext,$GLOBALS['ignored_extensions'])))
			Phool\Display::warning("Found unknown file type: $fname");
		}
	}
}

//------

public function track_count()
{
return count($this->songs);
}

//------

public function is_root()
{
return ($this->name==='');
}

//------

public function has_cover()
{
return (!is_null($this->cover_data));
}

//------

private function get_cover_from_file($path)
{
try {
	$img=Image::mk_image_from_file($path);
	if (Image::normalize($img))
		{
		Phool\Display::debug($path.': Normalizing album cover image');
		}
	Image::image_to_jpeg($img,$this->cover_data,$this->cover_mime);
	unset($img);
	}
catch (Exception $e)
	{
	throw new Exception($this->path().': Reading album cover image (from '
		.$path.') - '.$e->getMessage());
	}
}

//------

public function fix()
{
// Fix album name

$nname=ucfirst(fix_spaces($this->name));

//-- Suppress artist name as album prefix

suppress_prefix($nname,$this->artist->name);
suppress_prefix($nname,'the '.$this->artist->name);

//-- Remove year, if present

$def_year=null;
$new_nname='';
$a=array();
foreach(array(
	'/^\(?(\d\d\d\d)\)?\s*[\-\._]\s*(\S.*)$/u',	// Ex: '(1989) title' ou '1989 - title'
	'/^\[([12]\d\d\d)\]\s*[\-\._]?\s*(\S.*)$/u'	// Ex: [1989] Wish you were here
	) as $re)
	{
	if (preg_match($re,$nname,$a))
		{
		$def_year=$a[1];
		$new_nname=$a[2];
		break;
		}
	}

if (is_null($def_year) && preg_match('/^(.*\S)\s*\((\d\d\d\d)\)$/u',$nname,$a))
	{
	$def_year=$a[2];
	$new_nname=$a[1];
	}

if (!is_null($def_year))
	{
	// Year valid if between 1900 and today
	if (($def_year > 1900)&&($def_year <= date('Y')))
		{
		$this->default_year=$def_year;
		$nname=btrim($new_nname);
		}
	}

//-- Suppress name again, as it can be after the year

suppress_prefix($nname,$this->artist->name);

//-- Rename dir if name changed

if ($nname !== $this->name) $this->rename($nname);

// Fix songs

$arc=false;

while (1)
	{
	$rc=false;
	foreach($this->songs as $song)
		{
		if ($song->fix()) $arc=$rc=true;
		}
	if (!$rc) break;
	//-- Sort songs for valid autotracks
	usort($this->songs,array('Song','sort_callbak'));
	}
}

//------

public function dir_order($song)
{
$res=array_search($song,$this->songs,true);
return ($res===false) ? false : $res+1;
}

//------

public function check()
{
// Check songs

foreach($this->songs as $song) $song->check();
}

//------

public function rename($name)
{
Phool\Display::trace('Renaming album : <'.$this->name.'> to <'.$name.'>');

$newdir=utf_to_fname($name);
if ($GLOBALS['do_changes'])
	{
	rename($this->path(),$this->artist->path($newdir));
	}
$this->name=$name;
$this->dir=$newdir;
}

//------

public function to_spare()
{
$res=0;
foreach($this->songs as $song) $res +=$song->to_spare();
return $res;
}

//------

public function path($fname=null)
{
$path=$this->artist->path($this->dir);
if (!is_null($fname)) $path=Phool\File::combinePath($path,$fname);
return $path;
}

//------
public function relpath($fname=null)
{
$path=$this->artist->relpath($this->dir);
if (!is_null($fname)) $path=Phool\File::combinePath($path,$fname);
return $path;
}

//------
} // End of class

//--------------------------
?>