<?php

require('lib/mp3lib.php');

//----------------------------------

function restore_lib($path)
{
if (file_exists($path))
	{
	return unserialize(file_get_contents($path));
	}
else return false;
}

//----------------------------------

get_options();

//if (($lib=restore_lib(LIB_DUMP))===false)
//	{

$path=array_shift($argv);
$lib=new Library($path);

//	$lib->dump(LIB_DUMP);
//	}

$lib->fix();
//$lib->dump(LIB_DUMP);


//$lib->check();

?>