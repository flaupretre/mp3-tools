<?php

require('lib/mp3lib.php');

//----------------------------------

get_options();

$path=array_shift($argv);
$artist_name=(count($argv)>0) ? array_shift($argv) : basename($path);

$artist=new Artist($path,$artist_name);
if (!$GLOBALS['check_only']) $artist->fix();
$artist->check();

if ($GLOBALS['max_bitrate'])
	PHO_Display::info('Size to spare: '.intval($artist->to_spare()/1048576).' Mo');

?>