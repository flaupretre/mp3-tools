<?php

class Artist
{

public $path;
public $name;	// UTF
public $dir; // Dir entry

public $albums;
public $root_album;

//------

public function __construct($path)
{
$this->path=$path;
$this->dir=basename($path);
$this->name=fname_to_utf($this->dir);

Phool\Display::trace("Creating artist: ".$this->name);

//---- Populate

$this->albums=array();

$this->root_album=$this->get_album(''); // Root album
foreach (get_subdirs($this->path()) as $adir) $this->get_album($adir);
}

//------

private function get_album($dir)
{
$album=null;
try
	{
	Phool\Display::debug("Artist: '".$this->name."' - Album dir: '".$dir."'");
	$album=$this->albums[]=new Album($this,$dir);
	}
catch (Exception $e)
	{
	$msg=$e->getMessage();
	if (($msg!='')&&($dir!='')) Phool\Display::warning($this->name.': Ignoring album <'.$dir
		.'> - Reason: '.$msg);
	}
return $album;
}

//------

public function is_multi()
{
return artist_string_is_multi($this->name);
}

//------

public function fix()
{
Phool\Display::debug("Fixing artist: ".$this->name);

// Fix albums

foreach($this->albums as $album) $album->fix();
}

//------

public function check()
{
// Check albums

foreach($this->albums as $album) $album->check();
}

//------

public function to_spare()
{
$res=0;
foreach($this->albums as $album) $res +=$album->to_spare();
return $res;
}

//------

public function path($fname='')
{
return Phool\File::combinePath($this->path,$fname);
}

//------

public function relpath($fname='')
{
return Phool\File::combinePath($this->dir,$fname);
}

//------
} // End of class
?>