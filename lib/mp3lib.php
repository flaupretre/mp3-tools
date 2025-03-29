<?php

//if (!extension_loaded('ktaglib')) dl('ktaglib.so');

require(dirname(__FILE__).'/../../getID3/getid3/getid3.php');
require(dirname(__FILE__).'/../../getID3/getid3/write.php');


require(dirname(__FILE__).'/../../phool/src/Phool/Display.php');
require(dirname(__FILE__).'/../../phool/src/Phool/File.php');
require(dirname(__FILE__).'/../../phool/src/Phool/Modifiable.php');
require(dirname(__FILE__).'/../../phool/src/Phool/Util.php');
require(dirname(__FILE__).'/../../phool/src/Phool/Options/Base.php');
require(dirname(__FILE__).'/../../phool/src/Phool/Options/Dummy.php');
require(dirname(__FILE__).'/../../phool/src/Phool/Options/Getopt.php');

//---------------

require(dirname(__FILE__).'/globals.php');
require(dirname(__FILE__).'/util.php');

require(dirname(__FILE__).'/Library.php');
require(dirname(__FILE__).'/Artist.php');
require(dirname(__FILE__).'/Album.php');
require(dirname(__FILE__).'/Song.php');
require(dirname(__FILE__).'/Image.php');

//---------------
// Devrait aller ailleurs mais depend de mp3lib

require(dirname(__FILE__).'/CRC_Arbo.php');
require(dirname(__FILE__).'/CRC_File.php');

?>
