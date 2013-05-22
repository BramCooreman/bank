<?php
/// TAMKin oppimisympäristö
/// notification.php
/// Annika Granlund, Jarmo Kortetjärvi
/// modified: 2010-08-03

/**
 * Dekoodaa ja printtaa TAMKin "varoitustekstin". 
 * Tätä käytetään niissä tiedostoissa, jotka toimivat Pupen kanssa. (koodaus ANSI)
 */
function printNotification() {
	echo utf8_decode('<p class="notification">Tämä on TAMKin oppimisympäristö!</p>');
}

/**
 * Printtaa pelkän TAMKin "varoitustekstin". 
 * Käytetään tiedostoissa, joissa UTF8-koodaus. (nettisivut)
 */
function printNotificationText() {
	print 'Tämä on TAMKin oppimisympäristö';
}
?>
