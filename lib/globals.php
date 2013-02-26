<?php

define('BLANKS',' 	');
define('SEPAR','-_');
define('BSEPAR',BLANKS.SEPAR);
define('DIGITS','0123456789');
define('NO_ALBUM','<No_Album>');
define('LIB_DUMP','/tmp/lib.dump');
define('IMG_MAX_SIZE',500);
define('MCOMMENT_TAG','[MPTAG]');

//--------------

$GLOBALS['do_changes']=true;

$GLOBALS['fname_encoding']=((DIRECTORY_SEPARATOR==='\\') ? 'Windows-1252' : 'UTF-8');

//--------------

date_default_timezone_set('Europe/Paris'); // Mandatory on Windows
setlocale(LC_ALL,'fr_FR');

?>