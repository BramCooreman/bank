<?php
/**
 * @file
 * Saapuvat viitemaksut
 *
 * TAMKin oppimisympäristö, Ainopankki
 * saapuvatViitemaksut.php
 * Author: Annika Granlund, Jarmo Kortetjärvi
 * Created: 06.07.2010
 * Modified: 2010-01-27
 *
 */
 
/**
 * @todo tarkitettava, että käyttäjällä on oikeus session yhtiöön!!
 *   $ytunnus = $_SESSION[ 'ytunnus' ];
 *   databaseConnect();
 */

/**
 * Haetaan ja muodostetaan saapuvien viitemaksujen tietueet
 * Searching and formed incoming reference payments records
 * @param $pankkitilirow
 *   Yksi rivi, joka sisältää pankkitilin tiedot
 *
 * @param $export
 *   Alkuarvo FALSE
 *	 Jos TRUE, tietueet tallennetaan erilliseen tiedostoon, mutta koska $export aina FALSE niin ei tapahdu
 */
function teeSaapuvatViitemaksut($pankkitilirow, $export = false){
	$tilinro = $pankkitilirow[ 'tilinro' ]; /**< Pankkitilin numero */
	
	// Jos 'ytunnus' löytyy
	if ( ! empty($tilinro) ) {
		$ytunnus = $pankkitilirow[ 'ytunnus' ]; /**< Yrityksen numero */
		
		$tapKpl = 0;		/**< Pankkitapahtumien määrä */
		$tapMaara = 0.0;	/**< Pankkitapahtumien rahamäärien summa */
	
		// Post structure
		$eraTietue = "0";										// 01 Tietuetunnus
		$eraTietue .= date('ymdHi');							// 02 Aineiston luontipäivä 03 luontiaika
		$eraTietue .= "9 ";										// 04 rahalaitostunnus
		$eraTietue .= sprintf('%09d', $ytunnus);				// 05 laskuttajan palvelutunnus 
		$eraTietue .= "1";										// 06 rahayksikön koodi
		$eraTietue .= sprintf('%67.67s', "");					// 07 varalla
		$eraTietue .= "\n";
		
		$query = "	LOCK TABLES TAMK_pankkitapahtuma WRITE";
		mysql_query($query);
			
		$query = "	SELECT	*
					FROM	TAMK_pankkitapahtuma
					WHERE	saaja = '$tilinro'
					AND		tapvm <= now()
					AND		viite <> ''
					AND		viiteAineistoHaettu IS NULL
					AND		(eiVaikutaSaldoon = ''
							OR eiVaikutaSaldoon IS NULL
							OR eiVaikutaSaldoon = 'l'
							OR eiVaikutaSaldoon = 'a'
							OR eiVaikutaSaldoon = 'k'
							OR eiVaikutaSaldoon = 'm')
				";
		
		$resultSet = mysql_query($query);
		
		$tapTietue = ""; /**< Tapahtumatietue, alustetaan tyhjäksi aluksi */
		
		// Muodostetaan tapahtumatietue pankkitapahtumista
		while ($tapahtumarow = mysql_fetch_assoc($resultSet)) {

			$saajanTili = $tapahtumarow[ 'saaja' ]; 	/**< Pankkitapahtuman saajan tilinumero */
			$tapvm = $tapahtumarow[ 'tapvm' ]; 			/**< Pankkitapahtuman päivämäärä, muotoa VVVV-KK-PP */
			$tapvm = date('ymd', strtotime($tapvm));	/**< Pankkitapahtuman päivämäärä, muotoa VVKKPP */
			
			$arkistointitunnus = $tapahtumarow[ 'arkistotunnus' ];
			$viite = $tapahtumarow[ 'viite' ];
			$maksajanNimi = $tapahtumarow[ 'maksajanNimi' ];
			$summa = ($tapahtumarow[ 'summa' ] * 100);
			
			// Tapahtumatietue
			$tapTietue .= "3";										// 01 Tietuetunnus
			$tapTietue .= sprintf('%-14.14s', $saajanTili);			// 02 Hyvitetty tili
			$tapTietue .= $tapvm;									// 03 Kirjauspäivä
			$tapTietue .= $tapvm;									// 04 Maksupäivä
			$tapTietue .= sprintf('%-16.16s', $arkistointitunnus);	// 05 Arkistointitunnus
			$tapTietue .= sprintf('%020d', $viite);					// 06 Viite
			$tapTietue .= sprintf('%-12.12s', $maksajanNimi);		// 07 Maksajan nimi
			$tapTietue .= "1";										// 08 Rahayksikön koodi
			$tapTietue .= "J";										// 09 Maksajan nimen lähde (J=järjestelmä)
			$tapTietue .= sprintf('%010d', $summa);					// 10 Rahamäärä
			$tapTietue .= "0";										// 11 Oikaisutunnus
			$tapTietue .= "A";										// 12 Välitystapa
			$tapTietue .= " ";										// 13 Palautekoodi
			$tapTietue .= "\n";
			
			$tapKpl++; 				// Lisätään tapahtumien määrää
			$tapMaara += $summa;	// Lisätään rahasumman määrää
		}
		
		// Summatietue
		$summaTietue = "9";										// 01 Tietuetunnus
		$summaTietue .= sprintf('%06d', $tapKpl);				// 02 Tapahtumien kpl
		$summaTietue .= sprintf('%011d', $tapMaara);			// 03 Tapahtumien määrä 
		$summaTietue .= sprintf('%06d', 0);						// 04 Oikaisutapahtumien kpl
		$summaTietue .= sprintf('%011d', 0);					// 05 Oikaisutapahtumien määrä 
		$summaTietue .= sprintf('%5.5s', "");					// 06 Varalla
		$summaTietue .= sprintf('%50.50s', "");
		$summaTietue .= "\n";
	
		$file = $eraTietue . $tapTietue . $summaTietue;

		if($export = true){
			$fn = "/var/www/html/pupesoft/dataout/viitesiirto_".date('Y-m-d')."_$tilinro";
			$fh = fopen($fn, 'w') or die("can't open file");
			fwrite($fh, $file);
			fclose($fh);
		}
		else{
			// Kerrotaan selaimelle, että tiedosto ja näytetään tietueet
			if (ini_get('zlib.output_compression')) {
				ini_set('zlib.output_compression', 'Off');
			}

			header("Pragma: public");
			header("Expires: 0");
			header("HTTP/1.1 200 OK");
			header("Status: 200 OK");
			header("Accept-Ranges: bytes");
			header("Content-Description: File Transfer");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: private", false);
			header("Content-Transfer-Encoding: binary");

			header("Content-Type: application/force-download");

			header('Content-Disposition: attachment; filename=viiteaineisto.txt');

			header("Content-Length: ".strlen($file));
		
			echo $file;
		}
		
		// Päivitetään tietokantaan viiteaineistoHaettu-kenttään järjestelmän aika
		$query = "	UPDATE  TAMK_pankkitapahtuma
					SET 	viiteaineistoHaettu = now()
					WHERE	saaja = '$tilinro'
					AND		viite <> ''
					AND		viiteAineistoHaettu is null
				";
		mysql_query($query);
		
		$query = "	UNLOCK TABLES";
		mysql_query($query);

	}
}
?>