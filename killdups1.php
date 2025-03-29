<?php

require('lib/mp3lib.php');

//----------------------------------

get_options();

$path=array_shift($argv);

Phool\Display::trace('Reading tree');

$arbo=new CRC_Arbo($path);

$dups=$arbo->find_dups();

Phool\Display::trace('Analyzing');

foreach($dups as $dup)
	{
	echo "------------------------------------------------\n";
	foreach ($dup as $f)
		{
		$f->display();
		}

	// Supprime les images en double (type AlbumArt_{...)
	
	$all_images=true;
	$dir=null;
	$same_dir=true;
	$shortest=null;
	$shortest_len='';
	foreach ($dup as $n => $f)
		{
		if (!Image::is_image_path($f->path())) $all_images=false;
		$path=$f->path();
		$d=dirname($path);
		if (is_null($dir)) $dir=$d;
		if ($d !== $dir) $same_dir=false;
		$b=basename($path);
		$len=strlen($b);
		if ((is_null($shortest))||($len<$shortest_len))
			{
			$shortest=$n;
			$shortest_len=$len;
			}
		}
	if ($all_images && $same_dir)
		{
		foreach ($dup as $n => $f)
			{
			if ($n!=$shortest) $arbo->delete($f);
			}
		}
	}

?>