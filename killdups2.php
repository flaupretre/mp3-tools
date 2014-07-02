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

foreach($c_arbo->get_files() as $cfile)
	{
	$bname=strtolower(basename($cfile->path()));
	if (($bname==='desktop.ini')||($bname==='thumbs.db'))
		{
		if (!$GLOBALS['inverse']) $c_arbo->delete($cfile);
		continue;
		}
	$tfile=$r_arbo->find($cfile);
	if ($tfile === false)
		{
		if ($GLOBALS['inverse'])
			{
			PHO_Display::msg("Not found: ".$cfile->path());
			}
		}
	else
		{
		if (!$GLOBALS['inverse'])
			{
			PHO_Display::msg("* Match: \n	".$cfile->path()."\n	".$tfile->path());
			$c_arbo->delete($cfile);
			}
		}
	}

// Remove empty dirs

if (!$GLOBALS['inverse'])
	{
	PHO_Display::trace('Removing empty directories');
	$c_arbo->remove_empty_dirs();
	}

//$c_arbo->display();

?>