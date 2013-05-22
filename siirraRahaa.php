<?php

/**
 * @file
 * Rahan siirtäminen yrityksen tilille
 *
 * TAMKin oppimisympäristö, Ainopankki
 * siirraRahaa.php
 * Author: Jani Keränen
 * Created: 31.07.2012
 * Modified: 31.07.2012
 *
 */
 
require_once 'functions.php';
echo "<h1>".localize('Siirrä rahaa')."</h1>";

echo '<div class="content padding20">';
$errorText = "";
$saajanNimi= "";
$summa = "";
$saaja = "";
if(isset($_POST['jatka'])) {

	databaseConnect();
	
	$saaja = mysql_real_escape_string($_POST[ 'saajanTili' ]);
	$saajanNimi = mysql_real_escape_string($_POST[ 'saajanNimi' ]);
	$summa = mysql_real_escape_string(str_replace(',','.',$_POST[ 'summa' ]));
	$maksunErapaiva = date("d.m.Y");
	$viesti = localize('Ylläpitäjän suorittama rahan siirto');
	
	if (empty($saaja)) {
		$errorText = localize('Tilinumero on virheellinen, tyhjä.');
	}
	
	if ($summa <= 0) {
		$errorText = localize('Syötä maksun summa.');
	}
	
	if (!$saajanNimi) {
		$errorText = localize('Anna saajan nimi.');
	}
	
	if(!isDataNumeric($summa)){
		$errorText = localize('Tarkista summa.');
	}
	if (substr($saaja, 0, 2) == 'FI') {

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

if (isset($_POST[ 'jatka' ]) && !$errorText) {

	echo "	<div id='uusiMaksuLomake' class='content padding20'>
			<p id='uusiMaksu'>".localize('Hyväksy maksu')."</p>";
		
	echo "<table id='hyvaksyTable'>";
		echo getPossibleTableRow(localize('Saajan tilinumero'), $saaja, true);		
		echo getPossibleTableRow(localize('Saajan nimi'), $saajanNimi, false, true);
		echo getPossibleTableRow(localize('Eräpäivä'), $maksunErapaiva);
		echo getPossibleTableRow(localize('Maksun määrä'), number_format($summa, 2, ',', ' '), true);
		echo getPossibleTableRow(localize('Viesti'), $viesti);
	echo "</table>";
	
	echo "<form action='' method='post'>";
		echo getPossibleHiddenField($maksunErapaiva, "maksunErapaiva");
		echo getPossibleHiddenField($saajanNimi, "saajanNimi");
		echo getPossibleHiddenField($saaja, "saajanTili");
		echo getPossibleHiddenField($summa, "summa");
		echo getPossibleHiddenField($viesti, "viesti");
		
		echo "<p id='painikkeet'>
					<input type='submit' name='muuta' value='<< ".localize('MUUTA TIETOJA')."' class='painike'> 
					<input type='submit' name='hyvaksySiirto' value=".localize('HYVÄKSY')." class='painike'> 
			</p>";
	echo "</form></div><!-- uusimaksulomake -->";
}

else {
	print ' <div id="uusiMaksuLomake" class="content padding20">
					<p> * '.localize('pakollinen kenttä').'</p>
					<p class="errorMessage">' . $errorText . '</p>
						<form action="" method="post" >
							<table id="uusiMaksuKentat">
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
								<td>'.localize('Maksun määrä').' *</td>
								<td><input type="text" name="summa" value="' . $summa . '" size="10" maxlength="19" class="kentta" id="maksunMaaraKentta" onkeypress="return disableEnterKey(event)"/>EUR</td>
								</tr>
								<tr>
								<td>'.localize('Tilioteteksti').'</td>
								<td>'.localize('tilisiirto').'</td>
								</tr>
								<tr>
								<td>'.localize('Viesti').'</td>
								<td>'.localize("Ylläpitäjän suorittama rahan siirto").'</td>
								</tr>
							</table>
							
							<p id="painikkeet">
							<input type="submit" name="tyhjenna" value='.localize('TYHJENNÄ').' class="painike"/>
							<input type="submit" name="jatka" value='.localize('JATKA').' class="painike"/>
							</p>
						</form>
				</div><!-- uusiMaksuLomake -->';
}
echo '</div>';
?>