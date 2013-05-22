<?php
/**
 * @file
 * Maksu suoritettu
 *
 * TAMKin oppimisympäristö, Ainopankki
 * maksuSuoritettu.php
 * Author: Annika Granlund, Jarmo Kortetjärvi
 * Created: 03.08.2010
 * Modified: 23.08.2010
 *
 */
	require_once 'functions.php';
	
	print "
		<p class='paddingBottom'>".localize('Maksu suoritettu.')."</p>
		<p class='link'><a href='index.php?sivu=uusiMaksu'>".localize('Tee uusi maksu')."</a></p>
	";
?>