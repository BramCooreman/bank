<?php
/**
 * @file
 * Ainopankin headeri
 *
 * TAMKin oppimisympäristö, Ainopankki
 * header.php
 * Author: Annika Granlund, Jarmo Kortetjärvi
 * Created: 03.08.2010
 * Modified: 23.08.2010
 *
 */

	// Ainopankki, header
	
	print '<div class="palkki">';
	printNotification();
	print '<small><a href=?lang=fin style="padding-left:25px;"><img src="images/Finland-icon.png" alt="suomi"></a></small>      <small><a href=?lang=en-us style="padding-left:15px;"><img src="images/GreatBritain-icon.png" alt="english"></a></small>';	
	print '</div>
			<img id="ainoLogo" src="images/ainologo.png" alt="Ainopankin logon kuva" />';
?>
