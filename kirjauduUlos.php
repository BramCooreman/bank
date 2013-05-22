<?php
/**
 * @file
 * Uloskirjautuminen
 *
 * TAMKin oppimisympäristö, Ainopankki
 * kirjauduUlos.php
 * Annika Granlund, Jarmo Kortetjärvi
 * Created: 28.05.2010
 * Modified: 23.08.2010
 *
 */
	require_once 'functions.php';
	session_destroy();
	echo "<p>".localize('Olet kirjautunut ulos pankkipalvelusta')."</p>";
?>