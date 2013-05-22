<?php
/**
 * @file
 * Pankkiaineiston luonti
 *
 * TAMKin oppimisympäristö, Ainopankki
 * teePankkiaineistot.php
 * Annika Granlund, Jarmo Kortetjärvi
 * Created: 29.09.2010
 * Modified: 2010-01-27
 *
 */

ini_set("include_path", "../ainopankki/".PATH_SEPARATOR.ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");
chdir('/var/www/html/ainopankki/');

require_once 'functions.php';
require_once 'konekielinenTiliote.php';
require_once 'saapuvatViitemaksut.php';

databaseConnect();


// Haetaan tietokannasta yrityksien tiedot
$query = "	SELECT 	*
			FROM 	TAMK_pankkitili ";
			
$result = mysql_query($query);

while($tunnusrow = mysql_fetch_assoc($result)){
	
	teeKonekielinenTiliote($tunnusrow, 1);
	teeSaapuvatViitemaksut($tunnusrow, 1);
}

?>