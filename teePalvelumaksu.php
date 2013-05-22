<?php
/**
 * @file
 * Palvelumaksujen tekeminen
 *
 * TAMKin oppimisympäristö, Ainopankki
 * teePalvelumaksu.php
 * Jarmo Kortetjärvi
 * Created: 06.10.2010
 * Modified: 06.10.2010
 *
 */

// AJETAAN KUUKAUDEN ENSIMMÄISENÄ PÄIVÄNÄ
// Tekee edellisen kuun palvelumaksut

	require_once 'functions.php';
	databaseConnect();
	
	$now = date('Y-m');
	
	// Tarkistetaan onko tämän kuun suoritus jo tehty
		$tarkistusquery = "	SELECT suoritettu FROM TAMK_tapahtumanSuoritus
							WHERE suoritettu LIKE '$now%'
							AND tapahtuma = 'palvelumaksu'
						";
		
		$result = mysql_query($tarkistusquery);
	
	if($result){ 
		if(mysql_num_rows($result)>0){
			exit; // tapahtuma on jo suoritettu
		}
	}
	
	// Haetaan OPY:t
		// Viime kuukausi
		$lastMonth = strtotime("-1 month");
		
		// Aloituspäivämäärä sql-kyselyä varten (datetime muodossa)
		$aloitusPvmMySql = date('Y-m-', $lastMonth) . '01 00:00:00';
		// Lopetuspäivämäärä sql-kyselyä varten (datetime muodossa)
		$lopetusPvmMySql = date('Y-m-t', $lastMonth) . ' 23:59:59';
		$tapahtuma = date('m/Y', $lastMonth);
		
		$query = "SELECT 		yhtio.nimi AS nimi
								, yhtio.yhtio AS yhtio
								, sum(TAMK_pankkitapahtuma.summa) AS summa
								, count(TAMK_pankkitapahtuma.summa) AS kpl
								, yhtio.ytunnus AS ytunnus
								, TAMK_pankkitili.tilinro AS pankkitili
					FROM 		yhtio 
					JOIN 		TAMK_pankkitili 
					ON 			yhtio.ytunnus = TAMK_pankkitili.ytunnus 
					JOIN 		TAMK_pankkitapahtuma 
					ON 			TAMK_pankkitili.tilinro = TAMK_pankkitapahtuma.maksaja 
					WHERE 		yhtio.yhtiotyyppi = 'OPY' 
					AND 		( eiVaikutaSaldoon != 'v'
								) 
					AND 		( TAMK_pankkitapahtuma.tapvm > '$aloitusPvmMySql' AND TAMK_pankkitapahtuma.tapvm < '$lopetusPvmMySql')
					GROUP BY 	yhtio.nimi
					";
		
		$result = mysql_query($query);
		
	// Tehdään tapahtumarivit
		mysql_query("START TRANSACTION");
		
		$maksuquery = "	INSERT INTO TAMK_pankkitapahtuma
							(
								yhtio
								, saaja
								, saajanNimi
								, maksaja
								, maksajanNimi
								, summa
								, tapvm
								, kurssi
								, valkoodi
								, selite
								, arkistotunnus
								, laatija
								, luontiaika
							)
							VALUES
						";
						
		
		while($opyrow = mysql_fetch_assoc($result)){
			$nimi = $opyrow['nimi'];
			$yhtio = $opyrow['yhtio'];
			$ytunnus = $opyrow['ytunnus'];
			$tapahtumat = $opyrow['kpl'];
			$summa = $tapahtumat * 0.15;
			$arkistotunnus = getArchiveReferenceNumber();
			//KIINTEÄ TILINUMERO OLI 99912300000518 VAIHDETTU IBAN MUOTOON 16.1.2013
			$maksuquery .= "(
								'$yhtio'
								, 'FI3099912300000518' 
								, 'Ainopankki'
								, '$opyrow[pankkitili]'
								, '$nimi'
								, $summa
								, now()
								, 1
								, 'EUR'
								, 'Palvelumaksu $tapahtuma'
								, '$arkistotunnus'
								, 'automaatti'
								, now()
								) ,
							";
		}
		$maksuquery = substr(trim($maksuquery),0,-1);
		echo $maksuquery;
		$maksuresult = mysql_query($maksuquery)or die("fuu:".mysql_error());

			$suoritettu = "	INSERT INTO TAMK_tapahtumanSuoritus SET
							yhtio = 'aino'
							, tapahtuma = 'palvelumaksu'
							, kuvaus = 'Palvelumaksu $tapahtuma'
							, suoritettu = now()
						";
			$suoritusresult = mysql_query($suoritettu);
		
		if($maksuresult && $suoritusresult){
			mysql_query("COMMIT");
		}
		else{
			mysql_query("ROLLBACK");
		}
?>