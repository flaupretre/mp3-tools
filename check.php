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

PHO_Display::set_verbose(1);

$lib=new Library($argv[1]);
//$lib=restore_lib(LIB_DUMP);

$titles=array();

foreach ($lib->artists as $artist)
	{
	foreach($artist->albums as $album)
		{
		if ((!$album->is_root())&&(!$album->has_cover()))
			{
			PHO_Display::info($album->relpath().': No cover');
			}
		foreach($album->songs as $song)
			{
			$title=strtolower($song->title);
			if (!array_key_exists($title,$titles)) $titles[$title]=array();
			$titles[$title][]=$song;
			}
		}
	}

foreach($titles as $title => $a)
	{
	if (count($a)>1)
		{
		$show=false;
		foreach($a as $song)
			{
			if ($song->album_obj->is_root())
				{
				$show=true;
				break;
				}
			}
		if ($show)
			{
			PHO_Display::info("====== Title: $title");
			foreach($a as $song) PHO_Display::info(($song->album_obj->is_root() ? '*' : ' ')
				."	".$song->relpath());
			}
		}
	}

?>