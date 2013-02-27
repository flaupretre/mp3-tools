<?php
//----------------------------------

class CRC_File
{
private $path;
private $crc=null;
private $crc_offset;
private $crc_len; // Length of data for CRC
private $id; // ID dans arbo

//----

public function __construct($path,$id)
{
$this->path=$path;
$this->id=$id;
$this->crc=null;
$this->crc_offset=0;
$this->crc_len=filesize($path); // Default: whole file

// Special case: mp3 files, compute CRC on AV data only

if (Song::is_a_song($path))
	{
	if (($info=Song::get_audio_data_info($path))!==false)
		{
		list($this->crc_offset,$this->crc_len)=$info;
		}
	}
}

//----

public function path()
{
return $this->path;
}

//----

public function id()
{
return $this->id;
}

//----

public function hash_key()
{
return $this->crc_len;
}

//----

public function crc()
{
if (is_null($this->crc))
	{
	$this->crc=$this->crc_len.md5(substr(file_get_contents($this->path),$this->crc_offset,$this->crc_len),true);
	}
return $this->crc;
}

//----

public function delete()
{
PHO_Display::trace('Deleting '.$this->path);
if ($GLOBALS['do_changes']) unlink($this->path);
}

//----

public function display()
{
PHO_Display::info($this->path);
}

}

//----------------------------------
?>