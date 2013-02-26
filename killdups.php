<?php

require('lib/mp3lib.php');

//----------------------------------

get_options();

$ref_path=array_shift($argv);
$check_path=array_shift($argv);

PHO_Display::trace('Reading ref tree');

$r_arbo=new CRC_Arbo($ref_path);

PHO_Display::trace('Reading check tree');

$c_arbo=new CRC_Arbo($check_path);

foreach($c_arbo->get_files() as $cid => $cfile)
	{
	$bname=strtolower(basename($cfile->path()));
	if (($bname==='desktop.ini')||($bname==='thumbs.db'))
		{
		$c_arbo->delete($cid);
		continue;
		}
	$tfile=$r_arbo->find($cfile);
	if ($tfile !== false)
		{
		PHO_Display::trace("* Match: \n	".$cfile->path()."\n	".$tfile->path());
		$c_arbo->delete($cid);
		}
	}

// Remove empty dirs
PHO_Display::trace('Removing empty directories');

$c_arbo->remove_empty_dirs();

//$c_arbo->display();

?>