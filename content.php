<?php
/**
 * @file
 * Sisällön valitseminen
 *
 * TAMKin oppimisympäristö, Ainopankki
 * content.php
 * Author: Annika Granlund, Jarmo Kortetjärvi
 * Created: 21.04.2010
 * Modified: 23.08.2010
 *
 */

echo '<div id="content">';

	if (isset($_GET[ 'sivu' ])) {
            $sivu = "./API/";
 		 $sivu .= $_GET[ 'sivu' ] . '.php';
		if (file_exists($sivu)) {
			require_once $sivu;
		} else {
			require_once 'virhe.php';
		}
	} else {
		require_once './API/login.php';
	}
?>

</div><!-- /content -->