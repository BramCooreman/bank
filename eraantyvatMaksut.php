<?php
/**
 * @file
 * Haetaan erääntyvät maksut
 *
 * TAMKin oppimisympäristö, Ainopankki
 * eraantyvatMaksut.php
 * Author: Annika Granlund, Jarmo Kortetjärvi
 * Created: 03.08.2010
 * Modified: 07.12.2010
 *
 */
	require_once 'functions.php';
	
	databaseConnect();

	$today = date('Y-m-d');
	$yhtio = $_SESSION[ 'yhtionNimi' ];
	$ytunnus = $_SESSION[ 'ytunnus' ];
	
	print '	<h1>'.localize('Erääntyvät maksut').'</h1>
			<p class="yhtionTiedot"><strong>' . $yhtio . '</strong></p>';
	
	$query = "	SELECT		*
				FROM		TAMK_pankkitili
				WHERE 		ytunnus = '$ytunnus'
				";
	$result = mysql_query($query);
	
	if (mysql_num_rows($result) == 0) {
		echo "<p>".localize('Pankkitiliä ei löydy')."</p>";
	}
	else {
		$row = mysql_fetch_array($result);
		$tilinro = $row[ 'tilinro' ];
		
		// Poistetaan erääntyvä maksu
		if(isset($_POST['poista']) && isset($_POST['tapahtuma'] )){
			$delete = "	DELETE FROM TAMK_pankkitapahtuma
						WHERE arkistotunnus = '$_POST[tapahtuma]'
						AND maksaja = '$tilinro'
						LIMIT 1";
			mysql_query($delete);
		}
		
		echo "<table class='tilinTiedot'><tr><td>".localize('Tilinro:')."</td><td> $tilinro</td></tr>";
		
		$printToday = date('d.m.Y',strtotime($today));
		
		echo "<tr><td>".localize('Erääntyvät maksut')." </td><td>"
			. $printToday ."</td></tr></table>"; 


		$query = "	SELECT	tapvm
							, saajanNimi
							, maksajanNimi
							, summa
							, selite
							, maksaja
							, arkistotunnus
					FROM	TAMK_pankkitapahtuma
					WHERE	(maksaja = '$tilinro') 
					AND		(tapvm > '$today') 
					AND		(eiVaikutaSaldoon = ''
								OR eiVaikutaSaldoon IS NULL
								OR eiVaikutaSaldoon = 'l'
								OR eiVaikutaSaldoon = 'a'
								OR eiVaikutaSaldoon = 'k'
								OR eiVaikutaSaldoon = 'm'
							)
					";
		$result = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($result) == 0) {
			echo "<div class='content padding20'>
				<p>".localize('Ei erääntyviä maksuja')."</p>

				<table id='tilioteTable'>";
		} else {
			//Is this nessecary??
			//$yesterday = mktime(0, 0, 0, $_POST[ 'startMonth' ]  , $_POST[ 'startDay' ] -1, $_POST[ 'startYear' ] );
			
			echo "	<div class='content'>
				<table id='tilioteTable'>
					<tr>
						<th>Tap.pvm</th>
						<th>Saajan nimi</th>
						<th>Maksajan nimi</th>
						<th>Summa</th>
						<th>Selite</th>
						<th>Poista</th>
					</tr>
				";
			
			$tilinSaldoAikavalilla = 0;
			
			// tulostetaan sql-kyselystä saadut tulokset
			$i = 1;
			while($row = mysql_fetch_array($result)) {
				$tapvm = $row[ 'tapvm' ];
				$saajanNimi = $row[ 'saajanNimi' ];
				$maksajanNimi = $row[ 'maksajanNimi' ];
				$summa = $row[ 'summa' ];
				$selite = $row[ 'selite' ];
				$maksaja = $row[ 'maksaja' ];
				$arkistotunnus = $row[ 'arkistotunnus' ];
				
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
					echo "<td>-$summa</td>";
					$tilinSaldoAikavalilla = $tilinSaldoAikavalilla - $summa;
				} else {
					echo "<td>+$summa</td>";
					$tilinSaldoAikavalilla = $tilinSaldoAikavalilla + $summa;
				}
				
				echo "<td>$selite</td>";
				
				// Erääntyvän laskun poisto
				$varmistus = localize('Oletko varma että haluat poistaa maksun?');
				echo "
						<td>
							<form action='' method='post'>";
								?><input type='submit' name='poista' value='x' onclick="javascript: return confirm('Oletko varma, että haluat poistaa tapahtuman?');"/><?php
				echo "			<input type='hidden' name='tapahtuma' value='$arkistotunnus'/>
							</form>
						</td>
					</tr>
					";
			}
		}
		echo "
			</table>";

		echo "</div>"; //div tilitapahtumat
	}
?>
