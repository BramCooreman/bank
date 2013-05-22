<?php
/**
 * @file
 * Luoton tietojen tarkastaminen
 *
 * TAMKin oppimisympäristö, Ainopankki
 * luotonTiedot.php
 * Author: Annika Granlund, Jarmo Kortetjärvi
 * Created: 03.08.2010
 * Modified: 13.10.2010
 *
 */
 
/**
 * @todo
 *   Lainojen lyhentämiset menevät lainamäärän ylitse!
 */ 
require_once 'functions.php';
echo "<h1>".localize('Luoton tiedot')."</h1>";

	$tilinro = $_SESSION[ 'tilinro' ];
	
	if(isset($_GET['ref'])){
		$arkistotunnus = $_GET['ref'];
		$tilinro = $_SESSION[ 'tilinro' ];
		
		databaseConnect();
		
		// Haetaan yhtiön pankkitapahtumat
		$query = "	SELECT *
							, DATE_FORMAT(tapvm, '%d.%m.%Y') AS date
					FROM TAMK_pankkitapahtuma
					WHERE arkistotunnus = '$arkistotunnus'
					AND saaja = '$tilinro'
				";
		$result = mysql_query($query);
		$row = mysql_fetch_assoc($result);
		//$lainojenMaara = mysql_num_rows; NOT USED
		
		// Jos löytyy tapahtumia, tarkastetaan lainojen tiedot
		if(mysql_num_rows($result) > 0){
		
				$query2 = "	SELECT * 
				FROM TAMK_lainantiedot
				WHERE arkistotunnus = '$arkistotunnus';
				";
				$result = mysql_query($query2);
				$tiedot = mysql_fetch_assoc($result);

			/**
			 *	Lainojen tiedot
			 */
			if(!isset($_GET['show'])){
				$koronMaara = $tiedot['korko'] + $tiedot['korkomarginaali'];
			
				echo "
					<div class='content padding20'>
					
					<p><span class='label'>".localize('Lainatili:')."</span> <span class='value'>$row[maksaja]</span></p>
					<p><span class='label'>".localize('Viite:')."</span> <span class='value'>$row[viite]</span></p>
					
						<table class='luotontiedot'>
							<tr class='bold'>
								<td>".localize('Pvm')."</td>
								<td>".localize('Summa')."</td>
								<td>".localize('Korko')."</td>
							</tr>
					";
			
					echo "	
								<tr>
									<td>$row[date]</td>
									<td>$row[summa]</td>
									<td>$koronMaara%</td>
								</tr>
						";
				
				echo "</table>
				
					<p><a class='painike' href='index.php?sivu=luotonTiedot&amp;ref=$arkistotunnus&amp;show=1'>".localize('Takaisinmaksusuunnitelma')."</a></p>
				</div>";
				
				$query = "	SELECT *
									, DATE_FORMAT(tapvm, '%d.%m.%Y') AS date
									, IF(eiVaikutaSaldoon = 'l', summa, summa*-1) AS summa
									, IF(selite REGEXP '[0-9]{2}\/[0-9]{4}$', SUBSTRING(selite, -7), 0) AS eranro
							FROM TAMK_pankkitapahtuma
							WHERE viite = '$row[viite]'
							ORDER BY tapvm ASC, eranro ASC, eiVaikutaSaldoon ASC
						";
				$result = mysql_query($query);
				
				echo "	<div class='content marginTop padding20'>
							<table class='luotontiedot alignLeft'>
								<tr>
									<th class='selite'>".localize('Selite')."</th>
									<th>".localize('Pvm')."</th>
									<th class='alignRight'>".localize('Summa')."</th>
								</tr>
					";
					
				$loppusumma = 0;	
				while($lyhennys = mysql_fetch_assoc($result)){
					echo "	<tr>";
					
					if(!empty($lyhennys['selite'])){
						echo "	<td>".ucfirst($lyhennys['selite'])."</td>";
					}
					else{
						echo "	<td>".localize('Lainan lyhennys')."</td>";
					}
						
					// Lisätään etumerkki
					if($lyhennys['summa'] > 0){
						$etumerkki = "+";
					}
					else{
						$etumerkki = null;
					}
							
					echo "
								<td>$lyhennys[date]</td>
								<td class='alignRight'>$etumerkki$lyhennys[summa]</td>
							</tr>
						";
					
					// Vähennetään lyhennykset lainan määrästä
					$maksutyyppi = $lyhennys['eiVaikutaSaldoon'];
					if($maksutyyppi != 'k' && $maksutyyppi != 'm'){
						$loppusumma = $loppusumma+$lyhennys['summa'];
					}
				}
				
				$loppusumma = number_format($loppusumma, 2, '.', '');
				if($loppusumma >= 0){
					$loppusumma = "+".$loppusumma;
				}
				
				echo "			<tr class='height30 verticalBottom borderTop bold'>
									<td>".localize('Lainaa jäljellä')."</td>
									<td>".date('d.m.Y')."</td>
									<td class='alignRight'>$loppusumma</td>
								</tr>
							</table>
							
						</div>";
						
						echo "<p><a href='index.php?sivu=luotonTiedot' class='painike'>".localize('Takaisin')."</a></p>";
				}
			/**
			* Takaisinmaksusuunnitelma
			*/
			else{
				// haetaan tehdyt lyhennykset
					$query = "
								SELECT *
										, DATE_FORMAT(maksupvm, '%d.%m.%Y') AS erapaiva
								FROM TAMK_lainatapahtuma
								WHERE arkistotunnus='$arkistotunnus'
								AND maksupvm IS NOT NULL
								ORDER BY maksupvm
							";
					$suoritusresult = mysql_query($query);
					$suoritusrivit = mysql_num_rows($suoritusresult);
				echo "<div class='content marginTop padding20'>";
			
				// muuttujat
				$myonnetty 	= $row['date'];
				$kokosumma 	= $row['summa'];
				$era		= $tiedot['maksuera'];
				$korkop		= ( $tiedot['korko'] + $tiedot['korkomarginaali'] ) / 100;
				$kkkorkop	= $korkop / 12;
				
				$erat 		= ceil($kokosumma/$era);
				
				// tulostetaan maksusuunnitelma
				echo "<table class='luotontiedot'>";
					echo "<tr class='alignRight'>";
						echo "<th>".localize('Maksuerä')."</th>";
						echo "<th>".localize('Eräpäivä')."</th>";
						echo "<th>".localize('Lyhennys')."</th>";
						echo "<th>".localize('Korko')."</th>";
						echo "<th>".localize('Maksuerä')."</th>";
						echo "<th>".localize('Lainaa jäljellä')."</th>";
					echo "</tr>";
					
				// tulostetaan jo menneet erät
					$k = 0;
					while($suoritus = mysql_fetch_assoc($suoritusresult)){
						$yhtsumma = $suoritus['lyhennys'] + $suoritus['korko'];
				
						$k++;
						$kokosumma = $kokosumma - $suoritus['lyhennys'];
						
						// muotoilut
						$lyhennys = number_format(( $suoritus['lyhennys']), 2, '.', ' ');
						$korko = number_format(($suoritus['korko']), 2, '.', ' ');
						$yhtsumma = number_format(($yhtsumma), 2, '.', ' ');
						$jaljella = number_format(($kokosumma), 2, '.', ' ');
						
						if($suoritus['suoritettu']==0) echo "<tr class='alignRight'>";
						else echo "	<tr class='alignRight gray'>";
						echo "
										<td>$k.</td>
										<td>$suoritus[erapaiva]</td>
										<td>$lyhennys&euro;</td>
										<td>$korko&euro;</td>
										<td>$yhtsumma&euro;</td>
										<td>$jaljella&euro;</td>
									</tr>";
					}
					// Jos maksettuja eriä ei ollut
					$jaljella = $kokosumma;
					
					//testisettiä
					/*
					if(is_null($suoritus['lyhennys']))
					{
						echo 'Lyhennys "NULL"' . $lyhennys ;
					}
					*/
					
					if($lyhennys == '0.00')
					{
						echo 'Virheellinen lyhennys (0.00 euroa) joten laina ei lyhenny koskaan' ;
						for($i = 0; $i < 3; $i++)
						{
							$k++;
							
							// lainaa jäljellä ennen maksuerää
							$jaljellaalku = $kokosumma - ($i * $era);
							
							// kuukauden korko
							$korko 	= $jaljellaalku * $kkkorkop;
							
							// tasalyhennys
							if($tiedot['tyyppi']==1){
								// lainaa jäljellä maksuerän jälkeen
								$jaljella = $jaljellaalku - $korko - $era;
								
								$yhtsumma 	= $era + $korko;
								$lyhennys 	= $era;
							}
							// kiinteä tasaerä
							if($tiedot['tyyppi']==2){
								// lyhennyksen suuruus
								$lyhennys = $era - $korko;
								
								// lainaa jäljellä maksuerän jälkeen
								$jaljella = $jaljellaalku - $lyhennys;
		
								$yhtsumma 	= $era;
							}
							
							// Lainaa jäljellä vähemmän kuin maksuerä
							if($jaljella<=0){
								$yhtsumma = ($yhtsumma+$jaljella);
								$jaljella = 0;
							}
							
							$erapaiva = strtotime(date("Y-m-d", strtotime($myonnetty)) . "+$k month");
							$erapaiva = date('d.m.Y', $erapaiva);
						
							// muotoillaan arvot
							$lyhennys = number_format(($lyhennys), 2, '.', ' ');
							$korko = number_format(($korko), 2, '.', ' ');
							$yhtsumma = number_format(($yhtsumma), 2, '.', ' ');
							$jaljella = number_format(($jaljella), 2, '.', ' ');
						
						
							echo "<tr class='alignRight'>";
								echo "<td>$k.</td>";
								echo "<td>$erapaiva</td>";
								echo "<td>$lyhennys&euro;</td>";
								echo "<td>$korko&euro;</td>";
								echo "<td>$yhtsumma&euro;</td>";
								echo "<td>$jaljella&euro;</td>";
							echo "</tr>";
						}
					}
					else
					{
						for($i=0; $jaljella>0; $i++){
							$k++;
							// lainaa jäljellä ennen maksuerää
							$jaljellaalku = $kokosumma - ($i * $era);
							
							// kuukauden korko
							$korko 	= $jaljellaalku * $kkkorkop;
							
							// tasalyhennys
							if($tiedot['tyyppi']==1){
								// lainaa jäljellä maksuerän jälkeen
								$jaljella = $jaljellaalku - $korko - $era;
								
								$yhtsumma 	= $era + $korko;
								$lyhennys 	= $era;
							}
							// kiinteä tasaerä
							if($tiedot['tyyppi']==2){
								// lyhennyksen suuruus
								$lyhennys = $era - $korko;
								
								// lainaa jäljellä maksuerän jälkeen
								$jaljella = $jaljellaalku - $lyhennys;
		
								$yhtsumma 	= $era;
							}
							
							// Lainaa jäljellä vähemmän kuin maksuerä
							if($jaljella<=0){
								$yhtsumma = ($yhtsumma+$jaljella);
								$jaljella = 0;
							}
							
							$erapaiva = strtotime(date("Y-m-d", strtotime($myonnetty)) . "+$k month");
							$erapaiva = date('d.m.Y', $erapaiva);
						
							// muotoillaan arvot
							$lyhennys = number_format(($lyhennys), 2, '.', ' ');
							$korko = number_format(($korko), 2, '.', ' ');
							$yhtsumma = number_format(($yhtsumma), 2, '.', ' ');
							$jaljella = number_format(($jaljella), 2, '.', ' ');
							
							echo "<tr class='alignRight'>";
								echo "<td>$k.</td>";
								echo "<td>$erapaiva</td>";
								echo "<td>$lyhennys&euro;</td>";
								echo "<td>$korko&euro;</td>";
								echo "<td>$yhtsumma&euro;</td>";
								echo "<td>$jaljella&euro;</td>";
							echo "</tr>";
						}
					}
			
				echo "</table>";
				
				echo "</div>";
				echo "<p><a href='index.php?sivu=luotonTiedot&amp;ref=$viite' class='painike'>".localize('Takaisin')."</a></p>";
			}

		}
		else{
			echo "<p>".localize('Sinulla ei ole riittäviä oikeuksia tämän luoton tietojen tarkasteluun.')."</p>";
		}
	
		
	}
	else{
		databaseConnect();
		
		$query= "
			SELECT 	*
					, DATE_FORMAT(tapvm, '%d.%m.%Y') AS date
					, SUM(summa) AS summa
			FROM TAMK_pankkitapahtuma 
			WHERE eiVaikutaSaldoon = 'l' 
				AND yhtio = 'pankk' 
				AND saaja='$tilinro'
			GROUP BY viite
			ORDER BY tapvm ASC
		;";
		$result = mysql_query($query);
		$num_rows = mysql_num_rows($result);
		
		if ( $num_rows != 0 ) {
			
			// Siirrytään samantien tarkastamaan lainan tietoja mikäli lainoja on vain yksi
			if($num_rows == 1)
			{
				// Haetaan arkistotunnus ja siirrytään luoton tietojen tarkastelu -sivulle
				$row = mysql_fetch_assoc($result);
				header("Location: index.php?sivu=luotonTiedot&ref=".$row['arkistotunnus']);
			}
				echo "	<div class='content padding20'>
							<p class='bold'>".localize('Myönnetyt luotot')."</p>";
				
				echo "
							<table class='luotontiedot'>
								<tr>
									<th>".localize('Pvm')."</th>
									<th>".localize('Summa')."</th>
									<th>".localize('Lainatili')."</th>
									<th>".localize('Viite')."</th>	
								</tr>";
								
				while($row=mysql_fetch_assoc($result)){
					echo		"<tr>	
									<td>". $row[ 'date' ] . "</td>
									<td>". $row[ 'summa' ] . "&euro;</td>
									<td>". $row[ 'maksaja' ] . "</td>
									<td><a href='index.php?sivu=luotonTiedot&amp;ref=".$row[ 'arkistotunnus' ]."'>". $row[ 'viite' ]. "</a></td>
									";
				}
				
				echo "
								</tr>
							</table>
						</div>";
		}
		else {
			echo "<p>".localize('Ei myönnettyjä luottoja.')."</p>";
		}
	}

?>
