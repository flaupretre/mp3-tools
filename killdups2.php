<?php

require('lib/mp3lib.php');

//----------------------------------

get_options();

$ref_path=array_shift($argv);
$check_path=array_shift($argv);

Phool\Display::trace('Reading ref tree');

$r_arbo=new CRC_Arbo($ref_path);

Phool\Display::trace('Reading check tree');

$c_arbo=new CRC_Arbo($check_path);

foreach($c_arbo->get_files() as $cfile)
	{
	$bname=strtolower(basename($cfile->path()));
	if (($bname==='desktop.ini')||($bname==='thumbs.db'))
		{
		$c_arbo->delete($cfile);
		continue;
		}
	$tfile=$r_arbo->find($cfile);
	if ($tfile !== false)
		{
		Phool\Display::trace("* Match: \n	".$cfile->path()."\n	".$tfile->path());
		$c_arbo->delete($cfile);
		}
	}

// Remove empty dirs
Phool\Display::trace('Removing empty directories');

$c_arbo->remove_empty_dirs();

//$c_arbo->display();

?>