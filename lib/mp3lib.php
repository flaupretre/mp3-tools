<?php

//if (!extension_loaded('ktaglib')) dl('ktaglib.so');

require(dirname(__FILE__).'/external/getid3.phk');
require(dirname(__FILE__).'/external/phool.phk');

//---------------

require(dirname(__FILE__).'/globals.php');
require(dirname(__FILE__).'/util.php');

require(dirname(__FILE__).'/Library.php');
require(dirname(__FILE__).'/Artist.php');
require(dirname(__FILE__).'/Album.php');
require(dirname(__FILE__).'/Song.php');

//---------------
// Devrait aller ailleurs mais depend de mp3lib

require(dirname(__FILE__).'/CRC_Arbo.php');
require(dirname(__FILE__).'/CRC_File.php');

?>
