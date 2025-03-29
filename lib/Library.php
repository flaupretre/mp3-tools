<?php

class Library
{

private $path;
private $artists; // Aray of artist names

//------

public function __construct($path)
{
$this->path=$path;
foreach(get_subdirs($path) as $name)
	{
	$this->artists[]=$name;
	}
}

//------

public function artists()
{
return $this->artists;
}

//------

public function artist($name)
{
return new Artist(Phool\File::combinePath($this->path(),$name),$name);
}

//------

public function path()
{
return $this->path;
}

//------
} // End of class
?>