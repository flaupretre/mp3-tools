<?php

$GLOBALS['props']=array(
	'title' => ''
	,'artist' => ''
	,'album' => ''
	,'year' => ''
	,'comment' => ''
	,'genre' => ''
	,'track' => 0);

$GLOBALS['tag_translation']=array(
	'tagging_time' => null,	// ID3V2.4 only
	'recording_time' => null,	// ID3V2.4 only
	'release_time' => null,	// ID3V2.4 only
	'encoding_time' => null,	// ID3V2.4 only
	'performer_sort_order' => null,	// ID3V2.4 only
	'text' => null,	// Strange error message when converting to 'user-text'
	'Linked information' => null,	// Another strange one
	'commercial_information' => 'commercial',
	'copyright_message' => 'copyright',
	'unsynchronised_lyric' => 'unsynchronised_lyrics',
	'part_of_a_compilation' => 'itunescompilation',
	'bpm' => 'beats_per_minute',
	'original_album' => 'original_album_title',
	'url_payment' => 'payment',
	'album_sort_order' => null, // Unknown field in write code
	'band' => null,	// Creates a different album entry on Ipod
//	'publisher' => null,	// Creates a different album entry on Ipod
  'totaltracks' => null,
	);

//-------------

class Song extends Phool\Modifiable
{
public $dir;	// Relative dir from album path
public $fname;

private $album_obj; // backlink

public $format;
public $title;
public $artist;
public $album;
public $year;
public $comment;
public $genre;
public $track;
public $audio_offset; // Bytes
public $audio_size; // Bytes

public $other_tags; // Array of additional ID3V2 tags to preserve

public $bitrate;

public $cover_data=null;
public $cover_mime='';

public $mcomments;

//------

private static $id3=null;

//------

public function __construct($album_obj,$dir,$fname)
{
Phool\Display::debug("Creating song: ".$fname);

$this->album_obj=$album_obj;
$this->dir=$dir;
$this->fname=$fname;

parent::__construct();

//-- Populate

$info=$this->get_info();

if (!array_key_exists('fileformat',$info))
	throw new Exception('Unrecognized file format');

//$info['comments']['picture'][0]['data']='';var_dump($info);//DEBUG	

$this->format=$info['fileformat'];

$this->bitrate=round($info['bitrate']/1000);

list($this->audio_offset,$this->audio_size)=self::audio_data_from_info($info);

$this->other_tags=(isset($info['tags']['id3v2']) ? $info['tags']['id3v2'] : array());

foreach($GLOBALS['props'] as $prop => $def)
	{
	if (isset($info['tags']['id3v2'][$prop][0]))
		$this->$prop=$info['tags']['id3v2'][$prop][0];
	else if (isset($info['tags']['id3v1'][$prop][0]))
		$this->$prop=$info['tags']['id3v1'][$prop][0];
	else $this->$prop=$def;
	clear_key($this->other_tags,$prop);
	}
clear_key($this->other_tags,'track_number');

//-- Extract mcomments

$this->mcomments=array();
$comments=array();
$mlen=strlen(MCOMMENT_TAG);
foreach(explode("\r\n",$this->comment) as $comment)
	{
	if ((strlen($comment)>$mlen)&&(substr($comment,0,$mlen)===MCOMMENT_TAG))
		{
		$comment=substr($comment,$mlen);
		if (($pos=strpos($comment,':'))!==false)
			{
			$mname=substr($comment,0,$pos);
			$mvalue=Phool\Util::substr($comment,$pos+1);
			}
		else
			{
			$mname=$comment;
			$mvalue=null;
			}
		$this->mcomments[$mname]=$mvalue;
		}
	else
		{
		$comments[]=$comment;
		}
	}
$this->comment=implode("\r\n",$comments);

//-- Fix album name between internal and external value

if ($this->album==='') $this->setModified(); // Force change by fix()
if ($this->album===NO_ALBUM) $this->album='';

//-- Track number could be set as 'track_number' in V2 tags only

if (($this->track==0)&&(isset($info['tags']['id3v2']['track_number'][0])))
	{
	$this->track=$info['tags']['id3v2']['track_number'][0];
	 // To set ID3V1 track number (id3v1 does not support track number greater than 255)
	 if ($this->track < 256) $this->setModified();
	}

// Fix track (remove optional '/<total>' suffix)

$this->track=substr($this->track,0,strcspn($this->track,'/'));

//-- Cover image

$this->cover_data=$this->cover_mime=null;
if (isset($info['comments']['picture']))
	{
	// Some files contain a short invalid data block, which we ignore
	if (strlen($info['comments']['picture'][0]['data']) > 50)
		{
		$this->cover_data=$info['comments']['picture'][0]['data'];
		$this->cover_mime=$info['comments']['picture'][0]['image_mime'];
		// Check if mime type is correct. Sometimes, it is empty (with bmp, for
		// instance). There, we try to convert the image to jpeg and set the object
		// as 'modified'. If Image::mk_image() fails, it throws an exception and the song
		// is ignored.
		if ($this->cover_mime==='')
			{
			$this->trace('Image data size: '.strlen($this->cover_data));
			$this->trace('Converting invalid image type to jpeg');
			$img=Image::mk_image($this->cover_data);
			Image::image_to_jpeg($img,$this->cover_data,$this->cover_mime);
			$this->setModified();
			}
		}
	else // Invalid picture - must be cleared
		{
		$this->setModified();
		}
	}
}

//------

public function has_cover()
{
return (!is_null($this->cover_data));
}

//------

public static function is_a_song($path)
{
return (strtolower(Phool\File::fileSuffix($path))==='mp3');
}

//------

public static function init_id3()
{
if (is_null(self::$id3))
	{
	self::$id3=new getID3;
	self::$id3->encoding='UTF-8';
	}
}

//------

public static function analyze($path)
{
self::init_id3();
$data = self::$id3->analyze($path);
unset($data['tags']['id3v2']['totaltracks']);
//var_dump($data);
return $data;
}

//------

private static function audio_data_from_info($info)
{
if (is_array($info) && isset($info['avdataend']) && isset($info['avdataoffset']))
	{
	$offset=$info['avdataoffset'];
	$size=$info['avdataend']-$info['avdataoffset'];
	}
else
	{
	$offset=$size=0;
	}

return array($offset,$size);
}

//------
// Return offset and length of audio data

public static function get_audio_data_info($path)
{
$info = self::analyze($path);
return self::audio_data_from_info($info);
}

//------

public static function get_info_from_path($path)
{
$info = self::analyze($path);
getid3_lib::CopyTagsToComments($info);
return $info;
}

//------

public function get_info()
{
return self::get_info_from_path($this->path());
}
 
//------

private function get_property($info,$a,$prop,$def)
{
$this->$prop=isset($info[$a][$prop][0]) ? $info[$a][$prop][0] : $def;
}

//------

public function save()
{
if (!$this->modified()) return false;
$this->write();
return true;
}

//------

public function write()
{
$this->debug('Writing tags');

//-- Create writer obj

$writer=new \getid3_writetags;
$writer->overwrite_tags=true;
$writer->tag_encoding='UTF-8';
$writer->tagformats = array('id3v1', 'id3v2.3'); 
$writer->remove_other_tags=false;
$writer->filename=$this->path();

//-- Create tag data

$data=array();

foreach($GLOBALS['props'] as $prop => $def)
	{
	$data[$prop]=array(0 => $this->$prop);
	}

//-- Set track nb to v2 format

$data['track'][0] = min ($data['track'][0], $this->album_obj->track_count());
$data['track'][0].='/'.$this->album_obj->track_count();

//-- Integrate mcomments into the comment tag

$comment=$this->comment;
if (count($this->mcomments))
	{
	foreach($this->mcomments as $mtag => $mvalue)
		{
		if ($comment!='') $comment.="\r\n";
		$comment.=MCOMMENT_TAG.$mtag;
		if (!is_null($mvalue)) $comment .= ':'.$mvalue;
		}
	$data['comment'][0]=$comment;
	}

//-- Fix album name (pb with empty names)

if ($data['album'][0]==='') $data['album'][0]=NO_ALBUM;

//-- Incorporate saved ID3V2 additional tags

$data=array_merge($data,$this->other_tags);

//-- Workaround buggy getid3 lib, where read and write tag names are inconsistent
//-- Also allows to filter some problematic tags out

foreach(array_keys($data) as $key)
	{
	if (array_key_exists($key,$GLOBALS['tag_translation']))
		{
		$new_key=$GLOBALS['tag_translation'][$key];
		if (!is_null($new_key)) $data[$new_key]=$data[$key];
		unset($data[$key]);
		}
	}

//-- Set cover image

if (!is_null($this->cover_data))
	{
	$data['attached_picture']=array(0 => array(
		'data' => $this->cover_data,
		'picturetypeid' => 3, // Front cover
		'description' => '',
		'mime' => $this->cover_mime));
	}

//-- Write new tags

// var_dump($data['track'][0]);
$writer->tag_data=$data;
if ($GLOBALS['do_changes'])
	{
	$mtime=filemtime($writer->filename);
	$writer->WriteTags();
	// Incr mtime for sync utilities (size may remain the same). Need to add 3 sec
	// because FAT precision is 2 sec, so most sync software have a tolerance of 2 sec.
	touch($writer->filename,$mtime+3);
	if (!empty($writer->errors))
		{
		Phool\Display::error($this->relpath().': Errors writing tags: '
			.implode("\n",array_unique($writer->errors)));
		}
	if (!empty($writer->warnings))
		{
		Phool\Display::warning($this->relpath().': Warnings writing tags: '
			.implode("\n",array_unique($writer->warnings)));
		}
	}
$this->clearModified();
unset($writer);
}

//------

private function set_property($prop,$value)
{
if ($value == $this->$prop) return;
$this->trace('Setting '.$prop.': <'.$this->$prop.'> to <'.$value.'>');
$this->$prop=$value;
$this->setModified();
}

//------

public function trace($msg)
{
Phool\Display::trace($this->relpath().': '.$msg);
}

//------

public function debug($msg)
{
Phool\Display::debug($this->relpath().': '.$msg);
}

//------

public function set_title($value) { $this->set_property('title',$value); }
public function set_artist($value) { $this->set_property('artist',$value); }
public function set_album($value) { $this->set_property('album',$value); }
public function set_year($value) { $this->set_property('year',$value); }
public function set_comment($value) { $this->set_property('comment',$value); }
public function set_genre($value) { $this->set_property('genre',$value); }
public function set_track($value) { $this->set_property('track',$value); }

//------

public function rename($new_fname)
{
$this->trace('Renaming to <'.$new_fname.'>');

if ($GLOBALS['do_changes'])
	{
	rename($this->path(),$this->path($new_fname));
	}
$this->fname=$new_fname;
}

//------

public function set_mcomment($mname,$mvalue=null)
{
if ((!array_key_exists($mname,$this->mcomments))||($this->mcomments[$mname]!==$mvalue))
	{
	$this->trace('Setting mcomment: '.$mname);
	$this->mcomments[$mname]=$mvalue;
	$this->setModified();
	}
}

//------

public function unset_mcomment($mname)
{
if (array_key_exists($mname,$this->mcomments))
	{
	$this->trace('Clearing mcomment: '.$mname);
	unset($this->mcomments[$mname]);
	$this->setModified();
	}
}

//------

public function get_mcomment($name)
{
return ($this->isset_mcomment($name) ? $this->mcomments[$name] : null);
}

//------

public function isset_mcomment($name)
{
return array_key_exists($name,$this->mcomments);
}

//------

public function fix()
{
$rc=false;

while (1)
	{
	if (!$this->do_fix()) break;
	$rc=true;
	}
return $rc;
}

//------

public function do_fix()
{
//-- Suppress tags marked for suppression

foreach($GLOBALS['tag_translation'] as $tag => $target)
	{
	if ((is_null($target))&&(isset($this->other_tags[$tag][0])))
		{
		$this->trace("Found tag to remove : $tag");
		clear_key($this->other_tags,$tag); // not mandatory, but cleaner
		$this->setModified();
		}
	}

//-- Fix filename

$ufname=fname_to_utf($this->fname);
$dotpos=strrpos($ufname,'.');
$ext=Phool\File::fileSuffix($ufname);
$fbase=substr($ufname,0,$dotpos);

$fbase=fix_spaces($fbase);

$prefix='';
$a=array();
if (preg_match('/^(\d\d?)\s*[\-_\.]\s*(\S.*)$/u',$fbase,$a))
	{
	$prefix=$a[1].' - ';
	$fbase=btrim($a[2]);
	}

suppress_prefix_suffix($fbase,$this->album_obj->artist->name);
suppress_prefix_suffix($fbase,$this->artist);

suppress_prefix_suffix($fbase,$this->album_obj->name);
suppress_prefix_suffix($fbase,$this->album);

$a=array();
if (preg_match('/^(.*\S)\s*\((\d\d\d\d)\)$/u',$fbase,$a))
	{
	$fbase=btrim($a[1]);
	$this->set_year($a[2]);
	$this->unset_mcomment('DEFAULT_YEAR');
	}

// If year is not set, try to get album's default year

if (($this->year=='')&&($this->album_obj->name!='')
	&&($this->album_obj->default_year!=''))
	{
	$this->set_mcomment('DEFAULT_YEAR');
	$this->set_year($this->album_obj->default_year);
	}

// In an artist bundle, try to prefix file name with artist name if set in previous tags

if ($this->album_obj->artist->is_multi())
	{
	$orig_artist=$this->artist;
	if (($orig_artist=='')||(artist_string_is_multi($orig_artist)))
		$orig_artist=$this->get_mcomment('ORIG_ARTIST');
	if (($orig_artist=='')||(artist_string_is_multi($orig_artist))
		||(starts_with($orig_artist,'Divers '))
		||(starts_with($orig_artist,'Various '))
			) $orig_artist=null;
	if (!is_null($orig_artist))
		{
		$orig_artist=normalize_string($orig_artist);
		if ((strlen($orig_artist)>4)&&(strcasecmp(substr($orig_artist,0,4),'the ')==0))
			{
			$a2=$orig_artist;
			$orig_artist=substr($orig_artist,4);
			}
		else
			{
			$a2='the '.$orig_artist;
			}
		suppress_prefix_suffix($fbase,$orig_artist);
		suppress_prefix_suffix($fbase,$a2);
		$p1=$orig_artist.' - ';
		$fbase=add_prefix($fbase,$p1);
		$p2=$a2.' - ';
		$tmp_title=$this->title;
		suppress_prefix_suffix($tmp_title,$a2);
		if ($tmp_title!=$this->title) $this->set_title($tmp_title);
		$this->set_title(add_prefix($this->title,$p1));
			
		}
	}

$ftitle=ucfirst($fbase);
$fbase=$prefix.$fbase;
$ftrack=0;
$a=array();
if (preg_match('/^(\d\d?)\s*[\-_\.]\s*(\S.*)$/u',$fbase,$a)
	||preg_match('/^(\d\d)\s+(\S.*)$/u',$fbase,$a))
	{
	$ftrack=intval($a[1]);
	$ftitle=btrim($a[2]);
	$fbase=substr($ftrack+100,-2).' - '.$ftitle;
	}

$fbase=str_replace('/',',',$fbase);
$fbase=str_replace('\\',',',$fbase);
$fbase=fix_spaces(str_replace('_',' ',$fbase));
$ftitle=fix_spaces(str_replace('_',' ',$ftitle));

$nname=utf_to_fname($fbase.'.'.$ext);
if ($nname !== $this->fname) $this->rename($nname);

//-- Artist

$artist_name=$this->album_obj->artist->name;
if ((!$this->isset_mcomment('ORIG_ARTIST'))&&($this->artist!='')
	&&($this->artist!=$artist_name))
	{
	$this->set_mcomment('ORIG_ARTIST:'.$this->artist);
	}
$this->set_artist($artist_name);

//-- Album

$album_name=$this->album_obj->name;
if ((!$this->isset_mcomment('ORIG_ALBUM'))&&($this->album!='')
	&&($this->album!=$album_name))
	{
	$this->set_mcomment('ORIG_ALBUM:'.$this->album);
	}
$this->set_album($album_name);

//-- Fix cover
// We know that the album cover image was normalized. So, can be used as-is.
// If album cover set and song cover unset or different, set album cover
// If album cover unset and song cover set, normalize image

if (($this->album_obj->has_cover())
	&& (is_null($this->cover_data)
		||($this->cover_data!==$this->album_obj->cover_data)))
	{
	$this->trace('Setting album cover');
	$this->cover_data=$this->album_obj->cover_data;
	$this->cover_mime=$this->album_obj->cover_mime;
	$this->setModified();
	}
else if ((!$this->album_obj->has_cover())&&(!is_null($this->cover_data)))
	{
	try
		{
		$img=Image::mk_image($this->cover_data);
		if (Image::normalize($img))
			{
			$this->debug('Normalizing cover image');
			Image::image_to_jpeg($img,$this->cover_data,$this->cover_mime);
			$this->setModified();
			}
		unset($img);
		}
	catch (Exception $e) {}
	}

//-- Title

if ((btrim($this->title)=='')
	||(preg_match('/^track +\d+ *$/ui',$this->title))
	||($this->isset_mcomment('TITLE_FNAME')))
	{
	$this->set_mcomment('TITLE_FNAME');
	$this->set_title($ftitle);
	}

//-- Fix track nb
// First, try to get track num from filename
// Last resort, take dir alpha order

if ($ftrack)
	{
	$this->set_mcomment('AUTOTRACK','fname');
	$this->set_track($ftrack);
	}
elseif (($this->track==0)||($this->isset_mcomment('AUTOTRACK')))
	{
	$this->set_mcomment('AUTOTRACK','dir_order');
	$this->set_track($this->album_obj->dir_order($this));
	}

//-- Save changes

return $this->save();
}

//------

public function check()
{
//-- Check if bitrate more than reference

if (($GLOBALS['max_bitrate']!=0)&&($this->bitrate>$GLOBALS['max_bitrate']))
	{
	Phool\Display::info($this->relpath().': Excessive bitrate ('.$this->bitrate.')');
	if (!is_null($GLOBALS['output_file']))
		{
		file_put_contents($GLOBALS['output_file'],$this->path()."\n",FILE_APPEND);
		}
	}
}

//------
// Compute size that could be spared if quality set to max_bitrate

public function to_spare()
{
if (($GLOBALS['max_bitrate'])&&($this->bitrate>$GLOBALS['max_bitrate']))
	{
	$res=intval((($this->bitrate-$GLOBALS['max_bitrate'])*$this->audio_size)/$this->bitrate);
	Phool\Display::debug($this->relpath().': can be reduced by '.$res.' bytes');
	}
else $res=0;

return $res;
}

//------

public static function sort_callbak($song1,$song2)
{
return strcasecmp($song1->album_rel_path(),$song2->album_rel_path());
}

//------

public function album_rel_path()
{
return Phool\File::combinePath($this->dir,$this->fname);
}

//------

public function path($fname=null)
{
return Phool\File::combinePath($this->album_obj->path($this->dir)
	,(is_null($fname)?$this->fname:$fname));
}

//------

public function relpath($fname=null)
{
return Phool\File::combinePath($this->album_obj->relpath($this->dir)
	,(is_null($fname)?$this->fname:$fname));
}

//------
} // End of class
?>