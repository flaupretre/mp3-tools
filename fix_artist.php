<?php

require('lib/mp3lib.php');

//----------------------------------

get_options();

$path=array_shift($argv);
$artist=(count($argv)>0) ? array_shift($argv) : basename($path);

$artist=new Artist($path,$artist);

$artist->fix();

?>