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

PHO_Display::trace("Creating artist: ".$this->name);

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
	PHO_Display::debug("Artist: ".$this->name.' - Album dir: '.$dir);
	$album=$this->albums[]=new Album($this,$dir);
	}
catch (Exception $e)
	{
	$msg=$e->getMessage();
	if (($msg!='')&&($dir!='')) PHO_Display::warning($this->name.': Ignoring album <'.$dir
		.'> - Reason: '.$msg);
	}
return $album;
}

//------

public function is_multi()
{
return ($this->name{0}==='-');
}

//------

public function fix()
{
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
public function path($fname='')
{
return PHO_File::combine_path($this->path,$fname);
}

//------

public function relpath($fname='')
{
return PHO_File::combine_path($this->dir,$fname);
}

//------
} // End of class
?>