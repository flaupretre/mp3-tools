<?php

class Library
{

public $path;
public $artists;

//------

public function __construct($path)
{
$this->path=$path;

//---- Populate

foreach(get_subdirs($path) as $artist)
	{
	$this->artists[$artist]=new Artist(PHO_File::combine_path($this->path(),$artist),$artist);
	}
}

//------

public function fix()
{
foreach($this->artists as $artist) $artist->fix();
}

//------

public function check()
{
foreach($this->artists as $artist) $artist->check();
}

//------

public function dump($path)
{
file_put_contents($path,serialize($this));
}

//------

public function path()
{
return $this->path;
}

//------
} // End of class
?>