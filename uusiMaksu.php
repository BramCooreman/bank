<?php
/**
 * @file
 * Uuden maksun suorittaminen
 * 
 * TAMKin oppimisympäristö, Ainopankki
 * uusiMaksu.php
 * Annika Granlund, Jarmo Kortetjärvi
 * Created: 09.06.2010
 * Modified: 23.08.2010
 *
 */

require_once 'functions.php';
//variables
$maksunErapaiva = '';
$errorText= '';
$saaja= '';
$saajanNimi= '';
$summa= '';
$viite= '';
$viesti= '';

echo "<h1>".localize('Uusi maksu')."</h1>";

// the user has pressed to continue or modify the information button
if ((isset($_POST[ 'jatka' ]) || isset($_POST[ 'muuta' ])) && !isset($_POST[ 'tyhjenna' ])) {
		
		databaseConnect();
		
		// muuttujat
		$maksunErapaiva = mysql_real_escape_string($_POST[ 'maksunErapaiva' ]);
		$maksupvm = mysql_real_escape_string(substr($maksunErapaiva, 6, 4) . '-' . substr($maksunErapaiva, 3, 2) . '-' . substr($maksunErapaiva, 0, 2));
		$maksajanNimi = mysql_real_escape_string($_POST[ 'maksajanNimi' ]);
		$saajanNimi = mysql_real_escape_string($_POST[ 'saajanNimi' ]);
		$maksaja = mysql_real_escape_string($_POST[ 'maksajanTili' ]);
		$saaja = mysql_real_escape_string($_POST[ 'saajanTili' ]);
		$viite = mysql_real_escape_string($_POST[ 'viite' ]);
		$viesti = mysql_real_escape_string($_POST[ 'viesti' ]);
		$summa = mysql_real_escape_string(str_replace(',','.',$_POST[ 'summa' ]));
		$errorText = '';

		// Syötteiden tarkistus
		// tarkistetaan ja formatoidaan pankkitili

		//require_once '../pupesoft/inc/pankkitilinoikeellisuus.php';
		if (empty($saaja)) {
			$errorText = localize('Tilinumero on virheellinen, tyhjä.');
		}
		
		// maksulla on oltava viite tai viesti
		if(!$viite && !$viesti){
			$errorText = localize('Maksulla on oltava viite tai viesti.');
		}
		
		// summan pitää olla yli 0
		if ($summa <= 0) {
			$errorText = localize('Syötä maksun summa.');
		}
		
		// saajan nimi on tyhjä
		if(!$saajanNimi){
			$errorText = localize('Anna saajan nimi.');
		}
			
		// maksun määrässä voi olla vain numeroita
		if(!isDataNumeric($summa)){
			$errorText = localize('Tarkista summa.');
		}
		
		// tarkistetaan, että on rahaa maksaa maksu (jos tapahtumapäivä on nyt)
		else if ($maksupvm == date('Y-m-d')) {
			if ($summa > getSaldo($maksaja, $maksupvm)) {
				// rahaa ei ole tarpeeksi
				$errorText = localize('Tilin saldo ei riitä maksuun.');
			} else {
				// rahaa on tarpeeksi, ei toimintoja
			}
		}
		
		// päivämäärän tarkistus
		if(!checkDateFormat("$maksunErapaiva")){
			$errorText = localize('Tarkista päivämäärä.');
		}
		
		// tarkistetaan ettei eräpäivä ole jo mennyt
		if(strtotime($maksunErapaiva) < strtotime(date('d.m.Y'))){
			$errorText = localize('Valitsemasi eräpäivä on jo mennyt');
		}

		/*// tilinumerossa voi olla vain numeroita, ei voi olla tyhjä, ei hyväksy '-' -käyttöä toistaiseksi
		if(!ereg("[1-9]+0*", $saaja)){
			$errorText = 'Tarkista saajan tilinumero.';
			$saaja = '';
		}*/
		
		// saaja ja maksaja ei voi olla sama yritys
		if ($saaja == $maksaja) {
			$errorText = localize('Et voi maksaa omalle tilillesi.');
		}
		//Mun mielesta ois helvetin ihanaa et jos tanne tehdaan jotain muutoskia niin ei jatettais tammosia vanhoja kommentteja roikkuun.
		// tarkistetaan alkaako pankkitili numerolla 9
		//Tahan voi lisailla lisaa noita alkuja jos/sit kun on tarvetta!
		if (substr($saaja, 0, 2) == 'FI' || substr($saaja, 0, 2) == 'GB' || substr($saaja, 0, 2) == 'SE') {

			$query = "SELECT	omistaja
			FROM	TAMK_pankkitili 
			WHERE	yhtio = 'pankk'
			AND		tilinro = '$saaja' 
			";

			$result = mysql_query($query);

			if (mysql_num_rows($result) == 0) {
				// tilinumeroa ei ole olemassa
				$errorText = localize('Tilinumero on virheellinen.');
			} else {
					$row = mysql_fetch_array($result);
			}
		}
		else {
			$errorText = localize('Tarkista saajan tilinumero.');			
		}
		
	}

	// käyttäjä on painanut Jatka -nappia, ei virheviestiä
	if (isset($_POST[ 'jatka' ]) && !$errorText) {
	
		echo "	<div id='uusiMaksuLomake' class='content padding20'>
				<p id='uusiMaksu'>".localize('Hyväksy maksu')."</p>";
			
		echo "<table id='hyvaksyTable'>";
			echo getPossibleTableRow(localize('Maksajan tili'), $maksaja);
			echo getPossibleTableRow(localize('Maksajan nimi'), $maksajanNimi, false, true);
			echo getPossibleTableRow(localize('Saajan tilinumero'), $saaja, true);		
			echo getPossibleTableRow(localize('Saajan nimi'), $saajanNimi, false, true);
			echo getPossibleTableRow(localize('Eräpäivä'), $maksunErapaiva);
			echo getPossibleTableRow(localize('Maksun määrä'), number_format($summa, 2, ',', ' '), true);
			echo getPossibleTableRow(localize('Viite'), $viite);
			echo getPossibleTableRow(localize('Viesti'), $viesti);
		echo "</table>";
		
		echo "<form action='' method='post'>";
			echo getPossibleHiddenField($maksupvm, "maksupvm");
			echo getPossibleHiddenField($maksunErapaiva, "maksunErapaiva");
			echo getPossibleHiddenField($maksajanNimi, "maksajanNimi");
			echo getPossibleHiddenField($saajanNimi, "saajanNimi");
			echo getPossibleHiddenField($maksaja, "maksajanTili");
			echo getPossibleHiddenField($saaja, "saajanTili");
			echo getPossibleHiddenField($viite, "viite");
			echo getPossibleHiddenField($viesti, "viesti");
			echo getPossibleHiddenField($summa, "summa");
			
			echo "<p id='painikkeet'>
						<input type='submit' name='muuta' value='<< ".localize('MUUTA TIETOJA')."' class='painike'> 
						<input type='submit' name='hyvaksyMaksu' value=".localize('HYVÄKSY')." class='painike'> 
				</p>";
		echo "</form></div><!-- uusimaksulomake -->";
	}
	else {
	
	// jos pvm ei ole annettu, ehdotetaan tätä päivää
	if(!$maksunErapaiva) $maksunErapaiva = date('d.m.Y');			
	
	// uuden maksun syöttö -lomake
		print ' <div id="uusiMaksuLomake" class="content padding20">
					<p> * '.localize('pakollinen kenttä').'<br/>** '.localize('toinen kenttä pakollinen').'<br/><br/></p>
					
					<p class="errorMessage">' . $errorText . '</p>
						<form action="" method="post" >
							<table id="uusiMaksuKentat">
								<tr>
								<td>'.localize('Maksetaan tililtä').'</td>
								<td>' . $_SESSION[ 'tilinro' ] . '
								<input type="hidden" name="maksajanTili" value="' . $_SESSION[ 'tilinro' ] . '" onkeypress="return disableEnterKey(event)"/></td>
								</tr>
								<tr>
								<td>'.localize('Maksajan nimi').'</td>
								<td>' . $_SESSION['yhtionNimi'] . '
								<input type="hidden" name="maksajanNimi" value="' . $_SESSION[ 'yhtionNimi' ] . '" onkeypress="return disableEnterKey(event)"/></td>
								</tr>
								<tr>
								<td>&nbsp;</td>
								</tr>
								
								<tr>
								<td>'.localize('Saajan tilinumero').' *</td>
								<td>
								<input type="text" name="saajanTili" value="' . $saaja .  '" size="20" maxlength="22" class="kentta" onkeypress="return disableEnterKey(event)"/>
								</td>
								</tr>
								<tr>
								<td>'.localize('Saajan nimi').' *</td>
								<td><input type="text" name="saajanNimi" value="' . $saajanNimi . '" size="20" maxlength="35" class="kentta" onkeypress="return disableEnterKey(event)"/>
								</td>
								</tr>
								<tr>
								<td>'.localize('Eräpäivä').' *</td>
								<td>
								<input type="text" name="maksunErapaiva" size="10" maxlength="10" class="pvmKentta" id="date" value="' . $maksunErapaiva . '" onkeypress="return disableEnterKey(event)"/>
									<script type="text/javascript">
										calendar.set("date");
									</script>
								</td>
								</tr>
								<tr>
								<td>'.localize('Maksun määrä').' *</td>
								<td><input type="text" name="summa" value="' . $summa . '" size="10" maxlength="19" class="kentta" id="maksunMaaraKentta" onkeypress="return disableEnterKey(event)"/>EUR</td>
								</tr>
								<tr>
								<td>'.localize('Viite').' **</td>
								<td><input type="text" name="viite" value="' . $viite . '" size="20" maxlength="20" class="kentta" onkeypress="return disableEnterKey(event)"/></td>
								</tr>
								<tr>
								<td>'.localize('Viesti').' **</td>
								<td><textarea class="kentta" name="viesti" rows="3" cols="1" >' . $viesti . '</textarea></td>
								</tr>
								<tr>
								<td>'.localize('Tilioteteksti').'</td>
								<td>'.localize('tilisiirto').'</td>
								</tr>
							</table>
							
							<p id="painikkeet">
							<input type="submit" name="tyhjenna" value='.localize('TYHJENNÄ').' class="painike"/>
							<input type="submit" name="jatka" value='.localize('JATKA').' class="painike"/>
							</p>
						</form>
				</div><!-- uusiMaksuLomake -->';
	}
?>
