<?php
/**
 * @file
 * Hyv'ksytään rahan siirtosuoritus
 *
 * TAMKin oppimisympäristö, Ainopankki
 * hyvaksySiirto.php
 * Author: Jani Keränen
 * Created: 01.08.2012
 * Modified: 01.08.2012
 *
 */

	require_once 'functions.php';
	databaseConnect();
	
	// Muuttuja, joka tallennetaan tiedostoon
	$luonti = getdate();
	
	// Muuttujat
	$maksupvm = $_POST[ 'maksupvm' ];				/**< Maksun päivämäärä */
	$saajanNimi = $_POST[ 'saajanNimi' ];			/**< Maksun saajan nimi*/
	$saaja = $_POST[ 'saajanTili' ];				/**< Maksun saajan tilinumero */
	$viite = $_POST[ 'viite' ];						/**< Maksun viitenumero */
	$summa = $_POST[ 'summa' ];						/**< Maksusumma */
	$arkistotunnus = getArchiveReferenceNumber();	/**< Maksun arkistointitunnus */
	
	// Tarkistetaan tilinumero
	if (empty($saaja)) {
		$errorText = localize('Tilinumero on virheellinen, tyhjä.');
	} 
	
	// Tarkistetaan, että saajan tilinumero alkaa FI:llä
	if (substr($saaja, 0, 2) == 'FI') {
	
		$query = "SELECT	omistaja
					FROM	TAMK_pankkitili 
					WHERE	yhtio = 'pankk'
					AND		tilinro = '$saaja' 
					";
		
		$result = mysql_query($query);
		
		// Jos tietoja ei löydy, tilinumero on virheellinen
		if (mysql_num_rows($result) == 0) {
			$errorText = localize('Tilinumero on virheellinen.');
		} else {
			if (empty($saajanNimi)) {
				$row = mysql_fetch_array($result);
				$saajanNimi = $row[ 'omistaja' ];
			}
		}
	}
	$viite = $_POST[ 'viite' ];
	$summa = str_replace(',','.',$_POST[ 'summa' ]);
	
	// Maksun summa täytyy olla suurempi kuin 0
	if ($summa <= 0) {
		$errorText = localize('Syötä maksun summa.');
	}
	
	$tapvm = $maksupvm;
	$laatija = $_SESSION[ 'kayttaja' ]; 
	
	$query = 
	"	INSERT INTO TAMK_pankkitapahtuma set 
		yhtio = 'pankk',
		saaja='$saaja',
		saajanNimi = '$saajanNimi',
		maksaja='',
		maksajanNimi = 'Ylläpitäjä',
		summa='$summa',
		tapvm=if('$tapvm' < now(), now(), '$tapvm'),
		kurssi=1,
		valkoodi = 'EUR',
		viite = '$viite',
		selite = 'Ylläpitäjän suorittama rahan siirto',
		arkistotunnus = '$arkistotunnus', 
		laatija='$laatija',
		luontiaika=now()
	";
	
	// Suoritetaan transaktio ja commitataan tietokantaan tai palautetaan ennalleen virhetilanteessa
	try {
		mysql_query("START TRANSACTION");
		$result = mysql_query($query);
		mysql_query("COMMIT");
	}
	catch (Exception $error) {
		mysql_query("ROLLBACK");
	}

	if ($result === true) {
		header("Location: index.php?sivu=maksuSuoritettu");
	} else {
		header("Location: index.php?sivu=errorPage");
	}

?>
