<?php
/**
 * @file
 * Hyväksytään maksusuoritus
 *
 * TAMKin oppimisympäristö, Ainopankki
 * hyvaksyMaksu.php
 * Annika Granlund, Jarmo Kortetjärvi
 * Created: 28.05.2010
 * Modified: 23.08.2010
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
	$viesti = $_POST[ 'viesti' ];					/**< Maksun viesti */
	$summa = $_POST[ 'summa' ];						/**< Maksusumma */
	$arkistotunnus = getArchiveReferenceNumber();	/**< Maksun arkistointitunnus */
	
	// Haetaan maksaja tietokannasta, jolloin post-injektio ei vaikuta
	$ytunnus = $_SESSION[ 'ytunnus' ];
	$tiliquery = "SELECT tilinro, omistaja FROM TAMK_pankkitili WHERE ytunnus = '$ytunnus'";
	$tiliresult = mysql_query($tiliquery);
	
	$maksaja = mysql_result( $tiliresult, 0, 'tilinro') ;
	$maksajanNimi = mysql_result( $tiliresult, 0, 'omistaja') ;
	
	// Tarkistetaan tilinumero, Checking account number
	$pankkitili = $saaja;
	require_once '../pupesoft/inc/pankkitilinoikeellisuus.php';
	if (empty($pankkitili)) {
		$errorText = localize('Tilinumero on virheellinen, tyhjä.');
	} else {
		$saaja = $pankkitili;
	}
	
	// Maksaja ja saaja eivät voi olla sama yhtiö
	// The payer and payee can not be the same company
	if ($saaja == $maksaja) {
		$errorText = localize('Et voi maksaa omalle tilillesi.');
	}
	
	// Tarkistetaan, että saajan tilinumero alkaa 9:llä
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
	$viesti = $_POST[ 'viesti' ];
	$summa = str_replace(',','.',$_POST[ 'summa' ]);
	
	// Maksun summa täytyy olla suurempi kuin 0
	if ($summa <= 0) {
		$errorText = localize('Syötä maksun summa.');
	}
	// Jos maksun suoritus tapahtuu tänään, tarkastetaan onko rahaa tarpeeksi laskun suorittamiseen
	if ($maksupvm == date('Y-m-d')) {
		if ($summa > getSaldo($maksaja, $maksupvm)) {
			// Ei tarpeeksi rahaa
			$errorText = localize('Tilin saldo ei riitä maksuun.');
		} else {
			// Tarpeeksi rahaa, ei toimintoja
		}
	}
	
	$tapvm = $maksupvm;
	$laatija = $_SESSION[ 'kayttaja' ]; 
	
	$query = 
	"	INSERT INTO TAMK_pankkitapahtuma set 
		yhtio = 'pankk',
		saaja='$saaja',
		saajanNimi = '$saajanNimi',
		maksaja='$maksaja',
		maksajanNimi = '$maksajanNimi',
		summa='$summa',
		tapvm=if('$tapvm' < now(), now(), '$tapvm'),
		kurssi=1,
		valkoodi = 'EUR',
		viite = '$viite',
		selite = '$viesti',
		arkistotunnus = '$arkistotunnus', 
		laatija='$laatija',
		luontiaika=now()
	";
	
	// Suoritetaan transaktio ja commitataan tietokantaan tai palautetaan ennalleen virhetilanteessa
	// Performed the transaction and the COMMIT or database will be restored error occurs
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