<?php
/**
 * @file
 * Tarkastetaan lainojen lyhennykset
 *
 * TAMKin oppimisympäristö, Ainopankki
 * tarkistaLyhennys.php
 * Jarmo Kortetjärvi
 * Created: 25.08.2010
 * Modified: 10.03.2010
 *
 */

/**
 * @todo 
 *   TARRKISTA KÄYYTTÖOIKEUDET TÄHÄN TIEDOSTOON
 */
require_once 'functions.php';
databaseConnect();

// Haetaan lainojen tiedot
$query = "		SELECT * 
				FROM TAMK_lainantiedot
				JOIN TAMK_pankkitapahtuma
				ON TAMK_pankkitapahtuma.arkistotunnus = TAMK_lainantiedot.arkistotunnuss
				WHERE saaja != 'FI3599912300014493'
				AND saaja != 'GB8299912300052253'
				AND TAMK_lainantiedot.maksuera > 0
			";
$lainaresult = mysql_query($query);



// Tarkastetaan kaikki lainat
while($lainarow = mysql_fetch_assoc($lainaresult)){
	static $nu = 0; /**< Lainojen numerointi */
	$nu++;

	// Jos korko on sidottu Euriboriin, lasketaan korkoprosentti
	$korkotyyppi = $lainarow['korkotyyppi']; /**< Lainan korkotyyppi, 3 kk, 6 kk tai 12 kk euribor*/
	if($korkotyyppi >= 2 && $korkotyyppi <= 4){

		// Onko korko vanhentunut?
		$paivitetty = $lainarow['euribor'];
		$korkoid = $lainarow['id'];
		$paivitetaan = 0;
		
		// Euriboria ei ole vielä asetettu
		if($paivitetty == NULL){
			$paivitetaan = 1;
		}
		else{
			// Lasketaan koron ikä
			switch($korkotyyppi){
				case 2: // 3 kk euribor
					$voimassa = 3;
					break;
				case 3: // 6 kk euribor
					$voimassa = 6;
					break;
				case 4: // 12 kk euribor
					$voimassa = 12;
					break;
			}
			
			$paivitetty = strtotime($paivitetty);
			$nyt = strtotime('now');
			$age = ($nyt - $paivitetty) / 86400;
			
			// Korko on päivitettävä
			if($age>($voimassa*30)) 
				$paivitetaan = 1;
		}
		
		// Päivitetään korko
		if($paivitetaan == 1){
			require_once 'tarkistaEuribor.php';
			$newrate = getRssRate($voimassa);
			if($newrate){
				$update = "	UPDATE TAMK_lainantiedot
							SET	
							euribor = now()
							, korko	= $newrate
							WHERE id = $korkoid
						";
						
				echo "$nu. $lainarow[saajanNimi] - päivitetty euribor $newrate.\n";
				mysql_query($update);
			}
			else echo "$nu. $lainarow[saajanNimi] - uutta korkoprosenttia ei voitu hakea.\n";
		}
	}

	// Haetaan tilin saldo
	$tilinsaldo = getSaldo($lainarow['saaja'], 'now()');
        	
	// Lasketaan maksuerä
		
		// Muuttujat
			$arkistotunnus	= $lainarow['arkistotunnus'];
			$kokolaina 		= $lainarow['summa'];
			$korko 			= $lainarow['korko']+$lainarow['korkomarginaali'];
			$maksuera		= $lainarow['maksuera'];
			$lainatyyppi 	= $lainarow['tyyppi'];
			$lainapvm		= $lainarow['tapvm'];
			
		if($korko==0){
			echo "Virheellinen korko!"; 
			break;
		}
		
		// Lyhennykset
			$laina = $kokolaina;
			//for($i=0;$laina>0;$i++){
			while($laina > 0) {
				$kkkorko 	= $laina * ($korko/100/12);
				
				// Tasalyhennys
				if($lainatyyppi == 1){
					$lyhennys 	= $maksuera;
				}
				// Kiinteä tasaerä
				elseif($lainatyyppi == 2){
					$lyhennys 	= $maksuera-$kkkorko;
				}
				
				// Lyhennys on suurempi kuin jäljelläoleva laina
				if($laina<$lyhennys){
					$lyhennys = $laina;
				}
				
				$laina = $laina - $lyhennys;
			}
	
	// Tarkistetaan onko maksueriä, joista ei ole merkintää lainatapahtuma-taulussa
		$thismonth = date('Ym');
		
		// Tehdään lainatapahtuma-tauluun merkintä uusista maksueristä
		$continue = 1; 
				
		while($continue == 1){
			// Haetaan lainan viimeisin maksuerä
				$query = "	SELECT *
							, DATE_FORMAT(maksupvm, '%Y%m') AS lastmonth
							FROM TAMK_lainatapahtuma
							WHERE arkistotunnus = '$arkistotunnus'
							ORDER BY maksupvm DESC
							LIMIT 1;
							";
				
				$result = mysql_query($query);
				
				// Query ei onnistu
				if(!$result){
					echo "Virhe kyselyssä: $query";
					$continue = 0;
				}
				
				$maksuerarow = mysql_fetch_assoc($result);
				
				// Yhtään lainatapahtumaa ei vielä ole
				$numrows = mysql_num_rows($result);
				if($numrows==0){
					$paymentmonth = $lainapvm;
				}
				elseif($maksuerarow['maksupvm']){
					$paymentmonth = $maksuerarow['maksupvm'];
				}
				// lastmonth muotoon yyyymm
				$lastmonth = strtotime(date("Y-m-d", strtotime($paymentmonth)) . "+1 month");
				$lastmonth = date('Ym', $lastmonth);
			
				// Tehdään lainatapahtumat
				if($lastmonth <= $thismonth){
					// kaikki maksetut erät
						$eraquery = "SELECT SUM(lyhennys) AS maksetut FROM TAMK_lainatapahtuma WHERE arkistotunnus = '$arkistotunnus'";
						$maksetutrow = mysql_fetch_assoc(mysql_query($eraquery));
					
					$lainaajaljella = $kokolaina - $maksetutrow['maksetut'];
					$kkkorkop = $korko / 12 / 100;
					$kkkorko = round($lainaajaljella*$kkkorkop,2);
					
					// tasalyhennys
					if($lainatyyppi == 1){
						$lyhennys 	= $maksuera;
					}
					// kiinteä tasaerä
					elseif($lainatyyppi == 2){
						$lyhennys 	= $maksuera-$kkkorko;
					}
					// lyhennys on suurempi kuin jäljelläoleva laina
					if($kokolaina<$lyhennys){
						$lyhennys = $kokolaina;
					}
					
					// Laina on nolla, lainaa ei tarvitse enää maksaa
					if($kokolaina <= 0) {
						$continue = 0;
					}
					
					// tehtävä kuukausi
					$paymentmonth = strtotime(date("Y-m-d", strtotime($paymentmonth)) . "+1 month");
					$paymentmonth = date('Y-m-d', $paymentmonth);
					
					// tehdään viimeistä maksuerää seuraavalle kuukaudelle suoritus
						$query = "	INSERT INTO TAMK_lainatapahtuma
									(
									arkistotunnus
									, lyhennys
									, korko
									, maksupvm
									, suoritettu
									, korkoprosentti
									)
									VALUES
									(
									'$arkistotunnus'
									, $lyhennys
									, $kkkorko
									, '$paymentmonth'
									, 0
									, $korko
									)
								";
					$result = mysql_query($query);
				}
				else{
					$continue = 0;
					break;
				}
                
		}

	// -------------------------------------------------------------------------- //
	
	// tarkistetaan onko maksamattomia lainaeriä
		$query = "	SELECT *
							, TAMK_lainatapahtuma.id AS tunniste
							, DATE_FORMAT(TAMK_lainatapahtuma.maksupvm, '%m/%Y') AS erapvm
							, TAMK_lainatapahtuma.korko AS korko
					FROM TAMK_lainatapahtuma
					JOIN TAMK_pankkitapahtuma
					ON TAMK_lainatapahtuma.arkistotunnus = TAMK_pankkitapahtuma.arkistotunnus
					JOIN TAMK_lainantiedot
					ON TAMK_lainantiedot.arkistotunnus = TAMK_lainatapahtuma.arkistotunnus
					WHERE suoritettu = 0
					AND	TAMK_lainatapahtuma.arkistotunnus = '$arkistotunnus'
					ORDER BY maksupvm DESC
					";
		$maksamatonresult = mysql_query($query);
		
	// maksamattomia lainaeriä löytyy
		$numrows = mysql_num_rows($maksamatonresult);
		
		
                
		                        
		
		if($numrows > 0){
		        
			while($maksamaton = mysql_fetch_assoc($maksamatonresult)){
				// Tarkistetaan onko tilillä katetta maksaa
				
					// erän kasvama korko
					$maksuera 		= $maksamaton['maksuera'];
					$lyhennysera	= $maksamaton['lyhennys'];
					$korkoera 		= $maksamaton['korko'];
					$erapvm 		= $maksamaton['erapvm'];
					
					// lasketaan paljonko maksu on myöhässä
					$maksupvm 	= strtotime($maksamaton['maksupvm']);
					$suorituspvm = strtotime(date("Y-m-d"));
					$myohassa = $suorituspvm - $maksupvm;
					$myohassa = $myohassa / 86400;
					
					$tempkorko 	= $maksamaton['korkoprosentti'];
					
					$viivastyskorko = round(($maksuera*$tempkorko*$myohassa)/(100*365), 2);
					
					$maksamatta = $maksuera;
											
					// tilillä on katetta
					if($maksamatta < $tilinsaldo){
						// maksetaan lasku tililtä
						// kentät
							$query = 
							"	INSERT INTO TAMK_pankkitapahtuma
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
									, viite
									, selite
									, arkistotunnus
									, laatija
									, luontiaika
									, eiVaikutaSaldoon
								)
								VALUES
							";
							
						// lyhennyksen osuus
							$maksutunnus = getArchiveReferenceNumber();
							$query .=	
							"
								(
								'pankk'
								, '$lainarow[maksaja]'
								, '$lainarow[maksajanNimi]'
								, '$lainarow[saaja]'
								, '$lainarow[saajanNimi]'
								, '$lyhennysera'
								, now()
								, 1
								, 'EUR'
								, '$lainarow[viite]'
								, 'Lainan lyhennys, $erapvm'
								, '$maksutunnus'
								, 'automaatti'
								, now()
								, 'a'
								)";
						// koron osuus
						if($korkoera>0){
						$maksutunnus = getArchiveReferenceNumber();
						$query .= 
							"
							,(
							'pankk'
							, '$lainarow[maksaja]'
							, '$lainarow[maksajanNimi]'
							, '$lainarow[saaja]'
							, '$lainarow[saajanNimi]'
							, '$korkoera'
							, now()
							, 1
							, 'EUR'
							, '$lainarow[viite]'
							, 'Lainan korko, $erapvm'
							, '$maksutunnus'
							, 'automaatti'
							, now()
							, 'k'
							)";
						}
						$result = mysql_query($query);
						
						if($viivastyskorko>0){
							// maksetaan lainaerän viivästyskorot
								$maksutunnus = getArchiveReferenceNumber();
							
								$query = 
								"	INSERT INTO TAMK_pankkitapahtuma
									SET
										yhtio = 'pankk',
										saaja='$lainarow[maksaja]',
										saajanNimi = '$lainarow[maksajanNimi]',
										maksaja='$lainarow[saaja]',
										maksajanNimi = '$lainarow[saajanNimi]',
										summa='$viivastyskorko',
										tapvm=now(),
										kurssi=1,
										valkoodi = 'EUR',
										viite = '$lainarow[viite]',
										selite = 'Lainan viivästyskorko, $erapvm',
										arkistotunnus = '$maksutunnus', 
										laatija='automaattimaksu',
										luontiaika=now(),
										eiVaikutaSaldoon = 'm'
								";
							$result = mysql_query($query);
							
						// merkitään viivästyskorot lainatapahtumiin
						$query = "	INSERT INTO TAMK_lainatapahtuma
									(
									arkistotunnus
									, korko
									, suorituspvm
									, suoritettu
									, korkoprosentti
									)
									VALUES
									(
									'$arkistotunnus'
									, '$viivastyskorko'
									, now()
									, 1
									, $korko
									)
								";
						$result = mysql_query($query);
						}
						
						
						// päivitetään lasku maksetuksi
							$query = "	UPDATE TAMK_lainatapahtuma
										SET suoritettu = 1, suorituspvm=now()
										WHERE id = $maksamaton[tunniste]";
						$result = mysql_query($query);
					}
				// tilillä ei ole katetta
				else {
					// Tarkistetaan onko huomautusmaksu jo tehty
					$query = "	SELECT maksupvm
								FROM TAMK_lainatapahtuma
								WHERE date_format(maksupvm, '%m/%Y') = '$erapvm'
								AND arkistotunnus = '$arkistotunnus'
								AND korkoprosentti IS NULL
								AND lyhennys IS NULL
							";
					$result = mysql_query($query);
					$rows = mysql_num_rows($result);
					
					if($rows > 0){
						// Ei tehdä mitään, huomautusmaksu on jo tehty
						}
					else {
						// Huomautusmaksu
						$query = "	INSERT INTO TAMK_lainatapahtuma
									(
									arkistotunnus
									, korko
									, suorituspvm
									, suoritettu
									, maksupvm
									)
									VALUES
									(
									'$arkistotunnus'
									, 5
									, now()
									, 1
									, '$maksamaton[maksupvm]'
									)
								";
						$result = mysql_query($query);
					}
				}								
                        }
		}

}

?>
