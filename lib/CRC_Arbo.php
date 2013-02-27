<?php
//----------------------------------

class CRC_Arbo
{
private $base_path;
private $files;
private $hash_table;

//----

public function __construct($path)
{
$this->base_path=$path;
$this->files=array();
$this->hash_table=array();

$this->get_dir($path);
//ksort($this->hash_table); // Trie par taille
}

//----

public function get_files()
{
return $this->files;
}

//----

private function get_dir($path)
{
PHO_Display::trace('Getting dir '.$path);

$entries=PHO_File::scandir($path);
sort($entries);
foreach($entries as $entry)
	{
	$epath=PHO_File::combine_path($path,$entry);
	switch(filetype($epath))
		{
		case 'dir':
			$this->get_dir($epath);
			break;
		case 'file':
			$id=count($this->files);
			$file=new CRC_File($epath,$id);
			$this->files[$id]=$file;
			$key=$file->hash_key();
			if (!array_key_exists($key,$this->hash_table)) $this->hash_table[$key]=array();
			$this->hash_table[$key][]=$file;
			break;
		}
	}
}

//----

public function remove_empty_dirs()
{
$this->remove_empty_dirs_rec($this->base_path);
}

//----

public function remove_empty_dirs_rec($path)
{
$status=true;
foreach(PHO_File::scandir($path) as $entry)
	{
	$epath=PHO_File::combine_path($path,$entry);
	if ((filetype($epath)!=='dir')||(!$this->remove_empty_dirs_rec($epath))) $status=false;
	}
if ($status)
	{
	PHO_Display::trace('Removing empty dir: '.$path);
	if ($GLOBALS['do_changes']) rmdir($path);
	}
return $status;
}

//----

public function find($rfile)
{
$key=$rfile->hash_key();
if (!array_key_exists($key,$this->hash_table)) return false;
$crc=$rfile->crc();
foreach($this->hash_table[$key] as $cfile)
	{
	if ($cfile->crc()===$crc) return $cfile;
	}
return false;
}

//----

public function find_dups()
{
$dups=array();

PHO_Display::trace('Starting analyzis: File count = '
	.count($this->files).' - Hash table size = '.count($this->hash_table));
foreach ($this->hash_table as $hash => $files)
	{
	if (count($files) < 2) continue;
	$d=array();
	PHO_Display::debug('Analyzing; Size='.$hash.' ; nb='.count($files));
	foreach($files as $file)
		{
		$crc=$file->crc();
		if (!array_key_exists($crc,$d)) $d[$crc]=array();
		$d[$crc][]=$file;
		}
	foreach($d as $a)
		{
		if (count($a) > 1) $dups[]=$a;
		}
	}

return $dups;
}

//----

public function delete($file)
{
$id=$file->id();
$file->delete();
unset($this->files[$id]);
$key=$file->hash_key();
$ba=$this->hash_table[$key];
foreach(array_keys($ba) as $ind)
	{
	if ($ba[$ind]===$file)
		{
		unset($ba[$ind]);
		if (empty($ba))
			{
			unset($this->hash_table[$key]);
			}
		break;
		}
	}
}

//----

public function display()
{
foreach($this->files as $file) $file->display();
}

}

//----------------------------------
?>