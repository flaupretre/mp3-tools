<?php

require('lib/mp3lib.php');

//----------------------------------

get_options();

$path=array_shift($argv);

PHO_Display::trace('Reading tree');

$arbo=new CRC_Arbo($path);

$dups=$arbo->find_dups();

PHO_Display::trace('Analyzing');

foreach($dups as $dup)
	{
	echo "------------------------------------------------\n";
	foreach ($dup as $f)
		{
		$f->display();
		}
	}

?>