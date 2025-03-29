<?php

//-----------------------

function array_find_pattern($a,$pattern)
{
foreach($a as $idx => $value)
	{
	if (preg_match('/'.$pattern.'/',$value)>0) return $idx;
	}
return false;
}

//-----------------------

function get_subdirs($path)
{
$a=array();

foreach(Phool\File::scandir($path) as $entry)
	{
	if (is_dir(Phool\File::combinePath($path,$entry))) $a[]=basename($entry);
	}
natcasesort($a);
return $a;
}

//-----------------------

function add_prefix($string,$prefix)
{
return (starts_with($string,$prefix) ? $string : $prefix.$string);
}
//-----------------------

function starts_with($string,$prefix)
{
$len=strlen($prefix);
return ((strlen($string)>=$len)&&(strcasecmp(substr($string,0,$len),$prefix)==0));
}

//-----------------------

function ends_with($string,$suffix)
{
$len=strlen($suffix);
return ((strlen($string)>=$len)&&(strcasecmp(substr($string,-$len),$suffix)==0));
}

//-----------------------

function btrim($string)
{
return trim($string,BSEPAR);
}

//-----------------------

function fix_spaces($string)
{
return btrim(preg_replace('/\s+/u',' ',$string));
}

//-----------------------

function suppress_prefix_suffix(&$string,$prefix)
{
$rc1=suppress_prefix($string,$prefix);
$rc2=suppress_suffix($string,$prefix);
return ($rc1||$rc2);
}

//-----------------------

function suppress_prefix(&$string,$prefix)
{
$prefix=trim($prefix,BSEPAR);
if ($prefix=='') return $string;
$rc=false;

while(1)
	{
	$string=trim($string,BSEPAR);
	if (suppress_prefix_2($string,$prefix)
		|| suppress_prefix_2($string,str_replace(' ','',$prefix)))
		{
		$rc=true;
		continue;
		}
	break;
	}

return $rc;
}

//-----------------------


function suppress_prefix_2(&$string,$prefix) // private
{
$plen=strlen($prefix);
if (strcasecmp(substr($string,0,$plen),$prefix)==0)
	{
	$str2=trim(substr($string,$plen),BLANKS);
	if (($str2!=='') && (strpos(SEPAR,$str2[0])!==false))
		{
		$str2=trim($str2,BSEPAR);
		if ($str2!=='')
			{
			$string=$str2;
			return true;
			}
		}
	}
return false;
}

//-----------------------

function suppress_suffix(&$string,$suffix)
{
$suffix=trim($suffix,BSEPAR);
if ($suffix=='') return $string;
$rc=false;

while(1)
	{
	$string=trim($string,BSEPAR);
	if (suppress_suffix_2($string,$suffix)
		|| suppress_suffix_2($string,str_replace(' ','',$suffix)))
		{
		$rc=true;
		continue;
		}
	break;
	}

return $rc;
}

//-----------------------


function suppress_suffix_2(&$string,$suffix) // private
{
$plen=strlen($suffix);
if (strcasecmp(substr($string,-$plen),$suffix)==0)
	{
	$str2=trim(substr($string,0,-$plen),BLANKS);
	if (($str2!=='') && (strpos(SEPAR,substr($str2,-1))!==false))
		{
		$str2=trim($str2,BSEPAR);
		if ($str2!=='')
			{
			$string=$str2;
			return true;
			}
		}
	}
return false;
}

//-----------------------

function artist_string_is_multi($string)
{
if ($string=='') return false;
return ($string[0]==='-');
}

//-----------------------

function normalize_string($string)
{
$string=str_replace('/','-',$string);
$string=str_replace('(','',$string);
$string=str_replace(')','-',$string);

return $string;
}

//-----------------------

function clear_key(&$a,$key)
{
if (isset($a[$key])) unset($a[$key]);
}

//-----------------------

function fname_to_utf($fname)
{
if (!strlen($fname)) return '';

//Phool\Display::trace("fname_to_utf: input: $fname - ".bin2hex($fname));

if ($GLOBALS['fname_encoding']=='UTF-8') return $fname;
//return iconv($GLOBALS['fname_encoding'],'UTF-8',$fname);
$res=mb_convert_encoding($fname,'UTF-8',$GLOBALS['fname_encoding']);

//Phool\Display::trace("fname_to_utf: output: $res - ".bin2hex($res));
return $res;
}

//-----------------------

function utf_to_fname($string)
{
if (!strlen($string)) return '';

//Phool\Display::trace("utf_to_fname: input: $string - ".bin2hex($string));

if ($GLOBALS['fname_encoding']=='UTF-8') return $string;
//return iconv('UTF-8',$GLOBALS['fname_encoding'],$string);
$res=mb_convert_encoding($string,$GLOBALS['fname_encoding'],'UTF-8');

//Phool\Display::trace("utf_to_fname: output: $res - ".bin2hex($string));
return $res;
}

//-----------------------

function get_options()
{
$args=Phool\Options\Getopt::readPHPArgv();
array_shift($args);
list($options,$args2)=Phool\Options\Getopt::getopt2($args,'nvr:f:c'
	,array('verbose','noexec','max_bitrate','output_file','check_only'));

foreach($options as $option)
	{
	list($opt,$arg)=$option;
	switch($opt)
		{
		case 'v':
		case '--verbose':
			Phool\Display::incVerbose();
			break;

		case 'n':
		case '--noexec':
			$GLOBALS['do_changes']=false;
			break;

		case 'r':
		case '--max_bitrate':
			$GLOBALS['max_bitrate']=intval($arg);
			break;

		case 'f':
		case '--output_file':
			$GLOBALS['output_file']=$arg;
			break;

		case 'c':
		case '--check_only':
			$GLOBALS['check_only']=true;
			break;

		}
	}
$GLOBALS['argv']=$args2;
}

//-----------------------
?>