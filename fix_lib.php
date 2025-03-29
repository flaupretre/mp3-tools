<?php

require('lib/mp3lib.php');

//----------------------------------

get_options();

$path=array_shift($argv);

$lib=new Library($path);

$to_spare=0;
foreach ($lib->artists() as $artist_name)
	{
	$artist=$lib->artist($artist_name);
	if (!$GLOBALS['check_only']) $artist->fix();
	$artist->check();
	$to_spare += $artist->to_spare();
	unset($artist);
	}

if ($GLOBALS['max_bitrate'])
	Phool\Display::info('Size to spare: '.intval($to_spare/1048576).' Mo');

?>