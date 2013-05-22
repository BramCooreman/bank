<?php
/**
 * @file
 * Tilitietojen hakeminen
 *
 * TAMKin oppimisympäristö, Ainopankki
 * konekielinenTiliote.php
 * Annika Granlund, Jarmo Kortetjärvi
 * Created: 28.05.2010
 * Modified: 2010-01-27
 *
 */

/**
 * Haetaan tilitiedot ja muodostetaan konekielinen tiliote
 * Retrieving account information and build electronic account statement
 * @param $pankkitilirow
 *   Yksittäinen rivi, joka sisältää pankkitilin tietoja
 *
 * @param $export
 *   Alkuarvo: FALSE
 *   Jos TRUE muodostetut tietueet tallennetaan erilliseen tiedostoon, mutta koska $export aina FALSE, ei koskaan tallenneta
 *   
 */
function teeKonekielinenTiliote($pankkitilirow, $export = false){
	$tilinro = $pankkitilirow[ 'tilinro' ];
	
	// Jos tilinumero $tilinro löytyy
	if ( !empty($tilinro)) {
		
		$tapKpl = 0;
		$tapMaara = 0.0;
		$tilinNimi =  $pankkitilirow['nimi'];

		// Muuttujia tilitietueeseen
		$tiliotteenNro = 1;
		$alkusaldo = 0;

		$query = "	LOCK TABLES TAMK_pankkitapahtuma WRITE";
		mysql_query($query);
		
		$query = "	SELECT	IFNULL( min(tapvm), now() ) AS 'alkupvm'
							, (IFNULL( min(tapvm), now() ) - INTERVAL 1 DAY) AS 'alkusaldopvm'
							, IFNULL(max(tapvm), now() ) AS 'loppupvm'
					FROM	TAMK_pankkitapahtuma
					WHERE	(saaja = '$tilinro'
							AND tilioteHaettuSaaja IS NULL)
					OR 		(maksaja = '$tilinro'
							AND tilioteHaettuMaksaja IS NULL)
					AND		(eiVaikutaSaldoon = ''
							OR eiVaikutaSaldoon IS NULL
							OR eiVaikutaSaldoon = 'l')
				";
		$resultSet = mysql_query($query);

		// Tarkastetaan löytyykö tietokannasta tietoja
		$num_rows = mysql_num_rows($resultSet);
		
		// Jos jotain löytyy
		if ( $num_rows == 1 ) {

			$tilirow = mysql_fetch_assoc($resultSet);
			
			$alkupvm = date('ymd', strtotime($tilirow['alkupvm']));
			$loppupvm = date('ymd', strtotime($tilirow['loppupvm']));
			$alkusaldopvm = date('ymd', strtotime($tilirow['alkusaldopvm']));
			$alkusaldo = getSaldo($tilinro, $tilirow['alkusaldopvm']);
			$lkm = mysql_num_rows($resultSet);

			if($alkusaldo >= 0) $etumerkki = '+';
			else $etumerkki = '-';
			$alkusaldo = abs($alkusaldo);
		} 
		// Jos mitään ei löydy
		else { 
			
			$alkupvm = date('ymd');
			$loppupvm = date('ymd');
			$alkusaldopvm = mktime(0, 0, 0, date("m"), date("d")-1, date("y")); // eilinen
			$alkuSaldopvmSql = date( 'Y-m-d', $alkusaldopvm );
			$alkusaldo = getSaldo($tilinro, $alkuSaldopvmSql);
			$lkm = 0;

			if($alkusaldo >= 0) $etumerkki = '+';
			else $etumerkki = '-';
			$alkusaldo = abs($alkusaldo);
		}
		
		// Tiliotteen tiliotetietue
		$tiliotetietue = "T";											// 01 Aineistotunnus
		$tiliotetietue .= "00";											// 02 Tietuetunnus
		$tiliotetietue .= "188";										// 03 Tietueen pituus
		$tiliotetietue .= "100";										// 04 Versionumero
		$tiliotetietue .= sprintf('%-14.14s', $tilinro);				// 05 Tilinumero
		$tiliotetietue .= sprintf('%03d', $tiliotteenNro);				// 06 Tiliotteen numero
		$tiliotetietue .= $alkupvm;										// 07.1 Tiliotekausi 	alkupäivä (VVKKPP)
		$tiliotetietue .= $loppupvm;									// 07.2 				loppupäivä (VVKKPP)
		$tiliotetietue .= date('ymdHi');								// 08 Muodostamispäivä, kellonaika
		$tiliotetietue .= sprintf('%17.17s', "");						// 09 Asiakastunnus
		$tiliotetietue .= $alkusaldopvm;								// 10 Alkusaldon pvm
		$tiliotetietue .= $etumerkki;									// 11 Tiliotteen alkusaldon etumerkki
		$tiliotetietue .= sprintf('%018d', $alkusaldo*100);				// 11 Tiliotteen alkusaldo
		$tiliotetietue .= sprintf('%06d', $lkm);						// 12 Tiliotteen tietueiden lkm
		$tiliotetietue .= "EUR";										// 13 Tilin valuutan tunnus
		$tiliotetietue .= $tilinNimi;									// 14 Tilin nimi
		$tiliotetietue .= "\n";


		$query = "	SELECT	*
							, IF(maksaja = '$tilinro', summa * -1, summa) AS 'oikeasumma'
							, IF(maksaja = '$tilinro', '2', '1') AS 'tapahtumatyyppi'
							, IF(maksaja = '$tilinro', '702', '710') AS 'kirjausselitteenkoodi'
							, IF(maksaja = '$tilinro', 'otto', 'pano') AS 'kirjausselite'
							, IF(maksaja = '$tilinro',saaja, maksaja) AS 'tilinumero'
							, IF(maksaja = '$tilinro', saajanNimi, maksajanNimi) AS 'nimi'
							, summa*100 AS 'saldo'
					FROM	TAMK_pankkitapahtuma
					WHERE	tapvm <= now()
					AND		(saaja = '$tilinro'
							AND tilioteHaettuSaaja IS NULL
							AND viite IS NULL)
					OR 		(maksaja = '$tilinro'
							AND tilioteHaettuMaksaja IS NULL)
					AND		(eiVaikutaSaldoon = ''
							OR eiVaikutaSaldoon IS NULL
							OR eiVaikutaSaldoon = 'l') 
					ORDER BY tapvm ASC, summa
				";
		$resultSet = mysql_query($query);
	
		// Jos jotain löytyy
		$tapnro = 1;
		$tapahtumatietue = null;
			
		while ($tapahtumarow = mysql_fetch_assoc($resultSet)) {
			$tapvm = date('ymd', strtotime($tapahtumarow['tapvm']));
			$tapahtumatyyppi = $tapahtumarow['tapahtumatyyppi'];
			$kirjausselite = $tapahtumarow['kirjausselite'];
			$kirjausselitteenkoodi = $tapahtumarow[ 'kirjausselitteenkoodi' ];
			$rahamaara = $tapahtumarow['saldo'];
			if($tapahtumatyyppi == 1) $etumerkki = '+';
			else $etumerkki = '-';
			
			// Tapahtumatietue
			$tapahtumatietue .= "T";												// 01 Aineistotunnus
			$tapahtumatietue .= "10";												// 02 Tietuetunnus
			$tapahtumatietue .= "188";												// 03 Tietueen pituus
			$tapahtumatietue .= sprintf('%06d', $tapnro);							// 04 Tapahtuman numero
			$tapahtumatietue .= sprintf('%018s', $tapahtumarow['arkistotunnus']);	// 05 Arkistointitunnus
			$tapahtumatietue .= $tapvm;												// 06 Kirjauspäivä
			$tapahtumatietue .= $tapvm;												// 07 Arvopäivä
			$tapahtumatietue .= $tapvm;												// 08 Maksupäivä
			$tapahtumatietue .= $tapahtumatyyppi;		 							// 09 Tapahtumatunnus
			$tapahtumatietue .= sprintf('%03d', $kirjausselitteenkoodi);			// 10.1 Kirjausselite	koodi
			$tapahtumatietue .= sprintf('%35.35s', $kirjausselite);					// 10.2 				seliteteksti
			$tapahtumatietue .= $etumerkki;											// 11.1 Etumerkki
			$tapahtumatietue .= sprintf('%018d', $rahamaara);			 			// 11.2 Tapahtuman rahamäärä
			$tapahtumatietue .= " ";									 			// 12 Kuittikoodi
			$tapahtumatietue .= "J";									 			// 13 Välitystapa - pankin järjestelmä
			$tapahtumatietue .= sprintf('%35.35s', $tapahtumarow['nimi']);			// 14.1 Saaja/maksaja	nimi
			$tapahtumatietue .= "J";												// 14.2					nimen lähde
			$tapahtumatietue .= sprintf('%-14.14s', $tapahtumarow['tilinumero']);	// 15.1 Saajan tili	tilinumero
			$tapahtumatietue .= " ";												// 15.2 				tili muuttunut -tieto
			$tapahtumatietue .= sprintf('%020d', '0');								// 16 Viite
			$tapahtumatietue .= sprintf('%08.08s', '');								// 17 Lomakkeen numero		
			$tapahtumatietue .= " ";												// 18 Tasotunnus		
			$tapahtumatietue .= "\n";
				
			// Lisätietotietue
			$lisatietotietue  = "T";												// 01 Aineistotunnus
			$lisatietotietue .= "11";												// 02 Tietuetunnus
			$lisatietotietue .= "428";												// 03 Tietueen pituus
			$lisatietotietue .= "00";												// 04 Lisatiedon tyyppi
			$lisatietotietue .= sprintf('%-420s', $tapahtumarow['selite']);			// 05 Lisatieto
			$lisatietotietue .= "\n";
			
			$tapahtumatietue .= $lisatietotietue;

		$tapnro++;
		}

		// Muodostetaan viitekoonti
		$query = "	SELECT	*, COUNT(*) AS 'tapahtumia', summa*100 AS 'saldo'
					FROM	TAMK_pankkitapahtuma
					WHERE	tapvm <= now()
					AND		(saaja = '$tilinro'
							AND tilioteHaettuSaaja IS NULL
							AND viite IS NOT NULL)
					AND		(eiVaikutaSaldoon = ''
							OR eiVaikutaSaldoon IS NULL
							OR eiVaikutaSaldoon = 'l')
					GROUP BY tapvm
					ORDER BY tapvm ASC
				";
		$resultSet = mysql_query($query);
	
		// Jos tietoa löytyy
		while ($tapahtumarow = mysql_fetch_assoc($resultSet)) {
			$tapvm = date('ymd', strtotime($tapahtumarow['tapvm']));
			$rahamaara = $tapahtumarow['saldo'];
			
			// Tapahtumatietue
			$tapahtumatietue .= "T";												// 01 Aineistotunnus
			$tapahtumatietue .= "10";												// 02 Tietuetunnus
			$tapahtumatietue .= "188";												// 03 Tietueen pituus
			$tapahtumatietue .= sprintf('%06d', $tapnro);							// 04 Tapahtuman numero
			$tapahtumatietue .= sprintf('%018d', '');								// 05 Arkistointitunnus
			$tapahtumatietue .= $tapvm;												// 06 Kirjauspäivä
			$tapahtumatietue .= $tapvm;												// 07 Arvopäivä
			$tapahtumatietue .= $tapvm;												// 08 Maksupäivä
			$tapahtumatietue .= "1";		 										// 09 Tapahtumatunnus
			$tapahtumatietue .= "705";												// 10.1 Kirjausselite	koodi
			$tapahtumatietue .= sprintf('%35.35s', "viitepanot");					// 10.2 				seliteteksti
			$tapahtumatietue .= "+";												// 11.1 Etumerkki
			$tapahtumatietue .= sprintf('%018d', $rahamaara);			 			// 11.2 Tapahtuman rahamäärä
			$tapahtumatietue .= "E";									 			// 12 Kuittikoodi
			$tapahtumatietue .= "J";									 			// 13 Välitystapa - pankin järjestelmä
			$tapahtumatietue .= sprintf('%35.35s', '');								// 14.1 Saaja/maksaja	nimi
			$tapahtumatietue .= "J";												// 14.2					nimen lähde
			$tapahtumatietue .= sprintf('%-14.14s', '');							// 15.1 Saajan tili	tilinumero
			$tapahtumatietue .= " ";												// 15.2 				tili muuttunut -tieto
			$tapahtumatietue .= sprintf('%020d', '0');								// 16 Viite
			$tapahtumatietue .= sprintf('%08.08s', '');								// 17 Lomakkeen numero		
			$tapahtumatietue .= " ";												// 18 Tasotunnus		
			$tapahtumatietue .= "\n";

			// Tapahtumatietueen alkuosa
			$tapahtumatietue .= "T";												// 01 Aineistotunnus
			$tapahtumatietue .= "11";												// 02 Tietuetunnus
			$tapahtumatietue .= "016";												// 03 Tietueen pituus
			$tapahtumatietue .= "01";												// 04 Kappalemäärätieto
			$tapahtumatietue .= sprintf('%08d', $tapahtumarow['tapahtumia']);		// 05 Tietueen pituus
			$tapahtumatietue .= "\n";

			$tapnro++;
		}
			
			// Saldotietue
			// Muutujat
			$loppusaldo = getSaldo($tilinro, $tilirow['loppupvm']);
			if($loppusaldo >= 0) $etumerkki = '+';
			else $etumerkki = '-';
			$loppusaldo = abs($loppusaldo);
			
			$saldotietue = "T";												// 01 Aineistotunnus
			$saldotietue .= "40";											// 02 Tietuetunnus
			$saldotietue .= "050";											// 03 Tietueen pituus
			$saldotietue .= $loppupvm;										// 04 Kirjauspäivä
			$saldotietue .= $etumerkki;										// 05 Kirjauspäivän loppusaldo	etumerkki
			$saldotietue .= sprintf('%018d', ($loppusaldo*100));			// 05 							loppusaldo
			$saldotietue .= $etumerkki;										// 06 Käytettävissä oleva saldo	etumerkki
			$saldotietue .= sprintf('%018d', ($loppusaldo*100));			// 06 							loppusaldo
			$saldotietue .= "\n";

		$file = $tiliotetietue . $tapahtumatietue . $saldotietue;
		
		// TÄNNE EI KOSKAAN TULLA
		if($export = true){
			$fn = "/var/www/html/pupesoft/dataout/tiliote_".date('Y-m-d')."_$tilinro";
			$fh = fopen($fn, 'w') or die("can't open file");
			fwrite($fh, $file);
			fclose($fh);
		}
		else{
			// Kerrotaan selaimelle tämän olevan tiedosto ja printataan tietueet
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

			header('Content-Disposition: attachment; filename=tilioteaineisto.txt');

			header("Content-Length: ".strlen($file));
		
			echo $file;
		}

		// Päivitetään tilioteHaettuSaaja ja tilioteHaettuMaksaja -kentät järjestelmän aikaan
		$query = "	UPDATE  TAMK_pankkitapahtuma
					SET 	tilioteHaettuSaaja = now()
							, tilioteHaettuMaksaja = now()
					WHERE	saaja = '$tilinro'
					AND     tapvm<=now()
					AND		( tilioteHaettuMaksaja IS NULL
					OR		tilioteHaettuSaaja IS NULL )
				";
		mysql_query($query);
		
		$query = "	UNLOCK TABLES";
		mysql_query($query);

	}
}
?>
