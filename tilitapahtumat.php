<?php
/**
 * @file
 * Yrityksen tilitapahtumien haku
 *
 * TAMKin oppimisympäristö, Ainopankki
 * tilitapahtumat.php
 * Author: Annika Granlund, Jarmo Kortetjärvi
 * Created: 28.07.2010
 * Modified: 1.2.2011
 *
 */
	
	require_once 'functions.php';
	echo "<h1>".localize('Tilitapahtumat')."</h1>";
	
	$errorText = '';
	$tilinSaldoAikavalilla = 0;
	
	// jos käyttäjä on painanut Hae -nappia
    //if the user has pressed the Search button
	if (isset($_POST[ 'search' ])) {
		$startDate = $_POST[ 'startdate' ];
		$endDate = $_POST[ 'enddate' ];
		$startDate = checkDateFormat( $startDate );
		$endDate = checkDateFormat( $endDate );
		
		databaseConnect();
		
		// jos alkupvm ei ole annettu
		if( $startDate == false ){
			$errorText = "<p class='errorMessage'>".localize('Tarkista alkupäivämäärä.')."</p>";
		}
		// jos loppupvm ei ole annettu
		if( $endDate == false ){
			$errorText = "<p class='errorMessage'>".localize('Tarkista loppupäivämäärä.')."</p>";
		}
		
		$today = date('d.m.Y');
		// jos alkupvm on tulevaisuudessa
		if( strtotime($startDate) > strtotime($today) ) {
			$errorText = "<p class='errorMessage'>".localize('Tarkista alkupäivämäärä.')."</p>";
			$startDate = false;
		}
		// jos lopupvm on tulevaisuudessa
		if ( strtotime($endDate) > strtotime($today) ) {
			$errorText = "<p class='errorMessage'>".localize('Tarkista loppupäivämäärä.')."</p>";
			$endDate = false;
		}
		
		if ($errorText) {
			print $errorText;
		}

		// jos alku- ja loppupvm:t on annettu oikein ja errorText on tyhjä, haetaan tilitapahtumat
		if( $startDate != false and $endDate != false and empty($errorText) ) {

			$yhtio = $_SESSION[ 'yhtionNimi' ];
			$ytunnus = $_SESSION[ 'ytunnus' ];
		
			if(strtotime($endDate) > strtotime(date('d.m.Y'))){
				//$endDate = date('d.m.Y');
			}

			print '<p class="yhtionTiedot"><strong>' . $yhtio . '</strong></p>';
		
			$query = "	SELECT		*
						FROM		TAMK_pankkitili
						WHERE 		ytunnus = '$ytunnus' 
						";
			$result = mysql_query($query) or pupe_error($query);
		
			if (mysql_num_rows($result) == 0) {
				echo "<p>".localize('Pankkitiliä ei löydy')."</p>";
			}
			else {
				$row = mysql_fetch_array($result);
				$tilinro = $row[ 'tilinro' ];
			
				echo "<table class='tilinTiedot'><tr><td>".localize('Tilinro:')."</td><td> $tilinro</td></tr>";
			
				echo "<tr><td>".localize('Tilitapahtumat aikavälillä')." </td><td>$startDate - $endDate</td></tr></table>";
					
				$startDateMySql = date( 'Y-m-d', strtotime($startDate) );
				$endDateMySql = date( 'Y-m-d', strtotime( $endDate ) );

				// TODO: eiVaikutaSaldoon-ehdot kuntoon
				$query = "	SELECT	tapvm
									, saajanNimi
									, maksajanNimi
									, summa
									, selite
									, viite
									, maksaja
							FROM	TAMK_pankkitapahtuma
							WHERE	(saaja = '$tilinro' OR maksaja = '$tilinro')
							AND		(tapvm >= '$startDateMySql' AND tapvm <= '$endDateMySql')
							AND		(eiVaikutaSaldoon = ''
									OR eiVaikutaSaldoon IS NULL
									OR eiVaikutaSaldoon = 'l'
									OR eiVaikutaSaldoon = 'a'
									OR eiVaikutaSaldoon = 'k'
									OR eiVaikutaSaldoon = 'm'
									)
							ORDER BY tapvm ASC
							";
				
						
				$result = mysql_query($query) or pupe_error($query);
			
				if (mysql_num_rows($result) == 0) {
					echo "<div id='tilitapahtumat'>
						<p>".localize('Tilitapahtumia ei löydy aikavälillä')." $startDate - $endDate !</p>
						";
						
				} else {

					$dateTable = explode(".", $startDate);
					$yesterday = mktime(0, 0, 0, $dateTable[1]  , $dateTable[0] -1, $dateTable[2] );
				
					echo "	<div class='content'>
						<table id='tilioteTable'>
							<tr>
								<th>".localize('Tap.pvm')."</th>
								<th>".localize('Saajan nimi')."</th>
								<th>".localize('Maksajan nimi')."</th>
								<th>".localize('Summa')."</th>
								<th>".localize('Viite selite')."</th>
							</tr>
							<tr>
								<td></td>
								<td></td>
								<td>".localize('Alkusaldo')." $startDate</td>";
								
					// etumerkki alkusaldoon
						$tempSaldo = getSaldo($tilinro, date('Y-m-d', $yesterday));
							if($tempSaldo>=0){
								$tempSaldo = "+".$tempSaldo;
							}
						
					echo "		<td class='alignRight'>$tempSaldo</td>
								<td></td>
							</tr>
						";
				
					
				
					// tulostetaan sql-kyselystä saadut tulokset
					$i = 1;
					while($row = mysql_fetch_array($result)) {
						$tapvm = $row[ 'tapvm' ];
						$saajanNimi = $row[ 'saajanNimi' ];
						$maksajanNimi = $row[ 'maksajanNimi' ];
						$summa = $row[ 'summa' ];
						$selite = $row[ 'selite' ];
						$viite = $row[ 'viite' ];
						if ( !empty($viite) ) {
							$viite = $viite . ",<br/>";
						}
						$maksaja = $row[ 'maksaja' ];
					
						echo "<tr";
							if ($i%2 == 1) echo " class='oddRow'";
							$i++;
						echo ">
								<td>".date('d.m.Y',strtotime($tapvm))."</td>
								<td>$saajanNimi</td>
								<td>$maksajanNimi</td>
							";
					
						// jos laskun maksaja on sama kuin yrityksen oma tili, on kyse maksusta (eli tulostetaan miinusmerkki)
						if ($row[ 'maksaja' ] == $tilinro) {
							echo "<td class='alignRight'>-$summa</td>";
							$tilinSaldoAikavalilla = $tilinSaldoAikavalilla - $summa;
						} else {
							echo "<td class='alignRight'>+$summa</td>";
							$tilinSaldoAikavalilla = $tilinSaldoAikavalilla + $summa;
						}
					
						echo "
								<td>$viite $selite</td>
							</tr>
							";
					}
				echo "<tr>
						<td></td>
						<td></td>
						<td>".localize('Tapahtumat yhteensä')."</td>
						<td class='alignRight'>".number_format($tilinSaldoAikavalilla,2,'.',' ')."</td>
						<td></td>
					</tr>
					<tr>
						<td></td>
						<td></td>
						<td>".localize('Tilin saldo')." " . $endDate ."</td>";
				
				// etumerkki saldoon
				$tempSaldo = getSaldo($tilinro, date('Y-m-d', strtotime($endDate)));
				if($tempSaldo>=0){
					$tempSaldo = "+".$tempSaldo;
				}
				
				echo "
						<td class='alignRight'>$tempSaldo</td>
						<td></td>
					</tr>
					</table>";
				}
			
				$tilinSaldo = getSaldo($tilinro, date('Y-m-d'));
			
				echo "<table class='tilinSaldot'>";

				echo "<tr><td>".localize('Tilin saldo')." ".date('d.m.Y').": </td><td>";
					if($tilinSaldo >= 0) {
						echo "+";
					}
				echo "$tilinSaldo ".localize('euroa')."</td></tr></table>";
				echo "</div><!-- /tilitapahtumat -->
						<p><a class='painike' href='index.php?sivu=tilitapahtumat'>".localize('Takaisin')."</a></p>";
			}
		}
		else {
			if($startDate == false && $endDate == false){
				printTimeFrameSearchForm();
			}
			elseif($startDate == false){
				printTimeFrameSearchForm(false, $endDate);
			}
			elseif($endDate == false){
				printTimeFrameSearchForm($startDate);
			}
		}
	} 
	else {
		printTimeFrameSearchForm();
		printLastTransactions();
	}
	
	function printTimeFrameSearchForm($startDate = false, $endDate = false) {

		// tilitietojen aikavälin hakulomake
		print '	<div id="login" class="content">
				<h2>'.localize('Hae aikaväliltä').'</h2>
					<div id="kirjaudulomake">
						<form action="" method="post">
							<p>'.localize('Syötä tiedot muodossa pp.kk.vvvv').'</p>
							<table id="authentication">
							<tr>
							<td>'.localize('alkupvm').'</td>
							<td>
							<input type="text" name="startdate" id="startdate" value="' . $startDate . '" />
							<script type="text/javascript">
								calendar.set("startdate");
							</script>
							</td>
							</tr>
							<tr>
							<td>'.localize('loppupvm').'</td>
							<td>
							<input type="text" name="enddate" id="enddate" value="' . $endDate . '" />
							<script type="text/javascript">
								calendar.set("enddate");
							</script>
							</td>
							</tr>
							</table>
							<p id="painikkeet">
							<input type="submit" name="search" value='.localize('HAE').' class="painike"/>
							<input type="reset" value='.localize('TYHJENNÄ').' class="painike"/>
							</p>
						
						</form>
					</div><!-- /kirjaudu -->
				</div><!-- /login -->';
	} // end of function printTimeFrameSearchForm()
	
	/**
	 * Tulostetaan Tilitapahtumat-sivulle 5 viimeisintä tilitapahtumaa
	 */
	function printLastTransactions() {
	
		// Tallennetaan yrityksen y-tunnus muuttajaan
		$ytunnus = $_SESSION[ 'ytunnus' ];
		//$tilinSaldoAikavalilla = 0;
		// Tietokantayhteys
		databaseConnect();
		
		// Haetaan y-tunnyksen avulla yrityksen tilinumero
		$query = "SELECT *
				  FROM TAMK_pankkitili
				  WHERE ytunnus = '$ytunnus' 
				 ";
				 
		$result = mysql_query($query) or pupe_error($query);
		
		// Tilinumeroa ei löydy (ei pitäisi koskaan tapahtua)
		if (mysql_num_rows($result) == 0) {
			echo "<p>".localize('Pankkitiliä ei löydy')."</p>";
		}
		
		// Tilinumero löytyi, tallennetaan muuttujaan
		else {
			$row = mysql_fetch_array($result);
			$tilinro = $row[ 'tilinro' ];
		}
		
		// Haetaan yrityksen tiedoista viisi viimeisintä pankkitapahtumaa
		$query = "	SELECT	tapvm
					, saajanNimi
					, maksajanNimi
					, summa
					, selite
					, viite
					, maksaja
				FROM	TAMK_pankkitapahtuma
				WHERE	(saaja = '$tilinro' OR maksaja = '$tilinro')
				AND	    tapvm <= now()
				AND		(eiVaikutaSaldoon = ''
						OR eiVaikutaSaldoon IS NULL
						OR eiVaikutaSaldoon = 'l'
						OR eiVaikutaSaldoon = 'a'
						OR eiVaikutaSaldoon = 'k'
						OR eiVaikutaSaldoon = 'm'
						)
					ORDER BY tapvm DESC
					LIMIT 5
					";
					
		$result = mysql_query($query) or pupe_error($query);
		
		// Pankkitapahtumia ei löytynyt
		if (mysql_num_rows($result) == 0) {
			echo " <p>".localize('Ei tilitapahtumia')."</p>";		
		}
		
		// Tulostetaan taulukko, joka sisältää tietokantahaun tulokset
		else {
			echo '	<div id="tilitapahtumat" class="viimeisetTilit">
					<h2>'.localize("Viimeisimmät tilitapahtumat").'</h2>
				<table id="tilioteTable" class="tiliTiedot">
					<tr>
						<th>'.localize("Tap.pvm").'</th>
						<th>'.localize("Saajan nimi").'</th>
						<th>'.localize("Maksajan nimi").'</th>
						<th>'.localize("Summa").'</th>
						<th>'.localize("Viite selite").'</th>
					</tr>';
					
					$i = 1;
					while($row = mysql_fetch_array($result)) {
						$tapvm = $row[ 'tapvm' ];
						$saajanNimi = $row[ 'saajanNimi' ];
						$maksajanNimi = $row[ 'maksajanNimi' ];
						$summa = $row[ 'summa' ];
						$selite = $row[ 'selite' ];
						$viite = $row[ 'viite' ];
						if ( !empty($viite) ) {
							$viite = $viite . ",<br/>";
						}
						$maksaja = $row[ 'maksaja' ];
						
						echo "<tr";
							if ($i%2 == 1) echo " class='oddRow'";
							$i++;
						echo ">
								<td>".date('d.m.Y',strtotime($tapvm))."</td>
								<td>$saajanNimi</td>
								<td>$maksajanNimi</td>
							";
					
						// jos laskun maksaja on sama kuin yrityksen oma tili, on kyse maksusta (eli tulostetaan miinusmerkki)
						if ($row[ 'maksaja' ] == $tilinro) {
							echo "<td class='alignRight'>-$summa</td>";
							$tilinSaldoAikavalilla = $tilinSaldoAikavalilla - $summa;
						} else {
							echo "<td class='alignRight'>+$summa</td>";
							$tilinSaldoAikavalilla = $tilinSaldoAikavalilla + $summa;
						}
					
						echo "
								<td>$viite $selite</td>
							</tr>
							";
					}
						
			echo '	
				</table>
				</div><!-- /tilitapahtumat -->';
		}
	}
?>
